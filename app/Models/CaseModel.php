<?php

namespace App\Models;

/**
 * CaseModel
 * 
 * Quản lý các vụ việc pháp lý.
 */
/**
 * CaseModel
 * 
 * Quản lý các vụ việc pháp lý từ khi khởi tạo đến khi kết thúc.
 * Đóng vai trò là trung tâm lưu trữ thông tin về trạng thái, luật sư phụ trách và thời hạn.
 */
class CaseModel extends BaseModel
{
    // 1. Cấu hình bảng dữ liệu
    protected $table            = 'cases';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    // 2. Các trường được phép chỉnh sửa
    protected $allowedFields    = [
        'customer_id', 'title', 'code', 'description', 
        'status', 'deadline', 'current_step', 'priority', 
        'assigned_lawyer_id', 'assigned_staff_id', 'consultant_id', 'consultation_closed_at', 'start_date', 'end_date',
        'workflow_template_id', 'contract_value', 'payment_progress'
    ];

    // 3. Quản lý thời gian tự động
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // 4. Ràng buộc dữ liệu (Validation)
    // Đảm bảo vụ việc luôn phải có khách hàng, tiêu đề và mã số duy nhất.
    protected $validationRules      = [
        'id'                   => 'permit_empty|numeric',
        'customer_id'          => 'required|is_not_unique[customers.id]',
        'workflow_template_id' => 'permit_empty|is_not_unique[workflow_templates.id]',
        'title'                => 'required|min_length[3]|max_length[255]',
        'code'                 => 'required|is_unique[cases.code,id,{id}]',
        'status'               => 'required',
    ];

    protected $validationMessages   = [
        'code' => [
            'is_unique' => 'Mã hồ sơ định danh này đã tồn tại, hệ thống từ chối tạo bản ghi trùng lặp.'
        ],
        'customer_id' => [
            'required' => 'Bắt buộc phải gán Hồ sơ vào một Khách hàng cụ thể.',
            'is_not_unique' => 'Khách hàng không hợp lệ hoặc đã bị gỡ khỏi hệ thống.'
        ],
        'title' => [
            'required' => 'Tiêu đề hồ sơ không được để trống.',
            'min_length' => 'Tiêu đề quá ngắn, cần nhập mô tả rõ ràng hơn.'
        ]
    ];

}
