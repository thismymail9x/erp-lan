<?php

namespace App\Controllers;

use App\Models\DocumentModel;
use App\Models\CustomerModel;
use App\Models\CaseModel;
use App\Services\DocumentService;

/**
 * DocumentController
 * 
 * Module quản trị số hóa hồ sơ (DMS).
 * Phục vụ nhu cầu lưu trữ tập trung và tìm kiếm tài liệu thông minh.
 */
class DocumentController extends BaseController
{
    /**
     * Khai báo metadata cho hệ thống Tự động Đồng bộ (Auto-Sync Permissions).
     * Dùng cho cỗ máy quét tại: /perm-fix/sync
     */
    public static $modulePermissions = [
        'group' => 'Hệ thống',
        'permissions' => [
            'document.view'   => 'Xem và truy xuất kho tài liệu số (DMS)',
            'document.manage' => 'Quản trị hồ sơ: Upload, chỉnh sửa và gỡ bỏ tài liệu'
        ]
    ];

    /**
     * Khai báo danh mục thuộc thể loại Nhãn dán (Smart Tags).
     * Dùng cho cỗ máy quét tại: /perm-fix/sync
     */
    public static $taggable = [
        'type'  => 'documents',
        'label' => 'Tài liệu (DMS)'
    ];

    protected $docModel;
    protected $customerModel;
    protected $caseModel;
    protected $docService;

    public function __construct()
    {
        $this->docModel = new DocumentModel();
        $this->customerModel = new CustomerModel();
        $this->caseModel = new CaseModel();
        $this->docService = new DocumentService();
    }

    /**
     * TRANG CHỦ DMS: Tìm kiếm và lọc tài liệu đa năng.
     */
    public function index()
    {
        // 1. Thu thập bộ lọc từ Request
        $filters = [
            'keyword'     => $this->request->getGet('keyword'),
            'category'    => $this->request->getGet('category'),
            'customer_id' => $this->request->getGet('customer_id'),
            'case_id'     => $this->request->getGet('case_id'),
            'tag_id'      => $this->request->getGet('tag_id'), // Lọc theo nhãn dán cụ thể
            'sort'        => $this->request->getGet('sort') ?: 'created_at',
            'order'       => $this->request->getGet('order') ?: 'DESC',
        ];

        // 2. PHÂN QUYỀN DỮ LIỆU (Security Data Filtering)
        $myEmpId = null;
        $customers = [];
        $cases = [];

        if (!has_permission('sys.admin') && !has_permission('case.edit_all')) {
            $myEmpId = session()->get('employee_id');
            
            // Chỉ lấy các vụ việc nhân viên này tham gia
            $caseMemberModel = new \App\Models\CaseMemberModel();
            $involvedCaseIds = $caseMemberModel->where('employee_id', $myEmpId)->findColumn('case_id') ?: [-1];

            $cases = $this->caseModel->groupStart()
                ->where('assigned_lawyer_id', $myEmpId)
                ->orWhere('assigned_staff_id', $myEmpId)
                ->orWhereIn('id', $involvedCaseIds)
                ->groupEnd()
                ->findAll();
                
            $customerIds = array_column($cases, 'customer_id') ?: [-1];
            $customers = $this->customerModel->whereIn('id', array_unique($customerIds))->findAll();
        } else {
            // Admin/Quản lý: Xem toàn bộ - Chỉ nạp cho trang chính (không nạp cho AJAX để tối ưu)
            if (!$this->request->isAJAX()) {
                $customers = get_active_customers();
                $cases = get_active_cases();
            }
        }

        // 3. Thực hiện truy vấn danh sách tài liệu (Scoped search)
        $documents = $this->docModel->searchDocuments($filters, $myEmpId);

        $allUsers = [];
        if (!$this->request->isAJAX()) {
            $userModel = new \App\Models\UserModel();
            $allUsers = $userModel->select('users.id, employees.full_name')
                                 ->join('employees', 'employees.user_id = users.id')
                                 ->where('users.active_status', 1)
                                 ->findAll();
        }

        $data = [
            'documents'     => $documents,
            'customers'     => $customers,
            'cases'         => $cases,
            'allUsers'      => $allUsers,
            'availableTags' => get_available_tags('documents'), 
            'filters'       => $filters,
            'title'         => 'Quản lý Tài liệu Số | L.A.N ERP'
        ];

        if ($this->request->isAJAX()) {
            return view('dashboard/documents/index_table', $data);
        }

        return view('dashboard/documents/index', $data);
    }

