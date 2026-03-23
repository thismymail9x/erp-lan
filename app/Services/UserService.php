<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\EmployeeModel;
use App\Models\RoleModel;
use App\Services\SystemLogService;

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
    protected $logService;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->employeeModel = new EmployeeModel();
        $this->roleModel = new RoleModel();
        $this->logService = new SystemLogService();
    }

    /**
     * Lấy danh sách tài khoản hợp lệ dựa trên hạng mức phân quyền, kèm sorting và phân trang.
     * 
     * @param string $sort Trường cần sắp xếp
     * @param string $order Hướng sắp xếp (asc/desc)
     * @param int $perPage Số bản ghi mỗi trang
     * @return array Danh sách tài khoản đã phân trang.
     */
    public function getUsers(string $sort = 'id', string $order = 'desc', int $perPage = 10, string $search = '')
    {
        // Lấy thông tin quyền và bộ phận của người dùng hiện tại từ Session
        $roleName = session()->get('role_name');
        $departmentId = session()->get('department_id');

        // Mapping các trường sort thân thiện sang trường trong DB thực tế
        $sortMap = [
            'role'   => 'roles.name',
            'status' => 'users.active_status',
            'email'  => 'users.email',
            'id'     => 'users.id'
        ];

        $orderField = $sortMap[$sort] ?? 'users.id';
        $direction  = (strtolower($order) === 'asc') ? 'asc' : 'desc';

        // Khởi tạo câu truy vấn kết nối (JOIN)
        $query = $this->userModel->select('users.*, roles.name as role_title, employees.full_name, employees.id as emp_id, employees.department_id, departments.name as department_name')
                        ->join('roles', 'roles.id = users.role_id', 'left')
                        ->join('employees', 'employees.user_id = users.id', 'left')
                        ->join('departments', 'departments.id = employees.department_id', 'left');

        // Áp dụng bộ lọc tìm kiếm nếu có
        if (!empty($search)) {
            $query->groupStart()
                  ->like('users.email', $search)
                  ->orLike('employees.full_name', $search)
                  ->groupEnd();
        }

        $query->orderBy($orderField, $direction);

        if ($roleName == \Config\AppConstants::ROLE_ADMIN || $roleName == \Config\AppConstants::ROLE_MOD) {
            // Cấp cao nhất: Xem được tất cả. Sử dụng paginate để hỗ trợ phân trang.
            return $query->paginate($perPage);
        } elseif ($roleName == \Config\AppConstants::ROLE_TRUONG_PHONG) {
            // Cấp Trưởng phòng: Chỉ được xem tài khoản của nhân viên cùng bộ phận
            if ($departmentId) {
                $query->where('employees.department_id', $departmentId);
                return $query->paginate($perPage);
            }
            return [];
        }

        return [];
    }

    /**
     * Trả về object pager của UserModel để render links ở View
     */
    public function getPager()
    {
        return $this->userModel->pager;
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

        // Tách dữ liệu nhân viên (nếu có)
        $employeeData = [];
        if (isset($data['department_id'])) {
            $employeeData['department_id'] = $data['department_id'];
            unset($data['department_id']);
        }
        if (isset($data['full_name'])) {
            $employeeData['full_name'] = $data['full_name'];
            unset($data['full_name']);
        }

        if ($this->userModel->insert($data)) {
            $userId = $this->userModel->getInsertID();

            // Khởi tạo hồ sơ nhân viên cơ bản nếu có thông tin
            if (!empty($employeeData)) {
                $employeeData['user_id'] = $userId;
                $employeeData['position'] = $employeeData['position'] ?? 'Chưa xác định';
                $this->employeeModel->insert($employeeData);
            }

            // Ghi log
            $this->logService->log('CREATE', 'Users', $userId, ['email' => $data['email']]);

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

        // Tách dữ liệu nhân viên
        $employeeData = [];
        if (isset($data['department_id'])) {
            $employeeData['department_id'] = $data['department_id'];
            unset($data['department_id']);
        }
        if (isset($data['full_name'])) {
            $employeeData['full_name'] = $data['full_name'];
            unset($data['full_name']);
        }

        // Lấy dữ liệu cũ để đối soát
        $oldData = $this->userModel->find($id);

        // Bắt đầu cập nhật thông tin User
        if ($this->userModel->update($id, $data)) {
            $newData = $this->userModel->find($id);
            
            // Ghi log chi tiết thay đổi
            $changes = [
                'before' => array_diff_assoc($oldData, $newData),
                'after'  => array_diff_assoc($newData, $oldData)
            ];

            // Cập nhật thông tin phòng ban/tên ở bảng Employees
            if (!empty($employeeData)) {
                $employee = $this->employeeModel->where('user_id', $id)->first();
                if ($employee) {
                    $this->employeeModel->update($employee['id'], $employeeData);
                } else {
                    $employeeData['user_id'] = $id;
                    $employeeData['position'] = 'Chưa xác định';
                    $this->employeeModel->insert($employeeData);
                }
            }

            // Ghi log
            $this->logService->log('UPDATE', 'Users', $id, $changes);

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

        $oldData = $this->userModel->find($id);

        if ($this->userModel->delete($id)) {
            // Ghi log
            $this->logService->log('DELETE', 'Users', $id, ['deleted_record' => $oldData]);
            return $this->success(null, 'Đã gỡ vĩnh vễn tài khoản ra khỏi hệ thống.');
        }
        return $this->fail('Lỗi hệ thống: Không thể xóa tài khoản hiện tại.');
    }

    /**
     * Lấy số liệu thống kê tổng quan cho module User
     */
    public function getStats()
    {
        // Lấy danh sách số lượng người dùng theo từng vai trò
        $roleBreakdown = $this->userModel->select('roles.name as role_name, COUNT(users.id) as count')
                                         ->join('roles', 'roles.id = users.role_id', 'left')
                                         ->groupBy('users.role_id')
                                         ->findAll();

        return [
            'total'          => $this->userModel->countAllResults(),
            'active'         => $this->userModel->where('active_status', 1)->countAllResults(),
            'inactive'       => $this->userModel->where('active_status', 0)->countAllResults(),
            'role_breakdown' => $roleBreakdown
        ];
    }
}
