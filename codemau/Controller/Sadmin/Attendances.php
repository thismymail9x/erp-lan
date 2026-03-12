<?php
// controller quản lý nhan viên chấm công
namespace App\Controllers\Sadmin;
require_once APPPATH . 'ThirdParty/phpspreadsheet/vendor/autoload.php';

//
use App\Libraries\AttendanceType;
use App\Libraries\DeletedStatus;
use App\Libraries\IndexAPI;
use App\Libraries\UsersType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;


require_once '../vendor/autoload.php';

class Attendances extends Sadmin
{
    protected $attendance_type = '';
    protected $attendance_model = '';
    protected $attendance_name = 'Chấm công';
    public $table = 'attendances';
    // tham số dùng để thay đổi URL cho controller (nếu muốn)
    protected $controller_slug = 'attendances';
    // tham số dùng để đổi file view khi add hoặc edit attendance (nếu muốn)
    protected $add_view_path = 'attendances';
    // tham số dùng để thay đổi view của trang danh sách attendance
    protected $list_view_path = 'attendances';
    // số bản ghi trên mỗi trang
    protected $post_per_page = 50;

    public function __construct($for_extends = false)
    {
        parent::__construct();

        // kiểm tra quyền truy cập của tài khoản hiện tại
        $this->check_permision(__CLASS__);
        // hỗ trợ lấy theo params truyền vào từ url
        if ($this->attendance_type == '') {
            $this->attendance_type = $this->MY_get('attendance_type', AttendanceType::PRESENT);
        }

        // báo lỗi nếu không xác định được taxonomy
        // chỉ kiểm tra các điều kiện này nếu không được chỉ định là extends
        if ($for_extends === false) {
            if ($this->attendance_name == '') {
                die('attendance type not register in system: ' . $this->attendance_type);
            }
        }
        //
        $this->attendance_model = new \App\Models\Attendance();
    }

    public function index()
    {
        return $this->lists();
    }

