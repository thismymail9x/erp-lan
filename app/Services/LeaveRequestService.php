<?php

namespace App\Services;

use App\Models\LeaveRequestModel;
use App\Models\AttendanceModel;
use DateTime;

class LeaveRequestService
{
    protected $model;
    protected $attendanceModel;
    protected $notificationService;
    protected $employeeModel;
    protected $roleModel;
    protected $userModel;

    public function __construct(
        \App\Models\LeaveRequestModel $model = null,
        \App\Models\AttendanceModel $attendanceModel = null,
        \App\Models\EmployeeModel $employeeModel = null,
        \App\Models\RoleModel $roleModel = null,
        \App\Models\UserModel $userModel = null,
        NotificationService $notificationService = null
    ) {
        $this->model = $model ?? new \App\Models\LeaveRequestModel();
        $this->attendanceModel = $attendanceModel ?? new \App\Models\AttendanceModel();
        $this->employeeModel = $employeeModel ?? new \App\Models\EmployeeModel();
        $this->roleModel = $roleModel ?? new \App\Models\RoleModel();
        $this->userModel = $userModel ?? new \App\Models\UserModel();
        $this->notificationService = $notificationService ?? new NotificationService();
    }

    /**
     * TẠO ĐƠN XIN NGHỈ PHÉP (Master Service Logic)
     * 
     * @param array $data Dữ liệu từ Request (Controller chỉ chuyển tiếp)
     * @return array Trạng thái xử lý và Thông báo
     * 
     * GIẢI THÍCH QUY TẮC BÁO TRƯỚC (RULE #1):
     * Tại sao phải báo trước? Để đảm bảo vận hành doanh nghiệp không bị gián đoạn.
     * - Nghỉ 1 ngày: Báo trước 1 ngày (Đủ để sắp xếp công việc trong ngày).
     * - Nghỉ 2-4 ngày: Báo trước 3 ngày (Cần thời gian bàn giao danh sách khách hàng/vụ việc).
     * - Nghỉ >= 5 ngày: Báo trước 7 ngày (Kỳ nghỉ dài, cần lãnh đạo phê duyệt và phân bổ lại nhân sự thay thế).
     */
    public function create(array $data)
    {
        // 1. Tự động tính toán tổng số ngày nghỉ dựa trên ranh giới thực tế
        $data['total_days'] = $this->calculateDays($data['start_date'], $data['end_date']);
        
        // Xử lý logic Nghỉ nửa ngày (Rule #1: Cố định thời gian để tránh sai lệch chấm công)
        // Tại sao: Khi nghỉ nửa ngày, thời lượng tổng chắc chắn là 0.5 và không thể kéo dài qua nhiều ngày.
        if (isset($data['leave_duration']) && in_array($data['leave_duration'], ['morning_half', 'afternoon_half'])) {
            $data['total_days'] = 0.5;
            $data['end_date'] = $data['start_date']; // Ép buộc ngày kết thúc trùng ngày bắt đầu để bảo vệ dữ liệu nội bộ
        }

        $data['status'] = 'pending';

        // --- RÀO CHẮN NGÀY THÁNG (RULE #7: Security & Data Integrity) ---
        if (new DateTime($data['end_date']) < new DateTime($data['start_date'])) {
            return [
                'status'  => 'error',
                'message' => 'Ngày kết thúc không được phép diễn ra trước ngày bắt đầu. Vui lòng kiểm tra lại.'
            ];
        }

        // --- RÀO CHẮN BÀN GIAO (RULE #7: Security) ---
        if (!empty($data['handover_to']) && $data['handover_to'] == $data['employee_id']) {
            return [
                'status'  => 'error',
                'message' => 'Bạn không thể chọn chính mình làm người nhận bàn giao công việc.'
            ];
        }

        // 2. LOGIC KIỂM SOÁT BÁO TRƯỚC (RULE #1 & #7: Security)
        // Nếu không thuộc diện 'Nghỉ đột xuất' (Khẩn cấp), bắt buộc phải tuân thủ dải ngày báo trước.
        if (empty($data['is_emergency'])) {
            $today = new DateTime('today');
            $start = new DateTime($data['start_date']);
            
            // Tính số ngày làm việc từ khi làm đơn tới lúc nghỉ
            $noticeDays = $today->diff($start)->days;
            if ($start < $today) {
                // Trường hợp chọn ngày lùi về quá khứ (Dấu hiệu bất thường)
                $noticeDays = -1; 
            }

            $total = (float)$data['total_days'];
            $isValid = true;
            $msg = "";

            // Áp dụng bộ lọc dải ngày nghỉ theo yêu cầu quản trị
            if ($total == 1 && $noticeDays < 1) {
                $isValid = false;
                $msg = "Nghỉ 1 ngày cần báo trước ít nhất 1 ngày làm việc.";
            } elseif ($total >= 2 && $total < 5 && $noticeDays < 3) {
                $isValid = false;
                $msg = "Nghỉ từ 2-4 ngày cần báo trước ít nhất 3 ngày làm việc.";
            } elseif ($total >= 5 && $noticeDays < 7) {
                $isValid = false;
                $msg = "Nghỉ từ 5 ngày trở lên cần báo trước ít nhất 7 ngày làm việc.";
            }

            if (!$isValid) {
                return [
                    'status'  => 'error',
                    'message' => "Vi phạm quy dịnh báo trước: " . $msg
                ];
            }
        }

        // 3. THỰC THI LƯU TRỮ (BaseModel sẽ tự động chuyển "" thành NULL cho handover_to - Rule #6)
        if ($this->model->save($data)) {
            $insertId = $this->model->getInsertID();
            $employee = $this->employeeModel->find($data['employee_id']);
            $empName  = $employee ? $employee['full_name'] : 'Nhân viên';

            // 4. THÔNG BÁO CHO NGƯỜI NHẬN BÀN GIAO (CHỈ GỬI NẾU CÓ - Rule #7)
            if (!empty($data['handover_to'])) {
                $handoverPerson = $this->employeeModel->find($data['handover_to']);
                if ($handoverPerson && $handoverPerson['user_id']) {
                    $this->notificationService->sendToUser(
                        $handoverPerson['user_id'],
                        "Chỉ định bàn giao công việc",
                        "Bạn được chọn là người nhận bàn giao công việc từ {$empName} cho kỳ nghỉ từ " . date('d/m/Y', strtotime($data['start_date'])) . ".",
                        'info',
                        '/leave-requests'
                    );
                }
            }

            // Ghi nhật ký hệ thống (Audit Trail)
            (new \App\Services\SystemLogService())->log('LEAVE_CREATE', 'LeaveRequest', $insertId, [
                'note' => "Nhân viên {$empName} tạo đơn nghỉ phép. Bàn giao: " . ($data['handover_to'] ?? 'Không')
            ]);

            // 5. THÔNG BÁO QUẢN LÝ & ADMIN (Smart Dispatch)
            $recipientUserIds = [];

            // 1. Lấy danh sách Admin
            $adminRole = $this->roleModel->where('name', \Config\AppConstants::ROLE_ADMIN)->first();
            if ($adminRole) {
                $adminIds = $this->userModel->where('role_id', $adminRole['id'])->where('active_status', 1)->findColumn('id') ?? [];
                $recipientUserIds = array_merge($recipientUserIds, $adminIds);
            }

            // 2. Lấy danh sách Quản lý trực tiếp (Manager)
            $departmentId = $employee ? $employee['department_id'] : 3;
            $managerRole = $this->roleModel->where('name', \Config\AppConstants::ROLE_TRUONG_PHONG)->first();
            if ($managerRole) {
                $managerRecords = $this->userModel->select('users.id')
                                             ->join('employees', 'employees.user_id = users.id')
                                             ->where('employees.department_id', $departmentId)
                                             ->where('users.role_id', $managerRole['id']) 
                                             ->where('users.active_status', 1)
                                             ->get()->getResultArray();
                $managers = array_column($managerRecords, 'id');
                $recipientUserIds = array_merge($recipientUserIds, $managers);
            }

            // 3. Gửi thông báo tập trung (Tự động lọc trùng và loại bỏ chính người làm đơn)
            $this->notificationService->sendToMultiple(
                $recipientUserIds,
                "Đơn nghỉ phép mới: {$empName}",
                "Có đơn nghỉ phép mới từ nhân viên {$empName} đang chờ xét duyệt.",
                'approval',
                '/leave-requests'
            );

            return [
                'status'  => 'success',
                'message' => 'Đơn xin nghỉ phép đã được gửi và đang chờ phê duyệt.',
                'id'      => $insertId
            ];
        }

        return [
            'status' => 'error',
            'errors' => $this->model->errors()
        ];
    }

