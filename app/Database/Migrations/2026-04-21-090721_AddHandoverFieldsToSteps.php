<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHandoverFieldsToSteps extends Migration
{
    public function up()
    {
        // Thêm trường người phụ trách bước và người thực tế hoàn thành bước
        $fields = [
            'assigned_to' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'case_id',
                'comment'    => 'Nhân viên được giao phụ trách bước này (Để tính KPI tiềm năng)'
            ],
            'completed_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'completed_at',
                'comment'    => 'Nhân viên thực tế đã hoàn thành bước này (Để chốt KPI thực nhận)'
            ],
        ];

        $this->forge->addColumn('case_steps', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('case_steps', 'assigned_to');
        $this->forge->dropColumn('case_steps', 'completed_by');
    }
}
