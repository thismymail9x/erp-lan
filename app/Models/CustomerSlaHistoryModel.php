<?php

namespace App\Models;

/**
 * CustomerSlaHistoryModel
 * 
 * Đại diện cho bảng 'customer_sla_history' trong cơ sở dữ liệu.
 * Quản lý lịch sử tiến độ, đo lường thời gian xử lý và kiểm soát hạn chót SLA của Khách hàng.
 * 
 * Tuân thủ Rule #1 (Việt hóa 100%), Rule #5 (Comments đầy đủ), Rule #6 (Soft Delete).
 */
class CustomerSlaHistoryModel extends BaseModel
{
    protected $table            = 'customer_sla_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields    = [
        'customer_id', 'assigned_staff_id', 'status', 
        'start_time', 'end_time', 'sla_duration', 'due_time', 'sla_status'
    ];

    /**
     * Lấy bản ghi tiến trình SLA đang hoạt động của một khách hàng.
     * Đảm bảo loại trừ các bản ghi đã xóa mềm (Rule #6).
     * 
     * @param int $customerId ID khách hàng
     * @return array|null Bản ghi SLA hiện tại hoặc null
     */
    public function getActiveSla(int $customerId)
    {
        return $this->where('customer_id', $customerId)
                    ->where('end_time', null)
                    ->where('deleted_at', null)
                    ->first();
    }
}
