<?php

namespace App\Models;

/**
 * CustomerMonitoringStatusSettingModel
 *
 * Đại diện bảng cấu hình trạng thái "Giám sát" trong quy trình CSKH.
 * Mỗi trạng thái là một danh mục tùy biến để quản lý chất lượng tư vấn trên danh sách khách hàng.
 */
class CustomerMonitoringStatusSettingModel extends BaseModel
{
    protected $table            = 'customer_monitoring_status_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields    = [
        'status_key',
        'status_name',
        'color',
        'sort_order',
        'is_active'
    ];

    protected $validationRules  = [
        'id'          => 'permit_empty|numeric',
        'status_key'  => 'required|alpha_dash|max_length[80]|is_unique[customer_monitoring_status_settings.status_key,id,{id}]',
        'status_name' => 'required|min_length[3]|max_length[150]',
        'color'       => 'required|max_length[20]',
        'sort_order'  => 'permit_empty|numeric',
        'is_active'   => 'permit_empty|in_list[0,1]'
    ];

    protected $validationMessages = [
        'status_key' => [
            'required' => 'Mã định danh trạng thái giám sát bắt buộc phải nhập.',
            'alpha_dash' => 'Mã định danh chỉ gồm chữ cái, chữ số, gạch ngang và gạch dưới.',
            'is_unique' => 'Mã định danh trạng thái giám sát này đã tồn tại.'
        ],
        'status_name' => [
            'required' => 'Tên hiển thị trạng thái giám sát bắt buộc phải nhập.',
            'min_length' => 'Tên hiển thị quá ngắn.'
        ]
    ];
}
