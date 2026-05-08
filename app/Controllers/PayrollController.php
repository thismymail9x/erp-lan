<?php

namespace App\Controllers;

use App\Services\PayrollService;
use App\Models\EmployeeModel;
use App\Models\PayrollModel;
use App\Models\PayrollConfigModel;
use Config\AppConstants;
use CodeIgniter\I18n\Time;

/**
 * PayrollController
 * 
 * Quản lý tính lương, thưởng và chốt công tháng cho nhân sự.
 */
class PayrollController extends BaseController
{
    /**
     * Khai báo metadata cho hệ thống Tự động Đồng bộ (Auto-Sync Permissions).
     */
    public static $modulePermissions = [
        'group' => 'Tài chính & Nhân sự',
        'permissions' => [
            'payroll.view'   => 'Xem bảng lương cá nhân',
            'payroll.manage' => 'Quản trị bảng lương: Cấu hình ngày công, tính lương và chốt sổ'
        ]
    ];

    protected $payrollService;
    protected $payrollModel;
    protected $configModel;
    protected $employeeModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        
        $this->payrollService = new PayrollService();
        $this->payrollModel   = new PayrollModel();
        $this->configModel    = new PayrollConfigModel();
        $this->employeeModel  = new EmployeeModel();
    }

    /**
     * Trang chủ Bảng lương.
     * Admin/Hành chính thấy toàn bộ, Nhân viên thấy cá nhân.
     */
    public function index()
    {
        $role = session()->get('role_name');
        $month = $this->request->getGet('month') ?: date('Y-m');
        
        // Đảm bảo có config cho tháng này
        $config = $this->payrollService->getOrCreateConfig($month);

        if (has_permission('payroll.manage') || $role === AppConstants::ROLE_ADMIN || session()->get('department_id') == AppConstants::DEPT_HANH_CHINH) {
            // VIEW QUẢN TRỊ
            $this->payrollModel->select('payrolls.*, employees.full_name, departments.name as dept_name')
                ->join('employees', 'employees.id = payrolls.employee_id')
                ->join('departments', 'departments.id = employees.department_id', 'left')
                ->where('payrolls.month', $month);

            $data = [
                'title'    => 'Quản lý Bảng lương - Tháng ' . $month,
                'month'    => $month,
                'config'   => $config,
                'payrolls' => $this->payrollModel->paginate(15),
                'pager'    => $this->payrollModel->pager,
                'isAdmin'  => true
            ];
            return view('dashboard/payroll/admin_index', $data);
        } else {
            // VIEW CÁ NHÂN
            $payroll = $this->payrollModel->where([
                'employee_id' => session()->get('employee_id'),
                'month'       => $month
            ])->first();

            $data = [
                'title'   => 'Bảng lương cá nhân - Tháng ' . $month,
                'month'   => $month,
                'payroll' => $payroll,
                'config'  => $config,
                'isAdmin' => false
            ];
            return view('dashboard/payroll/personal_view', $data);
        }
    }

    /**
     * Cấu hình ngày công cho tháng.
     */
    public function config($month)
    {
        if (!has_permission('payroll.manage')) return redirect()->to('/payroll');

        $config = $this->configModel->where('month', $month)->first();
        if (!$config) return redirect()->to('/payroll');

        if (strtolower($this->request->getMethod()) === 'post') {
//            die("HỆ THỐNG ĐÃ NHẬN DỮ LIỆU: " . json_encode($_POST));
            
            $workingDays = $this->request->getPost('working_days') ?: [];
            $dayNotes    = $this->request->getPost('day_notes') ?: [];
            
            // Lọc ra các note chỉ dành cho những ngày có note thực sự
            $holidays = array_filter($dayNotes, function($v) { return !empty(trim($v)); });
            
            $updateData = [
                'working_days_json'   => json_encode($workingDays),
                'holidays_json'       => json_encode($holidays, JSON_UNESCAPED_UNICODE),
                'total_standard_days' => count($workingDays)
            ];

            $ok = $this->configModel->update($config['id'], $updateData);
            
            // LOG DEBUG
            $logMsg = date('Y-m-d H:i:s') . " | Save Config month $month | ID: " . $config['id'] . " | Status: " . ($ok ? 'OK' : 'FAIL') . " | Data: " . json_encode($updateData) . "\n";
            file_put_contents(WRITEPATH . 'logs/payroll_debug.log', $logMsg, FILE_APPEND);

            if (!$ok) {
                return redirect()->back()->with('error', 'Lỗi lưu cơ sở dữ liệu: ' . json_encode($this->configModel->errors()));
            }

            return redirect()->to("/payroll?month=$month")->with('success', 'Đã cập nhật cấu hình ngày công.');
        }

        $data = [
            'title'  => 'Cấu hình ngày công tháng ' . $month,
            'config' => $config,
            'month'  => $month,
            'daysInMonth' => date('t', strtotime($month . '-01')),
            'currentWorkingDays' => json_decode($config['working_days_json'], true),
            'currentHolidays'    => json_decode($config['holidays_json'] ?: '{}', true)
        ];

        return view('dashboard/payroll/config', $data);
    }

    /**
     * Kích hoạt tính toán lương cho tháng.
     */
    public function calculate($month)
    {
        if (!has_permission('payroll.manage')) return redirect()->to('/payroll');
        
        $result = $this->payrollService->calculateMonthlyPayroll($month);
        
        if ($result['status'] === 'success') {
            return redirect()->to("/payroll?month=$month")->with('success', $result['message']);
        } else {
            return redirect()->to("/payroll?month=$month")->with('error', $result['message']);
        }
    }

    /**
     * Chốt sổ (Khóa dữ liệu tháng).
     */
    public function close($month)
    {
        if (!has_permission('payroll.manage')) return redirect()->to('/payroll');

        $this->configModel->where('month', $month)->set(['is_closed' => 1])->update();
        $this->payrollModel->where('month', $month)->set(['status' => 'approved'])->update();

        return redirect()->to("/payroll?month=$month")->with('success', "Đã chốt sổ bảng lương tháng $month.");
    }

    /**
     * Xuất file bảng lương (CSV).
     */
    public function export($month)
    {
        if (!has_permission('payroll.manage')) return redirect()->to('/payroll');

        $payrolls = $this->payrollModel->select('payrolls.*, employees.full_name, departments.name as dept_name')
            ->join('employees', 'employees.id = payrolls.employee_id')
            ->join('departments', 'departments.id = employees.department_id', 'left')
            ->where('payrolls.month', $month)
            ->findAll();

        $filename = "BangLuong_" . $month . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, [
            'Nhân viên', 'Phòng ban', 'Lương cơ bản', 'Lương KPI', 'Phụ cấp', 
            'Thưởng thêm', 'Khấu trừ', 'Phát sinh', 'Công chuẩn', 'Công thực', 'Vi phạm', 'Thực lĩnh', 'Ghi chú'
        ]);
        
        foreach ($payrolls as $p) {
            $notes = json_decode($p['notes_json'] ?? '[]', true);
            $notesText = '';
            if (is_array($notes)) {
                $notesTexts = array_map(function($n) { return $n['text'] ?? ''; }, $notes);
                $notesText = implode('; ', array_filter($notesTexts));
            }

            fputcsv($output, [
                $p['full_name'],
                $p['dept_name'],
                number_format($p['salary_base']),
                number_format($p['salary_kpi']),
                number_format($p['salary_allowance']),
                number_format($p['salary_bonus']),
                number_format($p['salary_deduction']),
                number_format($p['salary_other'] ?? 0),
                $p['total_standard_days'],
                $p['actual_working_days'],
                $p['attendance_violations'],
                number_format($p['net_salary']),
                $notesText
            ]);
        }
        fclose($output);
        exit();
    }

    /**
     * Cập nhật nhanh Thưởng/Ghi chú cho 1 dòng lương.
     */
    public function updateItem($id)
    {
        if (!has_permission('payroll.manage')) return $this->response->setJSON(['code' => 1, 'error' => 'Permission denied']);

        $payroll = $this->payrollModel->find($id);
        if (!$payroll) return $this->response->setJSON(['code' => 1, 'error' => 'Not found']);

        $bonus = (float)$this->request->getPost('salary_bonus');
        $kpi   = (float)$this->request->getPost('salary_kpi');
        $deduction = (float)$this->request->getPost('salary_deduction');
        $other = (float)$this->request->getPost('salary_other');
        $notes = $this->request->getPost('notes');

        $data = [
            'salary_bonus' => $bonus,
            'salary_kpi'   => $kpi,
            'salary_deduction' => $deduction,
            'salary_other' => $other,
            'notes'        => $notes
        ];

        // Recalculate net_salary: (Base / Std) * Actual + Bonus (includes KPI) + Allowance - Deduction (Penalty) + Other
        $salaryByWork = ($payroll['total_standard_days'] > 0) ? ($payroll['salary_base'] / $payroll['total_standard_days']) * $payroll['actual_working_days'] : 0;
        $netSalary = $salaryByWork + $bonus + $kpi + $payroll['salary_allowance'] - $deduction + $other;
        
        $data['net_salary'] = $netSalary;

        $this->payrollModel->update($id, $data);

        return $this->response->setJSON(['code' => 0, 'message' => 'Updated', 'net_salary' => number_format($netSalary)]);
    }

    /**
     * Lấy danh sách ghi chú JSON
     */
    public function getNotes($id)
    {
        $payroll = $this->payrollModel->find($id);
        if (!$payroll) return $this->response->setJSON(['code' => 1, 'msg' => 'Không tìm thấy']);
        
        $notes = json_decode($payroll['notes_json'] ?? '[]', true);
        return $this->response->setJSON(['code' => 0, 'data' => $notes]);
    }

    /**
     * Lưu lại toàn bộ danh sách ghi chú JSON
     */
    public function saveNotes($id)
    {
        $payroll = $this->payrollModel->find($id);
        if (!$payroll) return $this->response->setJSON(['code' => 1, 'msg' => 'Không tìm thấy']);

        if (!has_permission('payroll.manage') && $payroll['employee_id'] != session()->get('employee_id')) {
            return $this->response->setJSON(['code' => 1, 'msg' => 'Không có quyền truy cập bảng lương này']);
        }

        $notesJson = $this->request->getPost('notes_json');
        
        // Validate JSON
        json_decode($notesJson);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $notesJson = '[]';
        }

        $this->payrollModel->update($id, ['notes_json' => $notesJson]);

        return $this->response->setJSON(['code' => 0, 'msg' => 'Đã lưu ghi chú']);
    }
}
