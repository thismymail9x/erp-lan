<?php

namespace App\Models;

/**
 * DocumentFileModel
 *
 * Quan ly cac tep vat ly thuoc mot tai lieu DMS. Mot tai lieu co the gom nhieu tep
 * nhung van chi co mot ban ghi metadata cha trong bang `documents`.
 */
class DocumentFileModel extends BaseModel
{
    protected $table            = 'document_files';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'document_id',
        'original_name',
        'file_path',
        'file_type',
        'mime_type',
        'size',
        'sort_order',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
