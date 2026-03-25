<?php

namespace App\Controllers;

use App\Services\EmployeeService;

/**
 * EmployeeController
 * 
 * Bộ điều khiển trung tâm quản lý vòng đời nhân sự (HRM).
 * Đảm nhiệm:
 * 1. Quản lý danh sách nhân viên (Lọc theo quyền hạn).
 * 2. Phân quyền tự chỉnh sửa hồ sơ cá nhân (Self-Service).
 * 3. Bảo mật thông tin nhạy cảm (Bank, CCCD) cho nhân viên.
 * 4. Kết nối hồ sơ nhân sự với tài khoản đăng nhập (Account Linkage).
 */
class EmployeeController extends BaseController
{
    protected $employeeService;

    public function __construct()
    {
        // Khởi tạo Service xử lý nghiệp vụ nhân sự
        $this->employeeService = new EmployeeService();
    }

    /**
     * Hiển thị bảng danh bạ nhân viên.
     * Áp dụng chính sách bảo mật: Người không có quyền quản lý chỉ được xem hồ sơ của chính mình.
     */
    public function index()
    {
        $roleName = session()->get('role_name');
        $deptName = session()->get('department_name');

        // --- KIỂM TRA QUYỀN TRUY CẬP (Access Control) ---
        // Chỉ Admin, Giám đốc (Mod) và nhân sự phòng Hành chính mới được xem danh sách tổng.
        if ($roleName !== \Config\AppConstants::ROLE_ADMIN && 
            $roleName !== \Config\AppConstants::ROLE_MOD && 
            $deptName !== \Config\AppConstants::DEPT_NAME_HANH_CHINH) {
            
            // Nếu là nhân viên bình thường, tự động chuyển hướng về trang Sửa hồ sơ cá nhân.
            $myEmpId = session()->get('employee_id');
            if ($myEmpId) {
                return redirect()->to('/employees/edit/' . $myEmpId);
            }
            return redirect()->to('/dashboard')->with('error', 'Hồ sơ nhân viên của bạn chưa được liên kết với tài khoản này.');
        }

        // --- XỬ LÝ LỌC VÀ SẮP XẾP ---
        $sort   = $this->request->getGet('sort') ?? 'id';
        $order  = $this->request->getGet('order') ?? 'desc';
        $search = $this->request->getGet('search') ?? '';
        $perPage = 10;

        // Gọi Service lấy dữ liệu đã được phân trang
        $employees = $this->employeeService->getAllEmployees($sort, $order, $perPage, $search);

        $data = [
            'title'        => 'Quản lý nhân viên | L.A.N ERP',
            'employees'    => $employees,
            'pager'        => $this->employeeService->getPager(),
            'currentSort'  => $sort,
            'currentOrder' => $order,
            'search'       => $search
        ];

        // Hỗ trợ AJAX Load (Khi chuyển trang hoặc tìm kiếm nhanh không gây lag trang)
        if ($this->request->isAJAX()) {
            return view('dashboard/employees/index_table', $data);
        }

        return view('dashboard/employees/index', $data);
    }

    /**
     * Form thêm mới nhân sự.
     * Chỉ dành cho các cấp có thẩm quyền tuyển dụng/quản lý.
     */
    public function create()
    {
        $roleName = session()->get('role_name');
        $deptName = session()->get('department_name');

        // Chặn người dùng trái phép
        if ($roleName !== \Config\AppConstants::ROLE_ADMIN && $deptName !== \Config\AppConstants::DEPT_NAME_HANH_CHINH) {
            return redirect()->to('/employees')->with('error', 'Hành động bị từ chối: Chỉ Quản trị viên và bộ phận Hành chính mới được phép thêm nhân sự.');
        }

        $data = [
            'title' => 'Thêm nhân viên mới | L.A.N ERP',
            'departments' => $this->employeeService->getDepartments(),
            // Lấy danh sách tài khoản chưa có hồ sơ để liên kết ngay khi tạo
            'unlinkedUsers' => $this->employeeService->getUnlinkedUsers()
        ];
        return view('dashboard/employees/create', $data);
    }