    public function lists($ops = [])
    {
        $attendance_id = $this->MY_get('id');
        if ($attendance_id > 0) {
            return $this->details($attendance_id);
        }
        // URL cho các action dùng chung
        $for_action = '';
        // URL cho phân trang
        $urlPartPage = 'sadmin/' . $this->controller_slug . '?part_type=' . $this->attendance_type;
//        //
        $by_is_deleted = $this->MY_get('is_deleted', DeletedStatus::FOR_DEFAULT);
        $by_keyword = $this->MY_get('s');
//
//        //
        if ($by_is_deleted > 0) {
            $urlPartPage .= '&is_deleted=' . $by_is_deleted;
            $for_action .= '&is_deleted=' . $by_is_deleted;
        }

//        // tìm kiếm theo từ khóa nhập vào
        $where_or_like = [];
        if ($by_keyword != '') {
            $urlPartPage .= '&s=' . $by_keyword;
            $for_action .= '&s=' . $by_keyword;

            //
            $by_like = $this->base_model->_eb_non_mark_seo($by_keyword);
            // tối thiểu từ 1 ký tự trở lên mới kích hoạt tìm kiếm
            if (strlen($by_like) > 0) {
                //var_dump( strlen( $by_like ) );
                // nếu là số -> chỉ tìm theo ID
                if (is_numeric($by_like) === true) {
                    $where_or_like = [
                        'id' => $by_like * 1,
                        //'attendance_post_ID' => $by_like,
                        //'attendance_parent' => $by_like,
                        //'user_id' => $by_like,
                    ];
                }
            }
        }


        // --- BỘ LỌC MỚI ---
        $by_user = $this->MY_get('user_id');
        $date = $this->MY_get('attendance_date');
        $to_date = $this->MY_get('to_date');
        $by_status = $this->MY_get('status'); // 'Vi phạm' hoặc 'Đúng giờ'

        // các kiểu điều kiện where
        $where = [
            'users.user_status' => 0,
//            'users.member_type' => UsersType::MEMBER,
            'users.is_deleted' => 0,
            //'attendances.attendance_type' => $this->attendance_type, // Có thể bỏ dòng này để xem tất cả
        ];

        $filter = [
            'left_join' => [
                'attendances' => 'users.ID = attendances.user_id AND attendances.attendance_date = "' . date('Y-m-d') . '"',
            ],
//            'group_by'=>['users.ID']
        ];

        // Thêm điều kiện lọc vào $where và $urlPartPage
            if ($date=='') {
                $date = date('Y-m-d');
            }
        if ($date != '') {
//            $where['attendances.attendance_date'] = $date;
            $urlPartPage .= '&attendance_date=' . $date;
            $filter = [
                'left_join' => [
                    'attendances' => 'users.ID = attendances.user_id AND attendances.attendance_date = "' . $date . '"',
                ],
                'where_in'=>[
                    'users.member_type'=> [
                        UsersType::MOD,
                        UsersType::MEMBER
                    ]
                ]
//            'group_by'=>['users.ID']
            ];
        }

        if ($by_status != '') {
            // Tìm các bản ghi có ít nhất 1 trong 2 trạng thái là "Vi phạm"
            $where['(attendances.check_in_status = "' . $by_status . '" OR attendances.check_out_status = "' . $by_status . '")'] = null;
            $urlPartPage .= '&status=' . $by_status;
        }


        /*
         * phân trang
         */
        $totalThread = $this->base_model->select('COUNT(wp_users.ID) AS c', 'users', $where, $filter);
        //print_r( $totalThread );
        $totalThread = $totalThread[0]['c'];

        //
        if ($totalThread > 0) {
            $totalPage = ceil($totalThread / $this->post_per_page);
            if ($totalPage < 1) {
                $totalPage = 1;
            }
            $page_num = $this->MY_get('page_num', 1);
            //echo $totalPage . '<br>' . PHP_EOL;
            if ($page_num > $totalPage) {
                $page_num = $totalPage;
            } else if ($page_num < 1) {
                $page_num = 1;
            }
            $for_action .= $page_num > 1 ? '&page_num=' . $page_num : '';
            //echo $totalThread . '<br>' . PHP_EOL;
            //echo $totalPage . '<br>' . PHP_EOL;
            $offset = ($page_num - 1) * $this->post_per_page;

            //
            $pagination = $this->base_model->EBE_pagination($page_num, $totalPage, $urlPartPage, '&page_num=');


            // select dữ liệu từ 1 bảng bất kỳ
            $filter['offset'] = $offset;
            $filter['limit'] = $this->post_per_page;


            $data = $this->base_model->select(
                'users.ID as uid,users.display_name, users.user_nicename, users.departments, attendances.* ',
                'users',
                $where,
                $filter
            );


            //
            //$data = $this->post_model->list_meta_post( $data );
            foreach ($data as $k => $v) {
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

                // tạo dữ liệu check vị tri

                if (AttendanceType::isLocationValid($v['check_in_latitude'],$v['check_in_longitude']) == false) {
                    $v['check_in'] = 'Sai vị trí';
                } else {
                    $v['check_in'] = 'Đúng';
                }
                if (AttendanceType::isLocationValid($v['check_out_latitude'],$v['check_out_longitude']) == false) {
                    $v['check_out'] = 'Sai vị trí';
                }  else {
                    $v['check_out'] = 'Đúng';
                }

                $data[$k] = $v;
            }
            //print_r( $data );
        } else {
            $data = [];
            $pagination = '';
        }

        //
        $this->teamplate_admin['content'] = view('vadmin/' . $this->list_view_path . '/list', array(
            'list_view_path' => $this->list_view_path,
            'pagination' => $pagination,
            //'page_num' => $page_num,
            'for_action' => $for_action,
            'data' => $data,
            'attendance_type' => $this->attendance_type,
            'controller_slug' => $this->controller_slug,
            'DeletedStatus_DELETED' => DeletedStatus::DELETED,
            'by_is_deleted' => $by_is_deleted,
            'vue_data' => [
                'attendance_name' => $this->attendance_name,
                'attendance_date' => $date,
                'totalThread' => $totalThread,
                'by_keyword' => $by_keyword,
                'by_is_deleted' => $by_is_deleted,
            ],
        ));

        return view('vadmin/admin_teamplate', $this->teamplate_admin);
    }

