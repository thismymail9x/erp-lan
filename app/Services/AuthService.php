<?php

namespace App\Services;

use App\Models\BaseModel;
use CodeIgniter\Config\Services;

/**
 * AuthService
 * 
 * Dịch vụ xử lý xác thực người dùng, đăng ký và quản lý phân quyền.
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
        $this->userModel = model('UserModel');
        $this->session = Services::session();
        $this->mailService = new MailService();
        $this->logService = new SystemLogService();
    }

    /**
     * Xử lý đăng nhập người dùng
     * 
     * @param string $role_id
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login(string $email, string $password)
    {
        $user = $this->userModel->where('email', $email)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->fail('Email hoặc mật khẩu không chính xác.');
        }

        if ($user['active_status'] != 1) {
            return $this->fail('Tài khoản chưa được kích hoạt hoặc đã bị khóa. Vui lòng liên hệ Admin.');
        }

        // Thiết lập session
        $this->setSession($user);

        // Ghi log đăng nhập
        $this->logService->log('LOGIN', 'Auth', $user['id']);

        return $this->success($user, 'Đăng nhập thành công.');
    }

    /**
     * Xử lý đăng ký người dùng mới
     * 
     * @param array $data
     * @return array
     */
    public function register(array $data)
    {
        // Get email before hashing password
        $emailPrefix = explode('@', $data['email'])[0];

        // Mã hóa mật khẩu
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        // Mặc định vai trò là 'Thực tập sinh' nếu không chỉ định
        $roleModel = model('RoleModel');
        $defaultRole = $roleModel->where('name', \Config\AppConstants::ROLE_THUC_TAP_SINH)->first();
        $data['role_id'] = $data['role_id'] ?? ($defaultRole ? $defaultRole['id'] : null);
        $data['active_status'] = 0; // Requires admin activation

        $this->userModel->transStart();
        try {
            $this->userModel->insert($data);
            $userId = $this->userModel->getInsertID();

            // Default employee creation linked to "Pháp lý" (ID: 3)
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
            
            if ($this->userModel->transStatus() === false) {
                return $this->fail('Không thể đăng ký tài khoản. Vui lòng kiểm tra dữ liệu.');
            }

            return $this->success(null, 'Đăng ký tài khoản thành công.');
        } catch (\Exception $e) {
            $this->userModel->transRollback();
            $this->logError('Lỗi đăng ký: ' . $e->getMessage());
            return $this->fail('Email này đã được sử dụng.');
        }
    }

    /**
     * Đăng xuất người dùng
     */
    public function logout()
    {
        // Ghi log đăng xuất trước khi hủy session
        $this->logService->log('LOGOUT', 'Auth', session()->get('user_id'));
        $this->session->destroy();
    }

    /**
     * Kiểm tra trạng thái đăng nhập
     */
    public function isLoggedIn()
    {
        return $this->session->has('isLoggedIn');
    }

    /**
     * Tạo token reset mật khẩu và gửi email
     * 
     * @param string $email
     * @return array
     */
    public function forgotPassword(string $email)
    {
        $user = $this->userModel->where('email', $email)->first();

        if (!$user) {
            return $this->fail('Email không tồn tại trong hệ thống.');
        }

        $token = bin2hex(random_bytes(32));
        $resetModel = model('PasswordResetModel');

        // Xóa các token cũ của email này
        $resetModel->where('email', $email)->delete();

        // Lưu token mới (hết hạn sau 1 giờ)
        $resetModel->insert([
            'email'      => $email,
            'token'      => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);

        // Gửi Email thực tế qua MailService
        $resetLink = base_url("reset-password?token=" . $token);
        
        $emailSent = $this->mailService->sendWithTemplate(
            $email,
            'Đặt lại mật khẩu | L.A.N ERP',
            'emails/forgot_password',
            ['resetLink' => $resetLink]
        );

        if (!$emailSent) {
            return $this->fail('Không thể gửi email lúc này. Vui lòng thử lại sau.');
        }

        return $this->success(null, 'Chúng tôi đã gửi link đặt lại mật khẩu vào email của bạn.');
    }

    /**
     * Xác thực token reset mật khẩu
     * 
     * @param string $token
     * @return array
     */
    public function verifyResetToken(string $token)
    {
        $resetModel = model('PasswordResetModel');
        $reset = $resetModel->where('token', $token)
                            ->where('expires_at >', date('Y-m-d H:i:s'))
                            ->first();

        if (!$reset) {
            return $this->fail('Mã xác nhận không hợp lệ hoặc đã hết hạn.');
        }

        return $this->success($reset);
    }

    /**
     * Đặt lại mật khẩu mới
     * 
     * @param string $token
     * @param string $password
     * @return array
     */
    public function resetPassword(string $token, string $password)
    {
        $verify = $this->verifyResetToken($token);
        if (!$verify['status']) {
            return $verify;
        }

        $email = $verify['data']['email'];
        $user = $this->userModel->where('email', $email)->first();

        if (!$user) {
            return $this->fail('Người dùng không còn tồn tại.');
        }

        // Cập nhật mật khẩu mới
        $newPassword = password_hash($password, PASSWORD_BCRYPT);
        $this->userModel->update($user['id'], ['password' => $newPassword]);

        // Xóa token đã dùng
        model('PasswordResetModel')->where('email', $email)->delete();

        return $this->success(null, 'Mật khẩu của bạn đã được cập nhật thành công.');
    }

    /**
     * Thiết lập dữ liệu session
     */
    private function setSession($user)
    {
        $roleModel = model('RoleModel');
        $employeeModel = model('EmployeeModel');
        $deptModel = model('DepartmentModel');
        
        $role = $user['role_id'] ? $roleModel->find($user['role_id']) : null;
        $employee = $employeeModel->where('user_id', $user['id'])->first();
        $dept = ($employee && $employee['department_id']) ? $deptModel->find($employee['department_id']) : null;
        
        $data = [
            'user_id'         => $user['id'],
            'employee_id'     => $employee ? $employee['id'] : null,
            'role_id'         => $user['role_id'],
            'role_name'       => $role ? $role['name'] : \Config\AppConstants::ROLE_DEFAULT,
            'department_id'   => $employee ? $employee['department_id'] : null,
            'department_name' => $dept ? $dept['name'] : null,
            'full_name'       => $employee ? $employee['full_name'] : 'User',
            'email'           => $user['email'],
            'isLoggedIn'      => true,
        ];

        $this->session->set($data);
    }
}
