<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\CaseStepModel;
use App\Models\SystemLogModel;
use App\Models\EmployeeModel;

/**
 * CaseReminderService
 * 
 * Dịch vụ Tự động: Quản lý nhắc hẹn và Cảnh báo tiến độ vụ việc.
 * Chức năng:
 * 1. Quét toàn bộ các bước quy trình đang diễn ra.
 * 2. Phân tích Deadline để đưa ra các mức cảnh báo (3 ngày, 1 ngày).
 * 3. Tự động chuyển trạng thái "Quá hạn" và thông báo cho cấp quản lý.
 * Thường được chạy qua Cronjob hoặc các Trigger định kỳ.
 */
class CaseReminderService
{
    protected $caseModel;
    protected $stepModel;
    protected $logModel;
    protected $employeeModel;

    public function __construct()
    {
        // Khởi tạo các Model dữ liệu cần thiết để truy xuất thông tin Hồ sơ và Nhân sự
        $this->caseModel = new CaseModel();
        $this->stepModel = new CaseStepModel();
        $this->logModel = new SystemLogModel();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Engine chính: Kiểm tra toàn bộ các nhắc hẹn trên hệ thống.
     * Duyệt qua các bước chưa được đánh dấu hoàn thành (completed_at == null).
     */
    public function checkAllReminders()
    {
        // 1. Lọc các bước còn đang hoạt động
        $activeSteps = $this->stepModel->where('completed_at', null)->findAll();
        $today = new \DateTime();

        foreach ($activeSteps as $step) {
            $deadline = new \DateTime($step['deadline']);
            $interval = $today->diff($deadline);
            
            // Tính số ngày còn lại (Số âm nếu đã quá hạn)
            $daysLeft = $interval->days * ($interval->invert ? -1 : 1);

            // Truy xuất thông tin vụ việc gốc
            $case = $this->caseModel->find($step['case_id']);
            if (!$case) continue;

            // --- CHIẾN LƯỢC NHẮC HẸN (Notification Strategy) ---
            
            // Mức 1: Cảnh báo sớm (3 ngày trước Deadline) - Giúp nhân viên chủ động sắp xếp
            if ($daysLeft === 3) {
                $this->sendReminder($case, $step, 'Cảnh báo: Bước "' . $step['step_name'] . '" còn 3 ngày nữa đến hạn.');
            } 
            // Mức 2: Cảnh báo khẩn (1 ngày trước Deadline) - Cần hoàn tất ngay
            elseif ($daysLeft === 1) {
                $this->sendReminder($case, $step, 'KHẨN CẤP: Bước "' . $step['step_name'] . '" sẽ hết hạn vào ngày mai!', true);
            } 
            // Mức 3: Đã quá hạn - Cần xử lý hậu quả và báo cáo quản lý
            elseif ($daysLeft < 0 && $step['status'] !== 'overdue') {
                $this->handleOverdue($case, $step);
            }
        }
    }

    /**
     * Gửi thông báo nhắc hẹn đa kênh (In-app, Log, Email).
     * 
     * @param array $case Mảng dữ liệu vụ việc.
     * @param array $step Mảng dữ liệu bước quy trình.
     * @param string $message Nội dung thông báo.
     * @param bool $isUrgent Cờ đánh dấu mức độ khẩn cấp.
     */
    private function sendReminder($case, $step, $message, $isUrgent = false)
    {
        // 1. Lưu vết vào Nhật ký hệ thống (Audit Trail) cho mục đích hậu kiểm
        $this->logModel->save([
            'user_id' => 0, // Gán ID = 0 để định danh đây là hành động tự động của System
            'action'  => 'REMINDER',
            'details' => $message . ' (Mã vụ việc: ' . $case['code'] . ')'
        ]);

        // 2. Tương tác đa phương tiện (Mục tiêu tương lai)
        // Ghi lại vào error_log của server để SysAdmin theo dõi tiến độ Cronjob
        error_log("REMINDER LOG: $message - Case ID: " . $case['id']);
        
        // TODO: Tích hợp Send email hoặc đẩy thông báo qua Zalo OA tại đây.
    }

    /**
     * Quy trình xử lý hồ sơ Quá hạn (Overdue Escalation).
     * Đánh dấu vào database và kích hoạt cảnh báo cấp phòng ban/công ty.
     */
    private function handleOverdue($case, $step)
    {
        // 1. Chốt trạng thái 'overdue' trong database để hiển thị Badge đỏ trên UI
        $this->stepModel->update($step['id'], ['status' => 'overdue']);

        // 2. Nội dung cảnh báo vi phạm Deadline
        $message = 'VI PHẠM TIẾN ĐỘ: Bước "' . $step['step_name'] . '" của vụ việc ' . $case['code'] . ' đã quá hạn xử lý!';
        
        // 3. Ghi log cảnh báo nghiêm trọng
        $this->logModel->save([
            'user_id' => 0,
            'action'  => 'OVERDUE_ALERT',
            'details' => $message
        ]);

        // 4. Leo thang thông báo (Escalation Path)
        // Ghi nhận lỗi hệ thống để Trưởng phòng có thể xem trong Dashboard quản trị.
        error_log("CRITICAL OVERDUE: " . $case['code'] . " - Step ID: " . $step['id']);
    }
}