    // hiển thị chi tiết 1 attendance/ liên hệ
    protected function details($u_id)
    {

        $dataMonth = $this->base_model->select("DATE_FORMAT(attendance_date, '%Y-%m') as month_str", 'attendances', ['user_id'=>$u_id], ['order_by'=>['month_str'=>'ASC'],'distinct'=>true]);

        $list_months = [];
        foreach ($dataMonth as $k => $v) {
            $list_months[] = $v['month_str'];
        }
        $selected_month = $this->MY_get('month');
        if ($selected_month==''){
            $selected_month = date('Y-m');
        }


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

            // tạo dữ liệu check vị tri

            if (AttendanceType::isLocationValid($v['check_in_latitude'],$v['check_in_longitude']) == false) {
                $v['check_in'] = 'Sai vị trí';
            } else {
                $v['check_in'] = 'Đúng';
            }
            if (AttendanceType::isLocationValid($v['check_out_latitude'],$v['check_out_longitude']) == false) {
                $v['check_out'] = 'Sai vị trí';
            }  else {
                $v['check_out'] = 'Đúng';
            }

            $data[$k] = $v;
        }


        //
        $this->teamplate_admin['content'] = view('vadmin/' . $this->add_view_path . '/details', array(
            'data' => $data,
            'user_info' => $user_info,
            'selected_month' => $selected_month,
            'controller_slug' => $this->controller_slug,
            'list_months' => $list_months,
            'u_id' => $u_id,
            'vue_data' => [
                'controller_slug' => $this->controller_slug,
                'attendance_name' => $this->attendance_name,
            ],
        ));
        return view('vadmin/admin_teamplate', $this->teamplate_admin);
    }
    protected function get_value()
    {
        $value = $this->MY_post('value', '');
        if (empty($value)) {
            $this->result_json_type([
                'code' => __LINE__,
                'error' => 'ids not found!',
            ]);
        }
        return $value;
    }
    // ham doi trang thai diem danh nhanh
    public function change_status_attendance_list()
    {
        return $this->before_all_change_value('status');
    }
    public function before_all_change_value($type)
    {
        $ids = $this->get_ids();
        if ($type == 'status') {
            $status = $this->get_value();
            $update = $this->base_model->update_multiple($this->table, [
                // SET
                'status' => $status
            ], [
                'status !=' => $status
            ], [
                'where_in' => array(
                    'id' => $ids
                ),
                // hiển thị mã SQL để check
                //'show_query' => 1,
                // trả về câu query để sử dụng cho mục đích khác
                //'get_query' => 1,
            ]);
        }
        $this->result_json_type([
            'code' => __LINE__,
            'result' => $update,
        ]);
    }


    // hàm tính thời gian chênh lệch giữa 2 khoảng thời gian
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

    // hàm tính, xác định xem vị trí checkin,checkout có họp lệ hay ko


    protected function get_ids()
    {
        $ids = $this->MY_post('ids', '');
        if (empty($ids)) {
            $this->result_json_type([
                'code' => __LINE__,
                'error' => 'ids not found!',
            ]);
        }

        //
        $ids = explode(',', $ids);
        if (count($ids) <= 0) {
            $this->result_json_type([
                'code' => __LINE__,
                'error' => 'ids EMPTY!',
            ]);
        }
        //print_r( $ids );

        //
        return $ids;
    }


    /**
     * Xuất dữ liệu chấm công ra file Excel chuẩn (.xlsx)
     */
    public function export_month($user_id = 0)
    {
        // Xóa buffer để tránh lỗi file
        if (ob_get_level()) {
            ob_end_clean();
        }

        $user_id = (int)$user_id;
        $month = $this->MY_get('month', date('Y-m'));

        // 1. Lấy thông tin nhân viên
        $user = $this->base_model->select('display_name, user_nicename, user_email', 'users', ['id' => $user_id], ['limit' => 1]);

        if (empty($user)) {
            die('Lỗi: Không tìm thấy thông tin nhân viên.');
        }

        // 2. Lấy dữ liệu
        $where = [
            'user_id' => $user_id,
            'attendance_date >=' => $month . '-01',
            'attendance_date <=' => date('Y-m-t', strtotime($month . '-01')),
        ];

        $data = $this->base_model->select('*', 'attendances', $where, [
            'order_by' => ['attendance_date' => 'ASC']
        ]);

        // 3. Khởi tạo Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // --- CẤU HÌNH STYLE ---
        // Style tiêu đề bảng
        $styleHeader = [
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        // Style viền cơ bản
        $styleBorder = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        // Style chữ đỏ (Vi phạm)
        $styleRedText = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']],
        ];

        // --- VẼ TIÊU ĐỀ LỚN ---
        $sheet->setCellValue('A1', 'BẢNG CHI TIẾT CHẤM CÔNG THÁNG ' . date('m/Y', strtotime($month . '-01')));
        $sheet->mergeCells('A1:F1'); // Gộp ô từ A đến F
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Nhân viên: ' . $user['display_name'] . ' (' . $user['user_email'] . ')');
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // --- VẼ HEADER CỘT (Dòng 4) ---
        $headers = ['Ngày', 'Giờ Vào', 'Giờ Ra', 'Trạng thái', 'Ghi chú', 'Tổng hợp'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }
        $sheet->getStyle('A4:F4')->applyFromArray($styleHeader);

        // --- ĐỔ DỮ LIỆU ---
        $row = 5;
        if (empty($data)) {
            $sheet->setCellValue('A5', 'Không có dữ liệu chấm công.');
            $sheet->mergeCells('A5:F5');
        } else {
            foreach ($data as $item) {
                $date_display = date('d/m/Y', strtotime($item['attendance_date']));

                // Logic tổng hợp
                $summary = 'Thiếu giờ';
                $rowColor = null; // Màu nền dòng

                if ($item['status'] == AttendanceType::STATUS_ERROR) {
                    $summary = 'Vi phạm';
                    $rowColor = 'FFF5F5'; // Đỏ nhạt
                } else if (!empty($item['check_in_time']) && !empty($item['check_out_time'])) {
                    $summary = 'Đủ công';
                } else {
                    $rowColor = 'FFFFEB'; // Vàng nhạt
                }

                // Gộp ghi chú
                $note_display = '';
                if (!empty($item['check_in_note'])) $note_display .= "Vào: " . $item['check_in_note'];
                if (!empty($item['check_out_note'])) {
                    if ($note_display != '') $note_display .= " | ";
                    $note_display .= "Ra: " . $item['check_out_note'];
                }

                // Ghi dữ liệu vào ô
                $sheet->setCellValue('A' . $row, $date_display);
                $sheet->setCellValue('B' . $row, date('H:i:s', strtotime($item['check_in_time'])));
                $sheet->setCellValue('C' . $row, date("H:i:s",strtotime($item['check_out_time'])));
                $sheet->setCellValue('D' . $row, $item['status']);
                $sheet->setCellValue('E' . $row, $note_display);
                $sheet->setCellValue('F' . $row, $summary);

                // Kẻ viền cho dòng hiện tại
                $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($styleBorder);

                // Căn giữa các cột ngày giờ
                $sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Tô màu chữ đỏ nếu trạng thái là Vi phạm
                if ($item['status'] == AttendanceType::STATUS_ERROR) {
                    $sheet->getStyle('D' . $row)->applyFromArray($styleRedText);
                }

                // Tô màu nền nếu có
                if ($rowColor) {
                    $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($rowColor);
                }

                $row++;
            }
        }

        // Tự động chỉnh độ rộng cột
        foreach (range('A', 'F') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // 4. Xuất file
        $name_slug = !empty($user['user_nicename']) ? $user['user_nicename'] : $this->base_model->_eb_non_mark_seo($user['display_name']);
        $filename = "ChamCong_" . $name_slug . "_" . date('m_Y',strtotime($month)) . ".xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

}



