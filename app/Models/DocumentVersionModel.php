<?php

namespace App\Models;

/**
 * DocumentVersionModel
 * 
 * Quản lý lịch sử các phiên bản của tài liệu trong DMS.
 */
class DocumentVersionModel extends BaseModel
{
    protected $table            = 'document_versions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields    = [
        'document_id',
        'version_number',
        'file_name',
        'file_path',
        'uploaded_by',
        'uploaded_at',
        'change_log'
    ];

    protected $useTimestamps = false; // Uploaded_at is enough
}
