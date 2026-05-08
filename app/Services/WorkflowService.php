<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\CaseStepModel;
use App\Models\WorkflowTemplateModel;
use App\Models\WorkflowStepModel;
use App\Models\DocumentModel;
use DateTime;
use Exception;

/**
 * WorkflowService
 * 
 * Linh hồn của hệ thống tự động hóa Logic nghiệp vụ (ERP Workflow Engine).
 * Chức năng:
 * 1. Khởi tạo cây quy trình (Process Tree) cho từng hồ sơ thực tế từ bản mẫu (Template).
 * 2. Kiểm soát luồng phê duyệt (Approval Workflow) đa cấp.
 * 3. Ràng buộc điều kiện hoàn thành (Tài liệu, Thời gian).
 * 4. Tự động thông báo và leo thang trạng thái (Escalation).
 */
class WorkflowService extends BaseService
{
    protected $caseModel;
    protected $stepModel;
    protected $templateModel;
    protected $templateStepModel;
    protected $timelineService;
    protected $documentModel;
    protected $notificationService;
    protected $roleModel;
    protected $userModel;
    protected $employeeModel;

    public function __construct(
        \App\Models\CaseModel $caseModel = null,
        \App\Models\CaseStepModel $stepModel = null,
        \App\Models\WorkflowTemplateModel $templateModel = null,
        \App\Models\WorkflowStepModel $templateStepModel = null,
        \App\Models\DocumentModel $documentModel = null,
        \App\Models\EmployeeModel $employeeModel = null,
        \App\Models\RoleModel $roleModel = null,
        \App\Models\UserModel $userModel = null,
        NotificationService $notificationService = null,
        CaseTimelineService $timelineService = null
    ) {
        parent::__construct();
        $this->caseModel = $caseModel ?? new \App\Models\CaseModel();
        $this->stepModel = $stepModel ?? new \App\Models\CaseStepModel();
        $this->templateModel = $templateModel ?? new \App\Models\WorkflowTemplateModel();
        $this->templateStepModel = $templateStepModel ?? new \App\Models\WorkflowStepModel();
        $this->documentModel = $documentModel ?? new \App\Models\DocumentModel();
        $this->employeeModel = $employeeModel ?? new \App\Models\EmployeeModel();
        $this->roleModel = $roleModel ?? new \App\Models\RoleModel();
        $this->userModel = $userModel ?? new \App\Models\UserModel();
        $this->notificationService = $notificationService ?? new NotificationService();
        $this->timelineService = $timelineService ?? new CaseTimelineService();
    }

    /**
     * Khởi tạo Quy trình (Workflow) cho một Vụ việc cụ thể.
     * Thao tác này biến một 'Bản mẫu tĩnh' thành 'Các bước thực thi động'.
     * 
     * @param int $caseId ID vụ việc cần áp dụng quy trình.
     * @param int|null $templateId ID template được chọn (mặc định lấy cái mới nhất).
     */
    public function initializeFlowForCase(int $caseId, ?int $templateId = null)
    {
        // 1. Kiểm tra tồn tại và tính hợp lệ của vụ việc
        $case = $this->caseModel->find($caseId);
        if (!$case) throw new Exception("Không tìm thấy vụ việc yêu cầu trên hệ thống.");

        $template = null;
        if ($templateId) {
            $template = $this->templateModel->find($templateId);
        }

        // 2. Cơ chế Auto-Selection: Nếu không chỉ định, lấy Quy trình mẫu đang Active và mới nhất.
        if (!$template) {
            $template = $this->templateModel->where('is_active', 1)
                                           ->orderBy('created_at', 'DESC')
                                           ->first();
        }

        if (!$template) {
            // Trường hợp hệ thống chưa cấu hình bất kỳ quy trình mẫu nào
            return false;
        }

        // 3. Liên kết Vụ việc với Quy trình mẫu (Tracking)
        $this->caseModel->update($caseId, ['workflow_template_id' => $template['id']]);

        // 4. Cơ chế CLONE (Sao chép trình tự):
        // Lấy danh sách các bước định nghĩa sẵn từ Template
        $templateSteps = $this->templateStepModel->where('template_id', $template['id'])
                                               ->orderBy('step_order', 'ASC')
                                               ->findAll();

        // Mốc thời gian bắt đầu chính là lúc khởi tạo vụ việc
        $currentDate = new DateTime($case['created_at'] ?? 'now');
        
        foreach ($templateSteps as $index => $tStep) {
            // Thuật toán cộng dồn Deadline: Bước sau bắt đầu khi bước trước kết thúc dự kiến.
            // TimelineService xử lý việc nhảy qua ngày nghỉ lễ/cuối tuần.
            $deadline = $this->timelineService->calculateDeadline($currentDate, $tStep['duration_days']);
            
            // Hiện thực hóa bước mẫu thành bước thực thi (case_steps)
            $this->stepModel->insert([
                'case_id'               => $caseId,
                'template_id'           => $template['id'],
                'template_step_id'      => $tStep['id'],
                'step_name'             => $tStep['step_name'],
                'sort_order'            => $tStep['step_order'],
                'duration_days'         => $tStep['duration_days'],
                'is_working_day_only'   => $tStep['is_working_day_only'],
                'deadline'              => $deadline->format('Y-m-d H:i:s'),
                // Chỉ kích hoạt (active) cho bước đầu tiên để nhân viên bắt đầu làm việc.
                'status'                => ($index === 0) ? 'active' : 'pending',
                'responsible_role'      => $tStep['responsible_role'],
                'required_documents'    => $tStep['required_documents'], // Danh sách giấy tờ cần quét upload
                'next_step_condition'   => $tStep['next_step_condition'],
                'notification_template' => $tStep['notification_template'],
                'kpi_reward'            => $tStep['kpi_reward'] ?? 0,
                'assigned_to'           => $case['assigned_lawyer_id'] ?: $case['assigned_staff_id']
            ]);

            // Cập nhật mốc 'startDate' cho vòng lặp kế tiếp
            $currentDate = clone $deadline;
        }

        return $template['id'];
    }

