<?php

namespace App\Models;

/**
 * DocumentModel
 * 
 * Quản lý kho tài liệu tập trung (DMS) cho toàn hệ thống.
 * Hỗ trợ liên kết khách hàng, vụ việc, phân loại thông minh và quản lý phiên bản.
 */
class DocumentModel extends BaseModel
{
    protected $table            = 'documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'customer_id',
        'case_id',
        'step_id',
        'document_category',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'size',
        'uploaded_by',
        'version_number',
        'is_encrypted',
        'is_confidential',
        'tags',
        'description',
        'retention_period',
        'expiry_date'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation rules for DMS
    protected $validationRules = [
        'file_name'         => 'required|min_length[3]',
        'file_path'         => 'required',
        'document_category' => 'required',
        'uploaded_by'       => 'required'
    ];

    /**
     * Tìm kiếm tài liệu theo bộ lọc đa năng.
     */
    public function searchDocuments($filters = [])
    {
        $builder = $this->builder();
        
        if (!empty($filters['customer_id'])) {
            $builder->where('customer_id', $filters['customer_id']);
        }
        
        if (!empty($filters['case_id'])) {
            $builder->where('case_id', $filters['case_id']);
        }
        
        if (!empty($filters['category'])) {
            $builder->where('document_category', $filters['category']);
        }
        
        if (!empty($filters['keyword'])) {
            $builder->groupStart()
                    ->like('file_name', $filters['keyword'])
                    ->orLike('description', $filters['keyword'])
                    ->orLike('tags', $filters['keyword'])
                    ->groupEnd();
        }

        return $builder->orderBy('created_at', 'DESC')->get()->getResultArray();
    }
}
