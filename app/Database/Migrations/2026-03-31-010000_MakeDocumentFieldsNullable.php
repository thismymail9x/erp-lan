<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeDocumentFieldsNullable extends Migration
{
    public function up()
    {
        // 2026-03-31: FIX FOREIGN KEY CONSTRAINT FOR VOLUNTARY DOCUMENTS
        // Đảm bảo case_id, customer_id, step_id luôn là NULLable để cho phép tài liệu nội bộ
        $fields = [
            'case_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'customer_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'step_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ];
        
        $this->forge->modifyColumn('documents', $fields);
    }

    public function down()
    {
        // Không khuyến khích rollback vì làm mất tính năng optional dms
    }
}
