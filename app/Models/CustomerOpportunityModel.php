<?php

namespace App\Models;

/**
 * CustomerOpportunityModel
 *
 * Luu cac co hoi phat trien dich vu phat sinh tu qua trinh cham soc quan he
 * khach hang.
 */
class CustomerOpportunityModel extends BaseModel
{
    protected $table            = 'customer_opportunities';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'customer_id',
        'issue_title',
        'issue_description',
        'service_suggestion',
        'estimated_value',
        'probability',
        'assigned_staff_id',
        'discovered_at',
        'follow_up_date',
        'stage',
        'status',
        'source_type',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getByCustomer(int $customerId): array
    {
        return $this->select('customer_opportunities.*, employees.full_name AS assigned_staff_name')
            ->join('employees', 'employees.id = customer_opportunities.assigned_staff_id', 'left')
            ->where('customer_opportunities.customer_id', $customerId)
            ->where('customer_opportunities.deleted_at', null)
            ->orderBy('customer_opportunities.follow_up_date', 'ASC')
            ->orderBy('customer_opportunities.created_at', 'DESC')
            ->findAll();
    }
}
