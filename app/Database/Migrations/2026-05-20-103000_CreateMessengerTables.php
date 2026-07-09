<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMessengerTables extends Migration
{
    public function up()
    {
        // 1. Messenger Contacts (zalo_followers equivalent)
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'psid'           => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'display_name'   => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'Khách Facebook'],
            'avatar_url'     => ['type' => 'TEXT', 'null' => true],
            'phone_number'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'mid_code'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'customer_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'assigned_to'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'tags'           => ['type' => 'TEXT', 'null' => true],
            'locale'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'vi_VN'],
            'timezone'       => ['type' => 'TINYINT', 'constraint' => 4, 'default' => 7],
            'page_id'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('assigned_to');
        $this->forge->createTable('messenger_contacts', true);

        // 2. Messenger Messages (zalo_messages equivalent)
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'contact_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'fb_msg_id'      => ['type' => 'VARCHAR', 'constraint' => 255, 'unique' => true, 'null' => true],
            'sender_type'    => ['type' => 'ENUM', 'constraint' => ['user', 'page'], 'default' => 'user'],
            'message_text'   => ['type' => 'TEXT', 'null' => true],
            'attachments'    => ['type' => 'TEXT', 'null' => true],
            'is_read'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'mid_staff_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('contact_id', 'messenger_contacts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('messenger_messages', true);

        // 3. Seed configurations to system_settings if they don't exist
        $db = \Config\Database::connect();
        $settings = [
            'messenger_page_access_token' => '',
            'messenger_app_id'            => '',
            'messenger_app_secret'        => '',
            'messenger_verify_token'      => 'lan_erp_messenger_verify_2026',
        ];

        foreach ($settings as $key => $val) {
            $exists = $db->table('system_settings')->where('key', $key)->countAllResults();
            if ($exists == 0) {
                $db->table('system_settings')->insert([
                    'key'   => $key,
                    'value' => $val,
                ]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('messenger_messages', true);
        $this->forge->dropTable('messenger_contacts', true);
    }
}
