<?php

namespace App\Services;

use App\Models\BaseModel;
use CodeIgniter\Config\Services;

/**
 * AuthService
 * 
 * Lớp dịch vụ tối quan trọng xử lý toàn bộ vòng đời xác thực của người dùng.
 * Bao gồm: Đăng nhập (Login), Đăng ký (Register), Quên mật khẩu (Forgot Password), 
 * và đặc biệt là tính năng Giả dạng tài khoản (Impersonate) dành cho Quản trị viên.
 */
class AuthService extends BaseService
{
    protected $userModel;
    protected $session;
    protected $mailService;
    protected $logService;

    public function __construct()
    {
        parent::__construct();
        // Nạp Model người dùng và các dịch vụ hỗ trợ (Session, Email, Nhật ký hệ thống)
        $this->userModel = model('UserModel');
        $this->session = Services::session();
        $this->mailService = new MailService();
        $this->logService = new SystemLogService();
    }

    /**
     * Xác thực thông tin đăng nhập từ Email và Mật khẩu.
     * @param string $email Địa chỉ email người dùng cung cấp
     * @param string $password Mật khẩu thô (chưa hash)
     * @return array Kết quả xử lý (success/fail) kèm dữ liệu hoặc thông báo
     */
    public function login(string $email, string $password)
    {
        // 1. Tìm người dùng trong CSDL dựa trên email duy nhất
        $user = $this->userModel->where('email', $email)->first();

        // 2. Kiểm tra tồn tại và khớp mật khẩu (sử dụng password_verify an toàn)
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->fail('Email hoặc mật khẩu không chính xác.');
        }

        // 3. Kiểm tra trạng thái hoạt động (0: Bị khóa/Chờ duyệt, 1: Đang hoạt động)
        if ($user['active_status'] != 1) {
            return $this->fail('Tài khoản chưa được kích hoạt hoặc đã bị khóa. Vui lòng liên hệ Admin.');
        }

        // 4. Thiết lập dữ liệu định danh vào Session (Phiên làm việc)
        $this->setSession($user);

        // 5. Ghi nhận sự kiện đăng nhập vào nhật ký hệ thống để giám sát bảo mật
        $this->logService->log('LOGIN', 'Auth', $user['id']);

