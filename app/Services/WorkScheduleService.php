<?php

namespace App\Services;

use App\Models\WorkScheduleModel;
use App\Models\EmployeeModel;

/**
 * WorkScheduleService
 * 
 * Tầng xử lý logic nghiệp vụ cho tính năng Lịch làm việc & Công tác.
 * Đảm bảo các quy tắc về bảo mật, phân quyền và thông báo.
 */
class WorkScheduleService extends BaseService
{
    protected $model;
    protected $employeeModel;
    protected $notificationService;

    public function __construct(
        WorkScheduleModel $model = null,
        EmployeeModel $employeeModel = null,
        NotificationService $notificationService = null
    ) {
        parent::__construct();
        $this->model = $model ?? new WorkScheduleModel();
        $this->employeeModel = $employeeModel ?? new EmployeeModel();
        $this->notificationService = $notificationService ?? new NotificationService();
    }

    /**
     * Lấy danh sách lịch trình theo bộ lọc
     */
    public function getList(array $filters = [])
    {
        return $this->model->getSchedules($filters);
    }

    /**
     * Tạo mới lịch trình
     * 
     * @param array $data Dữ liệu từ form
     * @return array
     */
    public function create(array $data)
    {
        // Kiểm tra logic thời gian
        if (strtotime($data['end_at']) < strtotime($data['start_at'])) {
            return $this->fail('Thời gian kết thúc không thể trước thời gian bắt đầu.');
        }

        // Tự động gán người tạo nếu chưa có
        if (empty($data['created_by'])) {
            $data['created_by'] = session()->get('employee_id');
        }

        // Thực hiện lưu dữ liệu
        if ($this->model->insert($data)) {
            $id = $this->model->getInsertID();
            
            // Ghi log hệ thống
            (new SystemLogService())->log('WS_CREATE', 'WorkSchedule', $id, [
                'title' => $data['title'],
                'type' => $data['type']
            ]);

            // THÔNG BÁO CHO CÔNG TY (Rule #10: Đồng bộ tập trung)
            $employee = $this->employeeModel->find($data['employee_id']);
            $empName = $employee ? $employee['full_name'] : 'Một nhân sự';
            $typeLabel = ($data['type'] === 'business_trip') ? 'Lịch công tác' : 'Lịch làm việc';
            
            $assignInfo = '';
            if (!empty($data['assigned_by_id'])) {
                $assigner = $this->employeeModel->find($data['assigned_by_id']);
                if ($assigner) {
                    $assignInfo = " (Phân công của: {$assigner['full_name']})";
                }
            }

            $titleNotif = "{$typeLabel}{$assignInfo} mới: {$empName}";
            $msgNotif = "{$empName} đã cập nhật {$typeLabel}: {$data['title']} tại " . ($data['location'] ?: 'Văn phòng') . " từ " . date('d/m/Y H:i', strtotime($data['start_at']));

            // Thông báo cho toàn thể nhân viên (theo yêu cầu: "thông báo cho nhau")
            $this->notificationService->notifyAllEmployees(
                $titleNotif,
                $msgNotif,
                'system',
                '/work-schedules'
            );

            return $this->success(['id' => $id], 'Đã tạo lịch trình và thông báo cho toàn thể nhân viên.');
        }

        return $this->fail('Không thể lưu lịch trình. Vui lòng thử lại.');
    }

    /**
     * Cập nhật lịch trình
     */
    public function update(int $id, array $data, int $currentEmployeeId)
    {
        $schedule = $this->model->find($id);
        if (!$schedule) {
            return $this->fail('Không tìm thấy lịch trình.');
        }

        // Kiểm tra quyền sở hữu (Rule #7)
        $isAdmin = has_permission('sys.admin') || session()->get('role_name') === \Config\AppConstants::ROLE_ADMIN;
        $isMySchedule = ($schedule['employee_id'] == $currentEmployeeId || $schedule['created_by'] == $currentEmployeeId);
        
        // Nếu không phải Admin và không phải lịch của mình
        if (!$isAdmin && !$isMySchedule) {
            // Kiểm tra nếu là Trưởng phòng thì được sửa lịch của nhân viên trong phòng
            $isManager = (session()->get('role_name') === \Config\AppConstants::ROLE_TRUONG_PHONG);
            if ($isManager) {
                $empModel = new \App\Models\EmployeeModel();
                $targetEmployee = $empModel->find($schedule['employee_id']);
                if (!$targetEmployee || $targetEmployee['department_id'] != session()->get('department_id')) {
                    return $this->fail('Bạn không có quyền chỉnh sửa lịch trình của nhân sự phòng ban khác.');
                }
            } else {
                return $this->fail('Bạn không có quyền chỉnh sửa lịch trình này.');
            }
        }

        if (isset($data['start_at']) && isset($data['end_at'])) {
            if (strtotime($data['end_at']) < strtotime($data['start_at'])) {
                return $this->fail('Thời gian kết thúc không thể trước thời gian bắt đầu.');
            }
        }

        if ($this->model->update($id, $data)) {
            // Ghi log
            (new SystemLogService())->log('WS_UPDATE', 'WorkSchedule', $id, $data);
            
            return $this->success(null, 'Đã cập nhật lịch trình thành công.');
        }

        return $this->fail('Cập nhật thất bại.');
    }

    /**
     * Xóa lịch trình
     */
    public function delete(int $id, int $currentEmployeeId)
    {
        $schedule = $this->model->find($id);
        if (!$schedule) {
            return $this->fail('Không tìm thấy lịch trình.');
        }

        // Kiểm tra quyền (Rule #7)
        $isAdmin = has_permission('sys.admin') || session()->get('role_name') === \Config\AppConstants::ROLE_ADMIN;
        $isMySchedule = ($schedule['employee_id'] == $currentEmployeeId || $schedule['created_by'] == $currentEmployeeId);

        if (!$isAdmin && !$isMySchedule) {
            return $this->fail('Bạn không có quyền xóa lịch trình này.');
        }

        if ($this->model->delete($id)) {
            // Ghi log
            (new SystemLogService())->log('WS_DELETE', 'WorkSchedule', $id);
            return $this->success(null, 'Đã xóa lịch trình.');
        }

        return $this->fail('Xóa thất bại.');
    }

    /**
     * Lấy chi tiết lịch trình
     */
    public function getDetail(int $id)
    {
        return $this->model->select('work_schedules.*, e.full_name as employee_name, creator.full_name as creator_name, assigner.full_name as assigner_name')
                        ->join('employees e', 'e.id = work_schedules.employee_id', 'left')
                        ->join('employees creator', 'creator.id = work_schedules.created_by', 'left')
                        ->join('employees assigner', 'assigner.id = work_schedules.assigned_by_id', 'left')
                        ->where('work_schedules.id', $id)
                        ->first();
    }
}
