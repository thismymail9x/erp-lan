<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKpiRewardToSteps extends Migration
{
    public function up()
    {
        // 2026-03-31: CẬP NHẬT CẤU TRÚC TIỀN THƯỞNG KPI
        
        $fields = [
            'kpi_reward' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'step_name',
                'comment'    => 'Mức thưởng KPI cho bước này'
            ],
        ];

        // Thêm vào bảng Template
        if (!$this->db->fieldExists('kpi_reward', 'workflow_template_steps')) {
            $this->forge->addColumn('workflow_template_steps', $fields);
        }

        // Thêm vào bảng Vụ việc thực tế
        if (!$this->db->fieldExists('kpi_reward', 'case_steps')) {
            $this->forge->addColumn('case_steps', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('workflow_template_steps', 'kpi_reward');
        $this->forge->dropColumn('case_steps', 'kpi_reward');
    }
}
