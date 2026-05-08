<?php

namespace App\Controllers;

use App\Models\CaseCommentModel;
use App\Models\CaseModel;
use App\Models\CaseStepModel;
use App\Models\CustomerModel;
use App\Models\EmployeeModel;
use App\Models\CaseHistoryModel;
use App\Models\DocumentModel;
use CodeIgniter\Controller;

/**
 * CaseController
 * 
 * Bộ điều khiển trung tâm quản lý Vụ việc / Hồ sơ pháp lý (Core ERP).
 * Chịu trách nhiệm:
 * 1. Quản lý toàn bộ vòng đời vụ việc (Từ tiếp nhận đến hoàn thành/lưu trữ).
 * 2. Vận hành quy trình nghiệp vụ (Workflow) đa tầng qua các Steps.
 * 3. Phân quyền truy cập dữ liệu nhạy cảm theo mô hình Role-based & Member-based.
 * 4. Tương tác nội bộ (Bình luận, Nhật ký thay đổi) và Quản lý tài liệu số.
 */
class CaseController extends BaseController
{
    /**
     * Khai báo metadata cho hệ thống Tự động Đồng bộ (Auto-Sync Permissions).
     * Dùng cho cỗ máy quét tại: /perm-fix/sync
     */
    public static $modulePermissions = [
        'group' => 'Vụ việc pháp lý',
        'permissions' => [
            'case.view'     => 'Xem danh sách hồ sơ cơ bản (Phòng ban/Cá nhân)',
            'case.manage'   => 'Sửa đổi, phân công, xoá hồ sơ cơ bản',
            'case.approve'  => 'Phê duyệt, duyệt chuyển bước công việc',
            'case.view_all' => 'Đặc quyền: Xem TOÀN BỘ hồ sơ hệ thống (Bypass isolation)',
            'case.edit_all' => 'Đặc quyền: Chỉnh sửa TOÀN BỘ hồ sơ hệ thống'
        ]
    ];

    /**
     * Khai báo danh mục thuộc thể loại Nhãn dán (Smart Tags).
     * Dùng cho cỗ máy quét tại: /perm-fix/sync
     */
    public static $taggable = [
        'type'  => 'cases',
        'label' => 'Vụ việc pháp lý'
    ];

    protected $stepModel;
    protected $timelineService;
    protected $commentModel;
    protected $workflowService;
    protected $caseModel;
    protected $customerModel;
    protected $roleModel;
    protected $userModel;

    public function __construct()
    {
        // KHỞI TẠO HỆ SINH THÁI DATA:
        // Nạp tất cả các Model và Service nòng cốt phục vụ quản trị hồ sơ.
        $this->caseService = new \App\Services\CaseService();
        $this->caseModel = new CaseModel();
        $this->customerModel = new CustomerModel();
        $this->employeeModel = new EmployeeModel();
        $this->historyModel = new CaseHistoryModel();
        $this->documentModel = new DocumentModel();
        $this->stepModel = new CaseStepModel();
        $this->commentModel = new CaseCommentModel();
        
        // Service tính toán Deadline và điều hướng Workflow
        $this->timelineService = new \App\Services\CaseTimelineService();
        $this->workflowService = new \App\Services\WorkflowService();
        $this->tagService = new \App\Services\TagService();
        $this->roleModel = new \App\Models\RoleModel();
        $this->userModel = new \App\Models\UserModel();
    }

    /**
     * Dashboard Vụ việc (Central Hub).
     * Cung cấp cái nhìn tổng quát về kho hồ sơ, hỗ trợ bộ lọc nâng cao và phân trang.
     */
    public function index()
    {
        // 1. Khởi tạo Service xử lý truy vấn dữ liệu theo phân quyền (Security Data Filtering)
        $caseService = new \App\Services\CaseService();
        
        // 2. Phân tích các tiêu chí tìm kiếm và sắp xếp từ người dùng
        $search = $this->request->getGet('search') ?? '';   // Tìm theo Mã/Tên vụ việc/Khách hàng
        $sort   = $this->request->getGet('sort') ?? 'id';   // Cột cần sắp xếp
        $order  = $this->request->getGet('order') ?? 'desc'; // Hướng (Mới nhất lên đầu)
        $lawyerIds = $this->request->getGet('lawyer_id');
        if (empty($lawyerIds)) {
            $lawyerIds = $this->request->getGet('lawyer_id[]');
        }
        
        if (!empty($lawyerIds)) {
            $lawyerIds = is_array($lawyerIds) ? $lawyerIds : [$lawyerIds];
        } else {
            $lawyerIds = [];
        }
        // 3. Lấy dữ liệu hồ sơ (Chỉ lấy những hồ sơ User được quyền xem - Logic nằm trong Service)
        $status = $this->request->getGet('status') ?? '';
        $tagId = (int)($this->request->getGet('tag_id') ?? 0);
        $month = (int)($this->request->getGet('month') ?? 0);
        $year = (int)($this->request->getGet('year') ?? 0);
        $perPage = 20;
        $cases = $caseService->getCases($sort, $order, $perPage, $search, $lawyerIds, $status, $tagId, $month, $year);
        
        $employeeModel = new \App\Models\EmployeeModel();
        
        // PHÂN QUYỀN TRUY XUẤT NHÂN SỰ ĐỂ LỌC:
        // Admin hoặc người có quyền quản lý/xem tất cả: Xem tất cả. Trưởng phòng: Xem nhân viên thuộc phòng mình.
        $roleName = session()->get('role_name');
        $deptId   = session()->get('department_id');
        
        $isRestricted = !has_permission('sys.admin') && !has_permission('case.edit_all') && !has_permission('case.view_all');
        $isManager = strpos(strtolower($roleName), 'trưởng phòng') !== false;
        
        $availableLawyers = get_available_employees($isRestricted && $isManager ? $deptId : null);
        
        // Nếu là nhân viên thường, chỉ cho lọc chính mình trong danh sách phân công
        if ($isRestricted && !$isManager) {
            $myEmpId = session()->get('employee_id');
            $availableLawyers = array_filter($availableLawyers, function($e) use ($myEmpId) {
                return $e['id'] == $myEmpId;
            });
        }

        // 4. Chuẩn bị dữ liệu hiển thị (Data Aggregation)
        $data = [
            'cases'         => $cases,
            'pager'         => $caseService->getPager(),
            'stats'         => $this->getStats(),
            'search'        => $search,
            'lawyerIds'     => $lawyerIds,
            'currentStatus' => $status,
            'availableLawyers' => $availableLawyers,
            'currentSort'   => $sort,
            'currentOrder'  => $order,
            'currentTagId'  => $tagId,
            'currentMonth'  => $month,
            'currentYear'   => $year,
            'statusLabels'  => \Config\AppConstants::CASE_STATUS_LABELS, // Nhãn trạng thái tiếng Việt
            'availableTags' => $this->tagService->getAvailableTags('cases', has_permission('sys.admin') ? -1 : null),
            'title'         => 'Quản lý vụ việc & Hồ sơ pháp lý | L.A.N ERP',
        ];

        // 5. TRẢ VỀ VIEW: 
        // Nếu là AJAX (khi bấm chuyển trang/lọc), chỉ trả về đoạn HTML bảng để chống giật trang (SPA-like experience).
        if ($this->request->isAJAX()) {
            return view('dashboard/cases/index_table', $data);
        }

        return view('dashboard/cases/index', $data);
    }

