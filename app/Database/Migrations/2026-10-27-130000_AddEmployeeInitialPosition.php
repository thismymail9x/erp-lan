<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmployeeInitialPosition extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('initial_position', 'employees')) {
            $this->forge->addColumn('employees', [
                'initial_position' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('employees', 'initial_position');
    }
}
