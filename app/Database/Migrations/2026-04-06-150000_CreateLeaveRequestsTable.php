<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLeaveRequestsTable extends Migration
{
    public function up()
    {
        // 1. Tạo bảng leave_requests
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'employee_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'leave_type' => [
                'type'       => 'ENUM',
                'constraint' => ['annual', 'sick', 'personal', 'unpaid', 'maternity', 'wedding', 'funeral'],
                'default'    => 'annual',
            ],
            'start_date' => [
                'type' => 'DATE',
            ],
            'end_date' => [
                'type' => 'DATE',
            ],
            'total_days' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,1',
                'default'    => 0.0,
            ],
            'reason' => [
                'type' => 'TEXT',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'cancelled'],
                'default'    => 'pending',
            ],
            'approver_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approval_note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('approver_id', 'employees', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('leave_requests', true);

        // 2. Bổ sung quyền hạn (Permissions)
        $db = \Config\Database::connect();
        
        $permissions = [
            ['leave.view', 'Nhân sự & Tài khoản', 'Xem danh sách đơn nghỉ phép (cá nhân/phòng ban)'],
            ['leave.manage', 'Nhân sự & Tài khoản', 'Tạo và quản lý đơn nghỉ phép cá nhân'],
            ['leave.approve', 'Nhân sự & Tài khoản', 'Phê duyệt đơn xin nghỉ phép']
        ];

        foreach ($permissions as $p) {
            $db->query("INSERT INTO permissions (name, module_group, description, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE description=VALUES(description), updated_at=NOW()", $p);
        }

        // --- PHÂN QUYỀN MẶC ĐỊNH CHO CÁC VAI TRÒ ---
        // Admin (ID 1) & Trưởng phòng (ID 3): Toàn quyền Leave
        $rolesToFull = [1, 3];
        foreach ($rolesToFull as $roleId) {
            foreach ($permissions as $p) {
                $permId = $db->table('permissions')->select('id')->where('name', $p[0])->get()->getRow()->id;
                $db->query("INSERT INTO roles_permissions (role_id, permission_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE role_id=role_id", [$roleId, $permId]);
            }
        }

        // Nhân viên & các vai trò khác (4, 5, 6, 7): Chỉ Xem & Quản lý đơn của mình
        $rolesToSelf = [4, 5, 6, 7];
        $selfPerms = ['leave.view', 'leave.manage'];
        foreach ($rolesToSelf as $roleId) {
            foreach ($selfPerms as $pName) {
                $permId = $db->table('permissions')->select('id')->where('name', $pName)->get()->getRow()->id;
                $db->query("INSERT INTO roles_permissions (role_id, permission_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE role_id=role_id", [$roleId, $permId]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('leave_requests');
        // Lưu ý: Không khuyến khích xóa permissions trong Down nếu có thể gây lỗi dữ liệu liên kết khác
    }
}
