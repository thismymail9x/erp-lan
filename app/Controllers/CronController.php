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
                         ->join('employees', 'employees.user_id = users.id AND employees.deleted_at IS NULL', 'left')
                         ->join('auth_groups_users', 'auth_groups_users.user_id = users.id', 'left') // In Myth-Auth, role might be in groups? Wait.
                         // Let's just check the current schema for roles:
                         ->join('roles', 'roles.id = users.role_id', 'left')
                         ->groupStart()
                            ->where('roles.name', \Config\AppConstants::ROLE_ADMIN)
                            ->orWhere('employees.department_id', \Config\AppConstants::DEPT_HANH_CHINH)
                         ->groupEnd()
                         ->where('users.active_status', 1)
                         ->where('users.deleted_at', null)
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

    /**
     * Cronjob: Tự động quét và đồng bộ lịch sử hội thoại Zalo OA trong 7 ngày qua
     * Lệnh chạy CLI/Wget định kỳ:
     * wget -qO- http://my-domain.com/cron/sync-zalo-conversations?key=secure_cron_erp_123
     */
    public function syncZaloConversations()
    {
        $key = $this->request->getGet('key');
        if ($key !== 'secure_cron_erp_123') {
            return $this->response->setStatusCode(403)->setBody('Access Denied');
        }

        $followerModel = new \App\Models\ZaloFollowerModel();
        
        // Quét các hội thoại có tương tác hoặc cập nhật trong vòng 7 ngày qua
        $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $activeFollowers = $followerModel->where('updated_at >=', $sevenDaysAgo)
                                         ->findAll();

        $zaloService = new \App\Services\ZaloService();
        $syncedCount = 0;
        $totalNewMessages = 0;
        $failedFollowers = [];

        foreach ($activeFollowers as $follower) {
            $zaloId = $follower['zalo_id'];
            if (empty($zaloId)) continue;

            // Đồng bộ cuộc trò chuyện của khách hàng trong 7 ngày gần nhất
            $syncResult = $zaloService->syncConversation($zaloId, 7);
            
            if ($syncResult['status'] === 'success') {
                $syncedCount++;
                $totalNewMessages += $syncResult['count'];
            } else {
                $failedFollowers[] = [
                    'display_name' => $follower['display_name'],
                    'zalo_id'      => $zaloId,
                    'error'        => $syncResult['message']
                ];
            }
        }

        // Ghi log kết quả đồng bộ tự động
        log_message('notice', "Zalo Cronjob: Đã quét đồng bộ xong. Thành công: {$syncedCount}/" . count($activeFollowers) . " hội thoại. Số tin nhắn mới kéo về: {$totalNewMessages}.");

        return $this->response->setJSON([
            'status'             => 'success',
            'scanned_followers'  => count($activeFollowers),
            'synced_followers'   => $syncedCount,
            'total_new_messages' => $totalNewMessages,
            'failures'           => $failedFollowers
        ]);
    }

    /**
     * Cronjob: Kiểm tra và thu hồi các Lead quá hạn phản hồi 2h.
     * Chạy định kỳ mỗi 5-15 phút.
     * Lệnh: wget -qO- http://my-domain.com/cron/check-chat-deadlines?key=secure_cron_erp_123
     */
    public function checkChatDeadlines()
    {
        // Kiểm tra mã bảo mật API key để tránh bị kích hoạt trái phép
        $key = $this->request->getGet('key');
        if ($key !== 'secure_cron_erp_123') {
            return $this->response->setStatusCode(403)->setBody('Access Denied');
        }

        // Khởi tạo dịch vụ điều phối phân công lead chat
        $assignmentService = new \App\Services\ChatAssignmentService();
        
        // Tiến hành xử lý và phân công lại các lead quá hạn phản hồi 2h
        $result = $assignmentService->processOverdueLeads();

        // Tích hợp thêm: Quét và cập nhật trạng thái trễ hạn SLA chăm sóc khách hàng
        $slaService = new \App\Services\CustomerSlaService();
        $overdueCount = $slaService->checkAndTriggerOverdueSlas();
        
        $result['triggered_customer_sla_count'] = $overdueCount;

        // Trả về kết quả xử lý dưới dạng JSON để thuận tiện giám sát
        return $this->response->setJSON($result);
    }

    /**
     * Cronjob: Nhắc nhở chăm sóc khách hàng (tasks quá hạn + khách cần follow-up)
     * Chạy định kỳ mỗi ngày 1 lần vào 9h sáng
     * Lệnh: wget -qO- http://my-domain.com/cron/care-reminders?key=secure_cron_erp_123
     */
    public function careReminders()
    {
        $key = $this->request->getGet('key');
        if ($key !== 'secure_cron_erp_123') {
            return $this->response->setStatusCode(403)->setBody('Access Denied');
        }

        $db = \Config\Database::connect();
        $notificationModel = new \App\Models\NotificationModel();
        $careService = new \App\Services\CustomerCareService();
        $today = date('Y-m-d');
        $notificationsSent = 0;

        // 1. Quét các công việc CSKH chưa hoàn thành và đã quá hạn
        $overdueTasks = $db->table('customer_care_tasks')
                           ->select('customer_care_tasks.*, customers.name as customer_name, customer_care_plans.assigned_staff_id, employees.user_id as user_id')
                           ->join('customer_care_plans', 'customer_care_plans.id = customer_care_tasks.care_plan_id AND customer_care_plans.deleted_at IS NULL')
                           ->join('customers', 'customers.id = customer_care_tasks.customer_id AND customers.deleted_at IS NULL')
                           ->join('employees', 'employees.id = customer_care_plans.assigned_staff_id AND employees.deleted_at IS NULL', 'left')
                           ->where('customer_care_tasks.is_completed', 0)
                           ->where('customer_care_tasks.due_date <', $today)
                           ->where('customer_care_tasks.deleted_at', null)
                           ->get()
                           ->getResultArray();

        foreach ($overdueTasks as $task) {
            if (!empty($task['user_id'])) {
                $notificationModel->insert([
                    'user_id'   => $task['user_id'],
                    'sender_id' => null, // Hệ thống
                    'type'      => 'care_alert',
                    'title'     => '⏰ Công việc CSKH quá hạn: ' . $task['title'],
                    'message'   => "Công việc CSKH cho khách hàng <b>{$task['customer_name']}</b> đã quá hạn từ ngày " . date('d/m/Y', strtotime($task['due_date'])) . ". Vui lòng kiểm tra và hoàn tất sớm.",
                    'link'      => base_url('customer-care/care-plan/' . $task['customer_id']),
                    'is_read'   => 0
                ]);
                $notificationsSent++;
            }
        }

        // 2. Quét khách hàng đã hoàn thành dịch vụ 7 ngày mà chưa có kế hoạch chăm sóc
        $needingFollowUp = $careService->getCustomersNeedingFollowUp(7);
        foreach ($needingFollowUp as $cust) {
            $staffId = $cust['assigned_care_staff_id'] ?? null;
            if ($staffId) {
                $emp = $db->table('employees')->where('id', $staffId)->where('deleted_at', null)->get()->getRowArray();
                if ($emp && !empty($emp['user_id'])) {
                    $notificationModel->insert([
                        'user_id'   => $emp['user_id'],
                        'sender_id' => null, // Hệ thống
                        'type'      => 'care_alert',
                        'title'     => '👤 Khách hàng cần follow-up: ' . $cust['name'],
                        'message'   => "Khách hàng <b>{$cust['name']}</b> đã hoàn thành dịch vụ được 7 ngày nhưng chưa được khởi tạo kế hoạch chăm sóc. Vui lòng kiểm tra ngay.",
                        'link'      => base_url('customer-care/care-plan/' . $cust['id']),
                        'is_read'   => 0
                    ]);
                    $notificationsSent++;
                }
            }
        }

        return $this->response->setJSON([
            'status'             => 'success',
            'notifications_sent' => $notificationsSent
        ]);
    }

    /**
     * Cronjob: Chúc mừng sinh nhật khách hàng và nhắc nhở nhân viên
     * Chạy định kỳ mỗi ngày 1 lần vào 7h sáng
     * Lệnh: wget -qO- http://my-domain.com/cron/birthday-greetings?key=secure_cron_erp_123
     */
    public function birthdayGreetings()
    {
        $key = $this->request->getGet('key');
        if ($key !== 'secure_cron_erp_123') {
            return $this->response->setStatusCode(403)->setBody('Access Denied');
        }

        $db = \Config\Database::connect();
        $notificationModel = new \App\Models\NotificationModel();
        $taskModel = new \App\Models\CustomerCareTaskModel();
        $planModel = new \App\Models\CustomerCarePlanModel();
        $careService = new \App\Services\CustomerCareService();

        // Lấy danh sách khách hàng có sinh nhật vào hôm nay
        $birthdaysToday = $careService->getUpcomingBirthdays(0);
        $greetingsSent = 0;

        foreach ($birthdaysToday as $cust) {
            $staffId = $cust['assigned_care_staff_id'] ?? null;
            if ($staffId) {
                $emp = $db->table('employees')->where('id', $staffId)->where('deleted_at', null)->get()->getRowArray();
                if ($emp && !empty($emp['user_id'])) {
                    // 1. Gửi thông báo cho nhân viên chăm sóc
                    $notificationModel->insert([
                        'user_id'   => $emp['user_id'],
                        'sender_id' => null, // Hệ thống
                        'type'      => 'birthday_alert',
                        'title'     => '🎉 Sinh nhật khách hàng hôm nay: ' . $cust['name'],
                        'message'   => "Hôm nay là sinh nhật của khách hàng <b>{$cust['name']}</b> (SĐT: {$cust['phone']}). Vui lòng gọi điện hoặc gửi tin nhắn chúc mừng khách hàng.",
                        'link'      => base_url('customer-care/loyalty/' . $cust['id']),
                        'is_read'   => 0
                    ]);

                    // 2. Tự động thêm một công việc đặc biệt vào kế hoạch đang chạy (nếu có)
                    $activePlan = $planModel->where('customer_id', $cust['id'])
                                            ->where('status', 'in_progress')
                                            ->where('deleted_at', null)
                                            ->first();
                    if ($activePlan) {
                        $taskModel->save([
                            'care_plan_id' => $activePlan['id'],
                            'customer_id'  => $cust['id'],
                            'task_type'    => 'birthday_wish',
                            'title'        => 'Chúc mừng sinh nhật khách hàng',
                            'description'  => 'Gọi điện hoặc gửi tin nhắn chúc mừng sinh nhật khách hàng, cập nhật quà tặng/chương trình ưu đãi.',
                            'channel'      => 'zalo',
                            'is_completed' => 0,
                            'due_date'     => date('Y-m-d'),
                            'sort_order'   => 0
                        ]);
                    }

                    $greetingsSent++;
                }
            }
        }

        return $this->response->setJSON([
            'status'         => 'success',
            'greetings_sent' => $greetingsSent
        ]);
    }
}
