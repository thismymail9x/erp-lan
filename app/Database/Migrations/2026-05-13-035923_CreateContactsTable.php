<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContactsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'source' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'Nguồn / Tab',
            ],
            'unit_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'comment'    => 'Tên đơn vị / Người phụ trách',
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'comment'    => 'Số điện thoại',
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Địa chỉ / Cơ quan',
            ],
            'position' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'Chức vụ / Chức danh',
            ],
            'area' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Địa bàn / Phạm vi quản lý',
            ],
            'reorganized_unit' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'Đơn vị tổ chức lại / Sau sắp xếp',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Lưu ý / Ghi chú',
            ],
            'province' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'Tỉnh / Khu vực',
            ],
            'is_private' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Cờ Private (1: Chỉ Admin xem SĐT và sửa, 0: Mọi người)',
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'ID người tạo',
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
                'comment' => 'Xóa mềm',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('unit_name');
        $this->forge->addKey('phone');
        $this->forge->addKey('province');
        $this->forge->createTable('contacts');
    }

    public function down()
    {
        $this->forge->dropTable('contacts');
    }
}
