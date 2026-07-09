<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateZaloTables extends Migration
{
    public function up()
    {
        // 1. Zalo Followers (Customers who interacted with OA)
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'zalo_id'        => ['type' => 'VARCHAR', 'constraint' => 255, 'unique' => true], // User ID from Zalo
            'display_name'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'avatar_url'     => ['type' => 'TEXT', 'null' => true],
            'phone_number'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'mid_code'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true], // L.A.N specific MID
            'customer_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true], // Link to CRM
            'tags'           => ['type' => 'TEXT', 'null' => true], // JSON tags
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('zalo_followers');

        // 2. Zalo Messages (Chat History)
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'zalo_msg_id'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'follower_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'sender_type'    => ['type' => 'ENUM', 'constraint' => ['user', 'oa'], 'default' => 'user'],
            'message_text'   => ['type' => 'TEXT'],
            'attachments'    => ['type' => 'TEXT', 'null' => true], // JSON
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('follower_id', 'zalo_followers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('zalo_messages');
    }

    public function down()
    {
        $this->forge->dropTable('zalo_messages', true);
        $this->forge->dropTable('zalo_followers', true);
    }
}
