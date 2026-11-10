<?php

namespace App\Filters;

use App\Services\CaseManagementService;
use App\Services\CaseAccountingAccess;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Bảo vệ các URL vụ việc hiện hữu theo người quản lý trực tiếp và thành viên vụ việc. */
class CaseManagerFilter implements FilterInterface
{
    /** Kiểm tra vụ việc cha cho cả endpoint bước công việc để không vượt quyền bằng cách sửa ID. */
    public function before(RequestInterface $request, $arguments = null)
    {
        $access = new CaseManagementService();
        if (!$access->ready()) {
            return;
        }
        $segments = $request->getUri()->getSegments();
        $action = $segments[1] ?? '';
        $id = $segments[2] ?? '';
        if (!ctype_digit((string) $id)) {
            return;
        }
        if (in_array($action, ['complete-step', 'approve-step', 'approve-step-kpi', 'reject-step', 'delete-step'], true)) {
            $step = model('CaseStepModel')->find($id);
            $id = $step['case_id'] ?? 0;
        }
        $case = model('CaseModel')->find($id);
        if (!$case || empty($case['case_manager_id'])) {
            return;
        }
        $accounting = new CaseAccountingAccess();
        if (!$access->canView($case) && !$accounting->canManage($case)) {
            return service('response')->setStatusCode(403)->setBody('Bạn không có quyền truy cập vụ việc này.');
        }
        if (strtolower($request->getMethod()) === 'post') {
            service('security')->verify($request);
            if (!$access->canManage($case) && !$accounting->canManage($case)) {
                $employeeId = (int) session()->get('employee_id');
                $isAssigned = $employeeId > 0 && in_array($employeeId, [(int) $case['assigned_lawyer_id'], (int) $case['assigned_staff_id']], true);
                $isMember = $employeeId > 0 && model('CaseMemberModel')->where('case_id', $id)->where('employee_id', $employeeId)->countAllResults() > 0;
                $canApprove = in_array($action, ['approve-step', 'approve-step-kpi', 'reject-step'], true) && has_permission('case.approve');
                if (!$isAssigned && !$isMember && !$canApprove) {
                    return service('response')->setStatusCode(403)->setBody('Quyền xem không bao gồm quyền cập nhật vụ việc.');
                }
            }
        }
    }

    /** Không thay đổi phản hồi sau khi Controller xử lý nghiệp vụ. */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
