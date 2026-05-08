<?php

namespace App\Services;

use App\Models\DocumentModel;
use App\Models\DocumentVersionModel;
use App\Models\DocumentAccessLogModel;
use App\Models\CaseModel;
use CodeIgniter\Files\File;

/**
 * DocumentService
 * 
 * Luồng xử lý nghiệp vụ trung tâm cho Hệ thống Quản lý Tài liệu (DMS).
 * Đảm nhiệm: Tải lên, Phân loại, Bảo mật, Phân quyền và Nhật ký Audit.
 */
class DocumentService extends BaseService
{
    protected $docModel;
    protected $versionModel;
    protected $accessLogModel;

    public function __construct()
    {
        parent::__construct();
        $this->docModel = new DocumentModel();
        $this->versionModel = new DocumentVersionModel();
        $this->accessLogModel = new DocumentAccessLogModel();
    }

    /**
     * Xử lý tải lên tài liệu mới hoặc phiên bản mới.
     * 
     * @param \CodeIgniter\HTTP\Files\UploadedFile $file Đối tượng file từ request.
     * @param array $data Metadata bổ sung (category, customer_id, case_id, tags...).
     * @param int|null $existingDocId Nếu là upload phiên bản mới cho tài liệu cũ.
     */
    public function upload($file, array $data, $existingDocId = null)
    {
        if (!$file->isValid() || $file->hasMoved()) {
            return $this->fail('File không hợp lệ hoặc đã được di chuyển.');
        }

        // 1. Kiểm tra quyền upload (RBAC)
        if (!has_permission('case.manage') && !has_permission('sys.admin')) {
            // Nhân viên thường chỉ được upload vào vụ việc họ được gán
            if (!empty($data['case_id'])) {
                $caseModel = new CaseModel();
                $isMember = $caseModel->db->table('case_members')
                    ->where('case_id', $data['case_id'])
                    ->where('employee_id', session()->get('employee_id'))
                    ->countAllResults() > 0;
                
                if (!$isMember) {
                    return $this->fail('Bạn không có quyền tải lên tài liệu cho vụ việc này.');
                }
            }
        }

        // 2. Chế độ lưu trữ an toàn (Safe Storage)
        $newName = $file->getRandomName();
        $subDir = $data['document_category'] ?? 'internal';
        $uploadPath = WRITEPATH . 'uploads/dms/' . $subDir;
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Collect metadata BEFORE moving the file to prevent temp file access errors (finfo/stream)
        $clientName = $file->getClientName();
        $extension  = $file->getExtension();
        $mimeType   = $file->getClientMimeType();
        $fileSize   = $file->getSize();

        $file->move($uploadPath, $newName);
        $filePath = 'uploads/dms/' . $subDir . '/' . $newName;

        $dbData = [
            'file_name'         => $data['file_name'] ?? $clientName,
            'file_path'         => $filePath,
            'file_type'         => $extension,
            'mime_type'         => $mimeType,
            'size'              => $fileSize,
            'uploaded_by'       => session()->get('user_id'),
            'document_category' => $data['document_category'] ?? 'case_file',
            'customer_id'       => !empty($data['customer_id']) ? $data['customer_id'] : null,
            'case_id'           => !empty($data['case_id']) ? $data['case_id'] : null,
            'step_id'           => !empty($data['step_id']) ? $data['step_id'] : null,
            'is_confidential'   => $data['is_confidential'] ?? 0,
            'description'       => $data['description'] ?? '',
            'retention_period'  => $data['retention_period'] ?? 10,
            'expiry_date'       => $data['expiry_date'] ?? null,
        ];

        if ($existingDocId) {
            // XỬ LÝ VERSIONING
            $oldDoc = $this->docModel->find($existingDocId);
            if (!$oldDoc) return $this->fail('Không tìm thấy tài liệu gốc để nâng cấp phiên bản.');

            // Lưu phiên bản cũ vào bảng versions
            $this->versionModel->insert([
                'document_id'    => $oldDoc['id'],
                'version_number' => $oldDoc['version_number'],
                'file_name'      => $oldDoc['file_name'],
                'file_path'      => $oldDoc['file_path'],
                'uploaded_by'    => $oldDoc['uploaded_by'],
                'uploaded_at'    => $oldDoc['updated_at'] ?? $oldDoc['created_at'],
                'change_log'     => $data['change_log'] ?? 'Cập nhật phiên bản mới'
            ]);

            // Cập nhật tài liệu chính lên phiên bản mới
            $dbData['version_number'] = $oldDoc['version_number'] + 1;
            $this->docModel->update($existingDocId, $dbData);
            $docId = $existingDocId;

            // ĐỒNG BỘ NHÃN DÁN (Smart Tagging System)
            if (isset($data['tags']) && is_array($data['tags'])) {
                $tagService = new \App\Services\TagService();
                $tagService->syncTags($docId, 'documents', $data['tags']);
            }
        } else {
            $this->docModel->db->transStart();
            try {
                // TẠO MỚI (Physical Insert)
                if (!$this->docModel->insert($dbData)) {
                    throw new \Exception('Insert failed: ' . implode(', ', $this->docModel->errors()));
                }
                
                $docId = $this->docModel->getInsertID() ?: $this->docModel->db->insertID();
                
                if (empty($docId)) {
                    throw new \Exception('Không thể lấy ID tài liệu vừa tạo.');
                }

                // ĐỒNG BỘ NHÃN DÁN (Smart Tagging System)
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $tagService = new \App\Services\TagService();
                    $tagService->syncTags($docId, 'documents', $data['tags']);

                    // Cập nhật chuỗi tags cache để hiển thị/tìm kiếm nhanh
                    $tags = $tagService->getTagsByEntity($docId, 'documents');
                    $tagNames = array_column($tags, 'name');
                    $this->docModel->update($docId, ['tags' => json_encode($tagNames)]);
                }

                // Nhật ký Audit (Bên trong Transaction)
                $this->logAccess($docId, 'upload');
                
                $this->docModel->db->transComplete();
                
                if ($this->docModel->db->transStatus() === false) {
                    throw new \Exception('Transaction failed. Có lỗi ràng buộc dữ liệu (DB Constraint).');
                }
            } catch (\Exception $e) {
                $this->docModel->db->transRollback();
                log_message('error', 'DMS Insert Error: ' . $e->getMessage());
                return $this->fail('Lỗi hệ thống: ' . $e->getMessage() . '. Vui lòng kiểm tra liên kết Vụ việc/Khách hàng.');
            }
        }

