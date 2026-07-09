<?php

namespace App\Models;

/**
 * CustomerLoyaltyModel
 * 
 * Quản lý chương trình khách hàng thân thiết, điểm số, hạng thẻ và mã giới thiệu VIP.
 * Tuân thủ Rule #1 (Việt hóa 100%), Rule #5 (Comments đầy đủ), Rule #6 (Soft Delete).
 */
class CustomerLoyaltyModel extends BaseModel
{
    protected $table            = 'customer_loyalty';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields    = [
        'customer_id',
        'loyalty_tier',
        'benefits',
        'points',
        'referral_code',
        'total_referrals',
        'notes',
        'activated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Lấy thông tin loyalty của khách hàng.
     * 
     * @param int $customerId
     * @return array|null
     */
    public function getByCustomer(int $customerId)
    {
        return $this->where('customer_id', $customerId)
                    ->where('deleted_at', null)
                    ->first();
    }

    /**
     * Tự động sinh mã giới thiệu duy nhất cho khách hàng.
     * Cấu trúc: REF + ID Khách + chuỗi ngẫu nhiên (VD: REF100A3)
     * 
     * @param int $customerId
     * @return string
     */
    public function generateReferralCode(int $customerId)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {
            $rand = '';
            for ($i = 0; $i < 4; $i++) {
                $rand .= $chars[rand(0, strlen($chars) - 1)];
            }
            $code = 'REF' . $customerId . $rand;
            $exists = $this->where('referral_code', $code)->countAllResults();
        } while ($exists > 0);

        return $code;
    }
}
