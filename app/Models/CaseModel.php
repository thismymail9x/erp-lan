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
        'customer_id', 'title', 'type', 'code', 'description', 
        'status', 'deadline', 'current_step', 'priority', 
        'assigned_lawyer_id', 'assigned_staff_id', 'start_date', 'end_date',
        'workflow_template_id'
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
        'customer_id'   => 'required|is_not_unique[customers.id]',
        'title'         => 'required|min_length[3]',
        'code'          => 'required|is_unique[cases.code,id,{id}]',
        'status'        => 'required',
    ];

    /**
     * Tự động tính toán Deadline tổng của Hồ sơ dựa trên loại vụ việc (Nay dùng số ngày từ Template)
     */
    public function calculateDeadline(string $type, string $createdAt = null): ?string
    {
        $start = $createdAt ? strtotime($createdAt) : time();
        
        switch ($type) {
            case 'to_tung_dan_su':
                return date('Y-m-d H:i:s', strtotime('+15 days', $start));
            case 'tu_van':
                return date('Y-m-d H:i:s', strtotime('+24 hours', $start));
            case 'ly_hon_thuan_tinh':
                return date('Y-m-d H:i:s', strtotime('+30 days', $start));
            case 'xoa_an_tich':
                return date('Y-m-d H:i:s', strtotime('+60 days', $start));
            default:
                return null;
        }
    }
}
