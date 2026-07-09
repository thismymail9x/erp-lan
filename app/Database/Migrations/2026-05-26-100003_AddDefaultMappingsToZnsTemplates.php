<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

/**
 * Migration: Thêm cột default_mappings vào bảng zns_templates
 * Mô tả: Lưu trữ cấu hình ánh xạ mặc định do Admin thiết lập giữa tham số ZNS và trường dữ liệu ERP.
 */
class AddDefaultMappingsToZnsTemplates extends Migration
{
    public function up()
    {
        $this->forge->addColumn('zns_templates', [
            'default_mappings' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Cấu hình ánh xạ mặc định do Admin thiết lập giữa tham số ZNS và trường dữ liệu ERP',
                'after' => 'template_params'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('zns_templates', 'default_mappings');
    }
}
