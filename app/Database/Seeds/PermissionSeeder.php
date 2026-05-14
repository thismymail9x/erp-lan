<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        
        $perm = [
            'name'         => 'attendance.view_all',
            'module_group' => 'Thời gian & Chấm công',
            'description'  => 'Quyền theo dõi chấm công tổng (Toàn công ty)',
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s')
        ];

        // 1. Insert permission
        $existing = $db->table('permissions')->where('name', $perm['name'])->get()->getRow();
        if (!$existing) {
            $db->table('permissions')->insert($perm);
            $permId = $db->insertID();
            echo "Permission 'attendance.view_all' created.\n";
        } else {
            $permId = $existing->id;
            echo "Permission 'attendance.view_all' already exists.\n";
        }

        // 2. Assign to Admin (Role 1) and Mod (Role 2) and Manager (Role 3)
        $roles = [1, 3];
        foreach ($roles as $roleId) {
            $hasLink = $db->table('roles_permissions')
                         ->where(['role_id' => $roleId, 'permission_id' => $permId])
                         ->countAllResults();
            
            if ($hasLink == 0) {
                $db->table('roles_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permId
                ]);
                echo "Assigned to Role ID: $roleId\n";
            }
        }
    }
}
