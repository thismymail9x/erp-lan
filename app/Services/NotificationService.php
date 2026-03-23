<?php

namespace App\Services;

use App\Models\NotificationModel;
use App\Models\EmployeeModel;
use App\Models\UserModel;

class NotificationService extends BaseService
{
    protected $notificationModel;
    protected $userModel;
    protected $employeeModel;

    public function __construct()
    {
        parent::__construct();
        $this->notificationModel = new NotificationModel();
        $this->userModel = new UserModel();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Gửi thông báo cho một User cụ thể
     */
    public function sendToUser($userId, $title, $message, $type = 'system', $link = null, $senderId = null)
    {
        return $this->notificationModel->insert([
            'user_id'    => $userId,
            'sender_id'  => $senderId ?? session()->get('user_id'),
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Gửi thông báo cho Admin
     */
    public function notifyAdmins($title, $message, $type = 'system', $link = null, $senderId = null)
    {
        // Lấy tất cả user có role Admin (role_id = 1, assume 1 is Admin based on seed)
        $admins = $this->userModel->where('role_id', 1)->where('active_status', 1)->findAll();
        foreach ($admins as $admin) {
            $this->sendToUser($admin['id'], $title, $message, $type, $link, $senderId);
        }
    }

    /**
     * Gửi thông báo cho Trưởng phòng của một nhân viên
     */
    public function notifyManagerOfEmployee($employeeId, $title, $message, $type = 'approval', $link = null, $senderId = null)
    {
        $departmentId = 3; // Default to Legal Department
        $employee = $this->employeeModel->find($employeeId);
        
        if ($employee && $employee['department_id']) {
            $departmentId = $employee['department_id'];
        }

        // Lấy tất cả trưởng phòng của phòng ban này (ví dụ: role = 'trưởng phòng')
        $managers = $this->userModel->select('users.*')
                                    ->join('employees', 'employees.user_id = users.id')
                                    ->where('employees.department_id', $departmentId)
                                    ->where('users.role_id', 3) // 3 is usually Trưởng phòng based on seeding
                                    ->where('users.active_status', 1)
                                    ->findAll();
        
        // Nếu không có ai (ví dụ phòng ban đó chưa có trưởng phòng), fallback về Pháp lý
        if (empty($managers) && $departmentId !== 3) {
            $managers = $this->userModel->select('users.*')
                                    ->join('employees', 'employees.user_id = users.id')
                                    ->where('employees.department_id', 3)
                                    ->where('users.role_id', 3)
                                    ->where('users.active_status', 1)
                                    ->findAll();
        }

        foreach ($managers as $manager) {
            $this->sendToUser($manager['id'], $title, $message, $type, $link, $senderId);
        }
        return true;
    }
}
