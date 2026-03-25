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
    protected $stepModel;
    protected $timelineService;
    protected $commentModel;
    protected $workflowService;

    public function __construct()
    {
        // KHỞI TẠO HỆ SINH THÁI DATA:
        // Nạp tất cả các Model và Service nòng cốt phục vụ quản trị hồ sơ.
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
        $perPage = 10; // Giới hạn bản ghi mỗi trang để tối ưu UI

        // 3. Lấy dữ liệu hồ sơ (Chỉ lấy những hồ sơ User được quyền xem - Logic nằm trong Service)
        $cases = $caseService->getCases($sort, $order, $perPage, $search);
        
        // 4. Chuẩn bị dữ liệu hiển thị (Data Aggregation)
        $data = [
            'cases'         => $cases,
            'pager'         => $caseService->getPager(),             // Phân trang
            'stats'         => $this->getStats(),                   // Các chỉ số KPI (Hoàn thành, Quá hạn,...)
            'search'        => $search,
            'currentSort'   => $sort,
            'currentOrder'  => $order,
            'statusLabels'  => \Config\AppConstants::CASE_STATUS_LABELS, // Nhãn trạng thái tiếng Việt
            'title'         => 'Quản lý vụ việc & Hồ sơ pháp lý | L.A.N ERP'
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
     * Thuật toán thống kê phân tích (Analytics Statistics).
     * Tính toán số lượng hồ sơ theo trạng thái dựa trên phạm vi quyền hạn của User.
     * 
     * @return array Các chỉ số Dashboard nhanh.
     */
    private function getStats()
    {
        $employeeId = session()->get('employee_id');
        $role = session()->get('role_name');
        
        $baseQuery = $this->caseModel;
        
        // PHÂN QUYỀN THỐNG KÊ (Dynamic Scope):
        // Nếu không có quyền quản trị tối cao, hệ thống chỉ đếm các số liệu trong phạm vi hồ sơ cá nhân.
        if (!in_array($role, \Config\AppConstants::PRIVILEGED_ROLES)) {
            $caseIds = model('CaseMemberModel')->where('employee_id', $employeeId)->findColumn('case_id');
            
            $baseQuery = clone $this->caseModel; 
            $baseQuery->groupStart()
                          ->where('cases.assigned_staff_id', $employeeId)
                          ->orWhere('cases.assigned_lawyer_id', $employeeId);
                          
            if (!empty($caseIds)) {
                $baseQuery->orWhereIn('cases.id', $caseIds);
            }
            
            $baseQuery->groupEnd()
                      ->groupBy('cases.id');
        }

        return [
            'total' => (clone $baseQuery)->countAllResults(), // Quy mô hồ sơ đang nắm giữ
            
            // Hồ sơ "Sống": Đang trong quá trình xử lý, chưa đóng
            'active' => (clone $baseQuery)->whereIn('status', ['moi_tiep_nhan', 'dang_xu_ly', 'cho_tham_tam'])->countAllResults(),
            
            // Tỷ lệ hoàn thành trong tháng (Dùng cho báo cáo hiệu suất cá nhân/phòng ban)
            'completed' => (clone $baseQuery)->where('status', 'da_giai_quyet')->where('MONTH(updated_at)', date('m'))->countAllResults(),
            
            // CẢNH BÁO QUÁ HẠN (Critical Warning):
            // Thống kê số lượng các bước (Steps) đã vượt quá Deadline mà chưa hoàn tất.
            'overdue' => (clone $this->stepModel)->join('cases', 'cases.id = case_steps.case_id')
                                ->where('case_steps.completed_at', null)
                                ->where('case_steps.deadline <', date('Y-m-d H:i:s'))
                                ->countAllResults()
        ];
    }

    /**
     * Giao diện khởi tạo vụ việc mới.
     * Chuẩn bị danh sách Khách hàng, Luật sư và các Mẫu quy trình (Workflow Templates).
     */
    public function create(): string
    {
        $templateModel = new \App\Models\WorkflowTemplateModel();
        $data = [
            'customers' => $this->customerModel->findAll(),
            'lawyers'   => $this->employeeModel->where('department_id', 3)->findAll(), // Ưu tiên phòng dịch vụ pháp lý
            'staffs'    => $this->employeeModel->findAll(),
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
        $input = $this->request->getPost();
        $templateId = $this->request->getPost('workflow_template_id');

        // --- BƯỚC 1: TỰ ĐỘNG HÓA DEADLINE (Workflow Automation) ---
        // Nếu chọn Template, hệ thống sẽ tự động tính Deadline tổng dựa trên tổng ngày dự kiến của mẫu quy trình.
        if ($templateId) {
            $templateModel = new \App\Models\WorkflowTemplateModel();
            $template = $templateModel->find($templateId);
            if ($template) {
                $input['type'] = $template['case_type'];
                $days = $template['total_estimated_days'] ?: 30; // Mặc định 30 ngày nếu không định nghĩa
                // Thuật toán calculateDeadline tự động trừ Thứ 7/Chủ nhật
                $input['deadline'] = $this->timelineService->calculateDeadline(new \DateTime(), $days)->format('Y-m-d H:i:s');
            }
        } else {
            // Trường hợp hồ sơ tự do (Custom Case): Deadline sẽ tính theo loại vụ việc mặc định (Config driven)
            $input['type'] = $input['type'] ?? 'khac';
            $input['deadline'] = $this->caseModel->calculateDeadline($input['type']);
        }

        $input['status'] = 'moi_tiep_nhan'; // Trạng thái khởi tạo
        
        // --- BƯỚC 2: GHI DANH VÀO CƠ SỞ DỮ LIỆU ---
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
        // 1. Thu thập dữ liệu gốc từ nhiều nguồn liên quan
        $case = $this->caseModel->select('cases.*, customers.name as customer_name, lawyer.full_name as lawyer_name, staff.full_name as staff_name, wt.name as template_name')
                    ->join('customers', 'customers.id = cases.customer_id')
                    ->join('employees as lawyer', 'lawyer.id = cases.assigned_lawyer_id', 'left')
                    ->join('employees as staff', 'staff.id = cases.assigned_staff_id', 'left')
                    ->join('workflow_templates as wt', 'wt.id = cases.workflow_template_id', 'left')
                    ->find($id);

        if (!$case) {
            return redirect()->to(base_url('cases'))->with('error', 'Dữ liệu hồ sơ không còn tồn tại trên hệ thống.');
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
            'lawyers'   => $this->employeeModel->where('department_id', 3)->findAll(),
            'staffs'    => $this->employeeModel->findAll(),
            'members'   => $members,
            'memberGroups' => $memberGroups,
            'isApprover' => false, // User hiện tại có phải người duyệt (Approver) ko?
            'isAssignee' => false, // User hiện tại có phải người thực hiện chính ko?
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
     * Quản lý Phân công nhân sự tham gia (Resource Management).
     * Cho phép Quản trị viên thay đổi cơ cấu nhân sự xử lý hồ sơ.
     */
    public function updateMembers($id)
    {
        // Kiểm tra quyền hạn manage hồ sơ
        if (!has_permission('case.manage')) {
            return redirect()->back()->with('error', 'Bạn không được phân quyền để thay đổi nhân sự vụ việc này.');
        }

        $approvers = $this->request->getPost('approvers') ?? [];
        $assignees = $this->request->getPost('assignees') ?? [];
        $supporters = $this->request->getPost('supporters') ?? [];

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

        $this->commentModel->save([
            'case_id' => $id,
            'user_id' => session()->get('user_id'),
            'content' => $content,
            'is_internal' => 1 // Mặc định là ghi chú nội bộ của công ty
        ]);

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
        
        // Tìm bước tiếp theo có sort_order lớn hơn bước vừa hoàn tất
        $nextStep = $this->stepModel->where('case_id', $step['case_id'])
                                    ->where('sort_order >', $step['sort_order'])
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
            $this->caseModel->update($step['case_id'], ['status' => 'da_giai_quyet']);
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

        $oldStatus = $case['status'];
        
        if ($this->caseModel->update($id, ['status' => $newStatus])) {
            $this->logHistory($id, 'cap_nhat_trang_thai', $oldStatus, $newStatus, $note);
            return redirect()->back()->with('success', 'Trạng thái vụ việc đã được cập nhật thủ công.');
        }

        return redirect()->back()->with('error', 'Không thể hoàn thành yêu cầu cập nhật.');
    }

    /**
     * Quản lý hồ sơ số: Số hóa tài liệu.
     * Tiếp nhận tệp tin, phân loại và lưu trữ theo cấu trúc thư mục vụ việc an toàn.
     */
    public function uploadDocument($id)
    {
        $file = $this->request->getFile('doc_file');
        
        if ($file->isValid() && !$file->hasMoved()) {
            // 1. CHẾ ĐỘ LƯU TRỮ PHÂN TÁN (Scoped Storage):
            // Mỗi vụ việc có folder riêng (public/uploads/cases/{id}) để dễ kiểm soát dung lượng và phân quyền.
            $newName = $file->getRandomName();
            $file->move(ROOTPATH . 'public/uploads/cases/' . $id, $newName);

            // 2. Định danh và liên kết tài liệu vào Timeline (Document Association)
            $docData = [
                'case_id'     => $id,
                'step_id'     => $this->request->getPost('step_id'), // Gắn vào Bước quy trình cụ thể
                'file_name'   => $this->request->getPost('file_name') ?: $file->getClientName(),
                'type'        => $this->request->getPost('doc_type'),
                'file_path'   => 'uploads/cases/' . $id . '/' . $newName,
                'uploaded_by' => session()->get('user_id')
            ];

            $this->documentModel->save($docData);
            
            // 3. Ghi vết Audit Log
            $this->logHistory($id, 'upload_ho_so', null, $docData['file_name'], 'Bổ sung tài liệu pháp lý vào hồ sơ số của dự án.');

            return redirect()->back()->with('success', 'Tài liệu đã được số hóa và lưu trữ an toàn.');
        }

        return redirect()->back()->with('error', 'Tệp tin không hợp lệ hoặc lỗi trong quá trình truyền dữ liệu.');
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
}
