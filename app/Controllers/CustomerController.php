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
 * Bộ điều khiển trung tâm quản lý Quan hệ khách hàng (CRM).
 * Chịu trách nhiệm:
 * 1. Quản lý vòng đời khách hàng (Từ tiềm năng đến đối tác chiến lược).
 * 2. Lưu trữ hồ sơ 360 độ (Thông tin cá nhân, vụ việc, tài chính, tài liệu).
 * 3. Bảo mật dữ liệu nhạy cảm theo tiêu chuẩn PDPL.
 * 4. Phân tích sự tương tác và chăm sóc khách hàng (Stale Customer Tracking).
 */
class CustomerController extends BaseController
{
    protected $customerModel;
    protected $customerService;

    public function __construct()
    {
        // Khởi tạo model và service phục vụ cho controller CRM
        $this->customerModel = new CustomerModel();
        $this->customerService = new CustomerService();
    }

    /**
     * Giao diện CRM Dashboard & Danh sách khách hàng.
     * Tích hợp bộ lọc tìm kiếm và các chỉ số thống kê quan trọng.
     */
    public function index()
    {
        // 1. Phân tích các tham số lọc từ GET Request
        $search = $this->request->getGet('q');         // Từ khóa tìm kiếm
        $type = $this->request->getGet('type');       // Phân loại: Cá nhân / Doanh nghiệp
        
        $query = $this->customerModel;

        // 2. LOGIC TÌM KIẾM ĐA LUỒNG (Multi-field Search):
        // Cho phép tìm kiếm bằng Tên, Số điện thoại, Số CCCD/Hộ chiếu hoặc Mã khách hàng nội bộ.
        if ($search) {
            $query->groupStart()
                  ->like('name', $search)
                  ->orLike('phone', $search)
                  ->orLike('identity_number', $search)
                  ->orLike('code', $search)
                  ->groupEnd();
        }

        // 3. Phân loại đối tượng khách hàng
        if ($type) {
            $query->where('type', $type);
        }

        // --- BẢO MẬT: LỌC DỮ LIỆU DANH SÁCH (Data Isolation) ---
        // Nhân viên thường chỉ thấy khách hàng mà họ đang/đã từng phụ trách vụ việc.
        if (!has_permission('sys.admin') && !has_permission('case.manage')) {
            $myEmpId = session()->get('employee_id');
            $db = \Config\Database::connect();
            
            // 1. Phụ trách chính (Lawyer/Staff)
            $subQuery1 = $db->table('cases')
                ->select('customer_id')
                ->where('assigned_lawyer_id', $myEmpId)
                ->orWhere('assigned_staff_id', $myEmpId)
                ->getCompiledSelect();

            // 2. Là thành viên (CaseMember)
            $subQuery2 = $db->table('cases')
                ->select('customer_id')
                ->join('case_members', 'case_members.case_id = cases.id')
                ->where('case_members.employee_id', $myEmpId)
                ->getCompiledSelect();

            $query->where("id IN ($subQuery1) OR id IN ($subQuery2)", null, false);
        }

        // 4. Tổng hợp dữ liệu hiển thị
        $data = [
            'customers' => $query->orderBy('created_at', 'DESC')->findAll(), // Sắp xếp khách hàng mới nhất lên đầu
            'stats'     => $this->customerService->getDashboardStats(),     // Lấy các chỉ số KPI doanh thu/số lượng từ Service
            'title'     => 'Quản lý khách hàng (CRM) | L.A.N ERP'
        ];

        return view('dashboard/customers/index', $data);
    }

    /**
     * Giao diện tiếp nhận khách hàng mới (Wizard Form).
     * Hỗ trợ quy trình nhập liệu đa bước để đảm bảo tính đầy đủ của hồ sơ pháp lý.
     */
    public function create()
    {
        $data = [
            'title' => 'Tiếp nhận khách hàng mới | L.A.N ERP'
        ];

        return view('dashboard/customers/create', $data);
    }

    /**
     * API: Kiểm tra trùng lặp hồ sơ khách hàng.
     * Sử dụng trong quy trình Wizard để ngăn chặn việc tạo trùng SĐT hoặc CCCD đã tồn tại.
     * 
     * @return \CodeIgniter\HTTP\Response
     */
    public function checkDuplicate()
    {
        $data = $this->request->getGet();
        $duplicates = $this->customerService->findDuplicates($data);

        if (!empty($duplicates)) {
            return $this->response->setJSON([
                'exists' => true,
                'duplicates' => $duplicates // Trả về thông tin hồ sơ trùng để nhân viên đối soát
            ]);
        }

        return $this->response->setJSON(['exists' => false]);
    }

