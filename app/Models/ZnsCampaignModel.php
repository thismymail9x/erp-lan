<?php

namespace App\Models;

/**
 * ZnsCampaignModel
 * 
 * Quản lý chiến dịch gửi thông báo ZNS hàng loạt.
 * Mỗi chiến dịch liên kết với 1 template và nhóm KH mục tiêu.
 */
class ZnsCampaignModel extends BaseModel
{
    protected $table            = 'zns_campaigns';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'name', 'description', 'zns_template_id', 'template_data_mapping',
        'filter_criteria', 'customer_ids', 'status', 'total_recipients',
        'sent_count', 'success_count', 'fail_count', 'created_by',
        'started_at', 'completed_at'
    ];
    protected $returnType       = 'array';

    /**
     * Lấy danh sách chiến dịch kèm tên template (JOIN)
     */
    public function getCampaignsWithTemplate($perPage = 20)
    {
        return $this->select('zns_campaigns.*, zns_templates.template_name, zns_templates.template_id as zalo_template_id')
                    ->join('zns_templates', 'zns_templates.id = zns_campaigns.zns_template_id', 'left')
                    ->where('zns_campaigns.deleted_at IS NULL')
                    ->orderBy('zns_campaigns.created_at', 'DESC')
                    ->paginate($perPage);
    }

    /**
     * Lấy thống kê tổng quan tất cả chiến dịch
     */
    public function getOverallStats()
    {
        $result = $this->selectSum('total_recipients', 'total_sent')
                       ->selectSum('success_count', 'total_success')
                       ->selectSum('fail_count', 'total_fail')
                       ->selectCount('id', 'total_campaigns')
                       ->where('deleted_at IS NULL')
                       ->first();
        return $result;
    }
}
