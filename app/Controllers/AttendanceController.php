<?php

namespace App\Controllers;

use App\Services\AttendanceService;
use App\Models\EmployeeModel;
use App\Models\DepartmentModel;
use Config\AppConstants;
use CodeIgniter\I18n\Time;

class AttendanceController extends BaseController
{
    protected $attendanceService;
    protected $employeeModel;
    protected $deptModel;

    public function __construct()
    {
        $this->attendanceService = new AttendanceService();
        $this->employeeModel = new EmployeeModel();
        $this->deptModel = new DepartmentModel();
    }

    /**
     * Giao diện điểm danh chính (Camera & GPS) cho TẤT CẢ nhân viên
     */
    public function index()
    {
        $employeeId = session()->get('employee_id');
        if (!$employeeId) {
            return redirect()->to('/dashboard')->with('error', 'Tài khoản chưa được gán hồ sơ nhân sự để thực hiện điểm danh.');
        }

        $isLan = $this->attendanceService->isLanIp($this->request->getIPAddress());
        
        $userAgent = $this->request->getUserAgent();
        $isMobile = $userAgent->isMobile();

        $data = [
            'title' => 'Chấm công thông minh | L.A.N ERP',
            'status' => $this->attendanceService->getTodayStatus($employeeId),
            'isLan' => $isLan,
            'isMobile' => $isMobile
        ];

        return view('dashboard/attendance/index', $data);
    }

    /**
     * Danh sách lịch sử điểm danh (Phân quyền: Admin xem tất cả, Trưởng phòng xem bộ phận, Nhân viên xem cá nhân)
     */
    public function list()
    {
        $role = session()->get('role_name');
        $myDeptId = session()->get('department_id');
        $myEmployeeId = session()->get('employee_id');

        if (!$myEmployeeId) {
            return redirect()->to('/dashboard')->with('error', 'Tài khoản chưa có thông tin nhân sự.');
        }

        $now = Time::now('Asia/Ho_Chi_Minh');
        $date = $this->request->getGet('date') ?: $now->format('Y-m-d');
        $deptId = $this->request->getGet('department_id');

        $db = \Config\Database::connect();
        $builder = $db->table('employees e');
        $builder->select('a.id, e.id as emp_id, e.full_name, d.name as dept_name, a.attendance_date, a.check_in_time, a.check_out_time, a.status, a.worked_hours, a.is_valid_location, a.check_in_photo, a.check_out_photo, a.check_in_note, a.check_out_note');
        $builder->join('departments d', 'd.id = e.department_id', 'left');
        
        $sort = $this->request->getGet('sort') ?? 'date';
        $order = $this->request->getGet('order') ?? 'desc';
        $direction = (strtolower($order) === 'asc') ? 'asc' : 'desc';

        $sortMap = [
            'name' => 'e.full_name',
            'dept' => 'd.name',
            'date' => 'a.attendance_date',
            'hours' => 'a.worked_hours',
            'status' => 'a.status'
        ];
        $orderField = $sortMap[$sort] ?? 'a.attendance_date';

        // Join với bảng attendance theo ngày (hoặc tháng nếu chọn xem lịch sử)
        $viewType = $this->request->getGet('view') ?: 'daily';
        
        if ($viewType === 'daily') {
            $builder->join('attendances a', 'a.employee_id = e.id AND a.attendance_date = ' . $db->escape($date), 'left');
            $builder->orderBy($orderField, $direction);
        } else {
            // Xem lịch sử tháng
            $month = $this->request->getGet('month') ?: $now->format('Y-m');
            $builder->join('attendances a', 'a.employee_id = e.id', 'inner');
            $builder->where('a.attendance_date >=', $month . '-01');
            $lastDay = Time::createFromFormat('Y-m-d', $month . '-01')->format('Y-m-t');
            $builder->where('a.attendance_date <=', $lastDay);
            $builder->orderBy($orderField, $direction);
        }

        // BẮT ĐẦU PHÂN QUYỀN
        if ($role === AppConstants::ROLE_ADMIN || $role === AppConstants::ROLE_MOD) {
            // Admin thấy toàn bộ, có thể lọc theo phòng ban
            if ($deptId) {
                $builder->where('e.department_id', $deptId);
            }
        } 
        elseif ($role === AppConstants::ROLE_TRUONG_PHONG) {
            // Trưởng phòng thấy nhân viên cùng phòng ban
            $builder->where('e.department_id', $myDeptId);
        } 
        else {
            // Nhân viên thường chỉ thấy của mình
            $builder->where('e.id', $myEmployeeId);
        }

        $records = $builder->get()->getResultArray();

        $data = [
            'role'        => $role,
            'title'       => 'Nhật ký chấm công | L.A.N ERP',
            'records'     => $records,
            'departments' => $this->deptModel->findAll(),
            'currentDate' => $date,
            'currentDept' => $deptId,
            'currentMonth'=> $this->request->getGet('month') ?: $now->format('Y-m'),
            'viewType'    => $viewType,
            'currentSort' => $sort,
            'currentOrder'=> $order
        ];

        // Nếu là nhân viên thường, dùng view history riêng cho gọn hoặc dùng chung
        if (!in_array($role, AppConstants::PRIVILEGED_ROLES)) {
            return view('dashboard/attendance/history', [
                'title'   => 'Lịch sử chấm công cá nhân | L.A.N ERP',
                'history' => $records,
                'currentMonth' => $data['currentMonth']
            ]);
        }

        return view('dashboard/attendance/admin_index', $data);
    }

