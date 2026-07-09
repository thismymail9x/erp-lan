<?php

namespace App\Models;

/**
 * WorkScheduleModel
 * 
 * Quản lý dữ liệu lịch làm việc và công tác của nhân sự.
 * Kế thừa BaseModel để tự động xử lý timestamps, soft delete và chuẩn hóa dữ liệu.
 */
class WorkScheduleModel extends BaseModel
{
    protected $table            = 'work_schedules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'employee_id', 
        'assigned_by_id',
        'created_by', 
        'type', 
        'title', 
        'location', 
        'start_at', 
        'end_at', 
        'requires_vehicle',
        'status'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Lấy lịch trình kèm theo thông tin nhân viên
     * 
     * @param array $filters Bộ lọc (employee_id, type, start_date, end_date)
     * @return array
     */
    public function getSchedules($filters = [])
    {
        $builder = $this->select('work_schedules.*, e.full_name as employee_name, creator.full_name as creator_name, assigner.full_name as assigner_name')
                        ->join('employees e', 'e.id = work_schedules.employee_id', 'left')
                        ->join('employees creator', 'creator.id = work_schedules.created_by', 'left')
                        ->join('employees assigner', 'assigner.id = work_schedules.assigned_by_id', 'left')
                        ->where('work_schedules.deleted_at', null);

        if (!empty($filters['employee_id'])) {
            $builder->where('work_schedules.employee_id', $filters['employee_id']);
        }

        if (!empty($filters['dept_id'])) {
            $builder->where('e.department_id', $filters['dept_id']);
        }

        if (!empty($filters['types'])) {
            $builder->whereIn('work_schedules.type', $filters['types']);
        }

        if (!empty($filters['start_date'])) {
            $builder->where('work_schedules.start_at >=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $builder->where('work_schedules.start_at <=', $filters['end_date']);
        }

        return $builder->orderBy('work_schedules.start_at', 'ASC')->findAll();
    }
}
