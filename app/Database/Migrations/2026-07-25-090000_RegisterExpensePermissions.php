<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Register expense module permissions for RBAC when auto-sync has not been run yet.
 */
class RegisterExpensePermissions extends Migration
{
    private array $permissions = [
        'case_expense.submit' => [
            'group' => 'Chi phí xử lý vụ việc',
            'desc' => 'Tạo phiếu chi phí cho vụ việc mình được phân công hoặc tham gia',
            'roles' => [1, 2, 3, 4, 5, 6, 7],
        ],
        'case_expense.view_own' => [
            'group' => 'Chi phí xử lý vụ việc',
            'desc' => 'Xem chi phí vụ việc của cá nhân',
            'roles' => [1, 2, 3, 4, 5, 6, 7],
        ],
        'case_expense.view_team' => [
            'group' => 'Chi phí xử lý vụ việc',
            'desc' => 'Xem chi phí của nhân sự cấp dưới trực tiếp',
            'roles' => [1, 2, 3],
        ],
        'case_expense.view_all' => [
            'group' => 'Chi phí xử lý vụ việc',
            'desc' => 'Xem toàn bộ chi phí xử lý vụ việc',
            'roles' => [1, 2],
        ],
        'case_expense.approve' => [
            'group' => 'Chi phí xử lý vụ việc',
            'desc' => 'Duyệt hoặc từ chối chi phí xử lý vụ việc',
            'roles' => [1, 2],
        ],
        'office_expense.view' => [
            'group' => 'Chi phí vận hành',
            'desc' => 'Xem thống kê và danh sách chi phí vận hành',
            'roles' => [1, 2],
        ],
        'office_expense.manage' => [
            'group' => 'Chi phí vận hành',
            'desc' => 'Nhập và xóa chi phí vận hành',
            'roles' => [1, 2],
        ],
    ];

    public function up()
    {
        foreach ($this->permissions as $name => $config) {
            $this->registerPermission($name, $config);
        }
    }

    public function down()
    {
        // Keep permission definitions to avoid breaking existing user overrides.
    }

    private function registerPermission(string $name, array $config): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->db->table('permissions')->where('name', $name)->get()->getRowArray();

        if ($existing) {
            $permissionId = (int)$existing['id'];
            $this->db->table('permissions')->where('id', $permissionId)->update([
                'module_group' => $config['group'],
                'description' => $config['desc'],
                'updated_at' => $now,
            ]);
        } else {
            $this->db->table('permissions')->insert([
                'name' => $name,
                'module_group' => $config['group'],
                'description' => $config['desc'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $permissionId = (int)$this->db->insertID();
        }

        foreach ($config['roles'] as $roleId) {
            $exists = $this->db->table('roles_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->countAllResults();

            if (!$exists) {
                $this->db->table('roles_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }
}