    /**
     * Phê duyệt đơn nghỉ phép.
     * Tích hợp với module chấm công: Sau khi duyệt, ghi nhận trạng thái 'LEAVE' (hoặc 'PHE_DUYET') cho các ngày nghỉ để bảo vệ bảng lương.
     */
    public function approve(int $id, int $approverId, string $note = '')
    {
        $request = $this->model->find($id);
        if (!$request) {
            return ['status' => 'error', 'message' => 'Không tìm thấy đơn yêu cầu.'];
        }
        if ($request['status'] !== 'pending') {
            return ['status' => 'error', 'message' => 'Đơn này đã được xử lý rồi.'];
        }

        $updateData = [
            'status'        => 'approved',
            'approver_id'   => $approverId,
            'approval_note' => $note,
            'approved_at'   => date('Y-m-d H:i:s')
        ];

        if ($this->model->update($id, $updateData)) {
            // ĐỒNG BỘ CHẤM CÔNG (Attendance Sync Logic):
            $this->syncToAttendance($request['employee_id'], $request['start_date'], $request['end_date'], $request['leave_type']);

            // THÔNG BÁO NHÂN VIÊN: Gửi kết quả phê duyệt cho người làm đơn
            $emp = $this->employeeModel->find($request['employee_id']);
            if ($emp && $emp['user_id']) {
                $this->notificationService->sendToUser(
                    $emp['user_id'],
                    "Đơn nghỉ phép đã được PHÊ DUYỆT",
                    "Đơn xin nghỉ phép từ ngày " . date('d/m/Y', strtotime($request['start_date'])) . " đã được cấp trên phê duyệt.",
                    'system',
                    '/leave-requests'
                );
            }

            // THÔNG BÁO CHO ADMIN (Giám sát nhân sự)
            $this->notificationService->notifyAdmins(
                "Đơn nghỉ phép đã duyệt",
                "Đơn nghỉ của {$emp['full_name']} đã được phê duyệt.",
                'system',
                '/leave-requests'
            );

            // Ghi nhật ký hệ thống
            (new \App\Services\SystemLogService())->log('LEAVE_APPROVE', 'LeaveRequest', $id, [
                'note' => "Phê duyệt đơn nghỉ phép ID #{$id}. Note: {$note}"
            ]);

            return ['status' => 'success', 'message' => 'Đã phê duyệt đơn nghỉ phép. Dữ liệu ngày nghỉ đã được đồng bộ vào hệ thống chấm công.'];
        }

        return ['status' => 'error', 'message' => 'Phát sinh lỗi khi cập nhật trạng thái đơn.'];
    }

