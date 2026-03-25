<?php

namespace App\Controllers;

use App\Services\SystemLogService;

/**
 * SystemLogController
 * 
 * Bộ điều khiển quản lý và hiển thị Nhật ký hệ thống (Audit Trail).
 * Chức năng:
 * 1. Cung cấp lịch sử hoạt động của tất cả người dùng (Đăng nhập, Thay đổi dữ liệu, Bảo mật).
 * 2. Hỗ trợ truy vấn log theo thời gian, hành động và đối tượng thực hiện.
 */
class SystemLogController extends BaseController
{
    protected $logService;

    public function __construct()
    {
        // Khởi tạo Service quản lý log
        $this->logService = new SystemLogService();
    }

    /**
     * Hiển thị danh sách Nhật ký hệ thống.
     * Trang này bị giới hạn truy cập cực kỳ nghiêm ngặt (Chỉ dành cho Quản trị viên tối cao).
     */
    public function index()
    {
        // BẢO MẬT: Chỉ cho phép người dùng có vai trò 'Admin' truy cập.
        if (session()->get('role_name') !== \Config\AppConstants::ROLE_ADMIN) {
            return redirect()->to('/dashboard')->with('error', 'Cảnh báo bảo mật: Bạn không có đặc quyền truy cập khu vực lưu trữ nhật ký hệ thống.');
        }

        // 1. Thu thập các bộ lọc (Filters) từ URL cho mục đích tra cứu (Searching)
        $filters = [
            'date'    => $this->request->getGet('date'),    // Lọc theo ngày cụ thể
            'action'  => $this->request->getGet('action'),  // Lọc theo loại hành động (vd: LOGIN, UPDATE...)
            'user_id' => $this->request->getGet('user_id'), // Lọc theo nhân viên thực hiện
        ];

        // 2. Lấy danh sách tài khoản để hiển thị trong Selectbox tìm kiếm
        $userModel = new \App\Models\UserModel();

        $data = [
            'title'   => 'Nhật ký hệ thống | L.A.N ERP',
            'logs'    => $this->logService->getLogs($filters), // Truy vấn log đã được lọc và phân trang
            'pager'   => $this->logService->getPager(),        // Cung cấp đối tượng phân trang (Pager) cho View
            'filters' => $filters,
            'users'   => $userModel->select('id, email')->findAll() // Danh sách user tối giản để lọc
        ];

        // 3. Trả về giao diện danh sách
        return view('dashboard/system_logs/index', $data);
    }
}
