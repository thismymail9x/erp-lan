<?php

namespace App\Services;

/**
 * AccessControlService
 * 
 * Lớp dịch vụ điều hướng và kiểm soát truy cập bề mặt (UI/UX).
 * Chịu trách nhiệm:
 * 1. Xây dựng cấu trúc Menu động dựa trên quyền hạn thực tế của User (Dynamic Sidebar).
 * 2. Quyết định phạm vi dữ liệu mà User được nhìn thấy (Global vs Department vs Personal).
 */
class AccessControlService extends BaseService
{
    /**
     * Kiểm tra xem User có quyền "Bao quát" toàn bộ dữ liệu hệ thống hay không.
     * Thường dùng để quyết định có hiển thị bộ lọc "Toàn công ty" hay không.
     */
    public function canViewAllData($roleName = null)
    {
        // Trả về true nếu sở hữu quyền Admin tối cao hoặc quyền quản lý vụ việc tổng thể
        return has_permission('sys.admin') || has_permission('case.manage');
    }

    /**
     * Sinh danh mục Menu (Sidebar) cá nhân hóa cho từng người dùng.
     * Menu sẽ tự động co giãn (ẩn/hiện) các mục chức năng dựa theo permissions trong Session.
     * 
     * @param int|null $departmentId ID phòng ban (dùng để lọc bổ sung nếu cần)
     * @param string|null $roleName Tên vai trò
     * @return array Mảng danh sách các mục Menu hợp lệ.
     */
    public function getSidebarMenu(?int $departmentId = null, ?string $roleName = null)
    {
        // Khởi tạo menu với Dashboard là mục mặc định cho tất cả mọi người
        $menu = [
            ['title' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'fas fa-th-large'],
        ];

        // 1. MODULE CHẤM CÔNG (Attendance):
        if (session()->get('employee_id')) {
            if (has_permission('attendance.view') || has_permission('sys.admin')) {
                // Dành cho Quản lý/Admin: Xem bảng tổng quát hàng ngày
                $menu[] = ['title' => 'Quản lý chấm công', 'url' => 'attendance/list', 'icon' => 'fas fa-clock'];
            } else {
                // Dành cho Nhân viên: Xem lịch sử cá nhân theo tháng
                $menu[] = ['title' => 'Lịch sử chấm công', 'url' => 'attendance/list?view=monthly', 'icon' => 'fas fa-history'];
            }
            // Mục điểm danh trực tiếp cho tất cả nhân viên
//            $menu[] = ['title' => 'Điểm danh Camera', 'url' => 'attendance', 'icon' => 'fas fa-camera'];
        }

        // 2. MODULE VỤ VIỆC PHÁP LÝ (Legal Cases):
        // Chỉ hiện nếu là người có quyền quản lý hoặc được phép xem vụ việc
        if (has_permission('case.view') || has_permission('case.manage')) {
            $menu[] = ['title' => 'Vụ việc pháp lý', 'url' => 'cases', 'icon' => 'fas fa-briefcase'];
        }
        
        // 3. MODULE KHÁCH HÀNG (Customers):
        if (has_permission('customer.view')) {
            $menu[] = ['title' => 'Khách hàng', 'url' => 'customers', 'icon' => 'fas fa-id-card'];
        }

        // 3.5 MODULE QUẢN LÝ TÀI LIỆU (DMS):
        // Mọi thành viên đều được vào kho tài liệu (DMS), quyền xem chi tiết sẽ check bên trong Service
        if (session()->get('employee_id')) {
            $menu[] = ['title' => 'Tài liệu (DMS)', 'url' => 'documents', 'icon' => 'fas fa-folder-open'];
        }

        // 4. MODULE QUẢN TRỊ NHÂN SỰ & TÀI KHOẢN:
        if (has_permission('user.view')) {
            $menu[] = ['title' => 'Tài khoản', 'url' => 'users', 'icon' => 'fas fa-users-cog'];
            $menu[] = ['title' => 'Nhân sự', 'url' => 'employees', 'icon' => 'fas fa-user-tie'];
        } else {
            /** 
             * ĐỐI VỚI NHÂN VIÊN THƯỜNG:
             * Không hiện menu 'Tài khoản' chung, nhưng cho phép truy cập nhanh vào 'Hồ sơ cá nhân'.
             */
            $myEmpId = session()->get('employee_id');
            if ($myEmpId) {
                $menu[] = ['title' => 'Hồ sơ cá nhân', 'url' => 'employees/edit/' . $myEmpId, 'icon' => 'fas fa-user-tie'];
            }
        }

        // 5. CÀI ĐẶT HỆ THỐNG (System Settings):
        // Chỉ dành riêng cho Admin tối cao (Sys Admin)
        if (has_permission('sys.admin')) {
            $menu[] = ['title' => 'Quy trình mẫu', 'url' => 'workflows', 'icon' => 'fas fa-project-diagram'];
            $menu[] = ['title' => 'Log hệ thống', 'url' => 'system-logs', 'icon' => 'fas fa-history'];
        }

        // Lọc bỏ các mục trùng lặp (nếu có lỗi logic nạp) bằng phương thức uniqueMenu
        return $this->uniqueMenu($menu);
    }

    /**
     * Hàm hỗ trợ (Helper): Loại bỏ các mục Menu có tiêu đề giống hệt nhau.
     * Đảm bảo tính duy nhất và thẩm mỹ cho Sidebar.
     */
    private function uniqueMenu($menu)
    {
        $titles = [];
        return array_filter($menu, function($item) use (&$titles) {
            if (in_array($item['title'], $titles)) return false;
            $titles[] = $item['title'];
            return true;
        });
    }
}