    /**
     * Kiểm tra tính tuân thủ về hồ sơ (Regulatory Compliance).
     * Ngăn chặn việc hoàn thành bước nếu thiếu các văn bản/chứng từ bắt buộc.
     * 
     * @param array $step Bản ghi bước hiện tại.
     */
    protected function verifyRequiredDocuments($step)
    {
        if (!empty($step['required_documents'])) {
            $required = json_decode($step['required_documents'], true);
            if (is_array($required) && count($required) > 0) {
                // Lấy danh sách tài liệu thực tế đã lưu trong hệ thống
                $docs = $this->documentModel->where('step_id', $step['id'])->findAll();
                
                // Trích xuất các loại (Type) tài liệu đã có
                $uploadedTypes = array_column($docs, 'type');
                
                foreach ($required as $reqDoc) {
                    $docTypeName = is_array($reqDoc) ? ($reqDoc['name'] ?? '') : $reqDoc;
                    // Logic cơ bản: Nếu bước có yêu cầu chứng từ nhưng Folder bước đó đang trống -> Chặn.
                    if (empty($docs)) {
                        throw new Exception("Yêu cầu nghiệp vụ: Bạn phải tải lên tài liệu [ " . $docTypeName . " ] mới có thể kết thúc bước này.");
                    }
                }
            }
        }
    }

