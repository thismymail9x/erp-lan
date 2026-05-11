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
     * Xuất file bảng lương (Excel-HTML format).
     */
    public function export($month)
    {
        if (!has_permission('payroll.manage')) return redirect()->to('/payroll');

        $payrolls = $this->payrollModel->select('payrolls.*, employees.full_name, departments.name as dept_name')
            ->join('employees', 'employees.id = payrolls.employee_id')
            ->join('departments', 'departments.id = employees.department_id', 'left')
            ->where('payrolls.month', $month)
            ->findAll();

        $filename = "BangLuong_" . $month . ".xls";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Cache-Control: max-age=0');

        // Khởi tạo HTML cho Excel
        echo '<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">';
        echo '<style>
                table { border-collapse: collapse; width: 100%; font-family: "Times New Roman", serif; font-size: 11pt; }
                th, td { border: 1px solid black; padding: 5px; text-align: center; }
                .text-right { text-align: right; }
                .text-left { text-align: left; }
                .header-title { font-size: 16pt; font-weight: bold; border: none; }
                .no-border { border: none; }
                .bold { font-weight: bold; }
                .footer-sign { border: none; padding-top: 30px; }
              </style>';

        echo '<table>';
        // Tiêu đề
        echo '<tr><td colspan="21" class="header-title no-border">BẢNG THANH TOÁN TIỀN LƯƠNG THÁNG ' . date('m/Y', strtotime($month . '-01')) . '</td></tr>';
        echo '<tr><td colspan="21" class="no-border"></td></tr>';

        // Header
        echo '<thead>
                <tr style="background-color: #f2f2f2;">
                    <th rowspan="2">STT</th>
                    <th rowspan="2">Họ và tên</th>
                    <th rowspan="2">Chức vụ</th>
                    <th rowspan="2">Lương đóng BH</th>
                    <th rowspan="2">Lương tháng</th>
                    <th rowspan="2">Ngày công chuẩn</th>
                    <th rowspan="2">Lương 1 ngày công</th>
                    <th colspan="2">Lương theo ngày công làm việc</th>
                    <th rowspan="2">Phụ cấp CC</th>
                    <th rowspan="2">Phụ cấp Xăng</th>
                    <th rowspan="2">Lương trách nhiệm</th>
                    <th rowspan="2">Khác</th>
                    <th rowspan="2">Tổng lương</th>
                    <th colspan="5">Các khoản giảm trừ</th>
                    <th rowspan="2">Lương thực lĩnh</th>
                    <th rowspan="2">Ghi chú</th>
                </tr>
                <tr style="background-color: #f2f2f2;">
                    <th>Số công</th>
                    <th>Số tiền (TNCT)</th>
                    <th>BHXH vào CP (21.5%)</th>
                    <th>BHXH trừ lương (10.5%)</th>
                    <th>Giảm trừ PT</th>
                    <th>Thuế TNCN</th>
                    <th>Tổng cộng</th>
                </tr>
              </thead>';

        echo '<tbody>';
        $stt = 1;
        $totalNet = 0;
        $totalGross = 0;
        $totalDeduction = 0;

        foreach ($payrolls as $p) {
            $notes = json_decode($p['notes_json'] ?? '[]', true);
            $notesText = '';
            if (is_array($notes)) {
                $notesTexts = array_map(function($n) { return $n['text'] ?? ''; }, $notes);
                $notesText = implode('; ', array_filter($notesTexts));
            }

            $gross = $p['taxable_income'] + $p['diligence_allowance'] + $p['petrol_allowance'] + $p['salary_kpi'] + ($p['salary_bonus'] ?? 0);
            
            echo '<tr>';
            echo '<td>' . $stt++ . '</td>';
            echo '<td class="text-left">' . esc($p['full_name']) . '</td>';
            echo '<td>' . esc($p['position'] ?? 'Nhân viên') . '</td>';
            echo '<td class="text-right">' . number_format($p['insurance_salary']) . '</td>';
            echo '<td class="text-right">' . number_format($p['salary_base']) . '</td>';
            echo '<td>' . $p['total_standard_days'] . '</td>';
            echo '<td class="text-right">' . number_format($p['salary_per_day']) . '</td>';
            echo '<td>' . $p['actual_working_days'] . '</td>';
            echo '<td class="text-right">' . number_format($p['taxable_income']) . '</td>';
            echo '<td class="text-right">' . number_format($p['diligence_allowance']) . '</td>';
            echo '<td class="text-right">' . number_format($p['petrol_allowance']) . '</td>';
            echo '<td class="text-right">' . number_format($p['salary_kpi']) . '</td>';
            echo '<td class="text-right">' . number_format($p['salary_bonus'] ?? 0) . '</td>';
            echo '<td class="text-right bold">' . number_format($gross) . '</td>';
            echo '<td class="text-right">' . number_format($p['si_employer']) . '</td>';
            echo '<td class="text-right">' . number_format($p['si_employee']) . '</td>';
            echo '<td class="text-right">' . number_format($p['dependent_deduction']) . '</td>';
            echo '<td class="text-right">' . number_format($p['pit_tax']) . '</td>';
            echo '<td class="text-right bold">' . number_format($p['total_deductions']) . '</td>';
            echo '<td class="text-right bold">' . number_format($p['net_salary']) . '</td>';
            echo '<td class="text-left">' . esc($notesText) . '</td>';
            echo '</tr>';

            $totalNet += $p['net_salary'];
            $totalGross += $gross;
            $totalDeduction += $p['total_deductions'];
        }

        // Dòng tổng kết
        echo '<tr class="bold" style="background-color: #f9f9f9;">';
        echo '<td colspan="13" class="text-right">TỔNG CỘNG</td>';
        echo '<td class="text-right">' . number_format($totalGross) . '</td>';
        echo '<td colspan="4"></td>';
        echo '<td class="text-right">' . number_format($totalDeduction) . '</td>';
        echo '<td class="text-right">' . number_format($totalNet) . '</td>';
        echo '<td></td>';
        echo '</tr>';

        echo '</tbody>';
        echo '</table>';

        // Phần chữ ký
        echo '<br><br>';
        echo '<table>';
        echo '<tr class="no-border">';
        echo '<td colspan="4" class="no-border footer-sign bold">Người lập bảng</td>';
        echo '<td colspan="4" class="no-border footer-sign bold">Kế toán trưởng</td>';
        echo '<td colspan="7" class="no-border footer-sign bold">Giám đốc duyệt</td>';
        echo '</tr>';
        echo '<tr class="no-border">';
        echo '<td colspan="4" class="no-border">(Ký, họ tên)</td>';
        echo '<td colspan="4" class="no-border">(Ký, họ tên)</td>';
        echo '<td colspan="7" class="no-border">(Ký, họ tên, đóng dấu)</td>';
        echo '</tr>';
        echo '</table>';

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
        $pitTax = (float)$this->request->getPost('pit_tax');
        $petrol = $this->request->getPost('petrol_allowance');
        $diligence = $this->request->getPost('diligence_allowance');

        $data = [
            'salary_bonus' => $bonus,
            'salary_kpi'   => $kpi,
            'salary_deduction' => $deduction,
            'pit_tax'      => $pitTax
        ];

        if ($petrol !== null) $data['petrol_allowance'] = (float)$petrol;
        if ($diligence !== null) $data['diligence_allowance'] = (float)$diligence;

        // Fetch again to have full data for recalculation if some fields were not sent
        $currentData = array_merge($payroll, $data);

        // Recalculate net_salary
        $totalSalary = $currentData['taxable_income'] + $currentData['diligence_allowance'] + $currentData['petrol_allowance'] + $currentData['salary_kpi'] + $currentData['salary_bonus'];
        $totalDeductions = $currentData['si_employee'] + $currentData['dependent_deduction'] + $currentData['pit_tax'] + $currentData['salary_deduction'];
        $netSalary = $totalSalary - $totalDeductions;
        
        $data['total_deductions'] = $totalDeductions;
        $data['net_salary'] = $netSalary;

        $this->payrollModel->update($id, $data);
        
        return $this->response->setJSON([
            'code' => 0, 
            'net_salary' => number_format($netSalary),
            'total_deductions' => number_format($totalDeductions),
            'total_gross' => number_format($totalSalary)
        ]);
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
        $petrol = $this->request->getPost('petrol_allowance');
        
        $data = [
            'notes_json' => $notesJson
        ];

        if ($petrol !== null) {
            $data['petrol_allowance'] = (float)$petrol;
        }

        $this->payrollModel->update($id, $data);

        // Recalculate net_salary if petrol changed
        if ($petrol !== null) {
            $payroll = $this->payrollModel->find($id);
            $totalSalary = $payroll['taxable_income'] + $payroll['diligence_allowance'] + $payroll['petrol_allowance'] + $payroll['salary_kpi'];
            $totalDeductions = $payroll['si_employee'] + $payroll['dependent_deduction'] + $payroll['pit_tax'] + $payroll['salary_deduction'];
            $netSalary = $totalSalary + $payroll['salary_bonus'] + $payroll['salary_other'] - $totalDeductions;
            
            $this->payrollModel->update($id, ['net_salary' => $netSalary]);
        }

        return $this->response->setJSON([
            'code' => 0, 
            'msg' => 'Đã lưu ghi chú và cập nhật phụ cấp',
            'net_salary' => isset($netSalary) ? number_format($netSalary) : null,
            'total_deductions' => isset($totalDeductions) ? number_format($totalDeductions) : null
        ]);
    }
}
