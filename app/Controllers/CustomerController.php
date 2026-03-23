<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\CustomerInteractionModel;
use App\Models\CustomerDocumentModel;
use App\Models\CustomerPaymentModel;
use App\Models\CaseModel;
use App\Services\CustomerService;

/**
 * CustomerController
 * 
 * Điều hướng và xử lý logic cho Module Khách hàng (CRM).
 * Quản lý vòng đời khách hàng từ khi tiếp nhận, phân loại đến theo dõi tương tác.
 */
class CustomerController extends BaseController
{
    protected $customerModel;
    protected $customerService;

    public function __construct()
    {
        // Khởi tạo model và service phục vụ cho controller
        $this->customerModel = new CustomerModel();
        $this->customerService = new CustomerService();
    }

    /**
     * Dashboard CRM & Danh sách khách hàng
     * Hiển thị bảng điều khiển tổng quát và danh sách khách hàng kèm bộ lọc.
     */
    public function index()
    {
        // 1. Lấy tham số tìm kiếm và lọc từ URL
        $search = $this->request->getGet('q');
        $type = $this->request->getGet('type');
        
        $query = $this->customerModel;

        // 2. Xử lý logic tìm kiếm đa trường (Tên, SĐT, CCCD, Mã KH)
        if ($search) {
            $query->groupStart()
                  ->like('name', $search)
                  ->orLike('phone', $search)
                  ->orLike('identity_number', $search)
                  ->orLike('code', $search)
                  ->groupEnd();
        }

        // 3. Lọc theo loại khách hàng (Cá nhân/Doanh nghiệp)
        if ($type) {
            $query->where('type', $type);
        }

        // 4. Tổng hợp dữ liệu trả về view
        $data = [
            'customers' => $query->orderBy('created_at', 'DESC')->findAll(),
            'stats'     => $this->customerService->getDashboardStats(), // Lấy thông số thống kê CRM
            'title'     => 'Quản lý khách hàng (CRM)'
        ];

        return view('dashboard/customers/index', $data);
    }

    /**
     * Giao diện thêm khách hàng mới (Wizard)
     * Trả về view chứa form Wizard 3 bước để tiếp nhận hồ sơ khách hàng.
     */
    public function create()
    {
        $data = [
            'title' => 'Thêm khách hàng mới'
        ];

        return view('dashboard/customers/create', $data);
    }

    /**
     * Kiểm tra trùng lặp khách hàng (API cho Wizard tạo mới)
     * Trả về JSON cho frontend để cảnh báo nếu SĐT hoặc CCCD đã tồn tại.
     */
    public function checkDuplicate()
    {
        $data = $this->request->getGet();
        $duplicates = $this->customerService->findDuplicates($data);

        if (!empty($duplicates)) {
            return $this->response->setJSON([
                'exists' => true,
                'duplicates' => $duplicates
            ]);
        }

        return $this->response->setJSON(['exists' => false]);
    }

    /**
     * Chi tiết hồ sơ khách hàng (360-degree view)
     * Hiển thị toàn bộ thông tin, vụ việc, tương tác và tài chính của khách hàng.
     */
    public function show($id)
    {
        // 1. Kiểm tra sự tồn tại của khách hàng
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            return redirect()->to(base_url('customers'))->with('error', 'Không tìm thấy khách hàng.');
        }

        // 2. Tuân thủ PDPL: Ghi log lịch sử truy cập dữ liệu nhạy cảm
        // Đảm bảo mọi hoạt động xem thông tin cá nhân đều được lưu vết audit.
        $logService = new \App\Services\SystemLogService();
        $logService->log('DATA_ACCESS', 'Customers', $id, [
            'type' => 'PROFILE_VIEW',
            'sensitive_fields' => ['identity_number', 'phone', 'address']
        ]);

        // 3. Khởi tạo các model liên quan để lấy dữ liệu đa tầng
        $caseModel = new CaseModel();
        $interactionModel = new CustomerInteractionModel();
        $paymentModel = new CustomerPaymentModel();
        $documentModel = new CustomerDocumentModel();

