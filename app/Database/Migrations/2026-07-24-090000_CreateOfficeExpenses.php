<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tạo bảng chi phí vận hành nội bộ.
 *
 * Bảng này tách các khoản điện, nước, internet, văn phòng phẩm khỏi chi phí vụ việc
 * để thống kê công ty có thể cộng hai nguồn nhưng vẫn giữ đúng bản chất dữ liệu.
 */
class CreateOfficeExpenses extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('office_expenses')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'expense_date' => [
                'type'    => 'DATE',
                'comment' => 'Ngày phát sinh chi phí vận hành',
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'comment'    => 'Loại chi phí vận hành: điện, nước, internet, văn phòng phẩm hoặc nhóm khác',
            ],
            'vendor' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Nhà cung cấp hoặc đơn vị nhận thanh toán',
            ],
            'amount' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => 'Số tiền chi phí vận hành bằng VND',
            ],
            'payment_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'cash',
                'comment'    => 'Phương thức thanh toán: cash, transfer, card hoặc other',
            ],
            'note' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Ghi chú kỳ thanh toán, mã hóa đơn hoặc lý do phát sinh',
            ],
            'receipt_file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Tên file chứng từ gốc do kế toán tải lên',
            ],
            'receipt_file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'Đường dẫn lưu chứng từ trong writable/uploads',
            ],
            'receipt_file_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
                'comment'    => 'MIME type của file chứng từ',
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Nhân sự kế toán hoặc admin tạo khoản chi',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Thời điểm tạo bản ghi',
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Thời điểm cập nhật gần nhất',
            ],
            'deleted_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Thời điểm xóa mềm bản ghi',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['expense_date', 'category']);
        $this->forge->addKey('created_by');
        $this->forge->addForeignKey('created_by', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('office_expenses');
    }

    public function down()
    {
        if ($this->db->tableExists('office_expenses')) {
            $this->forge->dropTable('office_expenses');
        }
    }
}
