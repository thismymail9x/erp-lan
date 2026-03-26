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

    public function __construct()
    {
        parent::__construct();
        // Khởi tạo các Model và Service phụ trợ để xử lý logic liên tầng
        $this->caseModel = new CaseModel();
        $this->stepModel = new CaseStepModel();
        $this->templateModel = new WorkflowTemplateModel();
        $this->templateStepModel = new WorkflowStepModel();
        $this->timelineService = new CaseTimelineService();
        $this->documentModel = new DocumentModel();
        $this->notificationService = new NotificationService();
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
                'notification_template' => $tStep['notification_template']
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

        // 3. Đánh dấu trạng thái 'pending_approval'
        $this->stepModel->update($stepId, [
            'status' => 'pending_approval'
        ]);

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
        
        // Không gửi cho Admin nếu Admin là người thực hiện (Tự duyệt)
        if (session()->get('role_name') !== 'Admin') {
            // Tìm danh sách người duyệt (Approvers) được phân công cụ thể cho vụ việc này
            $approvers = model('CaseMemberModel')->where('case_id', $step['case_id'])->where('role_in_case', 'approver')->findAll();
            
            if (count($approvers) > 0) {
                $employeeModel = model('EmployeeModel');
                foreach ($approvers as $app) {
                    $emp = $employeeModel->find($app['employee_id']);
                    if ($emp && $emp['user_id']) {
                        // Bắn thông báo qua Web/App notification
                        $this->notificationService->sendToUser($emp['user_id'], "Yêu cầu xét duyệt mới", $msg, 'approval', $link);
                    }
                }
            } else {
                // Nếu chưa có người duyệt cụ thể -> Gửi cho quản lý trực tiếp của nhân viên theo sơ đồ tổ chức
                $employeeId = session()->get('employee_id');
                $this->notificationService->notifyManagerOfEmployee($employeeId, "Phê duyệt công việc", $msg, 'approval', $link);
            }
        }

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

        // 1. Chốt thời gian hoàn thành thực tế
        $this->stepModel->update($stepId, [
            'completed_at' => date('Y-m-d H:i:s'),
            'status'       => 'completed'
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

        // 3. Quảng bá thông tin cho Ban vụ việc
        $case = $this->caseModel->find($step['case_id']);
        $msg = "Quản lý {$mgrName} đã chấp thuận và hoàn thành mục tiêu: [{$step['step_name']}] (Hồ sơ {$case['code']}).";
        $link = base_url('cases/show/' . $step['case_id']);
        
        $this->notifyCaseMembers($step['case_id'], "Hoàn thành tiến độ", $msg, 'approval', $link);

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
        
        $this->notifyCaseMembers($step['case_id'], "Yêu cầu chỉnh sửa", $msg, 'system', $link);

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

        $this->stepModel->update($stepId, [
            'completed_at' => date('Y-m-d H:i:s'),
            'status'       => 'completed'
        ]);

        $mgrName = session()->get('full_name');
        $historyModel = model('CaseHistoryModel');
        $historyModel->save([
            'case_id' => $step['case_id'],
            'user_id' => session()->get('user_id') ?: 0,
            'action'  => 'HOÀN THÀNH (FAST-TRACK): ' . $step['step_name'],
            'details' => json_encode($data)
        ]);

        return true;
    }

    /**
     * Lấy toàn bộ danh mục Quy trình mẫu.
     */
    public function getAllTemplates()
    {
        return $this->templateModel->orderBy('created_at', 'DESC')->findAll();
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
     * Helper: Phân phối thông báo cho toàn bộ đội ngũ tham gia vụ việc.
     * Tận dụng tối đa sự liên kết giữa các bảng CaseMember và Employee.
     */
    private function notifyCaseMembers(int $caseId, string $title, string $msg, string $type, string $link)
    {
        $caseMemberModel = model('CaseMemberModel');
        $employeeModel = model('EmployeeModel');
        $currentEmployeeId = session()->get('employee_id');

        // 1. Quét danh sách thành viên hiện hữu trong ban vụ việc
        $members = $caseMemberModel->where('case_id', $caseId)->findAll();
        
        // 2. Bổ sung các nhân sự cốt cán (Lawyer/Staff) từ hồ sơ gốc
        $case = $this->caseModel->find($caseId);
        $legacyEmpIds = [];
        if (!empty($case['assigned_lawyer_id'])) $legacyEmpIds[] = $case['assigned_lawyer_id'];
        if (!empty($case['assigned_staff_id'])) $legacyEmpIds[] = $case['assigned_staff_id'];

        $allEmpIds = array_column($members, 'employee_id');
        $allEmpIds = array_unique(array_merge($allEmpIds, $legacyEmpIds));

        // 3. Gửi thông báo loại trừ người vừa ra lệnh (Current User)
        foreach ($allEmpIds as $empId) {
            if ($empId != $currentEmployeeId) { 
                $emp = $employeeModel->find($empId);
                if ($emp && !empty($emp['user_id'])) {
                    $this->notificationService->sendToUser($emp['user_id'], $title, $msg, $type, $link);
                }
            }
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
        return $this->templateModel->update($id, $data);
    }

    /**
     * Xóa bỏ cấu hình Quy trình.
     */
    public function deleteTemplate($id)
    {
        return $this->templateModel->delete($id);
    }
}
