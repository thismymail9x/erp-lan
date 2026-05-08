<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKbStructuredFields extends Migration
{
    public function up()
    {
        $fields = [
            'summary' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Tóm tắt nhanh trong 1 câu',
                'after' => 'title'
            ],
            'problem' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Vấn đề (Dạng Bullet points)',
                'after' => 'summary'
            ],
            'solution' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Cách giải quyết',
                'after' => 'problem'
            ],
            'red_flags' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Lưu ý (Red flags) - Cảnh báo',
                'after' => 'solution'
            ],
        ];
        $this->forge->addColumn('knowledge_base', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('knowledge_base', ['summary', 'problem', 'solution', 'red_flags']);
    }
}
