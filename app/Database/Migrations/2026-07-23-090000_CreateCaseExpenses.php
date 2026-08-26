<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration tạo dữ liệu chi phí xử lý vụ việc.
 *
 * Chi phí được tách khỏi bảng cases để mỗi khoản chi có vòng duyệt riêng, giữ được
 * lịch sử theo nhân sự và không làm lộ thông tin nhạy cảm trên lịch công tác công khai.
 */
class CreateCaseExpenses extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('case_id', 'work_schedules')) {
            $this->forge->addColumn('work_schedules', [
                'case_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'assigned_by_id',
                    'comment'    => 'Vụ việc liên quan đến lịch công tác, chỉ hiển thị cho người có quyền',
                ],
            ]);
            $this->forge->addForeignKey('case_id', 'cases', 'id', 'SET NULL', 'CASCADE');
        }

        if (!$this->db->tableExists('case_expenses')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'case_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'comment'    => 'Vụ việc phát sinh chi phí xử lý',
                ],
                'work_schedule_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'comment'    => 'Lịch công tác liên quan nếu chi phí được nhập từ lịch',
                ],
                'employee_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'comment'    => 'Nhân sự trực tiếp phát sinh chi phí',
                ],
                'created_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'comment'    => 'Nhân sự tạo phiếu chi phí',
                ],
                'expense_date' => [
                    'type'    => 'DATE',
                    'comment' => 'Ngày phát sinh chi phí',
                ],
                'category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'default'    => 'other',
                    'comment'    => 'Loại chi phí: travel, fuel, taxi, meal, lodging, fee, other',
                ],
                'amount' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'default'    => 0,
                    'comment'    => 'Số tiền đề nghị thanh toán bằng VNĐ',
                ],
                'actual_start_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'comment' => 'Thời điểm bắt đầu xử lý thực tế',
                ],
                'actual_end_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'comment' => 'Thời điểm kết thúc xử lý thực tế',
                ],
                'actual_hours' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '6,2',
                    'default'    => 0,
                    'comment'    => 'Tổng số giờ nhân sự đi xử lý vụ việc',
                ],
                'note' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                    'comment' => 'Ghi chú nghiệp vụ hoặc giải trình khoản chi',
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['draft', 'pending', 'approved', 'rejected'],
                    'default'    => 'pending',
                    'comment'    => 'Trạng thái duyệt chi phí',
                ],
                'approved_amount' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'null'       => true,
                    'comment'    => 'Số tiền kế toán duyệt thực thanh toán',
                ],
                'approval_note' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                    'comment' => 'Ghi chú duyệt hoặc lý do từ chối',
                ],
                'approved_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'comment'    => 'Nhân sự kế toán/quản lý duyệt phiếu',
                ],
                'approved_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'comment' => 'Thời điểm duyệt chi phí',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey(['case_id', 'status']);
            $this->forge->addKey(['employee_id', 'expense_date']);
            $this->forge->addForeignKey('case_id', 'cases', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('work_schedule_id', 'work_schedules', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('created_by', 'employees', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('approved_by', 'employees', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('case_expenses');
        }

        if (!$this->db->tableExists('case_expense_attachments')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'expense_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'comment'    => 'Phiếu chi phí sở hữu chứng từ',
                ],
                'file_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'comment'    => 'Tên tệp chứng từ gốc',
                ],
                'file_path' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                    'comment'    => 'Đường dẫn lưu chứng từ trong writable/uploads',
                ],
                'file_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => true,
                    'comment'    => 'MIME type của tệp chứng từ',
                ],
                'uploaded_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'comment'    => 'Nhân sự tải chứng từ lên',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('expense_id');
            $this->forge->addForeignKey('expense_id', 'case_expenses', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('uploaded_by', 'employees', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('case_expense_attachments');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('case_expense_attachments')) {
            $this->forge->dropTable('case_expense_attachments');
        }
        if ($this->db->tableExists('case_expenses')) {
            $this->forge->dropTable('case_expenses');
        }
        if ($this->db->fieldExists('case_id', 'work_schedules')) {
            $this->forge->dropColumn('work_schedules', 'case_id');
        }
    }
}
