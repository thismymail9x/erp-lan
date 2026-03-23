<?php

namespace App\Services;

use App\Models\EmployeeModel;
use App\Models\UserModel;
use App\Models\DepartmentModel;

/**
 * EmployeeService
 * 
 * Xử lý các nghiệp vụ liên quan đến quản lý nhân viên: CRUD, gán quyền, hồ sơ.
 */
class EmployeeService extends BaseService
{
    protected $employeeModel;
    protected $userModel;
    protected $logService;

    public function __construct()
    {
        parent::__construct();
        $this->employeeModel = new EmployeeModel();
        $this->userModel = new UserModel();
        $this->logService = new SystemLogService();
    }

    /**
     * Lấy danh sách nhân viên có lọc theo quyền và bộ phận.
     */
    /**
     * Lấy danh sách nhân viên có lọc theo quyền và bộ phận.
     */
    public function getAllEmployees(string $sort = 'id', string $order = 'desc', int $perPage = 10, string $search = '')
    {
        $roleName = session()->get('role_name');
        $departmentName = session()->get('department_name');

        $sortMap = [
            'name' => 'employees.full_name',
            'position' => 'employees.position',
            'dept' => 'departments.name',
            'join_date' => 'employees.join_date',
            'salary' => 'employees.salary_base',
            'id' => 'employees.id'
        ];

        $orderField = $sortMap[$sort] ?? 'employees.id';
        $direction  = (strtolower($order) === 'asc') ? 'asc' : 'desc';

        $query = $this->employeeModel->select('employees.*, departments.name as department_name')
                            ->join('departments', 'departments.id = employees.department_id', 'left');

        // Áp dụng bộ lọc tìm kiếm
        if (!empty($search)) {
            $query->groupStart()
                  ->like('employees.full_name', $search)
                  ->orLike('employees.position', $search)
                  ->orLike('departments.name', $search)
                  ->groupEnd();
        }

        $query->orderBy($orderField, $direction);

        // Admin, Mod và người thuộc phòng Hành chính được xem tất cả
        if ($roleName === \Config\AppConstants::ROLE_ADMIN || 
            $roleName === \Config\AppConstants::ROLE_MOD || 
            $departmentName === \Config\AppConstants::DEPT_NAME_HANH_CHINH) {
            return $query->paginate($perPage);
        }

        return [];
    }

    /**
     * Trả về object pager của EmployeeModel
     */
    public function getPager()
    {
        return $this->employeeModel->pager;
    }

    /**
     * Lấy thông tin chi tiết một nhân viên theo ID
     */
    public function getEmployeeById(int $id)
    {
        $employee = $this->employeeModel->find($id);
        if (!$employee) {
            return $this->fail('Không tìm thấy nhân viên.');
        }
        return $this->success($employee);
    }

    /**
     * Tạo mới hồ sơ nhân viên và tài khoản nếu cần
     */
    public function createEmployee(array $data)
    {
        // Nếu có yêu cầu tạo tài khoản, xử lý tại đây (chưa triển khai chi tiết)
        
        if ($this->employeeModel->insert($data)) {
            $empId = $this->employeeModel->getInsertID();
            $this->logService->log('CREATE', 'Employees', $empId, ['full_name' => $data['full_name']]);
            return $this->success(['id' => $empId], 'Tạo nhân viên thành công.');
        }

        return $this->fail('Không thể tạo nhân viên. Vui lòng kiểm tra dữ liệu.');
    }

    /**
     * Cập nhật thông tin nhân viên
     */
    public function updateEmployee(int $id, array $data)
    {
        $oldData = $this->employeeModel->find($id);
        
        if ($this->employeeModel->update($id, $data)) {
            $newData = $this->employeeModel->find($id);
            
            // So sánh và chỉ ghi lại những gì thực sự thay đổi cho gọn log
            $changes = [
                'before' => array_diff_assoc($oldData, $newData),
                'after'  => array_diff_assoc($newData, $oldData)
            ];

            $this->logService->log('UPDATE', 'Employees', $id, $changes);
            return $this->success(null, 'Cập nhật thông tin thành công.');
        }
        return $this->fail('Cập nhật thất bại.');
    }

    /**
     * Xóa nhân viên (Xóa mềm)
     */
    public function deleteEmployee(int $id)
    {
        $oldData = $this->employeeModel->find($id);
        
        if ($this->employeeModel->delete($id)) {
            $this->logService->log('DELETE', 'Employees', $id, ['deleted_record' => $oldData]);
            return $this->success(null, 'Đã xóa nhân viên.');
        }
        return $this->fail('Xóa thất bại.');
    }

    /**
     * Lấy danh sách các tài khoản chưa được gán cho nhân viên nào.
     */
    public function getUnlinkedUsers()
    {
        return $this->userModel->select('users.id, users.email')
                               ->join('employees', 'employees.user_id = users.id', 'left')
                               ->where('employees.user_id', null)
                               ->findAll();
    }

    /**
     * Lấy danh sách toàn bộ phòng ban.
     */
    public function getDepartments()
    {
        $departmentModel = new DepartmentModel();
        return $departmentModel->findAll();
    }
}
