<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\CaseStepModel;
use App\Models\SystemLogModel;
use App\Models\EmployeeModel;

/**
 * CaseReminderService
 * 
 * Dịch vụ kiểm tra và gửi nhắc hẹn cho các vụ việc/bước quy trình sắp đến hạn.
 * Xử lý logic 3 ngày, 1 ngày và quá hạn theo yêu cầu.
 */
class CaseReminderService
{
    protected $caseModel;
    protected $stepModel;
    protected $logModel;
    protected $employeeModel;

    public function __construct()
    {
        $this->caseModel = new CaseModel();
        $this->stepModel = new CaseStepModel();
        $this->logModel = new SystemLogModel();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Chạy toàn bộ tiến trình kiểm tra nhắc hẹn
     */
    public function checkAllReminders()
    {
        $activeSteps = $this->stepModel->where('completed_at', null)->findAll();
        $today = new \DateTime();

        foreach ($activeSteps as $step) {
            $deadline = new \DateTime($step['deadline']);
            $interval = $today->diff($deadline);
            $daysLeft = $interval->days * ($interval->invert ? -1 : 1);

            $case = $this->caseModel->find($step['case_id']);
            if (!$case) continue;

            if ($daysLeft === 3) {
                $this->sendReminder($case, $step, 'Cảnh báo: Bước "' . $step['step_name'] . '" còn 3 ngày nữa đến hạn.');
            } elseif ($daysLeft === 1) {
                $this->sendReminder($case, $step, 'KHẨN CẤP: Bước "' . $step['step_name'] . '" sẽ hết hạn vào ngày mai!', true);
            } elseif ($daysLeft < 0 && $step['status'] !== 'overdue') {
                $this->handleOverdue($case, $step);
            }
        }
    }

    /**
     * Gửi thông báo nhắc hẹn
     */
    private function sendReminder($case, $step, $message, $isUrgent = false)
    {
        // 1. Ghi log hệ thống
        $this->logModel->save([
            'user_id' => 0, // Hệ thống tự động
            'action' => 'reminder',
            'details' => $message . ' (Vụ việc: ' . $case['code'] . ')'
        ]);

        // 2. Draft: Gửi Email / Zalo OA / In-app Notification
        // Trong thực tế, gọi API Zalo OA hoặc Mailer tại đây.
        error_log("REMINDER: $message - Case ID: " . $case['id']);
        
        // Cập nhật trạng thái nếu cần hiển thị trên Dashboard "Sắp quá hạn"
        if ($isUrgent) {
            // Logic để đẩy lên Dashboard (ví dụ: gán tag hoặc cập nhật status tạm)
        }
    }

    /**
     * Xử lý khi bước bị quá hạn
     */
    private function handleOverdue($case, $step)
    {
        // 1. Cập nhật trạng thái bước thành overdue
        $this->stepModel->update($step['id'], ['status' => 'overdue']);

        // 2. Thông báo cho trưởng phòng
        $message = 'QUÁ HẠN: Bước "' . $step['step_name'] . '" của vụ việc ' . $case['code'] . ' đã quá hạn xử lý!';
        
        $this->logModel->save([
            'user_id' => 0,
            'action' => 'overdue_alert',
            'details' => $message
        ]);

        // 3. Tạo task hoặc thông báo riêng cho Trưởng phòng
        error_log("OVERDUE ALERT sent to Manager for Step: " . $step['id']);
    }
}
