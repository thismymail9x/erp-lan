<?php

namespace App\Controllers;

use App\Services\AuthService;

/**
 * AuthController
 * 
 * Bộ điều khiển trung tâm cho các hoạt động Xác thực người dùng.
 * Quản lý luồng Đăng nhập, Đăng ký, Quên mật khẩu và các tính năng bảo mật nâng cao như Impersonate.
 */
class AuthController extends BaseController
{
    protected $authService;
    protected $utilityService;

    public function __construct()
    {
        // Khởi tạo AuthService để xử lý nghiệp vụ xác thực và mã hóa
        $this->authService = new AuthService();
        // Khởi tạo UtilityService để lấy các nội dung phụ trợ (câu nói hay, tiện ích...)
        $this->utilityService = new \App\Services\UtilityService();
    }

    /**
     * Hiển thị giao diện trang Đăng nhập.
     * Kiểm tra nếu đã đăng nhập thì tự động chuyển hướng vào Dashboard.
     */
    public function login()
    {
        // Nếu Session người dùng đã tồn tại (đã login trước đó)
        if ($this->authService->isLoggedIn()) {
            return redirect()->to('/dashboard');
        }

        // Chuẩn bị dữ liệu hiển thị trang login: Câu châm ngôn ngẫu nhiên
        $data = [
            'quote' => $this->utilityService->getTodayQuote()
        ];

        // Trả về view auth/login kèm dữ liệu trang trí
        return view('auth/login', $data);
    }

    /**
     * Xử lý dữ liệu khi người dùng bấm nút "Đăng nhập".
     * Nhận email và mật khẩu từ POST Request.
     */
    public function attemptLogin()
    {
        // Lấy thông tin đầu vào từ form
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // Gọi AuthService thực hiện kiểm tra thông tin trong CSDL và tạo Session
        $result = $this->authService->login($email, $password);

        // Kiểm tra kết quả trả về từ nghiệp vụ đăng nhập
        if ($result['status'] === 'success') {
            // Đăng nhập thành công -> Chuyển hướng tới trang chính kèm thông báo chào mừng
            return redirect()->to('/dashboard')->with('message', 'Chào mừng trở lại!');
        }

        // Đăng nhập thất bại -> Quay lại trang login, giữ lại Input và ném thông báo lỗi
        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Hiển thị trang đăng ký
     */
    public function register()
    {
        if ($this->authService->isLoggedIn()) {
            return redirect()->to('/dashboard');
        }
        return view('auth/register');
    }

    /**
     * Xử lý dữ liệu gửi lên từ biểu mẫu Đăng ký.
     */
    public function attemptRegister()
    {
        // Gói dữ liệu đăng ký sơ bộ
        $data = [
            'email'    => $this->request->getPost('email'), // Địa chỉ email đăng ký
            'password' => $this->request->getPost('password'), // Mật khẩu thô (sẽ được hash trong service)
            'role_id'  => 3, // Mặc định gán vai trò: Nhân viên khi đăng ký tự do
        ];

        // Giao việc lưu trữ cho AuthService
        $result = $this->authService->register($data);

        // Nếu tạo tài khoản thành công
        if ($result['status'] === 'success') {
            // Chuyển về trang login và thông báo chờ Admin kích hoạt (Pending status)
            return redirect()->to('/login')->with('message', 'Đăng ký thành công. Tài khoản đang chờ Admin kích hoạt.');
        }

        // Nếu trùng email hoặc validate lỗi -> Quay lại form
        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Hủy phiên làm việc hiện tại (Logout).
     */
    public function logout()
    {
        // Gọi service để clear toàn bộ dữ liệu Session
        $this->authService->logout();
        // Đuổi người dùng về trang login
        return redirect()->to('/login');
    }

    /**
     * Hiển thị trang yêu cầu reset mật khẩu
     */
    public function forgotPassword()
    {
        return view('auth/forgot_password', ['title' => 'Quên mật khẩu | L.A.N ERP']);
    }

    /**
     * Xử lý yêu cầu gửi link đặt lại mật khẩu qua Email.
     */
    public function attemptForgotPassword()
    {
        // Lấy email người dùng cung cấp
        $email = $this->request->getPost('email');
        // AuthService sẽ kiểm tra email tồn tại và sinh Token bảo mật gửi đi
        $result = $this->authService->forgotPassword($email);

        // Thông báo kết quả cho người dùng (Thành công hoặc Không tìm thấy email)
        if ($result['status'] === 'success') {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Hiển thị giao diện thiết lập mật khẩu mới sau khi bấm vào Link từ Email.
     */
    public function resetPassword()
    {
        // Lấy mã Token bảo mật từ URL (GET)
        $token = $this->request->getGet('token');
        // Xác minh Token xem có hợp lệ và còn thời hạn hay không
        $verify = $this->authService->verifyResetToken($token);

        // Nếu token sai hoặc hết hạn -> Buộc đăng nhập lại hoặc yêu cầu reset lại
        if (!$verify['status']) {
            return redirect()->to('/login')->with('error', $verify['message']);
        }

        // Token hợp lệ -> Chuyển sang View nhập mật khẩu mới
        return view('auth/reset_password', [
            'title' => 'Đặt lại mật khẩu | L.A.N ERP',
            'token' => $token
        ]);
    }

    /**
     * Xử lý việc cập nhật mật khẩu mới vào CSDL.
     */
    public function attemptResetPassword()
    {
        // Thu thập thông tin từ form reset
        $token = $this->request->getPost('token');
        $password = $this->request->getPost('password');
        $confirm = $this->request->getPost('password_confirm');

        // Kiểm tra khớp mật khẩu cấp độ UI
        if ($password !== $confirm) {
            return redirect()->back()->with('error', 'Mật khẩu xác nhận không khớp.');
        }

        // Gọi service thực hiện đổi mật khẩu trong DB và hủy Token
        $result = $this->authService->resetPassword($token, $password);

        if ($result['status'] === 'success') {
            return redirect()->to('/login')->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Quyền Admin: Tạm thời đăng nhập vào một tài khoản người dùng khác (Login As).
     * @param int $userId ID người dùng cần đăng nhập hộ
     */
    public function impersonate($userId)
    {
        // Thực hiện nghiệp vụ tráo đổi Session tạm thời
        $result = $this->authService->impersonate((int)$userId);

        if ($result['status'] === 'success') {
            // Chuyển hướng vào Dashboard với danh tính của nhân viên kia
            return redirect()->to('/dashboard')->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Thoát chế độ "đăng nhập hộ" và quay lại phiên làm việc của Admin ban đầu.
     */
    public function stopImpersonating()
    {
        // Khôi phục Session của Admin từ bộ nhớ tạm
        $result = $this->authService->stopImpersonating();

        if ($result['status'] === 'success') {
            // Quay lại trang quản lý người dùng
            return redirect()->to('/users')->with('success', $result['message']);
        }

        // Nếu có lỗi hệ thống, buộc out hẳn để đảm bảo bảo mật
        return redirect()->to('/login')->with('error', $result['message']);
    }
}
