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
        $roleName = session()->get('role_name');
        $deptName = session()->get('department_name');

        // Quyền truy cập: Admin, Mod và phòng Hành chính
        if ($roleName !== \Config\AppConstants::ROLE_ADMIN && 
            $roleName !== \Config\AppConstants::ROLE_MOD && 
            $deptName !== \Config\AppConstants::DEPT_NAME_HANH_CHINH) {
            return redirect()->to('/dashboard')->with('error', 'Bạn không có quyền truy cập danh sách nhân sự.');
        }

        $sort  = $this->request->getGet('sort') ?? 'id';
        $order = $this->request->getGet('order') ?? 'desc';

        $data = [
            'title' => 'Quản lý nhân viên | L.A.N ERP',
            'employees' => $this->employeeService->getAllEmployees($sort, $order),
            'currentSort' => $sort,
            'currentOrder' => $order
        ];
        return view('dashboard/employees/index', $data);
    }

    /**
     * Hiển thị form tạo nhân viên mới
     */
    public function create()
    {
        $roleName = session()->get('role_name');
        $deptName = session()->get('department_name');

        // Chỉ Admin và phòng Hành chính được tạo
        if ($roleName !== \Config\AppConstants::ROLE_ADMIN && $deptName !== \Config\AppConstants::DEPT_NAME_HANH_CHINH) {
            return redirect()->to('/employees')->with('error', 'Hành động bị từ chối: Chỉ Quản trị viên và bộ phận Hành chính mới được phép thêm nhân sự.');
        }

        $data = [
            'title' => 'Thêm nhân viên mới | L.A.N ERP',
            'departments' => $this->employeeService->getDepartments(),
            'unlinkedUsers' => $this->employeeService->getUnlinkedUsers()
        ];
        return view('dashboard/employees/create', $data);
    }

    /**
     * Xử lý lưu nhân viên mới
     */
    public function store()
    {
        $roleName = session()->get('role_name');
        $deptName = session()->get('department_name');

        if ($roleName !== \Config\AppConstants::ROLE_ADMIN && $deptName !== \Config\AppConstants::DEPT_NAME_HANH_CHINH) {
            return redirect()->to('/employees')->with('error', 'Lỗi bảo mật: Bạn không có quyền thực hiện thao tác này.');
        }

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
        $roleName = session()->get('role_name');
        $deptName = session()->get('department_name');

        // Chỉ Admin và phòng Hành chính được sửa
        if ($roleName !== \Config\AppConstants::ROLE_ADMIN && $deptName !== \Config\AppConstants::DEPT_NAME_HANH_CHINH) {
            return redirect()->to('/employees')->with('error', 'Hành động bị từ chối: Bạn không có quyền sửa đổi thông tin nhân sự.');
        }

        $result = $this->employeeService->getEmployeeById($id);
        if ($result['status'] === 'error') {
            return redirect()->to('/employees')->with('error', $result['message']);
        }

        $unlinkedUsers = $this->employeeService->getUnlinkedUsers();
        
        if ($result['data']['user_id']) {
            $userModel = new \App\Models\UserModel();
            $currentUser = $userModel->find($result['data']['user_id']);
            if ($currentUser) {
                array_unshift($unlinkedUsers, $currentUser);
            }
        }

        $data = [
            'title' => 'Chỉnh sửa nhân viên | L.A.N ERP',
            'employee' => $result['data'],
            'departments' => $this->employeeService->getDepartments(),
            'unlinkedUsers' => $unlinkedUsers
        ];
        return view('dashboard/employees/edit', $data);
    }

    /**
     * Xử lý cập nhật thông tin nhân viên
     */
    public function update(int $id)
    {
        $roleName = session()->get('role_name');
        $deptName = session()->get('department_name');

        if ($roleName !== \Config\AppConstants::ROLE_ADMIN && $deptName !== \Config\AppConstants::DEPT_NAME_HANH_CHINH) {
            return redirect()->to('/employees')->with('error', 'Lỗi bảo mật: Bạn không có quyền thực hiện thao tác này.');
        }

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
        // Chỉ DUY NHẤT Admin được quyền xóa
        if (session()->get('role_name') !== \Config\AppConstants::ROLE_ADMIN) {
            return redirect()->to('/employees')->with('error', 'Hành động nguy hiểm: Chỉ Admin hệ thống mới có quyền xóa vĩnh viễn dữ liệu.');
        }

        $result = $this->employeeService->deleteEmployee($id);
        
        if ($result['status'] === 'success') {
            return redirect()->to('/employees')->with('success', $result['message']);
        }

        return redirect()->to('/employees')->with('error', $result['message']);
    }
}
