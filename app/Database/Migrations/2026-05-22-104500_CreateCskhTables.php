<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration tạo các bảng phục vụ tính năng Chăm sóc Khách hàng Cũ (CSKH).
 * Bao gồm ALTER bảng customers, bảng customer_care_plans, customer_care_tasks, customer_loyalty.
 * Tuân thủ Rule #1 (Việt hóa 100%), Rule #5 (Comments đầy đủ), Rule #6 (Soft Delete).
 */
class CreateCskhTables extends Migration
{
    public function up()
    {
        // 1. ALTER bảng customers để thêm 7 cột mới phục vụ CSKH
        $this->forge->addColumn('customers', [
            'customer_segment' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
                'comment'    => 'Phân nhóm khách hàng A/B/C: vip (VIP - Nhóm A), regular (Phổ thông - Nhóm B), potential (Tiềm năng - Nhóm C)',
                'after'      => 'assigned_care_staff_id',
            ],
            'zalo_phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'comment'    => 'Số điện thoại Zalo riêng (nếu khác SĐT chính)',
                'after'      => 'customer_segment',
            ],
            'occupation' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Nghề nghiệp/Lĩnh vực hoạt động',
                'after'      => 'zalo_phone',
            ],
            'care_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'default'    => 'new',
                'comment'    => 'Trạng thái CSKH: new (Mới), phase1 (Giai đoạn 1), phase2 (Giai đoạn 2), phase3 (Giai đoạn 3), completed (Đã hoàn thành chăm sóc), dormant (Bỏ quên/cần kích hoạt lại)',
                'after'      => 'occupation',
            ],
            'service_completed_date' => [
                'type'       => 'DATE',
                'null'       => true,
                'comment'    => 'Ngày hoàn thành dịch vụ/hợp đồng gần nhất',
                'after'      => 'care_status',
            ],
            'referral_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Số lần khách giới thiệu người khác',
                'after'      => 'service_completed_date',
            ]
        ]);

        // 2. Tạo bảng customer_care_plans
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
                'comment'        => 'Khóa chính'
            ],
            'customer_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'comment'        => 'Khóa ngoại liên kết bảng customers'
            ],
            'phase' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'comment'    => 'Giai đoạn CSKH: phase1 (Giai đoạn 1), phase2 (Giai đoạn 2), phase3 (Giai đoạn 3)'
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Tiêu đề kế hoạch chăm sóc'
            ],
            'description' => [
                'type'       => 'TEXT',
                'null'       => true,
                'comment'    => 'Mô tả chi tiết kế hoạch'
            ],
            'assigned_staff_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null'           => true,
                'comment'        => 'Nhân sự chịu trách nhiệm chăm sóc (Liên kết employees)'
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'default'    => 'pending',
                'comment'    => 'Trạng thái: pending (Chờ), in_progress (Đang làm), completed (Hoàn thành), skipped (Bỏ qua)'
            ],
            'due_date' => [
                'type'       => 'DATE',
                'null'       => true,
                'comment'    => 'Hạn chót hoàn thành kế hoạch'
            ],
            'completed_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Thời điểm hoàn thành thực tế'
            ],
            'result_notes' => [
                'type'       => 'TEXT',
                'null'       => true,
                'comment'    => 'Kết quả hoặc ghi chú thu thập được từ khách'
            ],
            'created_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Thời điểm tạo bản ghi'
            ],
            'updated_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Thời điểm cập nhật bản ghi'
            ],
            'deleted_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Thời điểm xóa mềm (Soft Delete)'
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('assigned_staff_id', 'employees', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('customer_care_plans', true);

        // 3. Tạo bảng customer_care_tasks
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
                'comment'        => 'Khóa chính'
            ],
            'care_plan_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'comment'        => 'Khóa ngoại liên kết customer_care_plans'
            ],
            'customer_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'comment'        => 'Khóa ngoại liên kết customers để query nhanh'
            ],
            'task_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Loại công việc: thank_you, feedback, follow_up, gift, content, call, etc.'
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Tiêu đề công việc CSKH'
            ],
            'description' => [
                'type'       => 'TEXT',
                'null'       => true,
                'comment'    => 'Mô tả chi tiết công việc'
            ],
            'channel' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Kênh tương tác: zalo, email, call, meeting, letter'
            ],
            'is_completed' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Trạng thái hoàn thành: 0 (Chưa), 1 (Đã xong)'
            ],
            'completed_by' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null'           => true,
                'comment'        => 'Nhân sự thực hiện công việc (Liên kết employees)'
            ],
            'completed_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Thời điểm hoàn thành thực tế'
            ],
            'due_date' => [
                'type'       => 'DATE',
                'null'       => true,
                'comment'    => 'Hạn chót hoàn thành công việc'
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Thứ tự sắp xếp hiển thị'
            ],
            'created_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Thời điểm tạo bản ghi'
            ],
            'updated_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Thời điểm cập nhật bản ghi'
            ],
            'deleted_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Thời điểm xóa mềm (Soft Delete)'
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('care_plan_id', 'customer_care_plans', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('completed_by', 'employees', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('customer_care_tasks', true);

        // 4. Tạo bảng customer_loyalty
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
                'comment'        => 'Khóa chính'
            ],
            'customer_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'comment'        => 'Khóa ngoại liên kết customers'
            ],
            'loyalty_tier' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'default'    => 'standard',
                'comment'    => 'Hạng thành viên: standard (Tiêu chuẩn), silver (Bạc), gold (Vàng), vip (VIP)'
            ],
            'benefits' => [
                'type'       => 'TEXT',
                'null'       => true,
                'comment'    => 'Quyền lợi được áp dụng (Định dạng JSON)'
            ],
            'points' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Điểm tích lũy'
            ],
            'referral_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'comment'    => 'Mã giới thiệu duy nhất của khách hàng'
            ],
            'total_referrals' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Tổng số lượng khách giới thiệu thành công'
            ],
            'notes' => [
                'type'       => 'TEXT',
                'null'       => true,
                'comment'    => 'Ghi chú thêm về loyalty/VIP'
            ],
            'activated_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Thời điểm kích hoạt thẻ/hạng'
            ],
            'created_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Thời điểm tạo bản ghi'
            ],
            'updated_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Thời điểm cập nhật bản ghi'
            ],
            'deleted_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Thời điểm xóa mềm (Soft Delete)'
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('referral_code');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('customer_loyalty', true);
    }

    public function down()
    {
        $this->forge->dropTable('customer_loyalty', true);
        $this->forge->dropTable('customer_care_tasks', true);
        $this->forge->dropTable('customer_care_plans', true);
        
        $this->forge->dropColumn('customers', [
            'customer_segment',
            'zalo_phone',
            'occupation',
            'care_status',
            'service_completed_date',
            'referral_count'
        ]);
    }
}
