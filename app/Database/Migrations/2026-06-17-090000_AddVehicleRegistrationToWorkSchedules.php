<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Thêm cờ đăng ký xe cho lịch trình công việc.
 *
 * Cờ này được tách riêng khỏi loại lịch trình để một lịch vẫn giữ đúng bản chất
 * là "tại văn phòng" hoặc "đi công tác", đồng thời vẫn thể hiện nhu cầu dùng xe.
 */
class AddVehicleRegistrationToWorkSchedules extends Migration
{
    public function up()
    {
        $this->forge->addColumn('work_schedules', [
            'requires_vehicle' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'location',
                'comment'    => '1 nếu lịch trình có đăng ký sử dụng xe công ty',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('work_schedules', 'requires_vehicle');
    }
}
