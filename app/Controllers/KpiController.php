<?php

namespace App\Controllers;

use App\Services\KpiService;

/**
 * KpiController
 *
 * Điều phối 2 báo cáo KPI độc lập:
 * - KPI vụ việc: tính theo case_steps.kpi_reward như module cũ.
 * - KPI tư vấn: tính theo giá trị hợp đồng đã chốt trong tháng.
 */
class KpiController extends BaseController
{
    /**
     * Khai báo metadata cho hệ thống tự động đồng bộ phân quyền.
     */
    public static $modulePermissions = [
        'group' => 'Hiệu suất (KPI)',
        'permissions' => [
            'kpi.view_all'   => 'Xem báo cáo KPI toàn bộ hệ thống',
            'kpi.view_team'  => 'Xem báo cáo KPI của đội ngũ quản lý',
            'kpi.consulting' => 'Xem và ghi nhận KPI tư vấn chốt khách',
        ],
    ];

    protected $kpiService;

    public function __construct()
    {
        $this->kpiService = new KpiService();
    }

    /**
     * Báo cáo KPI vụ việc theo các bước công việc.
     */
    public function index()
    {
        $canViewAll = has_permission('kpi.view_all');
        $canViewTeam = has_permission('kpi.view_team');

        if (!$canViewAll && !$canViewTeam) {
            return redirect()->to('/dashboard')->with('error', 'Bạn không được cấp quyền xem báo cáo KPI.');
        }

        $filters = [
            'search'        => $this->request->getGet('search'),
            'department_id' => $this->request->getGet('department_id'),
            'year'          => $this->request->getGet('year') ?? date('Y'),
        ];

        if (!$canViewAll && $canViewTeam) {
            $filters['manager_id'] = session()->get('employee_id');
        }

        $stats = $this->kpiService->getAllEmployeesStats($filters);

        $data = [
            'title'       => 'Giám sát Hiệu suất (KPI) | L.A.N ERP',
            'stats'       => $stats,
            'departments' => get_departments(),
            'filters'     => $filters,
        ];

        if ($this->request->isAJAX()) {
            return view('dashboard/kpi/table_partial', $data);
        }

        return view('dashboard/kpi/index', $data);
    }

    /**
     * Báo cáo KPI tư vấn theo giá trị hợp đồng đã chốt.
     */
    public function consulting()
    {
        $canViewAll = has_permission('kpi.view_all');
        $canViewTeam = has_permission('kpi.view_team');
        $canViewConsulting = has_permission('kpi.consulting');

        if (!$canViewAll && !$canViewTeam && !$canViewConsulting) {
            return redirect()->to('/dashboard')->with('error', 'Bạn không được cấp quyền xem báo cáo KPI tư vấn.');
        }

        $filters = [
            'search'        => $this->request->getGet('search'),
            'department_id' => $this->request->getGet('department_id'),
            'month'         => $this->request->getGet('month') ?: date('Y-m'),
        ];

        if (!$canViewAll && $canViewTeam) {
            $filters['manager_id'] = session()->get('employee_id');
        } elseif (!$canViewAll) {
            $filters['employee_id'] = session()->get('employee_id');
        }

        $stats = $this->kpiService->getConsultingAllEmployeesStats($filters);

        $data = [
            'title'        => 'Giám sát KPI tư vấn | L.A.N ERP',
            'stats'        => $stats,
            'departments'  => get_departments(),
            'filters'      => $filters,
            'targetValue'  => KpiService::CONSULTING_MONTHLY_TARGET_VALUE,
            'targetReward' => KpiService::CONSULTING_TARGET_REWARD,
        ];

        if ($this->request->isAJAX()) {
            return view('dashboard/kpi/consulting_table_partial', $data);
        }

        return view('dashboard/kpi/consulting_index', $data);
    }
}
