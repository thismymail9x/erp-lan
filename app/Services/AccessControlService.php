<?php

namespace App\Services;

/**
 * AccessControlService
 * 
 * Quản lý cấu trúc Menu và Quyền truy cập dựa trên Bộ phận (Department) và Chức danh (Role).
 */
class AccessControlService extends BaseService
{
    /**
     * Dựa trên phân quyền chi tiết của User để quyết định xem họ có thể view toàn bộ dữ liệu (All Data) hay không.
     */
    public function canViewAllData($roleName = null)
    {
        // Có thể view tất cả nếu là admin hoặc có quyền quản lý cấp cao
        return has_permission('sys.admin') || has_permission('case.manage');
    }

    /**
     * Lấy cấu trúc Menu động hoàn toàn dựa vào Custom Permissions
     */
    public function getSidebarMenu(?int $departmentId = null, ?string $roleName = null)
    {
        $menu = [
            ['title' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'fas fa-th-large'],
        ];

        // 1. Chấm công
        if (has_permission('attendance.view') || has_permission('sys.admin')) {
            $menu[] = ['title' => 'Chấm công', 'url' => 'attendance/list', 'icon' => 'fas fa-clock'];
        }

        // 2. Vụ việc pháp lý (Cấp cho nhân viên được phân công hoặc có quyền View)
        if (has_permission('case.view') || has_permission('case.manage')) {
            $menu[] = ['title' => 'Vụ việc pháp lý', 'url' => 'cases', 'icon' => 'fas fa-briefcase'];
        }
        
        // 3. Khách hàng
        if (has_permission('customer.view')) {
            $menu[] = ['title' => 'Khách hàng', 'url' => 'customers', 'icon' => 'fas fa-users'];
        }

        // 4. Quản trị Tài khoản & Nhân sự
        if (has_permission('user.view')) {
            $menu[] = ['title' => 'Tài khoản', 'url' => 'users', 'icon' => 'fas fa-users-cog'];
            $menu[] = ['title' => 'Nhân sự', 'url' => 'employees', 'icon' => 'fas fa-user-tie'];
        }

        // 5. Cài đặt hệ thống (Admin Tối Cao)
        if (has_permission('sys.admin')) {
            $menu[] = ['title' => 'Quy trình mẫu', 'url' => 'workflows', 'icon' => 'fas fa-project-diagram'];
            $menu[] = ['title' => 'Log hệ thống', 'url' => 'system-logs', 'icon' => 'fas fa-history'];
        }

        $uniqueMenu = [];
        $titles = [];
        foreach ($menu as $item) {
            if (!in_array($item['title'], $titles)) {
                $uniqueMenu[] = $item;
                $titles[] = $item['title'];
            }
        }
        return $uniqueMenu;
    }

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
