<?php

namespace App\Controllers;

use App\Services\PermissionService;

/**
 * PermissionController
 * 
 * Bộ điều khiển quản lý Ma trận phân quyền (Override Permissions).
 * Chức năng:
 * 1. Cung cấp giao diện trực quan để Admin cấp/tước quyền cho từng User cụ thể.
 * 2. Xử lý logic ghi đè quyền (User Overrides) vượt trên quyền mặc định của Vai trò (Role).
 */
class PermissionController extends BaseController
{
    protected $permissionService;

    public function __construct()
    {
        // Khởi tạo Service nghiệp vụ phân quyền
        $this->permissionService = new PermissionService();
    }

    /**
     * Lấy giao diện (HTML) Ma trận phân quyền (Matrix) cho một người dùng cụ thể.
     * Thường được tải động qua AJAX để đổ vào Modal chỉnh sửa tài khoản.
     * 
     * @param int $userId ID của tài khoản cần xem quyền.
     */
    public function getUserMatrix($userId)
    {
        // BẢO MẬT: Chặn truy cập nếu không có quyền 'user.manage' (Thường là Admin hoặc HR)
        if (!has_permission('user.manage')) {
            return $this->response->setJSON([
                'status'  => 'error', 
                'message' => 'Bạn không có đặc quyền để xem cấu trúc phân quyền của hệ thống.'
            ]);
        }

        // 1. Lấy thông tin tài khoản kèm theo tên Vai trò (Role) hiện tại
        $userModel = model('UserModel');
        $user = $userModel->select('users.*, roles.name as role_name')
                          ->join('roles', 'roles.id = users.role_id')
                          ->find($userId);

        if (!$user) {
            return $this->response->setJSON([
                'status'  => 'error', 
                'message' => 'Tài khoản yêu cầu không tồn tại trên hệ thống.'
            ]);
        }

        // 2. Sử dụng Service để tính toán Ma trận quyền:
        // Kết quả trả về trạng thái của từng quyền: Mặc định (Role), Cho phép (Grant), hoặc Chặn (Revoke).
        $matrix = $this->permissionService->getUserPermissionMatrix($userId, $user['role_id']);

        // 3. NHÓM QUYỀN (Grouping): 
        // Sắp xếp các quyền vào từng Module Group (Hệ thống, Nhân sự, Vụ việc...) để hiển thị khoa học trên UI.
        $grouped = [];
        foreach ($matrix as $item) {
            $grouped[$item['module_group']][] = $item;
        }

        $data = [
            'user'          => $user,
            'groupedMatrix' => $grouped
        ];

        // Trả về view partial (chỉ chứa block table/matrix)
        return view('dashboard/users/partials/permission_matrix', $data);
    }

    /**
     * Lưu cấu hình Ghi đè phân quyền (User Preference Overrides).
     * Được gọi khi Admin nhấn "Lưu thay đổi" trên Matrix phân quyền.
     * 
     * @param int $userId ID tài khoản người dùng thụ hưởng.
     */
    public function saveUserOverrides($userId)
    {
        // KIỂM TRA QUYỀN TRUY CẬP: Ngăn chặn người dùng thường tự cấp quyền cho mình
        if (!has_permission('user.manage')) {
            return $this->response->setJSON([
                'status'  => 'error', 
                'message' => 'Hành động bị từ chối: Bạn không có quyền thay đổi cấu hình bảo mật.'
            ]);
        }

        // 1. Phân tích dữ liệu từ Form (mảng permissions gửi lên)
        // Dạng: [ permission_id => '1' (Bật), '0' (Tắt), 'default' (Theo Vai trò) ]
        $overrides = $this->request->getPost('permissions') ?? [];
        
        // 2. Thực hiện cập nhật vào Database qua Service
        $success = $this->permissionService->updateUserOverrides($userId, $overrides);

        if ($success) {
            // 3. GHI NHẬT KÝ BẢO MẬT (Security Audit Log):
            // Lưu vết ai đã thay đổi quyền của ai vào thời điểm nào.
            $logService = new \App\Services\SystemLogService();
            $logService->log('UPDATE_PERMISSION', 'Security', $userId, [
                'note'    => 'Cập nhật ghi đè phân quyền đặc biệt cho user ID: ' . $userId,
                'details' => $overrides
            ]);

            return $this->response->setJSON([
                'status'  => 'success', 
                'message' => 'Cấu hình phân quyền đặc biệt đã được áp dụng ngay lập tức.'
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'error', 
            'message' => 'Lỗi hệ thống: Không thể đồng bộ cấu hình phân quyền.'
        ]);
    }
}
