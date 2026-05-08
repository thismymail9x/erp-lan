<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\CaseModel;
use App\Models\NotificationModel;

class CronController extends BaseController
{
    /**
     * Cronjob: Nhắc nhở thanh toán vụ việc
     * Chạy định kỳ mỗi ngày 1 lần (VD: 8h sáng)
     * Lệnh: wget -qO- http://my-domain.com/cron/payment-reminders
     */
    public function paymentReminders()
    {
        // Bí mật API key để tránh bị gọi trái phép. Có thể truyền ?key=your_secret_key
        $key = $this->request->getGet('key');
        if ($key !== 'secure_cron_erp_123') {
            return $this->response->setStatusCode(403)->setBody('Access Denied');
        }

        $caseModel = new CaseModel();
        $notificationModel = new NotificationModel();
        $db = \Config\Database::connect();

        // 1. Tìm tất cả user là Admin hoặc Hành chính Kế toán
        $recipients = $db->table('users')
                         ->select('users.id')
                         ->join('employees', 'employees.user_id = users.id', 'left')
                         ->join('auth_groups_users', 'auth_groups_users.user_id = users.id', 'left') // In Myth-Auth, role might be in groups? Wait.
                         // Let's just check the current schema for roles:
                         ->join('roles', 'roles.id = users.role_id', 'left')
                         ->groupStart()
                            ->where('roles.name', \Config\AppConstants::ROLE_ADMIN)
                            ->orWhere('employees.department_id', \Config\AppConstants::DEPT_HANH_CHINH)
                         ->groupEnd()
                         ->where('users.active_status', 1)
                         ->get()
                         ->getResultArray();
        
        $userIds = array_column($recipients, 'id');
        if (empty($userIds)) {
            return $this->response->setJSON(['status' => 'no_recipients']);
        }

        // 2. Load các vụ việc đang mở có payment_progress
        $cases = $caseModel->whereIn('status', [\Config\AppConstants::CASE_STATUS_IN_PROGRESS, \Config\AppConstants::CASE_STATUS_PENDING])
                           ->where('payment_progress IS NOT NULL')
                           ->findAll();
        
        $today = date('Y-m-d');
        $notificationsSent = 0;

        foreach ($cases as $case) {
            $payments = json_decode($case['payment_progress'], true);
            if (!is_array($payments)) continue;

            $updated = false;

            foreach ($payments as &$payment) {
                // Đã thu -> bỏ qua
                $isPaid = isset($payment['is_paid']) && $payment['is_paid'] == 1;
                if ($isPaid) continue;

                // Không có thời hạn -> bỏ qua
                if (empty($payment['deadline'])) continue;

                // So sánh ngày
                $deadlineDate = date('Y-m-d', strtotime($payment['deadline']));
                
                // Nếu deadline <= today và chưa được nhắc nhở trong 24h qua (để tránh spam)
                if ($deadlineDate <= $today) {
                    $lastRemindedKey = 'last_reminded_' . $payment['title'];
                    $lastReminded = isset($payment[$lastRemindedKey]) ? $payment[$lastRemindedKey] : null;

                    if ($lastReminded !== $today) {
                        // Gửi thông báo
                        foreach ($userIds as $userId) {
                            $notificationModel->insert([
                                'user_id' => $userId,
                                'sender_id' => null, // Hệ thống
                                'type' => 'finance_alert',
                                'title' => '⏰ Nhắc nhở thu tiền: ' . escapeshellarg($payment['title']),
                                'message' => "Hồ sơ <b>{$case['code']} - {$case['title']}</b> đã đến hạn (hoặc quá hạn) thanh toán <b>{$payment['title']}</b> số tiền " . number_format($payment['amount'], 0, ',', '.') . " VNĐ. \nHạn chót: " . date('d/m/Y', strtotime($payment['deadline'])),
                                'link' => base_url('finance/cases'),
                                'is_read' => 0
                            ]);
                            $notificationsSent++;
                        }
                        
                        // Đánh dấu đã nhắc trong phần json để khỏi nhắc lại hôm nay
                        $payment[$lastRemindedKey] = $today;
                        $updated = true;
                    }
                }
            }

            // Lưu lại nếu có cập nhật last_reminded
            if ($updated) {
                // Tạm thời tắt tự cập nhật updated_at để tránh trôi nổi hồ sơ
                $db->table('cases')->where('id', $case['id'])->update([
                    'payment_progress' => json_encode($payments, JSON_UNESCAPED_UNICODE)
                ]);
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'notifications_sent' => $notificationsSent
        ]);
    }

    /**
     * Cronjob: Kiểm tra hạn chót quy trình (Workflow Deadlines)
     * Chạy định kỳ mỗi ngày 1 lần.
     * Lệnh: wget -qO- http://my-domain.com/cron/check-workflows?key=secure_cron_erp_123
     */
    public function checkWorkflows()
    {
        $key = $this->request->getGet('key');
        if ($key !== 'secure_cron_erp_123') {
            return $this->response->setStatusCode(403)->setBody('Access Denied');
        }

        $workflowService = new \App\Services\WorkflowService();
        $workflowService->checkStepDeadlines();

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Workflow deadlines checked and notifications dispatched.'
        ]);
    }
}
