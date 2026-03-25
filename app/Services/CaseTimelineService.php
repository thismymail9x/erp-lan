<?php

namespace App\Services;

use DateTime;

/**
 * CaseTimelineService
 * 
 * Lớp dịch vụ quản lý quy trình nghiệp vụ (Workflow) và thời hạn (Timeline) mặc định.
 * Định nghĩa các bước chuẩn cho từng loại vụ việc pháp lý dựa trên quy định pháp luật hoặc quy trình nội bộ.
 * Cung cấp thuật toán tính toán Deadline thông minh.
 */
class CaseTimelineService
{
    /**
     * TỪ ĐIỂN QUY TRÌNH (Standard Operating Procedures - SOP).
     * Định nghĩa danh mục các bước và thời gian thực hiện chuẩn cho mỗi loại vụ việc.
     * Mỗi bước bao gồm: 
     * - name: Tên bước thực hiện.
     * - days: Số ngày làm việc dự kiến (Sử dụng để cộng dồn vào Deadline).
     * - docs: Danh sách tên các tài liệu/giấy tờ bắt buộc phải có để hoàn tất bước này.
     */
    public const WORKFLOWS = [
        // Quy trình Tố tụng dân sự: Dành cho các vụ kiện tụng tại Tòa án.
        'to_tung_dan_su' => [
            ['name' => 'Chuẩn bị hồ sơ', 'days' => 5, 'docs' => ['CMND/CCCD', 'Giấy tờ liên quan vụ việc']],
            ['name' => 'Nộp đơn đề nghị hòa giải tại xã', 'days' => 2, 'docs' => ['Đơn đề nghị hòa giải']],
            ['name' => 'Hòa giải tại xã', 'days' => 2, 'docs' => ['Biên bản hòa giải']],
            ['name' => 'Nộp đơn khởi kiện', 'days' => 1, 'docs' => ['Đơn khởi kiện']],
            ['name' => 'Chuẩn bị chứng cứ', 'days' => 3, 'docs' => ['Tài liệu chứng cứ bổ sung']],
            ['name' => 'Soạn đơn', 'days' => 1, 'docs' => ['Đơn từ liên quan']],
            ['name' => 'Đưa vụ việc ra xét xử', 'days' => 1, 'docs' => []],
            ['name' => 'Tòa ra xét xử sơ thẩm', 'days' => 0, 'docs' => []],
        ],
        // Quy trình Thủ tục hành chính: Dành cho các hồ sơ đất đai, giấy phép, thuế.
        'thu_tuc_han_chinh' => [
            ['name' => 'Chuẩn bị hồ sơ', 'days' => 3, 'docs' => ['Hồ sơ hành chính']],
            ['name' => 'Nộp hồ sơ', 'days' => 15, 'docs' => ['Giấy biên nhận']],
            ['name' => 'Trả kết quả tại thuế', 'days' => 5, 'docs' => ['Thông báo thuế']],
            ['name' => 'Nhận kết quả', 'days' => 0, 'docs' => []],
        ],
        // Quy trình Xóa án tích: Thủ tục tư pháp đặc thù.
        'xoa_an_tich' => [
            ['name' => 'Xin bản án', 'days' => 7, 'docs' => ['Bản sao CMND/CCCD', 'Giấy ủy quyền']],
            ['name' => 'Nộp án phí', 'days' => 7, 'docs' => ['Biên lai nộp án phí']],
            ['name' => 'Xác nhận chấp hành án', 'days' => 7, 'docs' => ['Giấy xác nhận thi hành án']],
            ['name' => 'Nộp hồ sơ CA thành phố', 'days' => 2, 'docs' => ['Đơn yêu cầu cấp Phiếu LLTP']],
            ['name' => 'Xã/phường', 'days' => 10, 'docs' => ['Giấy xác nhận hạnh kiểm']],
            ['name' => 'Nộp lại CA thành phố', 'days' => 5, 'docs' => ['Hồ sơ bổ sung']],
            ['name' => 'Nhận kết quả', 'days' => 0, 'docs' => ['Phiếu lý lịch tư pháp số 2']],
        ],
        // Quy trình Ly hôn thuận tình: Quy trình rút gọn cho các vụ việc hôn nhân.
        'ly_hon_thuan_tinh' => [
            ['name' => 'Nộp đơn', 'days' => 3, 'docs' => ['Đơn ly hôn thuận tình', 'Đăng ký kết hôn']],
            ['name' => 'Phân công thẩm phán', 'days' => 3, 'docs' => []],
            ['name' => 'Hòa giải', 'days' => 3, 'docs' => ['Biên bản hòa giải']],
            ['name' => 'Công nhận thuận tình', 'days' => 3, 'docs' => []],
            ['name' => 'Nhận quyết định', 'days' => 3, 'docs' => []],
        ],
        // Quy trình Tư vấn nhanh: Dành cho các yêu cầu giải đáp pháp luật đơn giản (Tính theo giờ).
        'tu_van' => [
            ['name' => 'Mới', 'days' => 0.25, 'docs' => []],
            ['name' => 'Đang tư vấn', 'days' => 0.25, 'docs' => []],
            ['name' => 'Chốt', 'days' => 0.25, 'docs' => []],
            ['name' => 'Đóng', 'days' => 0.25, 'docs' => []],
        ]
    ];

    /**
     * THUẬT TOÁN TÍNH TOÁN THỜI HẠN (Deadline Engine).
     * Tính toán ngày hết hạn dựa trên số ngày làm việc thực tế của cơ quan pháp luật.
     * 
     * @param DateTime $startDate Ngày bắt đầu tính toán (Thời điểm chuyển bước).
     * @param int|float $days Số ngày (hoặc phần thập phân của ngày) cần cộng thêm.
     * @return DateTime Đối tượng DateTime đã được xử lý logic bỏ qua ngày nghỉ.
     */
    public function calculateDeadline(DateTime $startDate, $days)
    {
        // 1. Tạo bản sao để không làm ảnh hưởng đến mốc thời gian gốc (Immutability)
        $date = clone $startDate;
        
        // --- TRƯỜNG HỢP ĐẶC BIỆT: TÍNH THEO GIỜ ---
        // Nếu days < 1 (Ví dụ: 0.25 = 6 tiếng), hệ thống thực hiện phép cộng giờ trực tiếp.
        if ($days < 1) {
            $hours = $days * 24;
            $date->modify("+".round($hours)." hours");
            return $date;
        }

        // --- TRƯỜNG HỢP CHUẨN: TÍNH THEO NGÀY LÀM VIỆC ---
        // Logic: Tự động nhảy qua các ngày Thứ 7 (Sat) và Chủ nhật (Sun).
        while ($days > 0) {
            $date->modify('+1 day');
            
            /**
             * Kiểm tra định dạng ngày:
             * 'N' trả về 1 (T2) -> 7 (CN).
             * Nếu nhỏ hơn 6 tức là ngày thường (T2-T6).
             */
            if ($date->format('N') < 6) { 
                $days--;
            }
            // Nếu là T7 hoặc CN, vòng lặp tiếp tục nhưng không trừ giá trị $days (tức là ngày bù).
        }
        
        return $date;
    }

    /**
     * Truy xuất cấu hình của một loại quy trình cụ thể.
     */
    public function getStepsForType(string $type): array
    {
        return self::WORKFLOWS[$type] ?? [];
    }
}
