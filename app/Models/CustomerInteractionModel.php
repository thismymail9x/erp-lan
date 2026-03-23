<?php

namespace App\Models;

/**
 * CustomerInteractionModel
 * 
 * Lưu trữ nhật ký các lần tương tác giữa nhân viên và khách hàng.
 * Giúp theo dõi lịch sử chăm sóc và phản hồi từ phía khách hàng.
 */
class CustomerInteractionModel extends BaseModel
{
    // 1. Cấu hình bảng
    protected $table            = 'customer_interactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    // 2. Các trường thông tin tương tác
    protected $allowedFields    = [
        'customer_id',      // ID khách hàng liên quan
        'user_id',          // ID nhân viên thực hiện tương tác
        'channel',          // Kênh: call, email, zalo, meeting...
        'interaction_date', // Thời điểm diễn ra
        'summary',          // Tóm tắt nội dung chính
        'detailed_content', // Nội dung chi tiết cuộc trao đổi
        'next_follow_up'    // Ngày hẹn liên hệ lại lần sau
    ];

    // 3. Tự động quản lý thời gian ghi log
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Lấy danh sách tương tác của một khách hàng cụ thể
     * Kèm theo email của nhân viên đã thực hiện tương tác đó.
     * 
     * @param int $customerId ID của khách hàng
     * @return array Danh sách lịch sử tương tác giảm dần theo thời gian
     */
    public function getByCustomer(int $customerId)
    {
        return $this->select('customer_interactions.*, users.email as staff_email')
                    ->join('users', 'users.id = customer_interactions.user_id')
                    ->where('customer_id', $customerId)
                    ->orderBy('interaction_date', 'DESC')
                    ->findAll();
    }
}
