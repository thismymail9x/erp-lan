<?php

namespace App\Controllers;

use App\Services\AuthService;

/**
 * AuthController
 * 
 * Điều phối các yêu cầu đăng nhập, đăng ký và đăng xuất.
 */
class AuthController extends BaseController
{
    protected $authService;
    protected $utilityService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->utilityService = new \App\Services\UtilityService();
    }

    /**
     * Hiển thị trang đăng nhập
     */
    public function login()
    {
        if ($this->authService->isLoggedIn()) {
            return redirect()->to('/dashboard');
        }

        $data = [
            'quote' => $this->utilityService->getTodayQuote()
        ];

        return view('auth/login', $data);
    }

    /**
     * Xử lý gửi biểu mẫu đăng nhập
     */
    public function attemptLogin()
    {
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $result = $this->authService->login($email, $password);

        if ($result['status'] === 'success') {
            return redirect()->to('/dashboard')->with('message', 'Chào mừng trở lại!');
        }

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
     * Xử lý gửi biểu mẫu đăng ký
     */
    public function attemptRegister()
    {
        $data = [
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
            'role_id'  => 3, // Vai trò nhân viên mặc định
        ];

        $result = $this->authService->register($data);

        if ($result['status'] === 'success') {
            return redirect()->to('/login')->with('message', 'Đăng ký thành công. Tài khoản đang chờ Admin kích hoạt.');
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Đăng xuất
     */
    public function logout()
    {
        $this->authService->logout();
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
     * Xử lý yêu cầu gửi link reset mật khẩu
     */
    public function attemptForgotPassword()
    {
        $email = $this->request->getPost('email');
        $result = $this->authService->forgotPassword($email);

        if ($result['status'] === 'success') {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Hiển thị trang đặt lại mật khẩu mới
     */
    public function resetPassword()
    {
        $token = $this->request->getGet('token');
        $verify = $this->authService->verifyResetToken($token);

        if (!$verify['status']) {
            return redirect()->to('/login')->with('error', $verify['message']);
        }

        return view('auth/reset_password', [
            'title' => 'Đặt lại mật khẩu | L.A.N ERP',
            'token' => $token
        ]);
    }

    /**
     * Xử lý xác nhận đặt lại mật khẩu mới
     */
    public function attemptResetPassword()
    {
        $token = $this->request->getPost('token');
        $password = $this->request->getPost('password');
        $confirm = $this->request->getPost('password_confirm');

        if ($password !== $confirm) {
            return redirect()->back()->with('error', 'Mật khẩu xác nhận không khớp.');
        }

        $result = $this->authService->resetPassword($token, $password);

        if ($result['status'] === 'success') {
            return redirect()->to('/login')->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
