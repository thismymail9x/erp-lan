<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CleanupPayrollColumns extends Migration
{
    public function up()
    {
        // 1. Migrate data: sum salary_other into salary_bonus
        $this->db->query("UPDATE payrolls SET salary_bonus = salary_bonus + IFNULL(salary_other, 0)");

        // 2. Drop columns
        $this->forge->dropColumn('payrolls', 'salary_other');
        $this->forge->dropColumn('payrolls', 'salary_allowance');
        $this->forge->dropColumn('payrolls', 'notes');
    }

    public function down()
    {
        // Not easily reversible due to data migration, but adding columns back
        $this->forge->addColumn('payrolls', [
            'salary_other'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'salary_allowance' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'notes'            => ['type' => 'TEXT', 'null' => true],
        ]);
    }
}
