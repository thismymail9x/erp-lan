<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ImplementAdvancedDMS extends Migration
{
    public function up()
    {
        // 1. Nâng cấp bảng `documents` hiện có
        $fields = [
            'customer_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'id'
            ],
            'document_category' => [
                'type'       => 'ENUM',
                'values'     => ['client_intake', 'case_file', 'correspondence', 'financial', 'template', 'internal'],
                'default'    => 'case_file',
                'after'      => 'step_id'
            ],
            'file_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'after'      => 'file_name'
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'after'      => 'file_type'
            ],
            'size' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'default'    => 0,
                'after'      => 'mime_type'
            ],
            'version_number' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 1,
                'after'      => 'uploaded_by'
            ],
            'is_encrypted' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'version_number'
            ],
            'is_confidential' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'is_encrypted'
            ],
            'tags' => [
                'type' => 'JSON',
                'null' => true,
                'after' => 'is_confidential'
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'tags'
            ],
            'retention_period' => [
                'type'       => 'INT',
                'constraint' => 3,
                'default'    => 10,
                'after'      => 'description'
            ],
            'expiry_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'retention_period'
            ],
        ];
        $this->forge->addColumn('documents', $fields);

        // Thêm foreign key cho customer_id (nếu chưa có)
        // Lưu ý: MySql có thể yêu cầu query thô cho foreign key nếu forge không hỗ trợ modify tốt
        $this->db->query("ALTER TABLE `documents` ADD CONSTRAINT `fk_doc_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `documents` MODIFY COLUMN `case_id` INT(11) UNSIGNED NULL");

        // 2. Tạo bảng `document_versions`
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'document_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'version_number' => [
                'type'       => 'INT',
                'constraint' => 5,
            ],
            'file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'uploaded_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'uploaded_at' => [
                'type' => 'DATETIME',
            ],
            'change_log' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('document_versions');

        // 3. Tạo bảng `document_access_logs`
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'document_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'action' => [
                'type'       => 'ENUM',
                'values'     => ['view', 'download', 'edit', 'delete', 'upload'],
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('document_access_logs');
    }

    public function down()
    {
        $this->forge->dropTable('document_access_logs');
        $this->forge->dropTable('document_versions');
        // Đối với bảng documents, thường không rollback addColumn trừ khi thực sự cần
        // $this->forge->dropColumn('documents', ['customer_id', ...]);
    }
}
