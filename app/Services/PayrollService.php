<?php

namespace App\Services;

use App\Models\PayrollModel;
use App\Models\PayrollConfigModel;
use App\Models\EmployeeModel;
use App\Models\AttendanceModel;
use App\Models\LeaveRequestModel;
use Config\AppConstants;

/**
 * PayrollService
 * 
 * Nghiệp vụ tính lương, quản lý ngày công và chốt sổ tháng.
 * 
 * Các thuật toán đã triển khai:
 * 1. calcTaxableIncome()           — Tính thu nhập chịu thuế (TNCT) có xử lý chuyển hạng giữa tháng.
 * 2. detectAndCalcRetroPayroll()   — Tự động phát hiện và tính truy lĩnh lương tháng trước cho nhân viên mới.
 * 3. calculateMonthlyPayroll()     — Tính toán bảng lương tổng thể, tích hợp cả 2 thuật toán trên + ngày công bù thủ công.
 */
class PayrollService extends BaseService
{
    protected $payrollModel;
    protected $configModel;
    protected $empModel;
    protected $attModel;
    protected $leaveRequestModel;

    public function __construct()
    {
        parent::__construct();
        $this->payrollModel      = new PayrollModel();
        $this->configModel       = new PayrollConfigModel();
        $this->empModel          = new EmployeeModel();
        $this->attModel          = new AttendanceModel();
        $this->leaveRequestModel = new LeaveRequestModel();
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
            'month'               => $month,
            'working_days_json'   => json_encode($workingDays),
            'holidays_json'       => json_encode([]),
            'total_standard_days' => count($workingDays),
            'is_closed'           => 0
        ];

