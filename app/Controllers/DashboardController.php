<?php

namespace App\Controllers;

/**
 * DashboardController
 * 
 * Trang quản trị chính sau khi đăng nhập thành công.
 */
class DashboardController extends BaseController
{
    public function index()
    {
        if (!session()->has('isLoggedIn')) {
            return redirect()->to('/login');
        }

        $data = [
            'title' => 'Bảng điều khiển | LawFirm ERP',
            'user'  => [
                'email' => session()->get('email'),
                'role'  => session()->get('role_id') == 1 ? 'Quản trị viên' : 'Nhân viên'
            ]
        ];

        return view('dashboard/index', $data);
    }
}
