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
    /**
     * Khai báo metadata cho hệ thống Tự động Đồng bộ (Auto-Sync Permissions).
     * Dùng cho cỗ máy quét tại: /perm-fix/sync
     */
    public static $modulePermissions = [
        'group' => 'Khách hàng',
        'permissions' => [
            'customer.view'     => 'Xem danh sách khách hàng (Phòng ban/Cá nhân)',
            'customer.manage'   => 'Tạo, sửa, xoá thông tin khách hàng cơ bản',
            'customer.view_all' => 'Đặc quyền: Xem TOÀN BỘ khách hàng hệ thống (Bypass isolation)',
            'customer.edit_all' => 'Đặc quyền: Chỉnh sửa TOÀN BỘ khách hàng hệ thống'
        ]
    ];

    /**
     * Khai báo danh mục thuộc thể loại Nhãn dán (Smart Tags).
     * Dùng cho cỗ máy quét tại: /perm-fix/sync
     */
    public static $taggable = [
        'type'  => 'customers',
        'label' => 'Khách hàng (CRM)'
    ];

    protected $customerModel;
    protected $customerService;
    protected $tagService;

    public function __construct()
    {
        // Khởi tạo model và service phục vụ cho controller CRM
        $this->customerModel = new CustomerModel();
        $this->customerService = new CustomerService();
        $this->tagService = new \App\Services\TagService();
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
        $tagId = $this->request->getGet('tag_id');     // Lọc theo tag
        
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

        // 4. Lọc theo Tag (Sử dụng bảng trung gian entity_tags)
        if ($tagId) {
            $query->whereIn('customers.id', function($builder) use ($tagId) {
                $builder->select('entity_id')->from('entity_tags')
                        ->where('entity_type', 'customers')
                        ->where('tag_id', $tagId);
            });
        }

        // --- BẢO MẬT: LỌC DỮ LIỆU DANH SÁCH (Data Isolation) ---
        // Nếu không có quyền quản lý toàn cục hoặc quyền xem TẤT CẢ khách hàng thì áp dụng lọc phạm vi
        if (!has_permission('sys.admin') && !has_permission('customer.manage') && !has_permission('customer.view_all')) {
            $myEmpId = session()->get('employee_id');
            $myDeptId = session()->get('department_id');
            $roleName = session()->get('role_name');
            $db = \Config\Database::connect();

            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
                // QUẢN LÝ (TEAM-BASED MANAGEMENT): Lấy ID các nhân viên báo cáo cho mình
                $myTeamIds = $db->table('employees')->where('manager_id', $myEmpId)->select('id')->get()->getResultArray();
                $myTeamIds = array_column($myTeamIds, 'id');
                $myTeamIds[] = $myEmpId; // Bao gồm sếp

                $query->whereIn('customers.id', function($builder) use ($myTeamIds, $myDeptId) {
                    $builder->select('customer_id')->from('cases')->groupStart();
                        // A. Khách hàng của TEAM (Sếp + Quân)
                        $builder->groupStart()
                            ->whereIn('assigned_lawyer_id', $myTeamIds)
                            ->orWhereIn('assigned_staff_id', $myTeamIds)
                            ->orWhereIn('id', function($sub) use ($myTeamIds) {
                                return $sub->select('case_id')->from('case_members')->whereIn('employee_id', $myTeamIds);
                            })
                        ->groupEnd();

                            // B. NGOẠI LỆ PHÁP LÝ: Thấy khách hàng của vụ việc mồ côi
                            if ($myDeptId == \Config\AppConstants::DEPT_PHAP_LY) {
                                $builder->orGroupStart()
                                    ->where('assigned_lawyer_id IS NULL')
                                    ->where('assigned_staff_id IS NULL')
                                ->groupEnd();
                            }
                        $builder->groupEnd();
                });
            } else {
                // NHÂN VIÊN bình thường: Phụ trách chính hoặc là thành viên
                $query->whereIn('customers.id', function($builder) use ($myEmpId) {
                    $builder->select('customer_id')->from('cases')
                        ->groupStart()
                            ->where('assigned_lawyer_id', $myEmpId)
                            ->orWhere('assigned_staff_id', $myEmpId)
                            ->orWhereIn('id', function($sub) use ($myEmpId) {
                                return $sub->select('case_id')->from('case_members')->where('employee_id', $myEmpId);
                            })
                        ->groupEnd();
                });
            }
        }

        // 4. Tổng hợp dữ liệu hiển thị (Aggregated Data Scoping)
        $myEmpId = session()->get('employee_id');
        $myDeptId = session()->get('department_id');
        $roleName = session()->get('role_name');
        
        $statsEmpId = null;
        $statsDeptId = null;
        $statsManagerId = null;

        // Chỉ lọc thống kê nếu không có quyền quản trị toàn cục hoặc xem tất cả (view_all)
        if (!has_permission('sys.admin') && !has_permission('customer.manage') && !has_permission('customer.view_all')) {
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
                $statsDeptId = $myDeptId;
                $statsManagerId = $myEmpId; // Filter theo TEAM
            } else {
                $statsEmpId = $myEmpId;
            }
        }

        $data = [
            'customers'     => $query->orderBy('created_at', 'DESC')->findAll(),
            'stats'         => $this->customerService->getDashboardStats($statsEmpId, $statsDeptId, $statsManagerId), 
            'availableTags' => get_available_tags('customers'), // Core Function
            'title'         => 'Quản lý khách hàng | L.A.N ERP'
        ];

        if ($this->request->isAJAX()) {
            return view('dashboard/customers/index_table', $data);
        }

        return view('dashboard/customers/index', $data);
    }

    /**
     * Giao diện tiếp nhận khách hàng mới (Wizard Form).
     * Hỗ trợ quy trình nhập liệu đa bước để đảm bảo tính đầy đủ của hồ sơ pháp lý.
     */
    public function create()
    {
        $data = [
            'availableTags' => get_available_tags('customers'), // Core Function
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
        $excludeId = $this->request->getGet('exclude_id');
        $duplicates = $this->customerService->findDuplicates($data, $excludeId);

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
        // Cho xem nếu là Admin, có quyền quản lý chung, hoặc có quyền Xem TẤT CẢ được sếp cấp riêng.
        if (!has_permission('sys.admin') && !has_permission('customer.manage') && !has_permission('customer.view_all')) {
            $myEmpId = session()->get('employee_id');
            $myDeptId = session()->get('department_id');
            $roleName = session()->get('role_name');
            $db = \Config\Database::connect();
            
            $isLegalManager = ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG && $myDeptId == \Config\AppConstants::DEPT_PHAP_LY);

            if (!$isLegalManager) {
                // XÂY DỰNG QUERY KIỂM TRA (Check if this customer has any case assigned to current user OR department)
                $checkQuery = $db->table('cases')->where('customer_id', $id);

                if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
                    // TRƯỞNG PHÒNG khác: Thấy KH nếu vụ việc có bất kỳ nhân sự nào thuộc phòng mình tham gia
                    $deptEmpIds = $db->table('employees')->where('department_id', $myDeptId)->select('id')->get()->getResultArray();
                    $deptEmpIds = array_column($deptEmpIds, 'id');
                    
                    if (!empty($deptEmpIds)) {
                        $checkQuery->groupStart()
                            ->whereIn('assigned_lawyer_id', $deptEmpIds)
                            ->orWhereIn('assigned_staff_id', $deptEmpIds)
                            ->orWhereIn('id', function($builder) use ($deptEmpIds) {
                                return $builder->select('case_id')->from('case_members')->whereIn('employee_id', $deptEmpIds);
                            })
                        ->groupEnd();
                    } else {
                        $checkQuery->where('1=0', null, false);
                    }
                } else {
                    // NHÂN VIÊN: Họ phải là member hoặc nhân sự chính
                    $checkQuery->groupStart()
                        ->where('assigned_lawyer_id', $myEmpId)
                        ->orWhere('assigned_staff_id', $myEmpId)
                        ->orWhereIn('cases.id', function($builder) use ($myEmpId) {
                            return $builder->select('case_id')->from('case_members')->where('employee_id', $myEmpId);
                        })
                        ->groupEnd();
                }

                if ($checkQuery->countAllResults() === 0) {
                    return redirect()->to(base_url('customers'))->with('error', 'Cảnh báo bảo mật: Bạn không có quyền truy cập hồ sơ khách hàng này.');
                }
            }
        }

        // 2. BẢO MẬT & TRUY VẾT (Compliance Logging):
        // Nhật ký hệ thống sẽ ghi nhận ai đã xem hồ sơ nhạy cảm này để phục vụ Audit sau này.
        $logService = new \App\Services\SystemLogService();
        $logService->log('DATA_ACCESS', 'Customers', $id, [
            'action' => 'VIEW_FULL_PROFILE',
            'sensitive_fields' => ['identity_number', 'phone', 'address']
        ]);

        // --- PHÂN QUYỀN CHỈNH SỬA (Edit Permission) ---
        // Bổ sung: Nếu được cấp quyền 'customer.view_all' (hoặc dùng customer.manage) thì xem/sửa được hết.
        $canEdit = false;
        if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.view_all')) {
            $canEdit = true;
        } else {
            $roleName = session()->get('role_name');
            $myEmpId = session()->get('employee_id');
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG || $customer['created_by'] == $myEmpId) {
                $canEdit = true;
            }
        }

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
            'tags'         => $this->tagService->getTagsByEntity($id, 'customers'),
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
        if (!has_permission('sys.admin') && !has_permission('customer.manage') && !has_permission('customer.edit_all')) {
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
            $count = $this->customerModel->withDeleted()->countAllResults() + 1;
            $data['code'] = 'KH-' . date('Y') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        // 2. Tiền xử lý TAG (Nếu là mảng từ POST, chuyển về CSV để lưu vào metadata khách hàng nếu cần)
        $tags = $this->request->getPost('tags');
        if (is_array($tags)) {
            $data['tags'] = implode(',', $tags);
        }

        // 3. Lưu dữ liệu
        $data['created_by'] = session()->get('employee_id');
        if ($this->customerModel->save($data)) {
            $customerId = $this->customerModel->getInsertID();

            // ĐỒNG BỘ NHÃN DÁN (Multi-modal relations)
            if (is_array($tags) && !empty($tags)) {
                $this->tagService->syncTags($customerId, 'customers', $tags);
            }

            return redirect()->to(base_url('customers'))->with('success', 'Hồ sơ khách hàng mới đã được thiết lập thành công.');
        }

        // 3. Trả về thông báo lỗi chi tiết nếu vi phạm các ràng buộc dữ liệu
        return redirect()->back()->withInput()->with('errors', $this->customerModel->errors());
    }

    /**
     * Giao diện chỉnh sửa hồ sơ khách hàng.
     */
    public function edit($id)
    {
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            return redirect()->to(base_url('customers'))->with('error', 'Hồ sơ không tồn tại.');
        }

        // --- BẢO MẬT TRUY CẬP: CHỈ NGƯỜI CÓ THẨM QUYỀN MỚI ĐƯỢC LOAD FORM ---
        $canEdit = false;
        if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
            $canEdit = true;
        } else {
            $roleName = session()->get('role_name');
            $myEmpId = session()->get('employee_id');
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG || $customer['created_by'] == $myEmpId) {
                $canEdit = true;
            }
        }

        if (!$canEdit) {
            return redirect()->to(base_url('customers/show/' . $id))->with('error', 'Khóa bảo mật: Bạn không có quyền chỉnh sửa hồ sơ khách hàng này. Chỉ Quản lý hoặc người trực tiếp tạo mới được phép đổi.');
        }

        $data = [
            'customer'      => $customer,
            'availableTags' => get_available_tags('customers'), // Core Function
            'selectedTags'  => array_column($this->tagService->getTagsByEntity($id, 'customers'), 'id'),
            'title'         => 'Chỉnh sửa hồ sơ: ' . $customer['name'] . ' | L.A.N ERP'
        ];

        return view('dashboard/customers/edit', $data);
    }

    /**
     * Cập nhật thông tin khách hàng.
     */
    public function update($id)
    {
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            return redirect()->to(base_url('customers'))->with('error', 'Hồ sơ không tồn tại.');
        }

        // --- BẢO MẬT API: CHẶN CẬP NHẬT TRÁI PHÉP ---
        $canEdit = false;
        if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
            $canEdit = true;
        } else {
            $roleName = session()->get('role_name');
            $myEmpId = session()->get('employee_id');
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG || $customer['created_by'] == $myEmpId) {
                $canEdit = true;
            }
        }

        if (!$canEdit) {
            return redirect()->to(base_url('customers/show/' . $id))->with('error', 'Thao tác bị từ chối do vi phạm quy chế bảo mật phân quyền.');
        }

        $data = $this->request->getPost();
        
        // Tiền xử lý TAGS cho bảng customers (metadata)
        $tags = $this->request->getPost('tags');
        if (is_array($tags)) {
            $data['tags'] = implode(',', $tags);
        }

        if ($this->customerModel->update($id, $data)) {
            // ĐỒNG BỘ NHÃN DÁN (Bridge Table relations)
            if (is_array($tags)) {
                $this->tagService->syncTags($id, 'customers', $tags);
            }

            return redirect()->to(base_url('customers/show/' . $id))->with('success', 'Hồ sơ khách hàng đã được cập nhật.');
        }

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

    /**
     * Xóa hồ sơ khách hàng (Soft Delete) với kiểm tra bảo toàn dữ liệu (Data Integrity).
     */
    public function delete($id)
    {
        // 1. Phân quyền: Cấp thao tác cao nhất
        if (!has_permission('sys.admin')) {
            return redirect()->back()->with('error', 'Cảnh báo bảo mật: Chỉ Quản trị viên hệ thống mới được phép xóa hồ sơ khách hàng.');
        }

        // 2. Bảo vệ Giao dịch (Integrity Check)
        // Tuyệt đối không cho phép xóa khách hàng đã và đang có Vụ việc pháp lý
        $caseModel = new \App\Models\CaseModel();
        $casesCount = $caseModel->where('customer_id', $id)->countAllResults();
        
        if ($casesCount > 0) {
             return redirect()->back()->with('error', "Vi phạm toàn vẹn dữ liệu: Khách hàng này đang sở hữu {$casesCount} vụ việc pháp lý. Yêu cầu xử lý các vụ việc trước khi gỡ khách hàng.");
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 3. Liên đới dữ liệu (Orphaned Data Management)
        // Xóa mềm nhật ký tương tác
        $interactionModel = new \App\Models\CustomerInteractionModel();
        $interactionModel->where('customer_id', $id)->delete();

        // Hủy liên kết (Unlink) tài liệu thay vì xóa vật lý tài liệu
        $docModel = new \App\Models\DocumentModel();
        $docModel->where('customer_id', $id)->set(['customer_id' => null])->update();

        // 4. Thực thi Xóa Khách Hàng
        $this->customerModel->delete($id);

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Phát sinh lỗi toàn vẹn CSDL. Đã hủy bỏ thao tác xóa.');
        }

        // Ghi Log truy vết
        $logService = new \App\Services\SystemLogService();
        $logService->log('DELETE', 'Customers', $id, ['action' => 'HARD_REVOKE_CUSTOMER']);

        return redirect()->to(base_url('customers'))->with('success', 'Đã gỡ bỏ an toàn kho lưu trữ số của khách hàng này.');
    }
}