    /**
     * Cá nhân hóa: Vụ việc của tôi (My Workspace).
     * Dành riêng cho nhân viên xem các hồ sơ họ trực tiếp chịu trách nhiệm hoặc hỗ trợ.
     */
    public function myCases()
    {
        // 1. Xác định nhân dạng nhân viên đang đăng nhập
        $employeeId = session()->get('employee_id');
        
        // 2. Truy xuất danh sách hồ sơ tham gia qua bảng trung gian CaseMember
        $caseIds = model('CaseMemberModel')->where('employee_id', $employeeId)->findColumn('case_id');

        // 3. Xây dựng câu truy vấn đặc thù (Query Builder):
        // Chỉ lấy hồ sơ: do họ là Trợ lý chính, Luật sư chính, HOẶC là thành viên tham gia nhóm.
        $query = $this->caseModel->select('cases.*, customers.name as customer_name, current_step.step_name as current_step_name, current_step.deadline as step_deadline')
                        ->join('customers', 'customers.id = cases.customer_id')
                        // Xác định bước đang 'ACTIVE' để báo cáo tiến độ ngay trên danh sách
                        ->join('case_steps as current_step', "current_step.case_id = cases.id AND current_step.status IN ('active', 'pending_approval')", 'left')
                        ->groupStart()
                            ->where('cases.assigned_staff_id', $employeeId)
                            ->orWhere('cases.assigned_lawyer_id', $employeeId);
                            
        if (!empty($caseIds)) {
            $query->orWhereIn('cases.id', $caseIds);
        }
        
        // Sắp xếp ưu tiên: Hồ sơ sắp đến hạn (Deadline gần nhất) lên đầu để cảnh báo
        $cases = $query->groupEnd()
                        ->groupBy('cases.id')
                        ->orderBy('current_step.deadline', 'ASC') 
                        ->findAll();

        $data = [
            'cases' => $cases,
            'title' => 'Không gian làm việc: Hồ sơ của tôi | L.A.N ERP'
        ];

        return view('dashboard/cases/my_cases', $data);
    }

    /**
     * Thu thập bộ số liệu thống kê (Stats Hub).
     */
    private function getStats()
    {
        return $this->caseService->getStats();
    }

    /**
     * Giao diện khởi tạo vụ việc mới.
     * Chuẩn bị danh sách Khách hàng, Luật sư và các Mẫu quy trình (Workflow Templates).
     */
    public function create(): string
    {
        $templateModel = new \App\Models\WorkflowTemplateModel();
        $data = [
            'customers' => get_active_customers(), // Core Function
            'lawyers'   => get_available_employees(3), // Phòng Pháp lý (ID=3) - Core Function
            'staffs'    => get_available_employees(), // Tất cả nhân sự - Core Function
            'templates' => $templateModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
            'title'     => 'Thiết lập hồ sơ vụ việc mới | L.A.N ERP'
        ];
        return view('dashboard/cases/create', $data);
    }

    /**
     * Lưu trữ và vận hành Vụ việc mới (Case Orchestration).
     * Xử lý phức hợp: Lưu dữ liệu gốc -> Áp dụng Workflow -> Tính toán Timeline -> Phân công nhân sự.
     */
    public function store()
    {
        $postData = $this->request->getPost();
        
        // --- BẢO VỆ DỮ LIỆU (Data Validation & Normalization) ---
        $input = $postData;
        
        // 1. QUY TẮC ĐỊNH DANH (Standard ID Coding): Tìm mã số cao nhất đang có và +1
        if (empty($input['code'])) {
            $db = \Config\Database::connect();
            $year = date('y');
            $month = date('m');
            $day = date('d');
            $prefix = $day . $month . $year . '-';
            
            // Truy vấn mã số lớn nhất hiện có trong năm (kể cả bản ghi đã xóa mềm)
            $lastCode = $db->table('cases')
                           ->selectMax('code')
                           ->like('code', $prefix, 'after')
                           ->get()->getRow();
            
            $nextNum = 1;
            if ($lastCode && $lastCode->code) {
                // Tách phần số cuối: VV-2026-007 → 7
                $parts = explode('-', $lastCode->code);
                $nextNum = (int)end($parts) + 1;
            }
            
            $input['code'] = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        }

        $input['workflow_template_id'] = !empty($postData['workflow_template_id']) ? $postData['workflow_template_id'] : null;
        $input['customer_id']          = !empty($postData['customer_id']) ? $postData['customer_id'] : null;

        $assignees = $this->request->getPost('assignees') ?? [];
        $primaryAssignee = !empty($assignees) ? $assignees[0] : null;

        $input['assigned_lawyer_id']   = !empty($postData['assigned_lawyer_id']) ? $postData['assigned_lawyer_id'] : $primaryAssignee;
        $input['assigned_staff_id']    = !empty($postData['assigned_staff_id']) ? $postData['assigned_staff_id'] : null;

        $roleName = session()->get('role_name');
        $isHanhChinhOrAdmin = ($roleName === \Config\AppConstants::ROLE_ADMIN || session()->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH);
        if (!$isHanhChinhOrAdmin) {
            unset($input['contract_value'], $input['payment_progress'], $input['payments']);
        } else {
            if (isset($input['contract_value'])) {
                $input['contract_value'] = str_replace([',', '.'], '', $input['contract_value']);
                if ($input['contract_value'] === '') $input['contract_value'] = null;
            }
            if (isset($input['payments']) && is_array($input['payments'])) {
                $paymentsInfo = [];
                foreach ($input['payments'] as $p) {
                    $cleanAmount = isset($p['amount']) ? str_replace(['.', ','], '', $p['amount']) : '';
                    if (!empty($cleanAmount)) {
                        $p['amount'] = $cleanAmount;
                        $p['is_paid'] = isset($p['is_paid']) ? 1 : 0;
                        $paymentsInfo[] = $p;
                    }
                }
                $input['payment_progress'] = count($paymentsInfo) > 0 ? json_encode(array_values($paymentsInfo), JSON_UNESCAPED_UNICODE) : null;
            }
        }


        // --- BƯỚC 1: TỰ ĐỘNG HÓA DEADLINE (Workflow Automation) ---
        // Mặc định thời hạn dự kiến là 30 ngày (nếu không chọn quy trình)
        $daysOfCase = 30;
        
        if ($input['workflow_template_id']) {
            $templateModel = new \App\Models\WorkflowTemplateModel();
            $template = $templateModel->find($input['workflow_template_id']);
            if ($template) {
                $daysOfCase = $template['total_estimated_days'] ?: 30;
            }
        }
        
        $input['deadline'] = $this->timelineService->calculateDeadline(new \DateTime(), $daysOfCase)->format('Y-m-d H:i:s');

        $input['status'] = 'cho_tiep_nhan';
        $templateId = $input['workflow_template_id']; // For use in initializeFlowForCase
        if ($this->caseModel->save($input)) {
            $caseId = $this->caseModel->getInsertID();
            
            // --- BƯỚC 3: PHÁT HÀNH QUY TRÌNH (Workflow Initialization) ---
            // Tự động sinh ra các bước (Steps) trong timeline để nhân viên bắt đầu thực hiện.
            try {
                $initialized = $this->workflowService->initializeFlowForCase($caseId, $templateId);
                
                // Fallback: Nếu không dùng Template động, khởi tạo theo Template tĩnh định nghĩa trong code.
                if (!$initialized && isset($input['type'])) {
                    $stepsConfig = $this->timelineService->getStepsForType($input['type']);
                    if (!empty($stepsConfig)) {
                         $currentRefDate = new \DateTime();
                         foreach ($stepsConfig as $index => $stepConfig) {
                             $deadline = $this->timelineService->calculateDeadline($currentRefDate, $stepConfig['days']);
                             $this->stepModel->save([
                                 'case_id' => $caseId,
                                 'step_name' => $stepConfig['name'],
                                 'duration_days' => $stepConfig['days'],
                                 'deadline' => $deadline->format('Y-m-d H:i:s'),
                                 'status' => ($index === 0) ? 'active' : 'pending',
                                 'sort_order' => $index,
                                 'required_documents' => json_encode($stepConfig['docs'])
                             ]);
                             $currentRefDate = clone $deadline;
                         }
                    }
                }
            } catch (\Exception $e) {
                log_message('error', 'Khởi tạo quy trình thất bại: ' . $e->getMessage());
            }

            // --- BƯỚC 4: LƯU VẾT NHẬT KÝ (Audit Trail) ---
            $this->logHistory($caseId, 'tiep_nhan', null, 'moi_tiep_nhan', 'Khởi tạo hồ sơ vụ việc và áp dụng quy trình tự động.');

            // --- BƯỚC 5: PHÂN QUYỀN THÀNH VIÊN (Member Synchronization) ---
            // Đồng bộ danh sách Người duyệt, Người thực hiện chính và Trợ lý hỗ trợ.
            $approvers = $this->request->getPost('approvers') ?? [];
            $assignees = $this->request->getPost('assignees') ?? [];
            $supporters = $this->request->getPost('supporters') ?? [];

            $caseMemberModel = model('CaseMemberModel');
            $caseMemberModel->syncMembers($caseId, 'approver', $approvers);
            $caseMemberModel->syncMembers($caseId, 'assignee', $assignees);
            $caseMemberModel->syncMembers($caseId, 'supporter', $supporters);
            
            // --- THÔNG BÁO CHO NHÂN SỰ ĐƯỢC GIAO ---
            $case = $this->caseModel->find($caseId);
            $msg = "Bạn đã được phân công tham gia vụ việc mới: [{$case['code']}] - {$case['title']}";
            $link = base_url('cases/show/' . $caseId);
            
            $notifService = new \App\Services\NotificationService();
            // Thông báo cho tất cả thành viên (trừ người tạo)
            $workflowService = new \App\Services\WorkflowService();
            $workflowService->notifyCaseMembers($caseId, "Hồ sơ vụ việc mới", $msg, 'task', $link);
            
            return redirect()->to(base_url('cases'))->with('success', 'Hồ sơ đã được thiết lập và quy trình đã được kích hoạt thành công.');
        }

        return redirect()->back()->withInput()->with('errors', $this->caseModel->errors());
    }

