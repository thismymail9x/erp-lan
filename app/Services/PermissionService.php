<?php

namespace App\Services;

class PermissionService extends BaseService
{
    /**
     * Tải và cache danh sách quyền của User vào Session khi đăng nhập.
     * Cây quyền được kết hợp từ: Quyền Mặc Định Của Nhóm Role + Ghi Đè Quyền Cá Nhân.
     * 
     * @param int $userId ID tài khoản
     * @param int $roleId ID chức danh của tài khoản
     */
    public function loadUserPermissions(int $userId, int $roleId)
    {
        $db = \Config\Database::connect();
        
        // 1. Lấy danh sách ID quyền mặc định từ Role
        $rolePerms = $db->table('roles_permissions')
                        ->select('TRIM(permissions.name) as name, permissions.id')
                        ->join('permissions', 'permissions.id = roles_permissions.permission_id')
                        ->where('role_id', $roleId)
                        ->get()->getResultArray();
                        
        $basePermissions = [];
        $basePermissionIds = [];
        foreach ($rolePerms as $rp) {
            $basePermissions[] = $rp['name'];
            $basePermissionIds[] = $rp['id'];
        }

        // 2. Lấy danh sách ghi đè từ User (Ép cấp hoặc Ép tước)
        $userPerms = $db->table('user_permissions')
                        ->select('TRIM(permissions.name) as name, permissions.id, user_permissions.is_granted')
                        ->join('permissions', 'permissions.id = user_permissions.permission_id')
                        ->where('user_id', $userId)
                        ->get()->getResultArray();

        $granted = [];
        $revoked = [];
        foreach ($userPerms as $up) {
            if ($up['is_granted'] == 1) {
                $granted[] = $up['name'];
            } else {
                $revoked[] = $up['name'];
            }
        }

        // 3. Kết hợp quyền: (Base + Granted) - Revoked
        $finalPermissions = array_diff(array_merge($basePermissions, $granted), $revoked);
        
        // Mở khóa tối cao nếu là Sys Admin (hoặc Admin ID = 1)
        if (in_array('sys.admin', $finalPermissions) || $roleId == 1) {
            $finalPermissions[] = 'sys.admin'; // Đảm bảo cờ admin tồn tại
        }

        // 4. Lưu mảng khóa quyền vào Session Array
        session()->set('permissions', array_unique($finalPermissions));
    }

    /**
     * Lấy toàn bộ phân quyền tổng quan của 1 Role và 1 User cụ thể 
     * Dùng để hiển thị lên bảng Matrix giao diện UI quản lý.
     */
    public function getUserPermissionMatrix(int $userId, int $roleId)
    {
        $db = \Config\Database::connect();
        $allPerms = $db->table('permissions')->orderBy('module_group', 'ASC')->get()->getResultArray();
        
        $roleLinked = $db->table('roles_permissions')->where('role_id', $roleId)->get()->getResultArray();
        $rolePermIds = array_column($roleLinked, 'permission_id');
        
        $userLinked = $db->table('user_permissions')->where('user_id', $userId)->get()->getResultArray();
        
        $matrix = [];
        foreach ($allPerms as $p) {
            $isRoleGranted = in_array($p['id'], $rolePermIds);
            $userOverride = null; // null = ko ghi đè, 1 = cấp, 0 = tước
            
            foreach ($userLinked as $ul) {
                if ($ul['permission_id'] == $p['id']) {
                    $userOverride = $ul['is_granted'];
                    break;
                }
            }

            // Tính toán trạng thái Final cuối cùng
            $finalStatus = false;
            if ($userOverride !== null) {
                $finalStatus = ($userOverride == 1);
            } else {
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
     * Cập nhật Ghi đè Quyền cho 1 User
     *
     * @param array $overrides Mảng dữ liệu VD: [permission_id => is_granted, permission_id => is_granted]
     *                         Giá trị null nghĩa là xoá bỏ ghi đè (fallback về Role).
     */
    public function updateUserOverrides(int $userId, array $overrides)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('user_permissions');
        
        $db->transStart();
        foreach ($overrides as $permId => $val) {
            if ($val === '' || $val === null || $val === 'default') {
                // Xoá ghi đè -> trả về tự động theo Role
                $builder->where(['user_id' => $userId, 'permission_id' => $permId])->delete();
            } else {
                // Ép thêm (1) hoặc Ép tước (0)
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
        return $db->transStatus();
    }
}
