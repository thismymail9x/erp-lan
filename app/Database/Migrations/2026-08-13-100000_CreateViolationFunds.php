<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tạo bảng quỹ vi phạm nội bộ.
 *
 * Bảng lưu từng khoản vi phạm để nhân sự/admin ghi nhận, hành chính theo dõi thu
 * và người vi phạm nhận thông báo minh bạch theo từng lỗi phát sinh.
 */
class CreateViolationFunds extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('violation_funds')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'employee_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'Nhân sự bị ghi nhận vi phạm nội bộ',
            ],
            'violation_date' => [
                'type' => 'DATE',
                'comment' => 'Ngày xảy ra hành vi vi phạm',
            ],
            'due_month' => [
                'type' => 'CHAR',
                'constraint' => 7,
                'comment' => 'Tháng hành chính cần theo dõi thu quỹ, định dạng YYYY-MM',
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'comment' => 'Nhóm vi phạm: chấm công, báo cáo, bảo mật, nội quy, nghỉ phép hoặc nhóm khác',
            ],
            'behavior' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'comment' => 'Hành vi vi phạm cụ thể theo quy định hoặc nội dung nhập thủ công',
            ],
            'rank_level' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'unsigned' => true,
                'default' => 2,
                'comment' => 'Cấp bậc áp dụng tại thời điểm vi phạm: 1, 2 hoặc 3',
            ],
            'base_amount' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 0,
                'comment' => 'Mức sàn theo bảng quy định trước khi điều chỉnh tái phạm hoặc nhập tay',
            ],
            'amount' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 0,
                'comment' => 'Số tiền thực tế cần thu vào quỹ vi phạm nội bộ bằng VND',
            ],
            'recurrence_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 1,
                'comment' => 'Số lần tái phạm cùng lỗi trong tháng tại thời điểm ghi nhận',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'notified',
                'comment' => 'Trạng thái thu quỹ: notified, collected hoặc waived',
            ],
            'collection_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'cash',
                'comment' => 'Hình thức thu: tiền mặt, chuyển khoản, cấn trừ bảng lương hoặc hình thức khác',
            ],
            'explanation' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Giải trình hoặc bối cảnh xem xét trước khi ghi nhận vi phạm',
            ],
            'hr_note' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Ghi chú của nhân sự/admin khi lập khoản vi phạm',
            ],
            'admin_note' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Ghi chú hành chính khi thu, miễn hoặc xử lý khoản vi phạm',
            ],
            'notified_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Thời điểm hệ thống thông báo cho người vi phạm và hành chính',
            ],
            'collected_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Thời điểm hành chính xác nhận đã thu khoản vi phạm',
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'Nhân sự tạo bản ghi vi phạm',
            ],
            'updated_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Nhân sự cập nhật trạng thái hoặc ghi chú gần nhất',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Thời điểm tạo bản ghi',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Thời điểm cập nhật gần nhất',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Thời điểm xóa mềm bản ghi',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['due_month', 'status']);
        $this->forge->addKey(['employee_id', 'violation_date']);
        $this->forge->addKey('created_by');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('updated_by', 'employees', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('violation_funds');
    }

    public function down()
    {
        if ($this->db->tableExists('violation_funds')) {
            $this->forge->dropTable('violation_funds');
        }
    }
}
