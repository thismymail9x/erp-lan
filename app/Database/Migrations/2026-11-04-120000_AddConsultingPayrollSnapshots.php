<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Lưu số liệu KPI tại lúc tính lương để đối chiếu quỹ thưởng cuối năm. */
class AddConsultingPayrollSnapshots extends Migration
{
    public function up()
    {
        foreach ([
            'consulting_revenue' => 'Doanh thu thực thu dùng tính KPI tư vấn tháng lương',
            'consulting_monthly' => 'KPI tư vấn 40% cộng vào lương tháng',
            'consulting_annual' => 'KPI tư vấn 60% tích lũy phải chi cuối năm',
            'consulting_milestone' => 'Thưởng vượt mốc KPI tư vấn cộng vào lương tháng',
        ] as $field => $comment) {
            if (!$this->db->fieldExists($field, 'payrolls')) {
                $this->forge->addColumn('payrolls', [$field => [
                    'type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false, 'comment' => $comment,
                ]]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropColumn('payrolls', ['consulting_revenue', 'consulting_monthly', 'consulting_annual', 'consulting_milestone']);
    }
}
