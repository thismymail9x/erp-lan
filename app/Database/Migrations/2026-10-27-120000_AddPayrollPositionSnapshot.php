<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayrollPositionSnapshot extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('position_snapshot', 'payrolls')) {
            $this->forge->addColumn('payrolls', [
                'position_snapshot' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('payrolls', 'position_snapshot');
    }
}
