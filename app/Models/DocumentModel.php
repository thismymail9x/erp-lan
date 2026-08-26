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

    protected $validationRules = [
        'file_name'         => 'required|min_length[3]',
        'file_path'         => 'required',
        'document_category' => 'required',
        'uploaded_by'       => 'required'
    ];

    protected $insertValidationRules = [
        'file_name'         => 'required|min_length[3]',
        'file_path'         => 'required',
        'document_category' => 'required',
        'uploaded_by'       => 'required',
    ];

    protected $updateValidationRules = [];

    public function insert($row = null, bool $returnID = true)
    {
        $this->validationRules = $this->insertValidationRules;
        return parent::insert($row, $returnID);
    }

    public function update($id = null, $data = null): bool
    {
        $this->validationRules = $this->updateValidationRules;
        return parent::update($id, $data);
    }

    /**
     * Tìm kiếm tài liệu theo bộ lọc và Phân quyền dữ liệu (Security Scoping).
     */
    public function searchDocuments($filters = [], $employeeId = null, ?int $perPage = null)
    {
        $builder = $this;
        $hasDocumentFiles = $this->db->tableExists('document_files');
        $fileSummarySql = '(SELECT document_id, COUNT(*) AS attachment_count, SUM(size) AS total_size, GROUP_CONCAT(id ORDER BY sort_order SEPARATOR ",") AS attachment_ids, GROUP_CONCAT(original_name ORDER BY sort_order SEPARATOR "\n") AS attachment_names FROM document_files WHERE deleted_at IS NULL GROUP BY document_id) document_file_summary';

        $builder->select('documents.*, customers.name as customer_name, cases.code as case_code, cases.title as case_title, employees.full_name as uploader_name');
        if ($hasDocumentFiles) {
            $builder->select('COALESCE(document_file_summary.attachment_count, 1) AS attachment_count', false);
            $builder->select('COALESCE(document_file_summary.total_size, documents.size) AS total_size', false);
            $builder->select('document_file_summary.attachment_ids', false);
            $builder->select('document_file_summary.attachment_names', false);
        } else {
            $builder->select('1 AS attachment_count, documents.size AS total_size, NULL AS attachment_ids, NULL AS attachment_names', false);
        }
        $builder->join('customers', 'customers.id = documents.customer_id', 'left');
        $builder->join('cases', 'cases.id = documents.case_id', 'left');
        $builder->join('employees', 'employees.user_id = documents.uploaded_by', 'left');
        if ($hasDocumentFiles) {
            $builder->join($fileSummarySql, 'document_file_summary.document_id = documents.id', 'left', false);
        }
        
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
                // 1. Tài liệu do chính mình tải lên
                ->where('documents.uploaded_by', session()->get('user_id'))
                // 2. Tài liệu thuộc các danh mục chung (Nội bộ, Biểu mẫu, Tài chính, Thư từ...)
                ->orWhereNotIn('documents.document_category', ['client_intake', 'case_file'])
                // 3. Tài liệu Hồ sơ KH/Vụ việc nhưng mình có tham gia xử lý vụ việc đó
                ->orGroupStart()
                    ->whereIn('documents.document_category', ['client_intake', 'case_file'])
                    ->whereIn('documents.case_id', $allCaseIds)
                ->groupEnd()
            ->groupEnd();
        }

        if (!empty($filters['customer_id'])) {
            $builder->where('documents.customer_id', $filters['customer_id']);
        }
        
        if (!empty($filters['case_id'])) {
            $builder->where('documents.case_id', $filters['case_id']);
        }
        
        if (!empty($filters['category'])) {
            $builder->where('documents.document_category', $filters['category']);
        }

        if (!empty($filters['tag_id'])) {
            $tagDb = \Config\Database::connect();
            $tagQuery = $tagDb->table('entity_tags')
                                 ->select('entity_id')
                                 ->where('entity_type', 'documents')
                                 ->where('tag_id', $filters['tag_id'])
                                 ->get();
            
            $tagEntityIds = $tagQuery ? $tagQuery->getResultArray() : [];
            $involvedDocIds = array_column($tagEntityIds, 'entity_id') ?: [-1];
            $builder->whereIn('documents.id', $involvedDocIds);
        }
        
        if (!empty($filters['keyword'])) {
            $builder->groupStart()
                    ->like('documents.file_name', $filters['keyword'])
                    ->orLike('documents.description', $filters['keyword'])
                    ->orLike('documents.tags', $filters['keyword'])
                    ->groupEnd();
        }

        // Đảm bảo chỉ lấy các tài liệu chưa bị xóa
        $builder->where('documents.deleted_at IS NULL');

        // Logic sắp xếp động (Sorting)
        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'DESC';

        $allowedSort = [
            'file_name'         => 'documents.file_name',
            'document_category' => 'documents.document_category',
            'size'              => 'documents.size',
            'created_at'        => 'documents.created_at',
            'case_id'           => 'documents.case_id',
            'customer_id'       => 'documents.customer_id'
        ];

        if (isset($allowedSort[$sort])) {
            $builder->orderBy($allowedSort[$sort], $order);
        } else {
            $builder->orderBy('documents.created_at', 'DESC');
        }

        if ($perPage !== null) {
            return $builder->paginate($perPage);
        }

        return $builder->findAll();
    }
}
