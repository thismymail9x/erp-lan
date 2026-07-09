<?php

namespace App\Models;

/**
 * CustomerSlaSettingModel
 * 
 * Đại diện cho bảng 'customer_sla_settings' trong cơ sở dữ liệu.
 * Quản lý cấu hình danh mục trạng thái và số giờ SLA cho từng bước chăm sóc.
 * 
 * Tuân thủ Rule #1 (Việt hóa 100%), Rule #5 (Comments đầy đủ), Rule #6 (Soft Delete).
 */
class CustomerSlaSettingModel extends BaseModel
{
    protected $table            = 'customer_sla_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields    = [
        'status_key', 'status_name', 'sla_hours', 'color', 'sort_order', 'is_active'
    ];

    // Cấu hình quy tắc kiểm tra dữ liệu đầu vào (Validation)
    protected $validationRules  = [
        'id'          => 'permit_empty|numeric',
        'status_key'  => 'required|alpha_dash|max_length[50]|is_unique[customer_sla_settings.status_key,id,{id}]',
        'status_name' => 'required|min_length[3]|max_length[100]',
        'sla_hours'   => 'required|numeric|greater_than_equal_to[0]',
        'color'       => 'required|max_length[20]',
        'sort_order'  => 'permit_empty|numeric'
    ];

    // Thông báo lỗi tùy chỉnh bằng tiếng Việt
    protected $validationMessages = [
        'status_key' => [
            'required' => 'Mã định danh trạng thái bắt buộc phải nhập.',
            'alpha_dash' => 'Mã định danh chỉ gồm chữ cái, chữ số, gạch ngang và gạch dưới.',
            'is_unique' => 'Mã định danh trạng thái này đã tồn tại trên hệ thống.'
        ],
        'status_name' => [
            'required' => 'Tên hiển thị trạng thái bắt buộc phải nhập.',
            'min_length' => 'Tên hiển thị quá ngắn.'
        ],
        'sla_hours' => [
            'required' => 'Số giờ SLA bắt buộc phải nhập.',
            'greater_than_equal_to' => 'Thời gian SLA phải lớn hơn hoặc bằng 0.'
        ]
    ];
}
