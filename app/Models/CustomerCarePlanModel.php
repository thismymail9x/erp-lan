<?php

namespace App\Models;

/**
 * CustomerCarePlanModel
 * 
 * Quản lý kế hoạch chăm sóc khách hàng cũ theo từng giai đoạn (Phase 1, 2, 3).
 * Tuân thủ Rule #1 (Việt hóa 100%), Rule #5 (Comments đầy đủ), Rule #6 (Soft Delete).
 */
class CustomerCarePlanModel extends BaseModel
{
    protected $table            = 'customer_care_plans';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields    = [
        'customer_id',
        'phase',
        'title',
        'description',
        'assigned_staff_id',
        'status',
        'due_date',
        'completed_at',
        'result_notes'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Lấy danh sách kế hoạch CSKH của một khách hàng cụ thể.
     * Kèm theo tên của nhân viên phụ trách chăm sóc.
     * 
     * @param int $customerId ID của khách hàng
     * @return array Danh sách kế hoạch chăm sóc
     */
    public function getByCustomer(int $customerId)
    {
        return $this->select('customer_care_plans.*, employees.full_name as staff_name')
                    ->join('employees', 'employees.id = customer_care_plans.assigned_staff_id AND employees.deleted_at IS NULL', 'left')
                    ->where('customer_care_plans.customer_id', $customerId)
                    ->where('customer_care_plans.deleted_at IS NULL')
                    ->orderBy('customer_care_plans.created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Lấy danh sách kế hoạch đang hoạt động theo giai đoạn (Phase).
     * 
     * @param string $phase Giai đoạn (phase1, phase2, phase3)
     * @return array Danh sách kế hoạch
     */
    public function getActiveByPhase(string $phase)
    {
        return $this->select('customer_care_plans.*, customers.name as customer_name, employees.full_name as staff_name')
                    ->join('customers', 'customers.id = customer_care_plans.customer_id AND customers.deleted_at IS NULL')
                    ->join('employees', 'employees.id = customer_care_plans.assigned_staff_id AND employees.deleted_at IS NULL', 'left')
                    ->where('customer_care_plans.phase', $phase)
                    ->where('customer_care_plans.status', 'in_progress')
                    ->where('customer_care_plans.deleted_at IS NULL')
                    ->findAll();
    }

    /**
     * Lấy danh sách kế hoạch quá hạn.
     * 
     * @return array
     */
    public function getOverduePlans()
    {
        $today = date('Y-m-d');
        return $this->select('customer_care_plans.*, customers.name as customer_name, employees.full_name as staff_name')
                    ->join('customers', 'customers.id = customer_care_plans.customer_id AND customers.deleted_at IS NULL')
                    ->join('employees', 'employees.id = customer_care_plans.assigned_staff_id AND employees.deleted_at IS NULL', 'left')
                    ->where('customer_care_plans.due_date <', $today)
                    ->whereIn('customer_care_plans.status', ['pending', 'in_progress'])
                    ->where('customer_care_plans.deleted_at IS NULL')
                    ->findAll();
    }
}
