<?php

namespace App\Controllers;

use App\Services\WorkScheduleService;
use App\Models\EmployeeModel;
use App\Models\DepartmentModel;
use Config\AppConstants;

/**
 * WorkScheduleController
 * 
 * Quản lý lịch làm việc và công tác của nhân sự.
 * Tích hợp hệ thống thông báo tự động để mọi người "thông báo cho nhau".
 */
class WorkScheduleController extends BaseController
{
    /**
     * Metadata cho hệ thống phân quyền tự động.
     */
    public static $modulePermissions = [
        'group' => 'Nhân sự & Lịch trình',
        'permissions' => [
            'work_schedule.view' => [
                'desc' => 'Xem lịch làm việc và công tác của toàn công ty',
                'roles' => [1, 2, 3, 4, 5, 6, 7] // Cấp cho mọi vai trò trong hệ thống
            ],
            'work_schedule.manage' => [
                'desc' => 'Tạo và quản lý lịch trình cá nhân/nhân viên cấp dưới',
                'roles' => [1, 2, 3, 4, 5, 6, 7] // Cấp cho mọi vai trò trong hệ thống
            ]
        ]
    ];

    protected $service;
    protected $employeeModel;
    protected $deptModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new WorkScheduleService();
        $this->employeeModel = new EmployeeModel();
        $this->deptModel = new DepartmentModel();
    }

    /**
     * Hiển thị giao diện lịch trình chính (Calendar View).
     */
    public function index()
    {
        if (!has_permission('work_schedule.view')) {
            return redirect()->to('/dashboard')->with('error', 'Bạn không có quyền xem lịch trình.');
        }

        $data = [
            'title' => 'Lịch làm việc & Công tác | L.A.N ERP',
            'departments' => $this->deptModel->findAll(),
            'employees' => get_available_employees(),
            'current_employee_id' => session()->get('employee_id')
        ];

        return view('dashboard/work_schedules/index', $data);
    }

    /**
     * API: Lấy danh sách sự kiện cho FullCalendar.
     */
    public function events()
    {
        if (!has_permission('work_schedule.view')) {
            return $this->response->setJSON([]);
        }

        $filters = [
            'start_date' => $this->request->getGet('start'),
            'end_date' => $this->request->getGet('end'),
            'employee_id' => $this->request->getGet('employee_id'),
            'dept_id' => $this->request->getGet('dept_id'),
            'types' => $this->request->getGet('types') ? explode(',', $this->request->getGet('types')) : []
        ];

        $schedules = $this->service->getList($filters);
        
        $events = [];
        foreach ($schedules as $item) {
            $typeLabel = ($item['type'] === 'business_trip') ? 'Công tác' : 'Tại văn phòng';
            $color = ($item['type'] === 'business_trip') ? '#ff3b30' : '#007aff';

            $startTs = strtotime($item['start_at']);
            $endTs = strtotime($item['end_at']);
            $isSameDay = date('Y-m-d', $startTs) === date('Y-m-d', $endTs);

            if ($isSameDay) {
                $timeDisplay = date('H:i', $startTs) . ' - ' . date('H:i', $endTs);
                $dateDisplay = date('d/m/Y', $startTs);
            } else {
                $timeDisplay = date('H:i d/m', $startTs) . ' ➔ ' . date('H:i d/m/Y', $endTs);
                $dateDisplay = 'Lịch trình dài ngày';
            }

            $events[] = [
                'id' => $item['id'],
                'title' => "[{$typeLabel}] {$item['employee_name']}: {$item['title']}",
                'start' => $item['start_at'],
                'end' => $item['end_at'],
                'color' => $color,
                'location' => $item['location'],
                'extendedProps' => [
                    'type' => $item['type'],
                    'type_label' => $typeLabel,
                    'employee_name' => $item['employee_name'],
                    'creator_name' => $item['creator_name'],
                    'location' => $item['location'],
                    'assigner_name' => $item['assigner_name'],
                    'time_display' => $timeDisplay,
                    'date_display' => $dateDisplay,
                    'is_same_day' => $isSameDay
                ]
            ];
        }

        return $this->response->setJSON($events);
    }

    /**
     * Xử lý lưu lịch trình mới.
     */
    public function store()
    {
        if (!has_permission('work_schedule.manage')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền tạo lịch trình.']);
        }

        $rules = [
            'employee_id' => 'required',
            'type'        => 'required',
            'title'       => 'required|min_length[3]',
            'start_at'    => 'required|valid_date[Y-m-d H:i]',
            'end_at'      => 'required|valid_date[Y-m-d H:i]',
        ];

        if (!$this->validate($rules)) {
            $error = current($this->validator->getErrors());
            return $this->response->setJSON(['status' => 'error', 'message' => $error]);
        }

        $data = $this->request->getPost();
        
        // Chuyển đổi định dạng datetime-local từ HTML sang MySQL
        $data['start_at'] = str_replace('T', ' ', $data['start_at']) . ':00';
        $data['end_at'] = str_replace('T', ' ', $data['end_at']) . ':00';

        $result = $this->service->create($data);
        return $this->response->setJSON($result);
    }

    /**
     * Lấy chi tiết để sửa hoặc xem.
     */
    public function detail(int $id)
    {
        $schedule = $this->service->getDetail($id);
        if (!$schedule) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Không tìm thấy lịch trình.']);
        }

        // Kiểm tra quyền chỉnh sửa để hiển thị UI
        $currentEmpId = session()->get('employee_id');
        $roleName = session()->get('role_name');
        $isAdmin = (has_permission('sys.admin') || $roleName === \Config\AppConstants::ROLE_ADMIN);
        $isOwner = ($schedule['employee_id'] == $currentEmpId || $schedule['created_by'] == $currentEmpId);
        $isManager = ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG);
        
        $canEdit = $isAdmin || $isOwner;
        if (!$canEdit && $isManager) {
            $empModel = new \App\Models\EmployeeModel();
            $target = $empModel->find($schedule['employee_id']);
            if ($target && $target['department_id'] == session()->get('department_id')) {
                $canEdit = true;
            }
        }

        $schedule['can_edit'] = $canEdit;
        $schedule['can_delete'] = ($isAdmin || $isOwner); // Xóa thì chỉ Admin hoặc Chủ sở hữu

        return $this->response->setJSON(['status' => 'success', 'data' => $schedule]);
    }

    /**
     * Cập nhật lịch trình.
     */
    public function update(int $id)
    {
        if (!has_permission('work_schedule.manage')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền chỉnh sửa.']);
        }

        $data = $this->request->getPost();
        
        $rules = [
            'type'     => 'required',
            'title'    => 'required|min_length[3]',
            'start_at' => 'required|valid_date[Y-m-d H:i]',
            'end_at'   => 'required|valid_date[Y-m-d H:i]',
        ];

        if (!$this->validate($rules)) {
            $error = current($this->validator->getErrors());
            return $this->response->setJSON(['status' => 'error', 'message' => $error]);
        }

        // Chuyển sang định dạng MySQL (Y-m-d H:i:s)
        $data['start_at'] .= ':00';
        $data['end_at'] .= ':00';

        $result = $this->service->update($id, $data, session()->get('employee_id'));
        return $this->response->setJSON($result);
    }

    /**
     * Xóa lịch trình.
     */
    public function delete(int $id)
    {
        if (!has_permission('work_schedule.manage')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền xóa.']);
        }

        $result = $this->service->delete($id, session()->get('employee_id'));
        return $this->response->setJSON($result);
    }
}
