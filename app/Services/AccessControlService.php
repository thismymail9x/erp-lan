<?php

namespace App\Services;

/**
 * AccessControlService
 *
 * Xây dựng metadata menu sidebar dựa trên quyền truy cập.
 * Chịu trách nhiệm:
 * 1. Sinh cấu trúc menu từ quyền hiện tại của người dùng.
 * 2. Xác định người dùng có được xem dữ liệu toàn công ty hay không.
 */
class AccessControlService extends BaseService
{
    protected $featureConfig;

    public function __construct()
    {
        $this->featureConfig = config('FeatureGuidelines');
    }

    /**
     * Kiểm tra người dùng hiện tại có quyền xem dữ liệu toàn hệ thống hay không.
     * Dùng cho các màn hình có bộ lọc phạm vi toàn công ty.
     */
    public function canViewAllData($roleName = null)
    {
        // Admin và các quyền view_all/edit_all được xem dữ liệu toàn hệ thống.
        return has_permission('sys.admin') || has_permission('case.edit_all') || has_permission('case.view_all') || has_permission('customer.view_all');
    }

    /**
     * Sinh các mục menu sidebar theo vai trò và bộ quyền hiện tại.
     *
     * @param int|null $departmentId ID phòng ban, dùng để lọc bổ sung khi cần.
     * @param string|null $roleName Tên vai trò hiện tại.
     * @return array Danh sách mục menu hợp lệ.
     */
    public function getSidebarMenu(?int $departmentId = null, ?string $roleName = null)
    {
        $isAdmin = ($roleName === \Config\AppConstants::ROLE_ADMIN || has_permission('sys.admin'));

        if ($isAdmin) {
            $menu = [
                ['title' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'fas fa-th-large'],
            ];

            $subReport = [];
            if (has_permission('zalo.performance')) {
                $subReport[] = ['title' => 'Hi&#7879;u su&#7845;t t&#432; v&#7845;n', 'url' => 'zalo/performance', 'icon' => 'fas fa-chart-line'];
            }
            if (has_permission('care.view') || has_permission('care.manage') || has_permission('customer.view')) {
                $subReport[] = ['title' => 'Hi&#7879;u su&#7845;t CSKH', 'url' => 'customer-care/sla-report', 'icon' => 'fas fa-stopwatch'];
                $subReport[] = ['title' => 'Hi&#7879;u su&#7845;t CSKH th&#225;ng', 'url' => 'customer-care/monthly-report', 'icon' => 'fas fa-chart-bar'];
            }
            if (has_permission('kpi.view_all') || has_permission('kpi.view_team')) {
                $subReport[] = ['title' => 'KPI nh&#226;n vi&#234;n', 'url' => 'kpi', 'icon' => 'fas fa-chart-line'];
            }
            if (has_permission('kpi.consulting') || has_permission('kpi.view_all') || has_permission('kpi.view_team')) {
                $subReport[] = ['title' => 'KPI t&#432; v&#7845;n', 'url' => 'kpi/consulting', 'icon' => 'fas fa-headset'];
            }
            if (!empty($subReport)) {
                $menu[] = [
                    'title' => 'B&#225;o c&#225;o/Th&#7889;ng k&#234;',
                    'icon'  => 'fas fa-chart-bar',
                    'submenu' => $subReport
                ];
            }

            if (has_permission('zalo.view') || has_permission('zalo.config') || has_permission('messenger.view') || has_permission('messenger.config') || has_permission('chat.view')) {
                $submenu = [];
                $submenu[] = ['title' => 'Trung t&#226;m Chat', 'url' => 'chat', 'icon' => 'fas fa-comments'];
                if (has_permission('zalo.view') || has_permission('zalo.config') || has_permission('messenger.view')) {
                    $submenu[] = ['title' => 'C&#226;u tr&#7843; l&#7901;i nhanh', 'url' => 'zalo/quick-replies', 'icon' => 'fas fa-bolt'];
                }
                if (has_permission('zalo.campaign')) {
                    $submenu[] = ['title' => 'Remarketing (ZNS)', 'url' => 'zalo/campaigns', 'icon' => 'fas fa-bullhorn'];
                }
                $menu[] = [
                    'title' => 'T&#432; V&#7845;n Kh&#225;ch H&#224;ng',
                    'icon'  => 'fas fa-headset',
                    'submenu' => $submenu
                ];
            }

            if (has_permission('customer.view') || has_permission('customer.view_all')) {
                $menu[] = ['title' => 'Kh&#225;ch h&#224;ng', 'url' => 'customers', 'icon' => 'fas fa-id-card'];
            }

            if (has_permission('care.view') || has_permission('care.manage') || has_permission('customer.view')) {
                $menu[] = [
                    'title' => 'CSKH',
                    'icon'  => 'fas fa-hand-holding-heart',
                    'submenu' => [
                        ['title' => 'Dashboard CSKH', 'url' => 'customer-care', 'icon' => 'fas fa-chart-pie'],
                        ['title' => 'Ph&#226;n nh&#243;m KH', 'url' => 'customer-care/customers', 'icon' => 'fas fa-layer-group'],
                        ['title' => 'Checklist h&#244;m nay', 'url' => 'customer-care/daily-checklist', 'icon' => 'fas fa-tasks'],
                    ]
                ];
            }

            if (has_permission('case.view') || has_permission('case.view_all') || has_permission('case.edit_all') || session()->get('department_id') == \Config\AppConstants::DEPT_PHAP_LY) {
                $menu[] = ['title' => 'V&#7909; vi&#7879;c ph&#225;p l&#253;', 'url' => 'cases', 'icon' => 'fas fa-briefcase'];
            }

            $menu[] = ['title' => 'T&#224;i ch&#237;nh V&#7909; vi&#7879;c', 'url' => 'finance/cases', 'icon' => 'fas fa-file-invoice-dollar'];

            $sub1 = [];
            if (has_permission('leave.view') || session()->get('employee_id')) {
                $sub1[] = ['title' => '&#272;&#417;n ngh&#7881; ph&#233;p', 'url' => 'leave-requests', 'icon' => 'fas fa-calendar-minus'];
            }
            if (has_permission('attendance.view') || session()->get('employee_id')) {
                $sub1[] = ['title' => 'Qu&#7843;n l&#253; ch&#7845;m c&#244;ng', 'url' => 'attendance/list', 'icon' => 'fas fa-clock'];
            }
            if (!empty($sub1)) {
                $menu[] = [
                    'title' => 'Ch&#7845;m c&#244;ng &amp; Ngh&#7881; ph&#233;p',
                    'icon'  => 'fas fa-calendar-check',
                    'submenu' => $sub1
                ];
            }

            if (has_permission('payroll.view') || has_permission('payroll.manage') || session()->get('employee_id')) {
                $menu[] = ['title' => 'B&#7843;ng l&#432;&#417;ng', 'url' => 'payroll', 'icon' => 'fas fa-money-check-alt'];
            }

            $sub3 = [];
            if (session()->get('employee_id')) {
                $sub3[] = ['title' => 'Trao &#273;&#7893;i', 'url' => 'notifications', 'icon' => 'fas fa-comments'];
                $sub3[] = ['title' => 'T&#224;i li&#7879;u', 'url' => 'documents', 'icon' => 'fas fa-folder-open'];
                $sub3[] = ['title' => 'C&#7849;m nang n&#7897;i b&#7897;', 'url' => 'knowledge', 'icon' => 'fas fa-book-open'];
            }
            if (has_permission('contact.view') || session()->get('employee_id')) {
                $sub3[] = ['title' => 'Danh b&#7841; li&#234;n h&#7879;', 'url' => 'contacts', 'icon' => 'fas fa-address-book'];
            }
            if (has_permission('case.view') || has_permission('case.edit_all') || has_permission('customer.view') || session()->get('department_id') == \Config\AppConstants::DEPT_PHAP_LY) {
                $sub3[] = ['title' => 'Danh m&#7909;c nh&#227;n d&#225;n', 'url' => 'tags', 'icon' => 'fas fa-tags'];
            }
            if (!empty($sub3)) {
                $menu[] = [
                    'title' => 'Trao &#273;&#7893;i &amp; T&#224;i li&#7879;u',
                    'icon'  => 'fas fa-share-alt',
                    'submenu' => $sub3
                ];
            }

            if (has_permission('user.view') || has_permission('user.manage')) {
                $menu[] = [
                    'title' => 'Nh&#226;n s&#7921; &amp; T&#224;i kho&#7843;n',
                    'icon'  => 'fas fa-users-cog',
                    'submenu' => [
                        ['title' => 'T&#224;i kho&#7843;n', 'url' => 'users', 'icon' => 'fas fa-users-cog'],
                        ['title' => 'Nh&#226;n s&#7921;', 'url' => 'employees', 'icon' => 'fas fa-user-tie'],
                    ]
                ];
            }

            $sub4 = [];
            if (has_permission('sys.admin') || has_permission('workflow.manage')) {
                $sub4[] = ['title' => 'Quy tr&#236;nh m&#7851;u', 'url' => 'workflows', 'icon' => 'fas fa-project-diagram'];
            }
            if (has_permission('zalo.config')) {
                $sub4[] = ['title' => 'C&#7845;u h&#236;nh Zalo', 'url' => 'zalo/config', 'icon' => 'fas fa-cog'];
            }
            if (has_permission('messenger.config')) {
                $sub4[] = ['title' => 'C&#7845;u h&#236;nh Messenger', 'url' => 'messenger/config', 'icon' => 'fab fa-facebook'];
            }
            if (has_permission('sys.admin')) {
                $sub4[] = ['title' => 'Log h&#7879; th&#7889;ng', 'url' => 'system-logs', 'icon' => 'fas fa-history'];
            }
            if (!empty($sub4)) {
                $menu[] = [
                    'title' => 'Qu&#7843;n tr&#7883; h&#7879; th&#7889;ng',
                    'icon'  => 'fas fa-cogs',
                    'submenu' => $sub4
                ];
            }
        } else {
            $menu = [
                ['title' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'fas fa-th-large'],
            ];

            $subReport = [];
            if (has_permission('kpi.view_team') || has_permission('kpi.view_all')) {
                $subReport[] = ['title' => 'KPI nh&#226;n vi&#234;n', 'url' => 'kpi', 'icon' => 'fas fa-chart-line'];
            }
            if (has_permission('kpi.consulting') || has_permission('kpi.view_team') || has_permission('kpi.view_all')) {
                $subReport[] = ['title' => 'KPI t&#432; v&#7845;n', 'url' => 'kpi/consulting', 'icon' => 'fas fa-headset'];
            }
            if (!empty($subReport)) {
                $menu[] = [
                    'title' => 'B&#225;o c&#225;o/Th&#7889;ng k&#234;',
                    'icon'  => 'fas fa-chart-bar',
                    'submenu' => $subReport
                ];
            }

            if (has_permission('zalo.view') || has_permission('zalo.config') || has_permission('messenger.view') || has_permission('messenger.config') || has_permission('chat.view')) {
                $submenu = [];
                $submenu[] = ['title' => 'Trung t&#226;m Chat', 'url' => 'chat', 'icon' => 'fas fa-comments'];
                if (has_permission('zalo.view') || has_permission('zalo.config') || has_permission('messenger.view')) {
                    $submenu[] = ['title' => 'C&#226;u tr&#7843; l&#7901;i nhanh', 'url' => 'zalo/quick-replies', 'icon' => 'fas fa-bolt'];
                }
                if (has_permission('zalo.campaign')) {
                    $submenu[] = ['title' => 'Remarketing (ZNS)', 'url' => 'zalo/campaigns', 'icon' => 'fas fa-bullhorn'];
                }
                $menu[] = [
                    'title' => 'T&#432; V&#7845;n Kh&#225;ch H&#224;ng',
                    'icon'  => 'fas fa-headset',
                    'submenu' => $submenu
                ];
            }

            if (has_permission('customer.view') || has_permission('customer.view_all')) {
                $menu[] = ['title' => 'Kh&#225;ch h&#224;ng', 'url' => 'customers', 'icon' => 'fas fa-id-card'];
            }

            if (has_permission('case.view') || has_permission('case.view_all') || has_permission('case.edit_all') || session()->get('department_id') == \Config\AppConstants::DEPT_PHAP_LY) {
                $menu[] = ['title' => 'V&#7909; vi&#7879;c ph&#225;p l&#253;', 'url' => 'cases', 'icon' => 'fas fa-briefcase'];
            }

            if (session()->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH) {
                $menu[] = ['title' => 'T&#224;i ch&#237;nh V&#7909; vi&#7879;c', 'url' => 'finance/cases', 'icon' => 'fas fa-file-invoice-dollar'];
            }

            if (has_permission('leave.view') || session()->get('employee_id')) {
                $menu[] = ['title' => '&#272;&#417;n ngh&#7881; ph&#233;p', 'url' => 'leave-requests', 'icon' => 'fas fa-calendar-minus'];
            }
            if (has_permission('attendance.view') || session()->get('employee_id')) {
                $menu[] = ['title' => 'Qu&#7843;n l&#253; ch&#7845;m c&#244;ng', 'url' => 'attendance/list', 'icon' => 'fas fa-clock'];
            }
            if (has_permission('payroll.view') || has_permission('payroll.manage') || session()->get('employee_id')) {
                $menu[] = ['title' => 'B&#7843;ng l&#432;&#417;ng', 'url' => 'payroll', 'icon' => 'fas fa-money-check-alt'];
            }
            if (session()->get('employee_id')) {
                $menu[] = ['title' => 'Trao &#273;&#7893;i', 'url' => 'notifications', 'icon' => 'fas fa-comments'];
                $menu[] = ['title' => 'T&#224;i li&#7879;u', 'url' => 'documents', 'icon' => 'fas fa-folder-open'];
                $menu[] = ['title' => 'C&#7849;m nang n&#7897;i b&#7897;', 'url' => 'knowledge', 'icon' => 'fas fa-book-open'];
            }
            if (has_permission('contact.view') || session()->get('employee_id')) {
                $menu[] = ['title' => 'Danh b&#7841; li&#234;n h&#7879;', 'url' => 'contacts', 'icon' => 'fas fa-address-book'];
            }
            if (has_permission('case.view') || has_permission('case.edit_all') || has_permission('customer.view') || session()->get('department_id') == \Config\AppConstants::DEPT_PHAP_LY) {
                $menu[] = ['title' => 'Danh m&#7909;c nh&#227;n d&#225;n', 'url' => 'tags', 'icon' => 'fas fa-tags'];
            }

            if (has_permission('user.view') || has_permission('user.manage')) {
                $menu[] = [
                    'title' => 'Nh&#226;n s&#7921; &amp; T&#224;i kho&#7843;n',
                    'icon'  => 'fas fa-users-cog',
                    'submenu' => [
                        ['title' => 'T&#224;i kho&#7843;n', 'url' => 'users', 'icon' => 'fas fa-users-cog'],
                        ['title' => 'Nh&#226;n s&#7921;', 'url' => 'employees', 'icon' => 'fas fa-user-tie'],
                    ]
                ];
            }
        }

        $menu = $this->uniqueMenu($menu);
        return $this->enrichMenuWithFeatures($menu);
    }