    /**
     * Quy trình Gửi phê duyệt (Submission Flow).
     * Chuyển quyền xử lý từ Nhân viên sang Quản lý để kiểm tra chất lượng.
     */
    public function submitForApproval(int $stepId, array $data = [])
    {
        // 1. Phân tích trạng thái bước
        $step = $this->stepModel->find($stepId);
        if (!$step) throw new Exception("Thông tin bước công việc không tồn tại.");

        // 2. Kiểm duyệt điều kiện tài liệu (Gating)
        $this->verifyRequiredDocuments($step);

        // --- CHỐT KPI CHO NGƯỜI PHỤ TRÁCH CHÍNH (ASSIGNED_TO) ---
        // Bất kể ai bấm nút, KPI vẫn thuộc về người được giao bước này
        $completedBy = $step['assigned_to'];
        
        if (!$completedBy) {
            $case = $this->caseModel->find($step['case_id']);
            $completedBy = $case['assigned_lawyer_id'] ?: $case['assigned_staff_id'];
            
            // Nếu vẫn rỗng, tìm trong bảng CaseMember (Người thực hiện được phân công)
            if (!$completedBy) {
                $member = model('CaseMemberModel')->where(['case_id' => $step['case_id'], 'role_in_case' => 'assignee'])->first();
                if ($member) $completedBy = $member['employee_id'];
            }
            
            // Fallback cuối cùng: Chính là người đang bấm nút (để tránh NULL làm hỏng KPI)
            if (!$completedBy) {
                $completedBy = session()->get('employee_id');
            }
        }

        // 3. Đánh dấu trạng thái 'pending_approval'
        $updateData = [
            'status' => 'pending_approval',
            'completed_by' => $completedBy
        ];

        // Nếu bước này chưa có người phụ trách, cập nhật luôn để chuẩn hóa dữ liệu
        if (empty($step['assigned_to']) && $completedBy) {
            $updateData['assigned_to'] = $completedBy;
        }

        $this->stepModel->update($stepId, $updateData);
        
        if ($this->stepModel->errors()) {
            throw new Exception("Lỗi Database: " . json_encode($this->stepModel->errors()));
        }

        log_message('info', "[WORKFLOW] Step $stepId submitted by user " . session()->get('user_id') . " with completed_by: $completedBy and status: pending_approval");

        // 4. Lưu vết lịch sử (Audit Log Specific to Case)
        $historyModel = model('CaseHistoryModel');
        $historyModel->save([
            'case_id' => $step['case_id'],
            'user_id' => session()->get('user_id') ?: 0,
            'action'  => 'TRÌNH DUYỆT: ' . $step['step_name'],
            'details' => json_encode($data)
        ]);

        // 5. Hệ thống thông báo tự động (Intelligent Notification)
        $case = $this->caseModel->find($step['case_id']);
        $senderName = session()->get('full_name');
        $msg = "Thành viên {$senderName} vừa gửi yêu cầu xét duyệt công việc: [{$step['step_name']}] của hồ sơ {$case['code']}.";
        $link = base_url('cases/show/' . $step['case_id']);
        
        // THU THẬP DANH SÁCH NGƯỜI NHẬN (Recipients)
        $recipientUserIds = [];

        // 1. Luôn thêm Admin vào danh sách giám sát (trừ khi chính Admin là người làm)
        $adminRole = $this->roleModel->where('name', \Config\AppConstants::ROLE_ADMIN)->first();
        if ($adminRole) {
            $adminIds = $this->userModel->where('role_id', $adminRole['id'])->where('active_status', 1)->findColumn('id') ?? [];
            $recipientUserIds = array_merge($recipientUserIds, $adminIds);
        }

        // 2. Thêm người duyệt cụ thể của vụ việc
        $approvers = model('CaseMemberModel')->where('case_id', $step['case_id'])->where('role_in_case', 'approver')->findAll();
        if (count($approvers) > 0) {
            $employeeModel = model('EmployeeModel');
            foreach ($approvers as $app) {
                $emp = $employeeModel->find($app['employee_id']);
                if ($emp && $emp['user_id']) {
                    $recipientUserIds[] = $emp['user_id'];
                }
            }
        } else {
            // Nếu không có người duyệt riêng -> Gửi cho Trưởng phòng/Quản lý
            $this->notificationService->notifyManagerOfEmployee((int)session()->get('employee_id'), "Yêu cầu xét duyệt mới", $msg, 'approval', $link);
        }

        // 3. Gửi thông báo tập trung (Smart Dispatch)
        $this->notificationService->sendToMultiple($recipientUserIds, "Yêu cầu xét duyệt mới", $msg, 'approval', $link);

        return true;
    }

    /**
     * Quản lý Ký duyệt (Approval Decision).
     * Kết thúc công việc và chuẩn bị cho bước kế tiếp.
     */
    public function approveStep(int $stepId)
    {
        $step = $this->stepModel->find($stepId);
        if (!$step) throw new Exception("Không tìm thấy dữ liệu bước.");

        // 1. Chốt thời gian hoàn thành thực tế và người thực hiện
        $completedAt = date('Y-m-d H:i:s');
        
        // Lấy người thực hiện (Ưu tiên người đã submit trước đó, nếu không lấy người được giao)
        $completedBy = !empty($step['completed_by']) ? $step['completed_by'] : (!empty($step['assigned_to']) ? $step['assigned_to'] : session()->get('employee_id'));

        $this->stepModel->update($stepId, [
            'completed_at' => $completedAt,
            'status'       => 'completed',
            'completed_by' => $completedBy
        ]);

        // 2. Ghi nhật ký phê duyệt (Audit Trail)
        $mgrName = session()->get('full_name');
        $historyModel = model('CaseHistoryModel');
        $historyModel->save([
            'case_id' => $step['case_id'],
            'user_id' => session()->get('user_id') ?: 0,
            'action'  => 'ĐÃ PHÊ DUYỆT: ' . $step['step_name'],
            'details' => "Ký duyệt bởi: " . $mgrName
        ]);

        // 3. Quảng bá thông tin cho Ban vụ việc & Admin (Smart Dispatch)
        $case = $this->caseModel->find($step['case_id']);
        $msg = "Quản lý {$mgrName} đã chấp thuận và hoàn thành mục tiêu: [{$step['step_name']}] (Hồ sơ {$case['code']}).";
        $link = base_url('cases/show/' . $step['case_id']);
        
        $this->broadcastWorkflowUpdate($step['case_id'], "Hoàn thành tiến độ", $msg, 'success', $link);

        return true;
    }