    /**
     * DOWNLOAD/VIEW FILE (Có logging bảo mật).
     */
    public function view($id)
    {
        // Kiểm tra quyền truy cập trc khi cho xem
        if (!$this->docService->checkAccess($id, 'view')) {
            return redirect()->back()->with('error', 'Cảnh báo bảo mật: Bạn không được quyền truy cập tài liệu này.');
        }

        $doc = $this->docModel->find($id);
        if (!$doc) return redirect()->back()->with('error', 'Tài liệu không tồn tại.');

        // Kiểm tra quyền truy cập (DMS Security Layer)
        if (!$this->docService->checkAccess($id, 'view')) {
            return redirect()->back()->with('error', 'Bạn không có quyền truy cập vào tài liệu bảo mật này.');
        }

        // Ghi Log Audit
        $this->docService->logAccess($id, 'view');

        // Phục vụ file từ WritePath
        $fullPath = $doc['file_path'];
        $realPath = WRITEPATH . $fullPath;

        if (file_exists($realPath)) {
            $isPreview = ($this->request->getGet('preview') == 1);
            
            // Tự động bổ sung đuôi file nếu tên người dùng đặt chưa có (Tránh file không định dạng)
            $downloadName = $doc['file_name'];
            $extension = pathinfo($realPath, PATHINFO_EXTENSION);
            if (!empty($extension) && !str_ends_with(strtolower($downloadName), '.' . strtolower($extension))) {
                $downloadName .= '.' . $extension;
            }

            if ($isPreview) {
                // Ép hiển thị trực tiếp bằng Header thủ công (Cực mạnh cho PDF/Ảnh)
                // Đảm bảo không bị trình duyệt hiểu nhầm là lệnh Download
                return $this->response
                    ->setHeader('Content-Type', $doc['mime_type'])
                    ->setHeader('Content-Disposition', 'inline; filename="' . $downloadName . '"')
                    ->setBody(file_get_contents($realPath));
            }

            // Tải xuống (Download) với tên file đầy đủ định dạng
            return $this->response->download($realPath, null)->setFileName($downloadName);
        }
        
        return redirect()->back()->with('error', 'Không tìm thấy tệp tin trên hệ thống lưu trữ.');
    }

