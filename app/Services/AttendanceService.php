<?php

namespace App\Services;

use App\Models\AttendanceModel;
use Config\AppConstants;
use CodeIgniter\I18n\Time;

/**
 * AttendanceService
 * 
 * Quản lý nghiệp vụ chấm công: Check-in, Check-out, tính toán thời gian và vị trí.
 */
class AttendanceService extends BaseService
{
    protected $attendanceModel;
    protected $logService;

    public function __construct()
    {
        parent::__construct();
        $this->attendanceModel = new AttendanceModel();
        $this->logService = new SystemLogService();
    }

    /**
     * Kiểm tra IP đầu vào có thuộc dải mạng LAN văn phòng không
     */
    public function isLanIp(string $ip): bool
    {
        foreach (AppConstants::ATT_LAN_IPS as $allowedIp) {
            if (strpos($ip, $allowedIp) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Lấy trạng thái chấm công hôm nay của nhân viên
     */
    public function getTodayStatus(int $employeeId)
    {
        $record = $this->attendanceModel->getTodayAttendance($employeeId);
        
        if (!$record) {
            return ['status' => 'NOT_CHECKED_IN'];
        }

        if (!$record['check_out_time']) {
            return [
                'status' => 'CHECKED_IN',
                'check_in_time' => date('H:i:s', strtotime($record['check_in_time']))
            ];
        }

        return [
            'status' => 'CHECKED_OUT',
            'check_in_time' => date('H:i:s', strtotime($record['check_in_time'])),
            'check_out_time' => date('H:i:s', strtotime($record['check_out_time']))
        ];
    }

    /**
     * Thực hiện chấm công (vào/ra)
     */
    public function submit(int $employeeId, array $data, $photo = null)
    {
        $nowTime = Time::now('Asia/Ho_Chi_Minh');
        $today = $nowTime->format('Y-m-d');
        $now = $nowTime->toDateTimeString();
        
        $record = $this->attendanceModel->getTodayAttendance($employeeId);
        
        $isLan = $data['isLan'] ?? false;

        // Xử lý lưu ảnh nếu không phải LAN
        $photoPath = null;
        if (!$isLan) {
            $photoPath = $this->savePhoto($photo, $employeeId);
            if (!$photoPath) {
                return $this->fail('Không thể xử lý ảnh chụp.');
            }
        }

        // Kiểm tra vị trí
        $isValidLocation = $isLan ? true : $this->isLocationValid($data['latitude'] ?? null, $data['longitude'] ?? null);

        // Đảm bảo có giá trị tọa độ (null nếu là LAN)
        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;

        if (!$record) {
            // Trường hợp Check-in
            $status = AppConstants::ATT_STATUS_REGULAR;
            if ($nowTime->format('H:i:s') > AppConstants::ATT_STANDARD_IN && empty($data['note'])) {
                $status = AppConstants::ATT_STATUS_LATE;
            }

            if (!$isValidLocation) {
                $status = AppConstants::ATT_STATUS_INVALID_LOC;
            }

            $insertData = [
                'employee_id'        => $employeeId,
                'attendance_date'    => $today,
                'check_in_time'      => $now,
                'check_in_latitude'  => $lat,
                'check_in_longitude' => $lng,
                'check_in_photo'     => $photoPath,
                'check_in_note'      => $data['note'] ?? null,
                'status'             => $status,
                'is_valid_location'  => $isValidLocation ? 1 : 0
            ];

            if ($this->attendanceModel->insert($insertData)) {
                try {
                    $this->logService->log('CHECK_IN', 'Attendance', $this->attendanceModel->getInsertID(), ['status' => $status]);
                } catch (\Exception $e) {
                    // Log fail should not break the response
                    $this->logError('Log CHECK_IN fail: ' . $e->getMessage());
                }
                return $this->success(null, 'Điểm danh VÀO thành công.');
            }
        } else {
            // Trường hợp Check-out
            if ($record['check_out_time']) {
                return $this->fail('Bạn đã điểm danh RA hôm nay rồi.');
            }

            $checkInTime = strtotime($record['check_in_time']);
            $checkOutTime = strtotime($now);
            $workedSeconds = $checkOutTime - $checkInTime;
            $workedHours = round($workedSeconds / 3600, 2);

            $status = $record['status'];
            if ($nowTime->format('H:i:s') < AppConstants::ATT_STANDARD_OUT && empty($data['note'])) {
                $status = AppConstants::ATT_STATUS_EARLY_LEAVE;
            }

            $updateData = [
                'check_out_time'      => $now,
                'check_out_latitude'  => $lat,
                'check_out_longitude' => $lng,
                'check_out_photo'     => $photoPath,
                'check_out_note'      => $data['note'] ?? null,
                'worked_hours'        => $workedHours,
                'status'              => $status,
                'is_valid_location'   => ($isValidLocation && $record['is_valid_location']) ? 1 : 0
            ];

            if ($this->attendanceModel->update($record['id'], $updateData)) {
                try {
                    $this->logService->log('CHECK_OUT', 'Attendance', $record['id'], ['worked_hours' => $workedHours]);
                } catch (\Exception $e) {
                    // Log fail should not break the response
                    $this->logError('Log CHECK_OUT fail: ' . $e->getMessage());
                }
                return $this->success(null, 'Điểm danh RA thành công.');
            }
        }

        return $this->fail('Lỗi hệ thống khi lưu chấm công.');
    }

    /**
     * Lưu và nén ảnh
     */
    private function savePhoto($photo, $employeeId)
    {
        if (!$photo || !$photo->isValid()) return null;

        $newName = $photo->getRandomName();
        $folder = 'uploads/attendance/' . date('Y/m');
        $uploadPath = FCPATH . $folder;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $fullName = $uploadPath . '/' . $newName;

        try {
            \Config\Services::image()
                ->withFile($photo->getTempName())
                ->resize(600, 600, true, 'auto') // Giảm xuống 600 để tiết kiệm dung lượng
                ->save($fullName, 60);

            return $folder . '/' . $newName;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Kiểm tra vị trí theo công thức Haversine
     */
    private function isLocationValid($userLat, $userLng)
    {
        if (!$userLat || !$userLng) return false;

        $earthRadius = 6371; // km
        $dLat = deg2rad($userLat - AppConstants::ATT_OFFICE_LAT);
        $dLon = deg2rad($userLng - AppConstants::ATT_OFFICE_LNG);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad(AppConstants::ATT_OFFICE_LAT)) * cos(deg2rad($userLat)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance <= AppConstants::ATT_RADIUS_KM;
    }

    /**
     * Lấy lịch sử chấm công của nhân viên
     */
    public function getHistory(int $employeeId, ?string $month = null)
    {
        $month = $month ?? date('Y-m');
        
        return $this->attendanceModel->where('employee_id', $employeeId)
                                     ->where('attendance_date >=', $month . '-01')
                                     ->where('attendance_date <=', date('Y-m-t', strtotime($month . '-01')))
                                     ->orderBy('attendance_date', 'DESC')
                                     ->findAll();
    }
}
