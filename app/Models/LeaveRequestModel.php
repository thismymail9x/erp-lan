<?php

namespace App\Models;

use CodeIgniter\Model;

class LeaveRequestModel extends BaseModel
{
    protected $table      = 'leave_requests';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'employee_id', 'leave_type', 'leave_duration', 'start_date', 'end_date', 'total_days', 
        'reason', 'handover_to', 'handover_content', 'is_emergency',
        'status', 'approver_id', 'approval_note', 'approved_at'
    ];

    // Note: BaseModel handles timestamps & soft deletes config.
    
    // Validation rules
    protected $validationRules = [
        'employee_id' => 'required|numeric',
        'start_date'  => 'required|valid_date',
        'end_date'    => 'required|valid_date',
        'leave_type'  => 'required|in_list[annual,sick,personal,unpaid,maternity,wedding,funeral]',
        'reason'      => 'required|min_length[5]'
    ];

    /**
     * Lấy danh sách phiếu nghỉ phép kèm thông tin nhân viên.
     *
     * @param array $filters
     * @return array
     */
    public function getLeaveRequests(array $filters = [])
    {
        $builder = $this->select('leave_requests.*, e.full_name as employee_name, e.position, d.name as department_name, ap.full_name as approver_name');
        $builder->join('employees e', 'e.id = leave_requests.employee_id', 'inner');
        $builder->join('departments d', 'd.id = e.department_id', 'left');
        $builder->join('employees ap', 'ap.id = leave_requests.approver_id', 'left');

        if (!empty($filters['employee_id'])) {
            $builder->where('leave_requests.employee_id', $filters['employee_id']);
        }

        if (!empty($filters['status'])) {
            $builder->where('leave_requests.status', $filters['status']);
        }

        if (!empty($filters['department_id'])) {
            $builder->where('e.department_id', $filters['department_id']);
        }

        return $builder->orderBy('leave_requests.created_at', 'DESC');
    }
}