    /**
     * Từ chối đơn nghỉ phép.
     */
    public function reject(int $id, int $approverId, string $note = '')
    {
        $request = $this->model->find($id);
        if (!$request) {
            return ['status' => 'error', 'message' => 'Không tìm thấy đơn yêu cầu.'];
        }
        if ($request['status'] !== 'pending') {
            return ['status' => 'error', 'message' => 'Đơn này đã được xử lý rồi.'];
        }

        $updateData = [
            'status'        => 'rejected',
            'approver_id'   => $approverId,
            'approval_note' => $note,
            'approved_at'   => date('Y-m-d H:i:s')
        ];

        if ($this->model->update($id, $updateData)) {
            // THÔNG BÁO NHÂN VIÊN: Gửi kết quả từ chối cho người làm đơn
            $emp = $this->employeeModel->find($request['employee_id']);
            if ($emp && $emp['user_id']) {
                $this->notificationService->sendToUser(
                    $emp['user_id'],
                    "Đơn nghỉ phép đã bị TỪ CHỐI",
                    "Đơn xin nghỉ phép từ ngày " . date('d/m/Y', strtotime($request['start_date'])) . " đã bị từ chối. Lý do: {$note}",
                    'alert',
                    '/leave-requests'
                );
            }

            // THÔNG BÁO CHO ADMIN (Giám sát nhân sự)
            $this->notificationService->notifyAdmins(
                "Đơn nghỉ phép bị từ chối",
                "Đơn nghỉ của {$emp['full_name']} đã bị từ chối.",
                'alert',
                '/leave-requests'
            );

            // Ghi nhật ký hệ thống
            (new \App\Services\SystemLogService())->log('LEAVE_REJECT', 'LeaveRequest', $id, [
                'note' => "Từ chối đơn nghỉ phép ID #{$id}. Lý do: {$note}"
            ]);

            return ['status' => 'success', 'message' => 'Đã từ chối đơn nghỉ phép.'];
        }

        return ['status' => 'error', 'message' => 'Lỗi phát sinh khi từ chối đơn.'];
    }

