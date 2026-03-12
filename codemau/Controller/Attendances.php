<?php

namespace App\Controllers;

use App\Libraries\AttendanceType;
use App\Models\Attendance;
use function Composer\Autoload\includeFile;

class Attendances extends Home
{
    protected $attendanceModel;
    protected $controller_slug = 'timeKeeping';

    public function __construct()
    {

        parent::__construct();

        // Giả sử BaseModel có thể làm việc với các bảng khác nhau
        $this->attendanceModel = new Attendance();
        $this->attendanceModel->table = 'attendances';

    }
    private function _validateTime(string $action, string $actualTime, string $attendanceDate): array
    {
        $actualTimestamp = strtotime($actualTime);

        if ($action === 'check-in') {
            // Thời gian chuẩn để check-in
            $standardTimestamp = strtotime($attendanceDate . ' ' . AttendanceType::STANDARD_CHECK_IN_TIME);
            // Chênh lệch > 0 nghĩa là đi muộn
            $differenceInSeconds = $actualTimestamp - $standardTimestamp;

            // Nếu đi muộn trong ngưỡng cho phép (mặc định <= 10 phút)
            if ($differenceInSeconds > 0 && $differenceInSeconds <= AttendanceType::GRACE_PERIOD_SECONDS) {
                return [
                    'status' => AttendanceType::STATUS_PASS,
                    'minutes_off' => (int)round($differenceInSeconds / 60)
                ];
            }
        } else { // 'check-out'
            // Thời gian chuẩn để check-out
            $standardTimestamp = strtotime($attendanceDate . ' ' . AttendanceType::STANDARD_CHECK_OUT_TIME);
            // Chênh lệch > 0 nghĩa là về sớm
            $differenceInSeconds = $standardTimestamp - $actualTimestamp;

            // Nếu về sớm trong ngưỡng cho phép (mặc định <= 10 phút)
            if ($differenceInSeconds > 0 && $differenceInSeconds <= AttendanceType::GRACE_PERIOD_SECONDS) {
                return [
                    'status' => AttendanceType::STATUS_PASS,
                    'minutes_off' => (int)round($differenceInSeconds / 60)
                ];
            }
        }

        // Nếu đi đúng giờ/về đúng giờ hoặc muộn hơn
        if ($differenceInSeconds <= 0) {
            return ['status' => AttendanceType::STATUS_PASS, 'minutes_off' => 0];
        }

        // Các trường hợp còn lại (đi muộn / về sớm quá ngưỡng) đều là vi phạm
        return [
            'status' => AttendanceType::STATUS_ERROR,
            'minutes_off' => (int)round($differenceInSeconds / 60)
        ];
    }

    public function timeKeeping()
    {
        $this->teamplate['breadcrumb'] = view(
            'breadcrumb_view',
            array(
                'breadcrumb' => ['<li>Chấm công</li>']
            )
        );

        $this->teamplate['main'] = view(
            'custom/time_keeping',
            array(
                'seo' => $this->base_model->default_seo('Thông tin tài khoản', $this->getClassName(__CLASS__) . '/' . __FUNCTION__),
                'session_data' => $this->session_data,
                'controller_slug' => $this->controller_slug,
                'lang_key' => $this->lang_key,
                'preview_url' => $this->MY_get('preview_url', ''),
                'preview_offset_top' => $this->MY_get('preview_offset_top', ''),
            )
        );

        return view('layout_view', $this->teamplate);
    }

    /**
     * API lấy trạng thái chấm công trong ngày của user hiện tại
     */
    public function status()
    {
        if (empty($this->session_data['ID'])) {
            return $this->result_json_type(['code' => 1, 'error' => 'Yêu cầu đăng nhập.']);
        }

        // Lấy 1 bản ghi và lấy tất cả các cột (*)
        $todayRecord = $this->attendanceModel->select('*', 'attendances', [
            'user_id' => $this->session_data['ID'],
            'attendance_date' => date('Y-m-d')
        ],['limit'=>1]);

        $response = [];
        // Logic kiểm tra trạng thái đúng
        if (empty($todayRecord)) {
            // Chưa có bản ghi nào trong ngày -> Chưa check-in
            $response['status'] = 'NOT_CHECKED_IN';
        } else if (empty($todayRecord['check_out_time'])) {
            // Có bản ghi rồi, nhưng cột check_out_time rỗng -> Đã check-in, chưa check-out
            $response['status'] = 'CHECKED_IN';
            $response['check_in_time'] = date('H:i:s', strtotime($todayRecord['check_in_time']));
        } else {
            // Có bản ghi và đã có check_out_time -> Đã hoàn thành
            $response['status'] = 'CHECKED_OUT';
        }

        return $this->result_json_type(['code' => 0, ...$response]);
    }


