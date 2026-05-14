<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class FeatureGuidelines extends BaseConfig
{
    /**
     * Danh sách các tính năng mới và hướng dẫn sử dụng.
     * key: segment của URL hoặc title của menu item để mapping.
     */
    public array $items = [
        'dashboard' => [
            'launch_date' => '2026-01-01',
            'guidance'    => [
                'title'   => 'Tổng quan Dashboard',
                'content' => "• Dashboard cung cấp cái nhìn tổng thể về các chỉ số KPI, vụ việc mới và lịch trình làm việc.\n• Bạn có thể theo dõi nhanh số lượng vụ việc đang xử lý, doanh thu và các thông báo quan trọng tại đây."
            ]
        ],
        'attendance' => [
            'launch_date' => '2026-05-01',
            'guidance'    => [
                'title'   => 'Hướng dẫn Chấm công',
                'content' => "• Xem lịch sử chấm công chi tiết theo từng ngày trong tháng.\n• Theo dõi giờ vào (Check-in) và giờ ra (Check-out) thực tế từ hệ thống.\n• Kiểm tra tổng số ngày công, số lần đi muộn hoặc về sớm để đảm bảo quyền lợi cá nhân."
            ]
        ],
        'leave-requests' => [
            'launch_date' => '2026-05-01',
            'guidance'    => [
                'title'   => 'Hướng dẫn Đăng ký Nghỉ phép',
                'content' => "• Nhấn 'Tạo đơn mới' để gửi yêu cầu nghỉ phép tới quản lý.\n• Chọn loại hình nghỉ (Nghỉ phép năm, nghỉ việc riêng, nghỉ chế độ...).\n• Ghi chi tiết lý do nghỉ để được duyệt\n• Theo dõi trạng thái đơn (Chờ duyệt, Đã duyệt, Từ chối) ngay trên danh sách."
            ]
        ],
        'payroll' => [
            'launch_date' => '2026-05-05',
            'guidance'    => [
                'title'   => 'Hướng dẫn Tra cứu Bảng lương',
                'content' => "• Bảng lương hiển thị chi tiết các khoản thu nhập: Lương cứng, phụ cấp, thưởng và các khoản khấu trừ bảo hiểm, thuế.\n• Bạn có thể xem lịch sử lương của các tháng trước đó bằng cách chọn thời gian.\n• Nếu có thắc mắc về số liệu, vui lòng liên hệ bộ phận Kế toán/Hành chính.\n• Thêm chi phí phát sinh trước ngày 05 hàng tháng."
            ]
        ],
        'cases' => [
            'launch_date' => '2026-01-01',
            'guidance'    => [
                'title'   => 'Hướng dẫn Quản lý Vụ việc',
                'content' => "• Theo dõi danh sách các vụ việc pháp lý bạn đang tham gia.\n• Sau khi được phân vụ việc thì chuyển trạng thái sang 'Đang xử lý' ở nút cập nhật trong chi tiết vụ việc.\n• Click vào từng vụ việc để xem chi tiết tiến độ, các bước thực hiện và tài liệu đính kèm.\n• Cập nhật kết quả công việc theo đúng quy trình đã đề ra."
            ]
        ],
        'finance' => [
            'launch_date' => '2026-01-01',
            'guidance'    => [
                'title'   => 'Hướng dẫn Tài chính Vụ việc',
                'content' => "• Quản lý các khoản thu/chi, công nợ và tiến độ thanh toán của từng vụ việc.\n• Lọc Tháng/Năm (Tổng HĐ): Áp dụng thời gian thanh toán đợt 1 (nếu trống lấy ngày tạo vụ việc).\n• Lọc Tháng/Năm (Đã thu): Lấy mốc thời gian của đợt cuối cùng đã thu (nếu trống lấy ngày tạo vụ việc).\n• Lọc Tháng/Năm (Chưa thu): Lấy mốc thời gian của đợt cuối cùng chưa thu (nếu trống lấy ngày tạo vụ việc)."
            ]
        ],
        'customers' => [
            'launch_date' => '2026-01-01',
            'guidance'    => [
                'title'   => 'Hướng dẫn Quản lý Khách hàng',
                'content' => "• Lưu trữ thông tin cá nhân/tổ chức khách hàng, lịch sử giao dịch và liên hệ.\n• Phân loại khách hàng theo nhóm để có chiến lược chăm sóc phù hợp.\n• Tra cứu nhanh hồ sơ khách hàng khi cần liên hệ hoặc xử lý vụ việc."
            ]
        ],
        'knowledge' => [
            'launch_date' => '2026-05-13',
            'guidance'    => [
                'title'   => 'Hướng dẫn Cẩm nang nội bộ',
                'content' => "• Nơi lưu trữ các quy định, quy trình làm việc và kiến thức chuyên môn dùng chung cho toàn công ty.\n• Bạn có thể tìm kiếm các bài viết hướng dẫn, biểu mẫu hoặc tài liệu đào tạo tại đây.\n• Hãy thường xuyên cập nhật kiến thức mới để nâng cao hiệu quả công việc."
            ]
        ],
        'contacts' => [
            'launch_date' => '2026-05-13',
            'guidance'    => [
                'title'   => 'Hướng dẫn Danh bạ liên hệ',
                'content' => "• Tra cứu nhanh thông tin liên lạc của các cơ quan, đơn vị bên ngoài (Tòa án, Công an, Thuế...).\n• Tìm kiếm theo tên đơn vị, số điện thoại hoặc địa bàn quản lý.\n• Ghi chú lại những thông tin quan trọng để sử dụng cho các lần liên hệ sau."
            ]
        ],
        'tags' => [
            'launch_date' => '2026-01-01',
            'guidance'    => [
                'title'   => 'Hướng dẫn Quản lý Nhãn dán',
                'content' => "• Nhãn dán giúp phân loại và đánh dấu các vụ việc, tài liệu một cách linh hoạt.\n• Bạn có thể sử dụng nhãn để nhóm các hồ sơ có cùng tính chất hoặc độ ưu tiên.\n• Quản lý danh sách nhãn dán để đảm bảo hệ thống phân loại luôn nhất quán."
            ]
        ],
        'documents' => [
            'launch_date' => '2026-01-01',
            'guidance'    => [
                'title'   => 'Hướng dẫn Quản lý Tài liệu',
                'content' => "• Kho lưu trữ dữ liệu số của công ty, cho phép quản lý tệp tin theo cây thư mục.\n• Tải lên, chia sẻ và phân quyền truy cập tài liệu cho từng nhân sự hoặc phòng ban.\n• Sử dụng tính năng tìm kiếm để truy xuất nhanh văn bản cần thiết."
            ]
        ],
        'notifications' => [
            'launch_date' => '2026-01-01',
            'guidance'    => [
                'title'   => 'Hướng dẫn Trao đổi nội bộ',
                'content' => "• Hệ thống nhận và gửi thông báo, tin nhắn nội bộ giữa các thành viên.\n• Cập nhật các yêu cầu phê duyệt, nhắc nhở lịch hẹn hoặc các thay đổi trong vụ việc.\n• Đảm bảo thông tin luôn được truyền đạt nhanh chóng và chính xác."
            ]
        ],
        'employees' => [
            'launch_date' => '2026-01-01',
            'guidance'    => [
                'title'   => 'Hướng dẫn Hồ sơ cá nhân',
                'content' => "• Quản lý thông tin cá nhân và quá trình công tác của bạn.\n• Cập nhật các thay đổi về thông tin liên lạc hoặc người thân khi cần thiết.\n• Theo dõi các quyết định khen thưởng, kỷ luật hoặc bổ nhiệm cá nhân."
            ]
        ],
    ];

    /**
     * Số ngày hiển thị badge "New" kể từ ngày ra mắt.
     */
    public int $newBadgeDurationDays = 14;
}
