<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentFilesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
                'comment'        => 'Khoa chinh cua tep vat ly trong tai lieu DMS',
            ],
            'document_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Tai lieu cha trong bang documents',
            ],
            'original_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Ten tep goc nguoi dung tai len',
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Duong dan tep trong WRITEPATH uploads/dms',
            ],
            'file_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'comment'    => 'Phan mo rong tep',
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'comment'    => 'MIME type cua tep',
            ],
            'size' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'default'    => 0,
                'comment'    => 'Dung luong tep tinh bang byte',
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Thu tu hien thi tep trong mot tai lieu',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Thoi diem tao ban ghi',
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Thoi diem cap nhat gan nhat',
            ],
            'deleted_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Thoi diem xoa mem tep',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('document_id');
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('document_files', true);
    }

    public function down()
    {
        $this->forge->dropTable('document_files', true);
    }
}
