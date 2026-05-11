<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration tạo bảng lịch làm việc và công tác (Work Schedules)
 * Cho phép nhân sự thông báo lịch trình cho nhau.
 */
class CreateWorkSchedulesTable extends Migration
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
            'employee_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'ID nhân sự sở hữu lịch trình',
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'ID nhân sự tạo bản ghi',
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['work', 'business_trip'],
                'default'    => 'work',
                'comment'    => 'Loại lịch trình: work (Công việc), business_trip (Công tác)',
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'comment'    => 'Tiêu đề ngắn gọn của lịch trình',
            ],
            'description' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Mô tả chi tiết nội dung công việc',
            ],
            'location' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'Địa điểm làm việc/công tác',
            ],
            'start_at' => [
                'type'    => 'DATETIME',
                'comment' => 'Thời gian bắt đầu',
            ],
            'end_at' => [
                'type'    => 'DATETIME',
                'comment' => 'Thời gian kết thúc',
            ],
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => '#007bff',
                'comment'    => 'Mã màu hiển thị trên lịch',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'active', 'completed', 'cancelled'],
                'default'    => 'active',
                'comment'    => 'Trạng thái lịch trình',
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
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('work_schedules');
    }

    public function down()
    {
        $this->forge->dropTable('work_schedules');
    }
}
