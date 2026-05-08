<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFinanceToCases extends Migration
{
    public function up()
    {
        // Thêm trường giá trị hợp đồng
        if (!$this->db->fieldExists('contract_value', 'cases')) {
            $this->forge->addColumn('cases', [
                'contract_value' => [
                    'type'       => 'BIGINT',
                    'null'       => true,
                    'default'    => null,
                    'comment'    => 'Giá trị hợp đồng (VND) - Chỉ Hành chính / Admin xem'
                ],
            ]);
        }

        // Thêm trường tiến độ thanh toán
        if (!$this->db->fieldExists('payment_progress', 'cases')) {
            $this->forge->addColumn('cases', [
                'payment_progress' => [
                    'type'       => 'TEXT',
                    'null'       => true,
                    'default'    => null,
                    'comment'    => 'Ghi chú tiến độ thanh toán'
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('contract_value', 'cases')) {
            $this->forge->dropColumn('cases', 'contract_value');
        }
        if ($this->db->fieldExists('payment_progress', 'cases')) {
            $this->forge->dropColumn('cases', 'payment_progress');
        }
    }
}
