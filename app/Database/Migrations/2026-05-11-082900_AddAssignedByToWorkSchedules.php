<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAssignedByToWorkSchedules extends Migration
{
    public function up()
    {
        $fields = [
            'assigned_by_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null'           => true,
                'after'          => 'employee_id',
                'comment'        => 'ID người giao việc hoặc người được đi thay',
            ],
        ];
        $this->forge->addColumn('work_schedules', $fields);

        // Thêm khóa ngoại
        $this->db->query("ALTER TABLE `work_schedules` ADD CONSTRAINT `fk_ws_assigner` FOREIGN KEY (`assigned_by_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `work_schedules` DROP FOREIGN KEY `fk_ws_assigner`");
        $this->forge->dropColumn('work_schedules', 'assigned_by_id');
    }
}
