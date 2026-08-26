<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration tạo cấu hình trạng thái Giám sát CSKH.
 *
 * Bổ sung cột trạng thái giám sát trên bảng khách hàng và bảng danh mục tùy biến để quản lý các trạng thái con.
 */
class CreateCustomerMonitoringStatusSettings extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (!$db->fieldExists('monitoring_status', 'customers')) {
            $this->forge->addColumn('customers', [
                'monitoring_status' => [
                    'type'       => 'TEXT',
                    'null'       => true,
                    'comment'    => 'Trạng thái giám sát chất lượng tư vấn CSKH'
                ],
            ]);
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
                'comment'        => 'Khóa chính'
            ],
            'status_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'comment'    => 'Khóa định danh trạng thái giám sát'
            ],
            'status_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'comment'    => 'Tên hiển thị trạng thái giám sát'
            ],
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '#6c757d',
                'comment'    => 'Màu sắc đại diện trạng thái'
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Thứ tự hiển thị'
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => 'Trạng thái hoạt động'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Ngày tạo'
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Ngày cập nhật'
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Ngày xóa mềm'
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('status_key');
        $this->forge->createTable('customer_monitoring_status_settings', true);

        $now = date('Y-m-d H:i:s');
        $db->table('customer_monitoring_status_settings')->ignore(true)->insertBatch([
            ['status_key' => 'good', 'status_name' => 'Good', 'color' => '#34c759', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'miss_tin_03_phut_khi_tao_nhom', 'status_name' => 'Miss tin trong 03 phút khi tạo nhóm', 'color' => '#ff3b30', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'miss_tuong_tac_trong_qua_trinh_tv', 'status_name' => 'Miss tương tác trong quá trình TV', 'color' => '#ff9500', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'chua_gui_bao_phi', 'status_name' => 'Chưa gửi báo phí', 'color' => '#af52de', 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'chua_co_anh_cham_soc_cuoi_cung', 'status_name' => 'Chưa có ảnh chăm sóc cuối cùng', 'color' => '#5856d6', 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'tu_van_chua_than_thiet_nhiet_tinh', 'status_name' => 'Tư vấn chưa thân thiện, nhiệt tình', 'color' => '#ff2d55', 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['status_key' => 'khach_goi_phan_nan', 'status_name' => 'Khách gọi phàn nàn', 'color' => '#d70015', 'sort_order' => 7, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('customer_monitoring_status_settings', true);

        $db = \Config\Database::connect();
        if ($db->fieldExists('monitoring_status', 'customers')) {
            $this->forge->dropColumn('customers', 'monitoring_status');
        }
    }
}