    /**
     * Chế độ xem chi tiết hồ sơ (Detailed Case View - 360 Degree).
     * Trung tâm chỉ huy cho một vụ việc: Quản lý Timeline, Thảo luận, Phê duyệt và Tài liệu.
     * 
     * @param int|string $id ID hồ sơ.
     */
    public function show($id)
    {
        $tagService = new \App\Services\TagService();
        // 1. Thu thập dữ liệu gốc từ nhiều nguồn liên quan
        $case = $this->caseModel->select('cases.*, customers.name as customer_name, customers.phone as customer_phone, lawyer.full_name as lawyer_name, staff.full_name as staff_name, wt.name as template_name')
                    ->join('customers', 'customers.id = cases.customer_id')
                    ->join('employees as lawyer', 'lawyer.id = cases.assigned_lawyer_id', 'left')
                    ->join('employees as staff', 'staff.id = cases.assigned_staff_id', 'left')
                    ->join('workflow_templates as wt', 'wt.id = cases.workflow_template_id', 'left')
                    ->find($id);

        if (!$case) {
            return redirect()->to(base_url('cases'))->with('error', 'Dữ liệu hồ sơ không còn tồn tại trên hệ thống.');
        }

        // Cho phép truy cập nếu là Admin, có quyền quản lý chung, hoặc có quyền Xem TẤT CẢ (view_all).
        $roleName = session()->get('role_name');
        $myEmpId = session()->get('employee_id');
        
        if (!has_permission('sys.admin') && !has_permission('case.edit_all') && !has_permission('case.view_all')) {
            $isAssigned = ($case['assigned_lawyer_id'] == $myEmpId || $case['assigned_staff_id'] == $myEmpId);
            $isMember = model('CaseMemberModel')->where('case_id', $id)->where('employee_id', $myEmpId)->first();
            
            // QUYỀN TRƯỞNG PHÒNG: Xem được mọi vụ việc mà nhân viên trong phòng tham gia
            $isMyDeptCase = false;
            if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
                $myDeptId = session()->get('department_id');
                $deptEmpIds = $this->employeeModel->where('department_id', $myDeptId)->select('id')->get()->getResultArray();
                $deptEmpIds = array_column($deptEmpIds, 'id');
                
                if (!empty($deptEmpIds)) {
                     // Kiểm tra xem Lawyer/Staff chính có thuộc phòng mình ko
                     if (in_array($case['assigned_lawyer_id'], $deptEmpIds) || in_array($case['assigned_staff_id'], $deptEmpIds)) {
                         $isMyDeptCase = true;
                     } else {
                         // Hoặc có thành viên nào trong phòng mình tham gia ko
                         $hasMemberInDept = model('CaseMemberModel')
                             ->where('case_id', $id)
                             ->whereIn('employee_id', $deptEmpIds)
                             ->countAllResults();
                         if ($hasMemberInDept > 0) $isMyDeptCase = true;
                     }
                }
            }

            if (!$isAssigned && !$isMember && !$isMyDeptCase) {
                return redirect()->to(base_url('cases'))->with('error', 'Cảnh báo bảo mật: Bạn không có quyền truy cập hồ sơ này.');
            }
        }

        // 2. Quản lý Timeline (Workflow Steps)
        $steps = $this->stepModel->where('case_id', $id)->orderBy('sort_order', 'ASC')->findAll();

        // TỰ ĐỘNG KHÔI PHỤC TIMELINE:
        // Đảm bảo không bao giờ xảy ra lỗi "Trống bước" cho các vụ việc cũ.
        if (empty($steps)) {
            try {
                $this->workflowService->initializeFlowForCase($id);
                $steps = $this->stepModel->where('case_id', $id)->orderBy('sort_order', 'ASC')->findAll();
            } catch (\Exception $e) {
                log_message('error', 'Phục hồi quy trình thất bại: ' . $e->getMessage());
            }
        }

        // 3. Phân tích cơ cấu nhân sự tham gia
        $caseMemberModel = model('CaseMemberModel');
        $members = $caseMemberModel->getMembersByCase($id);
        
        $memberGroups = ['approver' => [], 'assignee' => [], 'supporter' => []];
        foreach ($members as $m) {
            $memberGroups[$m['role_in_case']][] = $m;
        }

        // 4. Tổng hợp dữ liệu hiển thị (Aggregated Data)
        $data = [
            'case'      => $case,
            'history'   => $this->historyModel->where('case_id', $id)->orderBy('created_at', 'DESC')->findAll(),
            'documents' => $this->documentModel->where('case_id', $id)->findAll(),
            'steps'     => $steps,
            'active_step' => $this->stepModel->getCurrentStep($id),
            'comments'  => $this->commentModel->getCommentsByCase($id),
            'lawyers'   => get_available_employees(3), // Core Function
            'staffs'    => get_available_employees(), // Core Function
            'members'   => $members,
            'memberGroups' => $memberGroups,
            'tags'      => $this->tagService->getTagsByEntity($id, 'cases'),
            'availableTags' => get_available_tags('cases'), // Core Function
            'templates' => model('WorkflowTemplateModel')->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
            'statusLabels' => \Config\AppConstants::CASE_STATUS_LABELS,
            'isApprover' => false, 
            'isAssignee' => false, 
            'title'     => 'Hồ sơ: ' . $case['code'] . ' | L.A.N ERP'
        ];

