<?php

namespace App\Controllers;

/**
 * DashboardController
 */
class DashboardController extends BaseController
{
    /**
     * Hiển thị trang chủ Dashboard.
     */
    public function index()
    {
        // 1. KIỂM TRA PHIÊN (Session Check)
        if (!session()->has('isLoggedIn')) {
            return redirect()->to('/login');
        }
        $employeeId = session()->get('employee_id');
        $role = session()->get('role_name');
        $myDeptId = session()->get('department_id');
        $isAdmin = (has_permission('sys.admin') || in_array($role, [\Config\AppConstants::ROLE_ADMIN, \Config\AppConstants::ROLE_MOD]));
        $isManager = ($role === \Config\AppConstants::ROLE_TRUONG_PHONG);
        $isLegalManager = ($isManager && $myDeptId == \Config\AppConstants::DEPT_PHAP_LY);

        // 2. KHỞI TẠO DỊCH VỤ & MODEL
        $attendanceService = new \App\Services\AttendanceService();
        $kpiService = new \App\Services\KpiService();
        $db = \Config\Database::connect();
        
        $kpiYear = $this->request->getGet('year') ?? date('Y');
        $kpiMonth = $this->request->getGet('month') ?? date('Y-m');
        $canViewConsultingKpi = (has_permission('kpi.consulting') || has_permission('kpi.view_all') || has_permission('kpi.view_team') || $isAdmin);
        // KPI vụ việc cũ vẫn tính theo bước công việc.
        $kpiStats = $kpiService->getMotivationStats($isAdmin ? null : $employeeId, ['year' => $kpiYear]);
        // KPI tư vấn là chỉ số bổ sung theo tháng chốt hợp đồng.
        $consultingKpiEmployeeId = $employeeId;
        $consultingKpiFilters = ['month' => $kpiMonth];
        if ($isAdmin || has_permission('kpi.view_all')) {
            $consultingKpiEmployeeId = null;
        } elseif (has_permission('kpi.view_team')) {
            $consultingKpiEmployeeId = null;
            $consultingKpiFilters['manager_id'] = $employeeId;
        }
        $consultingKpiStats = $canViewConsultingKpi
            ? $kpiService->getConsultingMotivationStats($consultingKpiEmployeeId, $consultingKpiFilters)
            : null;
        $stats = [];
        // --- A. Vụ việc & Khách hàng (Sử dụng Service chuyên trách) ---
        $caseService     = new \App\Services\CaseService();
        $customerService = new \App\Services\CustomerService();

        // Tính toán params cho Customer Service dựa trên vai trò
        $custEmpId = $custDeptId = $custMgrId = null;
        if (!$isAdmin) {
            $custDeptId = $myDeptId;
            if ($isManager) {
                $custMgrId = $employeeId;
            } else {
                $custEmpId = $employeeId;
            }
        }

        $caseStats = $caseService->getStats();
        $customerStats = $customerService->getDashboardStats($custEmpId, $custDeptId, $custMgrId);

        $stats = [
            'total_cases'      => $caseStats['total'],
            'waiting_cases'    => $caseStats['waiting'],
            'processing_cases' => $caseStats['processing'],
            'paused_cases'     => $caseStats['paused'] ?? 0,
            'completed_cases'  => $caseStats['completed'],
            'overdue_cases'    => $caseStats['overdue'],
            'customers'        => $customerStats['total_customers'],
            'revenue'          => 0
        ];

        // --- C. Doanh thu ---
        $stats['revenue'] = 0;

        // --- D. Tỉ lệ chấm công ---
        $stats['attendance_rate'] = 0;
        if ($isAdmin) {
            $totalEmployees = $db->table('employees')->where('deleted_at', null)->countAllResults();
            if ($totalEmployees > 0) {
                $todayCheckedIn = $db->table('attendances')->where('attendance_date', date('Y-m-d'))->countAllResults();
                $stats['attendance_rate'] = round(($todayCheckedIn / $totalEmployees) * 100);
            }
        } elseif ($isManager) {
            // Trưởng phòng: Tỉ lệ chấm công của phòng trong ngày hôm nay
            $deptEmpIds = $db->table('employees')->where('department_id', $myDeptId)->select('id')->get()->getResultArray();
            $deptEmpIds = array_column($deptEmpIds, 'id');
            $totalInDept = count($deptEmpIds);
            
            if ($totalInDept > 0) {
                $todayDeptCheckedIn = $db->table('attendances')
                    ->where('attendance_date', date('Y-m-d'))
                    ->whereIn('employee_id', $deptEmpIds)
                    ->countAllResults();
                $stats['attendance_rate'] = round(($todayDeptCheckedIn / $totalInDept) * 100);
            }
        } else {
            // Nhân viên: Tỉ lệ chấm công cá nhân trong tháng
            $daysElapsed = (int)date('d');
            $myCheckins = $db->table('attendances')
                ->where('employee_id', $employeeId)
                ->where('attendance_date >=', date('Y-m-01'))
                ->countAllResults();
            $stats['attendance_rate'] = $daysElapsed > 0 ? round(($myCheckins / $daysElapsed) * 100) : 0;
        }

        // 4. TRẠNG THÁI CHẤM CÔNG CÁ NHÂN
        $attendanceStatus = null;
        if ($employeeId) {
            $attendanceStatus = $attendanceService->getTodayStatus($employeeId);
        }

        // --- D. Thống kê theo bộ phận (Departmental Customization) ---
        $deptStats = null;
        $isHRDept = ($myDeptId == \Config\AppConstants::DEPT_HANH_CHINH);
        $isSaleDept = ($myDeptId == \Config\AppConstants::DEPT_SALE);

        if ($isManager) {
            if ($isLegalManager) {
                // Đã xử lý ở mục A & B
            } elseif ($isHRDept) {
                // BỘ PHẬN HÀNH CHÍNH (HR/ADMIN): Thống kê nhân sự toàn công ty
                $deptStats = [
                    'total_company_employees' => $db->table('employees')->where('deleted_at', null)->countAllResults(),
                    'new_hires_this_month'   => $db->table('employees')
                                                    ->where('MONTH(join_date)', date('m'))
                                                    ->where('YEAR(join_date)', date('Y'))
                                                    ->where('deleted_at', null)
                                                    ->countAllResults(),
                    'company_attendance_today' => $db->table('attendances')
                                                    ->where('attendance_date', date('Y-m-d'))
                                                    ->countAllResults()
                ];
                $totalEmp = $deptStats['total_company_employees'];
                $deptStats['attendance_percent'] = $totalEmp > 0 ? round(($deptStats['company_attendance_today'] / $totalEmp) * 100) : 0;
            } else {
                // CÁC BỘ PHẬN KHÁC (Sale, Marketing...): Thống kê nhân sự và chấm công TEAM
                $deptStats = [
                    'total_members' => $db->table('employees')->where('department_id', $myDeptId)->where('deleted_at', null)->countAllResults(),
                    'today_attendance' => $db->table('attendances')
                        ->whereIn('employee_id', function($builder) use ($myDeptId) {
                            $builder->select('id')->from('employees')->where('department_id', $myDeptId);
                        })
                        ->where('attendance_date', date('Y-m-d'))
                        ->countAllResults(),
                ];
                
                if ($deptStats['total_members'] > 0) {
                    $deptStats['attendance_percent'] = round(($deptStats['today_attendance'] / $deptStats['total_members']) * 100);
                } else {
                    $deptStats['attendance_percent'] = 0;
                }
            }
        }

        $data = [
            'title'            => 'Bảng điều khiển | L.A.N ERP',
            'attendanceStatus' => $attendanceStatus,
            'stats'            => $stats,
            'kpiStats'         => $kpiStats,
            'consultingKpiStats' => $consultingKpiStats,
            'deptStats'        => $deptStats,
            'isAdmin'          => $isAdmin,
            'isManager'        => $isManager,
            'isLegalDept'      => ($myDeptId == \Config\AppConstants::DEPT_PHAP_LY),
            'isHRDept'         => $isHRDept,
            'isSaleDept'       => $isSaleDept,
            'currentMonthDisplay' => date('m/Y'),
            'kpiYear'          => $kpiYear,
            'kpiMonth'         => $kpiMonth,
            'canViewConsultingKpi' => $canViewConsultingKpi,
            'departments'      => $db->table('departments')->get()->getResultArray(),
            'employees'        => get_available_employees(),
            'current_employee_id' => $employeeId,
            'user'  => [
                'email' => session()->get('email'),
                'role'  => $role
            ]
        ];

        return view('dashboard/index', $data);
    }
}
