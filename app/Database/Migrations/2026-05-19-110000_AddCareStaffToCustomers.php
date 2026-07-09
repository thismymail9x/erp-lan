<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCareStaffToCustomers extends Migration
{
    public function up()
    {
        $fields = [
            'assigned_care_staff_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'created_by',
                'comment' => 'ID nhân sự phụ trách chăm sóc tư vấn (Liên kết bảng employees)'
            ]
        ];
        $this->forge->addColumn('customers', $fields);

        // Add foreign key
        $db = \Config\Database::connect();
        $db->query("ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_care_staff` FOREIGN KEY (`assigned_care_staff_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL");
    }

    public function down()
    {
        $db = \Config\Database::connect();
        // Drop foreign key first
        try {
            $db->query("ALTER TABLE `customers` DROP FOREIGN KEY `fk_customers_care_staff`");
        } catch (\Exception $e) {}

        $this->forge->dropColumn('customers', 'assigned_care_staff_id');
    }
}