        // 5. Kiểm tra quyền hạn trực tiếp đối với hồ sơ cụ thể
        $currentEmpId = session()->get('employee_id');
        if ($currentEmpId) {
            foreach ($members as $m) {
                if ($m['employee_id'] == $currentEmpId) {
                    if ($m['role_in_case'] === 'approver') $data['isApprover'] = true;
                    if ($m['role_in_case'] === 'assignee') $data['isAssignee'] = true;
                }
            }
        }

        // 6. GIẢI MÃ VAI TRÒ CHỊU TRÁCH NHIỆM (Responsible Decoding):
        // Chuyển đổi dữ liệu JSON định danh vai trò/người thực hiện bước sang HTML tag dễ hiểu.
        if (!empty($data['active_step']['responsible_role'])) {
            $rolesMap = ['admin' => 'Quản trị', 'truong_phong' => 'Trưởng phòng', 'nhan_vien' => 'Nhân viên', 'tu_van' => 'Tư vấn'];
            $responsible = [];
            $decoded = json_decode($data['active_step']['responsible_role'], true);
            $roleList = is_array($decoded) ? $decoded : [$data['active_step']['responsible_role']];
            
            foreach ($roleList as $item) {
                if (strpos($item, 'role:') === 0) {
                    $roleKey = substr($item, 5);
                    $responsible[] = '<span class="badge-secondary-minimal"><i class="fas fa-users-cog"></i> ' . ($rolesMap[$roleKey] ?? $roleKey) . '</span>';
                } elseif (strpos($item, 'user:') === 0) {
                    $userId = substr($item, 5);
                    foreach ($data['staffs'] as $s) {
                        if ($s['id'] == $userId) {
                            $responsible[] = '<span class="badge-secondary-minimal"><i class="fas fa-user"></i> ' . esc($s['full_name']) . '</span>';
                            break;
                        }
                    }
                } else {
                    $responsible[] = '<span class="badge-secondary-minimal">' . esc($item) . '</span>';
                }
            }
            $data['active_step']['responsible_display'] = implode(' ', $responsible);
        }

        // 7. TRACKING PHÊ DUYỆT (Approval Awareness):
        // Kiểm tra xem quản lý đã xem yêu cầu phê duyệt chưa để báo hiệu cho nhân viên biết.
        $data['is_approval_read'] = 0;
        if (!empty($data['active_step']) && $data['active_step']['status'] === 'pending_approval') {
            $notificationModel = new \App\Models\NotificationModel();
            $latestApprovalNotif = $notificationModel->where('sender_id', session()->get('user_id'))
                                                     ->where('type', 'approval')
                                                     ->like('link', 'cases/show/' . $id)
                                                     ->orderBy('created_at', 'DESC')
                                                     ->first();
            if ($latestApprovalNotif) {
                $notifs = $notificationModel->where('sender_id', session()->get('user_id'))
                                            ->where('type', 'approval')
                                            ->where('created_at', $latestApprovalNotif['created_at'])
                                            ->findAll();
                foreach($notifs as $n) {
                    if ($n['is_read'] == 1) {
                        $data['is_approval_read'] = 1;
                        break;
                    }
                }
            }
        }

