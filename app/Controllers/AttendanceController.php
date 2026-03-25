<?php

namespace App\Controllers;

use App\Services\AttendanceService;
use App\Models\EmployeeModel;
use App\Models\DepartmentModel;
use Config\AppConstants;
use CodeIgniter\I18n\Time;

/**
 * AttendanceController
 * 
 * Bộ điều khiển quản lý toàn bộ hệ thống Chấm công thông minh.
 * Hỗ trợ các tính năng: Điểm danh qua Camera/GPS, Tra cứu lịch sử, 
 * Quản lý Token văn phòng và Xuất báo cáo CSV.
 */
class AttendanceController extends BaseController
{
    protected $attendanceService;
    protected $employeeModel;
    protected $deptModel;

    /**
     * Khởi tạo các Service và Model cần thiết.
     * Lưu ý: Trong CodeIgniter 4, việc khởi tạo Service trong initController giúp 
     * tối ưu hóa vì không phải Request nào cũng cần nạp toàn bộ Service.
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        
        $this->attendanceService = new AttendanceService();
        $this->employeeModel = new EmployeeModel();
        $this->deptModel = new DepartmentModel();
    }

    /**
     * Hiển thị giao diện Chấm công chính.
     * Tự động nhận diện thiết bị (Mobile vs PC) và môi trường mạng (LAN vs Outside).
     */
    public function index()
    {
        // 1. Kiểm tra tài khoản đã liên kết với hồ sơ nhân sự chưa
        $employeeId = session()->get('employee_id');
        if (!$employeeId) {
            return redirect()->to('/dashboard')->with('error', 'Tài khoản chưa được gán hồ sơ nhân sự để thực hiện điểm danh.');
        }

        // 2. Kiểm tra IP hiện tại có thuộc dải mạng LAN văn phòng không
        $isLan = $this->attendanceService->isLanIp($this->request->getIPAddress());
        
        // 3. Phân tích User Agent để xác định giao diện (Mobile cần GPS & Cam, PC LAN có thể bỏ qua)
        $userAgent = $this->request->getUserAgent();
        $isMobile = $userAgent->isMobile();

        // 4. Đóng gói dữ liệu trạng thái hiện tại (Đã In chưa? Đã Out chưa?)
        $data = [
            'title' => 'Chấm công thông minh | L.A.N ERP',
            'status' => $this->attendanceService->getTodayStatus($employeeId),
            'isLan' => $isLan,
            'isMobile' => $isMobile,
            'role' => session()->get('role_name')
        ];

        return view('dashboard/attendance/index', $data);
    }

    /**
     * Hiển thị bảng Nhật ký chấm công.
     * Tùy theo Role mà người dùng sẽ thấy dữ liệu Cá nhân, Phòng ban hoặc Toàn công ty.
     */
    public function list()
    {
        // Thu thập thông tin định danh từ Session
        $role = session()->get('role_name');
        $myDeptId = session()->get('department_id');
        $myEmployeeId = session()->get('employee_id');

        if (!$myEmployeeId) {
            return redirect()->to('/dashboard')->with('error', 'Tài khoản chưa có thông tin nhân sự.');
        }

        // Lấy các tham số lọc từ GET Request
        $now = Time::now('Asia/Ho_Chi_Minh');
        $date = $this->request->getGet('date') ?: $now->format('Y-m-d');
        $deptId = $this->request->getGet('department_id');
        $empFilterId = $this->request->getGet('employee_id');

        // Khởi tạo Builder truy vấn dữ liệu chấm công kết nối với bảng Nhân sự và Phòng ban
        $db = \Config\Database::connect();
        $builder = $db->table('employees e');
        $builder->select('a.id, e.id as emp_id, e.full_name, d.name as dept_name, a.attendance_date, a.check_in_time, a.check_out_time, a.status, a.worked_hours, a.is_valid_location, a.check_in_photo, a.check_out_photo, a.check_in_note, a.check_out_note');
        $builder->join('departments d', 'd.id = e.department_id', 'left');
        
        // Xử lý Sắp xếp (Sorting)
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

        // Xác định Chế độ xem: Theo ngày (Daily) hay Theo tháng (Monthly)
        $viewType = $this->request->getGet('view') ?: 'daily';
        
        if ($viewType === 'daily') {
            // Xem theo ngày: Join với bảng attendance của đúng ngày đó
            $builder->join('attendances a', 'a.employee_id = e.id AND a.attendance_date = ' . $db->escape($date), 'left');
            $builder->orderBy($orderField, $direction);
        } else {
            // Xem theo tháng: Chỉ lấy những ngày có dữ liệu phát sinh trong tháng
            $month = $this->request->getGet('month') ?: $now->format('Y-m');
            $builder->join('attendances a', 'a.employee_id = e.id', 'inner');
            $builder->where('a.attendance_date >=', $month . '-01');
            $lastDay = Time::createFromFormat('Y-m-d', $month . '-01')->format('Y-m-t');
            $builder->where('a.attendance_date <=', $lastDay);
            $builder->orderBy($orderField, $direction);
        }

        // --- PHÂN TÁCH DỮ LIỆU DỰA TRÊN QUYỀN TRUY CẬP (Data Isolation) ---
        if ($role === AppConstants::ROLE_ADMIN || $role === AppConstants::ROLE_MOD) {
            // Admin/Giám đốc: Được phép lọc theo bất kỳ phòng ban nào
            if ($deptId) $builder->where('e.department_id', $deptId);
            if ($empFilterId) $builder->where('e.id', $empFilterId);
        } elseif ($role === AppConstants::ROLE_TRUONG_PHONG) {
            // Trưởng phòng: Bắt buộc chỉ thấy dữ liệu nhân viên cùng phòng ban
            $builder->where('e.department_id', $myDeptId);
            if ($empFilterId) $builder->where('e.id', $empFilterId);
        } else {
            // Nhân viên: Chỉ thấy duy nhất lịch sử cá nhân của mình
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
            'currentEmployee' => $empFilterId,
            'employeeInfo' => $empFilterId ? $this->employeeModel->find($empFilterId) : null,
            'currentMonth'=> $this->request->getGet('month') ?: $now->format('Y-m'),
            'viewType'    => $viewType,
            'currentSort' => $sort,
            'currentOrder'=> $order
        ];

        // Nếu là nhân sự bình thường, chuyển sang View lịch sử tối giản
        if (!in_array($role, AppConstants::PRIVILEGED_ROLES) || ($empFilterId && $viewType === 'monthly')) {
            return view('dashboard/attendance/history', [
                'title'   => ($empFilterId && $empFilterId != $myEmployeeId) ? 'Lịch sử chấm công: ' . ($data['employeeInfo']['full_name'] ?? '...') : 'Lịch sử chấm công cá nhân | L.A.N ERP',
                'history' => $records,
                'currentMonth' => $data['currentMonth'],
                'targetEmployeeId' => $empFilterId,
                'isViewingOthers' => ($empFilterId && $empFilterId != $myEmployeeId)
            ]);
        }

        // Quản lý xem View Dashboard tổng quát
        return view('dashboard/attendance/admin_index', $data);
    }