    /**
     * Đồng bộ ngày nghỉ vào bảng Attendances.
     */
    private function syncToAttendance(int $employeeId, string $start, string $end, string $type)
    {
        $begin = new DateTime($start);
        $last = new DateTime($end);
        $last = $last->modify('+1 day');

        $interval = new \DateInterval('P1D');
        $daterange = new \DatePeriod($begin, $interval, $last);

        foreach($daterange as $date){
            $dateStr = $date->format('Y-m-d');
            
            // Kiểm tra xem ngày này đã có bản ghi chấm công chưa
            $existing = $this->attendanceModel->where('employee_id', $employeeId)
                                              ->where('attendance_date', $dateStr)
                                              ->first();
            
            $statusStr = 'LEAVE_' . strtoupper($type);
            
            // Xử lý logic nghỉ nửa ngày
            $leaveDuration = $this->model->where('employee_id', $employeeId)
                                         ->where('start_date <=', $dateStr)
                                         ->where('end_date >=', $dateStr)
                                         ->where('status', 'approved')
                                         ->select('leave_duration')
                                         ->first();
            
            if ($leaveDuration) {
                if ($leaveDuration['leave_duration'] === 'morning_half') {
                    $statusStr = 'LEAVE_MORNING';
                } elseif ($leaveDuration['leave_duration'] === 'afternoon_half') {
                    $statusStr = 'LEAVE_AFTERNOON';
                }
            }

            if ($existing) {
                // Cập nhật trạng thái nếu đã có check-in rồi (vd: nghỉ bù nửa ngày hoặc đổi trạng thái)
                $this->attendanceModel->update($existing['id'], [
                    'status' => $statusStr,
                    'check_in_note' => 'Hệ thống tự động đồng bộ từ Đơn nghỉ phép đã duyệt.'
                ]);
            } else {
                // Tạo bản ghi chấm công ảo để tính lương/báo cáo
                $this->attendanceModel->insert([
                    'employee_id'     => $employeeId,
                    'attendance_date' => $dateStr,
                    'status'          => $statusStr,
                    'check_in_note'   => 'Nghỉ phép có đơn: ' . $type
                ]);
            }
        }
    }

    /**
     * Xóa đơn nghỉ phép (Chỉ Admin).
     * Phải dọn dẹp cả dữ liệu chấm công đã đồng bộ (nếu đơn đã duyệt).
     */
    public function delete(int $id)
    {
        $request = $this->model->find($id);
        if (!$request) return ['status' => 'error', 'message' => 'Không tìm thấy đơn.'];

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Nếu đơn đã duyệt, phải xóa các bản ghi chấm công ảo đã tạo
        if ($request['status'] === 'approved') {
            $this->cleanupAttendance($request['employee_id'], $request['start_date'], $request['end_date']);
        }

        // 2. Xóa đơn
        $this->model->delete($id);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return ['status' => 'error', 'message' => 'Lỗi hệ thống khi xóa dữ liệu.'];
        }

