<?php

namespace App\Models;

/**
 * Model lưu từng khoản hoa hồng phát sinh của đối tác theo đợt khách thanh toán.
 */
class PartnerCommissionEntryModel extends BaseModel
{
    protected $table = 'partner_commission_entries';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'case_partner_id',
        'partner_id',
        'case_id',
        'payment_index',
        'payment_title',
        'payment_date',
        'calculation_base',
        'base_amount',
        'percentage',
        'fixed_amount',
        'commission_amount',
        'status',
        'request_note',
        'admin_note',
        'requested_at',
        'approved_at',
        'paid_at',
    ];
}