    /**
     * Hiển thị Hồ sơ khách hàng toàn diện (360-degree Profile View).
     * Tập hợp dữ liệu từ nhiều Module: Vụ việc, Tương tác, Tài chính và Tài liệu số hóa.
     * 
     * @param int|string $id ID của khách hàng.
     */
    public function show($id)
    {
        // 1. Xác thực sự tồn tại của khách hàng trong hệ thống
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            return redirect()->to(base_url('customers'))->with('error', 'Hồ sơ khách hàng không tồn tại hoặc đã được gỡ bỏ.');
        }

        // --- BẢO MẬT: KIỂM TRA QUYỀN TRUY CẬP TRỰC TIẾP (IDOR Protection) ---
        if (!has_permission('sys.admin') && !has_permission('case.manage')) {
            $myEmpId = session()->get('employee_id');
            $db = \Config\Database::connect();
            $hasAccess = $db->table('cases')
                ->groupStart()
                    ->where('customer_id', $id)
                    ->groupStart()
                        ->where('assigned_lawyer_id', $myEmpId)
                        ->orWhere('assigned_staff_id', $myEmpId)
                    ->groupEnd()
                ->groupEnd()
                ->orGroupStart()
                    ->where('cases.customer_id', $id)
                    ->join('case_members', 'case_members.case_id = cases.id')
                    ->where('case_members.employee_id', $myEmpId)
                ->groupEnd()
                ->countAllResults() > 0;

            if (!$hasAccess) {
                return redirect()->to(base_url('customers'))->with('error', 'Bạn không có quyền truy cập hồ sơ khách hàng này.');
            }
        }

        // 2. BẢO MẬT & TRUY VẾT (Compliance Logging):
        // Nhật ký hệ thống sẽ ghi nhận ai đã xem hồ sơ nhạy cảm này để phục vụ Audit sau này.
        $logService = new \App\Services\SystemLogService();
        $logService->log('DATA_ACCESS', 'Customers', $id, [
            'action' => 'VIEW_FULL_PROFILE',
            'sensitive_fields' => ['identity_number', 'phone', 'address']
        ]);

        // 3. Kết nối dữ liệu đa tầng từ các Model liên quan
        $caseModel = new CaseModel();                       // Quản lý vụ việc/hồ sơ pháp lý
        $interactionModel = new CustomerInteractionModel(); // Quản lý nhật ký liên lạc
        $paymentModel = new CustomerPaymentModel();         // Quản lý dòng tiền/thanh toán
        $documentModel = new \App\Models\DocumentModel(); // Sử dụng kho tài liệu DMS trung tâm

        // 4. Chuẩn bị dữ liệu hiển thị theo cấu trúc Tabbed UI
        $data = [
            'customer'     => $customer,
            'cases'        => $caseModel->where('customer_id', $id)->findAll(),
            'interactions' => $interactionModel->getByCustomer($id),
            'payments'     => $paymentModel->where('customer_id', $id)->findAll(),
            'documents'    => $documentModel->where('customer_id', $id)->findAll(),
            'title'        => 'Hồ sơ khách hàng: ' . $customer['name'] . ' | L.A.N ERP'
        ];

