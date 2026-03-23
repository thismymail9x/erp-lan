<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateCustomerAndCaseTables extends Migration
{
    public function up()
    {
        // 1. Update Customers Table
        $this->forge->addColumn('customers', [
            'identity_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'after'      => 'type',
            ],
            'note' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'address',
            ],
        ]);

        // 2. Update Cases Table
        // (Bỏ qua bước rename internal_code thành code vì migration đầu tiên đã được cập nhật)

        // Add New Columns
        $this->forge->addColumn('cases', [
            'type' => [
                'type'       => 'ENUM',
                'values'     => ['to_tung_dan_su', 'thu_tuc_hanh_chinh', 'xoa_an_tich', 'ly_hon_thuan_tinh', 'tu_van', 'khac'],
                'default'    => 'khac',
                'after'      => 'title',
            ],
            'deadline' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'status',
            ],
            'current_step' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'deadline',
            ],
            'assigned_staff_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'assigned_lawyer_id',
            ],
        ]);

        // Status update
        $this->forge->modifyColumn('cases', [
            'status' => [
                'type'       => 'ENUM',
                'values'     => ['moi_tiep_nhan', 'dang_xu_ly', 'cho_tham_tam', 'da_giai_quyet', 'dong_ho_so', 'huy'],
                'default'    => 'moi_tiep_nhan',
            ],
        ]);

        // Add Foreign Key for assigned_staff_id using raw SQL for accuracy
        $this->db->query("ALTER TABLE `cases` ADD CONSTRAINT `cases_assigned_staff_id_foreign` FOREIGN KEY (`assigned_staff_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL ON UPDATE CASCADE");
    }

    public function down()
    {
        // Rollback strategy: Drop columns, rename back, restore old enums if needed
    }

}
