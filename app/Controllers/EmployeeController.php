<?php

namespace App\Controllers;

use App\Services\EmployeeService;

/**
 * EmployeeController
 * 
 * Kiểm soát các luồng yêu cầu liên quan đến quản lý nhân sự.
 */
class EmployeeController extends BaseController
{
    protected $employeeService;

    public function __construct()
    {
        $this->employeeService = new EmployeeService();
    }

    /**
     * Hiển thị danh sách nhân viên
     */
    public function index()
    {
        $data = [
            'title' => 'Quản lý nhân viên | LawFirm ERP',
            'employees' => $this->employeeService->getAllEmployees()
        ];
        return view('dashboard/employees/index', $data);
    }

    /**
     * Hiển thị form tạo nhân viên mới
     */
    public function create()
    {
        $data = [
            'title' => 'Thêm nhân viên mới | LawFirm ERP'
        ];
        return view('dashboard/employees/create', $data);
    }

    /**
     * Xử lý lưu nhân viên mới
     */
    public function store()
    {
        $data = $this->request->getPost();
        $result = $this->employeeService->createEmployee($data);

        if ($result['status'] === 'success') {
            return redirect()->to('/employees')->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Hiển thị form chỉnh sửa nhân viên
     */
    public function edit(int $id)
    {
        $result = $this->employeeService->getEmployeeById($id);
        if ($result['status'] === 'error') {
            return redirect()->to('/employees')->with('error', $result['message']);
        }

        $data = [
            'title' => 'Chỉnh sửa nhân viên | LawFirm ERP',
            'employee' => $result['data']
        ];
        return view('dashboard/employees/edit', $data);
    }

    /**
     * Xử lý cập nhật thông tin nhân viên
     */
    public function update(int $id)
    {
        $data = $this->request->getPost();
        $result = $this->employeeService->updateEmployee($id, $data);

        if ($result['status'] === 'success') {
            return redirect()->to('/employees')->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Xử lý xóa nhân viên
     */
    public function delete(int $id)
    {
        $result = $this->employeeService->deleteEmployee($id);
        
        if ($result['status'] === 'success') {
            return redirect()->to('/employees')->with('success', $result['message']);
        }

        return redirect()->to('/employees')->with('error', $result['message']);
    }
}