        return view('dashboard/customers/show', $data);
    }

    /**
     * Danh sách khách hàng "Tiềm năng bỏ ngỏ" (Stale Customers Tracking).
     * Phân tích các khách hàng quá 30 ngày không phát sinh tương tác để đưa vào phễu chăm sóc lại.
     */
    public function stale()
    {
        // Gọi Service lấy danh sách dựa trên thuật toán thời gian tương tác cuối (engagement score)
        $staleCustomers = $this->customerService->getStaleCustomers(30);
        
        $data = [
            'customers' => $staleCustomers,
            'title'     => 'Khách hàng cần chăm sóc lại | L.A.N ERP'
        ];

        return view('dashboard/customers/stale', $data);
    }

    /**
     * Xử lý tải lên và số hóa tài liệu khách hàng (Digital Asset Management).
     * Tích hợp với DMS tập trung, tự động lưu vào phân mục Hồ sơ khách hàng.
     */
    public function uploadDocument($id)
    {
        $file = $this->request->getFile('document');
        if (!$file) return redirect()->back()->with('error', 'Chưa chọn tệp tin.');
        
        // --- BẢO MẬT: KIỂM TRA QUYỀN (IDOR Protection) ---
        if (!has_permission('sys.admin') && !has_permission('case.manage')) {
             return redirect()->back()->with('error', 'Cảnh báo bảo mật: Bạn không được quyền tải tài liệu vào hồ sơ khách hàng.');
        }

        // 1. CHUẨN BỊ DỮ LIỆU ĐỒNG BỘ
        $data = [
            'document_category' => 'client_intake',
            'customer_id'       => $id,
            'file_name'         => $this->request->getPost('file_name'),
            'description'       => $this->request->getPost('description') ?: 'Hồ sơ số hóa khách hàng'
        ];

        // 2. SỬ DỤNG DỊCH VỤ DMS TRUNG TÂM
        $docService = new \App\Services\DocumentService();
        $result = $docService->upload($file, $data);

        if ($result['status'] === 'success') {
            return redirect()->back()->with('success', 'Hồ sơ tài liệu khách hàng đã được số hóa và đồng bộ vào kho DMS.');
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Nhập tài liệu từ kho DMS vào hồ sơ khách hàng.
     */
    public function importDocument($customerId)
    {
        $docId = $this->request->getPost('document_id');
        if (!$docId) return $this->response->setJSON(['status' => 'error', 'message' => 'Chưa chọn tài liệu.']);

        $docModel = new \App\Models\DocumentModel();
        $updated = $docModel->update($docId, [
            'customer_id' => $customerId
        ]);

        if ($updated) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Đã thêm tài liệu vào hồ sơ khách hàng.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Lỗi khi liên kết tài liệu.']);
    }

    /**
     * Lưu trữ thông tin khách hàng mới vào hệ thống.
     * Tự động hóa quy trình cấp mã khách hàng và chuẩn hóa dữ liệu đầu vào.
     */
    public function store()
    {
        $data = $this->request->getPost();
        
        // 1. QUY TẮC ĐỊNH DANH (Standard ID Coding):
        // Nếu không nhập mã thủ công, hệ thống tự động sinh theo mẫu: KH-YYYY-STT (VD: KH-2024-001)
        if (empty($data['code'])) {
            $count = $this->customerModel->countAllResults() + 1;
            $data['code'] = 'KH-' . date('Y') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        // 2. Lưu dữ liệu (Hệ thống Validation trong Model sẽ tự động kiểm tra định dạng Email/SĐT)
        if ($this->customerModel->save($data)) {
            return redirect()->to(base_url('customers'))->with('success', 'Hồ sơ khách hàng mới đã được thiết lập thành công.');
        }

        // 3. Trả về thông báo lỗi chi tiết nếu vi phạm các ràng buộc dữ liệu
        return redirect()->back()->withInput()->with('errors', $this->customerModel->errors());
    }

    /**
     * Ghi nhận Nhật ký tương tác khách hàng (Log Interaction).
     * Cập nhật chỉ số Engagement (Ngày liên lạc gần nhất) để phục vụ báo cáo CRM.
     * 
     * @param int|string $customerId ID khách hàng.
     */
    public function addInteraction($customerId)
    {
        $interactionModel = new CustomerInteractionModel();
        
        // 1. Thu thập thông tin tương tác (Call, Email, Meeting, Zalo,...)
        $data = $this->request->getPost();
        $data['customer_id'] = $customerId;
        $data['user_id'] = session()->get('user_id'); // Định danh nhân viên thực hiện tương tác
        $data['interaction_date'] = date('Y-m-d H:i:s');

        // 2. Ghi nhận vào cơ sở dữ liệu
        if ($interactionModel->save($data)) {
            // 3. ĐỒNG BỘ CHỈ SỐ (Heuristic Update):
            // Cập nhật 'last_contact_date' để hệ thống biết khách hàng này vẫn đang được chăm sóc tích cực.
            $this->customerModel->update($customerId, [
                'last_contact_date' => $data['interaction_date']
            ]);
            
            // 4. Tính toán lại các chỉ số tài chính/vụ việc liên quan thông qua Service
            $this->customerService->syncCustomerStats($customerId);

            return redirect()->back()->with('success', 'Đã ghi nhận nhật ký tương tác.');
        }

        return redirect()->back()->with('error', 'Không thể lưu nhật ký. Vui lòng kiểm tra lại nội dung nhập.');
    }
}