        return view('dashboard/cases/show', $data);
    }

    /**
     * Giao diện chỉnh sửa thông tin vụ việc.
     */
    public function edit($id)
    {
        $case = $this->caseModel->find($id);
        if (!$case) {
            return redirect()->to(base_url('cases'))->with('error', 'Hồ sơ không tồn tại.');
        }

        // --- BẢO MẬT: KIỂM TRA QUYỀN (Chỉ Admin, Quản lý hoặc người phụ trách chính) ---
        if (!has_permission('sys.admin') && !has_permission('case.edit_all')) {
            $myEmpId = session()->get('employee_id');
            $roleName = session()->get('role_name');

            // 1. Kiểm tra gán trực tiếp
            $isDirectlyAssigned = ($case['assigned_lawyer_id'] == $myEmpId || $case['assigned_staff_id'] == $myEmpId);
            
            // 2. Kiểm tra thông qua bảng CaseMember (Vai trò Assignee)
            $isCaseAssignee = model('CaseMemberModel')->where([
                'case_id' => $id,
                'employee_id' => $myEmpId,
                'role_in_case' => 'assignee'
            ])->countAllResults() > 0;

            if (!$isDirectlyAssigned && !$isCaseAssignee) {
                // Kiểm tra xem có phải trưởng phòng pháp lý không
                $canEditByRole = ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG && session()->get('department_id') == \Config\AppConstants::DEPT_PHAP_LY);
                // Bổ sung quyền cho Hành chính Kế toán được phép truy cập Edit Form để cập nhật Tài chính
                $isHanhChinh = (session()->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH);
                
                if (!$canEditByRole && !$isHanhChinh) {
                    return redirect()->back()->with('error', 'Bạn không có quyền chỉnh sửa hồ sơ này.');
                }
            }
        }

        $templateModel = new \App\Models\WorkflowTemplateModel();
        $caseMemberModel = model('CaseMemberModel');
        $members = $caseMemberModel->where('case_id', $id)->findAll();

        $data = [
            'case'      => $case,
            'customers' => get_active_customers(), // Core Function
            'lawyers'   => get_available_employees(3), // Core Function
            'staffs'    => get_available_employees(), // Core Function
            'templates' => $templateModel->where('is_active', 1)->findAll(),
            'members'   => $members,
            'title'     => 'Chỉnh sửa hồ sơ: ' . $case['code'] . ' | L.A.N ERP'
        ];

        return view('dashboard/cases/edit', $data);
    }

    /**
     * Cập nhật thông tin vụ việc.
     */
    public function update($id)
    {
        $roleName = session()->get('role_name');
        
        $case = $this->caseModel->find($id);
        if (!$case) return redirect()->to(base_url('cases'));

        // --- BẢO MẬT: KIỂM TRA QUYỀN TRƯỚC KHI LƯU ---
        if (!has_permission('sys.admin') && !has_permission('case.edit_all')) {
            $myEmpId = session()->get('employee_id');

            $isDirectlyAssigned = ($case['assigned_lawyer_id'] == $myEmpId || $case['assigned_staff_id'] == $myEmpId);
            $isCaseAssignee = model('CaseMemberModel')->where([
                'case_id' => $id,
                'employee_id' => $myEmpId,
                'role_in_case' => 'assignee'
            ])->countAllResults() > 0;

            if (!$isDirectlyAssigned && !$isCaseAssignee) {
                $canEditByRole = ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG && session()->get('department_id') == \Config\AppConstants::DEPT_PHAP_LY);
                if (!$canEditByRole) {
                    return redirect()->back()->with('error', 'Cảnh báo: Bạn không có quyền ghi đè dữ liệu lên hồ sơ này.');
                }
            }
        }

        $input = $this->request->getPost();
        
        // Data Normalization
        // --- BỘ ĐIỀU HƯỚNG: PHÁT HIỆN & XỬ LÝ ĐỔI QUY TRÌNH KHI SỬA HỒ SƠ ---
        $isWorkflowChanging = false;
        if (array_key_exists('workflow_template_id', $input)) {
            $newTemplateId = $input['workflow_template_id'];
            $oldTemplateId = $case['workflow_template_id'];
            
            if (!empty($newTemplateId) && $newTemplateId != $oldTemplateId) {
                $isWorkflowChanging = true;
                $deptId   = session()->get('department_id');

                // Phân quyền bảo mật tối đa: Gác cổng
                $isAuthorized = has_permission('sys.admin') || has_permission('case.edit_all') ||
                                ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG && $deptId == \Config\AppConstants::DEPT_PHAP_LY);

                if (!$isAuthorized) {
                    return redirect()->back()->withInput()->with('error', 'Cảnh báo bảo mật: Bạn không có thẩm quyền đổi quy trình cho vụ việc (Chỉ dành cho Trưởng phòng Pháp lý/Admin).');
                }

                try {
                    // Tiêm thẳng vào Service để thực thi xoá và sinh lại timeline an toàn trước khi đổi Data hành chính
                    $this->workflowService->changeWorkflowForCase($id, $newTemplateId);
                } catch (\Exception $e) {
                    return redirect()->back()->withInput()->with('error', $e->getMessage());
                }
            } else {
                // Nếu User chọn lại đúng quy trình cũ, hoặc rỗng do lỗi gì đó -> xóa nó khỏi POST để khỏi update dư thừa
                unset($input['workflow_template_id']);
            }
        } else {
            // Rất QUAN TRỌNG: Nếu form bị disabled, HTML sẽ không mớm giá trị workflow_template_id lên POST.
            // Phải chặn không cho biến nó thành NULL (vì update của CodeIgniter xử lý array sẽ chọc thẳng vào DB gán bằng NULL).
            unset($input['workflow_template_id']);
        }
        // -------------------------------------------------------------------
        $assignees = $this->request->getPost('assignees') ?? [];
        $primaryAssignee = !empty($assignees) ? $assignees[0] : null;

        $input['customer_id']          = !empty($input['customer_id']) ? $input['customer_id'] : null;
        $input['assigned_lawyer_id']   = !empty($input['assigned_lawyer_id']) ? $input['assigned_lawyer_id'] : $primaryAssignee;
        $input['assigned_staff_id']    = !empty($input['assigned_staff_id']) ? $input['assigned_staff_id'] : null;

        // BẢO MẬT TÀI CHÍNH: Chỉ Hành chính & Admin được cập nhật giá trị hợp đồng
        $isHanhChinhOrAdmin = ($roleName === \Config\AppConstants::ROLE_ADMIN || session()->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH);
        if (!$isHanhChinhOrAdmin) {
            unset($input['contract_value'], $input['payment_progress']);
        } else {
            if (isset($input['contract_value'])) {
                // Xóa dấu phẩy/chấm ngàn để lưu BIGINT
                $input['contract_value'] = str_replace([',', '.'], '', $input['contract_value']);
                if ($input['contract_value'] === '') $input['contract_value'] = null;
            }
            if (isset($input['payments']) && is_array($input['payments'])) {
                $paymentsInfo = [];
                foreach ($input['payments'] as $p) {
                    $cleanAmount = isset($p['amount']) ? str_replace(['.', ','], '', $p['amount']) : '';
                    if (!empty($cleanAmount)) {
                        $p['amount'] = $cleanAmount;
                        $p['is_paid'] = isset($p['is_paid']) ? 1 : 0;
                        $paymentsInfo[] = $p;
                    }
                }
                $input['payment_progress'] = count($paymentsInfo) > 0 ? json_encode(array_values($paymentsInfo), JSON_UNESCAPED_UNICODE) : null;
            }
        }

        if ($this->caseModel->update($id, $input)) {
            // --- LOGIC BÀN GIAO KPI (Handover Integration) ---
            // Đồng bộ 'người được giao' cho các bước chưa hoàn thành để bảo đảm KPI tiềm năng chuyển sang người mới
            $newAssignedId = $input['assigned_lawyer_id'] ?: $input['assigned_staff_id'];
            if ($newAssignedId) {
                $this->stepModel->where('case_id', $id)
                                ->whereIn('status', ['pending', 'active', 'pending_approval', 'overdue'])
                                ->set(['assigned_to' => $newAssignedId])
                                ->update();
            }

            // Đồng bộ nhân sự tham gia
            $caseMemberModel = model('CaseMemberModel');
            $caseMemberModel->syncMembers($id, 'approver', $this->request->getPost('approvers') ?? []);
            $caseMemberModel->syncMembers($id, 'assignee', $this->request->getPost('assignees') ?? []);
            $caseMemberModel->syncMembers($id, 'supporter', $this->request->getPost('supporters') ?? []);

            $this->logHistory($id, 'update_info', null, null, 'Cập nhật thông tin hành chính & phân công nhân sự hồ sơ.');
            return redirect()->to(base_url('cases/show/' . $id))->with('success', 'Hồ sơ đã được cập nhật thành công.');
        }

        return redirect()->back()->withInput()->with('errors', $this->caseModel->errors());
    }

    /**
     * Xóa vụ việc (Soft Delete) có kiểm soát vòng đời dữ liệu.
     */
    public function delete($id)
    {
        // 1. Phân quyền: Hành động nhạy cảm nhất hệ thống
        if (!has_permission('sys.admin')) {
            return redirect()->back()->with('error', 'Cảnh báo bảo mật: Chỉ Quản trị viên tối cao mới được quyền gỡ hồ sơ pháp lý khỏi hệ thống.');
        }

        $case = $this->caseModel->find($id);
        if (!$case) {
            return redirect()->back()->with('error', 'Hồ sơ pháp lý không tồn tại hoặc đã bị xóa trước đó.');
        }

        // 2. Chốt chặn vĩnh viễn (Integrity Barrier): KHÔNG XÓA NẾU ĐÃ PHÁT SINH TIẾN ĐỘ
        $stepModel = new \App\Models\CaseStepModel();
        $completedSteps = $stepModel->where('case_id', $id)->where('status', 'completed')->countAllResults();
        
        // Hỗ trợ bypass "Khóa bảo vệ" cho Admin khi cần thiết thông qua tham số ?force=1
        if ($completedSteps > 0 && $this->request->getGet('force') != 1) {
            return redirect()->back()->with('error', 'Khóa bảo vệ: Không thể xóa vụ việc đã có lịch sử nghiệm thu công đoạn. Vui lòng cập nhật rớt trạng thái (Dừng/Hủy) thay thế (hoặc thêm ?force=1 vào URL đối với Admin).');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 3. Liên đới dữ liệu (Cascading Orphans Fix)
        // Hủy liên kết toàn bộ tài liệu trực thuộc Vụ việc này thay vì xóa tài sản số của công ty
        $docModel = new \App\Models\DocumentModel();
        $docModel->where('case_id', $id)->set(['case_id' => null, 'step_id' => null])->update();

        // Hủy liên kết các bài viết Cẩm nang tri thức (Knowledge Base)
        $knowledgeModel = new \App\Models\KnowledgeModel();
        $knowledgeModel->where('case_id', $id)->set(['case_id' => null])->update();

        // Kích hoạt Xóa mềm (Soft Delete) đối với các bảng râu ria thông qua Model chuẩn
        $stepModel->where('case_id', $id)->delete();
        
        $commentModel = new \App\Models\CaseCommentModel();
        $commentModel->where('case_id', $id)->delete();
        
        $memberModel = new \App\Models\CaseMemberModel();
        $memberModel->where('case_id', $id)->delete();

        // KHÔNG xóa bảng cấu trúc Audit Trail (case_history, system_logs) nhằm bảo toàn chứng cứ vĩnh viễn.

        // 4. Exec Xóa Mềm Vụ việc chính
        $this->caseModel->delete($id);

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Lỗi Transaction: Xung đột dữ liệu không thể dọn dẹp hệ thống.');
        }

        // Ghi Log
        $logService = new \App\Services\SystemLogService();
        $logService->log('DELETE', 'Cases', $id, ['deleted_case_code' => $case['code']]);

        return redirect()->to(base_url('cases'))->with('success', 'Đã thiết quân luật và xóa trắng vụ việc.');
    }

    /**
     * Xóa VĨNH VIỄN vụ việc (Hard Delete) - Dùng khi tạo nhầm.
     */
    public function purge($id)
    {
        // 1. Chỉ Admin mới có quyền dọn dẹp rác hệ thống
        if (!has_permission('sys.admin')) {
            return redirect()->back()->with('error', 'Chỉ Quản trị viên mới được quyền xóa vĩnh viễn dữ liệu.');
        }

        $case = $this->caseModel->find($id);
        if (!$case) {
            return redirect()->back()->with('error', 'Hồ sơ không tồn tại.');
        }

        // 2. Chốt chặn: Không được xóa nếu đã có công việc HOÀN THÀNH (Tránh xóa nhầm dữ liệu thật)
        $stepModel = new \App\Models\CaseStepModel();
        $completedSteps = $stepModel->where('case_id', $id)->where('status', 'completed')->countAllResults();
        
        // Hỗ trợ dọn dẹp dữ liệu rác/lỗi cho Admin thông qua tham số ?force=1
        if ($completedSteps > 0 && $this->request->getGet('force') != 1) {
            return redirect()->back()->with('error', 'Bảo mật: Hồ sơ này đã có tiến trình thực tế, không thể xóa vĩnh viễn (Hãy dùng ?force=1 nếu bạn chắc chắn đây là dữ liệu rác).');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 3. Dọn dẹp sạch sẽ (Hard Delete) các phân hệ liên quan
        $db->table('case_steps')->where('case_id', $id)->delete();
        $db->table('case_members')->where('case_id', $id)->delete();
        $db->table('case_comments')->where('case_id', $id)->delete();
        $db->table('entity_tags')->where('entity_id', $id)->where('entity_type', 'cases')->delete();

        // Hủy liên kết tài liệu/cẩm nang (như logic delete cũ)
        $db->table('documents')->where('case_id', $id)->update(['case_id' => null, 'step_id' => null]);
        $db->table('knowledge_base')->where('case_id', $id)->update(['case_id' => null]);

        // 4. Xóa vĩnh viễn bản ghi gốc
        $this->caseModel->delete($id, true); // TRUE = Hard Delete

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Lỗi hệ thống khi dọn dẹp dữ liệu rác.');
        }

        return redirect()->to(base_url('cases'))->with('success', 'Đã xóa vĩnh viễn hồ sơ và dọn dẹp toàn bộ dữ liệu liên quan.');
    }

    /**
     * Xóa chọn hồ sơ (Bulk Action) - Chỉ Admin.
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

        $success = 0;
        foreach ($ids as $id) {
            // Sử dụng logic xóa mặc định (Soft Delete nếu model đã cấu hình)
            if ($this->caseModel->delete($id)) {
                $success++;
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "Đã dọn dẹp thành công {$success} hồ sơ vụ việc vào thùng rác."
        ]);
    }


    /**
     * Gửi nhắc nhở/chỉ đạo nghiệp vụ cho nhân sự khác.
     */
    public function sendReminder($id)
    {
        $recipientUserId = $this->request->getPost('recipient_user_id');
        $message = $this->request->getPost('message');

        if (!$recipientUserId || !$message) {
            return redirect()->back()->with('error', 'Vui lòng chọn người nhận và nội dung nhắc nhở.');
        }

        $case = $this->caseModel->find($id);
        if (!$case) {
            return redirect()->back()->with('error', 'Hồ sơ không tồn tại.');
        }

        // 1. Tạo thông báo (Notification)
        $notifModel = model('NotificationModel');
        $notifModel->save([
            'user_id'   => $recipientUserId,
            'sender_id' => session()->get('user_id'),
            'type'      => 'reminder',
            'title'     => 'Nhắc nhở vụ việc: ' . $case['code'],
            'message'   => $message,
            'link'      => base_url('cases/show/' . $id),
            'is_read'   => 0
        ]);

        // 2. Ghi nhật ký (History)
        $this->logHistory($id, 'gui_nhac_nho', null, null, 'Đã gửi nhắc nhở nghiệp vụ cho nhân sự.');

        return redirect()->back()->with('success', 'Đã chuyển thông báo nhắc nhở thành công.');
    }

    /**
     * Quản lý Phân công nhân sự tham gia (Resource Management).
     * Cho phép Quản trị viên thay đổi cơ cấu nhân sự xử lý hồ sơ.
     */
    public function updateMembers($id)
    {
        // Kiểm tra quyền hạn manage hồ sơ
        if (!has_permission('sys.admin') && !has_permission('case.edit_all')) {
            return redirect()->back()->with('error', 'Bạn không được phân quyền để thay đổi nhân sự vụ việc này.');
        }

        $approvers = $this->request->getPost('approvers') ?? [];
        $assignees = $this->request->getPost('assignees') ?? [];
        $supporters = $this->request->getPost('supporters') ?? [];

        // --- AUDIT LOGGING ---
        // Ghi lại danh sách nạp vào để đối soát nếu xảy ra lỗi không lưu được dữ liệu.
        log_message('info', "[CASE_MEMBER] Syncing Case ID: $id | Approvers: " . json_encode($approvers) . " | Assignees: " . json_encode($assignees) . " | Supporters: " . json_encode($supporters));

        $caseMemberModel = model('CaseMemberModel');
        $caseMemberModel->syncMembers($id, 'approver', $approvers);
        $caseMemberModel->syncMembers($id, 'assignee', $assignees);
        $caseMemberModel->syncMembers($id, 'supporter', $supporters);

        $this->logHistory($id, 'phan_cong_nhan_su', null, null, 'Cập nhật danh sách đội ngũ tham gia xử lý vụ việc.');

        return redirect()->back()->with('success', 'Đã cập nhật danh sách nhân sự tham gia hồ sơ thành công.');
    }

    /**
     * Quản lý thảo luận: Thêm bình luận nội bộ.
     * Dùng để trao đổi nghiệp vụ trực tiếp trong hồ sơ mà không qua các app nhắn tin khác.
     */
    public function addComment($id)
    {
        $content = $this->request->getPost('content');
        if (empty($content)) return redirect()->back();

        // Kiểm tra quyền (Phải là thành viên vụ việc)
        $myEmpId = session()->get('employee_id');
        if (!has_permission('sys.admin') && !has_permission('case.manage')) {
            $case = $this->caseModel->find($id);
            $isAssigned = ($case['assigned_lawyer_id'] == $myEmpId || $case['assigned_staff_id'] == $myEmpId);
            $isMember = model('CaseMemberModel')->where('case_id', $id)->where('employee_id', $myEmpId)->first();
            if (!$isAssigned && !$isMember) return redirect()->back()->with('error', 'Bạn không có quyền bình luận hồ sơ này.');
        }

        $this->commentModel->save([
            'case_id' => $id,
            'user_id' => session()->get('user_id'),
            'content' => $content,
            'is_internal' => 1 // Mặc định là ghi chú nội bộ của công ty
        ]);

        // --- GỬI THÔNG BÁO TẬP TRUNG (Smart Dispatch) ---
        $case = $this->caseModel->find($id);
        $senderName = session()->get('full_name');
        $msg = "{$senderName} vừa để lại một ghi chú mới trong hồ sơ {$case['code']}: \"".mb_substr($content, 0, 50)."...\"";
        $link = base_url('cases/show/' . $id);
        
        // 1. Lấy danh sách Admin
        $adminRole = $this->roleModel->where('name', \Config\AppConstants::ROLE_ADMIN)->first();
        $adminRoleId = $adminRole['id'] ?? 1;
        $adminIds = $this->userModel->where('role_id', $adminRoleId)->where('active_status', 1)->findColumn('id') ?? [];
        
        // 2. Lấy danh sách thành viên vụ việc
        $caseMemberModel = model('CaseMemberModel');
        $members = $caseMemberModel->where('case_id', $id)->findAll();
        $memberEmpIds = array_column($members, 'employee_id');
        
        // Bổ sung lawyer/staff chính
        if (!empty($case['assigned_lawyer_id'])) $memberEmpIds[] = $case['assigned_lawyer_id'];
        if (!empty($case['assigned_staff_id'])) $memberEmpIds[] = $case['assigned_staff_id'];
        
        $recipientUserIds = $adminIds;
        if (!empty($memberEmpIds)) {
            $memberUserIds = model('EmployeeModel')->whereIn('id', array_unique($memberEmpIds))->findColumn('user_id') ?? [];
            $recipientUserIds = array_merge($recipientUserIds, $memberUserIds);
        }

        $notifService = new \App\Services\NotificationService();
        $notifService->sendToMultiple($recipientUserIds, "Ghi chú vụ việc mới", $msg, 'task', $link);

        return redirect()->back()->with('success', 'Ghi chú nội bộ đã được lưu.');
    }

    /**
     * Vận hành quy trình: Xử lý hoàn thành Bước (Step Progression).
     * Logic: Nhân viên bấm hoàn tất -> Nếu có quyền thì hoàn tất ngay, nếu không thì gửi yêu cầu phê duyệt.
     */
    public function completeStep($stepId)
    {
        try {
            $role = session()->get('role_name');
            $myEmpId = session()->get('employee_id');
            $step = $this->stepModel->find($stepId);

            // Kiểm tra quyền truy cập hồ sơ chứa bước này
            if (!has_permission('sys.admin') && !has_permission('case.edit_all')) {
                $case = $this->caseModel->find($step['case_id']);
                $isAssigned = ($case['assigned_lawyer_id'] == $myEmpId || $case['assigned_staff_id'] == $myEmpId);
                $isMember = model('CaseMemberModel')->where('case_id', $step['case_id'])->where('employee_id', $myEmpId)->first();
                if (!$isAssigned && !$isMember) return redirect()->back()->with('error', 'Bạn không có quyền thao tác trên hồ sơ này.');
            }

            // --- CƠ CHẾ KIỂM SOÁT PHÊ DUYỆT (Gatekeeping) ---
            // Nếu là tài khoản 'Nhân viên', mọi bước hoàn thành phải được gửi cho 'Người duyệt' thẩm định.
            if (strpos(strtolower($role), 'nhân viên') !== false || $role == 'Nhân viên chính thức') {
                $step = $this->stepModel->find($stepId);
                
                // Trường hợp đặc biệt: Nhân viên này chính là Approver của vụ việc thì họ có quyền ký duyệt ngay.
                $isCaseApprover = model('CaseMemberModel')->where([
                    'case_id' => $step['case_id'],
                    'employee_id' => session()->get('employee_id'),
                    'role_in_case' => 'approver'
                ])->countAllResults() > 0;

                if (!$isCaseApprover) {
                    $this->workflowService->submitForApproval($stepId, $this->request->getPost());
                    return redirect()->back()->with('success', 'Yêu cầu phê duyệt hoàn thành bước đã được gửi đến quản lý.');
                }
            }

            // XỬ LÝ TRỰC TIẾP (Cho Manager/Admin hoặc Approver):
            $this->workflowService->completeStep($stepId, $this->request->getPost());
            $this->triggerNextStep($stepId); // Tự động đẩy sang bước kế tiếp trong quy trình

            return redirect()->back()->with('success', 'Đã xác nhận hoàn thành bước quy trình thành công.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Quản lý Phê duyệt: Phê duyệt Bước đã hoàn thành.
     */
    public function approveStep($stepId)
    {
        try {
            $this->workflowService->approveStep($stepId);
            $this->triggerNextStep($stepId); // Kích hoạt bước tiếp theo sau khi duyệt
            return redirect()->back()->with('success', 'Bạn đã phê duyệt và đẩy tiến độ hồ sơ sang bước tiếp theo.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Thuật toán Kích hoạt Bước tiếp theo (Auto-Trigger Logic).
     * Phân tích Timeline để tìm bước kế tiếp dựa trên thứ tự sắp xếp (sort_order).
     */
    private function triggerNextStep($stepId)
    {
        $step = $this->stepModel->find($stepId);
        if (!$step || empty($step['case_id'])) return;

        // Tìm bước tiếp theo có sort_order lớn hơn bước vừa hoàn tất
        // Sử dụng ép kiểu (int) để tránh lỗi SQL nếu sort_order không phải là số
        $currentOrder = (int)($step['sort_order'] ?? 0);
        
        $nextStep = $this->stepModel->where('case_id', (int)$step['case_id'])
                                    ->where('sort_order >', $currentOrder)
                                    ->orderBy('sort_order', 'ASC')
                                    ->first();

        if ($nextStep) {
            // Cài đặt Deadline mới cho bước tiếp theo dựa trên ngày bắt đầu hiện tại
            $newDeadline = $this->timelineService->calculateDeadline(new \DateTime(), $nextStep['duration_days']);
            $this->stepModel->update($nextStep['id'], [
                'status' => 'active',
                'deadline' => $newDeadline->format('Y-m-d H:i:s')
            ]);
        } else {
            // HOÀN TẤT TOÀN BỘ (Project Completion):
            // Nếu không còn bước nào, tự động đóng vụ việc với trạng thái "Đã giải quyết".
            $this->caseModel->update($step['case_id'], ['status' => 'da_hoan_thanh']);
        }
    }

    /**
     * Quản lý Phê duyệt: Từ chối yêu cầu hoàn thành Bước.
     * Trả lại bước cho nhân viên yêu cầu kèm theo lý do để sửa đổi/bổ sung.
     */
    public function rejectStep($stepId)
    {
        try {
            $reason = $this->request->getPost('reason') ?? 'Dữ liệu/Tài liệu chưa đáp ứng đúng yêu cầu pháp lý.';
            $this->workflowService->rejectStep($stepId, $reason);
            return redirect()->back()->with('success', 'Yêu cầu hoàn thành bước đã bị từ chối và trả về cho nhân viên xử lý.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cập nhật trạng thái tổng thể hồ sơ (Administrative Overwrite).
     * Thường dùng để thay đổi trạng thái vụ việc thủ công mà không qua quy trình Step-by-Step.
     */
    public function updateStatus($id)
    {
        $newStatus = $this->request->getPost('status');
        $note = $this->request->getPost('note');
        
        $case = $this->caseModel->find($id);
        if (!$case) return redirect()->back()->with('error', 'Không tìm thấy hồ sơ vụ việc.');

        // Kiểm tra quyền (Phải là thành viên vụ việc hoặc có quyền edit_all)
        if (!has_permission('sys.admin') && !has_permission('case.edit_all')) {
            $myEmpId = session()->get('employee_id');
            
            // 1. Kiểm tra gán trực tiếp
            $isDirectlyAssigned = ($case['assigned_lawyer_id'] == $myEmpId || $case['assigned_staff_id'] == $myEmpId);
            
            // 2. Kiểm tra thông qua bảng CaseMember (Vai trò Assignee)
            $isCaseAssignee = model('CaseMemberModel')->where([
                'case_id' => $id,
                'employee_id' => $myEmpId,
                'role_in_case' => 'assignee'
            ])->countAllResults() > 0;

            if (!$isDirectlyAssigned && !$isCaseAssignee) {
                 return redirect()->back()->with('error', 'Chỉ Quản lý hoặc Nhân sự phụ trách chính mới được quyền đổi trạng thái hồ sơ.');
            }
        }

        $oldStatus = $case['status'];
        
        if ($this->caseModel->update($id, ['status' => $newStatus])) {
            $this->logHistory($id, 'cap_nhat_trang_thai', $oldStatus, $newStatus, $note);
            return redirect()->back()->with('success', 'Trạng thái vụ việc đã được cập nhật thủ công.');
        }

        return redirect()->back()->with('error', 'Không thể hoàn thành yêu cầu cập nhật.');
    }

    /**
     * Quản trị: Đồng bộ lại Thưởng (KPI) từ Quy trình sang Vụ việc.
     * Cho phép Admin cập nhật lại định mức thưởng mới nhất nếu Template có sự thay đổi.
     */
    public function syncRewards($id)
    {
        if (!has_permission('sys.admin')) {
            return redirect()->back()->with('error', 'Cảnh báo: Bạn không có quyền thực hiện thao tác đồng bộ tài chính.');
        }

        try {
            if ($this->workflowService->syncRewardsForCase($id)) {
                $this->logHistory($id, 'sync_rewards', null, null, 'Cập nhật định mức thưởng từ Quy trình mẫu.');
                return redirect()->back()->with('success', 'Đã đồng bộ lại định mức thưởng từ quy trình gốc thành công.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi đồng bộ: ' . $e->getMessage());
        }

        return redirect()->back()->with('error', 'Có lỗi xảy ra trong quá trình cập nhật.');
    }

    /**
     * Quản lý hồ sơ số: Số hóa tài liệu.
     * Tiếp nhận tệp tin, phân loại và lưu trữ theo cấu trúc thư mục vụ việc an toàn.
     */
    /**
     * Quản lý hồ sơ số: Số hóa tài liệu và tích hợp DMS tập trung.
     * Tiếp nhận tệp tin, tự động liên kết với vụ việc và khách hàng mà không cần nhập liệu thủ công.
     */
    public function uploadDocument($id)
    {
        $file = $this->request->getFile('doc_file');
        if (!$file) return redirect()->back()->with('error', 'Chưa chọn tệp tin để tải lên.');

        // 1. KIỂM TRA QUYỀN TRUY CẬP (Thành viên vụ việc hoặc Admin)
        $myEmpId = session()->get('employee_id');
        $case = $this->caseModel->find($id);
        if (!$case) return redirect()->back()->with('error', 'Vụ việc không tồn tại.');

        if (!has_permission('sys.admin') && !has_permission('case.manage')) {
            $isAssigned = ($case['assigned_lawyer_id'] == $myEmpId || $case['assigned_staff_id'] == $myEmpId);
            $isMember = model('CaseMemberModel')->where('case_id', $id)->where('employee_id', $myEmpId)->first();
            if (!$isAssigned && !$isMember) {
                return redirect()->back()->with('error', 'Bạn không có quyền tải tài liệu lên hồ sơ này.');
            }
        }

        // 2. CHUẨN BỊ METADATA TỰ ĐỘNG (Automation)
        $data = [
            'document_category' => 'case_file',
            'case_id'           => $id,
            'customer_id'       => $case['customer_id'], // Tự động lấy ID khách hàng từ vụ việc
            'step_id'           => $this->request->getPost('step_id'), // Nếu có gắn với bước quy trình
            'file_name'         => $this->request->getPost('file_name'), // Tên hiển thị tùy chỉnh
            'is_confidential'   => $this->request->getPost('is_confidential') ?? 0,
            'description'       => $this->request->getPost('description') ?: 'Tài liệu bổ sung cho vụ việc: ' . $case['code']
        ];

        // 3. SỬ DỤNG DỊCH VỤ DMS TRUNG TÂM
        $docService = new \App\Services\DocumentService();
        $result = $docService->upload($file, $data);

        if ($result['status'] === 'success') {
            // Ghi vết nhật ký vụ việc
            $this->logHistory($id, 'upload_ho_so', null, $data['file_name'] ?: $file->getClientName(), 'Số hóa và lưu trữ tài liệu vào kho DMS trung tâm.');
            return redirect()->back()->with('success', 'Tài liệu đã được tải lên và đồng bộ vào kho dữ liệu DMS.');
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Nhập tài liệu vào vụ việc từ kho DMS trung tâm (Import from Vault).
     * @param int $caseId ID vụ việc.
     */
    public function importDocument($caseId)
    {
        $docId = $this->request->getPost('document_id');
        if (!$docId) return $this->response->setJSON(['status' => 'error', 'message' => 'Chưa chọn tài liệu.']);

        $case = $this->caseModel->find($caseId);
        if (!$case) return $this->response->setJSON(['status' => 'error', 'message' => 'Vụ việc không tồn tại.']);

        // Cập nhật chỉ số liên kết
        $docModel = new \App\Models\DocumentModel();
        $updated = $docModel->update($docId, [
            'case_id'     => $caseId,
            'customer_id' => $case['customer_id']
        ]);

        if ($updated) {
            $this->logHistory($caseId, 'import_ho_so', null, $docId, 'Nhập tài liệu vào vụ việc từ kho lưu trữ DMS tập trung.');
            return $this->response->setJSON(['status' => 'success', 'message' => 'Đã nhập tài liệu thành công.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Lỗi khi liên kết tài liệu.']);
    }

    /**
     * Hệ thống Ghi nhật ký Hồ sơ (Case Audit Log).
     * Theo dõi mọi biến động của vụ việc phục vụ việc truy vết trách nhiệm và quản trị rủi ro.
     */
    private function logHistory($caseId, $action, $oldValue, $newValue, $note)
    {
        $this->historyModel->save([
            'case_id'    => $caseId,
            'user_id'    => session()->get('user_id'),
            'action'     => $action,
            'old_value'  => $oldValue,
            'new_value'  => $newValue, // Giá trị mới (Ví dụ: Trạng thái mới, tên tài liệu mới...)
            'note'       => $note,      // Lý do hoặc ghi chú chi tiết
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Đồng bộ hóa nhãn dán cho các thực thể (Vụ việc, Khách hàng...).
     * Hỗ trợ cập nhật Real-time thông qua AJAX hoặc Redirect truyền thống.
     */
    public function updateTags($id)
    {
        $tagService = new \App\Services\TagService();
        $tagIds = $this->request->getPost('tag_ids') ?? [];
        $entityType = $this->request->getPost('entity_type') ?: 'cases';
        
        $result = $tagService->syncTags($id, $entityType, $tagIds);
        
        if ($this->request->isAJAX()) {
            if ($result['status'] === 'success') {
                $newTags = $tagService->getTagsByEntity($id, $entityType);
                return $this->response->setJSON([
                    'code' => 0,
                    'message' => 'Cập nhật nhãn thành công',
                    'tags' => $newTags
                ]);
            }
            return $this->response->setJSON(['code' => 1, 'error' => 'Cập nhật nhãn thất bại']);
        }

        if ($result['status'] === 'success') {
            return redirect()->back()->with('success', 'Nhãn dán đã được cập nhật.');
        }

        return redirect()->back()->with('error', 'Cập nhật nhãn dán thất bại.');
    }

    /**
     * Khởi tạo nhãn dán mới và tự động gán vào vụ việc hiện tại.
     */
    public function createTag()
    {
        $tagService = new \App\Services\TagService();
        $postData = $this->request->getPost();
        
        $employeeId = session()->get('employee_id');
        $roleName = session()->get('role_name');

        $result = $tagService->createTag([
            'name' => $postData['name'],
            'color' => $postData['color'],
            'type' => $postData['type'] ?? 'private',
            'module_scope' => $postData['module_scope'] ?? 'all'
        ], $employeeId, $roleName);

        if ($result['status'] === 'success') {
            $tagId = $result['data']['id'];
            $caseId = $postData['ref_case_id'] ?? null;
            if ($caseId) {
                // Tự động gán vào case hiện tại
                $currentTags = $tagService->getTagsByEntity($caseId, 'cases');
                $tagIds = array_column($currentTags, 'id');
                $tagIds[] = $tagId;
                $tagService->syncTags($caseId, 'cases', $tagIds);
            }
            return redirect()->back()->with('success', 'Đã tạo và gán nhãn mới hòan hòan hảo.');
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
