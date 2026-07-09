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
            'launch_date' => '2026-06-01',
            'guidance'    => [
                'title'   => 'Hướng dẫn Bảng lương nâng cao',
                'content' => "• Bảng lương hiển thị chi tiết các khoản thu nhập: Lương cứng, phụ cấp, thưởng và các khoản khấu trừ bảo hiểm, thuế.\n• MỚI: Hệ thống tự động áp dụng hệ số lương (%) theo từng giai đoạn: 85% Thử việc, 40% Thực tập, 60% Học việc, 100% Chính thức.\n• MỚI: Khi nhân viên hết thử việc vào giữa tháng, hệ thống tự chia 2 phần và tính lương chính xác theo từng mức.\n• MỚI: Nhân viên mới vào tháng trước sẽ được tự động tính truy lĩnh và cộng vào cột 'Khác' tháng hiện tại.\n• MỚI: Admin có thể thêm ngày công bù thủ công (cột 'Ngày bù') để xử lý delay chấm công cho nhân viên mới.\n• Thiết lập hệ số lương trong Hồ sơ nhân viên → mục 'Giai đoạn thử việc / Thực tập'."
            ]
        ],
        'kpi' => [
            'launch_date' => '2026-06-03',
            'guidance'    => [
                'title'   => 'Hướng dẫn KPI tư vấn',
                'content' => "• KPI tư vấn được tính theo tổng giá trị hợp đồng đã chốt trong tháng.\n• Mốc 150.000.000 VNĐ tương ứng thưởng 5.000.000 VNĐ; vượt hoặc thiếu mốc được tăng giảm tuyến tính theo tỷ lệ.\n• Ghi nhận người tư vấn chốt và ngày chốt trong form hồ sơ vụ việc. Chỉ người có quyền kpi.consulting hoặc admin được cập nhật thông tin này."
            ]
        ],
        'cases' => [
            'launch_date' => '2026-01-01',
            'guidance'    => [
                'title'   => 'Hướng dẫn Quản lý Vụ việc',
                'content' => "• Theo dõi danh sách các vụ việc pháp lý bạn đang tham gia.\n• Sau khi được phân vụ việc thì chuyển trạng thái sang 'Đang xử lý' ở nút cập nhật trong chi tiết vụ việc.\n• Click vào từng vụ việc để xem chi tiết tiến độ, các bước thực hiện và tài liệu đính kèm.\n• Cập nhật kết quả công việc theo đúng quy trình đã đề ra."
            ]
        ],
        'case-kpi-override' => [
            'launch_date' => '2026-07-09',
            'guidance'    => [
                'title'   => 'Ghi nhận KPI ngoại lệ',
                'content' => "• Deadline của bước vụ việc được tính đến 23:59:59 của ngày hạn định.\n• Quản lý hoặc người duyệt có thể ghi nhận KPI cho bước hoàn thành trễ nếu lý do giải trình hợp lý.\n• Báo cáo KPI vẫn giữ thời điểm hoàn thành thực tế và chỉ cộng thưởng khi bước đúng hạn hoặc đã được ghi nhận ngoại lệ."
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
            'launch_date' => '2026-07-03',
            'guidance'    => [
                'title'   => 'Hướng dẫn Quản lý Khách hàng',
                'content' => "• Lưu trữ thông tin cá nhân/tổ chức khách hàng, lịch sử giao dịch và liên hệ.\n• MỚI: Bổ sung tính năng phân bổ nhân sự phụ trách chăm sóc tư vấn độc lập.\n• MỚI: Theo dõi trạng thái quà tặng và cập nhật nhanh ngay trên danh sách khách hàng.\n• Phân loại khách hàng theo nhóm để có chiến lược chăm sóc phù hợp.\n• Tra cứu nhanh hồ sơ khách hàng khi cần liên hệ hoặc xử lý vụ việc."
            ]
        ],
        'customer-care' => [
            'launch_date' => '2026-05-22',
            'guidance'    => [
                'title'   => 'Hướng dẫn Module CSKH',
                'content' => "• Quản lý quy trình chăm sóc khách hàng cũ theo 3 giai đoạn tiêu chuẩn từ lúc kết thúc dịch vụ.\n• Tự động tạo checklist công việc hàng ngày/hàng tuần cho nhân viên CSKH phụ trách.\n• Theo dõi các chỉ số KPI thực tế (tỷ lệ quay lại, tỷ lệ giới thiệu, tỷ lệ phản hồi).\n• Quản lý chương trình khách hàng thân thiết, tích điểm và nâng hạng thành viên VIP tự động."
            ]
        ],
        'sla-report' => [
            'launch_date' => '2026-05-25',
            'guidance'    => [
                'title'   => 'Hướng dẫn Báo cáo & Cấu hình SLA',
                'content' => "• Xem thống kê chi tiết tỷ lệ hoàn thành đúng hạn (SLA) của từng nhân viên tư vấn.\n• Xem danh sách Cảnh báo đỏ các khách hàng quá hạn chưa xử lý.\n• Quản lý cấu hình danh mục các bước trạng thái tư vấn và thời hạn SLA động (số giờ) cho mỗi bước."
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
        'chat' => [
            'launch_date' => '2026-05-21',
            'guidance'    => [
                'title'   => 'Hướng dẫn Trung tâm Tư Vấn Khách Hàng',
                'content' => "• Quản lý tập trung TẤT CẢ hội thoại từ Zalo OA và Facebook Messenger trong 1 giao diện duy nhất.\n• Sử dụng tab lọc kênh (Tất cả / Zalo / Messenger) để chuyển đổi nhanh giữa các nền tảng.\n• MỚI (Giai đoạn 2): Tự động lọc trùng Lead qua SĐT/Email; Tự động phân tích từ khóa tin nhắn để gắn Lĩnh vực pháp lý và chấm điểm độ nóng của Lead (Nóng 🔥, Ấm ☀️, Lạnh ❄️).\n• MỚI (Giai đoạn 3): Tự động phân phối Lead cho nhân viên theo Chuyên môn và Tải công việc tối ưu; Quản lý Deadline phản hồi đầu tiên 2h nghiêm ngặt (tự động thu hồi & phân phối lại Lead cho nhân sự khác nếu quá hạn phản hồi)."
            ]
        ],
        'zalo' => [
            'launch_date' => '2026-05-15',
            'guidance'    => [
                'title'   => 'Hướng dẫn Cấu hình Zalo OA',
                'content' => "• Trang cấu hình kết nối giữa hệ thống ERP và Zalo Official Account.\n• Nhập App ID, Secret Key và xác thực OAuth để kích hoạt liên kết.\n• Sau khi cấu hình xong, hội thoại Zalo sẽ xuất hiện tại Trung tâm Chat."
            ]
        ],
        'messenger' => [
            'launch_date' => '2026-05-20',
            'guidance'    => [
                'title'   => 'Hướng dẫn Cấu hình Facebook Messenger',
                'content' => "• Trang cấu hình kết nối giữa hệ thống ERP và Facebook Page.\n• Nhập Page Access Token, App ID, App Secret và Verify Token.\n• Sau khi cấu hình xong, hội thoại Messenger sẽ xuất hiện tại Trung tâm Chat."
            ]
        ],
        'quick-replies' => [
            'launch_date' => '2026-05-16',
            'guidance'    => [
                'title'   => 'Hướng dẫn Câu trả lời nhanh',
                'content' => "• Thiết lập các mẫu câu trả lời phổ biến để hỗ trợ khách hàng nhanh hơn.\n• Các mẫu này sẽ hiển thị trong ô chat Zalo/Messenger dưới dạng phím tắt.\n• Giúp chuẩn hóa nội dung tư vấn và tiết kiệm thời gian gõ phím cho nhân sự."
            ]
        ],
    ];

    /**
     * Số ngày hiển thị badge "New" kể từ ngày ra mắt.
     */
    public int $newBadgeDurationDays = 14;
}
