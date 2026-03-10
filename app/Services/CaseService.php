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
     * Lấy danh sách vụ việc (có lọc theo quyền)
     */
    public function getCases()
    {
        $roleName = session()->get('role_name');
        $userId = session()->get('user_id');

        // Nếu không có quyền xem hết, chỉ lấy vụ việc được phân công cho nhân viên này
        if (!$this->accessControl->canViewAllData($roleName)) {
            // Cần tìm employee_id từ user_id
            $employeeModel = model('EmployeeModel');
            $employee = $employeeModel->where('user_id', $userId)->first();
            
            if ($employee) {
                return $this->caseModel->where('assigned_lawyer_id', $employee['id'])->findAll();
            }
            return [];
        }

        return $this->caseModel->findAll();
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
            
            if (!$employee || $case['assigned_lawyer_id'] != $employee['id']) {
                return $this->fail('Bạn không có quyền truy cập vụ việc này.');
            }
        }

        return $this->success($case);
    }
}
