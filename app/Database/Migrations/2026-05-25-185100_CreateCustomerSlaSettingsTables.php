<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration khởi tạo Hệ thống Trạng thái & SLA CSKH Cấu hình Động.
 * Tạo bảng customer_sla_settings và customer_sla_history.
 * Tuân thủ Rule #1 (Việt hóa 100%), Rule #5 (Comments đầy đủ), Rule #6 (deleted_at Soft Delete).
 */
class CreateCustomerSlaSettingsTables extends Migration
{
    public function up()
    {
        // 1. Tạo bảng customer_sla_settings
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
                'comment'        => 'Khóa chính'
            ],
            'status_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Khóa định danh trạng thái'
            ],
            'status_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'comment'    => 'Tên hiển thị trạng thái'
            ],
            'sla_hours' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Thời gian xử lý SLA (giờ), 0 là không giới hạn'
            ],
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '#6c757d',
                'comment'    => 'Màu sắc đại diện trạng thái (Hex hoặc CSS)'
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Thứ tự hiển thị'
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => 'Trạng thái hoạt động (1: Bật, 0: Tắt)'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Ngày tạo'
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Ngày cập nhật'
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Ngày xóa mềm'
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('status_key');
        $this->forge->createTable('customer_sla_settings', true);

        // 2. Tạo bảng customer_sla_history
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
            'assigned_staff_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null'           => true,
                'comment'        => 'Nhân viên phụ trách tại thời điểm này (Liên kết employees)'
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'Trạng thái tư vấn'
            ],
            'start_time' => [
                'type' => 'DATETIME',
                'comment' => 'Thời điểm bắt đầu trạng thái'
            ],
            'end_time' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Thời điểm kết thúc trạng thái'
            ],
            'sla_duration' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Thời hạn SLA được áp dụng (giờ)'
            ],
            'due_time' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Thời gian hạn chót hoàn thành'
            ],
            'sla_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'in_progress',
                'comment'    => 'Trạng thái SLA: in_progress, achieved, overdue, completed_late'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Ngày tạo'
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Ngày cập nhật'
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Ngày xóa mềm'
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('customer_sla_history', true);

        // Thêm khóa ngoại để đảm bảo tính toàn vẹn dữ liệu
        $db = \Config\Database::connect();
        $db->query("ALTER TABLE `customer_sla_history` ADD CONSTRAINT `fk_csh_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE");
        $db->query("ALTER TABLE `customer_sla_history` ADD CONSTRAINT `fk_csh_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL");

        // Chèn dữ liệu mẫu ban đầu cho danh mục trạng thái
        $now = date('Y-m-d H:i:s');
        $db->table('customer_sla_settings')->insertBatch([
            ['status_key' => 'chua_tu_van', 'status_name' => 'Chưa được tư vấn', 'sla_hours' => 24, 'color' => '#6c757d', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'dang_tu_van', 'status_name' => 'Đang tư vấn', 'sla_hours' => 48, 'color' => '#0071e3', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'doi_ho_so', 'status_name' => 'Đợi khách gửi hồ sơ', 'sla_hours' => 120, 'color' => '#ff9500', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'nghien_cuu_bao_phi', 'status_name' => 'Đang nghiên cứu để báo phí', 'sla_hours' => 48, 'color' => '#af52de', 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'thuong_luong', 'status_name' => 'Đang thương lượng', 'sla_hours' => 72, 'color' => '#5856d6', 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'chot_hop_dong', 'status_name' => 'Đã chốt hợp đồng', 'sla_hours' => 0, 'color' => '#34c759', 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'tam_dung', 'status_name' => 'Tạm dừng chăm sóc', 'sla_hours' => 0, 'color' => '#8e8e93', 'sort_order' => 7, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'khong_tiem_nang', 'status_name' => 'Không tiềm năng / Hủy', 'sla_hours' => 0, 'color' => '#ff3b30', 'sort_order' => 8, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down()
    {
        $db = \Config\Database::connect();
        try {
            $db->query("ALTER TABLE `customer_sla_history` DROP FOREIGN KEY `fk_csh_customer`");
            $db->query("ALTER TABLE `customer_sla_history` DROP FOREIGN KEY `fk_csh_staff`");
        } catch (\Exception $e) {}

        $this->forge->dropTable('customer_sla_history', true);
        $this->forge->dropTable('customer_sla_settings', true);
    }
}
