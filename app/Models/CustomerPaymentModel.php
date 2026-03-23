<?php

namespace App\Models;

/**
 * CustomerPaymentModel
 * 
 * Quản lý lịch sử dòng tiền và các giao dịch thanh toán từ khách hàng.
 * Đóng vai trò quan trọng trong việc tính toán doanh thu và giá trị khách hàng.
 */
class CustomerPaymentModel extends BaseModel
{
    // 1. Cấu hình bảng
    protected $table            = 'customer_payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    // 2. Các trường thông tin thanh toán
    protected $allowedFields    = [
        'customer_id',  // Khách hàng thanh toán
        'case_id',      // Vụ việc liên quan (nếu có)
        'amount',       // Số tiền (VND)
        'payment_date', // Ngày thực hiện giao dịch
        'method',       // Hình thức: Chuyển khoản, Tiền mặt...
        'description',  // Nội dung thanh toán
        'user_id'       // Nhân viên thu ngân/kế toán tiếp nhận
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Tính tổng doanh thu tích lũy từ một khách hàng cụ thể
     * 
     * @param int $customerId ID khách hàng cần tính
     * @return float|int Tổng số tiền đã thanh toán
     */
    public function getTotalRevenue(int $customerId)
    {
        // Sử dụng hàm sum của SQL để cộng dồn tất cả các khoản thanh toán của một khách hàng
        $result = $this->selectSum('amount')
                       ->where('customer_id', $customerId)
                       ->first();
                       
        return $result['amount'] ?? 0;
    }
}
