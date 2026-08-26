<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\CustomerInteractionModel;
use App\Models\CustomerDocumentModel;
use App\Models\CustomerPaymentModel;
use App\Models\CaseModel;
use App\Models\PartnerModel;
use App\Services\CustomerService;
use App\Services\CustomerRelationshipService;
use App\Services\CustomerMonitoringStatusService;

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
    protected $customerRelationshipService;
    protected $customerMonitoringStatusService;
    protected $tagService;

    public function __construct()
    {
        // Khởi tạo model và service phục vụ cho controller CRM
        $this->customerModel = new CustomerModel();
        $this->customerService = new CustomerService();
        $this->customerRelationshipService = new CustomerRelationshipService();
        $this->customerMonitoringStatusService = new CustomerMonitoringStatusService();
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
        $month = $this->request->getGet('month');      // Lọc theo tháng tạo khách hàng
        $year = $this->request->getGet('year');        // Lọc theo năm tạo khách hàng
        $careStaffId = $this->request->getGet('care_staff_id'); // Lọc theo nhân sự tư vấn
        $careStatus = $this->request->getGet('care_status');
        $monitoringStatus = $this->request->getGet('monitoring_status');   // Lọc theo trạng thái giám sát
        
        // Sửa đổi: Đếm số vụ việc và lấy thông tin SLA đang hoạt động bằng các subquery động hiệu năng cao
        $query = $this->customerModel->select('customers.*, employees.full_name as care_staff_name, 
            (SELECT COUNT(*) FROM cases WHERE cases.customer_id = customers.id AND cases.deleted_at IS NULL) as total_cases,
            (SELECT COUNT(*) FROM documents WHERE documents.customer_id = customers.id AND documents.deleted_at IS NULL) as profile_document_count,
            (SELECT due_time FROM customer_sla_history WHERE customer_sla_history.customer_id = customers.id AND customer_sla_history.end_time IS NULL AND customer_sla_history.deleted_at IS NULL LIMIT 1) as active_sla_due_time,
            (SELECT sla_status FROM customer_sla_history WHERE customer_sla_history.customer_id = customers.id AND customer_sla_history.end_time IS NULL AND customer_sla_history.deleted_at IS NULL LIMIT 1) as active_sla_status,
            (SELECT start_time FROM customer_sla_history WHERE customer_sla_history.customer_id = customers.id AND customer_sla_history.end_time IS NULL AND customer_sla_history.deleted_at IS NULL LIMIT 1) as active_sla_start_time')
                                     ->join('employees', 'employees.id = customers.assigned_care_staff_id AND employees.deleted_at IS NULL', 'left');

        // 2. LOGIC TÌM KIẾM ĐA LUỒNG (Multi-field Search):
        // Cho phép tìm kiếm bằng Tên, Số điện thoại, Số CCCD/Hộ chiếu hoặc Mã khách hàng nội bộ.
        if ($search) {
            $query->groupStart()
                  ->like('customers.name', $search)
                  ->orLike('customers.phone', $search)
                  ->orLike('customers.identity_number', $search)
                  ->orLike('customers.code', $search)
                  ->groupEnd();
        }

        // 3. Phân loại đối tượng khách hàng
        if ($type) {
            $query->where('customers.type', $type);
        }

        // 4. Lọc theo Tag (Sử dụng bảng trung gian entity_tags)
        if ($tagId) {
            $query->whereIn('customers.id', function($builder) use ($tagId) {
                $builder->select('entity_id')->from('entity_tags')
                        ->where('entity_type', 'customers')
                        ->where('tag_id', $tagId);
            });
        }

        // 5. Lọc theo thời gian tạo (Tháng / Năm) để hỗ trợ bộ lọc nâng cao
        if ($month) {
            $query->where('MONTH(customers.created_at)', $month);
        }
        if ($year) {
            $query->where('YEAR(customers.created_at)', $year);
        }

        // 6. Lọc theo nhân sự tư vấn phụ trách chăm sóc
        if ($careStaffId) {
            $query->where('customers.assigned_care_staff_id', $careStaffId);
        }

        // Lọc theo trạng thái tư vấn (SLA)
        if ($careStatus) {
            $query->where('customers.care_status', $careStatus);
        }

        if ($monitoringStatus && preg_match('/^[A-Za-z0-9_-]{1,80}$/', (string) $monitoringStatus)) {
            $query->groupStart()
                ->where('customers.monitoring_status', $monitoringStatus)
                ->orLike('customers.monitoring_status', '"' . $monitoringStatus . '"')
                ->groupEnd();
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

                $query->groupStart()
                    ->whereIn('customers.assigned_care_staff_id', $myTeamIds)
                    ->orWhereIn('customers.created_by', $myTeamIds)
                    ->orWhereIn('customers.id', function($builder) use ($myTeamIds, $myDeptId) {
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
                    })
                ->groupEnd();
            } else {
                // NHÂN VIÊN bình thường: Phụ trách chính, thành viên, người tạo hoặc nhân viên tư vấn/chăm sóc
                $query->groupStart()
                    ->where('customers.assigned_care_staff_id', $myEmpId)
                    ->orWhere('customers.created_by', $myEmpId)
                    ->orWhereIn('customers.id', function($builder) use ($myEmpId) {
                        $builder->select('customer_id')->from('cases')
                            ->groupStart()
                                ->where('assigned_lawyer_id', $myEmpId)
                                ->orWhere('assigned_staff_id', $myEmpId)
                                ->orWhereIn('id', function($sub) use ($myEmpId) {
                                    return $sub->select('case_id')->from('case_members')->where('employee_id', $myEmpId);
                                })
                            ->groupEnd();
                    })
                ->groupEnd();
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

        // Sắp xếp động
        $sort = $this->request->getGet('sort') ?: 'created_at';
        $order = $this->request->getGet('order') ?: 'desc';

        $allowedSorts = ['code', 'name', 'care_staff_name', 'care_status', 'monitoring_status', 'total_cases', 'created_at'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';

        if ($sort === 'care_staff_name') {
            $query->orderBy('employees.full_name', $order);
        } elseif ($sort === 'total_cases') {
            $query->orderBy('total_cases', $order);
        } else {
            $query->orderBy('customers.' . $sort, $order);
        }

        $customers = $query->paginate(15);
        if (!empty($customers)) {
            $customerIds = array_column($customers, 'id');
            $db = \Config\Database::connect();
            
            // Tải các tài khoản Zalo liên kết
            $zaloFollowers = $db->table('zalo_followers')
                ->whereIn('customer_id', $customerIds)
                ->where('deleted_at IS NULL')
                ->get()
                ->getResultArray();
                
            // Tải các tài khoản FB Messenger liên kết
            $messengerContacts = $db->table('messenger_contacts')
                ->whereIn('customer_id', $customerIds)
                ->where('deleted_at IS NULL')
                ->get()
                ->getResultArray();
                
            // Phân loại theo customer_id
            $zaloByCustomer = [];
            foreach ($zaloFollowers as $zf) {
                $zaloByCustomer[$zf['customer_id']][] = $zf;
            }
            
            $messengerByCustomer = [];
            foreach ($messengerContacts as $mc) {
                $messengerByCustomer[$mc['customer_id']][] = $mc;
            }
            
            // Đồng bộ dữ liệu vào từng khách hàng
            foreach ($customers as &$c) {
                $c['zalo_channels'] = $zaloByCustomer[$c['id']] ?? [];
                $c['messenger_channels'] = $messengerByCustomer[$c['id']] ?? [];
            }
        }

        // Tải cấu hình danh mục SLA động cho bộ chọn nhanh tại danh sách KH
        $slaSettingModel = new \App\Models\CustomerSlaSettingModel();
        $slaSettings = $slaSettingModel->where('deleted_at', null)->orderBy('sort_order', 'ASC')->findAll();

        $monitoringSettings = $this->customerMonitoringStatusService->getSettings(true);

        // Tải danh sách mẫu tin ZNS đang hoạt động để phục vụ gửi nhanh (Zalo ZNS Bulk)
        $znsTemplateModel = new \App\Models\ZnsTemplateModel();
        $znsTemplates = [];
        try {
            $znsTemplates = $znsTemplateModel->getActiveTemplates();
        } catch (\Exception $e) {
            log_message('error', 'CustomerController::index - getActiveTemplates error: ' . $e->getMessage());
        }

        $canEdit = has_permission('sys.admin')
            || has_permission('customer.manage')
            || has_permission('customer.edit_all');

        $data = [
            'customers'     => $customers,
            'pager'         => $this->customerModel->pager,
            'stats'         => $this->customerService->getDashboardStats($statsEmpId, $statsDeptId, $statsManagerId), 
            'availableTags' => $this->tagService->getAvailableTags('customers', has_permission('sys.admin') ? -1 : null),
            'employees'     => get_available_employees(),
            'slaSettings'   => $slaSettings,
            'monitoringSettings' => $monitoringSettings,
            'canEdit'        => $canEdit,
            'znsTemplates'  => $znsTemplates,
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
        $partnerModel = new PartnerModel();
        $data = [
            'availableTags' => $this->tagService->getAvailableTags('customers', has_permission('sys.admin') ? -1 : null),
            'employees'     => get_available_employees(),
            'partners'      => $partnerModel->where('status', 'active')->orderBy('name', 'ASC')->findAll(300),
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
            
            // Lấy ID các nhân viên báo cáo cho mình nếu là Trưởng phòng
            $myTeamIds = [$myEmpId];
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
                $team = $db->table('employees')->where('manager_id', $myEmpId)->select('id')->get()->getResultArray();
                $myTeamIds = array_merge($myTeamIds, array_column($team, 'id'));
            }

            // Kiểm tra xem user (hoặc thành viên trong đội) có là Người tạo hoặc Người phụ trách chăm sóc trực tiếp không
            $isDirectOwner = in_array($customer['created_by'], $myTeamIds) || in_array($customer['assigned_care_staff_id'], $myTeamIds);
            
            $isLegalManager = ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG && $myDeptId == \Config\AppConstants::DEPT_PHAP_LY);

            if (!$isDirectOwner && !$isLegalManager) {
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
                    // NHÂN VIÊN: Họ phải là member hoặc nhân sự chính trong vụ việc của KH
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
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG || $customer['created_by'] == $myEmpId || $customer['assigned_care_staff_id'] == $myEmpId) {
                $canEdit = true;
            }
        }

        // 3. Kết nối dữ liệu đa tầng từ các Model liên quan
        $caseModel = new CaseModel();                       // Quản lý vụ việc/hồ sơ pháp lý
        $interactionModel = new CustomerInteractionModel(); // Quản lý nhật ký liên lạc
        $paymentModel = new CustomerPaymentModel();         // Quản lý dòng tiền/thanh toán
        $documentModel = new \App\Models\DocumentModel(); // Sử dụng kho tài liệu DMS trung tâm

        // 4. Chuẩn bị dữ liệu hiển thị theo cấu trúc Tabbed UI
        $careStaffName = null;
        $db = \Config\Database::connect();
        if (!empty($customer['assigned_care_staff_id'])) {
            $emp = $db->table('employees')->where('id', $customer['assigned_care_staff_id'])->where('deleted_at IS NULL')->select('full_name')->get()->getRow();
            if ($emp) {
                $careStaffName = $emp->full_name;
            }
        }

        // Truy xuất Lịch sử nhắn tin chat (Zalo OA + Facebook Messenger) hợp nhất của khách hàng
        $chatHistory = [];

        // 1. Lấy tin nhắn Zalo
        $zaloFollowers = $db->table('zalo_followers')
            ->where('customer_id', $id)
            ->where('deleted_at IS NULL')
            ->get()
            ->getResultArray();

        if (!empty($zaloFollowers)) {
            $followerIds = array_column($zaloFollowers, 'id');
            $zaloMessages = $db->table('zalo_messages')
                ->whereIn('follower_id', $followerIds)
                ->orderBy('created_at', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($zaloMessages as $msg) {
                $chatHistory[] = [
                    'id'           => $msg['id'],
                    'channel'      => 'zalo',
                    'message_text' => $msg['message_text'],
                    'is_staff'     => ($msg['sender_type'] === 'oa'),
                    'staff_name'   => ($msg['sender_type'] === 'oa') ? 'Zalo OA' : null,
                    'created_at'   => $msg['created_at']
                ];
            }
        }

        // 2. Lấy tin nhắn Messenger
        $messengerContacts = $db->table('messenger_contacts')
            ->where('customer_id', $id)
            ->where('deleted_at IS NULL')
            ->get()
            ->getResultArray();

        if (!empty($messengerContacts)) {
            $contactIds = array_column($messengerContacts, 'id');
            $messengerMessages = $db->table('messenger_messages')
                ->whereIn('contact_id', $contactIds)
                ->where('deleted_at IS NULL')
                ->orderBy('created_at', 'ASC')
                ->get()
                ->getResultArray();

            // Lấy danh sách nhân viên để hiển thị tên người trả lời Messenger
            $employees = [];
            $empList = $db->table('employees')
                ->select('user_id, full_name')
                ->where('deleted_at IS NULL')
                ->get()
                ->getResultArray();
            foreach ($empList as $emp) {
                if (!empty($emp['user_id'])) {
                    $employees[$emp['user_id']] = $emp['full_name'];
                }
            }

            foreach ($messengerMessages as $msg) {
                $isStaff = ($msg['sender_type'] === 'page');
                $staffName = 'Facebook Page';
                if ($isStaff && !empty($msg['mid_staff_id']) && isset($employees[$msg['mid_staff_id']])) {
                    $staffName = $employees[$msg['mid_staff_id']];
                }

                $chatHistory[] = [
                    'id'           => $msg['id'],
                    'channel'      => 'messenger',
                    'message_text' => $msg['message_text'],
                    'is_staff'     => $isStaff,
                    'staff_name'   => $isStaff ? $staffName : null,
                    'created_at'   => $msg['created_at']
                ];
            }
        }

        // Sắp xếp lịch sử nhắn tin theo trình tự thời gian tăng dần
        if (!empty($chatHistory)) {
            usort($chatHistory, function ($a, $b) {
                return strtotime($a['created_at']) <=> strtotime($b['created_at']);
            });
        }

        $carePlanModel = new \App\Models\CustomerCarePlanModel();
        $careTaskModel = new \App\Models\CustomerCareTaskModel();
        $loyaltyModel  = new \App\Models\CustomerLoyaltyModel();

        $carePlans = $carePlanModel->getByCustomer($id);
        $careTasks = $careTaskModel->getByCustomer($id);
        $loyalty   = $loyaltyModel->getByCustomer($id);
        $relationshipProfile = $this->customerRelationshipService->getProfile((int) $id);
        $opportunities = $this->customerRelationshipService->getOpportunities((int) $id);
        $activeEmployees = $db->table('employees')
            ->select('employees.id, employees.full_name')
            ->join('users', 'users.id = employees.user_id', 'inner')
            ->where('employees.deleted_at', null)
            ->where('users.deleted_at', null)
            ->where('users.active_status', 1)
            ->orderBy('full_name', 'ASC')
            ->get()
            ->getResultArray();
        $referralCustomers = $this->customerModel->select('id, code, name')
            ->where('id !=', $id)
            ->orderBy('name', 'ASC')
            ->limit(100)
            ->findAll();

        // Tải dữ liệu tiến trình SLA động cho giao diện
        $slaSettingModel = new \App\Models\CustomerSlaSettingModel();
        $slaHistoryModel = new \App\Models\CustomerSlaHistoryModel();
        
        $activeSla   = $slaHistoryModel->getActiveSla($id);
        $slaHistory  = $slaHistoryModel->where('customer_id', $id)
                                       ->where('deleted_at', null)
                                       ->orderBy('start_time', 'DESC')
                                       ->findAll();
        $slaSettings = $slaSettingModel->where('is_active', 1)
                                       ->where('deleted_at', null)
                                       ->orderBy('sort_order', 'ASC')
                                       ->findAll();

        $data = [
            'customer'      => $customer,
            'cases'         => $caseModel->where('customer_id', $id)->findAll(),
            'interactions'  => $interactionModel->getByCustomer($id),
            'payments'      => $paymentModel->where('customer_id', $id)->findAll(),
            'documents'     => $documentModel->searchDocuments([
                'customer_id' => $id,
                'sort'        => 'created_at',
                'order'       => 'DESC',
            ]),
            'tags'          => $this->tagService->getTagsByEntity($id, 'customers'),
            'careStaffName' => $careStaffName,
            'chatHistory'   => $chatHistory,
            'carePlans'     => $carePlans,
            'careTasks'     => $careTasks,
            'loyalty'       => $loyalty,
            'relationshipProfile' => $relationshipProfile,
            'opportunities'  => $opportunities,
            'employees'      => $activeEmployees,
            'referralCustomers' => $referralCustomers,
            'activeSla'     => $activeSla,
            'slaHistory'    => $slaHistory,
            'slaSettings'   => $slaSettings,
            'title'         => 'Hồ sơ khách hàng: ' . $customer['name'] . ' | L.A.N ERP'
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
        $files = $this->request->getFileMultiple('document');
        if (empty($files)) {
            $singleFile = $this->request->getFile('document');
            $files = $singleFile ? [$singleFile] : [];
        }

        $files = array_values(array_filter($files, function ($file) {
            return $file && $file->getClientName() !== '';
        }));

        if (empty($files)) {
            return redirect()->back()->withInput()->with('error', 'Vui lòng chọn ít nhất một tệp tin để tải lên.');
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
        $result = count($files) > 1
            ? $docService->uploadMultiple($files, $data)
            : $docService->upload($files[0], $data);

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
        // --- BẢO MẬT: KIỂM TRA QUYỀN TRUY CẬP (IDOR & Team Access Protection) ---
        $canImport = false;
        if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
            $canImport = true;
        } else {
            $myEmpId = session()->get('employee_id');
            $db = \Config\Database::connect();
            $hasCase = $db->table('cases')->where('customer_id', $customerId)
                          ->groupStart()
                            ->where('assigned_lawyer_id', $myEmpId)
                            ->orWhere('assigned_staff_id', $myEmpId)
                            ->orWhereIn('id', function($builder) use ($myEmpId) {
                                return $builder->select('case_id')->from('case_members')->where('employee_id', $myEmpId);
                            })
                          ->groupEnd()
                          ->countAllResults();
            if ($hasCase > 0) $canImport = true;
        }

        if (!$canImport) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không được quyền thực hiện thao tác này trên hồ sơ khách hàng này.']);
        }

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

        if (array_key_exists('date_of_birth', $data) && $data['date_of_birth'] === '') {
            $data['date_of_birth'] = null;
        }
        
        // --- BẢO MẬT API: CHẶN CẬP NHẬT TRÁI PHÉP ---
        $isAdminOrManager = false;
        if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
            $isAdminOrManager = true;
        } else {
            $roleName = session()->get('role_name');
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
                $isAdminOrManager = true;
            }
        }

        if (!$isAdminOrManager) {
            unset($data['assigned_care_staff_id']);
            unset($data['care_status']);
            unset($data['referred_partner_id']);
        } elseif (array_key_exists('assigned_care_staff_id', $data) && !$this->normalizeActiveCareStaffInput($data)) {
            return redirect()->back()->withInput()->with('error', 'Nhan vien tu van duoc chon khong hop le, da nghi hoac tai khoan dang bi khoa.');
        }

        if (array_key_exists('referred_partner_id', $data)) {
            $data['referred_partner_id'] = $data['referred_partner_id'] !== '' ? (int)$data['referred_partner_id'] : null;
        }

        if (array_key_exists('care_status', $data)) {
            $data['care_status'] = \App\Services\CustomerSlaService::normalizeStatusKey($data['care_status']);
        }
        
        // 1. QUY TẮC ĐỊNH DANH (Robust Auto-Coding):
        if (empty($data['code'])) {
            $year = date('Y');
            $latest = $this->customerModel->where('code LIKE', "KH-$year-%")
                                         ->orderBy('code', 'DESC')
                                         ->first();
            $num = 1;
            if ($latest) {
                // Parse KH-2024-001 -> 1
                $parts = explode('-', $latest['code']);
                $num = (int)end($parts) + 1;
            }
            $data['code'] = 'LAN-' . $data['phone'] ;
        }

        // 2. Tiền xử lý TAG
        $tags = $this->request->getPost('tags');
        if (is_array($tags)) {
            $data['tags'] = implode(',', $tags);
        }

        // 3. Thiết lập thông tin người tạo
        $data['created_by'] = session()->get('employee_id');

        // 4. Thực thi lưu
        try {
            if ($this->customerModel->save($data)) {
                $customerId = $this->customerModel->getInsertID();

                if (is_array($tags) && !empty($tags)) {
                    $this->tagService->syncTags($customerId, 'customers', $tags);
                }

                // Đồng bộ hóa liên hệ chat (Zalo/Messenger) theo SĐT
                $phone = $data['phone'] ?? '';
                $phoneSecondary = $data['phone_secondary'] ?? null;
                $this->customerService->syncChatContactsByPhone($customerId, $phone, $phoneSecondary);

                // Tự động phân nhóm khách hàng dựa trên dữ liệu nhập
                $this->customerService->autoSegmentCustomer($customerId);

                // Khởi tạo quy trình SLA động bắt đầu bằng trạng thái mặc định "Chưa tư vấn"
                $slaService = new \App\Services\CustomerSlaService();
                $slaService->transitionStatus($customerId, 'chua_tu_van');

                // Khởi tạo kế hoạch CSKH tự động nếu có ngày hoàn thành dịch vụ
                if (!empty($data['service_completed_date'])) {
                    $careService = new \App\Services\CustomerCareService();
                    $careService->initializeCarePlan($customerId, 'phase1');
                }

                return redirect()->to(base_url('customers'))->with('success', 'Hồ sơ khách hàng mới đã được thiết lập thành công.');
            } else {
                // Lỗi validation từ Model
                return redirect()->back()->withInput()->with('errors', $this->customerModel->errors());
            }
        } catch (\Exception $e) {
            // Lỗi Database hoặc Logic nghiêm trọng
            log_message('error', '[CustomerStore] Error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Lỗi hệ thống khi lưu: ' . $e->getMessage());
        }
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
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG || $customer['created_by'] == $myEmpId || $customer['assigned_care_staff_id'] == $myEmpId) {
                $canEdit = true;
            }
        }

        if (!$canEdit) {
            return redirect()->to(base_url('customers/show/' . $id))->with('error', 'Khóa bảo mật: Bạn không có quyền chỉnh sửa hồ sơ khách hàng này. Chỉ Quản lý hoặc người trực tiếp tạo mới được phép đổi.');
        }

        $data = [
            'customer'      => $customer,
            'availableTags' => $this->tagService->getAvailableTags('customers', has_permission('sys.admin') ? -1 : null),
            'selectedTags'  => array_column($this->tagService->getTagsByEntity($id, 'customers'), 'id'),
            'employees'     => get_available_employees(),
            'partners'      => (new PartnerModel())->where('status', 'active')->orderBy('name', 'ASC')->findAll(300),
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

        // --- BẢO MẬT API: CHẶN CẬP NHẬT TRÁP PHÉP ---
        $canEdit = false;
        if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
            $canEdit = true;
        } else {
            $roleName = session()->get('role_name');
            $myEmpId = session()->get('employee_id');
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG || $customer['created_by'] == $myEmpId || $customer['assigned_care_staff_id'] == $myEmpId) {
                $canEdit = true;
            }
        }

        if (!$canEdit) {
            return redirect()->to(base_url('customers/show/' . $id))->with('error', 'Thao tác bị từ chối do vi phạm quy chế bảo mật phân quyền.');
        }

        $data = $this->request->getPost();

        if (array_key_exists('date_of_birth', $data) && $data['date_of_birth'] === '') {
            $data['date_of_birth'] = null;
        }
        
        // --- BẢO MẬT API: CHẶN CẬP NHẬT TRÁI PHÉP ---
        $isAdminOrManager = false;
        if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
            $isAdminOrManager = true;
        } else {
            $roleName = session()->get('role_name');
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
                $isAdminOrManager = true;
            }
        }

        $myEmpId = session()->get('employee_id');
        $isCaretaker = (!empty($customer['assigned_care_staff_id']) && $customer['assigned_care_staff_id'] == $myEmpId);

        if (!$isAdminOrManager) {
            unset($data['assigned_care_staff_id']);
            unset($data['referred_partner_id']);
            if (!$isCaretaker) {
                unset($data['care_status']);
                unset($data['has_received_gift']);
            }
        } elseif (array_key_exists('assigned_care_staff_id', $data) && !$this->normalizeActiveCareStaffInput($data)) {
            return redirect()->back()->withInput()->with('error', 'Nhan vien tu van duoc chon khong hop le, da nghi hoac tai khoan dang bi khoa.');
        }

        if (array_key_exists('referred_partner_id', $data)) {
            $data['referred_partner_id'] = $data['referred_partner_id'] !== '' ? (int)$data['referred_partner_id'] : null;
        }

        if (array_key_exists('care_status', $data)) {
            $data['care_status'] = \App\Services\CustomerSlaService::normalizeStatusKey($data['care_status']);
        }
        
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

            // Đồng bộ hóa liên hệ chat (Zalo/Messenger) theo SĐT
            $phone = $data['phone'] ?? '';
            $phoneSecondary = $data['phone_secondary'] ?? null;
            $this->customerService->syncChatContactsByPhone($id, $phone, $phoneSecondary);

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
        $data['requires_follow_up'] = !empty($data['requires_follow_up']) ? 1 : 0;
        $data['importance_level'] = $data['importance_level'] ?? 'normal';

        // 2. Ghi nhận vào cơ sở dữ liệu
        if ($interactionModel->save($data)) {
            // 3. ĐỒNG BỘ CHỈ SỐ (Heuristic Update):
            // Cập nhật 'last_contact_date' để hệ thống biết khách hàng này vẫn đang được chăm sóc tích cực.
            $this->customerRelationshipService->syncAfterInteraction((int) $customerId, $data['next_follow_up'] ?? null);
            
            // 4. Tính toán lại các chỉ số tài chính/vụ việc liên quan thông qua Service
            $this->customerService->syncCustomerStats($customerId);

            return redirect()->back()->with('success', 'Đã ghi nhận nhật ký tương tác.');
        }

        return redirect()->back()->with('error', 'Không thể lưu nhật ký. Vui lòng kiểm tra lại nội dung nhập.');
    }

    /**
     * Xóa hồ sơ khách hàng (Soft Delete) với kiểm tra bảo toàn dữ liệu (Data Integrity).
     */
    public function updateRelationship($customerId)
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return redirect()->to(base_url('customers'))->with('error', 'Ho so khach hang khong ton tai.');
        }

        if (!$this->canEditCustomerRecord($customer)) {
            return redirect()->back()->with('error', 'Ban khong co quyen cap nhat ho so quan he cua khach hang nay.');
        }

        if ($this->customerRelationshipService->updateProfile((int) $customerId, $this->request->getPost())) {
            return redirect()->to(base_url('customers/show/' . $customerId))->with('success', 'Da cap nhat ho so quan he khach hang.');
        }

        return redirect()->back()->withInput()->with('error', 'Khong the cap nhat ho so quan he.');
    }

    public function storeOpportunity($customerId)
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return redirect()->to(base_url('customers'))->with('error', 'Ho so khach hang khong ton tai.');
        }

        if (!$this->canEditCustomerRecord($customer)) {
            return redirect()->back()->with('error', 'Ban khong co quyen tao co hoi cho khach hang nay.');
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'issue_title' => 'required|min_length[3]|max_length[255]',
            'probability' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
        ]);

        if (!$validation->run($this->request->getPost())) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        if ($this->customerRelationshipService->createOpportunity((int) $customerId, $this->request->getPost())) {
            return redirect()->to(base_url('customers/show/' . $customerId))->with('success', 'Da ghi nhan co hoi phat trien dich vu.');
        }

        return redirect()->back()->withInput()->with('error', 'Khong the tao co hoi phat trien dich vu.');
    }

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

    /**
     * Xóa chọn khách hàng (Bulk Action) - Chỉ Admin.
     */
    public function bulkDelete()
    {
        if (!has_permission('sys.admin')) {
             return $this->response->setJSON(['status' => 'error', 'message' => 'Chỉ Quản trị viên mới được thực hiện thao tác này.']);
        }

        $ids = $this->request->getPost('ids');
        if (empty($ids) || !is_array($ids)) {
             return $this->response->setJSON(['status' => 'error', 'message' => 'Danh sách chọn trống.']);
        }

        $caseModel = new \App\Models\CaseModel();
        $success = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            // Kiểm tra toàn vẹn: Không xóa khách hàng đang có vụ việc
            $casesCount = $caseModel->where('customer_id', $id)->countAllResults();
            if ($casesCount > 0) {
                $skipped++;
                continue;
            }

            // Xử lý liên đới (Dọn dẹp tương tác và gỡ liên kết tài liệu)
            $db = \Config\Database::connect();
            $db->table('customer_interactions')->where('customer_id', $id)->delete();
            $db->table('documents')->where('customer_id', $id)->update(['customer_id' => null]);

            if ($this->customerModel->delete($id)) {
                $success++;
            }
        }

        $msg = "Đã dọn dẹp thành công {$success} hồ sơ khách hàng.";
        if ($skipped > 0) {
            $msg .= " Bỏ qua {$skipped} mục do đang có vụ việc liên quan (Toàn vẹn dữ liệu).";
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => $msg
        ]);
    }

    /**
     * API: Cập nhật nhân sự tư vấn qua AJAX trực tiếp từ danh sách (Inline Editing).
     * Đảm bảo tính toàn vẹn dữ liệu và kiểm tra phân quyền chặt chẽ trước khi cập nhật.
     */
    public function updateCareStaff($id)
    {
        // 1. Kiểm tra sự tồn tại của hồ sơ khách hàng
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            return $this->response->setJSON([
                'code' => 1,
                'message' => 'Hồ sơ khách hàng không tồn tại trên hệ thống.'
            ]);
        }

        // 2. PHÂN QUYỀN BẢO MẬT (Access Control Verification):
        // Chỉ những người có quyền quản trị hoặc trưởng phòng mới được phép thay đổi.
        $canEdit = false;
        if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
            $canEdit = true;
        } else {
            $roleName = session()->get('role_name');
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
                $canEdit = true;
            }
        }

        if (!$canEdit) {
            return $this->response->setJSON([
                'code' => 1,
                'message' => 'Cảnh báo bảo mật: Bạn không có quyền chỉnh sửa nhân sự phụ trách chăm sóc khách hàng này.'
            ]);
        }

        // 3. Nhận và tiền xử lý dữ liệu đầu vào (Rule #6 - Nullify blank values)
        $assignedCareStaffId = $this->request->getPost('assigned_care_staff_id');
        if ($assignedCareStaffId === '') {
            $assignedCareStaffId = null;
        }

        // 4. Nếu có chọn nhân viên cụ thể, kiểm tra xem nhân viên đó có hợp lệ và hoạt động hay không
        $careStaffName = '';
        if ($assignedCareStaffId !== null) {
            $emp = $this->findActiveCareStaff((int)$assignedCareStaffId);
            if (!$emp) {
                return $this->response->setJSON([
                    'code' => 1,
                    'message' => 'Nhân viên được chọn không hợp lệ hoặc đã bị khóa khỏi hệ thống.'
                ]);
            }
            $careStaffName = $emp['full_name'];
        }

        // 5. Thực thi cập nhật cơ sở dữ liệu
        $updateData = [
            'assigned_care_staff_id' => $assignedCareStaffId,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($this->customerModel->update($id, $updateData)) {
            // Cập nhật assigned_staff_id trong SLA history đang hoạt động (active)
            $historyModel = new \App\Models\CustomerSlaHistoryModel();
            $activeSla = $historyModel->getActiveSla($id);
            if ($activeSla) {
                $historyModel->update($activeSla['id'], [
                    'assigned_staff_id' => $assignedCareStaffId,
                    // Nếu trước đây due_time là null (vì chưa gán nhân sự), thì nay gán nhân sự, tính due_time mới!
                    'due_time' => ($activeSla['due_time'] === null && $activeSla['sla_duration'] > 0 && $assignedCareStaffId !== null) 
                        ? date('Y-m-d H:i:s', strtotime("+{$activeSla['sla_duration']} hours")) 
                        : $activeSla['due_time'],
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else if ($assignedCareStaffId !== null) {
                // Khởi động tiến trình SLA động đầu tiên
                $slaService = new \App\Services\CustomerSlaService();
                $slaService->transitionStatus($id, $customer['care_status'] ?: 'chua_tu_van');
            }

            return $this->response->setJSON([
                'code' => 0,
                'message' => 'Đã cập nhật nhân sự phụ trách chăm sóc tư vấn thành công.',
                'care_staff_name' => $careStaffName
            ]);
        }

        return $this->response->setJSON([
            'code' => 1,
            'message' => 'Lỗi hệ thống: Không thể cập nhật thông tin trong cơ sở dữ liệu.'
        ]);
    }

    /**
     * API: Chuyển đổi trạng thái tư vấn & SLA nhanh qua AJAX.
     * Xác thực kỹ lưỡng phân quyền trước khi thực thi (Rule #7 - Chống Tampering).
     */
    /**
     * API: Cap nhat nhanh trang thai da tang qua/chua tang qua cua khach hang.
     * Endpoint nay chi nhan gia tri 0/1 va van kiem tra quyen tren tung ho so
     * de tranh nguoi dung sua ID tren front-end roi cap nhat trai phep.
     */
    public function updateGiftStatus($customerId)
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Hồ sơ khách hàng không tồn tại trên hệ thống.'
            ]);
        }

        $canEdit = false;
        if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
            $canEdit = true;
        } else {
            $roleName = session()->get('role_name');
            $myEmpId = session()->get('employee_id');
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG || (!empty($customer['assigned_care_staff_id']) && $customer['assigned_care_staff_id'] == $myEmpId)) {
                $canEdit = true;
            }
        }

        if (!$canEdit) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Ban khong co quyen cap nhat trang thai qua tang cua khach hang nay.'
            ]);
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'has_received_gift' => 'required|in_list[0,1]'
        ]);

        if (!$validation->run($this->request->getPost())) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Trang thai qua tang khong hop le.'
            ]);
        }

        $hasReceivedGift = $this->request->getPost('has_received_gift');
        $giftStatus = (int) $hasReceivedGift;
        $updated = $this->customerService->updateGiftStatus((int) $customerId, $giftStatus === 1);
        if (!$updated) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Khong the cap nhat trang thai qua tang.'
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => $giftStatus === 1 ? 'Da danh dau khach hang da duoc tang qua.' : 'Da chuyen ve trang thai chua tang qua.',
            'data' => [
                'has_received_gift' => $giftStatus,
                'label' => $giftStatus === 1 ? 'Da tang' : 'Chua tang'
            ]
        ]);
    }

    public function updateMonitoringStatus($customerId)
    {
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Ho so khach hang khong ton tai tren he thong.'
            ]);
        }

        if (!$this->canEditCustomerRecord($customer)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Bạn không có quyền cập nhật trạng thái giám sát của khách hàng này.'
            ]);
        }

        $postData = $this->request->getPost();
        $statusKey = $postData['status_keys'] ?? $postData['status_keys[]'] ?? null;
        if ($statusKey === null) {
            $statusKey = $postData['status_key'] ?? '';
        }
        if ($statusKey === '' || $statusKey === []) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Chưa chọn trạng thái giám sát.'
            ]);
        }

        $result = $this->customerMonitoringStatusService->updateCustomerStatus((int) $customerId, $statusKey);

        return $this->response->setJSON($result);
    }

    private function canEditCustomerRecord(array $customer): bool
    {
        if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
            return true;
        }

        $roleName = session()->get('role_name');
        $myEmpId = session()->get('employee_id');

        return $roleName === \Config\AppConstants::ROLE_TRUONG_PHONG
            || (isset($customer['created_by']) && $customer['created_by'] == $myEmpId)
            || (!empty($customer['assigned_care_staff_id']) && $customer['assigned_care_staff_id'] == $myEmpId)
            || (!empty($customer['relationship_manager_id']) && $customer['relationship_manager_id'] == $myEmpId);
    }

    private function normalizeActiveCareStaffInput(array &$data): bool
    {
        if (!array_key_exists('assigned_care_staff_id', $data)) {
            return true;
        }

        if ($data['assigned_care_staff_id'] === '' || $data['assigned_care_staff_id'] === null) {
            $data['assigned_care_staff_id'] = null;
            return true;
        }

        $employeeId = (int)$data['assigned_care_staff_id'];
        if ($employeeId <= 0 || !$this->findActiveCareStaff($employeeId)) {
            return false;
        }

        $data['assigned_care_staff_id'] = $employeeId;
        return true;
    }

    private function findActiveCareStaff(int $employeeId): ?array
    {
        if ($employeeId <= 0) {
            return null;
        }

        return \Config\Database::connect()
            ->table('employees')
            ->select('employees.id, employees.full_name')
            ->join('users', 'users.id = employees.user_id', 'inner')
            ->where('employees.id', $employeeId)
            ->where('employees.deleted_at', null)
            ->where('users.active_status', 1)
            ->where('users.deleted_at', null)
            ->get()
            ->getRowArray();
    }

    /**
     * API: Chuyen doi trang thai tu van va SLA nhanh qua AJAX.
     * Van kiem tra quyen theo tung ho so truoc khi cap nhat.
     */
    public function transitionStatus($customerId)
    {
        // 1. Kiểm tra sự tồn tại của khách hàng
        $customer = $this->customerModel->find($customerId);
        if (!$customer) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Hồ sơ khách hàng không tồn tại trên hệ thống.'
            ]);
        }

        // 2. PHÂN QUYỀN BẢO MẬT (IDOR & Access Control):
        // Chỉ những người có quyền quản trị, trưởng phòng, hoặc nhân sự trực tiếp phụ trách chăm sóc mới được phép thay đổi.
        $canEdit = false;
        if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
            $canEdit = true;
        } else {
            $roleName = session()->get('role_name');
            $myEmpId = session()->get('employee_id');
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG || (!empty($customer['assigned_care_staff_id']) && $customer['assigned_care_staff_id'] == $myEmpId)) {
                $canEdit = true;
            }
        }

        if (!$canEdit) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Cảnh báo bảo mật: Bạn không có quyền chuyển đổi trạng thái của khách hàng này.'
            ]);
        }

        // 3. Thực thi chuyển trạng thái
        $statusKey = $this->request->getPost('status_key');
        if (empty($statusKey)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Chưa chọn trạng thái mới.'
            ]);
        }

        try {
            $slaService = new \App\Services\CustomerSlaService();
            $result = $slaService->transitionStatus((int) $customerId, (string) $statusKey, session()->get('user_id'));

            return $this->response->setJSON($result);
        } catch (\Throwable $e) {
            log_message('error', 'Customer transition status error: ' . $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Lỗi hệ thống khi cập nhật trạng thái tư vấn. Vui lòng kiểm tra log server.'
                ]);
        }
    }
}

