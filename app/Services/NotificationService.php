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

    public function __construct()
    {
        parent::__construct();
        // Nạp các Model cần thiết để truy vấn đích đến (Recipients)
        $this->notificationModel = new NotificationModel();
        $this->userModel = new UserModel();
        $this->employeeModel = new EmployeeModel();
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
     * Phát thông báo đến toàn bộ ban Quản trị viên.
     * Thường dùng cho các thông báo lỗi hệ thống, yêu cầu hỗ trợ kỹ thuật hoặc báo cáo tổng.
     */
    public function notifyAdmins($title, $message, $type = 'system', $link = null, $senderId = null)
    {
        // Truy vấn tất cả User có Role là Admin (role_id = 1) và đang hoạt động
        $admins = $this->userModel->where('role_id', 1)->where('active_status', 1)->findAll();
        foreach ($admins as $admin) {
            $this->sendToUser($admin['id'], $title, $message, $type, $link, $senderId);
        }
    }

    /**
     * Gửi yêu cầu phê duyệt cho Trưởng phòng của một nhân viên cụ thể.
     * Tự động xác định cấp quản lý dựa trên sơ đồ tổ chức (Phòng ban).
     * 
     * @param int $employeeId ID nhân viên phát sinh yêu cầu.
     */
    public function notifyManagerOfEmployee($employeeId, $title, $message, $type = 'approval', $link = null, $senderId = null)
    {
        $departmentId = 3; // Mặc định là phòng Pháp lý nếu không tìm thấy dữ liệu
        $employee = $this->employeeModel->find($employeeId);
        
        if ($employee && $employee['department_id']) {
            $departmentId = $employee['department_id'];
        }

        // 1. Tìm kiếm Trưởng phòng (Role Trưởng phòng = 3) thuộc cùng phòng ban với nhân viên
        $managers = $this->userModel->select('users.*')
                                    ->join('employees', 'employees.user_id = users.id')
                                    ->where('employees.department_id', $departmentId)
                                    ->where('users.role_id', 3) 
                                    ->where('users.active_status', 1)
                                    ->findAll();
        
        // 2. CƠ CHẾ DỰ PHÒNG (Fallback Strategy):
        // Nếu phòng ban đó chưa có trưởng phòng, thông báo sẽ được định tuyến về phòng Pháp lý (Trụ sở chính quản lý).
        if (empty($managers) && $departmentId !== 3) {
            $managers = $this->userModel->select('users.*')
                                    ->join('employees', 'employees.user_id = users.id')
                                    ->where('employees.department_id', 3)
                                    ->where('users.role_id', 3)
                                    ->where('users.active_status', 1)
                                    ->findAll();
        }

        // 3. Thực hiện gửi thông báo cho từng Manager tìm được
        foreach ($managers as $manager) {
            $this->sendToUser($manager['id'], $title, $message, $type, $link, $senderId);
        }
        return true;
    }
}
