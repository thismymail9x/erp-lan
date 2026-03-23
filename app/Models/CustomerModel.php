<?php

namespace App\Models;

/**
 * CustomerModel
 * 
 * Đại diện cho bảng 'customers' trong cơ sở dữ liệu.
 * Quản lý toàn bộ thông tin định danh, liên lạc và các chỉ số CRM của khách hàng.
 */
class CustomerModel extends BaseModel
{
    // 1. Cấu hình các thông số cơ bản của bảng
    protected $table            = 'customers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true; // Sử dụng xóa mềm (soft delete) để bảo vệ dữ liệu

    // 2. Định nghĩa các quy tắc kiểm tra dữ liệu (Validation)
    // Đảm bảo dữ liệu đầu vào luôn sạch và không bị trùng lặp các thông tin quan trọng.
    protected $validationRules      = [
        'name'            => 'required|min_length[3]|max_length[255]',
        'phone'           => 'required|min_length[9]|max_length[20]',
        'identity_number' => 'permit_empty|is_unique[customers.identity_number,id,{id}]', // Kiểm tra trùng CCCD
        'code'            => 'permit_empty|is_unique[customers.code,id,{id}]',            // Kiểm tra trùng mã KH
    ];

    // Thông báo lỗi tùy chỉnh bằng tiếng Việt
    protected $validationMessages   = [
        'identity_number' => [
            'is_unique' => 'Số định danh này đã tồn tại trong hệ thống.'
        ],
        'phone' => [
            'required' => 'Số điện thoại là bắt buộc.'
        ]
    ];
    protected $protectFields    = true;

    // 3. Danh sách các trường được phép tác động (Whitelist)
    protected $allowedFields    = [
        'code', 'type', 'name', 'date_of_birth', 'gender',
        'identity_type', 'identity_number', 'issue_date', 'expiry_date', 'issued_by',
        'phone', 'phone_secondary', 'email', 'email_secondary',
        'address', 'address_json', 
        'company_name', 'tax_code', 'biz_registration_number', 'rep_position',
        'tags', 'source', 'referred_by', 'is_blacklist', 'blacklist_reason',
        // Các trường cache phục vụ thống kê (Dashboard)
        'total_revenue', 'total_cases', 'success_rate', 'last_contact_date', 'notes_internal'
    ];


    // 4. Cấu hình tự động quản lý thời gian
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
