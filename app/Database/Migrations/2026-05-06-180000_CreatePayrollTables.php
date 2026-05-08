<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Khởi tạo hệ thống Quản lý Lương (Payroll Management).
 * Quy tắc 4 & 5: Thiết lập bảng cấu hình ngày công và bảng lương chi tiết.
 */
class CreatePayrollTables extends Migration
{
    public function up()
    {
        // 1. Bảng cấu hình ngày công chuẩn hàng tháng
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'month' => [
                'type'       => 'VARCHAR',
                'constraint' => '7',
                'comment'    => 'Tháng tính lương (YYYY-MM)',
            ],
            'working_days_json' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Danh sách các ngày đi làm (JSON)',
            ],
            'holidays_json' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Danh sách các ngày lễ (JSON)',
            ],
            'total_standard_days' => [
                'type'    => 'FLOAT',
                'default' => 0,
                'comment' => 'Tổng ngày công chuẩn của tháng',
            ],
            'is_closed' => [
                'type'       => 'TINYINT',
                'constraint' => '1',
                'default'    => 0,
                'comment'    => 'Cờ hiệu đã chốt sổ lương',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('month');
        $this->forge->createTable('payroll_configs', true);

        // 2. Bảng lương chi tiết nhân sự
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'employee_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'comment'  => 'ID nhân viên sở hữu bảng lương',
            ],
            'month' => [
                'type'       => 'VARCHAR',
                'constraint' => '7',
                'comment'    => 'Tháng nhận lương (YYYY-MM)',
            ],
            'salary_base' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'comment'    => 'Mức lương cơ bản',
            ],
            'salary_kpi' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'comment'    => 'Thưởng KPI thi đua',
            ],
            'salary_allowance' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'comment'    => 'Phụ cấp cố định',
            ],
            'salary_bonus' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'comment'    => 'Thưởng thêm ngoài KPI',
            ],
            'salary_deduction' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'comment'    => 'Tổng tiền phạt/khấu trừ',
            ],
            'total_standard_days' => [
                'type'    => 'FLOAT',
                'default' => 0,
                'comment' => 'Số ngày công chuẩn',
            ],
            'actual_working_days' => [
                'type'    => 'FLOAT',
                'default' => 0,
                'comment' => 'Số ngày công thực tế',
            ],
            'attendance_violations' => [
                'type'    => 'INT',
                'default' => 0,
                'comment' => 'Số lần vi phạm điểm danh',
            ],
            'net_salary' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'comment'    => 'Lương thực lĩnh',
            ],
            'status' => [
                'type'       => "ENUM('pending', 'approved', 'paid')",
                'default'    => 'pending',
                'comment'    => 'Trạng thái thanh toán',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Ghi chú chi tiết',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['employee_id', 'month']);
        $this->forge->createTable('payrolls', true);
    }

    public function down()
    {
        $this->forge->dropTable('payrolls');
        $this->forge->dropTable('payroll_configs');
    }
}
