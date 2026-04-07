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
     * Tìm kiếm tài liệu theo bộ lọc và Phân quyền dữ liệu (Security Scoping).
     */
    public function searchDocuments($filters = [], $employeeId = null)
    {
        $builder = $this->builder();
        $builder->select('documents.*, customers.name as customer_name, cases.code as case_code, cases.title as case_title');
        $builder->join('customers', 'customers.id = documents.customer_id', 'left');
        $builder->join('cases', 'cases.id = documents.case_id', 'left');
        
        // --- BẢO MẬT: PHÂN LẬP DỮ LIỆU TÀI LIỆU (DMS isolation) ---
        if ($employeeId) {
            $db = \Config\Database::connect();
            // Lấy danh sách ID các vụ việc mà nhân viên này tham gia (Sync logic with CaseService)
            $caseIds1 = $db->table('cases')->select('id')
                          ->where('assigned_lawyer_id', $employeeId)
                          ->orWhere('assigned_staff_id', $employeeId)
                          ->get()->getResultArray();
            
            $caseIds2 = $db->table('case_members')->select('case_id')
                          ->where('employee_id', $employeeId)
                          ->get()->getResultArray();

            $allCaseIds = array_unique(array_merge(
                array_column($caseIds1, 'id'), 
                array_column($caseIds2, 'case_id')
            )) ?: [-1];

            $builder->groupStart()
                ->whereIn('documents.case_id', $allCaseIds)
                // Hoặc là tài liệu "Mẫu" dùng chung cho công ty
                ->orWhere('documents.document_category', 'template')
                // Hoặc là tài liệu do chính User này tải lên (không gán vụ việc)
                ->orWhere('documents.uploaded_by', session()->get('user_id'))
            ->groupEnd();
        }

        if (!empty($filters['customer_id'])) {
            $builder->where('documents.customer_id', $filters['customer_id']);
        }
        
        if (!empty($filters['case_id'])) {
            $builder->where('documents.case_id', $filters['case_id']);
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

        // Logic sắp xếp động (Sorting)
        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'DESC';
        
        $allowedSort = ['file_name', 'document_category', 'size', 'created_at', 'case_id', 'customer_id'];
        if (in_array($sort, $allowedSort)) {
            $builder->orderBy($sort, $order);
        } else {
            $builder->orderBy('created_at', 'DESC');
        }

        return $builder->get()->getResultArray();
    }
}
