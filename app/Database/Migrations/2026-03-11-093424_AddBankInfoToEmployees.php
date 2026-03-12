<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBankInfoToEmployees extends Migration
{
    public function up()
    {
        $this->forge->addColumn('employees', [
            'bank_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'position'
            ],
            'bank_account' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'bank_name'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employees', ['bank_name', 'bank_account']);
    }
}
