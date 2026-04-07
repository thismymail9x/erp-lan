<?php

namespace App\Services;

use App\Models\LeaveRequestModel;
use App\Models\AttendanceModel;
use DateTime;

class LeaveRequestService
{
    protected $model;
    protected $attendanceModel;

    public function __construct()
    {
        $this->model = new LeaveRequestModel();
        $this->attendanceModel = new AttendanceModel();
    }

    /**
     * Tạo đơn xin nghỉ phép mới.
     * Tự động tính toán số ngày nghỉ dựa trên ranh giới bắt đầu/kết thúc.
     */
    public function create(array $data)
    {
        $data['total_days'] = $this->calculateDays($data['start_date'], $data['end_date']);
        $data['status'] = 'pending';

        if ($this->model->save($data)) {
            return [
                'status' => 'success',
                'message' => 'Đơn xin nghỉ phép đã được gửi đi và đang chờ phê duyệt.',
                'id' => $this->model->getInsertID()
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
        if (!$request) return ['status' => 'error', 'message' => 'Không tìm thấy đơn yêu cầu.'];
        if ($request['status'] !== 'pending') return ['status' => 'error', 'message' => 'Đơn này đã được xử lý rồi.'];

        $updateData = [
            'status'        => 'approved',
            'approver_id'   => $approverId,
            'approval_note' => $note,
            'approved_at'   => date('Y-m-d H:i:s')
        ];

        if ($this->model->update($id, $updateData)) {
            // ĐỒNG BỘ CHẤM CÔNG (Attendance Sync Logic):
            // Lặp qua từng ngày từ start_date tới end_date và ghi nhận bản ghi Chấm công 'Nghỉ'.
            $this->syncToAttendance($request['employee_id'], $request['start_date'], $request['end_date'], $request['leave_type']);

            return ['status' => 'success', 'message' => 'Đã phê duyệt đơn nghỉ phép. Dữ liệu ngày nghỉ đã được đồng bộ vào hệ thống chấm công.'];
        }

        return ['status' => 'error', 'message' => 'Phát sinh lỗi khi cập nhật trạng thái đơn.'];
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
