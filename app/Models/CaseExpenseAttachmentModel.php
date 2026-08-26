<?php

namespace App\Models;

/**
 * CaseExpenseAttachmentModel
 *
 * Quản lý chứng từ đính kèm cho phiếu chi phí xử lý vụ việc. Chứng từ được tách
 * riêng để một phiếu có thể có nhiều ảnh hoặc file hóa đơn.
 */
class CaseExpenseAttachmentModel extends BaseModel
{
    protected $table            = 'case_expense_attachments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'expense_id',
        'file_name',
        'file_path',
        'file_type',
        'uploaded_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
