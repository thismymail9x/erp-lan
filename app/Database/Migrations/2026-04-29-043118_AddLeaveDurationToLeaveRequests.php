<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLeaveDurationToLeaveRequests extends Migration
{
    public function up()
    {
        $this->forge->addColumn('leave_requests', [
            'leave_duration' => [
                'type'       => 'ENUM',
                'constraint' => ['full_day', 'morning_half', 'afternoon_half'],
                'default'    => 'full_day',
                'after'      => 'end_date',
                'comment'    => 'Thời lượng nghỉ: Cả ngày (full_day), Sáng (morning_half), Chiều (afternoon_half)'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('leave_requests', 'leave_duration');
    }
}
