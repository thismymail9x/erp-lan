<?php

namespace App\Controllers;

/**
 * DashboardController
 * 
 * Trung tâm điều khiển chính của hệ thống ERP.
 * Hiển thị trang nhắm mắt (Overview) dành cho nhân viên và quản lý sau khi đăng nhập.
 * Trình bày các chỉ số nhanh: Trạng thái chấm công, Thông báo mới, và Tổng quan quyền hạn.
 */
class DashboardController extends BaseController
{
    /**
     * Hiển thị trang chủ Dashboard.
     */
    public function index()
    {
        // 1. KIỂM TRA PHIÊN (Session Check): 
        // Bắt buộc người dùng phải đăng nhập mới được truy cập vào giao diện quản trị.
        if (!session()->has('isLoggedIn')) {
            return redirect()->to('/login');
        }

        // 2. KHỞI TẠO TIỆN ÍCH DASHBOARD (Widgets):
        // Lấy thông tin trạng thái chấm công hôm nay của nhân sự hiện tại.
        $attendanceService = new \App\Services\AttendanceService();
        $employeeId = session()->get('employee_id');
        $attendanceStatus = null;
        
        if ($employeeId) {
            // Kiểm tra xem nhân viên đã Check-in hay Check-out chưa để hiển thị Nút/Trạng thái tương ứng.
            $attendanceStatus = $attendanceService->getTodayStatus($employeeId);
        }

        // 3. ĐÓNG GÓI DỮ LIỆU (Data Packaging):
        $data = [
            'title'            => 'Bảng điều khiển | L.A.N ERP',
            'attendanceStatus' => $attendanceStatus, // Thông tin chấm công realtime
            'user'  => [
                'email' => session()->get('email'), // Hiển thị email định danh
                // Map ID vai trò sang tên hiển thị (Localization)
                'role'  => session()->get('role_id') == 1 ? 'Quản trị viên' : 'Nhân viên'
            ]
        ];

        // 4. RENDER GIAO DIỆN CHÍNH
        return view('dashboard/index', $data);
    }
}
