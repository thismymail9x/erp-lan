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
 * Điều hướng và xử lý logic cho Module Quản lý vụ việc.
 * Chịu trách nhiệm về luồng xử lý hồ sơ, Timeline, Deadline và phân quyền nhân sự.
 */
class CaseController extends BaseController
{
    protected $stepModel;
    protected $timelineService;
    protected $commentModel;
    protected $workflowService;

    public function __construct()
    {
        // Khởi tạo các Model và Service cần thiết cho khởi tạo vụ việc
        $this->caseModel = new CaseModel();
        $this->customerModel = new CustomerModel();
        $this->employeeModel = new EmployeeModel();
        $this->historyModel = new CaseHistoryModel();
        $this->documentModel = new DocumentModel();
        $this->stepModel = new CaseStepModel();
        $this->commentModel = new CaseCommentModel();
        $this->timelineService = new \App\Services\CaseTimelineService();
        $this->workflowService = new \App\Services\WorkflowService();
    }

    /**
     * Dashboard Vụ việc (Tổng quát)
     * Hiển thị danh sách vụ việc dựa trên phân quyền của người dùng.
     */
    public function index()
    {
        $caseService = new \App\Services\CaseService();
        
        $search = $this->request->getGet('search') ?? '';
        $sort   = $this->request->getGet('sort') ?? 'id';
        $order  = $this->request->getGet('order') ?? 'desc';
        $perPage = 10;

        $cases = $caseService->getCases($sort, $order, $perPage, $search);
        
        $data = [
            'cases'         => $cases,
            'pager'         => $caseService->getPager(),
            'stats'         => $this->getStats(),
            'search'        => $search,
            'currentSort'   => $sort,
            'currentOrder'  => $order,
            'statusLabels'  => \Config\AppConstants::CASE_STATUS_LABELS,
            'title'         => 'Quản lý vụ việc'
        ];

        if ($this->request->isAJAX()) {
            return view('dashboard/cases/index_table', $data);
        }

        return view('dashboard/cases/index', $data);
    }

    /**
     * Vụ việc của tôi
     * Chế độ xem cá nhân hóa cho nhân viên xử lý hồ sơ.
     */
    public function myCases()
    {
        $employeeId = session()->get('employee_id');
        $caseIds = model('CaseMemberModel')->where('employee_id', $employeeId)->findColumn('case_id');

        $query = $this->caseModel->select('cases.*, customers.name as customer_name, current_step.step_name as current_step_name, current_step.deadline as step_deadline')
                        ->join('customers', 'customers.id = cases.customer_id')
                        ->join('case_steps as current_step', "current_step.case_id = cases.id AND current_step.status IN ('active', 'pending_approval')", 'left')
                        ->groupStart()
                            ->where('cases.assigned_staff_id', $employeeId)
                            ->orWhere('cases.assigned_lawyer_id', $employeeId);
                            
        if (!empty($caseIds)) {
            $query->orWhereIn('cases.id', $caseIds);
        }
        
        $cases = $query->groupEnd()
                        ->groupBy('cases.id')
                        ->orderBy('current_step.deadline', 'ASC') // Ưu tiên hồ sơ sắp đến hạn
                        ->findAll();

        $data = [
            'cases' => $cases,
            'title' => 'Quản lý vụ việc của tôi | L.A.N ERP'
        ];

        return view('dashboard/cases/my_cases', $data);
    }

    /**
     * Thống kê nhanh cho Dashboard
     * Tính toán số lượng hồ sơ theo trạng thái và cảnh báo quá hạn.
     */
    private function getStats()
    {
        $employeeId = session()->get('employee_id');
        $role = session()->get('role_name');
        
        $baseQuery = $this->caseModel;
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
                      ->groupBy('cases.id'); // Đảm bảo đếm số case duy nhất
        }

