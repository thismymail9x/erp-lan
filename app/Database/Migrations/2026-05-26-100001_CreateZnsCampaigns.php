<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

/**
 * Migration: Tạo bảng zns_campaigns
 * Mô tả: Lưu trữ thông tin các chiến dịch gửi thông báo ZNS hàng loạt.
 * Mỗi chiến dịch gắn với 1 template và một nhóm khách hàng mục tiêu.
 */
class CreateZnsCampaigns extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255, 'comment' => 'Tên chiến dịch'],
            'description' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Mô tả chi tiết chiến dịch'],
            'zns_template_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => 'FK tới zns_templates.id'],
            'template_data_mapping' => ['type' => 'JSON', 'null' => true, 'comment' => 'Mapping giữa biến template và trường dữ liệu KH (JSON)'],
            'filter_criteria' => ['type' => 'JSON', 'null' => true, 'comment' => 'Bộ lọc KH mục tiêu (tag, status, segment...)'],
            'customer_ids' => ['type' => 'JSON', 'null' => true, 'comment' => 'Danh sách ID KH được chọn thủ công'],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'sending', 'completed', 'failed', 'cancelled'], 'default' => 'draft', 'comment' => 'Trạng thái: draft=nháp, sending=đang gửi, completed=hoàn thành, failed=thất bại, cancelled=đã hủy'],
            'total_recipients' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => 'Tổng số người nhận'],
            'sent_count' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => 'Số tin đã gửi'],
            'success_count' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => 'Số tin gửi thành công'],
            'fail_count' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => 'Số tin gửi thất bại'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => 'ID nhân sự tạo chiến dịch'],
            'started_at' => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Thời điểm bắt đầu gửi'],
            'completed_at' => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Thời điểm hoàn thành'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Xóa mềm (Soft Delete)'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('zns_template_id');
        $this->forge->addKey('status');
        $this->forge->createTable('zns_campaigns', true);
    }

    public function down()
    {
        $this->forge->dropTable('zns_campaigns', true);
    }
}