    /**
     * Tiếp nhận và lưu trữ thông tin nhân viên mới.
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

        // Nếu thất bại (ví dụ: lỗi validate), quay lại kèm dữ liệu cũ để người dùng không phải nhập lại
        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Hiển thị chi tiết hồ sơ nhân sự để chỉnh sửa.
     * Hỗ trợ chế độ "Self-Edit" cho nhân viên tự cập nhật thông tin cá nhân.
     */
    public function edit(int $id)
    {
        $roleName = session()->get('role_name');
        $deptName = session()->get('department_name');
        $myEmployeeId = (int)session()->get('employee_id');

        // --- KIỂM TRA QUYỀN CHỈNH SỬA ---
        // Bạn chỉ được vào trang này nếu: 1. Là Admin/Hành chính, 2. Là CHÍNH CHỦ của hồ sơ đó.
        if ($roleName !== \Config\AppConstants::ROLE_ADMIN && 
            $deptName !== \Config\AppConstants::DEPT_NAME_HANH_CHINH && 
            $id !== $myEmployeeId) {
            return redirect()->to('/employees')->with('error', 'Hành động bị từ chối: Bạn không có quyền xem hoặc sửa đổi thông tin của đồng nghiệp.');
        }

        $result = $this->employeeService->getEmployeeById($id);
        if ($result['status'] === 'error') {
            return redirect()->to('/employees')->with('error', $result['message']);
        }

        // Xử lý danh sách tài khoản liên kết (để có thể thay đổi User gán cho nhân viên này)
        $unlinkedUsers = $this->employeeService->getUnlinkedUsers();
        
        if ($result['data']['user_id']) {
            $userModel = new \App\Models\UserModel();
            $currentUser = $userModel->select('users.*, roles.name as role_name')
                ->join('roles', 'roles.id = users.role_id', 'left')
                ->find($result['data']['user_id']);
            if ($currentUser) {
                // Đưa User hiện tại lên đầu danh sách chọn
                array_unshift($unlinkedUsers, $currentUser);
                $result['data']['role_name'] = $currentUser['role_name'];
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
     * Cập nhật dữ liệu nhân sự.
     * Áp dụng bộ lọc trường (Field Filtering) để ngăn nhân viên tự tăng lương hoặc đổi bộ phận.
     */
    public function update(int $id)
    {
        $roleName = session()->get('role_name');
        $deptName = session()->get('department_name');
        $myEmployeeId = (int)session()->get('employee_id');

        // 1. Xác thực thẩm quyền
        if (session()->get('role_name') !== \Config\AppConstants::ROLE_ADMIN && 
            session()->get('role_name') !== \Config\AppConstants::ROLE_MOD && 
            $deptName !== \Config\AppConstants::DEPT_NAME_HANH_CHINH && 
            $id !== $myEmployeeId) {
            return redirect()->to('/employees')->with('error', 'Lỗi bảo mật: Bạn không có quyền thực hiện thao tác này.');
        }

        $data = $this->request->getPost();

        // 2. BỘ LỌC DỮ LIỆU (DATA FILTERING):
        // Nếu không phải là Cấp quản lý/Hành chính (Tức là nhân viên đang tự sửa hồ sơ của mình)
        if (session()->get('role_name') !== \Config\AppConstants::ROLE_ADMIN && 
            session()->get('role_name') !== \Config\AppConstants::ROLE_MOD && 
            $deptName !== \Config\AppConstants::DEPT_NAME_HANH_CHINH) {
            
            // CHỈ CHO PHÉP cập nhật các trường mang tính chất thông tin liên hệ/cá nhân.
            // TUYỆT ĐỐI KHÔNG cho phép tự sửa: Lương (salary_base), Phòng ban (department_id), Ngày gia nhập (join_date).
            $allowedFields = [
                'full_name',        // Họ và tên
                'personal_email',   // Email cá nhân
                'phone_number',     // Số điện thoại
                'bank_name',        // Tên ngân hàng
                'bank_account',     // Số tài khoản
                'bank_owner',       // Chủ tài khoản
                'address',          // Địa chỉ
                'identity_card',    // CCCD (Số định danh)
                'dob'               // Ngày sinh
            ];
            // Loại bỏ mọi trường khác khỏi mảng $data
            $data = array_intersect_key($data, array_flip($allowedFields));
        }

        // 3. Thực thi cập nhật qua Service
        $result = $this->employeeService->updateEmployee($id, $data);

        if ($result['status'] === 'success') {
            return redirect()->to('/employees')->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Xóa vĩnh viễn hồ sơ nhân viên.
     * Cảnh báo: Chỉ Admin tối cao mới được phép thực hiện vì logic này liên quan đến toàn vẹn dữ liệu.
     */
    public function delete(int $id)
    {
        // Kiểm tra an ninh tối đa
        if (session()->get('role_name') !== \Config\AppConstants::ROLE_ADMIN) {
            return redirect()->to('/employees')->with('error', 'Hành động nguy hiểm: Bạn không đủ thẩm quyền để xóa bỏ dữ liệu nhân sự.');
        }

        $result = $this->employeeService->deleteEmployee($id);
        
        if ($result['status'] === 'success') {
            return redirect()->to('/employees')->with('success', $result['message']);
        }

        return redirect()->to('/employees')->with('error', $result['message']);
    }

    /**
     * Tính năng tự đổi mật khẩu cho nhân viên.
     * Tích hợp ngay trong trang quản lý hồ sơ nhân sự để thuận tiện cho User.
     */
    public function changePassword()
    {
        $userId = session()->get('user_id');
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);

        $oldPassword = $this->request->getPost('old_password');
        $newPassword = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');

        // Kiểm tra mật khẩu cũ (An toàn tuyệt đối)
        if (!$oldPassword || !password_verify($oldPassword, $user['password'])) {
            return redirect()->back()->with('error', 'Mật khẩu hiện tại không chính xác.');
        }

        // Validate mật khẩu mới
        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'Xác nhận mật khẩu mới không khớp.');
        }

        if (strlen($newPassword) < 6) {
            return redirect()->back()->with('error', 'Mật khẩu mới phải có độ dài tối thiểu 6 ký tự.');
        }

        // Hash mật khẩu mới và lưu trữ
        $userModel->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);

        return redirect()->back()->with('success', 'Bạn đã thay đổi mật khẩu truy cập thành công.');
    }
}
