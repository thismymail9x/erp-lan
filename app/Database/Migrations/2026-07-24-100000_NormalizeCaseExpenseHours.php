<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Chuẩn hóa giờ chi phí vụ việc không âm.
 *
 * Một số nguồn nhập lịch có thể từng lưu sai chiều thời gian khiến actual_hours âm.
 * Chi phí xử lý chỉ cần số giờ thực tế đã bỏ ra, nên dữ liệu lịch sử được đổi về trị tuyệt đối.
 */
class NormalizeCaseExpenseHours extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('case_expenses')) {
            $this->db->query('UPDATE `case_expenses` SET `actual_hours` = ABS(`actual_hours`) WHERE `actual_hours` < 0');
        }
    }

    public function down()
    {
        // Không đảo ngược vì giá trị âm là dữ liệu lỗi.
    }
}
