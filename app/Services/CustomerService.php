<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\CaseModel;

/**
 * CustomerService
 * 
 * Quản lý khách hàng với logic phân quyền.
 */
class CustomerService extends BaseService
{
    protected $customerModel;
    protected $accessControl;

    public function __construct()
    {
        parent::__construct();
        $this->customerModel = new CustomerModel();
        $this->accessControl = new AccessControlService();
    }

    /**
     * Lấy danh sách khách hàng (có lọc theo quyền)
     */
    public function getCustomers()
    {
        $roleName = session()->get('role_name');

        // Nếu có quyền xem hết (Admin, Mod, Trưởng phòng), trả về toàn bộ
        if ($this->accessControl->canViewAllData($roleName)) {
            return $this->customerModel->findAll();
        }

        // Nếu là nhân viên, chỉ xem được khách hàng của các vụ việc mình được phân công
        $userId = session()->get('user_id');
        $employee = model('EmployeeModel')->where('user_id', $userId)->first();
        
        if ($employee) {
            $caseModel = model('CaseModel');
            $myCustomerIds = $caseModel->where('assigned_lawyer_id', $employee['id'])
                                        ->select('customer_id')
                                        ->findAll();
            
            $ids = array_column($myCustomerIds, 'customer_id');
            if (empty($ids)) return [];

            return $this->customerModel->whereIn('id', $ids)->findAll();
        }

        return [];
    }
}