    /**
     * Bác bỏ yêu cầu phê duyệt (Rejection Flow).
     * Trả hồ sơ/bước về cho nhân viên sửa lại.
     */
    public function rejectStep(int $stepId, string $reason = '')
    {
        $step = $this->stepModel->find($stepId);
        if (!$step) throw new Exception("Lỗi: Không tìm thấy bước yêu cầu.");

        // 1. Rollback trạng thái về 'active' (Đang thực hiện)
        $this->stepModel->update($stepId, [
            'status' => 'active'
        ]);

        // 2. Ghi nhận sai sót hoặc yêu cầu bổ sung của quản lý
        $historyModel = model('CaseHistoryModel');
        $historyModel->save([
            'case_id' => $step['case_id'],
            'user_id' => session()->get('user_id') ?: 0,
            'action'  => 'TỪ CHỐI DUYỆT: ' . $step['step_name'],
            'details' => json_encode(['reason' => $reason])
        ]);

        // 3. Thông báo khẩn cho thành viên xử lý để kịp thời điều chỉnh
        $case = $this->caseModel->find($step['case_id']);
        $mgrName = session()->get('full_name');
        $msg = "Quản lý {$mgrName} đã TRẢ HỒ SƠ bước [{$step['step_name']}] (Hồ sơ {$case['code']}). Lý do: {$reason}";
        $link = base_url('cases/show/' . $step['case_id']);
        
        $this->broadcastWorkflowUpdate($step['case_id'], "Yêu cầu chỉnh sửa", $msg, 'system', $link);

        return true;
    }

    /**
     * Hoàn thành trực tiếp (Shortcut for Admins).
     * Tiết kiệm thời gian khi Admin tự thao tác hoặc trong các quy trình tư vấn nhanh.
     */
    public function completeStep(int $stepId, array $data = [])
    {
        $step = $this->stepModel->find($stepId);
        if (!$step) throw new Exception("Không tìm thấy bước.");

        // Chốt KPI cho người phụ trách bước
        $completedBy = $step['assigned_to'];
        if (!$completedBy) {
            $case = $this->caseModel->find($step['case_id']);
            $completedBy = $case['assigned_lawyer_id'] ?: $case['assigned_staff_id'];
        }

        $this->stepModel->update($stepId, [
            'completed_at' => date('Y-m-d H:i:s'),
            'status'       => 'completed',
            'completed_by' => $completedBy
        ]);

        $mgrName = session()->get('full_name');
        $historyModel = model('CaseHistoryModel');
        $historyModel->save([
            'case_id' => $step['case_id'],
            'user_id' => session()->get('user_id') ?: 0,
            'action'  => 'HOÀN THÀNH (FAST-TRACK): ' . $step['step_name'],
            'details' => json_encode($data)
        ]);

        // --- THÊM THÔNG BÁO CHO FAST-TRACK ---
        $case = $this->caseModel->find($step['case_id']);
        $msg = "Thành viên {$mgrName} đã HOÀN THÀNH TRỰC TIẾP bước [{$step['step_name']}] (Hồ sơ {$case['code']}).";
        $link = base_url('cases/show/' . $step['case_id']);
        
        $this->broadcastWorkflowUpdate($step['case_id'], "Hoàn thành tiến độ", $msg, 'success', $link);

        return true;
    }

