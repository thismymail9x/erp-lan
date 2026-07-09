<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

/**
 * Migration: Tạo bảng zns_templates
 * Mô tả: Lưu trữ danh sách mẫu tin ZNS (Zalo Notification Service) đã đăng ký.
 * Mỗi template tương ứng với một mẫu thông báo được Zalo phê duyệt.
 */
class CreateZnsTemplates extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'template_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'comment' => 'ID mẫu tin từ hệ thống Zalo Business'],
            'template_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'comment' => 'Tên mẫu tin hiển thị trong ERP'],
            'template_content' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Nội dung mẫu tin (preview)'],
            'template_params' => ['type' => 'JSON', 'null' => true, 'comment' => 'Danh sách các biến trong mẫu tin (JSON array)'],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active', 'comment' => 'Trạng thái: active=đang sử dụng, inactive=tạm ngưng'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => 'ID nhân sự tạo template'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Xóa mềm (Soft Delete)'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('template_id');
        $this->forge->createTable('zns_templates', true);
    }

    public function down()
    {
        $this->forge->dropTable('zns_templates', true);
    }
}
