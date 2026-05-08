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
                $attendedDates[] = $att['attendance_date'];
                
                // Đếm các lỗi vi phạm điểm danh (Muộn/Về sớm) để làm cơ sở trừ lương
                if (in_array($att['status'], [AppConstants::ATT_STATUS_LATE, AppConstants::ATT_STATUS_EARLY_LEAVE])) {
                    $violations++;
                }
            }
            
            $attendedDates = array_unique($attendedDates);

            foreach ($workingDays as $wDate) {
                if (in_array($wDate, $attendedDates)) {
                    // Được tính công nếu có điểm danh trong ngày làm việc
                    $actualDays += 1;
                } else if (isset($holidays[$wDate])) {
                    // Nếu không có điểm danh nhưng là ngày nghỉ lễ được Admin cấu hình thì vẫn tính công (hưởng nguyên lương)
                    $actualDays += 1;
                }
            }

            // 2. Lương KPI (KPI thi đua): Theo yêu cầu mới, phần này không tính tự động mà do Admin nhập tay.
            // Nếu là lần đầu tính toán, mặc định sẽ là 0.
            $kpiReward = 0; 

            // 3. Tính toán dòng lương (Financial Calculation)
            $salaryBase = $emp['salary_base'] ?? 0;
            $allowance = $emp['allowance_base'] ?? 0;
            
            // Công thức lương theo ngày công: (Lương CB / Tổng ngày công chuẩn) * Số ngày đi làm thực tế
            $standardDays = $config['total_standard_days'] ?: 26;
            $salaryByWork = ($standardDays > 0) ? ($salaryBase / $standardDays) * $actualDays : 0;
            
            // Khấu trừ vi phạm: Mặc định mỗi lần vi phạm trừ theo mức quy định
            $deduction = $violations * AppConstants::PENALTY_ATTENDANCE_VIOLATION;
            
            // Tổng thực lĩnh ban đầu
            $netSalary = $salaryByWork + $kpiReward + $allowance - $deduction;

            $payrollData = [
                'employee_id' => $emp['id'],
                'month' => $month,
                'salary_base' => $salaryBase,
                'salary_kpi' => $kpiReward, 
                'salary_allowance' => $allowance,
                'salary_deduction' => $deduction,
                'total_standard_days' => $standardDays,
                'actual_working_days' => $actualDays,
                'attendance_violations' => $violations,
                'net_salary' => $netSalary,
                'status' => 'pending'
            ];

            // Xử lý cập nhật: Nếu đã có bản ghi bảng lương, ta phải giữ lại các cột dữ liệu nhập tay (KPI, Thưởng, Ghi chú, Phát sinh)
            // Cột 'Khấu trừ' (salary_deduction) sẽ được tính lại dựa trên dữ liệu vi phạm điểm danh mới nhất.
            // Nếu Admin muốn phạt thêm, nên sử dụng cột 'Phát sinh' (salary_other) với giá trị âm.
            $existing = $this->payrollModel->where(['employee_id' => $emp['id'], 'month' => $month])->first();
            if ($existing) {
                $payrollData['salary_kpi'] = $existing['salary_kpi'];
                $payrollData['salary_bonus'] = $existing['salary_bonus'];
                $payrollData['salary_other'] = $existing['salary_other'] ?? 0;
                $payrollData['notes_json'] = $existing['notes_json'] ?? '[]';
                $payrollData['notes'] = $existing['notes'];
                
                // Tính lại thực lĩnh (sử dụng $deduction mới được tính từ vi phạm điểm danh)
                $payrollData['net_salary'] = $salaryByWork + $existing['salary_kpi'] + $allowance + $existing['salary_bonus'] - $deduction + $payrollData['salary_other'];
                
                $this->payrollModel->update($existing['id'], $payrollData);
            } else {
                $this->payrollModel->insert($payrollData);
            }
            
            $results[] = $payrollData;
        }

        return $this->success($results, "Đã tính toán bảng lương tháng $month thành công.");
    }
}
