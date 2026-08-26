<?php

namespace App\Models;

/**
 * CaseExpenseModel
 *
 * Lưu từng khoản chi phí xử lý vụ việc theo nhân sự. Model dùng soft delete để
 * giữ lịch sử duyệt, phục vụ đối soát kế toán và thống kê chi phí sau này.
 */
class CaseExpenseModel extends BaseModel
{
    protected $table            = 'case_expenses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'case_id',
        'work_schedule_id',
        'employee_id',
        'created_by',
        'expense_date',
        'category',
        'amount',
        'actual_start_at',
        'actual_end_at',
        'actual_hours',
        'note',
        'status',
        'approved_amount',
        'approval_note',
        'approved_by',
        'approved_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
