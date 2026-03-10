<?php

namespace App\Services;

use CodeIgniter\Config\Services;
use CodeIgniter\Log\Logger;

/**
 * BaseService
 * 
 * Lớp cơ sở trừu tượng cho tất cả các dịch vụ nghiệp vụ (business services) trong ERP.
 * Lớp này xử lý logic giữa Controllers và Models.
 */
abstract class BaseService
{
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct()
    {
        $this->logger = Services::logger();
    }

    /**
     * Ghi nhật ký lỗi chung cho các dịch vụ
     * 
     * @param string $message Thông báo lỗi
     * @param array $context Ngữ cảnh dữ liệu liên quan
     */
    protected function logError(string $message, array $context = [])
    {
        $this->logger->error($message, $context);
    }

    /**
     * Ghi nhật ký thông tin chung
     * 
     * @param string $message Thông báo
     * @param array $context Ngữ cảnh dữ liệu
     */
    protected function logInfo(string $message, array $context = [])
    {
        $this->logger->info($message, $context);
    }

    /**
     * Định dạng phản hồi thành công chung
     * 
     * @param mixed $data Dữ liệu trả về
     * @param string $message Thông báo thành công
     * @return array
     */
    protected function success($data = null, string $message = 'Thành công')
    {
        return [
            'status'  => 'success',
            'message' => $message,
            'data'    => $data
        ];
    }

    /**
     * Định dạng phản hồi lỗi chung
     * 
     * @param string $message Thông báo lỗi
     * @param int $code Mã lỗi HTTP
     * @return array
     */
    protected function fail(string $message = 'Thất bại', int $code = 400)
    {
        return [
            'status'  => 'error',
            'message' => $message,
            'code'    => $code
        ];
    }
}
