<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOngoingSLA extends Migration
{
    public function up()
    {
        // 1. Thêm cột cho zalo_followers
        $this->forge->addColumn('zalo_followers', [
            'ongoing_response_deadline' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Hạn chót để phản hồi tin nhắn mới nhất của khách trong quá trình trao đổi',
                'after'   => 'first_responded_at',
            ],
            'last_customer_msg_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Thời điểm khách hàng gửi tin nhắn cuối cùng',
                'after'   => 'ongoing_response_deadline',
            ],
            'ongoing_is_overdue' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Cờ đánh dấu vi phạm SLA trao đổi kế tiếp',
                'after'      => 'last_customer_msg_at',
            ]
        ]);

        // 2. Thêm cột cho messenger_contacts
        $this->forge->addColumn('messenger_contacts', [
            'ongoing_response_deadline' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Hạn chót để phản hồi tin nhắn mới nhất của khách trong quá trình trao đổi',
                'after'   => 'first_responded_at',
            ],
            'last_customer_msg_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Thời điểm khách hàng gửi tin nhắn cuối cùng',
                'after'   => 'ongoing_response_deadline',
            ],
            'ongoing_is_overdue' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => 'Cờ đánh dấu vi phạm SLA trao đổi kế tiếp',
                'after'      => 'last_customer_msg_at',
            ]
        ]);
        
        // 3. Thêm config cho system_settings (Thời hạn SLA kế tiếp - giờ)
        $db = \Config\Database::connect();
        $builder = $db->table('system_settings');
        // Tránh lỗi duplicate nếu đã có
        $exists = $builder->where('key', 'ongoing_sla_hours')->countAllResults();
        if ($exists == 0) {
            $builder->insert([
                'key' => 'ongoing_sla_hours',
                'value' => '2',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('zalo_followers', ['ongoing_response_deadline', 'last_customer_msg_at', 'ongoing_is_overdue']);
        $this->forge->dropColumn('messenger_contacts', ['ongoing_response_deadline', 'last_customer_msg_at', 'ongoing_is_overdue']);
        
        $db = \Config\Database::connect();
        $db->table('system_settings')->where('key', 'ongoing_sla_hours')->delete();
    }
}