    /**
     * API xử lý việc submit chấm công (cả vào và ra)
     */
    public function submit()
    {
        // 1. Xác thực cơ bản
        if (empty($this->session_data['ID'])) {
            return $this->result_json_type(['code' => 1, 'error' => 'Yêu cầu đăng nhập.']);
        }

        $latitude = $this->MY_post('latitude');
        $longitude = $this->MY_post('longitude');
        $note = $this->MY_post('note');
        $photo = $this->request->getFile('photo');

        if ( empty($latitude) || empty($longitude) || !$photo || !$photo->isValid()) {
            return $this->result_json_type(['code' => 2, 'error' => 'Dữ liệu không hợp lệ.']);
        }

        // 2. XỬ LÝ ẢNH MỚI: NÉN ẢNH VÀ LƯU
        $newName = $photo->getRandomName();
        $uploadPath = PUBLIC_PUBLIC_PATH . 'emp/attendance/' . date('Y/m');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Đường dẫn đầy đủ tới file sẽ được lưu
        $fullPath = $uploadPath . '/' . $newName;

        try {
            // Sử dụng Image service của CodeIgniter để xử lý
            \Config\Services::image()
                ->withFile($photo->getTempName()) // Mở file ảnh tạm đã upload
                ->resize(800, 800, true, 'auto') // Resize ảnh, giữ nguyên tỉ lệ, đảm bảo chiều rộng hoặc chiều cao không quá 800px
                ->save($fullPath, 75); // Lưu ảnh đã nén với chất lượng 75%
        } catch (\CodeIgniter\Images\ImageException $e) {
            // Trả về lỗi nếu quá trình xử lý ảnh thất bại
            return $this->result_json_type(['code' => 98, 'error' => 'Xử lý ảnh thất bại: ' . $e->getMessage()]);
        }

        // Đường dẫn tương đối để lưu vào database (giữ nguyên)
        $photoPath = 'emp/attendance/' . date('Y/m') . '/' . $newName;

        // 3. Logic Check-in hoặc Check-out
        $userId = $this->session_data['ID'];
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $todayRecord = $this->attendanceModel->select('*','attendances', ['user_id' => $userId, 'attendance_date' => $today],['limit'=>1]);

        if (empty($todayRecord)) {
            $status = AttendanceType::STATUS_ERROR;
            if ($note != '' || date('H:i:s') < AttendanceType::STANDARD_CHECK_IN_TIME) {
                $status = AttendanceType::STATUS_PASS;
            }
            $data = [
                'user_id' => $userId,
                'attendance_date' => $today,
                'check_in_time' => $now,
                'check_in_latitude' => $latitude,
                'check_in_longitude' => $longitude,
                'check_in_photo' => $photoPath,
                'check_in_note' => $note,
                'status' => $status, // Có thể thêm logic kiểm tra vị trí để đổi status
                'attendance_type' => AttendanceType::PRESENT, // Có thể thêm logic kiểm tra vị trí để đổi status
            ];
            $this->attendanceModel->insert('attendances',$data);


            // check vị trí xem hợp lệ không, neu không sẽ thông báo
            if (AttendanceType::isLocationValid($latitude,$longitude) == false) {
                return $this->result_json_type(['code' => 2, 'message' => 'Vị trí của bạn hiện không nằm trong khu vực công ty. Bạn vẫn có thể chấm công, nhưng hệ thống sẽ ghi nhận để quản lý kiểm tra.']);
            }

            return $this->result_json_type(['code' => 0, 'message' => 'Chấm công thành công!']);
        }
        else {
            if (!empty($todayRecord['check_out_time'])) {
                return $this->result_json_type(['code' => 5, 'error' => 'Bạn đã chấm công ra hôm nay rồi.']);
            }
            $status = $todayRecord['status'];
            $statusCheckout = AttendanceType::STATUS_ERROR;
           // quá thời gian thì ko vi phạm, nếu nhỏ hơn thì phải có note
            if (date('H:i:s') >= AttendanceType::STANDARD_CHECK_OUT_TIME) {
                $statusCheckout = AttendanceType::STATUS_PASS;
            }

            // tinh toán thời gian làm việc để xét duyệt làm bù.
            $checkInTimestamp = strtotime($todayRecord['check_in_time']);
            $checkOutTimestamp = strtotime($now);
            $worked = $checkOutTimestamp - $checkInTimestamp;
            // tổng làm việc là 9h30 tính 1h30 nghỉ trưa = 9*3600+30*60 = 34200s
            // logic tổng hợp trạng thái cuối cùng
            if ($status == AttendanceType::STATUS_ERROR && $todayRecord['check_in_note'] == '') {
                // nếu sáng vi phạm (đi muộn) thì kểm tra xem có làm bù đủ 9h30p khong
                if ($worked >= AttendanceType::TOTAL_WORKED) {
                    $status = AttendanceType::STATUS_PASS;
                }
            } else {
                // nếu không vi phạm thì phụ thuộc vào trạng thái lúc về
                $status = $statusCheckout;
            }
            // nếu có note biểu chiều thì pass, linh động cho cả trường hợp sáng vi phạm chiều về sớm đi công việc
            if ($note !='') {
                $status = AttendanceType::STATUS_PASS;
            }
            $data = [
                'check_out_time' => $now,
                'check_out_latitude' => $latitude,
                'check_out_longitude' => $longitude,
                'check_out_photo' => $photoPath,
                'check_out_note' => $note,
                'status'=> $status
            ];
            $this->attendanceModel->update('attendances',$data,'id', $todayRecord['id']);
            // check vị trí xem hợp lệ không, neu không sẽ thông báo
            if (AttendanceType::isLocationValid($latitude,$longitude) == false) {
                return $this->result_json_type(['code' => 2, 'message' => 'Vị trí của bạn hiện không nằm trong khu vực công ty. Bạn vẫn có thể chấm công, nhưng hệ thống sẽ ghi nhận để quản lý kiểm tra.']);
            }
            return $this->result_json_type(['code' => 0, 'message' => 'Chấm công thành công!']);
        }
        return $this->result_json_type(['code' => 99, 'error' => 'Hành động không xác định.']);
    }
    
