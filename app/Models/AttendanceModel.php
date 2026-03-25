<?php

namespace App\Models;

/**
 * AttendanceModel
 * 
 * Lớp trừu tượng hóa dữ liệu cho bảng Nhật ký Chấm công (attendances).
 * Chịu trách nhiệm lưu vết thời gian ra/vào, tọa độ GPS, ảnh thực tế và trạng thái hợp lệ của nhân viên.
 */
class AttendanceModel extends BaseModel
{
    // Tên bảng và cấu hình định danh cơ bản
    protected $table            = 'attendances';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false; // Không dùng xóa mềm vì dữ liệu chấm công cần sự xác thực tuyệt đối
    protected $protectFields    = true;

    // Danh sách các cột được phép tác động trực tiếp (Safe fields)
    protected $allowedFields    = [
        'employee_id',          // ID nhân viên thực hiện
        'attendance_date',      // Ngày ghi nhận (YYYY-MM-DD)
        'check_in_time',        // Thời điểm vào
        'check_in_latitude',    // Vĩ độ GPS lúc vào
        'check_in_longitude',   // Kinh độ GPS lúc vào
        'check_in_photo',       // Đường dẫn ảnh chụp khuôn mặt (AI/Visual check)
        'check_in_note',        // Ghi chú của nhân viên khi vào
        'check_out_time',       // Thời điểm ra
        'check_out_latitude',   // Vĩ độ GPS lúc ra
        'check_out_longitude',  // Kinh độ GPS lúc ra
        'check_out_photo',      // Đường dẫn ảnh chụp khuôn mặt lúc ra
        'check_out_note',       // Ghi chú lúc ra (Lý do về sớm/muộn)
        'worked_hours',         // Tổng thời gian làm việc (Số thực)
        'status',               // Trạng thái: on_time (Đúng giờ), late (Muộn), early (Về sớm),...
        'is_valid_location'     // Cờ hiệu xác nhận có thuộc Văn phòng hay không (1/0)
    ];

    // Tự động hóa việc ghi nhận thời điểm tạo/cập nhật bản ghi
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = '';

    /**
     * Tra cứu trạng thái Chấm công hiện tại của nhân viên trong ngày hôm nay.
     * 
     * @param int $employeeId ID nhân viên.
     * @return array|null Trả về dòng dữ liệu nếu đã có Check-in, ngược lại trả về null.
     */
    public function getTodayAttendance(int $employeeId)
    {
        // Sử dụng múi giờ Việt Nam (Asia/Ho_Chi_Minh) để đồng nhất dữ liệu
        $today = \CodeIgniter\I18n\Time::now('Asia/Ho_Chi_Minh')->format('Y-m-d');
        
        // Tìm bản ghi duy nhất của người đó trong ngày hôm nay
        return $this->where([
            'employee_id'     => $employeeId,
            'attendance_date' => $today
        ])->first();
    }
}
