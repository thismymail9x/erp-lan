<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration cập nhật cơ sở dữ liệu cho tính năng Làm sạch Lead và Phân công tự động (Phase 2 & 3).
 * Thêm các cột phục vụ lọc trùng lặp, chấm độ nóng, theo dõi deadline phản hồi 2h và gán chuyên môn cho nhân sự.
 * 
 * Tuân thủ Quy tắc số 1 (Comment tiếng Việt) và Quy tắc số 5 (Ghi chú Inline DB tiếng Việt).
 */
class UpdateChatTablesForAssignment extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Cập nhật bảng zalo_followers
        $zaloFields = [
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'phone_number',
                'comment'    => 'Địa chỉ email khách hàng cung cấp qua chat'
            ],
            'lead_warmth' => [
                'type'       => 'ENUM',
                'constraint' => ['hot', 'warm', 'cold'],
                'default'    => 'cold',
                'after'      => 'tags',
                'comment'    => 'Độ nóng của lead: hot (Nóng), warm (Ấm), cold (Lạnh)'
            ],
            'is_duplicate' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'lead_warmth',
                'comment'    => 'Cờ báo trùng lặp (1: Trùng lặp, 0: Bình thường)'
            ],
            'duplicate_of' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'is_duplicate',
                'comment'    => 'ID liên hệ chính trong zalo_followers bị trùng'
            ],
            'assigned_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'assigned_to',
                'comment' => 'Thời điểm phân công nhân sự gần nhất'
            ],
            'first_response_deadline' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'assigned_at',
                'comment' => 'Hạn chót để phản hồi khách hàng lần đầu (2 tiếng)'
            ],
            'first_responded_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'first_response_deadline',
                'comment' => 'Thời điểm phản hồi thực tế lần đầu tiên'
            ],
            'is_overdue' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'first_responded_at',
                'comment'    => 'Cờ đánh dấu quá hạn phản hồi (1: Quá hạn, 0: Đúng hạn)'
            ],
            'deleted_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'updated_at',
                'comment' => 'Thời gian xóa mềm (Soft Delete)'
            ]
        ];
        $this->forge->addColumn('zalo_followers', $zaloFields);

        // Thêm khóa ngoại cho duplicate_of ở zalo_followers
        $db->query("ALTER TABLE `zalo_followers` ADD CONSTRAINT `fk_zalo_follower_dup` FOREIGN KEY (`duplicate_of`) REFERENCES `zalo_followers` (`id`) ON DELETE SET NULL");

        // 2. Cập nhật bảng messenger_contacts
        $messengerFields = [
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'phone_number',
                'comment'    => 'Địa chỉ email khách hàng cung cấp qua chat'
            ],
            'lead_warmth' => [
                'type'       => 'ENUM',
                'constraint' => ['hot', 'warm', 'cold'],
                'default'    => 'cold',
                'after'      => 'tags',
                'comment'    => 'Độ nóng của lead: hot (Nóng), warm (Ấm), cold (Lạnh)'
            ],
            'is_duplicate' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'lead_warmth',
                'comment'    => 'Cờ báo trùng lặp (1: Trùng lặp, 0: Bình thường)'
            ],
            'duplicate_of' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'is_duplicate',
                'comment'    => 'ID liên hệ chính trong messenger_contacts bị trùng'
            ],
            'assigned_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'assigned_to',
                'comment' => 'Thời điểm phân công nhân sự gần nhất'
            ],
            'first_response_deadline' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'assigned_at',
                'comment' => 'Hạn chót để phản hồi khách hàng lần đầu (2 tiếng)'
            ],
            'first_responded_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'first_response_deadline',
                'comment' => 'Thời điểm phản hồi thực tế lần đầu tiên'
            ],
            'is_overdue' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'first_responded_at',
                'comment'    => 'Cờ đánh dấu quá hạn phản hồi (1: Quá hạn, 0: Đúng hạn)'
            ]
        ];
        $this->forge->addColumn('messenger_contacts', $messengerFields);

        // Thêm khóa ngoại cho duplicate_of ở messenger_contacts
        $db->query("ALTER TABLE `messenger_contacts` ADD CONSTRAINT `fk_messenger_contact_dup` FOREIGN KEY (`duplicate_of`) REFERENCES `messenger_contacts` (`id`) ON DELETE SET NULL");

        // 3. Cập nhật bảng employees
        $employeeFields = [
            'specialties' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'position',
                'comment'    => 'JSON array các lĩnh vực chuyên môn (VD: ["Đất đai","Ly hôn"])'
            ],
            'max_workload' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 15,
                'after'      => 'specialties',
                'comment'    => 'Giới hạn số lead tối đa nhân sự được nhận đồng thời'
            ]
        ];
        $this->forge->addColumn('employees', $employeeFields);
    }

    public function down()
    {
        $db = \Config\Database::connect();

        // Drop foreign keys
        try {
            $db->query("ALTER TABLE `zalo_followers` DROP FOREIGN KEY `fk_zalo_follower_dup`");
        } catch (\Exception $e) {}

        try {
            $db->query("ALTER TABLE `messenger_contacts` DROP FOREIGN KEY `fk_messenger_contact_dup`");
        } catch (\Exception $e) {}

        // Drop columns
        $this->forge->dropColumn('zalo_followers', ['email', 'lead_warmth', 'is_duplicate', 'duplicate_of', 'assigned_at', 'first_response_deadline', 'first_responded_at', 'is_overdue', 'deleted_at']);
        $this->forge->dropColumn('messenger_contacts', ['email', 'lead_warmth', 'is_duplicate', 'duplicate_of', 'assigned_at', 'first_response_deadline', 'first_responded_at', 'is_overdue']);
        $this->forge->dropColumn('employees', ['specialties', 'max_workload']);
    }
}
