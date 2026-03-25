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
 * Đảm nhận các nhiệm vụ:
 * 1. Lọc và phân trang danh sách Users dựa theo cấp bậc người đang xem (Data Isolation).
 * 2. Khởi tạo tài khoản đi kèm với hồ sơ nhân viên (Synchronized Creation).
 * 3. Chỉnh sửa vai trò, mật khẩu và trạng thái (RBAC Update Logic).
 * 4. Kiểm soát quyền xóa và ghi nhật ký thay đổi (Auditing).
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
        // Khởi tạo các Model nòng cốt
        $this->userModel = new UserModel();
        $this->employeeModel = new EmployeeModel();
        $this->roleModel = new RoleModel();
        $this->logService = new SystemLogService();
    }

    /**
     * Truy xuất danh sách tài khoản theo cấp độ phân quyền (Context-Aware Listing).
     * 
     * @param string $sort Tiêu chí sắp xếp.
     * @param string $order Hướng sắp xếp (asc/desc).
     * @param int $perPage Số lượng bản ghi trên một trang.
     * @param string $search Từ khóa tìm kiếm (Email hặc Tên).
     * @return array Danh sách kết quả đã được lọc và phân trang.
     */
    public function getUsers(string $sort = 'id', string $order = 'desc', int $perPage = 10, string $search = '')
    {
        // 1. Xác định danh tính và bộ phận của người đang thao tác
        $roleName = session()->get('role_name');
        $departmentId = session()->get('department_id');

        // 2. Bản đồ ánh xạ các trường sắp xếp từ giao diện sang tên cột thực tế trong Database
        $sortMap = [
            'role'   => 'roles.name',
            'status' => 'users.active_status',
            'email'  => 'users.email',
            'id'     => 'users.id'
        ];

        $orderField = $sortMap[$sort] ?? 'users.id';
        $direction  = (strtolower($order) === 'asc') ? 'asc' : 'desc';

        // 3. Xây dựng câu lệnh Query: Kết nối 4 bảng để lấy thông tin đầy đủ nhất
        $query = $this->userModel->select('users.*, roles.name as role_title, employees.full_name, employees.id as emp_id, employees.department_id, departments.name as department_name')
                        ->join('roles', 'roles.id = users.role_id', 'left')
                        ->join('employees', 'employees.user_id = users.id', 'left')
                        ->join('departments', 'departments.id = employees.department_id', 'left');

        // 4. Áp dụng bộ lọc tìm kiếm (Like Query trên nhiều cột)
        if (!empty($search)) {
            $query->groupStart()
                   // Tìm theo Email tài khoản
                  ->like('users.email', $search)
                   // Hoặc tìm theo họ tên nhân sự
                  ->orLike('employees.full_name', $search)
                  ->groupEnd();
        }

        // 5. Áp dụng sắp xếp
        $query->orderBy($orderField, $direction);

        // 6. LOGIC PHÂN TÁCH DỮ LIỆU (DATA ISOLATION):
        if ($roleName == \Config\AppConstants::ROLE_ADMIN || $roleName == \Config\AppConstants::ROLE_MOD) {
            // Cấp lãnh đạo/Quản trị: Xem được mọi tài khoản trong hệ thống
            return $query->paginate($perPage);
        } elseif ($roleName == \Config\AppConstants::ROLE_TRUONG_PHONG) {
            /**
             * Cấp Trưởng phòng: 
             * Chỉ được phép xem và quản trị các tài khoản của nhân viên thuộc đúng phòng ban của mình.
             */
            if ($departmentId) {
                $query->where('employees.department_id', $departmentId);
                return $query->paginate($perPage);
            }
            return [];
        }

        // Các vai trò khác không có quyền xem danh sách tài khoản
        return [];
    }

    /**
     * Trả về công cụ hỗ trợ phân trang (Pager) của Model.
     * Sử dụng để tạo thanh điều hướng (1, 2, 3...) ở View.
     */
    public function getPager()
    {
        return $this->userModel->pager;
    }

    /**
     * Lấy thông tin chi tiết một tài khoản kèm kiểm tra an ninh (Security Check).
     * 
     * @param int $id ID User cần lấy
     * @return array Trạng thái và dữ liệu
     */
    public function getUserById(int $id)
    {
        // Truy vấn thông tin người dùng kèm theo bộ phận làm việc
        $user = $this->userModel->select('users.*, roles.name as role_title, employees.full_name, employees.department_id')
                                ->join('roles', 'roles.id = users.role_id', 'left')
                                ->join('employees', 'employees.user_id = users.id', 'left')
                                ->where('users.id', $id)
                                ->first();
                                
        if (!$user) {
            return $this->fail('Hồ sơ tài khoản không tồn tại trên hệ thống.');
        }

        // KIỂM TRA QUYỀN TRUY CẬP:
        $roleName = session()->get('role_name');
        $departmentId = session()->get('department_id');

        if ($roleName == \Config\AppConstants::ROLE_TRUONG_PHONG) {
            // Trưởng phòng chỉ được xem nếu User đó chung bộ phận
            if ($user['department_id'] != $departmentId) {
                return $this->fail('Bạn không thể truy cập hồ sơ của nhân sự ngoài bộ phận.');
            }
        } elseif ($roleName != \Config\AppConstants::ROLE_ADMIN && $roleName != \Config\AppConstants::ROLE_MOD) {
             // Các cấp dưới bị từ chối truy cập API này hoàn toàn
             return $this->fail('Quyền truy cập bị giới hạn.');
        }

        return $this->success($user);
    }

    /**
     * Quy trình khởi tạo Tài khoản và Hồ sơ nhân viên đồng thời.
     * 
     * @param array $data Dữ liệu tổng hợp (User Data + Employee Data)
     * @return array
     */
    public function createUser(array $data)
    {
        $roleName = session()->get('role_name');
        // Chỉ Admin mới có quyền gieo (seed) tài khoản mới vào hệ thống
        if ($roleName != \Config\AppConstants::ROLE_ADMIN) {
            return $this->fail('Thao tác trái thẩm quyền: Chỉ Quản trị viên mới được phép tạo tài khoản.');
        }

        // BẢO MẬT: Mã hóa mật khẩu ngay khi nhận được dữ liệu
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        // Tài khoản được tạo bởi Admin sẽ được kích hoạt (Active) ngay lập tức
        $data['active_status'] = 1;

        // Bóc tách dữ liệu của bảng Employees ra khỏi mảng dữ liệu User chung
        $employeeData = [];
        if (isset($data['department_id'])) {
            $employeeData['department_id'] = $data['department_id'];
            unset($data['department_id']);
        }
        if (isset($data['full_name'])) {
            $employeeData['full_name'] = $data['full_name'];
            unset($data['full_name']);
        }

        // Thực hiện ghi vào CSDL
        if ($this->userModel->insert($data)) {
            $userId = $this->userModel->getInsertID();

            // Tự động tạo bản ghi hồ sơ nhân viên để đồng bộ dữ liệu (tránh mồ côi)
            if (!empty($employeeData)) {
                $employeeData['user_id'] = $userId;
                $employeeData['position'] = $employeeData['position'] ?? 'Chưa xác định';
                $this->employeeModel->insert($employeeData);
            }

            // Ghi nhận hành động vào nhật ký hệ thống
            $this->logService->log('CREATE', 'Users', $userId, ['email' => $data['email']]);

            return $this->success(['id' => $userId], 'Đã khởi tạo tài khoản và hồ sơ nhân viên thành công.');
        }

        return $this->fail('Lỗi đăng ký: Thông tin không hợp lệ hoặc Email đã được sử dụng.');
    }

    /**
     * Cập nhật thông số tài khoản và vai trò (RBAC Update Logic).
     * 
     * @param int $id ID tài khoản
     * @param array $data Dữ liệu chỉnh sửa
     */
    public function updateUser(int $id, array $data)
    {
        $roleName = session()->get('role_name');
        
        // 1. Phải có quyền xem thông tin User thì mới được quyền Sửa
        $targetUser = $this->getUserById($id);
        if (!$targetUser['status']) {
            return $targetUser;
        }

        // 2. LOGIC PHÂN QUYỀN SỬA ĐỔI:
        if ($roleName == \Config\AppConstants::ROLE_ADMIN) {
            // ADMIN: Được phép sửa mọi thứ, bao gồm cả đổi Mật khẩu trực tiếp
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            } else {
                unset($data['password']); // Không chỉnh sửa nếu để trống
            }
        } elseif ($roleName == \Config\AppConstants::ROLE_MOD || $roleName == \Config\AppConstants::ROLE_TRUONG_PHONG) {
            /**
             * CẤP QUẢN LÝ:
             * Chỉ được phép THAY ĐỔI VAI TRÒ (Role) của nhân viên cho phù hợp công việc.
             * Không được phép can thiệp vào Mật khẩu hoặc Trạng thái khóa (Bảo vệ tính riêng tư).
             */
            $allowedData = [];
            if (isset($data['role_id'])) {
                $allowedData['role_id'] = $data['role_id'];
            }
            $data = $allowedData; // Lọc sạch các trường khác gửi kèm lên
            
            // BẢO VỆ ADMIN: Các cấp quản lý chung ko thể sửa đổi vai trò của Admin cấp cao.
            if ($targetUser['data']['role_title'] == \Config\AppConstants::ROLE_ADMIN) {
                return $this->fail('Vi phạm quyền hạn: Bạn không thể sửa đổi thông số của Quản trị viên.');
            }
        } else {
            return $this->fail('Thao tác bị từ chối.');
        }

        // 3. Tách dữ liệu hồ sơ nhân sự
        $employeeData = [];
        if (isset($data['department_id'])) {
            $employeeData['department_id'] = $data['department_id'];
            unset($data['department_id']);
        }
        if (isset($data['full_name'])) {
            $employeeData['full_name'] = $data['full_name'];
            unset($data['full_name']);
        }

        // Lưu bản ghi gốc để so sánh và ghi log (Audit Trail)
        $oldData = $this->userModel->find($id);

        // 4. Thực thi cập nhật
        if ($this->userModel->update($id, $data)) {
            $newData = $this->userModel->find($id);
            
            // Tính toán sự khác biệt giữa cũ và mới để ghi log chi tiết
            $changes = [
                'before' => array_diff_assoc($oldData, $newData),
                'after'  => array_diff_assoc($newData, $oldData)
            ];

            // Đồng bộ lại hồ sơ bảng Employees nếu có thay đổi tên/phòng ban
            if (!empty($employeeData)) {
                $employee = $this->employeeModel->where('user_id', $id)->first();
                if ($employee) {
                    $this->employeeModel->update($employee['id'], $employeeData);
                } else {
                    // Nếu chưa có hồ sơ nhân viên, tạo mới
                    $employeeData['user_id'] = $id;
                    $employeeData['position'] = 'Chưa xác định';
                    $this->employeeModel->insert($employeeData);
                }
            }

            // Ghi log bảo mật
            $this->logService->log('UPDATE', 'Users', $id, $changes);

            return $this->success(null, 'Đã cập nhật cấu hình tài khoản cá nhân.');
        }

        return $this->fail('Thất bại: Lỗi cơ sở dữ liệu khi cập nhật.');
    }

    /**
     * Tiêu hủy tài khoản vĩnh viễn (Chỉ Admin).
     */
    public function deleteUser(int $id)
    {
        $roleName = session()->get('role_name');
        
        // Kiểm soát rủi ro mất dữ liệu ngẫu nhiên
        if ($roleName != \Config\AppConstants::ROLE_ADMIN) {
            return $this->fail('Nghiêm cấm: Thao tác tiêu hủy dữ loại chỉ dành riêng cho Admin tối cao.');
        }

        // Bản ghi cũ để lưu vết lịch sử
        $oldData = $this->userModel->find($id);

        if ($this->userModel->delete($id)) {
            // Nhật ký tiêu hủy
            $this->logService->log('DELETE', 'Users', $id, ['deleted_account' => $oldData['email']]);
            return $this->success(null, 'Đã gỡ bỏ tài khoản hoàn toàn khỏi hệ thống.');
        }
        return $this->fail('Lỗi: Không thể thực hiện lệnh xóa lúc này.');
    }

    /**
     * Tổng hợp số liệu thống kê hiện trạng người dùng cho Dashboard.
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
