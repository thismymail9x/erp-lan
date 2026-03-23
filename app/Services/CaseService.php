<?php

namespace App\Services;

use App\Models\CaseModel;

/**
 * CaseService
 * 
 * Quản lý các vụ việc pháp lý với logic phân quyền dữ liệu cao cấp.
 */
class CaseService extends BaseService
{
    protected $caseModel;
    protected $accessControl;

    public function __construct()
    {
        parent::__construct();
        $this->caseModel = new CaseModel();
        $this->accessControl = new AccessControlService();
    }

    /**
     * Lấy danh sách vụ việc (có lọc theo quyền, tìm kiếm, sắp xếp và phân trang)
     */
    public function getCases(string $sort = 'id', string $order = 'desc', int $perPage = 10, string $search = '')
    {
        $roleName = session()->get('role_name');
        $userId = session()->get('user_id');

        $sortMap = [
            'code'     => 'cases.code',
            'title'    => 'cases.title',
            'customer' => 'customers.name',
            'type'     => 'cases.type',
            'lawyer'   => 'employees.full_name',
            'status'   => 'cases.status',
            'deadline' => 'cases.deadline',
            'id'       => 'cases.id'
        ];

        $orderField = $sortMap[$sort] ?? 'cases.id';
        $direction  = (strtolower($order) === 'asc') ? 'asc' : 'desc';

        $query = $this->caseModel->select('cases.*, customers.name as customer_name, current_step.step_name as current_step_name, current_step.deadline as step_deadline')
                        ->join('customers', 'customers.id = cases.customer_id')
                        ->join('case_steps as current_step', "current_step.case_id = cases.id AND current_step.status IN ('active', 'pending_approval')", 'left')
                        ->groupBy('cases.id');

        // Áp dụng bộ lọc tìm kiếm
        if (!empty($search)) {
            $query->groupStart()
                  ->like('cases.title', $search)
                  ->orLike('cases.code', $search)
                  ->orLike('customers.name', $search)
                  ->groupEnd();
        }

        // Phân quyền xem: Chỉ Admin/Manager thấy hết, nhân viên chỉ thấy hồ sơ mình được giao
        if (!$this->accessControl->canViewAllData($roleName)) {
            $employeeModel = model('EmployeeModel');
            $employee = $employeeModel->where('user_id', $userId)->first();
            
            if ($employee) {
                $caseIds = model('CaseMemberModel')->where('employee_id', $employee['id'])->findColumn('case_id');

                $query->groupStart()
                      ->where('cases.assigned_staff_id', $employee['id'])
                      ->orWhere('cases.assigned_lawyer_id', $employee['id']);
                
                if (!empty($caseIds)) {
                    $query->orWhereIn('cases.id', $caseIds);
                }
                
                $query->groupEnd();
            } else {
                return []; // Không tìm thấy hồ sơ nhân sự của user này
            }
        }

        $cases = $query->orderBy($orderField, $direction)->paginate($perPage);

        // Fetch assignees for each case
        if (!empty($cases)) {
            $caseIds = array_column($cases, 'id');
            $assignees = $this->caseModel->db->table('case_members')
                ->select('case_id, employees.full_name')
                ->join('employees', 'employees.id = case_members.employee_id')
                ->whereIn('case_id', $caseIds)
                ->where('role_in_case', 'assignee')
                ->get()->getResultArray();
            
            $assigneeMap = [];
            foreach ($assignees as $a) {
                $assigneeMap[$a['case_id']][] = $a['full_name'];
            }
            
            foreach ($cases as &$case) {
                if (isset($assigneeMap[$case['id']])) {
                    $case['lawyer_name'] = implode(', ', $assigneeMap[$case['id']]);
                } else {
                    $case['lawyer_name'] = '';
                }
            }
        }

        return $cases;
    }

    /**
     * Trả về object pager của CaseModel
     */
    public function getPager()
    {
        return $this->caseModel->pager;
    }

    /**
     * Chi tiết vụ việc (Kiểm tra quyền truy cập)
     */
    public function getCaseDetails(int $id)
    {
        $case = $this->caseModel->find($id);
        if (!$case) {
            return $this->fail('Không tìm thấy vụ việc.');
        }

        $roleName = session()->get('role_name');
        if (!$this->accessControl->canViewAllData($roleName)) {
            $userId = session()->get('user_id');
            $employee = model('EmployeeModel')->where('user_id', $userId)->first();
            
            if (!$employee) {
                return $this->fail('Bạn không có quyền truy cập.');
            }

            // Kiểm tra phân quyền truy cập: là luật sư chính, trợ lý, hoặc member trong danh sách
            if ($case['assigned_lawyer_id'] != $employee['id'] && $case['assigned_staff_id'] != $employee['id']) {
                $isMember = model('CaseMemberModel')->where('case_id', $id)->where('employee_id', $employee['id'])->first();
                if (!$isMember) {
                    return $this->fail('Bạn không có quyền truy cập vụ việc này.');
                }
            }
        }

        return $this->success($case);
    }
}
