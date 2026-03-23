<?php

namespace App\Controllers;

use App\Services\PermissionService;

class PermissionController extends BaseController
{
    protected $permissionService;

    public function __construct()
    {
        $this->permissionService = new PermissionService();
    }

    /**
     * Lấy giao diện (HTML) Matrix phân quyền để đổ vào Modal
     */
    public function getUserMatrix($userId)
    {
        // Chặn quyền nếu không phải Admin hoặc người có quyền quản lý tài khoản
        if (!has_permission('user.manage')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không được phép xem phân quyền.']);
        }

        $userModel = model('UserModel');
        $user = $userModel->select('users.*, roles.name as role_name')
                          ->join('roles', 'roles.id = users.role_id')
                          ->find($userId);

        if (!$user) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy tài khoản.']);
        }

        $matrix = $this->permissionService->getUserPermissionMatrix($userId, $user['role_id']);

        // Group matrix theo module_group
        $grouped = [];
        foreach ($matrix as $item) {
            $grouped[$item['module_group']][] = $item;
        }

        $data = [
            'user' => $user,
            'groupedMatrix' => $grouped
        ];

        return view('dashboard/users/partials/permission_matrix', $data);
    }

    /**
     * Lưu các thiết lập ghi đè phân quyền
     */
    public function saveUserOverrides($userId)
    {
        if (!has_permission('user.manage')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không được phép cấp quyền.']);
        }

        $overrides = $this->request->getPost('permissions') ?? [];
        
        // $overrides dạng [ permission_id => '1' (Grant), '0' (Revoke), 'default' (Role) ]
        
        $success = $this->permissionService->updateUserOverrides($userId, $overrides);

        if ($success) {
            // Log history
            $logService = new \App\Services\SystemLogService();
            $logService->log('UPDATE_PERMISSION', 'Auth', $userId, ['note' => 'Đã cập nhật hệ thống ghi đè quyền cho user ' . $userId]);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Đã cập nhật cấu hình phân quyền đặc biệt.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể lưu yapı phân quyền.']);
    }
}
