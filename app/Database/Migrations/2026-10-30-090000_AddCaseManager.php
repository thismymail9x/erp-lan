<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Thêm duy nhất người quản lý vụ việc, tái sử dụng toàn bộ nhân sự và thành viên hiện hữu. */
class AddCaseManager extends Migration
{
    /** Cho phép bản cài bằng mysql.sql chạy migration mà không tạo lại cột đã có. */
    public function up()
    {
        if (!$this->db->fieldExists('case_manager_id', 'cases')) {
            $this->db->query(file_get_contents(__DIR__ . '/20261030_case_manager.sql'));
        }
    }

    /** Chỉ gỡ cột mới, không tác động đến phân công thực hiện hoặc thành viên vụ việc. */
    public function down()
    {
        $this->forge->dropKey('cases', 'idx_cases_manager');
        $this->forge->dropColumn('cases', 'case_manager_id');
    }
}
