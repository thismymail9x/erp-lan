<?php

namespace App\Models;

// Lưu ý: Các model được gọi thông qua hàm model() hoặc khởi tạo trực tiếp tùy cấu hình.
namespace App\Controllers;

use App\Models\DepartmentModel;
use App\Models\EmployeeModel;
use App\Services\UserService;
use App\Models\RoleModel;

/**
 * UserController
 * 
 * Bộ điều khiển (Controller) quản lý toàn bộ vòng đời của Tài khoản người dùng (Users).
 * Chịu trách nhiệm: Hiển thị danh sách, cấp quyền, tạo mới, chỉnh sửa và xóa tài khoản.
 * Tương tác chặt chẽ với UserService để thực hiện các quy tắc bảo mật và phân quyền phức tạp.
 */
class UserController extends BaseController
{
    protected $userService;
    protected $roleModel;
    protected $departmentModel;
    protected $employeeModel;

    public function __construct()
    {
        // Khởi tạo các Service và Model cần thiết khi Controller được gọi
        $this->userService = new UserService();
        $this->roleModel = new RoleModel();
        $this->departmentModel = new DepartmentModel();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Hiển thị trang danh sách tài khoản toàn bộ hệ thống.
     * Quy trình:
     * 1. Kiểm tra quyền truy cập (Chỉ dành cho Admin, Mod, Trưởng phòng).
     * 2. Thu thập các tham số lọc, tìm kiếm, sắp xếp từ URL.
     * 3. Gọi Service để lấy dữ liệu đã qua xử lý.
     * 4. Trả về View tương ứng (AJAX hoặc Full Page).
     */
    public function index()
    {
        // Lấy các tham số điều khiển danh sách từ GET Request
        $sort    = $this->request->getGet('sort') ?? 'id'; // Cột cần sắp xếp
        $order   = $this->request->getGet('order') ?? 'desc'; // Thứ tự (ASC/DESC)
        $search  = $this->request->getGet('search') ?? ''; // Từ khóa tìm kiếm (Email, Tên...)
        $perPage = 10; // Cấu hình số lượng bản ghi hiển thị trên mỗi trang

        // Lấy danh sách User và thống kê trạng thái từ Service
        $users = $this->userService->getUsers($sort, $order, $perPage, $search);
        $stats = $this->userService->getStats(); // Ví dụ: Tổng số user, số user bị khóa...

        // --- KIỂM TRA PHÂN QUYỀN TRUY CẬP TRANG ---
        $roleName = session()->get('role_name');
        // Chỉ những vai trò quản lý cao cấp mới được xem danh sách User
        if ($roleName != \Config\AppConstants::ROLE_ADMIN && $roleName != \Config\AppConstants::ROLE_MOD && $roleName != \Config\AppConstants::ROLE_TRUONG_PHONG) {
            return redirect()->to('/dashboard')->with('error', 'Cảnh báo bảo mật: Bạn không có thẩm quyền truy cập trang quản lý nhân sự.');
        }

        // Đóng gói dữ liệu gửi ra View
        $data = [
            'title'        => 'Quản lý tài khoản | L.A.N ERP',
            'users'        => $users,
            'stats'        => $stats,
            'pager'        => $this->userService->getPager(), // Cung cấp đối tượng phân trang cho View
            'currentSort'  => $sort,
            'currentOrder' => $order,
            'search'       => $search
        ];

        // Hỗ trợ cập nhật danh sách mượt mà qua AJAX (ví dụ khi gõ ô tìm kiếm)
        if ($this->request->isAJAX()) {
            return view('dashboard/users/index_table', $data);
        }

        return view('dashboard/users/index', $data);
    }

    /**
     * Hiển thị giao diện Form tạo tài khoản (Dành riêng cho Admin).
     */
    public function create()
    {
        $roleName = session()->get('role_name');
        // Chặn bảo mật: Xác minh chắc chắn chỉ Admin mới được mở Form tạo
        if ($roleName != \Config\AppConstants::ROLE_ADMIN) {
            return redirect()->to('/users')->with('error', 'Từ chối thao tác: Chỉ Quản trị viên (Admin) mới có quyền tạo mới tài khoản.');
        }

        $data = [
            'title' => 'Thêm tài khoản mới | L.A.N ERP',
            // Lấy danh sách vai trò và phòng ban để người dùng chọn trong dropdown
            'roles' => $this->roleModel->orderBy('role_name', 'ASC')->findAll(),
            'departments' => $this->departmentModel->orderBy('name', 'ASC')->findAll()
        ];
        return view('dashboard/users/create', $data);
    }

    /**
     * Xử lý dữ liệu Submit từ Form tạo tài khoản (POST).
     */
    public function store()
    {
        // 1. Thu thập dữ liệu thô từ form (email, password, role_id, v.v.)
        $data = $this->request->getPost();
        
        // 2. Chuyển cho UserService xử lý nghiệp vụ:
        // - Validate dữ liệu (Email hợp lệ, Pass đủ mạnh...)
        // - Kiểm tra email trùng lặp.
        // - Hash mật khẩu bảo mật.
        // - Tạo bản ghi trong CSDL.
        $result = $this->userService->createUser($data);

        // 3. Phản hồi kết quả cho người dùng
        if ($result['status'] === 'success') {
            return redirect()->to('/users')->with('success', $result['message']);
        }

        // Nếu có lỗi (ví dụ: email đã tồn tại) -> Quay lại form, giữ lại input để người dùng ko phải nhập lại
        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Hiển thị giao diện Form cập nhật quyền và thông tin tài khoản.
     * @param int $id ID của người dùng cần chỉnh sửa
     */
    public function edit(int $id)
    {
        // Yêu cầu Service lôi dữ liệu user này lên. 
        // Service đồng thời kiểm tra xem người đang thực hiện có quyền sửa user này không.
        $result = $this->userService->getUserById($id);
        
        if (!$result['status']) {
            return redirect()->to('/users')->with('error', $result['message']);
        }

        $data = [
            'title' => 'Cập nhật phân quyền / tài khoản | L.A.N ERP',
            'user'  => $result['data'],
            'roles' => $this->roleModel->findAll(),
            'departments' => $this->departmentModel->findAll(),
            // Truyền Role hiện tại của người đang thao tác để UI có logic ẩn/hiện phù hợp
            'currentRoleName' => session()->get('role_name') 
        ];
        return view('dashboard/users/edit', $data);
    }

    /**
     * Xử lý cập nhật dữ liệu tài khoản (mật khẩu, vai trò, trạng thái khóa).
     * @param int $id ID người dùng
     */
    public function update(int $id)
    {
        // Lấy dữ liệu thay đổi từ form
        $data = $this->request->getPost();
        
        // Chuyển toàn bộ dữ liệu xuống UserService.
        // Bên trong service sẽ có các logic bảo mật:
        // - Nhân viên thường ko thể tự nâng cấp Role của mình.
        // - Chỉ Admin mới được khóa tài khoản người khác.
        $result = $this->userService->updateUser($id, $data);

        if ($result['status'] === 'success') {
            return redirect()->to('/users')->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Xóa một tài khoản khỏi hệ thống.
     * @param int $id ID người dùng cần xóa
     */
    public function delete(int $id)
    {
        // Kiểm tra an toàn: Không cho phép User tự xóa chính mình từ giao diện này
        if ($id == session()->get('user_id')) {
            return redirect()->to('/users')->with('error', 'Bạn không thể tự xóa tài khoản của chính mình.');
        }

        // Ủy quyền hẳn cho Service thực hiện thao tác xóa và kiểm tra ràng buộc (Foreign keys)
        $result = $this->userService->deleteUser($id);
        
        if ($result['status'] === 'success') {
            return redirect()->to('/users')->with('success', $result['message']);
        }

        return redirect()->to('/users')->with('error', $result['message']);
    }

    /**
     * Xóa hàng loạt tài khoản (Bulk Action).
     * Yêu cầu quyền Admin tối cao.
     */
    public function bulkDelete()
    {
        $roleName = session()->get('role_name');
        
        // Bảo vệ API khỏi các truy cập trái phép
        if ($roleName != \Config\AppConstants::ROLE_ADMIN) {
            return $this->response->setJSON([
                'code' => 1,
                'error' => 'Chỉ Quản trị viên hệ thống mới có quyền thực hiện xóa hàng loạt.'
            ]);
        }

        // Lấy danh sách IDs từ yêu cầu AJAX
        $ids = $this->request->getPost('ids');
        
        if (empty($ids) || !is_array($ids)) {
            return $this->response->setJSON([
                'code' => 1,
                'error' => 'Danh sách chọn trống. Vui lòng chọn ít nhất một tài khoản.'
            ]);
        }

        $successCount = 0;
        $failCount = 0;

        // Lặp qua từng ID để thực hiện xóa đơn lẻ thông qua Service (để đảm bảo Logic nghiệp vụ đồng nhất)
        foreach ($ids as $id) {
            // Không được xóa chính mình trong danh sách hàng loạt
            if ($id == session()->get('user_id')) {
                $failCount++;
                continue;
            }

            $result = $this->userService->deleteUser((int)$id);
            if ($result['status'] === 'success') {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        // Trả về kết quả dạng JSON để Front-end hiển thị thông báo popup hoặc toast
        if ($successCount > 0) {
            return $this->response->setJSON([
                'code' => 0,
                'message' => "Đã dọn dẹp xong {$successCount} tài khoản." . ($failCount > 0 ? " Bỏ qua {$failCount} tài khoản lỗi/không đủ quyền." : '')
            ]);
        }

        return $this->response->setJSON([
            'code' => 1,
            'error' => 'Hệ thống từ chối thực hiện xóa các tài khoản đã chọn.'
        ]);
    }
}
