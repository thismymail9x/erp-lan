<?php

namespace App\Models;

/**
 * CustomerInteractionModel
 *
 * Lưu trữ nhật ký các lần tương tác giữa nhân viên và khách hàng.
 */
class CustomerInteractionModel extends BaseModel
{
    protected $table            = 'customer_interactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'customer_id',
        'user_id',
        'channel',
        'interaction_date',
        'summary',
        'interaction_result',
        'importance_level',
        'requires_follow_up',
        'detailed_content',
        'next_follow_up',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Lấy danh sách tương tác của một khách hàng, kèm email nhân viên ghi nhận.
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
