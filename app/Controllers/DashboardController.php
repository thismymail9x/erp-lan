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

        $attendanceService = new \App\Services\AttendanceService();
        $employeeId = session()->get('employee_id');
        $attendanceStatus = null;
        
        if ($employeeId) {
            $attendanceStatus = $attendanceService->getTodayStatus($employeeId);
        }

        $data = [
            'title'            => 'Bảng điều khiển | L.A.N ERP',
            'attendanceStatus' => $attendanceStatus,
            'user'  => [
                'email' => session()->get('email'),
                'role'  => session()->get('role_id') == 1 ? 'Quản trị viên' : 'Nhân viên'
            ]
        ];

        return view('dashboard/index', $data);
    }
}
