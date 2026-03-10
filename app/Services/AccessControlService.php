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
     * Kiểm tra xem người dùng có quyền xem toàn bộ dữ liệu hệ thống không
     */
    public function canViewAllData(string $roleName)
    {
        return in_array($roleName, \Config\AppConstants::PRIVILEGED_ROLES);
    }

    /**
     * Lấy cấu trúc Menu dựa trên Department và Role
     */
    public function getSidebarMenu(?int $departmentId, ?string $roleName)
    {
        $roleName = $roleName ?? \Config\AppConstants::ROLE_DEFAULT;
        // Nếu là Admin, trả về toàn bộ menu
        if ($roleName === \Config\AppConstants::ROLE_ADMIN) {
            return $this->getFullMenu();
        }

        $menu = $this->getCommonMenu();

        // Menu theo Bộ phận (Department)
        $departmentMenu = $this->getMenuByDepartment($departmentId);
        $menu = array_merge($menu, $departmentMenu);

        // Menu/Tính năng cộng thêm theo Chức danh (Role)
        $roleSpecificMenu = $this->getMenuByRole($roleName);
        $menu = array_merge($menu, $roleSpecificMenu);

        return $this->uniqueMenu($menu);
    }

    private function getFullMenu()
    {
        return [
            ['title' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'fas fa-th-large'],
            ['title' => 'Tài khoản', 'url' => 'users', 'icon' => 'fas fa-users-cog'],
            ['title' => 'Nhân viên', 'url' => 'employees', 'icon' => 'fas fa-user-tie'],
            ['title' => 'Vụ việc pháp lý', 'url' => 'cases', 'icon' => 'fas fa-briefcase'],
            ['title' => 'Khách hàng', 'url' => 'customers', 'icon' => 'fas fa-users'],
            ['title' => 'Hợp đồng', 'url' => 'contracts', 'icon' => 'fas fa-file-contract'],
            ['title' => 'Chấm công', 'url' => 'attendance', 'icon' => 'fas fa-clock'],
            ['title' => 'Kế toán', 'url' => 'accounting', 'icon' => 'fas fa-calculator'],
            ['title' => 'Marketing', 'url' => 'marketing', 'icon' => 'fas fa-bullhorn'],
            ['title' => 'Kinh doanh', 'url' => 'sales', 'icon' => 'fas fa-handshake'],
            ['title' => 'Hành chính', 'url' => 'admin-tasks', 'icon' => 'fas fa-tasks'],
            ['title' => 'Đối tác', 'url' => 'partners', 'icon' => 'fas fa-handshake-alt'],
        ];
    }

    private function getCommonMenu()
    {
        return [
            ['title' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'fas fa-th-large'],
        ];
    }

    private function getMenuByDepartment(?int $departmentId)
    {
        switch ($departmentId) {
            case \Config\AppConstants::DEPT_MARKETING:
                return [['title' => 'Marketing', 'url' => 'marketing', 'icon' => 'fas fa-bullhorn']];
            case \Config\AppConstants::DEPT_SALE:
                return [['title' => 'Kinh doanh', 'url' => 'sales', 'icon' => 'fas fa-handshake']];
            case \Config\AppConstants::DEPT_PHAP_LY:
                return [
                    ['title' => 'Vụ việc pháp lý', 'url' => 'cases', 'icon' => 'fas fa-briefcase'],
                    ['title' => 'Khách hàng', 'url' => 'customers', 'icon' => 'fas fa-users'],
                    ['title' => 'Hợp đồng', 'url' => 'contracts', 'icon' => 'fas fa-file-contract'],
                ];
            case \Config\AppConstants::DEPT_HANH_CHINH:
                return [
                    ['title' => 'Nhân viên', 'url' => 'employees', 'icon' => 'fas fa-user-tie'],
                    ['title' => 'Hành chính', 'url' => 'admin-tasks', 'icon' => 'fas fa-tasks'],
                ];
            default:
                return [];
        }
    }

    private function getMenuByRole(string $roleName)
    {
        switch ($roleName) {
            case \Config\AppConstants::ROLE_TRUONG_PHONG:
                return [
                    ['title' => 'Báo cáo bộ phận', 'url' => 'dept-reports', 'icon' => 'fas fa-chart-line'],
                    ['title' => 'Tài khoản', 'url' => 'users', 'icon' => 'fas fa-users-cog']
                ];
            case \Config\AppConstants::ROLE_MOD:
                return [
                    ['title' => 'Điều hành hệ thống', 'url' => 'mod-panel', 'icon' => 'fas fa-cogs'],
                    ['title' => 'Tài khoản', 'url' => 'users', 'icon' => 'fas fa-users-cog']
                ];
            default:
                return [];
        }
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
