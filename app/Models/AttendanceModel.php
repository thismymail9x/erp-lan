<?php

namespace App\Models;

class AttendanceModel extends BaseModel
{
    protected $table            = 'attendances';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'employee_id', 'attendance_date', 
        'check_in_time', 'check_in_latitude', 'check_in_longitude', 'check_in_photo', 'check_in_note',
        'check_out_time', 'check_out_latitude', 'check_out_longitude', 'check_out_photo', 'check_out_note',
        'worked_hours', 'status', 'is_valid_location'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = '';

    /**
     * Lấy trạng thái chấm công của nhân viên trong ngày
     */
    public function getTodayAttendance(int $employeeId)
    {
        $today = \CodeIgniter\I18n\Time::now('Asia/Ho_Chi_Minh')->format('Y-m-d');
        return $this->where([
            'employee_id'     => $employeeId,
            'attendance_date' => $today
        ])->first();
    }
}