    /**
     * XỬ LÝ UPLOAD (Tự động hóa thông tin metadata).
     */
    public function upload()
    {
        $file = $this->request->getFile('document');
        $data = $this->request->getPost();
        
        // Tags đã được xử lý thành array từ View (Multi-select)
        // Tuy nhiên vẫn hỗ trợ fallback nếu có dữ liệu text lẻ
        if (!empty($data['tags_raw'])) {
            $data['tags'] = is_array($data['tags_raw']) ? $data['tags_raw'] : explode(',', $data['tags_raw']);
        }

        $result = $this->docService->upload($file, $data);

        if ($result['status'] == 'success') {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * XÓA TÀI LIỆU (Soft Delete + Logging).
     */
    public function delete($id)
    {
        if (!has_permission('sys.admin')) {
             return redirect()->back()->with('error', 'Chỉ Quản trị viên mới được phép xóa vĩnh viễn tài liệu khỏi DMS.');
        }

        if ($this->docModel->delete($id)) {
            $this->docService->logAccess($id, 'delete');
            return redirect()->back()->with('success', 'Tài liệu đã được đưa vào thùng rác.');
        }

        return redirect()->back()->with('error', 'Có lỗi xảy ra khi xóa tài liệu.');
    }

    /**
     * API: Lấy danh sách tài liệu từ kho (Vault) để Import.
     * Trả về JSON cho Modal chọn tài liệu.
     */
    public function getVaultDocuments()
    {
        $filters = [
            'keyword'  => $this->request->getGet('keyword'),
            'category' => $this->request->getGet('category') ?: 'internal'
        ];
        
        // Chỉ lấy những tài liệu chưa được gán cho vụ việc/khách hàng cụ thể (nếu cần) 
        // hoặc lấy từ các category dùng chung.
        $documents = $this->docModel->searchDocuments($filters);

        return $this->response->setJSON($documents);
    }

    /**
     * API: Lấy thông tin tài liệu để chỉnh sửa (JSON).
     */
    public function edit($id)
    {
        $doc = $this->docModel->find($id);
        if (!$doc) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tài liệu không tồn tại.']);
        }

        // Lấy danh sách ID các tag hiện tại
        $tagService = new \App\Services\TagService();
        $tags = $tagService->getTagsByEntity($id, 'documents');
        $doc['tag_names'] = array_column($tags, 'name');

        return $this->response->setJSON($doc);
    }

    /**
     * XỬ LÝ CẬP NHẬT METADATA.
     */
    public function update($id)
    {
        $doc = $this->docModel->find($id);
        if (!$doc) return redirect()->back()->with('error', 'Tài liệu không tồn tại.');

        // Kiểm tra quyền (Admin hoặc người upload)
        if (!has_permission('sys.admin') && $doc['uploaded_by'] != session()->get('user_id')) {
            return redirect()->back()->with('error', 'Bạn không có quyền chỉnh sửa tài liệu của người khác.');
        }

        $input = $this->request->getPost();
        
        // Chuẩn hóa dữ liệu
        $updateData = [
            'file_name'         => $input['file_name'],
            'document_category' => $input['document_category'],
            'description'       => $input['description'],
            'is_confidential'   => $input['is_confidential'] ?? 0,
            'case_id'           => !empty($input['case_id']) ? $input['case_id'] : null,
            'customer_id'       => !empty($input['customer_id']) ? $input['customer_id'] : null,
        ];

        if ($this->docModel->update($id, $updateData)) {
            // Cập nhật nhãn dán
            if (isset($input['tags_raw'])) {
                $tagIds = is_array($input['tags_raw']) ? $input['tags_raw'] : explode(',', $input['tags_raw']);
                $tagService = new \App\Services\TagService();
                $tagService->syncTags($id, 'documents', $tagIds);
                
                // Cập nhật chuỗi tags cache để tìm kiếm nhanh
                $tags = $tagService->getTagsByEntity($id, 'documents');
                $tagNames = array_column($tags, 'name');
                $this->docModel->update($id, ['tags' => json_encode($tagNames)]);
            }

            $this->docService->logAccess($id, 'update');
            return redirect()->back()->with('success', 'Thông tin tài liệu đã được cập nhật.');
        }

        return redirect()->back()->with('error', 'Cập nhật thất bại.');
    }

    /**
     * Xóa chọn (Bulk Action).
     */
    public function bulkDelete()
    {
        if (!has_permission('sys.admin')) {
            return redirect()->back()->with('error', 'Chỉ Quản trị viên mới được thực hiện Xóa chọn.');
        }

        $ids = $this->request->getPost('ids');
        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', 'Cảnh báo: Bạn chưa chọn tài liệu nào để xử lý.');
        }

        $successCount = 0;
        foreach ($ids as $id) {
            if ($this->docModel->delete($id)) {
                $this->docService->logAccess($id, 'bulk_delete');
                $successCount++;
            }
        }

        return redirect()->back()->with('success', "Đã dọn dẹp thành công {$successCount} tài liệu khỏi danh sách.");
    }

    /**
     * CHIA SẺ TÀI LIỆU (Gửi thông báo).
     */
    public function share($id)
    {
        $doc = $this->docModel->find($id);
        if (!$doc) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tài liệu không tồn tại.']);
        }

        $userIds = $this->request->getPost('user_ids');
        $message = $this->request->getPost('message');

        if (empty($userIds)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Vui lòng chọn người nhận.']);
        }

        $notifService = new \App\Services\NotificationService();
        $title = "Tài liệu được chia sẻ: " . $doc['file_name'];
        $content = "Bạn nhận được một tài liệu chia sẻ từ " . session()->get('full_name') . ".\n\nNội dung: " . ($message ?: 'Không có ghi chú.');
        $link = base_url('documents/view/' . $id);

        $sentCount = $notifService->sendToMultiple((array)$userIds, $title, $content, 'system', $link);

        if ($sentCount > 0) {
            return $this->response->setJSON(['status' => 'success', 'message' => "Đã chia sẻ tài liệu đến {$sentCount} người dùng."]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Không thể gửi thông báo.']);
    }
}
