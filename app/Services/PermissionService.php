<?php

namespace App\Services;

/**
 * PermissionService
 * 
 * Lớp dịch vụ hạt nhân quản lý hệ thống phân quyền dựa trên vai trò (RBAC) 
 * và ghi đè quyền cá nhân (Individual Overrides).
 * 
 * Hệ thống hoạt động theo cơ chế: Final Permission = (Role Permissions + User Grants) - User Revokes.
 */
class PermissionService extends BaseService
{
    /**
     * Tải và cache danh sách quyền của User vào Session ngay khi đăng nhập.
     * Đây là hàm quan trọng nhất để xác định "khả năng" của một người trong hệ thống.
     * 
     * @param int $userId ID tài khoản
     * @param int $roleId ID chức danh mặc định
     */
    public function loadUserPermissions(int $userId, int $roleId)
    {
        $db = \Config\Database::connect();
        
        // 1. LẤY QUYỀN MẶC ĐỊNH TỪ VAI TRÒ (Role-based):
        // Truy vấn các quyền được gán cho Nhóm (ví dụ: Trưởng phòng có quyền 'case.view', 'attendance.approve')
        $rolePerms = $db->table('roles_permissions')
                        ->select('TRIM(permissions.name) as name, permissions.id')
                        ->join('permissions', 'permissions.id = roles_permissions.permission_id')
                        ->where('role_id', $roleId)
                        ->get()->getResultArray();
                        
        $basePermissions = [];
        foreach ($rolePerms as $rp) {
            $basePermissions[] = $rp['name'];
        }

        // 2. LẤY QUYỀN GHI ĐÈ RIÊNG BIỆT (User-specific overrides):
        // Đôi khi một nhân viên cụ thể cần thêm quyền hoặc bị tước quyền so với nhóm của họ.
        $userPerms = $db->table('user_permissions')
                        ->select('TRIM(permissions.name) as name, permissions.id, user_permissions.is_granted')
                        ->join('permissions', 'permissions.id = user_permissions.permission_id')
                        ->where('user_id', $userId)
                        ->get()->getResultArray();

        $granted = []; // Danh sách được cấp thêm
        $revoked = []; // Danh sách bị tước đi
        foreach ($userPerms as $up) {
            if ($up['is_granted'] == 1) {
                $granted[] = $up['name'];
            } else {
                $revoked[] = $up['name'];
            }
        }

        // 3. TÍNH TOÁN QUYỀN CUỐI CÙNG (Final Computation):
        // Hợp nhất quyền mặc định và quyền được cấp thêm, sau đó loại bỏ những quyền bị tước.
        $finalPermissions = array_diff(array_merge($basePermissions, $granted), $revoked);
        
        // CỜ TỐI CAO (Super Admin Flag): 
        // Nếu là Admin hệ thống (thường ID=1), đảm bảo họ luôn có cờ 'sys.admin' để bypass mọi kiểm tra.
        if (in_array('sys.admin', $finalPermissions) || $roleId == 1) {
            $finalPermissions[] = 'sys.admin';
        }

        // 4. LƯU VÀO SESSION:
        // Lưu mảng quyền (không trùng lặp) vào session để các hàm helper has_permission() sử dụng tức thì.
        session()->set('permissions', array_unique($finalPermissions));
    }

    /**
     * Lấy ma trận quyền để hiển thị trên giao diện Quản lý Phân Quyền.
     * Giúp Admin thấy được: Quyền nào từ Role, Quyền nào đang bị ghi đè.
     * 
     * @return array Mảng chứa toàn bộ các quyền và trạng thái tương ứng.
     */
    public function getUserPermissionMatrix(int $userId, int $roleId)
    {
        $db = \Config\Database::connect();
        // Lấy tất cả các "định nghĩa" quyền có trong hệ thống
        $allPerms = $db->table('permissions')->orderBy('module_group', 'ASC')->get()->getResultArray();
        
        // Lấy danh sách ID quyền mà Role hiện tại đang sở hữu
        $roleLinked = $db->table('roles_permissions')->where('role_id', $roleId)->get()->getResultArray();
        $rolePermIds = array_column($roleLinked, 'permission_id');
        
        // Lấy danh sách ghi đè của cá nhân User này
        $userLinked = $db->table('user_permissions')->where('user_id', $userId)->get()->getResultArray();
        
        $matrix = [];
        foreach ($allPerms as $p) {
            $isRoleGranted = in_array($p['id'], $rolePermIds);
            $userOverride = null; // null: Mặc định theo Role, 1: Cấp thêm, 0: Tước bỏ
            
            // Tìm xem có bản ghi ghi đè nào cho quyền này không
            foreach ($userLinked as $ul) {
                if ($ul['permission_id'] == $p['id']) {
                    $userOverride = $ul['is_granted'];
                    break;
                }
            }

            // Tính toán logic hiển thị thực tế trên UI
            $finalStatus = false;
            if ($userOverride !== null) {
                // Ưu tiên kết quả ghi đè trước
                $finalStatus = ($userOverride == 1);
            } else {
                // Nếu không ghi đè, dùng quyền của Role
                $finalStatus = $isRoleGranted;
            }

            $matrix[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'module_group' => $p['module_group'],
                'description' => $p['description'],
                'is_role_granted' => $isRoleGranted,
                'user_override' => $userOverride,
                'final_status' => $finalStatus
            ];
        }

        return $matrix;
    }

    /**
     * Cập nhật danh sách ghi đè quyền (Overrides) cho một người dùng.
     * 
     * @param int $userId
     * @param array $overrides Dữ liệu từ Form [perm_id => status]
     */
    public function updateUserOverrides(int $userId, array $overrides)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('user_permissions');
        
        // Sử dụng Transaction để an toàn khi cập nhật hàng loạt
        $db->transStart();
        foreach ($overrides as $permId => $val) {
            // Nếu chọn 'Mặc định' (null/default) -> Xóa bỏ mọi ghi đè cũ để quay về theo Role
            if ($val === '' || $val === null || $val === 'default') {
                $builder->where(['user_id' => $userId, 'permission_id' => $permId])->delete();
            } else {
                // Nếu chọn 'Cấp' (1) hoặc 'Tước' (0) -> Cập nhật hoặc thêm mới bản ghi ghi đè
                $existing = $builder->where(['user_id' => $userId, 'permission_id' => $permId])->countAllResults();
                if ($existing > 0) {
                    $builder->where(['user_id' => $userId, 'permission_id' => $permId])->update(['is_granted' => $val]);
                } else {
                    $builder->insert([
                        'user_id' => $userId,
                        'permission_id' => $permId,
                        'is_granted' => $val
                    ]);
                }
            }
        }
        $db->transComplete();

        // Sau khi cập nhật DB thành công, nạp lại vào Session để có hiệu lực ngay lập tức
        if ($db->transStatus()) {
             $userModel = model('UserModel');
             $u = $userModel->find($userId);
             if ($u) $this->loadUserPermissions($userId, $u['role_id']);
        }

        return $db->transStatus();
    }
}
