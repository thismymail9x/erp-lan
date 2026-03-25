<?php

namespace App\Services;

use CodeIgniter\Config\Services;
use CodeIgniter\Log\Logger;

/**
 * BaseService
 * 
 * Lớp Dịch vụ cơ sở trừu tượng (Abstract Service Base).
 * Định nghĩa chuẩn mực cho toàn bộ lớp Service trong hệ thống ERP:
 * 1. Tách biệt logic nghiệp vụ khỏi Controller (Fat Models/Thin Controllers).
 * 2. Thống nhất cấu trúc phản hồi API (Standardized Response).
 * 3. Hỗ trợ ghi nhật ký lỗi hệ thống (Centralized Logging).
 */
abstract class BaseService
{
    /**
     * @var Logger Đối tượng ghi log của CodeIgniter
     */
    protected $logger;

    public function __construct()
    {
        // Tự động khởi tạo engine ghi log để sẵn sàng sử dụng trong các Service con
        $this->logger = Services::logger();
    }

    /**
     * Ghi nhật ký lỗi nghiệp vụ hoặc lỗi hệ thống.
     * 
     * @param string $message Thông điệp lỗi chi tiết.
     * @param array $context Các biến số/dữ liệu tại thời điểm xảy ra lỗi.
     */
    protected function logError(string $message, array $context = [])
    {
        $this->logger->error($message, $context);
    }

    /**
     * Ghi nhật ký thông tin vận hành thông thường.
     * Dùng cho các sự kiện quan trọng cần theo dõi như: Giao dịch thành công, Thay đổi cấu hình...
     */
    protected function logInfo(string $message, array $context = [])
    {
        $this->logger->info($message, $context);
    }

    /**
     * Chuẩn hóa cấu trúc phản hồi THÀNH CÔNG cho Controller.
     * Đảm bảo mọi Service đều trả về cùng một định dạng JSON-ready.
     * 
     * @param mixed $data Dữ liệuPayload cần trả về (Mảng, Object, string...).
     * @param string $message Thông báo UI thân thiện.
     * @return array
     */
    protected function success($data = null, string $message = 'Thao tác thực hiện thành công')
    {
        return [
            'status'  => 'success',
            'message' => $message,
            'data'    => $data
        ];
    }

    /**
     * Chuẩn hóa cấu trúc phản hồi THẤT BẠI cho Controller.
     * Giúp Front-end dễ dàng bắt lỗi và hiển thị Toastify/Alert.
     * 
     * @param string $message Thông báo lỗi cho người dùng.
     * @param int $code Mã lỗi nghiệp vụ/HTTP (Mặc định 400).
     * @return array
     */
    protected function fail(string $message = 'Thao tác thất bại. Vui lòng thử lại sau', int $code = 400)
    {
        return [
            'status'  => 'error',
            'message' => $message,
            'code'    => $code
        ];
    }
}
