<?php

namespace App\Services;

use DateTime;

/**
 * CaseTimelineService
 * 
 * Lớp dịch vụ quản lý quy trình nghiệp vụ (Workflow) và thời hạn (Timeline).
 * Định nghĩa các bước chuẩn cho từng loại vụ việc pháp lý dựa trên quy định pháp luật.
 */
class CaseTimelineService
{
    /**
     * Định nghĩa danh mục các bước và thời gian thực hiện chuẩn cho mỗi loại vụ việc.
     * Mỗi bước bao gồm: Tên bước, Số ngày dự kiến, và các tài liệu bắt buộc cần thu thập.
     */
    public const WORKFLOWS = [
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
        'thu_tuc_hanh_chinh' => [
            ['name' => 'Chuẩn bị hồ sơ', 'days' => 3, 'docs' => ['Hồ sơ hành chính']],
            ['name' => 'Nộp hồ sơ', 'days' => 15, 'docs' => ['Giấy biên nhận']],
            ['name' => 'Trả kết quả tại thuế', 'days' => 5, 'docs' => ['Thông báo thuế']],
            ['name' => 'Nhận kết quả', 'days' => 0, 'docs' => []],
        ],
        'xoa_an_tich' => [
            ['name' => 'Xin bản án', 'days' => 7, 'docs' => ['Bản sao CMND/CCCD', 'Giấy ủy quyền']],
            ['name' => 'Nộp án phí', 'days' => 7, 'docs' => ['Biên lai nộp án phí']],
            ['name' => 'Xác nhận chấp hành án', 'days' => 7, 'docs' => ['Giấy xác nhận thi hành án']],
            ['name' => 'Nộp hồ sơ CA thành phố', 'days' => 2, 'docs' => ['Đơn yêu cầu cấp Phiếu LLTP']],
            ['name' => 'Xã/phường', 'days' => 10, 'docs' => ['Giấy xác nhận hạnh kiểm']],
            ['name' => 'Nộp lại CA thành phố', 'days' => 5, 'docs' => ['Hồ sơ bổ sung']],
            ['name' => 'Nhận kết quả', 'days' => 0, 'docs' => ['Phiếu lý lịch tư pháp số 2']],
        ],
        'ly_hon_thuan_tinh' => [
            ['name' => 'Nộp đơn', 'days' => 3, 'docs' => ['Đơn ly hôn thuận tình', 'Đăng ký kết hôn']],
            ['name' => 'Phân công thẩm phán', 'days' => 3, 'docs' => []],
            ['name' => 'Hòa giải', 'days' => 3, 'docs' => ['Biên bản hòa giải']],
            ['name' => 'Công nhận thuận tình', 'days' => 3, 'docs' => []],
            ['name' => 'Nhận quyết định', 'days' => 3, 'docs' => []],
        ],
        'tu_van' => [
            ['name' => 'Mới', 'days' => 0.25, 'docs' => []],
            ['name' => 'Đang tư vấn', 'days' => 0.25, 'docs' => []],
            ['name' => 'Chốt', 'days' => 0.25, 'docs' => []],
            ['name' => 'Đóng', 'days' => 0.25, 'docs' => []],
        ]
    ];

    /**
     * Tính toán ngày hết hạn (Deadline) dựa trên số ngày làm việc.
     * Thuật toán tự động bỏ qua các ngày Thứ 7 và Chủ nhật để khớp với lịch làm việc thực tế của tòa án/cơ quan nhà nước.
     * 
     * @param DateTime $startDate Ngày bắt đầu tính
     * @param int|float $days Số ngày thực hiện
     * @return DateTime Đối tượng DateTime chứa thông tin ngày hết hạn
     */
    public function calculateDeadline(DateTime $startDate, $days)
    {
        $date = clone $startDate;
        
        // 1. Xử lý trường hợp thời hạn tính theo giờ (Dành cho tư vấn nhanh)
        if ($days < 1) {
            $hours = $days * 24;
            $date->modify("+".round($hours)." hours");
            return $date;
        }

        // 2. Xử lý trường hợp tính theo ngày làm việc
        while ($days > 0) {
            $date->modify('+1 day');
            // Nếu không phải là Thứ 7 (6) hoặc Chủ nhật (7) thì mới tính là 1 ngày làm việc
            if ($date->format('N') < 6) { 
                $days--;
            }
        }
        return $date;
    }

    /**
     * Truy xuất danh sách các bước chuẩn cho một loại vụ việc cụ thể
     * 
     * @param string $type Mã loại vụ việc
     * @return array Mảng các bước quy trình
     */
    public function getStepsForType(string $type): array
    {
        return self::WORKFLOWS[$type] ?? [];
    }
}