        $this->configModel->insert($data);
        return $this->configModel->find($this->configModel->getInsertID());
    }

    /**
     * ===================================================================
     * THUẬT TOÁN 1: TÍNH THU NHẬP CHỊU THUẾ (TNCT) CÓ HỖ TRỢ CHUYỂN HẠNG GIỮA THÁNG
     * ===================================================================
     * 
     * Tại sao cần hàm này riêng?
     * → Vì nhân viên thử việc/thực tập/học việc có thể kết thúc giai đoạn vào GIỮA THÁNG.
     *   Ví dụ: hết thử việc ngày 15/06, thì:
     *     - 12 ngày đầu (đến 15/06): tính theo hệ số 85%
     *     - 10 ngày sau (từ 16/06): tính theo hệ số 100%
     * → Cần đếm ngày công trước/sau ngày chuyển hạng rồi tính riêng từng phần.
     * 
     * @param array  $emp              Hồ sơ nhân viên (từ DB)
     * @param array  $attendedDates    Mảng các ngày có chấm công hợp lệ trong tháng ['2026-06-01', ...]
     * @param float  $manualAdjustDays Số ngày công bù thủ công (Admin nhập tay)
     * @param int    $standardDays     Tổng số ngày công chuẩn trong tháng (theo cấu hình)
     * @param string $month            Tháng đang tính lương, định dạng 'YYYY-MM'
     * 
     * @return array [
     *   'taxable_income'          => float,  // Tổng thu nhập chịu thuế
     *   'salary_per_day'          => float,  // Lương 1 ngày công (theo probation_rate)
     *   'probation_rate_snapshot' => float,  // Hệ số % đã áp dụng (để lưu lịch sử)
     *   'transition_note'         => string, // Ghi chú chuyển hạng (rỗng nếu không có)
     * ]
     */
    private function calcTaxableIncome(array $emp, array $attendedDates, float $manualAdjustDays, int $standardDays, string $month): array
    {
        $salaryBase       = (float)($emp['salary_base'] ?? 0);
        $probationRate    = (float)($emp['probation_rate'] ?? 100.0);
        $probationEndDate = $emp['probation_end_date'] ?? null;
        $newRateAfter     = (float)($emp['new_rate_after'] ?? 100.0);

        // Mốc thời gian của tháng đang tính
        $monthStart = $month . '-01';
        $monthEnd   = $month . '-' . date('t', strtotime($monthStart));

        // Kiểm tra: probation_end_date có tồn tại VÀ rơi đúng vào tháng đang tính không?
        // Nếu rơi vào tháng khác → bỏ qua, dùng probation_rate toàn tháng
        $hasTransition = (!empty($probationEndDate))
            && ($probationEndDate >= $monthStart)
            && ($probationEndDate <= $monthEnd);

        if (!$hasTransition) {
            // ===== TRƯỜNG HỢP 1: Toàn tháng một hệ số (trường hợp phổ biến) =====
            // Tính lương thực tế = lương cơ bản × hệ số % / số ngày chuẩn × số ngày thực tế
            $effectiveSalary = $salaryBase * ($probationRate / 100.0);
            $salaryPerDay    = ($standardDays > 0) ? ($effectiveSalary / $standardDays) : 0;
            $totalActualDays = array_sum($attendedDates) + $manualAdjustDays;
            $taxableIncome   = $salaryPerDay * $totalActualDays;

            return [
                'taxable_income'          => $taxableIncome,
                'salary_per_day'          => $salaryPerDay,
                'probation_rate_snapshot' => $probationRate,
                'transition_note'         => '',
            ];
        }

        // ===== TRƯỜNG HỢP 2: Chuyển hạng giữa tháng — chia 2 phần =====
        // Ví dụ: NV hết thử việc ngày 15/06
        //   Phần 1: các ngày <= 15/06  → dùng probation_rate (85%)
        //   Phần 2: các ngày > 15/06   → dùng new_rate_after (100%)
        // Manual adjust days cộng vào phần trước (nhân viên mới, delay thường xảy ra đầu tháng)
        $daysBefore = 0;
        $daysAfter  = 0;

        foreach ($attendedDates as $date => $weight) {
            if ($date <= $probationEndDate) {
                $daysBefore += $weight;
            } else {
                $daysAfter += $weight;
            }
        }

        // Cộng ngày bù vào phần trước ngày chuyển hạng
        $daysBefore += $manualAdjustDays;

        // Tính thu nhập từng phần
        $incomeBefore = ($standardDays > 0)
            ? ($salaryBase * ($probationRate / 100.0) / $standardDays) * $daysBefore
            : 0;

        $incomeAfter = ($standardDays > 0)
            ? ($salaryBase * ($newRateAfter / 100.0) / $standardDays) * $daysAfter
            : 0;

        $taxableIncome = $incomeBefore + $incomeAfter;

        // Lương 1 ngày công hiển thị = theo probation_rate (hệ số chính, trước chuyển hạng)
        $salaryPerDay = ($standardDays > 0) ? ($salaryBase * ($probationRate / 100.0) / $standardDays) : 0;

        // Ghi chú tự động cho trường hợp chuyển hạng
        // Định dạng: "[Chuyển hạng] X ngày hệ số A% + Y ngày hệ số B%"
        $transitionNote = sprintf(
            '[Chuyển hạng] %.1f ngày hệ số %.0f%% + %.1f ngày hệ số %.0f%%',
            $daysBefore,
            $probationRate,
            $daysAfter,
            $newRateAfter
        );

        return [
            'taxable_income'          => $taxableIncome,
            'salary_per_day'          => $salaryPerDay,
            'probation_rate_snapshot' => $probationRate,
            'transition_note'         => $transitionNote,
        ];
    }

    /**
     * ===================================================================
     * THUẬT TOÁN 2: TỰ ĐỘNG PHÁT HIỆN VÀ TÍNH TRUY LĨNH LƯƠNG THÁNG TRƯỚC
     * ===================================================================
     * 
     * Tại sao cần hàm này?
     * → Nhân viên mới vào làm GIỮA THÁNG (ví dụ: ngày 20/05) sẽ không được
     *   tính lương tháng 5 ngay, vì kỳ lương thường chỉ chạy từ đầu tháng sau.
     * → Khi chạy tính lương tháng 6, hệ thống tự phát hiện:
     *   "NV này join 20/05, tháng 5 chưa có phiếu lương → tính truy lĩnh!"
     * → Số tiền truy lĩnh sẽ được cộng vào cột "Điều chỉnh khác" (salary_other) của tháng 6.
     * 
     * Điều kiện kích hoạt (phải thỏa cả hai):
     *   1. join_date của nhân viên thuộc tháng trước (prevMonth)
     *   2. Chưa có bản ghi lương nào trong payrolls cho (employee_id, prevMonth)
     * 
     * Idempotent: Bấm "Tính toán lương" nhiều lần sẽ không tạo truy lĩnh trùng lặp,
     * vì dùng marker '[Truy lĩnh tự động]' trong notes_json để dedup.
     * 
     * @param array  $emp          Hồ sơ nhân viên
     * @param string $currentMonth Tháng đang chạy tính lương ('YYYY-MM')
     * 
     * @return array|null Trả về null nếu không đủ điều kiện, hoặc:
     *   ['amount' => float, 'days' => float, 'month' => string, 'join_date' => string]
     */
    private function detectAndCalcRetroPayroll(array $emp, string $currentMonth): ?array
    {
        $joinDate = $emp['join_date'] ?? null;
        if (empty($joinDate)) {
            return null;
        }

        // Tính tháng trước (prevMonth)
        $prevMonth      = date('Y-m', strtotime($currentMonth . '-01 -1 month'));
        $prevMonthStart = $prevMonth . '-01';
        $prevMonthEnd   = $prevMonth . '-' . date('t', strtotime($prevMonthStart));

        // Điều kiện 1: join_date phải thuộc tháng trước
        if ($joinDate < $prevMonthStart || $joinDate > $prevMonthEnd) {
            return null;
        }

        // Điều kiện 2: Chưa có phiếu lương tháng trước (tránh truy lĩnh kép)
        // Phải thêm deleted_at IS NULL vì PayrollModel dùng Soft Delete (Quy tắc 6)
        $existingPrev = $this->payrollModel
            ->where('employee_id', $emp['id'])
            ->where('month', $prevMonth)
            ->where('deleted_at IS NULL', null, false)
            ->first();

        if ($existingPrev) {
            return null; // Đã có phiếu lương tháng trước → không truy lĩnh nữa
        }

        // Lấy cấu hình ngày công tháng trước
        $prevConfig   = $this->getOrCreateConfig($prevMonth);
        $prevStdDays  = (int)($prevConfig['total_standard_days'] ?: 26);
        $prevWorkDays = json_decode($prevConfig['working_days_json'], true) ?: [];

        // Đếm ngày công thực tế của nhân viên trong tháng trước (từ join_date đến hết tháng)
        $retroAttendances = $this->attModel
            ->where('employee_id', $emp['id'])
            ->where('attendance_date >=', $joinDate)
            ->where('attendance_date <=', $prevMonthEnd)
            ->findAll();

        $prevLeaveRequests = $this->leaveRequestModel
            ->where('employee_id', $emp['id'])
            ->where('status', 'approved')
            ->where('start_date <=', $prevMonthEnd)
            ->where('end_date >=', $joinDate)
            ->findAll();

        $prevLeaveMap = [];
        foreach ($prevLeaveRequests as $lr) {
            $lrStart  = new \DateTime($lr['start_date']);
            $lrEnd    = new \DateTime($lr['end_date']);
            $lrEnd->modify('+1 day');
            $interval = new \DateInterval('P1D');
            $range    = new \DatePeriod($lrStart, $interval, $lrEnd);
            
            $type = $lr['leave_type'];
            $dur  = $lr['leave_duration'] ?? '';
            
            foreach ($range as $d) {
                $ds  = $d->format('Y-m-d');
                if (!isset($prevLeaveMap[$ds])) {
                    $prevLeaveMap[$ds] = ['morning' => null, 'afternoon' => null];
                }
                if ($dur === 'morning_half') {
                    $prevLeaveMap[$ds]['morning'] = $type;
                } elseif ($dur === 'afternoon_half') {
                    $prevLeaveMap[$ds]['afternoon'] = $type;
                } else {
                    $prevLeaveMap[$ds]['morning'] = $type;
                    $prevLeaveMap[$ds]['afternoon'] = $type;
                }
            }
        }

        $prevAttMap = [];
        foreach ($retroAttendances as $att) {
            $prevAttMap[$att['attendance_date']] = $att;
        }

        $retroActualDays = 0;
        $daysBefore      = 0;
        $daysAfter       = 0;

        $startDateObj = new \DateTime($joinDate);
        $endDateObj   = new \DateTime($prevMonthEnd);
        $endDateObj->modify('+1 day');
        $intervalObj  = new \DateInterval('P1D');
        $periodObj    = new \DatePeriod($startDateObj, $intervalObj, $endDateObj);

        $paidLeaveTypes = ['annual', 'wedding', 'funeral'];

        foreach ($periodObj as $dt) {
            $date = $dt->format('Y-m-d');
            if (in_array($date, $prevWorkDays)) {
                $approvedPaidLeave   = 0.0;
                $approvedUnpaidLeave = 0.0;

                if (isset($prevLeaveMap[$date])) {
                    // Morning leave
                    if (!empty($prevLeaveMap[$date]['morning'])) {
                        if (in_array($prevLeaveMap[$date]['morning'], $paidLeaveTypes)) {
                            $approvedPaidLeave += 0.5;
                        } else {
                            $approvedUnpaidLeave += 0.5;
                        }
                    }
                    // Afternoon leave
                    if (!empty($prevLeaveMap[$date]['afternoon'])) {
                        if (in_array($prevLeaveMap[$date]['afternoon'], $paidLeaveTypes)) {
                            $approvedPaidLeave += 0.5;
                        } else {
                            $approvedUnpaidLeave += 0.5;
                        }
                    }
                }

                $attendanceLeave = 0.0;
                if (isset($prevAttMap[$date])) {
                    $status = $prevAttMap[$date]['status'];
                    if ($status === 'LEAVE_FULL_DAY') {
                        $attendanceLeave = 1.0;
                    } elseif ($status === 'LEAVE_MORNING' || $status === 'LEAVE_AFTERNOON') {
                        $attendanceLeave = 0.5;
                    }
                } else {
                    $attendanceLeave = 1.0;
                }

                $unexcusedAbsence = max(0.0, $attendanceLeave - ($approvedPaidLeave + $approvedUnpaidLeave));
                $dayWeight = 1.0 - ($approvedUnpaidLeave + $unexcusedAbsence);

                // Split for transition
                if (!empty($probationEndDate) && ($probationEndDate >= $prevMonthStart) && ($probationEndDate <= $prevMonthEnd)) {
                    if ($date <= $probationEndDate) {
                        $daysBefore += $dayWeight;
                    } else {
                        $daysAfter += $dayWeight;
                    }
                }

                $retroActualDays += $dayWeight;
            }
        }

        // Tính số tiền truy lĩnh
        $salaryBase       = (float)($emp['salary_base'] ?? 0);
        $probationRate    = (float)($emp['probation_rate'] ?? 100.0);
        $probationEndDate = $emp['probation_end_date'] ?? null;
        $newRateAfter     = (float)($emp['new_rate_after'] ?? 100.0);

        $hasTransitionInPrev = (!empty($probationEndDate))
            && ($probationEndDate >= $prevMonthStart)
            && ($probationEndDate <= $prevMonthEnd);

        if (!$hasTransitionInPrev) {
            // Trường hợp thông thường: một hệ số toàn tháng trước
            $retroAmount = ($prevStdDays > 0)
                ? ($salaryBase * ($probationRate / 100.0) / $prevStdDays) * $retroActualDays
                : 0;
        } else {
            // Chuyển hạng cũng rơi vào tháng trước → tính split cho tháng trước
            $retroAmount = ($prevStdDays > 0)
                ? ($salaryBase * ($probationRate / 100.0) / $prevStdDays) * $daysBefore
                  + ($salaryBase * ($newRateAfter / 100.0) / $prevStdDays) * $daysAfter
                : 0;
        }

        return [
            'amount'    => $retroAmount,
            'days'      => $retroActualDays,
            'month'     => $prevMonth,
            'join_date' => $joinDate,
        ];
    }

    /**
     * ===================================================================
     * HÀM CHÍNH: Tính toán bảng lương dự kiến cho toàn bộ nhân viên trong tháng.
     * ===================================================================
     * 
     * Quy trình tổng thể:
     * 1. Quét dữ liệu chấm công → tính ngày công thực tế.
     * 2. Cộng ngày công bù thủ công (manual_adjust_days) nếu Admin đã nhập.
     * 3. Gọi calcTaxableIncome() → tính TNCT chuẩn xác (có hỗ trợ chuyển hạng giữa tháng).
     * 4. Gọi detectAndCalcRetroPayroll() → phát hiện & tính truy lĩnh tháng trước.
     * 5. Bảo toàn các giá trị đã nhập thủ công (Thưởng, Thuế TNCN, Ghi chú).
     * 6. Tổng hợp và lưu vào DB.
     */
    public function calculateMonthlyPayroll($month, $selectedEmployeeIds = [])
    {
        $config = $this->getOrCreateConfig($month);
        if ($config['is_closed']) {
            return $this->fail("Tháng $month đã chốt sổ, không thể tính toán lại.");
        }

        $employees   = $this->empModel
            ->select('employees.*')
            ->join('users', 'users.id = employees.user_id', 'inner')
            ->where('users.active_status', 1)
            ->where('users.deleted_at', null)
            ->findAll();
        $workingDays = json_decode($config['working_days_json'], true) ?: [];
        $holidays    = json_decode($config['holidays_json'], true) ?: [];
        
        $results = [];

        foreach ($employees as $emp) {
            // Nếu có lọc danh sách nhân sự, bỏ qua những người không được chọn
            if (!empty($selectedEmployeeIds) && !in_array($emp['id'], $selectedEmployeeIds)) {
                continue;
            }

            // ---- BƯỚC 1: Thu thập dữ liệu chấm công ----
            $lastDayOfMonth  = date('t', strtotime($month . '-01'));
            $attendances     = $this->attModel
                ->where('employee_id', $emp['id'])
                ->where('attendance_date >=', $month . '-01')
                ->where('attendance_date <=', $month . '-' . $lastDayOfMonth)
                ->findAll();

            // ---- BƯỚC 1b: Tải đơn nghỉ phép đã duyệt trong tháng ----
            $leaveRequests = $this->leaveRequestModel
                ->where('employee_id', $emp['id'])
                ->where('status', 'approved')
                ->where('start_date <=', $month . '-' . $lastDayOfMonth)
                ->where('end_date >=', $month . '-01')
                ->findAll();

            $leaveMap = []; // date (Y-m-d) => ['morning' => leave_type, 'afternoon' => leave_type]
            foreach ($leaveRequests as $lr) {
                $lrStart  = new \DateTime($lr['start_date']);
                $lrEnd    = new \DateTime($lr['end_date']);
                $lrEnd->modify('+1 day');
                $interval = new \DateInterval('P1D');
                $range    = new \DatePeriod($lrStart, $interval, $lrEnd);
                
                $type = $lr['leave_type'];
                $dur  = $lr['leave_duration'] ?? '';
                
                foreach ($range as $d) {
                    $ds  = $d->format('Y-m-d');
                    if (!isset($leaveMap[$ds])) {
                        $leaveMap[$ds] = ['morning' => null, 'afternoon' => null];
                    }
                    if ($dur === 'morning_half') {
                        $leaveMap[$ds]['morning'] = $type;
                    } elseif ($dur === 'afternoon_half') {
                        $leaveMap[$ds]['afternoon'] = $type;
                    } else {
                        $leaveMap[$ds]['morning'] = $type;
                        $leaveMap[$ds]['afternoon'] = $type;
                    }
                }
            }
            
            $actualDays    = 0;
            $violations    = 0;
            $attendedDates = []; // dateStr => weight
            
            $monthStart = $month . '-01';
            $monthEnd   = $month . '-' . $lastDayOfMonth;
            
            $currDate = new \DateTime($monthStart);
            $endDate  = new \DateTime($monthEnd);
            $endDate->modify('+1 day');
            $interval = new \DateInterval('P1D');
            $period   = new \DatePeriod($currDate, $interval, $endDate);
            
            $attMap = [];
            foreach ($attendances as $att) {
                $attMap[$att['attendance_date']] = $att;
            }
            
            $paidLeaveTypes = ['annual', 'wedding', 'funeral'];
            
            foreach ($period as $dt) {
                $date = $dt->format('Y-m-d');
                
                // Chỉ tính nếu là ngày làm việc theo cấu hình, hoặc ngày lễ có hưởng lương
                if (in_array($date, $workingDays)) {
                    $approvedPaidLeave   = 0.0;
                    $approvedUnpaidLeave = 0.0;
                    
                    if (isset($leaveMap[$date])) {
                        // Morning leave
                        if (!empty($leaveMap[$date]['morning'])) {
                            if (in_array($leaveMap[$date]['morning'], $paidLeaveTypes)) {
                                $approvedPaidLeave += 0.5;
                            } else {
                                $approvedUnpaidLeave += 0.5;
                            }
                        }
                        // Afternoon leave
                        if (!empty($leaveMap[$date]['afternoon'])) {
                            if (in_array($leaveMap[$date]['afternoon'], $paidLeaveTypes)) {
                                $approvedPaidLeave += 0.5;
                            } else {
                                $approvedUnpaidLeave += 0.5;
                            }
                        }
                    }
                    
                    $attendanceLeave = 0.0;
                    $hasCheckIn = isset($attMap[$date]);
                    if ($hasCheckIn) {
                        $status = $attMap[$date]['status'];
                        if ($status === 'LEAVE_FULL_DAY') {
                            $attendanceLeave = 1.0;
                        } elseif ($status === 'LEAVE_MORNING' || $status === 'LEAVE_AFTERNOON') {
                            $attendanceLeave = 0.5;
                        }
                    } else {
                        $attendanceLeave = 1.0;
                    }
                    
                    $unexcusedAbsence = max(0.0, $attendanceLeave - ($approvedPaidLeave + $approvedUnpaidLeave));
                    $weight = 1.0 - ($approvedUnpaidLeave + $unexcusedAbsence);
                    if ($weight > 0) {
                        $actualDays += $weight;
                        $attendedDates[$date] = $weight;
                    }
                    
                    if ($hasCheckIn) {
                        $status = $attMap[$date]['status'];
                        if (in_array($status, [AppConstants::ATT_STATUS_LATE, AppConstants::ATT_STATUS_EARLY_LEAVE])) {
                            $violations++;
                        }
                    }
                } elseif (isset($holidays[$date])) {
                    // Ngày lễ/nghỉ có lương
                    $actualDays += 1.0;
                    $attendedDates[$date] = 1.0;
                }
            }

            // ---- BƯỚC 2: Lấy dữ liệu cũ để bảo toàn các giá trị đã nhập tay ----
            $existing = $this->payrollModel
                ->where(['employee_id' => $emp['id'], 'month' => $month])
                ->first();

            // Bảo toàn ngày công bù thủ công (Admin đã nhập) — không reset khi tính lại
            $manualAdjustDays = (float)($existing['manual_adjust_days'] ?? 0);

            // ---- BƯỚC 3: Lấy dữ liệu cấu hình lương từ hồ sơ nhân sự ----
            $salaryBase           = (float)($emp['salary_base'] ?? 0);
            $insuranceSalary      = (float)($emp['insurance_salary'] ?? $salaryBase);
            $diligenceAllowance   = (float)($emp['diligence_allowance'] ?? 0);
            $petrolAllowance      = (float)($emp['petrol_allowance'] ?? 0);
            $responsibilitySalary = (float)($emp['allowance_base'] ?? 0);
            $dependentCount       = (int)($emp['dependent_count'] ?? 0);
            $dependentDeduction   = $dependentCount * AppConstants::DEPENDENT_DEDUCTION_AMOUNT;
            
            // Bảo toàn Thưởng và Thuế TNCN đã nhập tay; KPI reset theo hồ sơ nhân sự.
            // LƯU Ý: salary_other KHÔNG được lấy từ existing vì nó đã bao gồm truy lĩnh tự động kỳ trước.
            // Truy lĩnh luôn được tính lại từ đầu mỗi lần, do đó reset $other về 0 để tránh cộng dồn.
            $kpiReward = $responsibilitySalary;
            if ($existing) {
                $bonus  = (float)($existing['salary_bonus'] ?? 0);
                $other  = 0; // Sẽ được cộng vào bởi detectAndCalcRetroPayroll() bên dưới nếu đủ điều kiện
                $pitTax = (float)($existing['pit_tax'] ?? 0);
            } else {
                $bonus  = 0;
                $other  = 0;
                $pitTax = 0;
            }

            // ---- BƯỚC 4: Tính TNCT (có hỗ trợ chuyển hạng giữa tháng) ----
            $standardDays = (int)($config['total_standard_days'] ?: 26);

            $incomeResult = $this->calcTaxableIncome(
                $emp,
                $attendedDates,
                $manualAdjustDays,
                $standardDays,
                $month
            );

            $taxableIncome         = $incomeResult['taxable_income'];
            $salaryPerDay          = $incomeResult['salary_per_day'];
            $probationRateSnapshot = $incomeResult['probation_rate_snapshot'];
            $transitionNote        = $incomeResult['transition_note'];

            // ---- BƯỚC 5: Tự động phát hiện và tính truy lĩnh tháng trước ----
            $retroResult = $this->detectAndCalcRetroPayroll($emp, $month);

            if ($retroResult !== null && $retroResult['amount'] > 0) {
                // Cộng dồn vào salary_other (không ghi đè — Admin có thể đã điền tay)
                $other += $retroResult['amount'];
            }

            // ---- BƯỚC 6: Tổng hợp tài chính ----
            // Bảo hiểm xã hội tính trên lương đóng BH (không phụ thuộc hệ số probation)
            $siEmployer = $insuranceSalary * AppConstants::SI_RATE_EMPLOYER;
            $siEmployee = $insuranceSalary * AppConstants::SI_RATE_EMPLOYEE;

            // Khấu trừ vi phạm: Đã loại bỏ theo thống nhất mới (không trừ trực tiếp)

            // Tổng lương: TNCT + Phụ cấp CC + Phụ cấp Xăng + KPI + Khác (bao gồm truy lĩnh nếu có)
            $totalSalary = $taxableIncome + $diligenceAllowance + $petrolAllowance + $kpiReward + $bonus + $other;
            
            // Tổng giảm trừ thực tế vào lương: BHXH nhân viên + Thuế TNCN
            $totalDeductions = $siEmployee + $pitTax;
            
            // Lương thực lĩnh = Tổng lương - Tổng giảm trừ
            $netSalary = $totalSalary - $totalDeductions;

            // ---- BƯỚC 7: Xử lý ghi chú tự động ----
            // Bảo toàn ghi chú cũ, thêm/cập nhật ghi chú tự động (chuyển hạng, truy lĩnh)
            $existingNotesRaw = $existing['notes_json'] ?? '[]';
            $notes = json_decode($existingNotesRaw, true);
            if (!is_array($notes)) {
                $notes = [];
            }
            $autoNoteDate = date('d/m/Y H:i');

            // Xóa các ghi chú tự động cũ (có marker) để tránh trùng lặp khi bấm tính lại
            $notes = array_values(array_filter($notes, function($n) {
                $text = $n['text'] ?? '';
                return (strpos($text, '[Chuyển hạng]') === false)
                    && (strpos($text, '[Truy lĩnh tự động]') === false);
            }));

            // Thêm ghi chú chuyển hạng nếu có
            if (!empty($transitionNote)) {
                $notes[] = ['text' => $transitionNote, 'date' => $autoNoteDate, 'auto' => true];
            }

            // Thêm ghi chú truy lĩnh nếu có
            if ($retroResult !== null && $retroResult['amount'] > 0) {
                $retroJoinDate   = date('d/m', strtotime($retroResult['join_date']));
                $retroMonthLabel = date('m/Y', strtotime($retroResult['month'] . '-01'));
                $notes[] = [
                    'text' => sprintf(
                        '[Truy lĩnh tự động] Lương tháng %s (vào làm %s): %.1f ngày = %s đ',
                        $retroMonthLabel,
                        $retroJoinDate,
                        $retroResult['days'],
                        number_format($retroResult['amount'])
                    ),
                    'date' => $autoNoteDate,
                    'auto' => true,
                ];
            }

            // ---- BƯỚC 8: Chuẩn bị dữ liệu ghi DB ----
            $payrollData = [
                'employee_id'             => $emp['id'],
                'month'                   => $month,
                'salary_base'             => $salaryBase,
                'insurance_salary'        => $insuranceSalary,
                'salary_kpi'              => $kpiReward,
                'diligence_allowance'     => $diligenceAllowance,
                'petrol_allowance'        => $petrolAllowance,
                'salary_bonus'            => $bonus,
                'salary_deduction'        => 0, // Đã loại bỏ phạt trực tiếp
                'salary_other'            => $other,
                'notes_json'              => json_encode($notes, JSON_UNESCAPED_UNICODE),
                'total_standard_days'     => $standardDays,
                'salary_per_day'          => $salaryPerDay,
                'actual_working_days'     => $actualDays,
                'manual_adjust_days'      => $manualAdjustDays,
                'probation_rate_snapshot' => $probationRateSnapshot,
                'taxable_income'          => $taxableIncome,
                'attendance_violations'   => $violations,
                'si_employer'             => $siEmployer,
                'si_employee'             => $siEmployee,
                'dependent_deduction'     => $dependentDeduction,
                'pit_tax'                 => $pitTax,
                'total_deductions'        => $totalDeductions,
                'net_salary'              => $netSalary,
                'status'                  => 'pending'
            ];

            if ($existing) {
                $this->payrollModel->update($existing['id'], $payrollData);
            } else {
                $this->payrollModel->insert($payrollData);
            }
            
            $results[] = $payrollData;
        }

        return $this->success($results, "Đã tính toán bảng lương tháng $month thành công.");
    }
}
