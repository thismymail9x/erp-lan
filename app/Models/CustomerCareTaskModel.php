<?php

namespace App\Models;

/**
 * CustomerCareTaskModel
 * 
 * Quản lý checklist công việc chăm sóc chi tiết cho từng kế hoạch.
 * Tuân thủ Rule #1 (Việt hóa 100%), Rule #5 (Comments đầy đủ), Rule #6 (Soft Delete).
 */
class CustomerCareTaskModel extends BaseModel
{
    protected $table            = 'customer_care_tasks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields    = [
        'care_plan_id',
        'customer_id',
        'task_type',
        'title',
        'description',
        'channel',
        'is_completed',
        'completed_by',
        'completed_at',
        'due_date',
        'sort_order'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Lấy các công việc thuộc một kế hoạch CSKH cụ thể.
     * 
     * @param int $planId ID của kế hoạch
     * @return array
     */
    public function getByPlan(int $planId)
    {
        return $this->select('customer_care_tasks.*, employees.full_name as completed_by_name')
                    ->join('employees', 'employees.id = customer_care_tasks.completed_by AND employees.deleted_at IS NULL', 'left')
                    ->where('customer_care_tasks.care_plan_id', $planId)
                    ->where('customer_care_tasks.deleted_at IS NULL')
                    ->orderBy('customer_care_tasks.sort_order', 'ASC')
                    ->orderBy('customer_care_tasks.due_date', 'ASC')
                    ->findAll();
    }

    /**
     * Lấy tất cả công việc CSKH liên quan đến một khách hàng.
     * 
     * @param int $customerId
     * @return array
     */
    public function getByCustomer(int $customerId)
    {
        return $this->select('customer_care_tasks.*, customer_care_plans.phase, customer_care_plans.title as plan_title, employees.full_name as completed_by_name')
                    ->join('customer_care_plans', 'customer_care_plans.id = customer_care_tasks.care_plan_id AND customer_care_plans.deleted_at IS NULL')
                    ->join('employees', 'employees.id = customer_care_tasks.completed_by AND employees.deleted_at IS NULL', 'left')
                    ->where('customer_care_tasks.customer_id', $customerId)
                    ->where('customer_care_tasks.deleted_at IS NULL')
                    ->orderBy('customer_care_tasks.is_completed', 'ASC')
                    ->orderBy('customer_care_tasks.due_date', 'ASC')
                    ->findAll();
    }

    /**
     * Lấy các công việc chưa hoàn thành của nhân sự.
     * 
     * @param int $staffId ID nhân sự phụ trách (của kế hoạch hoặc người thực hiện)
     * @return array
     */
    public function getPendingTasks(int $staffId)
    {
        return $this->select('customer_care_tasks.*, customers.name as customer_name, customer_care_plans.phase')
                    ->join('customer_care_plans', 'customer_care_plans.id = customer_care_tasks.care_plan_id AND customer_care_plans.deleted_at IS NULL')
                    ->join('customers', 'customers.id = customer_care_tasks.customer_id AND customers.deleted_at IS NULL')
                    ->where('customer_care_plans.assigned_staff_id', $staffId)
                    ->where('customer_care_tasks.is_completed', 0)
                    ->where('customer_care_tasks.deleted_at IS NULL')
                    ->orderBy('customer_care_tasks.due_date', 'ASC')
                    ->findAll();
    }

    /**
     * Lấy checklist công việc hàng ngày của nhân viên.
     * 
     * @param int $staffId
     * @return array
     */
    public function getDailyChecklist(int $staffId)
    {
        $today = date('Y-m-d');
        return $this->select('customer_care_tasks.*, customers.name as customer_name, customers.phone as customer_phone, customer_care_plans.phase')
                    ->join('customer_care_plans', 'customer_care_plans.id = customer_care_tasks.care_plan_id AND customer_care_plans.deleted_at IS NULL')
                    ->join('customers', 'customers.id = customer_care_tasks.customer_id AND customers.deleted_at IS NULL')
                    ->where('customer_care_plans.assigned_staff_id', $staffId)
                    ->where('customer_care_tasks.due_date <=', $today)
                    ->where('customer_care_tasks.is_completed', 0)
                    ->where('customer_care_tasks.deleted_at IS NULL')
                    ->orderBy('customer_care_tasks.due_date', 'ASC')
                    ->findAll();
    }
}
