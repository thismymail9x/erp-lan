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
        ];

        // 2. Thực hiện truy vấn (Phân quyền logic nằm ngay trong Model search hoặc Service)
        $documents = $this->docModel->searchDocuments($filters);

        // 3. Dữ liệu bổ trợ cho form lọc
        $customers = $this->customerModel->findAll();
        $cases = $this->caseModel->findAll();

        $data = [
            'documents' => $documents,
            'customers' => $customers,
            'cases'     => $cases,
            'filters'   => $filters,
            'title'     => 'Quản lý Tài liệu Số (DMS) | L.A.N ERP'
        ];

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

        // Phục vụ file từ WritePath (Cần cấu hình Storage hợp lý)
        $fullPath = $doc['file_path'];
        if (strpos($fullPath, 'uploads/') === 0) {
            // Trường hợp file nằm trong public/uploads hoặc bên ngoài writepath
            $realPath = WRITEPATH . $fullPath; 
            if (file_exists($realPath)) {
                return $this->response->download($realPath, null)->setFileName($doc['file_name']);
            }
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
        
        // Mở rộng tags từ chuỗi comma-separated qua array
        if (!empty($data['tags_raw'])) {
            $data['tags'] = explode(',', $data['tags_raw']);
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
            'category' => $this->request->getGet('category') ?: 'internal'
        ];
        
        // Chỉ lấy những tài liệu chưa được gán cho vụ việc/khách hàng cụ thể (nếu cần) 
        // hoặc lấy từ các category dùng chung.
        $documents = $this->docModel->searchDocuments($filters);

        return $this->response->setJSON($documents);
    }
}
