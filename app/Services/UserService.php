<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\EmployeeModel;
use App\Models\RoleModel;

/**
 * UserService
 * 
 * Lớp Dịch vụ xử lý tất cả các nghiệp vụ lõi (business logic) liên quan đến quản lý Tài khoản người dùng.
 * Bao gồm: Lọc danh sách theo phân quyền, Thêm, Sửa (vai trò/mật khẩu), và Xóa tài khoản.
 */
class UserService extends BaseService
{
    protected $userModel;
    protected $employeeModel;
    protected $roleModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->employeeModel = new EmployeeModel();
        $this->roleModel = new RoleModel();
    }

    /**
     * Lấy danh sách tài khoản hợp lệ dựa trên hạng mức phân quyền của người đang thao tác.
     * 
     * @return array Danh sách tài khoản kèm theo thông tin Nhân viên và Bộ phận.
     */
    public function getUsers()
    {
        // Lấy thông tin quyền và bộ phận của người dùng hiện tại từ Session
        $roleName = session()->get('role_name');
        $departmentId = session()->get('department_id');

        // Khởi tạo câu truy vấn kết nối (JOIN) thông tin từ bảng users sang bảng employees và roles, departments
        $this->userModel->select('users.*, roles.name as role_title, employees.full_name, employees.department_id, departments.name as department_name')
                        ->join('roles', 'roles.id = users.role_id', 'left')
                        ->join('employees', 'employees.user_id = users.id', 'left')
                        ->join('departments', 'departments.id = employees.department_id', 'left');

        if ($roleName == \Config\AppConstants::ROLE_ADMIN || $roleName == \Config\AppConstants::ROLE_MOD) {
            // Cấp cao nhất (Admin, Giám đốc/Mod): Xem được tất cả tài khoản hệ thống
            return $this->userModel->findAll();
        } elseif ($roleName == \Config\AppConstants::ROLE_TRUONG_PHONG) {
            // Cấp Trưởng phòng: Chỉ được xem tài khoản của nhân viên có cùng Department ID (thuộc cùng bộ phận)
            if ($departmentId) {
                return $this->userModel->where('employees.department_id', $departmentId)->findAll();
            }
            // Nếu trưởng phòng không thuộc bộ phận nào, trả về rỗng
            return [];
        }

        // Nếu là Nhân viên thông thường hoặc Thực tập sinh: Không có quyền truy vấn dữ liệu này
        return [];
    }

    /**
     * Lấy thôngত্তি chi tiết một tài khoản cụ thể (có kiểm tra quyền).
     * 
     * @param int $id ID của tài khoản cần lấy thông tin
     * @return array Trạng thái success/fail kèm data
     */
    public function getUserById(int $id)
    {
        // Tìm thông tin gốc kèm với phòng ban của họ
        $user = $this->userModel->select('users.*, employees.full_name, employees.department_id')
                                ->join('employees', 'employees.user_id = users.id', 'left')
                                ->where('users.id', $id)
                                ->first();
        if (!$user) {
            return $this->fail('Không tìm thấy tài khoản trong hệ thống.');
        }

        // Tương tự, dựa vào session người thao tác để quyết định xem có được xem chi tiết không
        $roleName = session()->get('role_name');
        $departmentId = session()->get('department_id');

        if ($roleName == \Config\AppConstants::ROLE_TRUONG_PHONG) {
            // Trưởng phòng chỉ được xem chi tiết nếu người đó chung phòng ban
            if ($user['department_id'] != $departmentId) {
                return $this->fail('Bạn không có quyền xem thông tin tài khoản của bộ phận khác.');
            }
        } elseif ($roleName != \Config\AppConstants::ROLE_ADMIN && $roleName != \Config\AppConstants::ROLE_MOD) {
             // Các cấp thấp hơn (nhân viên, TTS) bị chặn
             return $this->fail('Bạn không có thẩm quyền để soi thông tin tài khoản này.');
        }

        return $this->success($user);
    }

    /**
     * Khởi tạo và Lưu trữ một Tài khoản mới vào CSDL.
     * 
     * @param array $data Dữ liệu Mật khẩu, Email, Vai trò...
     * @return array Trạng thái
     */
    public function createUser(array $data)
    {
        $roleName = session()->get('role_name');
        // Chỉ đích danh Admin mới được tạo mới tài khoản
        if ($roleName != \Config\AppConstants::ROLE_ADMIN) {
            return $this->fail('Truy cập bị từ chối: Chỉ Admin mới có đặc quyền khởi tạo tài khoản hệ thống.');
        }

        // Mã hóa mật khẩu chuẩn BCRYPT an toàn trước khi đẩy vào Database
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        // Luôn đặt trạng thái rảnh rỗi = 1 (Đang kích hoạt) mặc định
        $data['active_status'] = 1;

        if ($this->userModel->insert($data)) {
            $userId = $this->userModel->getInsertID();
            return $this->success(['id' => $userId], 'Đã khởi tạo tài khoản bảo mật thành công.');
        }

        return $this->fail('Lỗi hệ thống: Tham số không hợp lệ hoặc Email có thể đã tồn tại.');
    }

    /**
     * Chỉnh sửa cấu hình tài khoản (Role/Quyền, Trạng thái Khóa, Reset Mật khẩu).
     * 
     * @param int $id ID User bị sửa
     * @param array $data Dữ liệu truyền lên từ View
     * @return array
     */
    public function updateUser(int $id, array $data)
    {
        $roleName = session()->get('role_name');
        $departmentId = session()->get('department_id');
        
        // Gọi lại hàm kiểm tra quyền xem gốc để biết mình có quyền touch vào user này không
        $targetUser = $this->getUserById($id);
        if (!$targetUser['status']) {
            return $targetUser;
        }

        // Dựa vào phân quyền để kiểm soát mức độ Chỉnh sửa 
        if ($roleName == \Config\AppConstants::ROLE_ADMIN) {
            // Admin có quyền chóp bu: Cho phép sửa Role, Đổi MK qua mặt xác thực, Mở/Khóa account
            if (!empty($data['password'])) {
                // Nếu form gửi MK, tiến hành hash
                $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            } else {
                // Nếu trống thì bỏ qua (Không đổi MK)
                unset($data['password']);
            }
        } elseif ($roleName == \Config\AppConstants::ROLE_MOD || $roleName == \Config\AppConstants::ROLE_TRUONG_PHONG) {
            // Mod (Giám đốc) và Trưởng phòng chỉ được cấp phép THAY ĐỔI VAI TRÒ
            $allowedData = [];
            if (isset($data['role_id'])) {
                $allowedData['role_id'] = $data['role_id'];
            }
            $data = $allowedData; // Gạt bỏ tất cả các parameter độc hại khác bị bypass chèn vào
            
            // Một nguyên tắc cơ bản (Priority Rule): Không ai được phép hạ bệ Admin ngoại trừ chính Admin
            if ($targetUser['data']['role_title'] == \Config\AppConstants::ROLE_ADMIN) {
                return $this->fail('Giới hạn mức quyền: Không thể thay thế hay sửa đổi Vai trò của quản trị viên cấp cao.');
            }
        } else {
            // Trường hợp bypass phòng thủ trái phép
            return $this->fail('Vượt rào hệ thống: Không có thẩm quyền thao tác sửa đổi.');
        }

        // Bắt đầu cập nhật thông tin
        if ($this->userModel->update($id, $data)) {
            return $this->success(null, 'Cập nhật phân quyền / thông số thành công.');
        }

        return $this->fail('Lỗi cập nhật CSDL.');
    }

    /**
     * Vô hiệu hóa và tiêu hủy vĩnh viễn tài khoản trong Database.
     */
    public function deleteUser(int $id)
    {
        $roleName = session()->get('role_name');
        
        // Check rào cản tối đa
        if ($roleName != \Config\AppConstants::ROLE_ADMIN) {
            return $this->fail('Nghiêm cấm: Thao tác xóa dữ liệu mật thiết chỉ dành riêng cho Admin.');
        }

        if ($this->userModel->delete($id)) {
            return $this->success(null, 'Đã gỡ vĩnh viễn tài khoản ra khỏi hệ thống.');
        }
        return $this->fail('Lỗi hệ thống: Không thể xóa tài khoản hiện tại.');
    }
}
