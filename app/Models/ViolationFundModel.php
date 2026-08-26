<?php

namespace App\Models;

/**
 * Model quản lý các khoản đóng quỹ vi phạm nội bộ.
 *
 * Bảng dùng soft delete để giữ lịch sử báo cáo minh bạch, đồng thời cho phép
 * hành chính lọc chính xác các khoản còn phải thu theo từng nhân sự và từng tháng.
 */
class ViolationFundModel extends BaseModel
{
    protected $table            = 'violation_funds';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'employee_id',
        'violation_date',
        'due_month',
        'category',
        'behavior',
        'rank_level',
        'base_amount',
        'amount',
        'recurrence_count',
        'status',
        'collection_method',
        'explanation',
        'hr_note',
        'admin_note',
        'notified_at',
        'collected_at',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
