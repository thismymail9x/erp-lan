<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKpiOverrideAndEndOfDayDeadlines extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('kpi_override_approved', 'case_steps')) {
            $this->forge->addColumn('case_steps', [
                'kpi_override_approved' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'after'      => 'kpi_reward',
                    'comment'    => 'Quản lý ghi nhận KPI dù step hoàn thành sau hạn',
                ],
                'kpi_override_reason' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                    'after'   => 'kpi_override_approved',
                    'comment' => 'Lý do chấp thuận KPI ngoại lệ',
                ],
                'kpi_override_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'kpi_override_reason',
                    'comment'    => 'Nhân sự quản lý đã chấp thuận KPI ngoại lệ',
                ],
                'kpi_override_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'after'   => 'kpi_override_by',
                    'comment' => 'Thời điểm chấp thuận KPI ngoại lệ',
                ],
            ]);
        }

        $this->db->query("UPDATE case_steps SET deadline = CONCAT(DATE(deadline), ' 23:59:59') WHERE deadline IS NOT NULL");
        $this->db->query("UPDATE cases SET deadline = CONCAT(DATE(deadline), ' 23:59:59') WHERE deadline IS NOT NULL");
    }

    public function down()
    {
        if ($this->db->fieldExists('kpi_override_approved', 'case_steps')) {
            $this->forge->dropColumn('case_steps', [
                'kpi_override_approved',
                'kpi_override_reason',
                'kpi_override_by',
                'kpi_override_at',
            ]);
        }
    }
}
