<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Bổ sung theo dõi nhắc nhở quá hạn hàng ngày cho quy trình.
 * Quy tắc 4 & 5: Ghi nhận cột last_overdue_notified_at kèm comment chi tiết.
 */
class AddOverdueTrackingToSteps extends Migration
{
    public function up()
    {
        $this->forge->addColumn('case_steps', [
            'last_overdue_notified_at' => [
                'type'       => 'DATE',
                'null'       => true,
                'default'    => null,
                'after'      => 'overdue_notified',
                'comment'    => 'Ngày cuối cùng hệ thống gửi thông báo nhắc nhở quá hạn cho bước này'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('case_steps', 'last_overdue_notified_at');
    }
}