        return [
            'total' => (clone $baseQuery)->countAllResults(),
            'active' => (clone $baseQuery)->whereIn('status', ['moi_tiep_nhan', 'dang_xu_ly', 'cho_tham_tam'])->countAllResults(),
            'completed' => (clone $baseQuery)->where('status', 'da_giai_quyet')->where('MONTH(updated_at)', date('m'))->countAllResults(),
            'overdue' => (clone $this->stepModel)->join('cases', 'cases.id = case_steps.case_id')
                                ->where('case_steps.completed_at', null)
                                ->where('case_steps.deadline <', date('Y-m-d H:i:s'))
                                ->countAllResults()
        ];
    }

    /**
     * Form tạo mới vụ việc
     * Lấy danh sách khách hàng và nhân sự để phục vụ lựa chọn khi khởi tạo.
     */
    public function create(): string
    {
        $data = [
            'customers' => $this->customerModel->findAll(),
            'lawyers'   => $this->employeeModel->where('department_id', 3)->findAll(), // Lấy từ phòng Pháp lý
            'staffs'    => $this->employeeModel->findAll(),
            'title'     => 'Thêm vụ việc mới'
        ];

        return view('dashboard/cases/create', $data);
    }

    /**
     * Lưu vụ việc mới
     * Xử lý khởi tạo Timeline và tính toán Deadline tổng dựa trên loại vụ việc.
     */
    public function store()
    {
        $input = $this->request->getPost();
        
        // 1. Tự động tính Deadline tổng (Ví dụ: Dân sự 15 ngày chuẩn bị)
        $input['deadline'] = $this->caseModel->calculateDeadline($input['type']);
        $input['status'] = 'moi_tiep_nhan';
        
        // 2. Lưu thông tin cơ bản của vụ việc
        if ($this->caseModel->save($input)) {
            $caseId = $this->caseModel->getInsertID();
            
            // 3. Khởi tạo quy trình (Workflow)
            try {
                $initialized = $this->workflowService->initializeFlowForCase($caseId);
                if (!$initialized) {
                    // Fallback về logic cũ nếu không có Template trong DB
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
                log_message('error', 'Workflow init failed: ' . $e->getMessage());
            }

            // 4. Ghi lại lịch sử hệ thống
            $this->logHistory($caseId, 'tiep_nhan', null, 'moi_tiep_nhan', 'Tạo lead/vụ việc mới.');

            // 5. Phân công người tham gia dựa theo Form
            $approvers = $this->request->getPost('approvers') ?? [];
            $assignees = $this->request->getPost('assignees') ?? [];
            $supporters = $this->request->getPost('supporters') ?? [];

            $caseMemberModel = model('CaseMemberModel');
            $caseMemberModel->syncMembers($caseId, 'approver', $approvers);
            $caseMemberModel->syncMembers($caseId, 'assignee', $assignees);
            $caseMemberModel->syncMembers($caseId, 'supporter', $supporters);
            
            return redirect()->to(base_url('cases'))->with('success', 'Đã khởi tạo vụ việc.');
        }

        return redirect()->back()->withInput()->with('errors', $this->caseModel->errors());
    }

    /**
     * Chi tiết hồ sơ: Tabs, Timeline, Bình luận
     * Giao diện trung tâm để quản lý một vụ việc cụ thể.
     */
    public function show($id)
    {
        // 1. Lấy thông tin vụ việc kèm các JOIN quan trọng
        $case = $this->caseModel->select('cases.*, customers.name as customer_name, lawyer.full_name as lawyer_name, staff.full_name as staff_name, wt.name as template_name')
                    ->join('customers', 'customers.id = cases.customer_id')
                    ->join('employees as lawyer', 'lawyer.id = cases.assigned_lawyer_id', 'left')
                    ->join('employees as staff', 'staff.id = cases.assigned_staff_id', 'left')
                    ->join('workflow_templates as wt', 'wt.id = cases.workflow_template_id', 'left')
                    ->find($id);

        if (!$case) {
            return redirect()->to(base_url('cases'))->with('error', 'Không tìm thấy hồ sơ.');
        }

        // 2. Tổng hợp dữ liệu đa dạng để hiển thị (Lịch sử, Tài liệu, Các bước quy trình)
        $steps = $this->stepModel->where('case_id', $id)->orderBy('sort_order', 'ASC')->findAll();

        // TỰ ĐỘNG KHỞI TẠO NẾU CHƯA CÓ TIMELINE (Cho các vụ việc cũ/import)
        if (empty($steps)) {
            try {
                $this->workflowService->initializeFlowForCase($id);
                $steps = $this->stepModel->where('case_id', $id)->orderBy('sort_order', 'ASC')->findAll();
            } catch (\Exception $e) {
                log_message('error', 'Auto-init timeline failed: ' . $e->getMessage());
            }
        }

        $caseMemberModel = model('CaseMemberModel');
        $members = $caseMemberModel->getMembersByCase($id);
        
        $memberGroups = [
            'approver' => [],
            'assignee' => [],
            'supporter' => []
        ];
        foreach ($members as $m) {
            $memberGroups[$m['role_in_case']][] = $m;
        }

        $data = [
            'case'      => $case,
            'history'   => $this->historyModel->where('case_id', $id)->orderBy('created_at', 'DESC')->findAll(),
            'documents' => $this->documentModel->where('case_id', $id)->findAll(),
            'steps'     => $steps,
            'active_step' => $this->stepModel->getCurrentStep($id),
            'comments'  => $this->commentModel->getCommentsByCase($id), // Bình luận nội bộ
            'lawyers'   => $this->employeeModel->where('department_id', 3)->findAll(),
            'staffs'    => $this->employeeModel->findAll(),
            'members'   => $members,
            'memberGroups' => $memberGroups,
            'title'     => 'Hồ sơ: ' . $case['code']
        ];

        // Xử lý giải mã người chịu trách nhiệm cho bước hiện tại
        if (!empty($data['active_step']['responsible_role'])) {
            $rolesMap = [
                'admin' => 'Admin',
                'truong_phong' => 'Trưởng phòng',
                'nhan_vien' => 'Nhân viên',
                'tu_van' => 'Tư vấn viên'
            ];
            
            $responsible = [];
            $decoded = json_decode($data['active_step']['responsible_role'], true);
            $roleList = is_array($decoded) ? $decoded : [$data['active_step']['responsible_role']];
            
            foreach ($roleList as $item) {
                if (strpos($item, 'role:') === 0) {
                    $roleKey = substr($item, 5);
                    $responsible[] = '<span class="badge-secondary-minimal"><i class="fas fa-users-cog"></i> ' . ($rolesMap[$roleKey] ?? $roleKey) . '</span>';
                } elseif (strpos($item, 'user:') === 0) {
                    $userId = substr($item, 5);
                    // Tìm tên nhân viên (có thể tối ưu bằng cách pluck trước đó)
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

        // Kiểm tra xem quản lý đã xem yêu cầu duyệt mới nhất chưa
        $data['is_approval_read'] = 0;
        if (!empty($data['active_step']) && $data['active_step']['status'] === 'pending_approval') {
            $notificationModel = new \App\Models\NotificationModel();
            // Lấy thông báo approval GẦN NHẤT cho case này do nhân viên này gửi
            $latestApprovalNotif = $notificationModel->where('sender_id', session()->get('user_id'))
                                                     ->where('type', 'approval')
                                                     ->like('link', 'cases/show/' . $id)
                                                     ->orderBy('created_at', 'DESC')
                                                     ->first();
            if ($latestApprovalNotif) {
                // Kiểm tra xem có bất kỳ thông báo nào gửi cùng lúc đã được xem chưa
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
     * Cập nhật danh sách phân công nhân sự tham gia vụ việc
     */
    public function updateMembers($id)
    {
        if (!has_permission('case.manage')) {
            return redirect()->back()->with('error', 'Bạn không có quyền phân công nhân sự.');
        }

        $approvers = $this->request->getPost('approvers') ?? [];
        $assignees = $this->request->getPost('assignees') ?? [];
        $supporters = $this->request->getPost('supporters') ?? [];

        $caseMemberModel = model('CaseMemberModel');
        $caseMemberModel->syncMembers($id, 'approver', $approvers);
        $caseMemberModel->syncMembers($id, 'assignee', $assignees);
        $caseMemberModel->syncMembers($id, 'supporter', $supporters);

        // Ghi lịch sử hành động
        $this->logHistory($id, 'phan_cong_nhan_su', null, null, 'Đã cập nhật danh sách nhân sự tham gia.');

        return redirect()->back()->with('success', 'Đã lưu danh sách nhân sự tham gia vụ việc.');
    }

    /**
     * Thêm bình luận nội bộ

     * Chỉ dành cho nhân viên trao đổi chiến thuật hoặc ghi chú hồ sơ.
     */
    public function addComment($id)
    {
        $content = $this->request->getPost('content');
        if (empty($content)) return redirect()->back();

        $this->commentModel->save([
            'case_id' => $id,
            'user_id' => session()->get('user_id'),
            'content' => $content,
            'is_internal' => 1
        ]);

        return redirect()->back()->with('success', 'Đã thêm ghi chú nội bộ.');
    }


    /**
     * Hoàn thành một bước trong Timeline (Sử dụng Workflow Module)
     */
    public function completeStep($stepId)
    {
        try {
            $role = session()->get('role_name');
            
            // Nếu là nhân viên thì gửi yêu cầu duyệt thay vì hoàn thành ngay
            // Dựa vào phân quyền mặc định hoặc tên role
            if (strpos(strtolower($role), 'nhân viên') !== false || $role == 'Nhân viên chính thức') {
                $this->workflowService->submitForApproval($stepId, $this->request->getPost());
                return redirect()->back()->with('success', 'Đã gửi yêu cầu xét duyệt hoàn thành bước.');
            }

            // 1. Thực hiện nghiệp vụ hoàn thành qua Service cho Quản lý / Admin
            $this->workflowService->completeStep($stepId, $this->request->getPost());

            // 2. Chuyển bước tiếp theo
            $this->triggerNextStep($stepId);

            return redirect()->back()->with('success', 'Đã trực tiếp hoàn thành bước.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Phê duyệt bước
     */
    public function approveStep($stepId)
    {
        try {
            $this->workflowService->approveStep($stepId);
            $this->triggerNextStep($stepId);
            return redirect()->back()->with('success', 'Đã phê duyệt hoàn thành bước.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Kích hoạt bước tiếp theo nội bộ
     */
    private function triggerNextStep($stepId)
    {
        $step = $this->stepModel->find($stepId);
        $nextStep = $this->stepModel->where('case_id', $step['case_id'])
                                    ->where('sort_order >', $step['sort_order'])
                                    ->orderBy('sort_order', 'ASC')
                                    ->first();

        if ($nextStep) {
            $newDeadline = $this->timelineService->calculateDeadline(new \DateTime(), $nextStep['duration_days']);
            $this->stepModel->update($nextStep['id'], [
                'status' => 'active',
                'deadline' => $newDeadline->format('Y-m-d H:i:s')
            ]);
        } else {
            $this->caseModel->update($step['case_id'], ['status' => 'da_giai_quyet']);
        }
    }

    /**
     * Từ chối bước
     */
    public function rejectStep($stepId)
    {
        try {
            $reason = $this->request->getPost('reason') ?? 'Không đáp ứng yêu cầu.';
            $this->workflowService->rejectStep($stepId, $reason);
            return redirect()->back()->with('success', 'Đã từ chối yêu cầu.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cập nhật trạng thái/workflow (Ghi đè thủ công)
     */
    public function updateStatus($id)
    {
        $newStatus = $this->request->getPost('status');
        $note = $this->request->getPost('note');
        
        $case = $this->caseModel->find($id);
        if (!$case) return redirect()->back()->with('error', 'Vụ việc không tồn tại.');

        $oldStatus = $case['status'];
        
        if ($this->caseModel->update($id, ['status' => $newStatus])) {
            $this->logHistory($id, 'cap_nhat_trang_thai', $oldStatus, $newStatus, $note);
            return redirect()->back()->with('success', 'Đã cập nhật trạng thái vụ việc.');
        }

        return redirect()->back()->with('error', 'Không thể cập nhật trạng thái.');
    }


    /**
     * Tải lên tài liệu hồ sơ
     */
    public function uploadDocument($id)
    {
        $file = $this->request->getFile('doc_file');
        
        if ($file->isValid() && !$file->hasMoved()) {
            // 1. Phân tách thư mục lưu trữ theo ID vụ việc
            $newName = $file->getRandomName();
            $file->move(ROOTPATH . 'public/uploads/cases/' . $id, $newName);

            // 2. Lưu thông tin metadata tài liệu
            $docData = [
                'case_id'     => $id,
                'step_id'     => $this->request->getPost('step_id'),
                'file_name'   => $this->request->getPost('file_name') ?: $file->getClientName(),
                'type'        => $this->request->getPost('doc_type'),
                'file_path'   => 'uploads/cases/' . $id . '/' . $newName,
                'uploaded_by' => session()->get('user_id')
            ];

            $this->documentModel->save($docData);
            
            // 3. Ghi lại lịch sử hành động
            $this->logHistory($id, 'upload_ho_so', null, $docData['file_name'], 'Tải lên tài liệu mới.');

            return redirect()->back()->with('success', 'Đã tải tài liệu lên thành công.');
        }

        return redirect()->back()->with('error', 'Lỗi khi tải file.');
    }

    /**
     * Helper: Ghi lịch sử hoạt động hồ sơ
     * Tạo một dòng nhật ký (Audit Log) cho mỗi thay đổi quan trọng trên hồ sơ.
     */
    private function logHistory($caseId, $action, $oldValue, $newValue, $note)
    {
        $this->historyModel->save([
            'case_id'    => $caseId,
            'user_id'    => session()->get('user_id'),
            'action'     => $action,
            'old_value'  => $oldValue,
            'new_value'  => $newValue,
            'note'       => $note,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
