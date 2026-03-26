<?php

namespace App\Models;

/**
 * DocumentAccessLogModel
 * 
 * Lưu trữ nhật ký truy cập tài liệu nhạy cảm phục vụ việc kiểm toán (Audit).
 */
class DocumentAccessLogModel extends BaseModel
{
    protected $table            = 'document_access_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields    = [
        'document_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'created_at'
    ];

    protected $useTimestamps = false; // Created_at is enough
}