    /**
     * API: Lấy trạng thái hiện tại (Real-time check)
     */
    public function status()
    {
        $employeeId = session()->get('employee_id');
        if (!$employeeId) return $this->response->setJSON(['code' => 1, 'error' => 'No employee ID']);

        $status = $this->attendanceService->getTodayStatus($employeeId);
        return $this->response->setJSON(['code' => 0, 'data' => $status]);
    }

    public function submit()
    {
        $employeeId = session()->get('employee_id');
        if (!$employeeId) return $this->response->setJSON(['code' => 1, 'error' => 'No employee ID']);

        $isMobile = $this->request->getUserAgent()->isMobile();
        $isLan = $this->attendanceService->isLanIp($this->request->getIPAddress());

        // PC tại văn phòng (LAN) thì không bắt buộc ảnh và GPS.
        // Điện thoại hoặc PC ngoài LAN thì bắt buộc.
        $needsMedia = $isMobile || !$isLan;

        $latitude  = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $note      = $this->request->getPost('note');
        $photo     = $this->request->getFile('photo');

        if ($needsMedia && (!$latitude || !$longitude || !$photo)) {
            return $this->response->setJSON(['code' => 2, 'error' => 'Dữ liệu không đầy đủ (Tọa độ hoặc Ảnh chụp)']);
        }

        $result = $this->attendanceService->submit($employeeId, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'note' => $note,
            'isLan' => !$isMobile && $isLan // Chỉ coi là Điểm danh LAN nếu không phải Mobile
        ], $needsMedia ? $photo : null);

        if ($result['status'] === 'error') {
            return $this->response->setJSON(['code' => 3, 'error' => $result['message']]);
        }

        return $this->response->setJSON(['code' => 0, 'message' => $result['message']]);
    }

    /**
     * Xuất dữ liệu chấm công
     */
    public function export()
    {
        $role = session()->get('role_name');
        if (!in_array($role, AppConstants::PRIVILEGED_ROLES)) {
            return redirect()->to('/dashboard');
        }

        $now = Time::now('Asia/Ho_Chi_Minh');
        $month = $this->request->getGet('month') ?: $now->format('Y-m');
        $db = \Config\Database::connect();
        
        $builder = $db->table('attendances a');
        $builder->select('e.full_name, d.name as dept_name, a.attendance_date, a.check_in_time, a.check_out_time, a.worked_hours, a.status');
        $builder->join('employees e', 'e.id = a.employee_id');
        $builder->join('departments d', 'd.id = e.department_id', 'left');
        $builder->where('a.attendance_date >=', $month . '-01');
        $lastDay = Time::createFromFormat('Y-m-d', $month . '-01')->format('Y-m-t');
        $builder->where('a.attendance_date <=', $lastDay);

        if ($role === AppConstants::ROLE_TRUONG_PHONG) {
            $builder->where('e.department_id', session()->get('department_id'));
        }

        $builder->orderBy('a.attendance_date', 'ASC');

        $results = $builder->get()->getResultArray();

        $filename = "ChamCong_" . $month . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['Họ tên', 'Phòng ban', 'Ngày', 'Vào', 'Ra', 'Số giờ', 'Trạng thái']);
        
        foreach ($results as $row) {
            fputcsv($output, [
                $row['full_name'],
                $row['dept_name'],
                $row['attendance_date'],
                $row['check_in_time'] ? Time::parse($row['check_in_time'])->format('H:i') : '',
                $row['check_out_time'] ? Time::parse($row['check_out_time'])->format('H:i') : '',
                $row['worked_hours'],
                $row['status']
            ]);
        }
        fclose($output);
        exit();
    }

    /**
     * API: Cập nhật trạng thái hàng loạt (Dành cho Admin/Mod)
     */
    public function bulkUpdate()
    {
        $role = session()->get('role_name');
        if (!in_array($role, AppConstants::PRIVILEGED_ROLES)) {
            return $this->response->setJSON(['code' => 1, 'error' => 'No permission']);
        }

        $ids = $this->request->getPost('ids');
        $status = $this->request->getPost('status');

        if (empty($ids) || !$status) {
            return $this->response->setJSON(['code' => 2, 'error' => 'Dữ liệu không hợp lệ']);
        }

        $db = \Config\Database::connect();
        $logService = new \App\Services\SystemLogService();
        $count = 0;

        foreach ($ids as $id) {
            $updated = $db->table('attendances')->where('id', $id)->update(['status' => $status]);
            if ($updated) {
                $count++;
                $logService->log('BULK_UPDATE_STATUS', 'Attendance', $id, ['new_status' => $status]);
            }
        }

        return $this->response->setJSON(['code' => 0, 'message' => "Đã cập nhật {$count} bản ghi sang trạng thái {$status}"]);
    }
}