        return $this->success($user, 'Đăng nhập thành công.');
    }

    /**
     * Khởi tạo tài khoản mới cho người dùng tự đăng ký hoặc được thêm mới.
     * @param array $data Dữ liệu người dùng (email, password thô, ...)
     * @return array
     */
    public function register(array $data)
    {
        // Lấy tiền tố email để đặt tên hiển thị tạm thời (VD: 'admin' từ 'admin@example.com')
        $emailPrefix = explode('@', $data['email'])[0];

        // 1. Mã hóa mật khẩu bằng thuật toán BCRYPT trước khi lưu vào CSDL
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // 2. Xác định vai trò (Role): Ưu tiên Thực tập sinh nế không chỉ định
        $roleModel = model('RoleModel');
        $defaultRole = $roleModel->where('name', \Config\AppConstants::ROLE_THUC_TAP_SINH)->first();
        $data['role_id'] = $data['role_id'] ?? ($defaultRole ? $defaultRole['id'] : null);
        
        // 3. Tài khoản mới mặc định ở trạng thái Chờ duyệt (0) để đảm bảo an toàn
        $data['active_status'] = 0; 

        // Sử dụng Transaction để đảm bảo tính toàn vẹn dữ liệu khi ghi vào nhiều bảng
        $this->userModel->transStart();
        try {
            // Lưu vào bảng users (Tài khoản)
            $this->userModel->insert($data);
            $userId = $this->userModel->getInsertID();

            // Tự động tạo bản ghi hồ sơ nhân viên (Employees) tương ứng
            // Mặc định ném vào bộ phận "Pháp lý" để bộ phận nhân sự xử lý sau
            $employeeModel = model('EmployeeModel');
            $employeeModel->insert([
                'user_id' => $userId,
                'department_id' => \Config\AppConstants::DEPT_PHAP_LY,
                'full_name' => 'Nhân sự mới (' . $emailPrefix . ')',
                'position' => 'Chưa xác định',
                'salary_base' => 0,
                'join_date' => date('Y-m-d')
            ]);
            
            $this->userModel->transComplete();
            
            // Kiểm tra trạng thái giao dịch Database
            if ($this->userModel->transStatus() === false) {
                return $this->fail('Không thể đăng ký tài khoản. Vui lòng kiểm tra dữ liệu.');
            }

            return $this->success(null, 'Đăng ký tài khoản thành công.');
        } catch (\Exception $e) {
            $this->userModel->transRollback();
            $this->logError('Lỗi đăng ký: ' . $e->getMessage());
            // Lỗi phổ biến nhất ở đây là Duplicate Key (Trùng email)
            return $this->fail('Email này đã được sử dụng hoặc dữ liệu không hợp lệ.');
        }
    }

    /**
     * Hủy bỏ phiên làm việc và dọn dẹp bộ nhớ Session.
     */
    public function logout()
    {
        // Ghi log sự kiện thoát để phục vụ báo cáo thời gian làm việc
        $userId = session()->get('user_id');
        if ($userId) {
            $this->logService->log('LOGOUT', 'Auth', $userId);
        }
        
        // Hủy hoàn toàn dữ liệu session hiện tại
        $this->session->destroy();
    }

    /**
     * Kiểm tra xem trình duyệt hiện tại có phiên đăng nhập hợp lệ hay không.
     */
    public function isLoggedIn()
    {
        return $this->session->has('isLoggedIn');
    }

    /**
     * Quy trình Quên mật khẩu: Tạo mã xác thực (Token) và gửi Link Reset qua Email.
     * @param string $email
     * @return array
     */
    public function forgotPassword(string $email)
    {
        // 1. Kiểm tra email có tồn tại chính thức trong hệ thống không
        $user = $this->userModel->where('email', $email)->first();
        if (!$user) {
            return $this->fail('Email không tồn tại trong hệ thống.');
        }

        // 2. Sinh mã Token bảo mật ngẫu nhiên (64 ký tự hex)
        $token = bin2hex(random_bytes(32));
        $resetModel = model('PasswordResetModel');

        // Dọn dẹp các yêu cầu reset cũ của email này để tránh xung đột
        $resetModel->where('email', $email)->delete();

        // 3. Lưu Token mới kèm thời gian hết hạn (thường là 1 giờ)
        $resetModel->insert([
            'email'      => $email,
            'token'      => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);

        // 4. Tạo đường dẫn reset mật khẩu hoàn chỉnh
        $resetLink = base_url("reset-password?token=" . $token);
        
        // 5. Sử dụng MailService để gửi email định dạng HTML cho người dùng
        $emailSent = $this->mailService->sendWithTemplate(
            $email,
            'Đặt lại mật khẩu | L.A.N ERP',
            'emails/forgot_password',
            ['resetLink' => $resetLink]
        );

        if (!$emailSent) {
            return $this->fail('Lỗi máy chủ gửi thư: Không thể gửi email lúc này.');
        }

        return $this->success(null, 'Hệ thống đã gửi hướng dẫn đặt lại mật khẩu vào hòm thư của bạn.');
    }

    /**
     * Kiểm tra tính hợp lệ và thời hạn của mã Reset Token.
     * @param string $token
     */
    public function verifyResetToken(string $token)
    {
        $resetModel = model('PasswordResetModel');
        // Tìm bản ghi khớp token và chưa hết hạn (expires_at > now)
        $reset = $resetModel->where('token', $token)
                            ->where('expires_at >', date('Y-m-d H:i:s'))
                            ->first();

        if (!$reset) {
            return $this->fail('Đường dẫn xác nhận không hợp lệ hoặc đã hết hạn (chỉ có hiệu lực trong 60 phút).');
        }

        return $this->success($reset);
    }

    /**
     * Thực hiện cập nhật mật khẩu mới dứt điểm vào CSDL.
     * @param string $token Mã xác thực
     * @param string $password Mật khẩu mới người dùng đặt
     */
    public function resetPassword(string $token, string $password)
    {
        // Tái xác thực token một lần nữa trước khi ghi đè dữ liệu
        $verify = $this->verifyResetToken($token);
        if (!$verify['status']) {
            return $verify;
        }

        $email = $verify['data']['email'];
        $user = $this->userModel->where('email', $email)->first();

        if (!$user) {
            return $this->fail('Yêu cầu thất bại: Người dùng liên kết không còn tồn tại.');
        }

        // 1. Mã hóa mật khẩu mới
        $newPassword = password_hash($password, PASSWORD_BCRYPT);
        // 2. Cập nhật vào bảng Users
        $this->userModel->update($user['id'], ['password' => $newPassword]);

        // 3. Xóa token đã sử dụng để ngăn chặn tấn công Replay (dùng lại link cũ)
        model('PasswordResetModel')->where('email', $email)->delete();

        return $this->success(null, 'Mật khẩu của bạn đã được cập nhật thành công. Vui lòng đăng nhập lại.');
    }

    /**
     * Quyền năng Admin: Đăng nhập dưới danh nghĩa của một người dùng khác (Impersonation).
     * Dùng để kiểm tra lỗi người dùng báo cáo mà không cần biết mật khẩu của họ.
     * @param int $targetUserId ID tài khoản muốn giả dạng
     */
    public function impersonate(int $targetUserId)
    {
        $currentUserId = session()->get('user_id');
        $currentRole = session()->get('role_name');

        // BẢO MẬT: Chỉ 'Admin' mới được sử dụng tính năng này
        if ($currentRole !== \Config\AppConstants::ROLE_ADMIN) {
            return $this->fail('Cảnh báo bảo mật: Bạn không được phép sử dụng quyền năng này.');
        }

        // Không cho phép Admin "giả dạng" chính mình (vô nghĩa)
        if ($currentUserId == $targetUserId) {
            return $this->fail('Bạn đã đang ở trong tài khoản của chính mình.');
        }

        // Kiểm tra đối tượng đích có tồn tại và đang hoạt động không
        $targetUser = $this->userModel->find($targetUserId);
        if (!$targetUser || $targetUser['active_status'] != 1) {
            return $this->fail('Mục tiêu không tồn tại hoặc tài khoản đang bị khóa.');
        }

        /**
         * LƯU TRỮ PHIÊN LÀM VIỆC GỐC:
         * Ta lưu Admin ID thực sự vào một key riêng (admin_user_id) 
         * để sau này có thể "quay lại" mà không mất phiên làm việc Admin.
         */
        $adminId = session()->get('admin_user_id') ?: $currentUserId;
        
        // Ghi đè session hiện tại bằng dữ liệu của người dùng đích
        $this->setSession($targetUser);
        
        // Đánh dấu flag đang giả dạng để UI hiển thị thông báo (Thanh Top Bar màu đỏ/vàng cảnh báo)
        session()->set([
            'admin_user_id' => $adminId,
            'is_impersonating' => true
        ]);

        // Nhật ký hệ thống: Giám sát chặt chẽ hành động Login As để tránh lạm dụng quyền
        $this->logService->log('IMPERSONATE_START', 'Auth', (int)$targetUserId, [
            'note' => "Quản trị viên (ID: $adminId) đã truy cập vào tài khoản (ID: $targetUserId)"
        ]);

        return $this->success(null, 'Chuyển đổi danh tính thành công: ' . $targetUser['email']);
    }

    /**
     * Dừng phiên làm việc "giả dạng" và khôi phục lại danh tính Admin ban đầu.
     */
    public function stopImpersonating()
    {
        // Lấy lại ID Admin đã lưu trữ lúc nãy
        $adminId = session()->get('admin_user_id');
        
        if (!$adminId) {
            return $this->fail('Lỗi logic: Không tìm thấy thông tin phiên làm việc quản trị gốc.');
        }

        $adminUser = $this->userModel->find($adminId);
        if (!$adminUser) {
            return $this->fail('Thất bại: Tài khoản Admin gốc không thể khôi phục.');
        }

        // 1. Nạp lại dữ liệu Session cho Admin
        $this->setSession($adminUser);
        
        // 2. Xóa bỏ các cờ (flag) liên quan đến việc giả dạng
        session()->remove('admin_user_id');
        session()->remove('is_impersonating');

        // Ghi log kết thúc phiên giả dạng
        $this->logService->log('IMPERSONATE_STOP', 'Auth', (int)$adminId, [
            'note' => "Quản trị viên đã rời khỏi phiên giả dạng và quay lại tài khoản gốc"
        ]);

        return $this->success(null, 'Đã khôi phục quyền Quản trị viên.');
    }

    /**
     * Private Helper: Tổng hợp và nạp tất cả thông tin định danh vào Session.
     * Quy trình này bao gồm việc nạp dữ liệu từ nhiều bảng (Users, Roles, Employees, Departments).
     */
    private function setSession($user)
    {
        // Khởi tạo các Model liên quan
        $roleModel = model('RoleModel');
        $employeeModel = model('EmployeeModel');
        $deptModel = model('DepartmentModel');
        
        // Thu thập thông tin vai trò, hồ sơ nhân viên và phòng ban từ CSDL
        $role = $user['role_id'] ? $roleModel->find($user['role_id']) : null;
        $employee = $employeeModel->where('user_id', $user['id'])->first();
        $dept = ($employee && $employee['department_id']) ? $deptModel->find($employee['department_id']) : null;
        
        // Đóng gói mảng dữ liệu Session chuẩn
        $data = [
            'user_id'         => $user['id'],
            'employee_id'     => $employee ? $employee['id'] : null,
            'role_id'         => $user['role_id'],
            // Nếu không có role cụ thể, gán role mặc định thấp nhất
            'role_name'       => $role ? $role['name'] : \Config\AppConstants::ROLE_DEFAULT,
            'department_id'   => $employee ? $employee['department_id'] : null,
            'department_name' => $dept ? $dept['name'] : null,
            'full_name'       => $employee ? $employee['full_name'] : 'Thành viên mới',
            'email'           => $user['email'],
            'isLoggedIn'      => true,
        ];

        // Ghi dữ liệu vào Session Driver (thường lưu ở File hoặc Database)
        $this->session->set($data);

        /**
         * NẠP CÂY PHÂN QUYỀN (RBAC):
         * Sau khi có thông tin cơ bản, ta cần nạp các quyền (Permissions) chi tiết của User này.
         * PermissionService sẽ tính toán dựa trên Role mặc định và các quyền ghi đè (Override) riêng lẻ.
         */
        $permService = new \App\Services\PermissionService();
        $permService->loadUserPermissions($user['id'], $user['role_id']);
    }
}
