<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\CustomerSlaSettingModel;
use App\Models\CustomerSlaHistoryModel;
use App\Models\EmployeeModel;

/**
 * CustomerSlaService
 * 
 * Lớp dịch vụ trung tâm xử lý 100% logic nghiệp vụ liên quan đến SLA và Trạng thái Chăm sóc Khách hàng.
 * Đảm bảo cơ chế chuyển đổi trạng thái động, tính toán hạn chót SLA,
 * quét trễ hạn gửi cảnh báo đỏ và lập báo cáo hiệu suất nhân sự.
 * 
 * Tuân thủ Rule #1 (Việt hóa 100%), Rule #2 (Strict MVC), Rule #6 (deleted_at Soft Delete).
 */
class CustomerSlaService extends BaseService
{
    protected $customerModel;
    protected $settingModel;
    protected $historyModel;
    protected $employeeModel;
    protected $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->customerModel       = new CustomerModel();
        $this->settingModel        = new CustomerSlaSettingModel();
        $this->historyModel        = new CustomerSlaHistoryModel();
        $this->employeeModel       = new EmployeeModel();
        $this->notificationService = new NotificationService();
    }

    /**
     * Chuyển trạng thái chăm sóc/tư vấn của khách hàng và tính toán lại SLA.
     * 
     * @param int $customerId ID khách hàng
     * @param string $newStatusKey Mã định danh trạng thái mới (status_key)
     * @param int|null $operatorId ID tài khoản người thực hiện (user_id)
     * @return array Kết quả xử lý chuẩn hóa
     */
    public function transitionStatus(int $customerId, string $newStatusKey, ?int $operatorId = null)
    {
        // 1. Kiểm tra sự tồn tại của khách hàng
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return $this->fail('Khách hàng không tồn tại trên hệ thống.');
        }

        // 2. Tra cứu cấu hình trạng thái mới từ bảng customer_sla_settings
        $setting = $this->settingModel->where('status_key', $newStatusKey)
                                      ->where('is_active', 1)
                                      ->where('deleted_at', null)
                                      ->first();
        if (!$setting) {
            return $this->fail('Trạng thái mới không hợp lệ hoặc đã bị khóa.');
        }

        $now = date('Y-m-d H:i:s');
        $db = \Config\Database::connect();
        $db->transStart(); // Bắt đầu Transaction để bảo vệ tính toàn vẹn dữ liệu

        // 3. Kết thúc tiến độ của trạng thái hiện tại (nếu có bản ghi active)
        $activeSla = $this->historyModel->getActiveSla($customerId);
        if ($activeSla) {
            // Nếu trùng trạng thái hiện tại thì không cần xử lý tiếp
            if ($activeSla['status'] === $newStatusKey) {
                $db->transComplete();
                return $this->success(null, 'Khách hàng đã ở trạng thái này.');
            }

            $slaStatus = 'achieved'; // Mặc định là Đúng hạn (Đạt)
            if ($activeSla['due_time'] !== null && strtotime($now) > strtotime($activeSla['due_time'])) {
                $slaStatus = 'completed_late'; // Thực hiện trễ hạn
            }

            // Cập nhật bản ghi SLA cũ
            $this->historyModel->update($activeSla['id'], [
                'end_time'   => $now,
                'sla_status' => $slaStatus,
                'updated_at' => $now
            ]);
        }

        // 4. Khởi tạo bản ghi SLA cho trạng thái mới
        $assignedStaffId = $customer['assigned_care_staff_id'] ?? null;
        $slaHours = (int)$setting['sla_hours'];
        $dueTime = null;

        // Chỉ tính thời hạn do thời gian chạy nếu nhân sự đã được phân công và SLA > 0 giờ
        if ($assignedStaffId !== null && $slaHours > 0) {
            $dueTime = date('Y-m-d H:i:s', strtotime("+$slaHours hours"));
        }

        $historyData = [
            'customer_id'       => $customerId,
            'assigned_staff_id' => $assignedStaffId,
            'status'            => $newStatusKey,
            'start_time'        => $now,
            'end_time'          => null,
            'sla_duration'      => $slaHours,
            'due_time'          => $dueTime,
            'sla_status'        => 'in_progress',
            'created_at'        => $now,
            'updated_at'        => $now
        ];

        $this->historyModel->save($historyData);

        // 5. Đồng bộ hóa cập nhật cột care_status của bảng customers
        $this->customerModel->update($customerId, [
            'care_status' => $newStatusKey,
            'updated_at'  => $now
        ]);

        $db->transComplete(); // Hoàn tất Transaction

        if ($db->transStatus() === false) {
            $this->logError('Lỗi Transaction khi chuyển trạng thái SLA khách hàng', ['customer_id' => $customerId, 'status' => $newStatusKey]);
            return $this->fail('Lỗi hệ thống khi lưu trữ tiến độ.');
        }

        // Ghi log bảo mật/truy vết
        $this->logInfo("Khách hàng ID {$customerId} chuyển sang trạng thái '{$newStatusKey}'", ['operator_id' => $operatorId]);

        // Gửi thông báo cho Admin & Quản lý nhóm khi nhân viên thay đổi trạng thái (SLA Transition Notification)
        $operatorName = 'Hệ thống';
        if ($operatorId) {
            $opUser = $db->table('users')->where('id', $operatorId)->select('email')->get()->getRow();
            if ($opUser) {
                $opEmp = $db->table('employees')->where('user_id', $operatorId)->select('full_name')->get()->getRow();
                $operatorName = $opEmp ? $opEmp->full_name : $opUser->email;
            }
        }

        // 1. Tìm Trưởng phòng (Quản lý nhóm) của nhân sự được phân công chăm sóc KH này
        $managerUserId = null;
        if ($assignedStaffId) {
            $staff = $this->employeeModel->find($assignedStaffId);
            if ($staff && !empty($staff['manager_id'])) {
                $manager = $this->employeeModel->find($staff['manager_id']);
                if ($manager && !empty($manager['user_id'])) {
                    $managerUserId = (int)$manager['user_id'];
                }
            }
        }

        // 2. Tìm tất cả các quản trị viên hệ thống (Admin) có role_id = 1
        $adminUsers = $db->table('users')->where('role_id', 1)->where('deleted_at', null)->select('id')->get()->getResultArray();
        $adminUserIds = array_column($adminUsers, 'id');

        // 3. Soạn tin nhắn thông báo
        $custName   = $customer['name'];
        $custCode   = $customer['code'];
        $statusName = $setting['status_name'];
        $title      = "🔔 TIẾN ĐỘ CSKH: Thay đổi trạng thái tư vấn";
        $msg        = "Nhân viên {$operatorName} đã chuyển trạng thái của khách hàng {$custName} ({$custCode}) sang bước '{$statusName}'.";
        $link       = base_url("customers/show/{$customerId}#customer-care");

        // Gửi cho Trưởng phòng nếu có (và Trưởng phòng không phải chính người đổi)
        if ($managerUserId && $managerUserId !== $operatorId) {
            $this->notificationService->sendToUser($managerUserId, $title, $msg, 'info', $link, 0);
        }

        // Gửi cho tất cả Admins (loại trừ chính người đổi hoặc Trưởng phòng đã nhận)
        foreach ($adminUserIds as $adminId) {
            $adminId = (int)$adminId;
            if ($adminId !== $operatorId && $adminId !== $managerUserId) {
                $this->notificationService->sendToUser($adminId, $title, $msg, 'info', $link, 0);
            }
        }

        return $this->success([
            'status_key'  => $newStatusKey,
            'status_name' => $setting['status_name'],
            'color'       => $setting['color']
        ], "Đã chuyển sang trạng thái: {$setting['status_name']}");
    }

    /**
     * Quét định kỳ phát hiện các tiến độ quá hạn SLA (Cron Job logic).
     * Cập nhật trạng thái thành 'overdue' và gửi thông báo đỏ cho nhân sự và cấp quản lý.
     * 
     * @return int Số ca quá hạn vừa phát hiện
     */
    public function checkAndTriggerOverdueSlas(): int
    {
        $now = date('Y-m-d H:i:s');

        // Tìm các bản ghi SLA đang xử lý quá hạn chót mà chưa kết thúc
        // Tuân thủ Rule #6: Loại trừ các bản ghi đã xóa mềm bằng deleted_at IS NULL
        $overdueRecords = $this->historyModel->where('end_time', null)
                                            ->where('sla_status', 'in_progress')
                                            ->where('due_time !=', null)
                                            ->where('due_time <', $now)
                                            ->where('deleted_at', null)
                                            ->findAll();

        if (empty($overdueRecords)) {
            return 0;
        }

        $triggeredCount = 0;
        $db = \Config\Database::connect();

        foreach ($overdueRecords as $record) {
            $db->transStart();

            // 1. Cập nhật trạng thái trong lịch sử thành 'overdue' (Bỏ lỡ)
            $this->historyModel->update($record['id'], [
                'sla_status' => 'overdue',
                'updated_at' => $now
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                continue;
            }

            $triggeredCount++;

            // 2. Gửi thông báo hệ thống (Rule #10 & NotificationService)
            // Lấy thông tin khách hàng và cấu hình trạng thái
            $customer = $this->customerModel->find($record['customer_id']);
            $setting  = $this->settingModel->where('status_key', $record['status'])->first();
            
            if ($customer && $setting) {
                $statusName = $setting['status_name'];
                $custName   = $customer['name'];
                $custCode   = $customer['code'];

                // Tìm user_id của nhân viên được giao phụ trách để bắn tin
                $staff = $this->employeeModel->find($record['assigned_staff_id']);
                if ($staff && !empty($staff['user_id'])) {
                    $userId = $staff['user_id'];
                    $title  = "⚠️ CẢNH BÁO ĐỎ: Quá hạn chăm sóc Khách hàng";
                    $msg    = "Khách hàng {$custName} ({$custCode}) ở bước '{$statusName}' đã vượt quá thời gian SLA ({$record['sla_duration']} giờ) được giao. Vui lòng xử lý ngay lập tức!";
                    $link   = base_url("customers/show/{$customer['id']}");

                    // Gửi tin nhắn cho nhân viên
                    $this->notificationService->sendToUser($userId, $title, $msg, 'alert', $link, 0); // 0 biểu thị gửi tự động từ Hệ thống

                    // Gửi tin nhắn cảnh báo cho Trưởng phòng của nhân viên đó để đôn đốc
                    $managerTitle = "🚨 GIÁM SÁT: Nhân sự trễ hạn chăm sóc Khách hàng";
                    $managerMsg   = "Nhân viên {$staff['full_name']} đã bỏ lỡ (trễ hạn) thời gian SLA bước '{$statusName}' của khách hàng {$custName} ({$custCode}).";
                    $this->notificationService->notifyManagerOfEmployee($staff['id'], $managerTitle, $managerMsg, 'alert', $link, 0);
                }
            }
        }

        if ($triggeredCount > 0) {
            $this->logInfo("Hệ thống phát hiện {$triggeredCount} tiến trình chăm sóc quá hạn SLA.");
        }

        return $triggeredCount;
    }

    /**
     * Thống kê chất lượng chăm sóc (SLA Performance Leaderboard) của nhân viên.
     * 
     * @param int|null $managerId Lọc theo nhóm của Trưởng phòng nếu có (Data Isolation - Rule #3)
     * @return array Danh sách thống kê hiệu suất của từng nhân viên
     */
    public function getStaffSlaPerformance(?int $managerId = null): array
    {
        $db = \Config\Database::connect();
        
        // Truy vấn danh sách nhân viên
        $empBuilder = $db->table('employees')
                         ->select('id, full_name, position')
                         ->where('deleted_at', null);

        if ($managerId) {
            // Data Isolation: Lấy quân số báo cáo cho Sếp
            $myTeamIds = $db->table('employees')->where('manager_id', $managerId)->select('id')->get()->getResultArray();
            $myTeamIds = array_column($myTeamIds, 'id');
            $myTeamIds[] = $managerId; // Bao gồm chính sếp
            
            $empBuilder->whereIn('id', $myTeamIds);
        }

        $employees = $empBuilder->get()->getResultArray();
        $report = [];

        foreach ($employees as $emp) {
            // Đếm số lượng ca theo các trạng thái SLA khác nhau trong bảng customer_sla_history
            // Sử dụng WHERE deleted_at IS NULL để tránh các bản ghi đã xóa mềm (Rule #6)
            $totalCount = $db->table('customer_sla_history')
                             ->where('assigned_staff_id', $emp['id'])
                             ->where('deleted_at', null)
                             ->countAllResults();

            if ($totalCount === 0) {
                continue; // Bỏ qua nhân sự không tham gia tư vấn/chăm sóc
            }

            // Ca đạt đúng hạn (achieved)
            $achievedCount = $db->table('customer_sla_history')
                                ->where('assigned_staff_id', $emp['id'])
                                ->where('sla_status', 'achieved')
                                ->where('deleted_at', null)
                                ->countAllResults();

            // Ca trễ hạn (completed_late hoặc overdue)
            $overdueCount = $db->table('customer_sla_history')
                               ->where('assigned_staff_id', $emp['id'])
                               ->whereIn('sla_status', ['overdue', 'completed_late'])
                               ->where('deleted_at', null)
                               ->countAllResults();

            // Ca đang trong tiến độ và chưa bị trễ (in_progress)
            $inProgressCount = $db->table('customer_sla_history')
                                 ->where('assigned_staff_id', $emp['id'])
                                 ->where('sla_status', 'in_progress')
                                 ->where('deleted_at', null)
                                 ->countAllResults();

            // Tính tỷ lệ đúng hạn = (Số ca đạt / (Tổng số ca đã đóng)) * 100%
            $closedCount = $achievedCount + $overdueCount;
            $rate = $closedCount > 0 ? round(($achievedCount / $closedCount) * 100, 1) : 100;

            $report[] = [
                'staff_id'          => $emp['id'],
                'full_name'         => $emp['full_name'],
                'position'          => $emp['position'],
                'total_assigned'    => $totalCount,
                'achieved_count'    => $achievedCount,
                'overdue_count'     => $overdueCount,
                'in_progress_count' => $inProgressCount,
                'efficiency_rate'   => $rate
            ];
        }

        // Sắp xếp giảm dần theo tỷ lệ đúng hạn, sau đó giảm dần theo số lượng đạt được
        usort($report, function($a, $b) {
            if ($b['efficiency_rate'] == $a['efficiency_rate']) {
                return $b['achieved_count'] <=> $a['achieved_count'];
            }
            return $b['efficiency_rate'] <=> $a['efficiency_rate'];
        });

        return $report;
    }

    /**
     * Lấy danh sách các khách hàng đang ở trạng thái trễ hạn (Overdue) để báo đỏ.
     * 
     * @param int|null $managerId Lọc phạm vi Trưởng phòng
     * @return array
     */
    public function getOverdueCustomersList(?int $managerId = null): array
    {
        $db = \Config\Database::connect();
        
        $builder = $db->table('customer_sla_history as csh')
                      ->select('csh.id as history_id, csh.start_time, csh.due_time, csh.sla_duration, csh.status as status_key, 
                                c.id as customer_id, c.name as customer_name, c.code as customer_code, 
                                e.id as staff_id, e.full_name as staff_name, css.status_name')
                      ->join('customers as c', 'c.id = csh.customer_id AND c.deleted_at IS NULL')
                      ->join('employees as e', 'e.id = csh.assigned_staff_id AND e.deleted_at IS NULL', 'left')
                      ->join('customer_sla_settings as css', 'css.status_key = csh.status AND css.deleted_at IS NULL')
                      ->where('csh.end_time', null)
                      ->where('csh.sla_status', 'overdue')
                      ->where('csh.deleted_at', null);

        if ($managerId) {
            $myTeamIds = $db->table('employees')->where('manager_id', $managerId)->select('id')->get()->getResultArray();
            $myTeamIds = array_column($myTeamIds, 'id');
            $myTeamIds[] = $managerId;
            $builder->whereIn('csh.assigned_staff_id', $myTeamIds);
        }

        $records = $builder->orderBy('csh.due_time', 'ASC')->get()->getResultArray();

        foreach ($records as &$r) {
            // Tính số giây trễ thực tế để hiển thị ra view
            $secondsLate = time() - strtotime($r['due_time']);
            $r['delay_string'] = format_seconds_to_duration($secondsLate);
        }

        return $records;
    }
}