    /**
     * Lấy toàn bộ danh mục Quy trình mẫu với bộ lọc nâng cao & Phân trang.
     */
    public function getAllTemplates(string $search = '', string $status = '', int $perPage = 20)
    {
        $query = $this->templateModel->orderBy('created_at', 'DESC');

        // BỘ LỌC TÌM KIẾM (Mã hoặc Tên)
        if (!empty($search)) {
            $query->groupStart()
                  ->like('workflow_templates.name', $search)
                  ->orLike('workflow_templates.code', $search)
                  ->groupEnd();
        }

        // BỘ LỌC TRẠNG THÁI
        if ($status !== '') {
            $query->where('workflow_templates.is_active', (int)$status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Lấy công cụ phân trang (Pager) cho View.
     */
    public function getPager()
    {
        return $this->templateModel->pager;
    }

    /**
     * Truy xuất Metadata của một quy trình mẫu.
     */
    public function getTemplateById($id)
    {
        return $this->templateModel->find($id);
    }

    /**
     * Lấy danh sách trình tự các bước thuộc về một bản mẫu.
     */
    public function getStepsByTemplateId($templateId)
    {
        return $this->templateStepModel->where('template_id', $templateId)
                                      ->orderBy('step_order', 'ASC')
                                      ->findAll();
    }

    /**
     * Lưu trữ bản mô tả Quy trình mới.
     */
    public function createTemplate(array $data)
    {
        return $this->templateModel->insert($data);
    }

    /**
     * Gửi thông báo cho toàn bộ thành viên ban vụ việc (Public Wrapper).
     */
    public function notifyCaseMembers(int $caseId, string $title, string $msg, string $type = 'task', string $link = '')
    {
        return $this->broadcastWorkflowUpdate($caseId, $title, $msg, $type, $link);
    }

    /**
     * Helper: Phối hợp thông báo cho cả thành viên vụ việc và ban quản trị (Admin).
     * Đảm bảo không trùng lặp và loại bỏ người gửi.
     */
    private function broadcastWorkflowUpdate(int $caseId, string $title, string $msg, string $type, string $link)
    {
        $recipientUserIds = [];

        // 1. Lấy danh sách Admin (User IDs)
        $adminRole = $this->roleModel->where('name', \Config\AppConstants::ROLE_ADMIN)->first();
        if ($adminRole) {
            $recipientUserIds = $this->userModel->where('role_id', $adminRole['id'])->where('active_status', 1)->findColumn('id') ?? [];
        }

        // 2. Lấy danh sách thành viên & nhân sự phụ trách (Employee IDs -> User IDs)
        $caseMemberModel = model('CaseMemberModel');
        $members = $caseMemberModel->where('case_id', $caseId)->findAll();
        
        $case = $this->caseModel->find($caseId);
        $legacyEmpIds = [];
        if (!empty($case['assigned_lawyer_id'])) $legacyEmpIds[] = $case['assigned_lawyer_id'];
        if (!empty($case['assigned_staff_id'])) $legacyEmpIds[] = $case['assigned_staff_id'];

        $allEmpIds = array_unique(array_merge(array_column($members, 'employee_id'), $legacyEmpIds));

        foreach ($allEmpIds as $empId) {
            $emp = $this->employeeModel->find($empId);
            if ($emp && !empty($emp['user_id'])) {
                $recipientUserIds[] = $emp['user_id'];
            }
        }

        // 3. CHỐT CHẶN CUỐI: Lọc trùng và loại bỏ người gửi (Sender)
        $recipientUserIds = array_unique($recipientUserIds);
        $senderId = (function_exists('session') && session()->has('user_id')) ? session()->get('user_id') : null;

        if ($senderId) {
            $recipientUserIds = array_filter($recipientUserIds, function($id) use ($senderId) {
                return $id != $senderId;
            });
        }

        if (!empty($recipientUserIds)) {
            $this->notificationService->sendToMultiple(array_values($recipientUserIds), $title, $msg, $type, $link);
        }
    }

    /**
     * Thuật toán Đồng bộ hóa quy trình (Dynamic Syncing).
     * Giải quyết bài toán thay đổi trình tự/số lượng bước mà vẫn đảm bảo tính nhất quán dữ liệu.
     * Sử dụng Transaction để bảo vệ dữ liệu khi có lỗi xảy ra giữa chừng.
     */
    public function syncSteps(int $templateId, array $steps)
    {
        $db = \Config\Database::connect();
        $db->transStart(); // Bắt đầu giao dịch an toàn

        // 1. Wipe-out: Xóa sạch cấu hình cũ của Template này để ghi đè danh sách mới.
        $this->templateStepModel->where('template_id', $templateId)->delete(null, true);

        $totalDays = 0;
        foreach ($steps as $index => $step) {
            $step['template_id'] = $templateId;
            $step['step_order']  = $index; // Sắp xếp lại chỉ số trình tự (0-indexed)
            
            // Xử lý nén dữ liệu phức tạp (JSON) để lưu trữ vào MySQL
            if (isset($step['required_documents']) && is_array($step['required_documents'])) {
                $step['required_documents'] = json_encode($step['required_documents']);
            }
            if (isset($step['responsible_role']) && is_array($step['responsible_role'])) {
                $step['responsible_role'] = json_encode($step['responsible_role']);
            }

            // Chèn từng bước theo trình tự mới
            $this->templateStepModel->insert($step);
            // Tính toán lại tổng thời gian thực hiện của cả chu trình
            $totalDays += (int)$step['duration_days'];
        }

        // 2. Cập nhật chỉ số hiệu quả (Total Days) vào bảng Template mẹ.
        $this->templateModel->update($templateId, ['total_estimated_days' => $totalDays]);

        $db->transComplete(); // Hoàn tất giao dịch
        return $db->transStatus();
    }

    /**
     * Cập nhật thông tin nhận dạng Quy trình mẫu.
     */
    public function updateTemplate(int $id, array $data)
    {
        // RÀO CẢN LOGIC: Ép kiểu ID vào quy tắc Validate để bỏ qua bản ghi hiện tại khi check mã Code trùng lặp
        $this->templateModel->setValidationRule('code', "required|is_unique[workflow_templates.code,id,$id]");
        
        return $this->templateModel->update($id, $data);
    }

    /**
     * Xóa bỏ cấu hình Quy trình.
     */
    public function deleteTemplate($id)
    {
        return $this->templateModel->delete($id);
    }

    /**
     * TÍNH NĂNG CLONE (NHÂN BẢN): 
     * Sao chép toàn bộ cấu trúc quy trình bao gồm cả Template và các Step chi tiết.
     */
    public function duplicateTemplate($id)
    {
        $oldTemplate = $this->templateModel->find($id);
        if (!$oldTemplate) {
            return false;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Chắt lọc dữ liệu Template mẹ
        $allowed = $this->templateModel->allowedFields;
        $newTemplateData = [];
        foreach ($allowed as $field) {
            if (isset($oldTemplate[$field])) {
                $newTemplateData[$field] = $oldTemplate[$field];
            }
        }

        $newTemplateData['name'] = $oldTemplate['name'] . ' (Bản sao)';
        $newTemplateData['code'] = substr($oldTemplate['code'], 0, 30) . '_CLON_' . time();
        $newTemplateData['created_at'] = date('Y-m-d H:i:s');
        
        try {
            $newTemplateId = $this->templateModel->insert($newTemplateData);
            
            if (!$newTemplateId) {
                return false; 
            }
        } catch (\Exception $e) {
            log_message('error', 'Lỗi DB khi nhân bản: ' . $e->getMessage());
            return false;
        }

        // 2. Nhân bản toàn bộ các bước con (Steps) hòan hảo
        $oldSteps = $this->templateStepModel->where('template_id', $id)->findAll();
        foreach ($oldSteps as $step) {
            $newStepData = $step;
            unset($newStepData['id']); 
            $newStepData['template_id'] = $newTemplateId; 
            
            try {
                if (!$this->templateStepModel->insert($newStepData)) {
                    log_message('error', 'Lỗi Validation Step: ' . json_encode($this->templateStepModel->errors()));
                    return false;
                }
            } catch (\Exception $e) {
                log_message('error', 'Lỗi SQL Step: ' . $e->getMessage());
                return false;
            }
        }

        $db->transComplete();
        return $db->transStatus() ? $newTemplateId : false;
    }
    /**
     * Truy xuất các sai sót phát sinh trong quá trình tương tác Model (vd: Validation errors).
     */
    public function getErrors()
    {
        return $this->templateModel->errors();
    }

    /**
     * Tự động kiểm tra hạn chót toàn hệ thống (Daily Task logic).
     * Thuật toán vận hành:
     * 1. Quét toàn bộ các bước (steps) đang ở trạng thái 'active' (đang thực hiện) và chưa có ngày hoàn thành.
     * 2. Tính toán khoảng cách thời gian giữa hiện tại và Hạn chót (Deadline).
     * 3. Phân loại để xử lý:
     *    - Sắp đến hạn (còn < 24h): Nhắc nhở để nhân viên chủ động dứt điểm công việc.
     *    - Đã quá hạn: Nhắc nhở lặp lại hàng ngày (Daily Reminder) cho đến khi hoàn thành.
     * 4. Sử dụng cột 'last_overdue_notified_at' để kiểm soát việc nhắc nhở đúng 1 lần/ngày, tránh spam gây khó chịu cho nhân sự.
     */
    public function checkStepDeadlines()
    {
        // Chỉ lấy các bước đang hoạt động và chưa xong
        $activeSteps = $this->stepModel->where('status', 'active')
                                       ->where('completed_at', null)
                                       ->findAll();

        $now = new \DateTime('now');
        $today = $now->format('Y-m-d');

        foreach ($activeSteps as $step) {
            $deadline = new \DateTime($step['deadline']);
            // Tính toán số giờ còn lại (Số âm tức là đã quá hạn)
            $hoursLeft = ($deadline->getTimestamp() - $now->getTimestamp()) / 3600;

            // TRƯỜNG HỢP 1: Cảnh báo sớm (Sắp đến hạn trong vòng 24h tới)
            // Chỉ cảnh báo 1 lần duy nhất để nhân viên nắm bắt thông tin.
            if ($hoursLeft > 0 && $hoursLeft <= 24 && $step['overdue_notified'] == 0) {
                $this->notifyUpcomingDeadline($step);
            }
            // TRƯỜNG HỢP 2: Đã quá hạn (Deadline < Hiện tại)
            // Áp dụng cơ chế "Nhắc nhở dai dẳng": Mỗi ngày bắn 1 thông báo cho đến khi bước được tích Hoàn thành.
            elseif ($hoursLeft <= 0) {
                // Kiểm tra xem ngày hôm nay đã gửi thông báo quá hạn chưa?
                // Nếu 'last_overdue_notified_at' khác ngày hôm nay, nghĩa là cần phải gửi lượt nhắc nhở mới.
                $lastNotified = $step['last_overdue_notified_at'] ?? null;
                if ($lastNotified !== $today) {
                    $this->handleOverdueStep($step);
                }
            }
        }
    }

    /**
     * Gửi nhắc nhở cho nhân viên phụ trách khi bước sắp đến hạn.
     */
    private function notifyUpcomingDeadline($step)
    {
        $case = $this->caseModel->find($step['case_id']);
        $title = "Cảnh báo: Sắp đến hạn bước";
        $msg = "Bước [{$step['step_name']}] của hồ sơ '{$case['code']}' chỉ còn chưa đầy 24h để hoàn thành. Hãy kiểm tra ngay!";
        $link = base_url('cases/show/' . $step['case_id']);
        
        $this->broadcastWorkflowUpdate($step['case_id'], $title, $msg, 'warning', $link);
    }

    /**
     * Quy trình xử lý hồ sơ Quá hạn (Escalation & Penalty logic).
     * Khi một bước bị quá hạn, hệ thống sẽ:
     * 1. Gửi cảnh báo nghiêm trọng cho nhân sự phụ trách và ban quản lý.
     * 2. Đánh dấu trạng thái quá hạn để phục vụ báo cáo KPI cuối tháng.
     * 3. Thiết lập cơ chế nhắc nhở lặp lại hàng ngày (thông qua last_overdue_notified_at).
     */
    private function handleOverdueStep($step)
    {
        $case = $this->caseModel->find($step['case_id']);
        $title = "Báo Cáo: QUÁ HẠN tiến độ";
        $msg = "CẢNH BÁO: Bước [{$step['step_name']}] của hồ sơ '{$case['code']}' đã QUÁ HẠN. Đề nghị nhân sự dứt điểm ngay để tránh ảnh hưởng đến KPI tổng thể.";
        $link = base_url('cases/show/' . $step['case_id']);
        
        // 1. Đánh dấu đã báo quá hạn và cập nhật ngày nhắc nhở cuối cùng
        $this->stepModel->update($step['id'], [
            'overdue_notified' => 1,
            'last_overdue_notified_at' => date('Y-m-d')
        ]);

        // 2. Tìm người phụ trách để trừ điểm tiềm năng
        // 3. Thông báo leo thang (Escalation Dispatch)
        $recipientIds = [];
        
        // 3.1 Thêm Admin & Manager (Ban quản lý)
        $adminRole = $this->roleModel->where('name', \Config\AppConstants::ROLE_ADMIN)->first();
        $managerRole = $this->roleModel->where('name', \Config\AppConstants::ROLE_TRUONG_PHONG)->first();

        if ($adminRole) {
            $recipientIds = $this->userModel->where('role_id', $adminRole['id'])->where('active_status', 1)->findColumn('id') ?? [];
        }
        if ($managerRole) {
            $managerIds = $this->userModel->where('role_id', $managerRole['id'])->where('active_status', 1)->findColumn('id') ?? [];
            $recipientIds = array_merge($recipientIds, $managerIds);
        }
        
        // 3.2 Thêm thành viên vụ việc
        $members = model('CaseMemberModel')->where('case_id', $step['case_id'])->findAll();
        foreach ($members as $m) {
            $emp = $this->employeeModel->find($m['employee_id']);
            if ($emp && $emp['user_id']) $recipientIds[] = $emp['user_id'];
        }
        
        // Bổ sung nhân sự chính
        if (!empty($case['assigned_lawyer_id'])) {
            $l = $this->employeeModel->find($case['assigned_lawyer_id']);
            if ($l && $l['user_id']) $recipientIds[] = $l['user_id'];
        }
        if (!empty($case['assigned_staff_id'])) {
            $s = $this->employeeModel->find($case['assigned_staff_id']);
            if ($s && $s['user_id']) $recipientIds[] = $s['user_id'];
        }
        $this->notificationService->sendToMultiple($recipientIds, $title, $msg, 'danger', $link);
    }

    /**
     * Đồng bộ lại mức thưởng KPI từ Quy trình mẫu vào Vụ việc thực tế.
     * Chỉ thực hiện khi có yêu cầu từ Quản trị viên (Admin).
     */
    public function syncRewardsForCase(int $caseId)
    {
        $steps = $this->stepModel->where('case_id', $caseId)->findAll();
        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($steps as $step) {
            if (!empty($step['template_step_id'])) {
                // Lấy giá trị mới nhất từ bảng Template gốc
                $tStep = $this->templateStepModel->find($step['template_step_id']);
                if ($tStep) {
                    $this->stepModel->update($step['id'], [
                        'kpi_reward' => $tStep['kpi_reward']
                    ]);
                }
            }
        }

        $db->transComplete();
        return $db->transStatus();
    }

    /**
     * Đổi Quy trình (Workflow) cho một Vụ việc.
     * Áp dụng nguyên tắc ngặt nghèo: Chỉ đổi khi chưa có bước nào hoàn thành.
     * 
     * @param int $caseId ID Vụ việc
     * @param int $newTemplateId ID Quy trình mẫu mới
     * @throws Exception
     */
    public function changeWorkflowForCase(int $caseId, int $newTemplateId)
    {
        $case = $this->caseModel->find($caseId);
        if (!$case) throw new Exception("Không tìm thấy vụ việc.");

        $newTemplate = $this->templateModel->find($newTemplateId);
        if (!$newTemplate) throw new Exception("Không tìm thấy quy trình mẫu mục tiêu.");

        // Kiểm tra điều kiện tiên quyết: Không có bất kỳ bước nào đã hoàn thành
        $completedSteps = $this->stepModel->where('case_id', $caseId)
                                          ->where('status', 'completed')
                                          ->countAllResults();
        if ($completedSteps > 0) {
            throw new Exception("Không thể thay đổi quy trình: Vụ việc này đã có bước hoàn thành. Đổi quy trình trực tiếp sẽ gây sai lệch dữ liệu tiến độ và KPI.");
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Bước 1.1: Gỡ liên kết của các tài liệu đính kèm với những bước công việc cũ (chuyển thành tài liệu chung của vụ việc)
            $documentModel = model('DocumentModel');
            $documentModel->where('case_id', $caseId)->set(['step_id' => null])->update();

            // Bước 1.2: Xóa trắng toàn bộ lịch trình cũ (do nó chưa được thực hiện hoàn tất)
            $this->stepModel->where('case_id', $caseId)->delete(null, true);

            // Bước 2: Khởi tạo lại tiến trình từ template mới
            $this->initializeFlowForCase($caseId, $newTemplateId);

            // Bước 3: Lưu lại lịch sử theo dõi
            $historyModel = model('CaseHistoryModel');
            $historyModel->insert([
                'case_id'   => $caseId,
                'user_id'   => session()->get('user_id') ?: 0,
                'action'    => 'doi_quy_trinh',
                'old_value' => $case['workflow_template_id'],
                'new_value' => $newTemplateId,
                'note'      => 'Quản trị viên đã đổi từ quy trình cơ bản sang quy trình: ' . $newTemplate['name'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $db->transComplete();
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }

        if ($db->transStatus() === false) {
            throw new Exception("Lỗi hệ thống khi cập nhật đổi quy trình.");
        }

        return true;
    }
}
