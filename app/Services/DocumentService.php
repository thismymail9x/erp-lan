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

        $file->move($uploadPath, $newName);
        $filePath = 'uploads/dms/' . $subDir . '/' . $newName;

        $dbData = [
            'file_name'         => $data['file_name'] ?? $file->getClientName(),
            'file_path'         => $filePath,
            'file_type'         => $file->getExtension(),
            'mime_type'         => $file->getClientMimeType(),
            'size'              => $file->getSize(),
            'uploaded_by'       => session()->get('user_id'),
            'document_category' => $data['document_category'] ?? 'case_file',
            'customer_id'       => $data['customer_id'] ?? null,
            'case_id'           => $data['case_id'] ?? null,
            'step_id'           => $data['step_id'] ?? null,
            'is_confidential'   => $data['is_confidential'] ?? 0,
            'tags'              => isset($data['tags']) ? json_encode($data['tags']) : null,
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
        } else {
            // TẠI MỚI
            $docId = $this->docModel->insert($dbData);
        }

        // 3. Ghi NHẬT KÝ AUDIT
        $this->logAccess($docId, 'upload');

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
        if (has_permission('sys.admin')) return true;

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
