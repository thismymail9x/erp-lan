<?php

namespace App\Services;

use CodeIgniter\Config\Services;

/**
 * MailService
 * 
 * Dịch vụ gửi email dùng chung cho toàn bộ hệ thống.
 * Hỗ trợ gửi mail qua SMTP và sử dụng template HTML.
 */
class MailService extends BaseService
{
    protected $email;

    public function __construct()
    {
        parent::__construct();
        $this->email = Services::email();
    }

    /**
     * Gửi email cơ bản
     * 
     * @param string $to Email người nhận
     * @param string $subject Tiêu đề email
     * @param string $message Nội dung email (HTML hoặc Text)
     * @param array $attachments Danh sách file đính kèm (tùy chọn)
     * @return bool
     */
    public function send(string $to, string $subject, string $message, array $attachments = [])
    {
        $this->email->setTo($to);
        $this->email->setSubject($subject);
        $this->email->setMessage($message);

        foreach ($attachments as $file) {
            $this->email->attach($file);
        }

        if ($this->email->send()) {
            $this->logInfo("Email sent to $to: $subject");
            return true;
        } else {
            $this->logError("Failed to send email to $to. Error: " . $this->email->printDebugger(['headers']));
            return false;
        }
    }

    /**
     * Gửi email theo template view
     * 
     * @param string $to Email người nhận
     * @param string $subject Tiêu đề email
     * @param string $viewPath Đường dẫn view template
     * @param array $data Dữ liệu truyền vào view
     * @return bool
     */
    public function sendWithTemplate(string $to, string $subject, string $viewPath, array $data = [])
    {
        $message = view($viewPath, $data);
        return $this->send($to, $subject, $message);
    }
}
