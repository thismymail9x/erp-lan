<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

/**
 * Migration: Tạo bảng zns_logs
 * Mô tả: Ghi log chi tiết từng tin nhắn ZNS đã gửi.
 * Dùng để theo dõi trạng thái gửi, debug lỗi, và thống kê chiến dịch.
 */
class CreateZnsLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'campaign_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => 'FK tới zns_campaigns.id (NULL nếu gửi đơn lẻ)'],
            'customer_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => 'FK tới customers.id'],
            'template_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'comment' => 'ID mẫu tin Zalo'],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => 'SĐT người nhận (format 84xxx)'],
            'template_data' => ['type' => 'JSON', 'null' => true, 'comment' => 'Dữ liệu đã gửi vào template (JSON)'],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'sent', 'delivered', 'failed'], 'default' => 'pending', 'comment' => 'Trạng thái: pending=chờ gửi, sent=đã gửi, delivered=đã nhận, failed=thất bại'],
            'zalo_msg_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'ID tin nhắn từ Zalo trả về'],
            'error_code' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'comment' => 'Mã lỗi từ Zalo API'],
            'error_message' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Nội dung lỗi chi tiết'],
            'sent_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => 'ID nhân sự thực hiện gửi'],
            'sent_at' => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Thời điểm gửi thực tế'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('campaign_id');
        $this->forge->addKey('customer_id');
        $this->forge->addKey('status');
        $this->forge->createTable('zns_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('zns_logs', true);
    }
}
