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
        $isPrivileged = in_array($role, \Config\AppConstants::PRIVILEGED_ROLES);

        // 2. KHỞI TẠO DỊCH VỤ & MODEL
        $attendanceService = new \App\Services\AttendanceService();
        $db = \Config\Database::connect();
        
        // 3. TÍNH TOÁN CÁC CHỈ SỐ (Statistics)
        $stats = [];
        
        // --- A. Vụ việc đang xử lý ---
        $caseBuilder = $db->table('cases');
        $caseBuilder->whereIn('status', ['moi_tiep_nhan', 'dang_xu_ly', 'cho_tham_tam', 'open', 'in_progress']);
        $caseBuilder->where('deleted_at', null);
        
        if (!$isPrivileged) {
            $caseBuilder->groupStart()
                ->where('assigned_lawyer_id', $employeeId)
                ->orWhere('assigned_staff_id', $employeeId)
                ->orWhereIn('id', function($builder) use ($employeeId) {
                    $builder->select('case_id')->from('case_members')->where('employee_id', $employeeId);
                })
            ->groupEnd();
        }
        $stats['cases'] = $caseBuilder->countAllResults();

        // --- B. Tổng Khách hàng ---
        $customerBuilder = $db->table('customers');
        $customerBuilder->where('deleted_at', null);
        
        if (!$isPrivileged) {
            $customerBuilder->whereIn('id', function($builder) use ($employeeId) {
                $builder->select('customer_id')->from('cases')
                    ->groupStart()
                        ->where('assigned_lawyer_id', $employeeId)
                        ->orWhere('assigned_staff_id', $employeeId)
                        ->orWhereIn('id', function($sub) use ($employeeId) {
                            $sub->select('case_id')->from('case_members')->where('employee_id', $employeeId);
                        })
                    ->groupEnd();
            });
        }
        $stats['customers'] = $customerBuilder->countAllResults();

        // --- C. Doanh thu ---
        $stats['revenue'] = 0;

        // --- D. Tỉ lệ chấm công ---
        $stats['attendance_rate'] = 0;
        if ($isPrivileged) {
            $totalEmployees = $db->table('employees')->where('deleted_at', null)->countAllResults();
            if ($totalEmployees > 0) {
                $todayCheckedIn = $db->table('attendances')
                    ->where('attendance_date', date('Y-m-d'))
                    ->countAllResults();
                $stats['attendance_rate'] = round(($todayCheckedIn / $totalEmployees) * 100);
            }
        } else {
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

        // 5. ĐÓNG GÓI DỮ LIỆU
        $data = [
            'title'            => 'Bảng điều khiển | L.A.N ERP',
            'attendanceStatus' => $attendanceStatus,
            'stats'            => $stats,
            'isPrivileged'     => $isPrivileged,
            'user'  => [
                'email' => session()->get('email'),
                'role'  => $role
            ]
        ];

        return view('dashboard/index', $data);
    }
}