        return $this->success(['id' => $docId], 'Tài liệu đã được tải lên thành công.');
    }

    /**
     * Kiểm tra quyền truy cập tài liệu (Row-Level Security).
     */
    public function checkAccess($docId, $action = 'view')
    {
        $doc = $this->docModel->find($docId);
        if (!$doc) return false;

        $userId = session()->get('user_id');
        $empId = session()->get('employee_id');

        // Admin/Mod luôn có quyền
        if (has_permission('sys.admin') || has_permission('case.manage')) return true;

        // Nếu không phải là Hồ sơ KH hoặc Hồ sơ vụ việc thì cho phép xem công khai (Nội bộ, Biểu mẫu, Tài chính...)
        if (!in_array($doc['document_category'], ['client_intake', 'case_file'])) {
            return true;
        }

        // Nếu là tài liệu vụ việc
        if ($doc['case_id']) {
            $caseModel = new CaseModel();
            // Kiểm tra xem nhân viên có trong team xử lý vụ việc không
            $isMember = $caseModel->db->table('case_members')
                ->where('case_id', $doc['case_id'])
                ->where('employee_id', $empId)
                ->countAllResults() > 0;
            
            if ($isMember) return true;
        }

        // Nếu là tài liệu khách hàng (Consultant quyền trên KH mình tạo)
        if ($doc['customer_id']) {
            $db = \Config\Database::connect();
            $customer = $db->table('customers')->where('id', $doc['customer_id'])->get()->getRowArray();
            // Giả định có field phụ trách (Ví dụ business logic hiện tại)
            // if ($customer['assigned_staff_id'] == $empId) return true;
        }

        // Nếu là người tải lên
        if ($doc['uploaded_by'] == $userId) return true;

        return false;
    }

    /**
     * Ghi nhật ký truy cập/thao tác file.
     */
    public function logAccess($docId, $action)
    {
        $request = \Config\Services::request();
        $this->accessLogModel->insert([
            'document_id' => $docId,
            'user_id'     => session()->get('user_id'),
            'action'      => $action,
            'ip_address'  => $request->getIPAddress(),
            'user_agent'  => $request->getUserAgent()->getAgentString(),
            'created_at'  => date('Y-m-d H:i:s')
        ]);
    }
}
