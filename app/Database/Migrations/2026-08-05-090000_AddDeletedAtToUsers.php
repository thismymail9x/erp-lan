<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeletedAtToUsers extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('deleted_at', 'users')) {
            $this->forge->addColumn('users', [
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'updated_at',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('deleted_at', 'users')) {
            $this->forge->dropColumn('users', 'deleted_at');
        }
    }
}
