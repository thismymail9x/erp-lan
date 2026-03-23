<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\CaseStepModel;
use App\Models\WorkflowTemplateModel;
use App\Models\WorkflowStepModel;
use App\Models\DocumentModel;
use DateTime;
use Exception;

class WorkflowService extends BaseService
{
    protected $caseModel;
    protected $stepModel;
    protected $templateModel;
    protected $templateStepModel;
    protected $timelineService;
    protected $documentModel;
    protected $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->caseModel = new CaseModel();
        $this->stepModel = new CaseStepModel();
        $this->templateModel = new WorkflowTemplateModel();
        $this->templateStepModel = new WorkflowStepModel();
        $this->timelineService = new CaseTimelineService();
        $this->documentModel = new DocumentModel();
        $this->notificationService = new NotificationService();
    }

    /**
     * Khởi tạo quy trình cho một Case dựa trên loại vụ việc
     */
    public function initializeFlowForCase(int $caseId)
    {
        $case = $this->caseModel->find($caseId);
        if (!$case) throw new Exception("Không tìm thấy vụ việc.");

        // Tìm template đang active cho loại vụ việc này
        $template = $this->templateModel->where('case_type', $case['type'])
                                      ->where('is_active', 1)
                                      ->orderBy('version', 'DESC')
                                      ->first();

        // Nếu không có template DB, có thể fallback về hardcoded hoặc báo lỗi
        if (!$template) {
            // Log warning or handle fallback
            return false;
        }

        // Cập nhật Case với link tới template
        $this->caseModel->update($caseId, ['workflow_template_id' => $template['id']]);

        // 2. Clone Steps từ Template sang Case Steps
        $templateSteps = $this->templateStepModel->where('template_id', $template['id'])
                                               ->orderBy('step_order', 'ASC')
                                               ->findAll();

        $currentDate = new DateTime($case['created_at'] ?? 'now');
        
        foreach ($templateSteps as $index => $tStep) {
            // Tính deadline thực tế (bỏ qua T7/CN nếu cần)
            $deadline = $this->timelineService->calculateDeadline($currentDate, $tStep['duration_days']);
            
            $this->stepModel->insert([
                'case_id'               => $caseId,
                'template_id'           => $template['id'],
                'template_step_id'      => $tStep['id'],
                'step_name'             => $tStep['step_name'],
                'sort_order'            => $tStep['step_order'],
                'duration_days'         => $tStep['duration_days'],
                'is_working_day_only'   => $tStep['is_working_day_only'],
                'deadline'              => $deadline->format('Y-m-d H:i:s'),
                'status'                => ($index === 0) ? 'active' : 'pending',
                'responsible_role'      => $tStep['responsible_role'],
                'required_documents'    => $tStep['required_documents'],
                'next_step_condition'   => $tStep['next_step_condition'],
                'notification_template' => $tStep['notification_template']
            ]);

            // Ngày bắt đầu bước tiếp theo là deadline của bước này
            $currentDate = clone $deadline;
        }

        return $template['id'];
    }

    /**
     * Xác minh xem bước đã đủ tài liệu yêu cầu chưa.
     */
    protected function verifyRequiredDocuments($step)
    {
        if (!empty($step['required_documents'])) {
            $required = json_decode($step['required_documents'], true);
            if (is_array($required) && count($required) > 0) {
                // Kiểm tra xem đã có tài liệu nào thuộc step này được upload chưa
                $docs = $this->documentModel->where('step_id', $step['id'])->findAll();
                
                // Thu thập danh sách các loại tài liệu đã upload cho step này
                $uploadedTypes = array_column($docs, 'type');
                
                // So sánh từng tài liệu yêu cầu
                foreach ($required as $reqDoc) {
                    $docTypeName = is_array($reqDoc) ? ($reqDoc['name'] ?? '') : $reqDoc;
                    
                    // Kiểm tra xem tên loại tài liệu yêu cầu có nằm trong danh sách đã upload không
                    // Hoặc ít nhất số lượng tài liệu upload >= số yêu cầu (Logic đơn giản hơn)
                    if (empty($docs)) {
                        throw new Exception("Vui lòng tải lên tài liệu: " . $docTypeName);
                    }
                }
            }
        }
    }

    /**
     * Nhân viên gửi yêu cầu duyệt hoàn thành bước
     */
    public function submitForApproval(int $stepId, array $data = [])
    {
        $step = $this->stepModel->find($stepId);
        if (!$step) throw new Exception("Không tìm thấy bước này.");

        $this->verifyRequiredDocuments($step);

        $this->stepModel->update($stepId, [
            'status' => 'pending_approval'
        ]);

        $historyModel = model('CaseHistoryModel');
        $historyModel->save([
            'case_id' => $step['case_id'],
            'user_id' => session()->get('user_id') ?: 0,
            'action'  => 'Gửi yêu cầu duyệt bước: ' . $step['step_name'],
            'details' => json_encode($data)
        ]);

        // Gửi thông báo
        $case = $this->caseModel->find($step['case_id']);
        $senderName = session()->get('full_name');
        $msg = "Nhân viên {$senderName} vừa gửi yêu cầu xét duyệt công việc: [{$step['step_name']}] (Hồ sơ {$case['code']}).";
        $link = base_url('cases/show/' . $step['case_id']);
        
        if (session()->get('role_name') !== 'Admin') {
            $approvers = model('CaseMemberModel')->where('case_id', $step['case_id'])->where('role_in_case', 'approver')->findAll();
            if (count($approvers) > 0) {
                $employeeModel = model('EmployeeModel');
                foreach ($approvers as $app) {
                    $emp = $employeeModel->find($app['employee_id']);
                    if ($emp && $emp['user_id']) {
                        $this->notificationService->sendToUser($emp['user_id'], "Yêu cầu xét duyệt", $msg, 'approval', $link);
                    }
                }
            } else {
                $employeeId = session()->get('employee_id');
                $this->notificationService->notifyManagerOfEmployee($employeeId, "Yêu cầu xét duyệt", $msg, 'approval', $link);
            }
        }

        return true;
    }

    /**
     * Quản lý phê duyệt bước
     */
    public function approveStep(int $stepId)
    {
        $step = $this->stepModel->find($stepId);
        if (!$step) throw new Exception("Không tìm thấy bước này.");

        $this->stepModel->update($stepId, [
            'completed_at' => date('Y-m-d H:i:s'),
            'status'       => 'completed'
        ]);

        $historyModel = model('CaseHistoryModel');
        $historyModel->save([
            'case_id' => $step['case_id'],
            'user_id' => session()->get('user_id') ?: 0,
            'action'  => 'Phê duyệt hoàn thành bước: ' . $step['step_name'],
            'details' => ''
        ]);

        // Kích hoạt bước tiếp theo (nếu có) logic này ở CaseController hoặc chuyển vào đây
        // Gửi thông báo cho những người liên quan
        $case = $this->caseModel->find($step['case_id']);
        $mgrName = session()->get('full_name');
        $msg = "Quản lý {$mgrName} đã PHÊ DUYỆT hoàn thành công việc: [{$step['step_name']}] (Hồ sơ {$case['code']}).";
        $link = base_url('cases/show/' . $step['case_id']);
        
        $this->notifyCaseMembers($step['case_id'], "Phê duyệt bước", $msg, 'approval', $link);

        return true;
    }

    /**
     * Quản lý từ chối bước
     */
    public function rejectStep(int $stepId, string $reason = '')
    {
        $step = $this->stepModel->find($stepId);
        if (!$step) throw new Exception("Không tìm thấy bước này.");

        $this->stepModel->update($stepId, [
            'status' => 'active'
        ]);

        $historyModel = model('CaseHistoryModel');
        $historyModel->save([
            'case_id' => $step['case_id'],
            'user_id' => session()->get('user_id') ?: 0,
            'action'  => 'Từ chối bước: ' . $step['step_name'],
            'details' => json_encode(['reason' => $reason])
        ]);

        $case = $this->caseModel->find($step['case_id']);
        $mgrName = session()->get('full_name');
        $msg = "Quản lý {$mgrName} đã TỪ CHỐI công việc: [{$step['step_name']}] (Hồ sơ {$case['code']}). Lý do: {$reason}";
        $link = base_url('cases/show/' . $step['case_id']);
        
        $this->notifyCaseMembers($step['case_id'], "Từ chối bước", $msg, 'system', $link);

        return true;
    }

    /**
     * Hoàn thành một bước (do Quản lý tự thao tác trực tiếp)
     */
    public function completeStep(int $stepId, array $data = [])
    {
        $step = $this->stepModel->find($stepId);
        if (!$step) throw new Exception("Không tìm thấy bước này.");

        // Manager cũng phải kèm tài liệu (tuỳ nghiệp vụ, có thể bỏ qua nhưng best practice là kiểm tra luôn)
        // $this->verifyRequiredDocuments($step);

        $this->stepModel->update($stepId, [
            'completed_at' => date('Y-m-d H:i:s'),
            'status'       => 'completed'
        ]);

        $historyModel = model('CaseHistoryModel');
        $historyModel->save([
            'case_id' => $step['case_id'],
            'user_id' => session()->get('user_id') ?: 0,
            'action'  => 'Hoàn thành bước: ' . $step['step_name'],
            'details' => json_encode($data)
        ]);

        $case = $this->caseModel->find($step['case_id']);
        $mgrName = session()->get('full_name');
        $msg = "{$mgrName} đã cập nhật hoàn thành công việc: [{$step['step_name']}] (Hồ sơ {$case['code']}).";
        $link = base_url('cases/show/' . $step['case_id']);
        
        $this->notifyCaseMembers($step['case_id'], "Hoàn thành bước", $msg, 'system', $link);

        return true;
    }

    /**
     * Lấy toàn bộ danh sách quy trình mẫu
     */
    public function getAllTemplates()
    {
        return $this->templateModel->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Lấy chi tiết quy trình mẫu
     */
    public function getTemplateById($id)
    {
        return $this->templateModel->find($id);
    }

    /**
     * Lấy các bước của 1 template
     */
    public function getStepsByTemplateId($templateId)
    {
        return $this->templateStepModel->where('template_id', $templateId)
                                      ->orderBy('step_order', 'ASC')
                                      ->findAll();
    }

    /**
     * Tạo mới template
     */
    public function createTemplate(array $data)
    {
        return $this->templateModel->insert($data);
    }

    /**
     * Gửi thông báo cho toàn bộ những người liên quan trong vụ việc (ngoại trừ Admin và chính người thao tác)
     */
    private function notifyCaseMembers(int $caseId, string $title, string $msg, string $type, string $link)
    {
        if (session()->get('role_name') === 'Admin') {
            return; // Admin thay đổi thì không gửi thông báo
        }

        $caseMemberModel = model('CaseMemberModel');
        $employeeModel = model('EmployeeModel');
        $currentEmployeeId = session()->get('employee_id');

        $members = $caseMemberModel->where('case_id', $caseId)->findAll();
        
        // Cần lấy thêm lawyer_id và staff_id cũ để đảm bảo không sót
        $case = $this->caseModel->find($caseId);
        $legacyEmpIds = [];
        if (!empty($case['assigned_lawyer_id'])) $legacyEmpIds[] = $case['assigned_lawyer_id'];
        if (!empty($case['assigned_staff_id'])) $legacyEmpIds[] = $case['assigned_staff_id'];

        $allEmpIds = array_column($members, 'employee_id');
        $allEmpIds = array_unique(array_merge($allEmpIds, $legacyEmpIds));

        foreach ($allEmpIds as $empId) {
            if ($empId != $currentEmployeeId) { // Không gửi cho chính mình
                $emp = $employeeModel->find($empId);
                if ($emp && !empty($emp['user_id'])) {
                    $this->notificationService->sendToUser($emp['user_id'], $title, $msg, $type, $link);
                }
            }
        }
    }

    /**
     * Đồng bộ hóa các bước của Template
     */
    public function syncSteps(int $templateId, array $steps)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Xóa các bước cũ (Sử dụng hard delete để tránh làm rác DB vì đây là bản mẫu)
        $this->templateStepModel->where('template_id', $templateId)->delete(null, true);

        // 2. Chèn các bước mới
        $totalDays = 0;
        foreach ($steps as $index => $step) {
            $step['template_id'] = $templateId;
            $step['step_order']  = $index + 1;
            
            // Xử lý JSON strings
            if (isset($step['required_documents']) && is_array($step['required_documents'])) {
                $step['required_documents'] = json_encode($step['required_documents']);
            }
            if (isset($step['responsible_role']) && is_array($step['responsible_role'])) {
                $step['responsible_role'] = json_encode($step['responsible_role']);
            }

            $this->templateStepModel->insert($step);
            $totalDays += (int)$step['duration_days'];
        }

        // 3. Cập nhật tổng số ngày dự kiến cho Template
        $this->templateModel->update($templateId, ['total_estimated_days' => $totalDays]);

        $db->transComplete();
        return $db->transStatus();
    }

    /**
     * Cập nhật thông tin Template
     */
    public function updateTemplate(int $id, array $data)
    {
        return $this->templateModel->update($id, $data);
    }

    /**
     * Xóa Template (Soft delete)
     */
    public function deleteTemplate($id)
    {
        return $this->templateModel->delete($id);
    }
}
