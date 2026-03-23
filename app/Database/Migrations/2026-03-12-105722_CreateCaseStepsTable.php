<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCaseStepsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'case_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'step_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'duration_days' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'deadline' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'values'     => ['pending', 'active', 'completed', 'overdue'],
                'default'    => 'pending',
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'required_documents' => [
                'type' => 'TEXT', // Use TEXT for JSON compatibility if needed
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
        $this->forge->addForeignKey('case_id', 'cases', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('case_steps');
    }

    public function down()
    {
        $this->forge->dropTable('case_steps');
    }

}
