<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (!function_exists('has_permission')) {
    /**
     * Kiểm tra nhanh xem người dùng hiện tại có quyền thực thi hành động hay không.
     * Cờ 'sys.admin' luôn trả về true cho mọi quyền.
     *
     * @param string $actionKey Mã quyền (VD: 'case.manage', 'user.view')
     * @return bool
     */
    function has_permission(string $actionKey): bool
    {
        $session = \Config\Services::session();
        $userPerms = $session->get('permissions');

        if (!is_array($userPerms)) {
            return false;
        }

        $cleanKey = trim($actionKey);

        if (in_array('sys.admin', $userPerms)) {
            return true;
        }

        return in_array($cleanKey, $userPerms);
    }
}
if (!function_exists('get_available_tags')) {
    /**
     * Lấy toàn bộ danh sách nhãn dán (Tags) có sẵn trong hệ thống.
     * Thường dùng cho các bộ lọc hoặc ô chọn nhãn đa năng.
     *
     * @param string $entityType Loại thực thể (vd: 'cases', 'customers', 'documents')
     * @return array
     */
    function get_available_tags(string $entityType = 'all'): array
    {
        $tagService = new \App\Services\TagService();
        return $tagService->getAvailableTags($entityType);
    }
}

if (!function_exists('get_available_employees')) {
    /**
     * Truy xuất danh sách nhân sự đang hoạt động.
     * Hỗ trợ lọc theo phòng ban để thu hẹp phạm vi chọn.
     *
     * @param int|null $deptId ID phòng ban cần lọc
     * @return array
     */
    function get_available_employees(?int $deptId = null): array
    {
        $model = new \App\Models\EmployeeModel();
        $query = $model->select('employees.*, roles.name as role_name')
                       ->join('users', 'users.id = employees.user_id', 'inner')
                       ->join('roles', 'roles.id = users.role_id', 'inner')
                       ->where('employees.deleted_at', null)
                       ->where('users.active_status', 1)
                       ->where('users.deleted_at', null);

        if ($deptId !== null) {
            $query->where('employees.department_id', $deptId);
        }

        return $query->orderBy('employees.full_name', 'ASC')->findAll();
    }
}

if (!function_exists('get_active_customers')) {
    /**
     * Lấy danh sách khách hàng đang hoạt động trong hệ thống.
     *
     * @return array
     */
    function get_active_customers(): array
    {
        $model = new \App\Models\CustomerModel();
        return $model->where('deleted_at', null)
                     ->orderBy('name', 'ASC')
                     ->findAll();
    }
}

if (!function_exists('get_active_cases')) {
    /**
     * Lấy danh sách các vụ việc/hồ sơ pháp lý đang vận hành.
     *
     * @return array
     */
    function get_active_cases(): array
    {
        $model = new \App\Models\CaseModel();
        return $model->where('deleted_at', null)
                     ->select('id, code, title')
                     ->orderBy('id', 'DESC')
                     ->findAll();
    }
}

if (!function_exists('get_departments')) {
    /**
     * Lấy danh sách toàn bộ phòng ban trong công ty.
     *
     * @return array
     */
    function get_departments(): array
    {
        $model = new \App\Models\DepartmentModel();
        return $model->orderBy('name', 'ASC')->findAll();
    }
}

if (!function_exists('get_available_roles')) {
    /**
     * Lấy danh sách toàn bộ các vai trò (Roles) trong hệ thống.
     *
     * @return array
     */
    function get_available_roles(): array
    {
        $model = new \App\Models\RoleModel();
        return $model->orderBy('id', 'ASC')->findAll();
    }
}
if (!function_exists('get_phone_variants')) {
    /**
     * Tạo ra các biến thể số điện thoại để khớp chính xác trong tìm kiếm (đặc biệt giữa Zalo OA và CRM).
     * Ví dụ: "0987654321" -> ["0987654321", "84987654321", "+84987654321", "987654321"]
     * 
     * @param string $phone Số điện thoại đầu vào
     * @return array
     */
    function get_phone_variants(string $phone): array
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (empty($clean)) {
            return [];
        }

        $variants = [$clean];

        // Nếu bắt đầu bằng 84, thêm bản thể đầu 0 và không đầu số
        if (strpos($clean, '84') === 0 && strlen($clean) > 2) {
            $noCountry = substr($clean, 2);
            $variants[] = '0' . $noCountry;
            $variants[] = $noCountry;
            $variants[] = '+84' . $noCountry;
        }
        // Nếu bắt đầu bằng 0, thêm bản thể đầu 84, +84 và không đầu số
        elseif (strpos($clean, '0') === 0 && strlen($clean) > 1) {
            $noZero = substr($clean, 1);
            $variants[] = '84' . $noZero;
            $variants[] = '+84' . $noZero;
            $variants[] = $noZero;
        }
        else {
            $variants[] = '0' . $clean;
            $variants[] = '84' . $clean;
            $variants[] = '+84' . $clean;
        }

        // Tạo thêm các biến thể có định dạng phổ biến khác
        return array_unique($variants);
    }
}

if (!function_exists('format_seconds_to_duration')) {
    /**
     * Chuyển đổi số giây thành chuỗi thời gian thân thiện bằng tiếng Việt.
     * Ví dụ: 5400 giây -> "1 giờ 30 phút", 90000 giây -> "1 ngày 1 giờ"
     * 
     * @param int $seconds Số giây cần định dạng
     * @return string Chuỗi hiển thị tiếng Việt
     */
    function format_seconds_to_duration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0 phút';
        }

        $days = floor($seconds / 86400);
        $seconds %= 86400;

        $hours = floor($seconds / 3600);
        $seconds %= 3600;

        $minutes = floor($seconds / 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = "{$days} ngày";
            if ($hours > 0) {
                $parts[] = "{$hours} giờ";
            }
        } elseif ($hours > 0) {
            $parts[] = "{$hours} giờ";
            if ($minutes > 0) {
                $parts[] = "{$minutes} phút";
            }
        } else {
            $parts[] = "{$minutes} phút";
        }

        return implode(' ', $parts);
    }
}
