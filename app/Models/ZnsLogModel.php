<?php

namespace App\Models;

/**
 * ZnsLogModel
 * 
 * Ghi log chi tiết từng tin nhắn ZNS đã gửi.
 * Phục vụ theo dõi trạng thái, debug lỗi và thống kê chiến dịch.
 */
class ZnsLogModel extends BaseModel
{
    protected $table            = 'zns_logs';
    protected $primaryKey       = 'id';
    protected $useTimestamps    = false; // Chỉ có created_at, không có updated_at
    protected $useSoftDeletes   = false; // Log không xóa mềm
    protected $allowedFields    = [
        'campaign_id', 'customer_id', 'template_id', 'phone',
        'template_data', 'status', 'zalo_msg_id', 'error_code',
        'error_message', 'sent_by', 'sent_at', 'created_at'
    ];
    protected $returnType       = 'array';

    /**
     * Lấy log của một chiến dịch cụ thể, kèm tên KH
     */
    public function getLogsByCampaign($campaignId, $perPage = 20)
    {
        return $this->select('zns_logs.*, customers.name as customer_name, customers.code as customer_code')
                    ->join('customers', 'customers.id = zns_logs.customer_id', 'left')
                    ->where('zns_logs.campaign_id', $campaignId)
                    ->orderBy('zns_logs.created_at', 'DESC')
                    ->paginate($perPage);
    }

    /**
     * Lấy log gửi ZNS đơn lẻ / nhanh (campaign_id IS NULL) kèm thông tin chi tiết
     */
    public function getIndividualLogs($perPage = 15)
    {
        return $this->select('zns_logs.*, customers.name as customer_name, customers.code as customer_code, zns_templates.template_name, employees.full_name as sender_name')
                    ->join('customers', 'customers.id = zns_logs.customer_id', 'left')
                    ->join('zns_templates', 'zns_templates.template_id = zns_logs.template_id', 'left')
                    ->join('employees', 'employees.user_id = zns_logs.sent_by', 'left')
                    ->where('zns_logs.campaign_id', null)
                    ->orderBy('zns_logs.created_at', 'DESC')
                    ->paginate($perPage, 'individual_logs');
    }

    /**
     * Lấy thống kê tổng quan gửi ZNS đơn lẻ/nhanh (campaign_id IS NULL)
     */
    public function getIndividualStats()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('zns_logs');
        $builder->select('COUNT(*) as total_sent');
        $builder->select('SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as total_success');
        $builder->select('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as total_fail');
        $builder->where('campaign_id', null);
        
        $stats = $builder->get()->getRowArray();
        return [
            'total_sent' => (int)($stats['total_sent'] ?? 0),
            'total_success' => (int)($stats['total_success'] ?? 0),
            'total_fail' => (int)($stats['total_fail'] ?? 0),
        ];
    }
}
