<?php

namespace App\Services;

use App\Models\EmployeeModel;
use App\Models\UserModel;

/**
 * EmployeeService
 * 
 * Xử lý các nghiệp vụ liên quan đến quản lý nhân viên: CRUD, gán quyền, hồ sơ.
 */
class EmployeeService extends BaseService
{
    protected $employeeModel;
    protected $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->employeeModel = new EmployeeModel();
        $this->userModel = new UserModel();
    }

    /**
     * Lấy danh sách toàn bộ nhân viên
     */
    public function getAllEmployees()
    {
        return $this->employeeModel->findAll();
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
            return $this->success(['id' => $this->employeeModel->getInsertID()], 'Tạo nhân viên thành công.');
        }

        return $this->fail('Không thể tạo nhân viên. Vui lòng kiểm tra dữ liệu.');
    }

    /**
     * Cập nhật thông tin nhân viên
     */
    public function updateEmployee(int $id, array $data)
    {
        if ($this->employeeModel->update($id, $data)) {
            return $this->success(null, 'Cập nhật thông tin thành công.');
        }
        return $this->fail('Cập nhật thất bại.');
    }

    /**
     * Xóa nhân viên (Xóa mềm)
     */
    public function deleteEmployee(int $id)
    {
        if ($this->employeeModel->delete($id)) {
            return $this->success(null, 'Đã xóa nhân viên.');
        }
        return $this->fail('Xóa thất bại.');
    }
}