    /**
     * API: Lấy trạng thái chấm công Real-time (Phục vụ UI tự động cập nhật).
     */
    public function status()
    {
        $employeeId = session()->get('employee_id');
        if (!$employeeId) return $this->response->setJSON(['code' => 1, 'error' => 'No employee ID']);

        // Gọi service để biết nhân viên này đã In/Out hôm nay chưa
        $status = $this->attendanceService->getTodayStatus($employeeId);
        return $this->response->setJSON(['code' => 0, 'data' => $status]);
    }

    /**
     * Xử lý yêu cầu gửi Chấm công (Submit Attendance).
     * Bao gồm cả logic kiểm tra điều kiện an toàn (Dùng PC văn phòng vs Dùng Mobile ngoài mạng).
     */
    public function submit()
    {
        $employeeId = session()->get('employee_id');
        if (!$employeeId) return $this->response->setJSON(['code' => 1, 'error' => 'No employee ID']);

        // 1. Phân tích môi trường yêu cầu
        $isMobile = $this->request->getUserAgent()->isMobile();
        $isLan = $this->attendanceService->isLanIp($this->request->getIPAddress());
        $officeToken = $this->request->getPost('officeToken');

        // 2. LOGIC ĐIỀU KIỆN (BẮT BUỘC TRUYỀN MEDIA):
        // Nếu là Mobile HOẶC (PC không thuộc LAN và không có Token xác thực văn phòng) -> Bắt buộc Ảnh & GPS
        $needsMedia = $isMobile || (!$isLan && !$officeToken);

        $latitude  = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $note      = $this->request->getPost('note');
        $photo     = $this->request->getFile('photo');

        // Kiểm tra tính đầy đủ của dữ liệu nếu ở chế độ bắt buộc
        if ($needsMedia && (!$latitude || !$longitude || !$photo)) {
            return $this->response->setJSON(['code' => 2, 'error' => 'Vui lòng cung cấp đầy đủ Ảnh chụp và Vị trí để xác thực ngoài văn phòng.']);
        }

        // 3. Gọi Service thực hiện nghiệp vụ lưu trữ và tính toán thời gian làm việc
        $result = $this->attendanceService->submit($employeeId, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'note' => $note,
            'clientIp' => $this->request->getIPAddress(),
            'officeToken' => $officeToken,
            'isLan' => !$isMobile && $isLan
        ], ($needsMedia && !$officeToken) ? $photo : null);

        // Phản hồi kết quả dạng JSON cho Front-end (Toastify)
        if ($result['status'] === 'error') {
            return $this->response->setJSON(['code' => 3, 'error' => $result['message']]);
        }

        return $this->response->setJSON(['code' => 0, 'message' => $result['message']]);
    }

    /**
     * Lấy Security Token văn phòng (Chỉ Admin)
     */
    public function getOfficeToken()
    {
        if (session()->get('role_name') !== AppConstants::ROLE_ADMIN) {
            return $this->response->setJSON(['code' => 1, 'error' => 'Không đủ thẩm quyền']);
        }
        $token = $this->attendanceService->getOfficeToken();
        return $this->response->setJSON(['code' => 0, 'token' => $token]);
    }

    /**
     * Cập nhật trạng thái hàng loạt cho nhiều lượt chấm công.
     */
    public function bulkUpdate()
    {
        $role = session()->get('role_name');
        if (!in_array($role, AppConstants::PRIVILEGED_ROLES)) {
            return $this->response->setJSON(['code' => 1, 'error' => 'Bạn không có quyền thực hiện nghiệp vụ này.']);
        }

        $ids = $this->request->getPost('ids');
        $status = $this->request->getPost('status');

        if (empty($ids) || empty($status)) {
            return $this->response->setJSON(['code' => 2, 'error' => 'Dữ liệu đầu vào không hợp lệ hoặc bị thiếu.']);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('attendances');
        
        $builder->whereIn('id', $ids);
        if ($builder->update(['status' => $status])) {
            return $this->response->setJSON(['code' => 0, 'message' => 'Cập nhật thành công ' . count($ids) . ' lượt chấm công.']);
        }

        return $this->response->setJSON(['code' => 3, 'error' => 'Lỗi hệ thống khi cập nhật cơ sở dữ liệu.']);
    }

    /**
     * Xuất dữ liệu chấm công CSV (Phân quyền Trưởng phòng/Admin)
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
}