    /**
     * Bổ sung trạng thái is_new và metadata hướng dẫn cho từng mục menu.
     */
    private function enrichMenuWithFeatures($menu)
    {
        $now = time();
        $duration = ($this->featureConfig->newBadgeDurationDays ?? 14) * 86400;
        $items = $this->featureConfig->items ?? [];

        foreach ($menu as &$item) {
            // Lấy segment đầu tiên của URL để mapping với cấu hình feature.
            $menuUrl = isset($item['url']) ? trim($item['url'], '/') : '';
            $segment = $menuUrl ? explode('/', $menuUrl)[0] : '';

            if ($segment && isset($items[$segment])) {
                $feature = $items[$segment];

                // Kiểm tra feature còn trong thời hạn hiển thị badge "New" không.
                $launchDate = strtotime($feature['launch_date']);
                $item['is_new'] = ($now - $launchDate < $duration) && ($now >= $launchDate);

                // Đính kèm hướng dẫn nếu có cấu hình.
                if (isset($feature['guidance'])) {
                    $item['guidance'] = $feature['guidance'];
                }
            } else {
                $item['is_new'] = false;
            }

            // Xử lý đệ quy các mục submenu.
            if (isset($item['submenu']) && is_array($item['submenu'])) {
                $item['submenu'] = $this->enrichMenuWithFeatures($item['submenu']);

                // Nếu submenu có mục mới, đánh dấu cả menu cha là mới.
                if (!$item['is_new']) {
                    foreach ($item['submenu'] as $sub) {
                        if (isset($sub['is_new']) && $sub['is_new']) {
                            $item['is_new'] = true;
                            break;
                        }
                    }
                }
            }
        }

        return $menu;
    }


    /**
     * Loại bỏ các mục menu trùng tiêu đề để sidebar không lặp mục.
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
