<?php

namespace App\Services;

use App\Models\PayrollModel;
use App\Models\PayrollConfigModel;
use App\Models\EmployeeModel;
use App\Models\AttendanceModel;
use App\Models\CaseModel;
use Config\AppConstants;

/**
 * PayrollService
 * 
 * Nghiệp vụ tính lương, quản lý ngày công và chốt sổ tháng.
 */
class PayrollService extends BaseService
{
    protected $payrollModel;
    protected $configModel;
    protected $empModel;
    protected $attModel;

    public function __construct()
    {
        parent::__construct();
        $this->payrollModel = new PayrollModel();
        $this->configModel = new PayrollConfigModel();
        $this->empModel = new EmployeeModel();
        $this->attModel = new AttendanceModel();
    }

    /**
     * Lấy hoặc tạo cấu hình tháng.
     * Mặc định: Thứ 7 cách tuần (1 tuần làm, 1 tuần nghỉ).
     * Thuật toán: Dựa vào số tuần trong năm (ISO Week). 
     * Các tuần chẵn sẽ được gợi ý đi làm thứ 7, tuần lẻ nghỉ.
     */
    public function getOrCreateConfig($month)
    {
        $config = $this->configModel->where('month', $month)->first();
        if ($config) {
            return $config;
        }

        // Tạo cấu hình mặc định nếu tháng này chưa từng được thiết lập
        $workingDays = [];
        $daysInMonth = date('t', strtotime($month . '-01'));
        
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = $month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
            $dayOfWeek = date('N', strtotime($dateStr)); // 1 (Thứ 2) -> 7 (Chủ nhật)
            
            if ($dayOfWeek <= 5) {
                // Thứ 2 đến Thứ 6 luôn là ngày làm việc mặc định
                $workingDays[] = $dateStr;
            } elseif ($dayOfWeek == 6) {
                // Thứ 7: Thực hiện tính toán cách tuần
                $weekNum = (int)date('W', strtotime($dateStr));
                if ($weekNum % 2 == 0) {
                    $workingDays[] = $dateStr;
                }
            }
            // Chủ nhật luôn mặc định là ngày nghỉ
        }

        $data = [
            'month' => $month,
            'working_days_json' => json_encode($workingDays),
            'holidays_json' => json_encode([]),
            'total_standard_days' => count($workingDays),
            'is_closed' => 0
        ];