        // Ghi nhật ký
        (new \App\Services\SystemLogService())->log('LEAVE_DELETE', 'LeaveRequest', $id, [
            'note' => "Quản trị viên xóa đơn ID #{$id} của nhân viên ID #{$request['employee_id']}"
        ]);

        return ['status' => 'success', 'message' => 'Đã xóa đơn nghỉ phép và dọn dẹp dữ liệu chấm công liên quan.'];
    }

    /**
     * Cập nhật đơn nghỉ phép (Dành cho Admin sửa sai).
     */
    public function update(int $id, array $data)
    {
        $request = $this->model->find($id);
        if (!$request) return ['status' => 'error', 'message' => 'Không tìm thấy đơn.'];

        $db = \Config\Database::connect();
        $db->transStart();

        // Nếu đơn đã duyệt, phải dọn dẹp attendance cũ trước khi cập nhật mới
        if ($request['status'] === 'approved') {
            $this->cleanupAttendance($request['employee_id'], $request['start_date'], $request['end_date']);
        }

        // Cập nhật lại số ngày
        if (isset($data['start_date']) && isset($data['end_date'])) {
            $data['total_days'] = $this->calculateDays($data['start_date'], $data['end_date']);
            
            // Xử lý logic Nghỉ nửa ngày (Rule #1)
            // Tại sao: Nếu Admin cập nhật đơn thành nửa ngày, phải cưỡng chế lại ngày kết thúc và số ngày để tránh xung đột dữ liệu.
            if (isset($data['leave_duration']) && in_array($data['leave_duration'], ['morning_half', 'afternoon_half'])) {
                $data['total_days'] = 0.5;
                $data['end_date'] = $data['start_date'];
            }
        }

        $this->model->update($id, $data);

        // Nếu đơn đang (hoặc sau khi sửa vẫn) là approved, đồng bộ lại
        $newRequest = $this->model->find($id);
        if ($newRequest['status'] === 'approved') {
            $this->syncToAttendance($newRequest['employee_id'], $newRequest['start_date'], $newRequest['end_date'], $newRequest['leave_type']);
        }

        $db->transComplete();

        return ['status' => 'success', 'message' => 'Đã cập nhật thông tin đơn nghỉ phép thành công.'];
    }

    /**
     * Dọn dẹp ngày nghỉ khỏi bảng Attendances.
     */
    private function cleanupAttendance(int $employeeId, string $start, string $end)
    {
        $begin = new DateTime($start);
        $last = new DateTime($end);
        $last = $last->modify('+1 day');

        $interval = new \DateInterval('P1D');
        $daterange = new \DatePeriod($begin, $interval, $last);

        foreach($daterange as $date){
            $this->attendanceModel->where('employee_id', $employeeId)
                                  ->where('attendance_date', $date->format('Y-m-d'))
                                  ->where('check_in_time', null) // Chỉ xóa những bản ghi ảo (không có check-in thực tế)
                                  ->delete();
            
            // Nếu có bản ghi có check-in thực tế nhưng status là LEAVE_..., reset về REGULAR hoặc null
            $existing = $this->attendanceModel->where('employee_id', $employeeId)
                                              ->where('attendance_date', $date->format('Y-m-d'))
                                              ->first();
            if ($existing && strpos($existing['status'], 'LEAVE_') === 0) {
                $this->attendanceModel->update($existing['id'], [
                    'status' => 'REGULAR',
                    'check_in_note' => 'Reset từ việc xóa/sửa đơn nghỉ phép.'
                ]);
            }
        }
    }

    /**
     * Tính toán số ngày nghỉ (Loại trừ chủ nhật nếu cần cấu hình).
     */
    public function calculateDays(string $start, string $end): float
    {
        $d1 = new DateTime($start);
        $d2 = new DateTime($end);
        $diff = $d1->diff($d2)->days + 1;
        
        // Note: Trong tương lai có thể bổ sung logic trừ ngày nghỉ lễ, chủ nhật ở đây.
        return (float) $diff;
    }
}
