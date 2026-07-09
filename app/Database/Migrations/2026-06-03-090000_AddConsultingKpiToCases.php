<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddConsultingKpiToCases extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('consultant_id', 'cases')) {
            $this->forge->addColumn('cases', [
                'consultant_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'comment'    => 'Nhân sự tư vấn đã chốt khách để tính KPI tư vấn',
                    'after'      => 'assigned_staff_id',
                ],
            ]);
        }

        if (!$this->db->fieldExists('consultation_closed_at', 'cases')) {
            $this->forge->addColumn('cases', [
                'consultation_closed_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'comment' => 'Thời điểm ghi nhận hồ sơ được tư vấn chốt thành công',
                    'after'   => 'consultant_id',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('consultation_closed_at', 'cases')) {
            $this->forge->dropColumn('cases', 'consultation_closed_at');
        }

        if ($this->db->fieldExists('consultant_id', 'cases')) {
            $this->forge->dropColumn('cases', 'consultant_id');
        }
    }
}
