<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFundFlagToOfficeExpenses extends Migration
{
    /**
     * Thêm cờ phân loại dòng tiền trong bảng chi phí vận hành.
     *
     * Dùng chung bảng `office_expenses` để mỗi dòng có thể là tiền nhận vào
     * hoặc khoản chi thực tế. Cách này giữ lịch sử theo tháng trong một nguồn
     * dữ liệu, đồng thời dashboard chỉ cần cộng/trừ theo `is_fund`.
     */
    public function up()
    {
        if (!$this->db->tableExists('office_expenses')) {
            return;
        }

        if (!$this->db->fieldExists('is_fund', 'office_expenses')) {
            $this->forge->addColumn('office_expenses', [
                'is_fund' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => true,
                    'default'    => 0,
                    'after'      => 'amount',
                    'comment'    => '1 = tiền vận hành nhận vào, 0 = khoản chi vận hành',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('office_expenses') && $this->db->fieldExists('is_fund', 'office_expenses')) {
            $this->forge->dropColumn('office_expenses', 'is_fund');
        }
    }
}
