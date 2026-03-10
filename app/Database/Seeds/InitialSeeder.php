<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialSeeder extends Seeder
{
    public function run()
    {
        // 1. Seed Roles
        $roles = [
            [
                'name'        => 'Admin',
                'description' => 'System Administrator with full access.',
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'name'        => 'Lawyer',
                'description' => 'Legal professionals managing cases.',
                'created_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'name'        => 'Staff',
                'description' => 'Support staff for administration and timekeeping.',
                'created_at'  => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('roles')->insertBatch($roles);

        // 2. Seed Permissions (Basic)
        $permissions = [
            ['name' => 'manage_employees', 'description' => 'Can create/edit employees'],
            ['name' => 'manage_cases', 'description' => 'Can manage legal cases'],
            ['name' => 'view_accounting', 'description' => 'Can view financial reports'],
            ['name' => 'manage_contracts', 'description' => 'Can handle legal contracts'],
        ];

        $this->db->table('permissions')->insertBatch($permissions);

        // 3. Admin User
        $adminRole = $this->db->table('roles')->where('name', 'Admin')->get()->getRow();
        
        $adminUser = [
            'role_id'       => $adminRole->id,
            'email'         => 'admin@lawfirm.erp',
            'password'      => password_hash('admin123', PASSWORD_BCRYPT),
            'active_status' => 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $this->db->table('users')->insert($adminUser);
        $userId = $this->db->insertID();

        // 4. Admin Employee Profile
        $adminEmployee = [
            'user_id'     => $userId,
            'full_name'   => 'System Administrator',
            'position'    => 'IT Manager',
            'salary_base' => 0,
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        $this->db->table('employees')->insert($adminEmployee);
    }
}
