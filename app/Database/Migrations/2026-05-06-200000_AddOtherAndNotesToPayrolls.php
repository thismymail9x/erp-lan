<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Thêm cột điều chỉnh Khác và Ghi chú JSON vào bảng lương
 */
class AddOtherAndNotesToPayrolls extends Migration
{
    public function up()
    {
        $this->forge->addColumn('payrolls', [
            'salary_other' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'comment'    => 'Khoản điều chỉnh khác (+ hoặc -)',
                'after'      => 'salary_deduction'
            ],
            'notes_json' => [
                'type'       => 'TEXT',
                'null'       => true,
                'comment'    => 'Danh sách ghi chú dạng JSON',
                'after'      => 'salary_other'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('payrolls', 'salary_other');
        $this->forge->dropColumn('payrolls', 'notes_json');
    }
}
