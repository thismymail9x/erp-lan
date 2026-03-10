<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Models\RoleModel;

/**
 * UserController
 * 
 * Bộ điều khiển (Controller) xử lý tất cả các yêu cầu từ phía giao diện người dùng
 * (View) liên quan đến phần Quản lý tài khoản, và giao tiếp với UserService 
 * nhằm áp dụng các chính sách phân quyền.
 */
class UserController extends BaseController
{
    protected $userService;
    protected $roleModel;

    public function __construct()
    {
        // Khởi tạo các Service và Model cần thiết khi Controller được gọi
        $this->userService = new UserService();
        $this->roleModel = new RoleModel();
    }

    /**
     * Hiển thị trang danh sách tài khoản
     * 
     * Bước 1: Gọi UserService để lấy danh sách tài khoản hợp lệ.
     * Bước 2: Kiểm tra lại quyền truy cập tổng quan, từ chối nhân viên/TTS.
     */
    public function index()
    {
        $users = $this->userService->getUsers();

        $roleName = session()->get('role_name');
        // Chỉ cấp phát quyền truy cập trang danh sách cho Admin, Giám đốc (Mod), Trưởng phòng
        if ($roleName != \Config\AppConstants::ROLE_ADMIN && $roleName != \Config\AppConstants::ROLE_MOD && $roleName != \Config\AppConstants::ROLE_TRUONG_PHONG) {
            return redirect()->to('/dashboard')->with('error', 'Cảnh báo bảo mật: Bạn không có thẩm quyền truy cập trang này.');
        }

        $data = [
            'title' => 'Quản lý tài khoản | LawFirm ERP',
            'users' => $users,
        ];
        return view('dashboard/users/index', $data);
    }

    /**
     * Hiển thị giao diện Form tạo tài khoản mới
     */
    public function create()
    {
        $roleName = session()->get('role_name');
        // Chặn bảo mật: Xác minh chắc chắn chỉ Admin mới được mở Form tạo
        if ($roleName != \Config\AppConstants::ROLE_ADMIN) {
            return redirect()->to('/users')->with('error', 'Từ chối thao tác: Chỉ Quản trị viên (Admin) mới có quyền tạo mới tài khoản.');
        }

        $data = [
            'title' => 'Thêm tài khoản mới | LawFirm ERP',
            'roles' => $this->roleModel->findAll() // Lấy toàn bộ danh sách chức danh để hiển thị ở Dropdown
        ];
        return view('dashboard/users/create', $data);
    }

    /**
     * Xử lý dữ liệu Submit từ Form tạo tài khoản (POST)
     */
    public function store()
    {
        // Lấy tất cả dữ liệu người dùng nhập từ Form
        $data = $this->request->getPost();
        // Nhờ UserService xử lý nghiệp vụ lưu và mã hóa password
        $result = $this->userService->createUser($data);

        // Kiểm tra kết quả trả về từ Service
        if ($result['status'] === 'success') {
            return redirect()->to('/users')->with('success', $result['message']);
        }

        // Lỗi (có thể do email trùng, v.v) thì ném lại trang Form kèm thông báo
        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Hiển thị giao diện Form cập nhật quyền / thông số cho 1 tài khoản
     */
    public function edit(int $id)
    {
        // Yêu cầu Service lôi dữ liệu user này lên, nếu không có thẩm quyền, Service sẽ block
        $result = $this->userService->getUserById($id);
        if (!$result['status']) {
            return redirect()->to('/users')->with('error', $result['message']);
        }

        $data = [
            'title' => 'Cập nhật phân quyền / tài khoản | LawFirm ERP',
            'user'  => $result['data'],
            'roles' => $this->roleModel->findAll(),
            'currentRoleName' => session()->get('role_name') // Trích xuất Role Name của người đang xem để View tự động ẩn hiện box Password
        ];
        return view('dashboard/users/edit', $data);
    }

    /**
     * Xử lý dữ liệu Submit từ Form cập nhật (POST)
     */
    public function update(int $id)
    {
        // Láy dữ liệu được gửi đến (Role, Password, Tình trạng khóa)
        $data = $this->request->getPost();
        // Chuyển toàn bộ dữ liệu xuống UserService, để bộ lọc phân quyền trong đó tự quyết định lấy/từ chối các thay đổi
        $result = $this->userService->updateUser($id, $data);

        if ($result['status'] === 'success') {
            return redirect()->to('/users')->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Xóa hoàn toàn tài khoản khỏi CSDL
     */
    public function delete(int $id)
    {
        // Ủy quyền hẳn cho Service thực hiện thao tác xóa
        $result = $this->userService->deleteUser($id);
        
        if ($result['status'] === 'success') {
            return redirect()->to('/users')->with('success', $result['message']);
        }

        return redirect()->to('/users')->with('error', $result['message']);
    }
}
