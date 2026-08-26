<?php

namespace App\Models;

/**
 * Model lưu chi phí vận hành nội bộ.
 *
 * Dùng BaseModel và soft delete để thống kê loại trừ bản ghi đã xóa mềm theo
 * cùng chuẩn dữ liệu sạch của các module ERP khác.
 */
class OfficeExpenseModel extends BaseModel
{
    protected $table            = 'office_expenses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'expense_date',
        'category',
        'vendor',
        'amount',
        'payment_method',
        'note',
        'receipt_file_name',
        'receipt_file_path',
        'receipt_file_type',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
