<?php

namespace App\Services;

use App\Models\NotificationModel;
use App\Models\EmployeeModel;
use App\Models\UserModel;

/**
 * NotificationService
 * 
 * Lớp Dịch vụ quản lý logic phát hành thông báo (Notification Dispatcher).
 * Hỗ trợ các kịch bản:
 * 1. Gửi thông báo đơn lẻ (P2P).
 * 2. Gửi thông báo nhóm (Broadcasting to Admin/Managers).
 * 3. Tự động tìm kiếm người quản lý trực tiếp để gửi yêu cầu phê duyệt.
 */
class NotificationService extends BaseService
{
    protected $notificationModel;
    protected $userModel;
    protected $employeeModel;
    protected $roleModel;

    public function __construct(
        \App\Models\NotificationModel $notificationModel = null,
        \App\Models\UserModel $userModel = null,
        \App\Models\EmployeeModel $employeeModel = null,
        \App\Models\RoleModel $roleModel = null
    ) {
        parent::__construct();
        $this->notificationModel = $notificationModel ?? new \App\Models\NotificationModel();
        $this->userModel = $userModel ?? new \App\Models\UserModel();
        $this->employeeModel = $employeeModel ?? new \App\Models\EmployeeModel();
        $this->roleModel = $roleModel ?? new \App\Models\RoleModel();
    }

    /**
     * Gửi một thông báo cụ thể đến một Tài khoản.
     * 
     * @param int|string $userId ID người nhận.
     * @param string $title Tiêu đề ngắn gọn.
     * @param string $message Nội dung chi tiết.
     * @param string $type Phân loại: system, approval, task, alert.
     * @param string|null $link Đường dẫn điều hướng khi click vào thông báo.
     * @param int|null $senderId ID người gửi (mặc định là User hiện tại).
     */
    public function sendToUser($userId, $title, $message, $type = 'system', $link = null, $senderId = null)
    {
        $res = $this->notificationModel->insert([
            'user_id'    => $userId,
            'sender_id'  => $senderId ?? session()->get('user_id'),
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($res) {
            $this->logInfo("NOTIF_SENT_TO_USER_{$userId}: {$title}");
        }

        return $res;
    }

    /**
     * Gửi thông báo đến danh sách nhiều người dùng (Smart Dispatcher).
     * Tự động loại bỏ các ID trùng lặp và loại bỏ người gửi (sender) khỏi danh sách nhận.
     * 
     * @param array $userIds Danh sách ID người nhận.
     * @param string $title
     * @param string $message
     * @param string $type
     * @param string|null $link
     * @param int|null $senderId
     */
    public function sendToMultiple(array $userIds, $title, $message, $type = 'system', $link = null, $senderId = null)
    {
        $senderId = $senderId ?? session()->get('user_id');
        // 1. Loại bỏ trùng và lọc bỏ chính người gửi
        $uniqueIds = array_unique($userIds);
        $uniqueIds = array_filter($uniqueIds, function($id) use ($senderId) {
            return !empty($id) && $id != $senderId;
        });

        foreach ($uniqueIds as $userId) {
            $this->sendToUser($userId, $title, $message, $type, $link, $senderId);
        }
        return count($uniqueIds);
    }

    /**
     * Phát thông báo đến toàn bộ ban Quản trị viên (Admin).
     */
    public function notifyAdmins($title, $message, $type = 'system', $link = null, $senderId = null)
    {
        $adminRole = $this->roleModel->where('name', \Config\AppConstants::ROLE_ADMIN)->first();
        if (!$adminRole) {
            $this->logError("ROLE_NOT_FOUND: " . \Config\AppConstants::ROLE_ADMIN);
            return 0;
        }
        
        $roleId = $adminRole['id'];

        $admins = $this->userModel->where('role_id', $roleId)->where('active_status', 1)->findColumn('id') ?? [];
        return $this->sendToMultiple($admins, $title, $message, $type, $link, $senderId);
    }

    /**
     * Gửi yêu cầu phê duyệt cho Trưởng phòng của một nhân viên cụ thể.
     */
    public function notifyManagerOfEmployee($employeeId, $title, $message, $type = 'approval', $link = null, $senderId = null)
    {
        $departmentId = \Config\AppConstants::DEPT_PHAP_LY; 
        $employee = $this->employeeModel->find($employeeId);
        if ($employee && $employee['department_id']) {
            $departmentId = $employee['department_id'];
        }

        // Lấy ID vai trò Trưởng phòng
        $managerRole = $this->roleModel->where('name', \Config\AppConstants::ROLE_TRUONG_PHONG)->first();
        if (!$managerRole) {
            $this->logError("ROLE_NOT_FOUND: " . \Config\AppConstants::ROLE_TRUONG_PHONG);
            return 0;
        }
        $roleId = $managerRole['id'];

        $managers = $this->userModel->select('users.id')
                                    ->join('employees', 'employees.user_id = users.id')
                                    ->where('employees.department_id', $departmentId)
                                    ->where('users.role_id', $roleId) 
                                    ->where('users.active_status', 1)
                                    ->get()->getResultArray();
        
        $ids = array_column($managers, 'id');
        
        // Fallback: Nếu phòng đó không có sếp, gửi cho sếp phòng Pháp lý
        if (empty($ids) && $departmentId !== \Config\AppConstants::DEPT_PHAP_LY) {
            $managers = $this->userModel->select('users.id')
                                    ->join('employees', 'employees.user_id = users.id')
                                    ->where('employees.department_id', \Config\AppConstants::DEPT_PHAP_LY)
                                    ->where('users.role_id', $roleId)
                                    ->where('users.active_status', 1)
                                    ->get()->getResultArray();
            $ids = array_column($managers, 'id');
        }

        return $this->sendToMultiple($ids, $title, $message, $type, $link, $senderId);
    }

    /**
     * Gửi thông báo đến toàn bộ Ban quản lý (Admin và Trưởng phòng).
     */
    public function notifyManagement($title, $message, $type = 'alert', $link = null, $senderId = null)
    {
        // Lấy ID các vai trò quản lý
        $roles = $this->roleModel->whereIn('name', [\Config\AppConstants::ROLE_ADMIN, \Config\AppConstants::ROLE_TRUONG_PHONG])->findAll();
        $roleIds = array_column($roles, 'id');

        if (empty($roleIds)) {
            $this->logError("MANAGEMENT_ROLES_NOT_FOUND");
            return 0;
        }

        $userIds = $this->userModel->whereIn('role_id', $roleIds)->where('active_status', 1)->findColumn('id') ?? [];
        
        return $this->sendToMultiple($userIds, $title, $message, $type, $link, $senderId);
    }

    /**
     * Gửi thông báo đến toàn bộ nhân viên đang hoạt động.
     */
    public function notifyAllEmployees($title, $message, $type = 'system', $link = null, $senderId = null)
    {
        $userIds = $this->userModel->where('active_status', 1)->findColumn('id') ?? [];
        return $this->sendToMultiple($userIds, $title, $message, $type, $link, $senderId);
    }
}
