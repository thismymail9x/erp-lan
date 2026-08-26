<?php

namespace App\Controllers;

use App\Services\WorkScheduleService;
use App\Services\CaseExpenseService;
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
    protected $caseExpenseService;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->service = new WorkScheduleService();
        $this->caseExpenseService = new CaseExpenseService();
    }

    /**
     * Hiển thị giao diện lịch trình chính (Calendar View).
     */
    public function index()
    {
        return redirect()->to('/dashboard');
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
            $requiresVehicle = !empty($item['requires_vehicle']);
            $typeLabel = ($item['type'] === 'business_trip') ? 'Công tác' : 'Tại văn phòng';
            $color = ($item['type'] === 'business_trip') ? '#ff3b30' : '#007aff';
            $casePayload = !empty($item['case_id'])
                ? $this->caseExpenseService->getScheduleCasePayload((int)$item['case_id'])
                : null;

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
                'title' => ($requiresVehicle ? "[Đăng ký xe] " : '') . "[{$typeLabel}] {$item['employee_name']}: {$item['title']}",
                'start' => $item['start_at'],
                'end' => $item['end_at'],
                'color' => $requiresVehicle ? '#2563eb' : (($item['type'] === 'business_trip') ? '#10b981' : '#f59e0b'), // Đăng ký xe: Blue, Công tác: Green, Văn phòng: Yellow
                'location' => $item['location'],
                'classNames' => $requiresVehicle ? ['ws-event-vehicle'] : [],
                'extendedProps' => [
                    'type' => $item['type'],
                    'type_label' => $typeLabel,
                    'requires_vehicle' => $requiresVehicle,
                    'employee_name' => $item['employee_name'],
                    'creator_name' => $item['creator_name'],
                    'location' => $item['location'],
                    'assigner_name' => $item['assigner_name'],
                    'case_id' => $casePayload['id'] ?? null,
                    'case_code' => $casePayload['code'] ?? null,
                    'case_title' => $casePayload['title'] ?? null,
                    'case_customer_name' => $casePayload['customer_name'] ?? null,
                    'time_display' => $timeDisplay,
                    'date_display' => $dateDisplay,
                    'is_same_day' => $isSameDay
                ]
            ];
        }

        // Lấy thêm danh sách nghỉ phép nếu được phép xem (Gộp module)
        if (has_permission('leave.view') && (empty($filters['types']) || in_array('leave', $filters['types']))) {
            $leaveModel = new \App\Models\LeaveRequestModel();
            $leaveQuery = $leaveModel->getLeaveRequests([
                'status' => 'approved'
            ]);
            
            if (!empty($filters['start_date'])) {
                $leaveQuery->groupStart()
                            ->where('leave_requests.start_date >=', $filters['start_date'])
                            ->orWhere('leave_requests.end_date >=', $filters['start_date'])
                          ->groupEnd();
            }
            if (!empty($filters['end_date'])) {
                $leaveQuery->groupStart()
                            ->where('leave_requests.start_date <=', $filters['end_date'])
                            ->orWhere('leave_requests.end_date <=', $filters['end_date'])
                          ->groupEnd();
            }

            if (!empty($filters['employee_id'])) {
                $leaveQuery->where('leave_requests.employee_id', $filters['employee_id']);
            }
            if (!empty($filters['dept_id'])) {
                $leaveQuery->where('e.department_id', $filters['dept_id']);
            }
            
            $leaves = $leaveQuery->findAll();
            $leaveTypeLabels = [
                'annual' => 'Nghỉ phép năm',
                'sick' => 'Nghỉ ốm',
                'personal' => 'Nghỉ có lương',
                'unpaid' => 'Nghỉ không lương',
                'maternity' => 'Nghỉ thai sản',
                'wedding' => 'Nghỉ cưới',
                'funeral' => 'Nghỉ tang',
            ];
            foreach ($leaves as $leave) {
                $start = $leave['start_date'];
                $end = $leave['end_date'];
                $baseLeaveLabel = $leaveTypeLabels[$leave['leave_type'] ?? ''] ?? 'Nghỉ phép';
                $leaveLabel = !empty($leave['is_emergency'])
                    ? 'Nghỉ gấp - ' . $baseLeaveLabel
                    : $baseLeaveLabel;
                $timeDisplay = 'Cả ngày';
                
                if ($leave['leave_duration'] === 'morning_half') {
                    $start .= ' 08:00:00';
                    $end .= ' 12:00:00';
                    $timeDisplay = 'Sáng (08:00-12:00)';
                } elseif ($leave['leave_duration'] === 'afternoon_half') {
                    $start .= ' 13:00:00';
                    $end .= ' 17:00:00';
                    $timeDisplay = 'Chiều (13:00-17:00)';
                } else {
                    $start .= ' 08:00:00';
                    $end .= ' 17:00:00';
                    $timeDisplay = 'Cả ngày (08:00-17:00)';
                }

                $events[] = [
                    'id' => 'leave_' . $leave['id'],
                    'title' => "[{$leaveLabel}] " . $leave['employee_name'],
                    'start' => $start,
                    'end' => $end,
                    'color' => '#e74c3c', // Màu đỏ cho nghỉ phép
                    'extendedProps' => [
                        'type' => 'leave',
                        'employee_name' => $leave['employee_name'],
                        'type_label' => $leaveLabel,
                        'location' => '',
                        'time_display' => $timeDisplay,
                        'date_display' => date('d/m', strtotime($leave['start_date'])) . ' - ' . date('d/m/Y', strtotime($leave['end_date'])),
                        'is_same_day' => ($leave['start_date'] === $leave['end_date'])
                    ]
                ];
            }
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
        // Checkbox không gửi value khi bỏ chọn, nên backend phải chuẩn hóa về 0/1.
        $data['requires_vehicle'] = $this->request->getPost('requires_vehicle') ? 1 : 0;
        if (!empty($data['case_id']) && !$this->caseExpenseService->canAccessCase((int)$data['case_id'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền gắn lịch vào vụ việc này.']);
        }
        
        // Chuyển đổi định dạng datetime-local từ HTML sang MySQL
        $data['start_at'] = str_replace('T', ' ', $data['start_at']) . ':00';
        $data['end_at'] = str_replace('T', ' ', $data['end_at']) . ':00';

        $result = $this->service->create($data);
        if ($result['status'] === 'success') {
            $result = $this->storeInlineCaseExpense($data, (int)($result['data']['id'] ?? 0), $result);
        }
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
        $casePayload = !empty($schedule['case_id'])
            ? $this->caseExpenseService->getScheduleCasePayload((int)$schedule['case_id'])
            : null;
        if (!$casePayload) {
            $schedule['case_id'] = null;
            unset($schedule['case_code'], $schedule['case_title'], $schedule['case_customer_name']);
        }

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
        // Không tin frontend: checkbox vắng mặt phải được hiểu là không đăng ký xe.
        $data['requires_vehicle'] = $this->request->getPost('requires_vehicle') ? 1 : 0;
        if (!empty($data['case_id']) && !$this->caseExpenseService->canAccessCase((int)$data['case_id'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bạn không có quyền gắn lịch vào vụ việc này.']);
        }
        
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
        if ($result['status'] === 'success') {
            $result = $this->storeInlineCaseExpense($data, $id, $result);
        }
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
        if ($result['status'] === 'success') {
            $this->caseExpenseService->deleteByWorkSchedule($id);
            $result['message'] .= ' Chi phí gắn với lịch trình cũng đã được xóa.';
        }
        return $this->response->setJSON($result);
    }

    private function storeInlineCaseExpense(array $scheduleData, int $workScheduleId, array $scheduleResult): array
    {
        $amount = preg_replace('/[^\d]/', '', (string)($scheduleData['expense_amount'] ?? ''));
        if ($amount === '' || (int)$amount <= 0) {
            return $scheduleResult;
        }

        if (empty($scheduleData['case_id'])) {
            $scheduleResult['message'] .= ' Chưa ghi chi phí vì lịch chưa gắn vụ việc.';
            return $scheduleResult;
        }

        $actualStartAt = $scheduleData['start_at'] ?? null;
        $actualEndAt = $scheduleData['end_at'] ?? null;
        if ($actualStartAt && $actualEndAt && strtotime($actualEndAt) < strtotime($actualStartAt)) {
            [$actualStartAt, $actualEndAt] = [$actualEndAt, $actualStartAt];
        }

        $expenseResult = $this->caseExpenseService->create([
            'case_id' => (int)$scheduleData['case_id'],
            'work_schedule_id' => $workScheduleId,
            'employee_id' => (int)($scheduleData['employee_id'] ?? session()->get('employee_id')),
            'expense_date' => !empty($actualStartAt) ? date('Y-m-d', strtotime($actualStartAt)) : date('Y-m-d'),
            'category' => $scheduleData['expense_category'] ?? 'other',
            'amount' => $amount,
            'actual_start_at' => $actualStartAt,
            'actual_end_at' => $actualEndAt,
            'note' => $scheduleData['expense_note'] ?? null,
        ], []);

        if ($expenseResult['status'] !== 'success') {
            $scheduleResult['message'] .= ' Chi phí chưa được ghi: ' . $expenseResult['message'];
            return $scheduleResult;
        }

        $scheduleResult['message'] .= ' Đã ghi thêm chi phí chờ duyệt.';
        return $scheduleResult;
    }
}
