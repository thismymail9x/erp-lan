<?php

namespace App\Services;

use App\Models\AttendanceModel;
use App\Models\SystemSettingModel;
use Config\AppConstants;
use CodeIgniter\I18n\Time;

/**
 * AttendanceService
 * 
 * Lớp Dịch vụ nòng cốt quản lý toàn bộ quy trình Chấm công.
 * Hỗ trợ các công nghệ:
 * 1. Chế độ mạng LAN (IP-based whitelist).
 * 2. Chế độ Camera & GPS (Location-based verification).
 * 3. Chế độ Authorized PC (Token-based verification) cho IP động.
 * 4. Tự động tính toán công thợ (Worked hours calculations).
 */
class AttendanceService extends BaseService
{
    protected $attendanceModel;
    protected $systemSettingModel;
    protected $logService;

    public function __construct()
    {
        parent::__construct();
        // Khởi tạo các model cần thiết
        $this->attendanceModel = new AttendanceModel();
        $this->systemSettingModel = new SystemSettingModel();
        $this->logService = new SystemLogService();
    }

    /**
     * Kiểm tra truy cập có được coi là "Nội bộ văn phòng" hay không.
     * 
     * @param string $ip Địa chỉ IP của Client.
     * @param string|null $token Mã xác thực máy tính văn phòng (nếu có).
     * @return bool
     */
    public function isInternalAccess(string $ip, ?string $token = null): bool
    {
        // CHIẾN LƯỢC 1: Kiểm tra IP (Dùng cho các văn phòng có đường truyền IP tĩnh)
        foreach (AppConstants::ATT_LAN_IPS as $allowedIp) {
            if (strpos($ip, $allowedIp) === 0) {
                return true;
            }
        }

        // CHIẾN LƯỢC 2: Kiểm tra Security Token (Dành cho PC cố định nhưng IP bị thay đổi liên tục)
        if (!empty($token)) {
            $officeToken = $this->systemSettingModel->where('key', 'office_security_token')->first();
            if ($officeToken && $token === $officeToken['value']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kiểm tra IP có thuộc dải IP LAN nội bộ hay không.
     * Alias cho isInternalAccess để tương thích với Controller.
     */
    public function isLanIp(string $ip): bool
    {
        return $this->isInternalAccess($ip);
    }

    /**
     * Lấy trạng thái điểm danh trong ngày của một nhân viên cụ thể.
     */
    public function getTodayStatus(int $employeeId)
    {
        // Truy vấn bản ghi chấm công của ngày hiện tại
        $record = $this->attendanceModel->getTodayAttendance($employeeId);
        
        if (!$record) {
            return ['status' => 'NOT_CHECKED_IN']; // Chưa có dữ liệu gì
        }

        if (!$record['check_out_time']) {
            return [
                'status' => 'CHECKED_IN', // Đã vào nhưng chưa ra
                'check_in_time' => date('H:i:s', strtotime($record['check_in_time']))
            ];
        }

        return [
            'status' => 'CHECKED_OUT', // Đã hoàn thành ca làm việc
            'check_in_time' => date('H:i:s', strtotime($record['check_in_time'])),
            'check_out_time' => date('H:i:s', strtotime($record['check_out_time']))
        ];
    }

    /**
     * Logic trung tâm xử lý việc Chấm công (Vào/Ra).
     * Tự động quyết định xem đây là Check-in hay Check-out dựa trên dữ liệu hiện có.
     * 
     * @param int $employeeId
     * @param array $data Dữ liệu GPS/Token/Notes
     * @param mixed $photo File ảnh chụp (nếu có)
     */
    public function submit(int $employeeId, array $data, $photo = null)
    {
        // 1. Khởi tạo mốc thời gian chuẩn (Asia/Ho_Chi_Minh)
        $nowTime = Time::now('Asia/Ho_Chi_Minh');
        $today = $nowTime->format('Y-m-d');
        $now = $nowTime->toDateTimeString();
        
        $clientIp = $data['clientIp'] ?? '';
        $clientToken = $data['officeToken'] ?? null;
        
        // Kiểm tra xem nhân viên đã có bản ghi nào trong ngày hôm nay chưa
        $record = $this->attendanceModel->getTodayAttendance($employeeId);
        
        // 2. Xác định độ tin cậy của vị trí (Internal vs External)
        $isInternal = $this->isInternalAccess($clientIp, $clientToken);

        // 3. Xử lý lưu trữ ảnh xác thực:
        // Nếu không thuộc mạng nội bộ -> Bắt buộc phải chụp ảnh khuôn mặt tại hiện trường.
        $photoPath = null;
        if (!$isInternal) {
            $photoPath = $this->savePhoto($photo, $employeeId);
            if (!$photoPath) {
                return $this->fail('Hệ thống yêu cầu ảnh chụp thực tế khi điểm danh ngoài văn phòng.');
            }
        }

        // 4. Kiểm tra tọa độ GPS so với tâm văn phòng
        // Nội bộ văn phòng mặc định là hợp lệ, bên ngoài phải nằm trong bán kính quy định (VD: 200m).
        $isValidLocation = $isInternal ? true : $this->isLocationValid($data['latitude'] ?? null, $data['longitude'] ?? null);

        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;

        // --- CHIỀU VÀO (CHECK-IN) ---
        if (!$record) {
            $status = AppConstants::ATT_STATUS_REGULAR; // Mặc định là Đúng giờ
            
            // Nếu đến muộn quá 8h30 thì tính là Đi muộn luôn
            if ($nowTime->format('H:i:s') > AppConstants::ATT_LATE_THRESHOLD && empty($data['note'])) {
                $status = AppConstants::ATT_STATUS_LATE;
            }

            // Nếu GPS sai lệch quá xa
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
                $this->logService->log('CHECK_IN', 'Attendance', (int)$this->attendanceModel->getInsertID(), ['status' => $status, 'is_internal' => $isInternal]);
                return $this->success(null, 'Ghi nhận giờ VÀO thành công' . ($isInternal ? ' (Xác thực tại VP)' : ''));
            }
        } 
        // --- CHIỀU RA (CHECK-OUT) ---
        else {
            if ($record['check_out_time']) {
                return $this->fail('Bạn đã hoàn tất phiếu điểm danh RA cho ngày hôm nay.');
            }

            // 5. Tính toán tổng thời gian làm việc (Đã trừ giờ nghỉ)
            $workedHours = $this->calculateWorkedHours($record['check_in_time'], $now);

            // 6. LOGIC "HOÀN THÀNH BÙ" (FLEX-TIME):
            // Lấy các mốc giờ cấu hình
            $checkInTimeStr = date('H:i:s', strtotime($record['check_in_time']));
            $standardInTime = AppConstants::ATT_STANDARD_IN;
            $standardOutTime = AppConstants::ATT_STANDARD_OUT;

            // Tính toán thời gian đi muộn so với 8h00 để cộng dồn vào giờ về
            $lateSeconds = max(0, strtotime($checkInTimeStr) - strtotime($standardInTime));
            // Giờ bắt buộc phải ở lại để đủ công (Ví dụ: 8h15 đến -> 17h45 mới được về)
            $requiredCheckOutStr = date('H:i:s', strtotime($standardOutTime) + $lateSeconds);

            // Giữ nguyên trạng thái từ bản Check-in (nếu là LATE thì vẫn là LATE)
            $status = $record['status'];
            
            // Kiểm tra vi phạm giờ về:
            // Nếu chưa thỏa mãn giờ về tối thiểu đã bù thời gian
            if ($nowTime->format('H:i:s') < $requiredCheckOutStr && empty($data['note'])) {
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
                // Hợp nhất kết quả GPS (Chỉ hợp lệ nếu cả In và Out đều đúng vị trí)
                'is_valid_location'   => ($isValidLocation && $record['is_valid_location']) ? 1 : 0
            ];

            if ($this->attendanceModel->update($record['id'], $updateData)) {
                $this->logService->log('CHECK_OUT', 'Attendance', (int)$record['id'], ['worked_hours' => $workedHours]);
                return $this->success(null, 'Ghi nhận giờ RA thành công. Tổng giờ làm: ' . $workedHours . 'h');
            }
        }

        return $this->fail('Lớp bảo mật phát hiện lỗi khi đồng bộ dữ liệu chấm công. Vui lòng thử lại.');
    }

    /**
     * Xử lý tối ưu hóa và lưu trữ tệp ảnh chấm công.
     */
    private function savePhoto($photo, $employeeId)
    {
        if (!$photo || !$photo->isValid()) return null;

        // Tạo tên tệp ngẫu nhiên và cấu trúc thư mục theo Tháng/Năm để dễ quản lý
        $newName = $photo->getRandomName();
        $folder = 'uploads/attendance/' . date('Y/m');
        $uploadPath = FCPATH . $folder;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $fullName = $uploadPath . '/' . $newName;

        try {
            // Nén ảnh xuống chất lượng 60% và resize về 600px để tiết kiệm dung lượng ổ cứng
            \Config\Services::image()
                ->withFile($photo->getTempName())
                ->resize(600, 600, true, 'auto')
                ->save($fullName, 60);

            return $folder . '/' . $newName;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Xác thực vị trí dựa trên công thức Haversine (Tính khoảng cách giữa 2 điểm tọa độ).
     */
    private function isLocationValid($userLat, $userLng)
    {
        if (!$userLat || !$userLng) return false;

        $earthRadius = 6371; // Bán kính trái đất (km)
        $dLat = deg2rad($userLat - AppConstants::ATT_OFFICE_LAT);
        $dLon = deg2rad($userLng - AppConstants::ATT_OFFICE_LNG);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad(AppConstants::ATT_OFFICE_LAT)) * cos(deg2rad($userLat)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        // Kiểm tra xem khoảng cách có nằm trong vòng tròn cho phép không (Mặc định 0.2km)
        return $distance <= AppConstants::ATT_RADIUS_KM;
    }

    /**
     * Lấy danh sách lịch sử chấm công theo tháng của nhân viên.
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

    /**
     * Tính toán tổng thời gian làm việc (Đơn vị: Giờ) thực tế trừ thời gian nghỉ.
     * Quy định nghỉ cố định từ AppConstants::ATT_BREAK_START đến AppConstants::ATT_BREAK_END.
     */
    private function calculateWorkedHours($checkIn, $checkOut)
    {
        $start = is_string($checkIn) ? strtotime($checkIn) : $checkIn;
        $end = is_string($checkOut) ? strtotime($checkOut) : $checkOut;
        
        if ($start >= $end) return 0;
        
        $date = date('Y-m-d', $start);
        $breakStart = strtotime($date . ' ' . AppConstants::ATT_BREAK_START);
        $breakEnd = strtotime($date . ' ' . AppConstants::ATT_BREAK_END);
        
        $totalSeconds = $end - $start;
        
        // Tính toán khoảng trùng (overlap) giữa ca làm và giờ nghỉ
        $overlapStart = max($start, $breakStart);
        $overlapEnd = min($end, $breakEnd);
        
        if ($overlapStart < $overlapEnd) {
            $totalSeconds -= ($overlapEnd - $overlapStart);
        }
        
        return round($totalSeconds / 3600, 2);
    }

    /**
     * Quản lý Security Token văn phòng.
     * Tự động tạo mã mới nếu hệ thống chưa có. Mã này dùng để định danh các máy tính "Tin cậy".
     */
    public function getOfficeToken()
    {
        $token = $this->systemSettingModel->where('key', 'office_security_token')->first();
        if (!$token) {
            // Tạo mã định danh duy nhất (Unique Office ID)
            $newToken = 'OFFICE_' . bin2hex(random_bytes(10));
            $this->systemSettingModel->insert([
                'key' => 'office_security_token',
                'value' => $newToken
            ]);
            return $newToken;
        }
        return $token['value'];
    }
}
