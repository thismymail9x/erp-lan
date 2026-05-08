<?php

namespace App\Controllers;

use App\Services\KpiService;

class KpiController extends BaseController
{
    /**
     * Khai báo metadata cho hệ thống Tự động Đồng bộ (Auto-Sync Permissions).
     */
    public static $modulePermissions = [
        'group' => 'Hiệu suất (KPI)',
        'permissions' => [
            'kpi.view_all' => 'Xem báo cáo KPI toàn bộ hệ thống (Admin)',
            'kpi.view_team' => 'Xem báo cáo KPI của đội ngũ quản lý (Manager)'
        ]
    ];

    protected $kpiService;

    public function __construct()
    {
        $this->kpiService = new KpiService();
    }

    /**
     * Dashboard KPI dành cho Admin & Quản lý
     */
    public function index()
    {
        // 1. Kiểm tra phân quyền chi tiết (RBAC)
        $canViewAll = has_permission('kpi.view_all');
        $canViewTeam = has_permission('kpi.view_team');

        if (!$canViewAll && !$canViewTeam) {
            return redirect()->to('/dashboard')->with('error', 'Bạn không được cấp quyền xem báo cáo KPI.');
        }

        // 2. Lọc thông tin từ Request
        $filters = [
            'search'        => $this->request->getGet('search'),
            'department_id' => $this->request->getGet('department_id'),
            'year'          => $this->request->getGet('year') ?? date('Y'),
        ];

        // 3. Phân vùng dữ liệu (Data Isolation)
        if (!$canViewAll && $canViewTeam) {
            $filters['manager_id'] = session()->get('employee_id');
        }

        // 4. Lấy dữ liệu thống kê
        $stats = $this->kpiService->getAllEmployeesStats($filters);
        
        // 5. Chuẩn bị dữ liệu hiển thị (Dùng chung)
        $data = [
            'title'       => 'Giám sát Hiệu suất (KPI) | L.A.N ERP',
            'stats'       => $stats,
            'departments' => get_departments(), // Lấy từ common.php
            'filters'     => $filters,
        ];

        // Nếu là yêu cầu AJAX -> Chỉ trả về phần lõi của bảng dữ liệu (Table Rows)
        if ($this->request->isAJAX()) {
            return view('dashboard/kpi/table_partial', $data);
        }

        return view('dashboard/kpi/index', $data);
    }
}