    /** Thông tin chấm cong của user trong tháng */
    public function attendancesEmployee()
    {

        $u_id = $this->session_data['ID'];
        $selected_month = date('Y-m');

        //echo $attendance_id . '<br>' . PHP_EOL;
        $where = [
            'user_id' => $u_id,
            'attendance_date >=' => $selected_month.'-01', // ngày đầu
            'attendance_date <=' => date('Y-m-t',strtotime($selected_month.'-01')), // ngày cuoi tháng
        ];

        $filter = [
            'join' => [
                'users' => 'users.ID = attendances.user_id',
            ],
            'order_by'=> [
                'attendances.attendance_date'=>'ASC',
                'attendances.id'=>'DESC'
            ]
        ];

        //
        $data = $this->base_model->select('users.ID as uid,users.display_name, users.user_nicename, users.departments, attendances.* ', 'attendances', $where, $filter);
        // lay thong tin usser
        $user_info = $this->base_model->select('user_nicename','users',['id'=>$u_id],['limit'=>1]);
        //
        foreach ($data as $k => $v) {
            $v['check_in_change'] = 0;
            $v['check_out_change'] = 0;
            // tạo dữ liệu chenh lệch thời gian đến
            if ($v['check_in_time'] != '') {
                $v['check_in_time'] = date('H:i', strtotime($v['check_in_time']));
                $v['check_in_change'] = $this->calculateTimeDifference($v['check_in_time'], '8:00');
            }
            // tạo dữ liệu chenh lệch thời gian ve
            if ($v['check_out_time'] != '') {
                $v['check_out_time'] = date('H:i', strtotime($v['check_out_time']));
                $v['check_out_change'] = $this->calculateTimeDifference('17:30', $v['check_out_time']);
            }
            $data[$k] = $v;
        }

        $this->teamplate['breadcrumb'] = view(
            'breadcrumb_view',
            array(
                'breadcrumb' => ['<li>Lịch sử điểm danh</li>']
            )
        );

        $this->teamplate['main'] = view(
            'custom/attendances_details',
            array(
                'seo' => $this->base_model->default_seo('Bảng chấm công', $this->getClassName(__CLASS__) . '/' . __FUNCTION__),
                'session_data' => $this->session_data,
                'controller_slug' => $this->controller_slug,
                'lang_key' => $this->lang_key,
                'preview_url' => $this->MY_get('preview_url', ''),
                'preview_offset_top' => $this->MY_get('preview_offset_top', ''),
                'data' => $data,
                'selected_month' => $selected_month,
                'u_id' => $u_id,
            )
        );

        return view('layout_view', $this->teamplate);


    }
    // hàm tính thời gian chênh lệch giữa 2 khoảng thời gian có trả ra màu sắc
    protected function calculateTimeDifference($time1, $time2)
    {
        // Đảm bảo định dạng 2 chữ số cho phút
        $time1 = sprintf('%02d:%02d', ...explode(':', $time1));
        $time2 = sprintf('%02d:%02d', ...explode(':', $time2));

        // Chuyển sang timestamp
        $timestamp1 = strtotime("1970-01-01 " . $time1);
        $timestamp2 = strtotime("1970-01-01 " . $time2);

        // Tính chênh lệch phút
        $diff_seconds = $timestamp1 - $timestamp2;
        $diff_minutes = intval($diff_seconds / 60);
        if ($diff_minutes > 0) {
            $result = "<b style='color: red'>+" . $diff_minutes . "</b>"; // +31
        } elseif ($diff_minutes < 0) {
            $result = "<span style='color: green'>" . $diff_minutes . "</span>";       // -X
        } else {
            $result = "<span>0</span>";        // 0
        }
        return $result;
    }
}