        $this->configModel->insert($data);
        return $this->configModel->find($this->configModel->getInsertID());
    }

    /**
     * Tính toán bảng lương dự kiến cho toàn bộ nhân viên trong tháng.
     * Quy trình: 
     * 1. Quét dữ liệu chấm công để tính ngày công thực tế và vi phạm.
     * 2. Lấy các khoản lương cứng và phụ cấp từ hồ sơ nhân sự.
     * 3. Bảo toàn các giá trị "KPI thi đua" và "Thưởng" đã được Admin nhập tay trước đó.
     */
    public function calculateMonthlyPayroll($month)
    {
        $config = $this->getOrCreateConfig($month);
        if ($config['is_closed']) {
            return $this->fail("Tháng $month đã chốt sổ, không thể tính toán lại.");
        }

        $employees = $this->empModel->findAll();
        $workingDays = json_decode($config['working_days_json'], true) ?: [];
        $holidays = json_decode($config['holidays_json'], true) ?: [];
        
        $results = [];

        foreach ($employees as $emp) {
            // 1. Thu thập dữ liệu chấm công thực tế
            $attendances = $this->attModel->where('employee_id', $emp['id'])
                ->where('attendance_date >=', $month . '-01')
                ->where('attendance_date <=', $month . '-' . date('t', strtotime($month . '-01')))
                ->findAll();
            
            $actualDays = 0;
            $violations = 0;
            $attendedDates = [];
            
            foreach ($attendances as $att) {
                $status = $att['status'];
                $date = $att['attendance_date'];
                
                // Nếu là ngày làm việc chuẩn (hoặc thứ 7 cách tuần)
                if (in_array($date, $workingDays)) {
                    if ($status === 'LEAVE_MORNING' || $status === 'LEAVE_AFTERNOON') {
                        $actualDays += 0.5;
                    } else if (str_starts_with($status, 'LEAVE_')) {
                        $actualDays += 1;
                    } else {
                        // Có đi làm thực tế
                        $actualDays += 1;
                        if (in_array($status, [AppConstants::ATT_STATUS_LATE, AppConstants::ATT_STATUS_EARLY_LEAVE])) {
                            $violations++;
                        }
                    }
                } else if (isset($holidays[$date])) {
                    // Ngày lễ/nghỉ có lương
                    $actualDays += 1;
                }
            }

            // 2. Lấy dữ liệu cấu hình từ hồ sơ nhân sự
            $salaryBase = $emp['salary_base'] ?? 0;
            $insuranceSalary = $emp['insurance_salary'] ?? $salaryBase; // Mặc định bằng lương cơ bản nếu chưa nhập
            $diligenceAllowance = $emp['diligence_allowance'] ?? 0;
            $petrolAllowance = $emp['petrol_allowance'] ?? 0;
            $dependentCount = $emp['dependent_count'] ?? 0;

            // 3. Tính toán các chỉ số
            $standardDays = $config['total_standard_days'] ?: 26;
            
            // Lương 1 ngày công
            $salaryPerDay = ($standardDays > 0) ? $salaryBase / $standardDays : 0;
            
            // Lương theo ngày công làm việc (Số tiền TNCT)
            $taxableIncome = $salaryPerDay * $actualDays;
            
            // Bảo hiểm
            $siEmployer = $insuranceSalary * AppConstants::SI_RATE_EMPLOYER;
            $siEmployee = $insuranceSalary * AppConstants::SI_RATE_EMPLOYEE;
            
            // Giảm trừ phụ thuộc
            $dependentDeduction = $dependentCount * AppConstants::DEPENDENT_DEDUCTION_AMOUNT;
            
            // KPI và Thưởng (Để mặc định là 0 nếu chưa có dữ liệu cũ)
            $kpiReward = 0; 
            $bonus = 0;
            $salaryOther = 0;
            $pitTax = 0; // Tạm thời để 0, Admin có thể nhập tay hoặc hệ thống tính sau

            // Khấu trừ vi phạm: Mặc định mỗi lần vi phạm trừ theo mức quy định
            $penaltyDeduction = $violations * AppConstants::PENALTY_ATTENDANCE_VIOLATION;
            
            // Kiểm tra xem đã có dữ liệu cũ chưa để bảo toàn
            $existing = $this->payrollModel->where(['employee_id' => $emp['id'], 'month' => $month])->first();
            if ($existing) {
                $kpiReward = $existing['salary_kpi'] ?? 0;
                // Merge data từ salary_other vào bonus nếu chưa được merge
                $bonus = ($existing['salary_bonus'] ?? 0) + ($existing['salary_other'] ?? 0);
                $pitTax = $existing['pit_tax'] ?? 0;
                // Bảo toàn phụ cấp nếu đã được nhập tay
                $diligenceAllowance = $existing['diligence_allowance'] ?? $diligenceAllowance;
                $petrolAllowance = $existing['petrol_allowance'] ?? $petrolAllowance;
            }

            // Tổng lương (Theo ảnh: Lương TNCT + Phụ cấp CC + Phụ cấp Xăng + KPI)
            $totalSalary = $taxableIncome + $diligenceAllowance + $petrolAllowance + $kpiReward;
            
            // Tổng cộng các khoản giảm trừ (Theo ảnh: BHXH + Giảm trừ PT + Thuế TNCN)
            $totalDeductions = $siEmployee + $dependentDeduction + $pitTax + $penaltyDeduction;
            
            // Lương thực lĩnh = Tổng lương + Thưởng (đã bao gồm phát sinh) - Tổng giảm trừ
            $netSalary = $totalSalary + $bonus - $totalDeductions;

            $payrollData = [
                'employee_id' => $emp['id'],
                'month' => $month,
                'salary_base' => $salaryBase,
                'insurance_salary' => $insuranceSalary,
                'salary_kpi' => $kpiReward, 
                'diligence_allowance' => $diligenceAllowance,
                'petrol_allowance' => $petrolAllowance,
                'salary_bonus' => $bonus,
                'salary_deduction' => $penaltyDeduction,
                'total_standard_days' => $standardDays,
                'salary_per_day' => $salaryPerDay,
                'actual_working_days' => $actualDays,
                'taxable_income' => $taxableIncome,
                'attendance_violations' => $violations,
                'si_employer' => $siEmployer,
                'si_employee' => $siEmployee,
                'dependent_deduction' => $dependentDeduction,
                'pit_tax' => $pitTax,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary,
                'status' => 'pending'
            ];

            if ($existing) {
                $payrollData['notes_json'] = $existing['notes_json'] ?? '[]';
                $this->payrollModel->update($existing['id'], $payrollData);
            } else {
                $this->payrollModel->insert($payrollData);
            }
            
            $results[] = $payrollData;
        }

        return $this->success($results, "Đã tính toán bảng lương tháng $month thành công.");
    }
}