        // 4. Chuẩn bị dữ liệu cho giao diện tabbed
        $data = [
            'customer'     => $customer,
            'cases'        => $caseModel->where('customer_id', $id)->findAll(), // Danh sách vụ việc
            'interactions' => $interactionModel->getByCustomer($id), // Lịch sử tương tác
            'payments'     => $paymentModel->where('customer_id', $id)->findAll(), // Dòng tiền/thanh toán
            'documents'    => $documentModel->where('customer_id', $id)->findAll(), // Kho tài liệu số hóa
            'title'        => 'Hồ sơ khách hàng: ' . $customer['name']
        ];

        return view('dashboard/customers/show', $data);
    }

    /**
     * Tìm kiếm khách hàng "bỏ ngỏ" (Stale Customers)
     * Lọc danh sách khách hàng không có tương tác trong hơn 30 ngày.
     */
    public function stale()
    {
        $staleCustomers = $this->customerService->getStaleCustomers(30);
        
        $data = [
            'customers' => $staleCustomers,
            'title'     => 'Khách hàng lâu chưa tương tác'
        ];

        return view('dashboard/customers/stale', $data);
    }

    /**
     * Tải lên tài liệu khách hàng (Identity Vault)
     * Xử lý file upload và lưu đường dẫn vào database.
     */
    public function uploadDocument($id)
    {
        $file = $this->request->getFile('document');
        
        // Kiểm tra tính hợp lệ của file
        if ($file->isValid() && !$file->hasMoved()) {
            // 1. Tạo tên file ngẫu nhiên và di chuyển vào thư mục lưu trữ an toàn
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/customer_docs', $newName);

            // 2. Lưu metadata vào bảng customer_documents
            $documentModel = new CustomerDocumentModel();
            $documentModel->save([
                'customer_id'   => $id,
                'document_type' => $this->request->getPost('document_type'),
                'file_name'     => $file->getClientName(),
                'file_path'     => 'uploads/customer_docs/' . $newName,
                'uploaded_by'   => session()->get('user_id')
            ]);

            return redirect()->back()->with('success', 'Đã tải lên tài liệu thành công.');
        }

        return redirect()->back()->with('error', 'Lỗi khi tải lên tài liệu.');
    }

    /**
     * Lưu thông tin khách hàng mới
     * Xử lý dữ liệu từ Wizard và tự động tạo mã khách hàng.
     */
    public function store()
    {
        $data = $this->request->getPost();
        
        // 1. Tự động sinh mã khách hàng (KH-YYYY-XXX) nếu chưa có
        if (empty($data['code'])) {
            $count = $this->customerModel->countAllResults() + 1;
            $data['code'] = 'KH-' . date('Y') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        // 2. Thực hiện lưu vào database (Validation đã được cấu hình trong Model)
        if ($this->customerModel->save($data)) {
            return redirect()->to(base_url('customers'))->with('success', 'Đã thêm khách hàng mới thành công.');
        }

        // 3. Trả về thông báo lỗi nếu validation thất bại
        return redirect()->back()->withInput()->with('errors', $this->customerModel->errors());
    }

    /**
     * Ghi nhận một tương tác mới
     * Cập nhật nhật ký liên lạc và làm mới ngày tương tác cuối cùng (Engagement).
     */
    public function addInteraction($customerId)
    {
        $interactionModel = new CustomerInteractionModel();
        
        // 1. Chuẩn bị dữ liệu tương tác
        $data = $this->request->getPost();
        $data['customer_id'] = $customerId;
        $data['user_id'] = session()->get('user_id');
        $data['interaction_date'] = date('Y-m-d H:i:s');

        // 2. Lưu vào database
        if ($interactionModel->save($data)) {
            // 3. Cập nhật ngày tương tác gần nhất vào bảng Customers để theo dõi engagement
            $this->customerModel->update($customerId, [
                'last_contact_date' => $data['interaction_date']
            ]);
            
            // 4. Đồng bộ lại các chỉ số thống kê (Revenue/Case count)
            $this->customerService->syncCustomerStats($customerId);

            return redirect()->back()->with('success', 'Đã lưu lịch sử tương tác.');
        }

        return redirect()->back()->with('error', 'Lỗi khi lưu tương tác.');
    }
}
