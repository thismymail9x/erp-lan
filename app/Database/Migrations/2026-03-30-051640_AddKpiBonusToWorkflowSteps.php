<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKpiBonusToWorkflowSteps extends Migration
{
    public function up()
    {
        // 1. Thêm vào bảng workflow_template_steps
        $this->forge->addColumn('workflow_template_steps', [
            'kpi_reward' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
                'after'      => 'next_step_condition',
                'comment'    => 'Mức thưởng/KPI cho bước này'
            ]
        ]);

        // 2. Thêm vào bảng case_steps
        $this->forge->addColumn('case_steps', [
            'kpi_reward' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
                'after'      => 'next_step_condition'
            ],
            'overdue_notified' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'earned_kpi',
                'comment'    => 'Đã bắn thông báo quá hạn chưa'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('workflow_template_steps', ['kpi_reward']);
        $this->forge->dropColumn('case_steps', ['kpi_reward', 'overdue_notified']);
    }
}
