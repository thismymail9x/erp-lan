<?php

namespace App\Services;

use CodeIgniter\Config\Services;

/**
 * MailService
 * 
 * Đơn vị vận chuyển thư điện tử dùng chung cho toàn bộ hệ thống (Centralized Mail Hub).
 * Chức năng:
 * 1. Cấu hình gửi mail qua giao thức SMTP (An toàn & Ổn định).
 * 2. Hỗ trợ Layout Email HTML chuyên nghiệp.
 * 3. Tự động đính kèm tài liệu bảo mật và ghi nhật ký truyền tin.
 */
class MailService extends BaseService
{
    protected $email;

    public function __construct()
    {
        parent::__construct();
        // Khởi tạo công cụ Email của CodeIgniter 4
        $this->email = Services::email();
    }

    /**
     * Phương thức Gửi Email thủ công.
     * 
     * @param string $to Địa chỉ hòm thư đích.
     * @param string $subject Chủ đề hấp dẫn.
     * @param string $message Nội dung thư (Chấp nhận mã HTML).
     * @param array $attachments Mảng đường dẫn tệp đính kèm (Ví dụ: CV, Hợp đồng).
     * @return bool Trạng thái gửi (Thành công/Thất bại).
     */
    public function send(string $to, string $subject, string $message, array $attachments = [])
    {
        // 1. Cấu hình các trường thông tin cơ bản
        $this->email->setTo($to);
        $this->email->setSubject($subject);
        $this->email->setMessage($message);

        // 2. Xử lý đính kèm tệp tin nếu có trong danh sách
        foreach ($attachments as $file) {
            $this->email->attach($file);
        }

        // 3. Thực thi hành động gửi và phân tích phản hồi từ Server Mail
        if ($this->email->send()) {
            // Lưu vết để Admin có thể kiểm soát lượng mail bắn ra
            $this->logInfo("Email đã được gửi tới [{$to}]: {$subject}");
            return true;
        } else {
            // Ghi log lỗi chi tiết (Bao gồm lỗi Header) để IT kiểm tra SMTP
            $this->logError("Lỗi máy chủ thư khi gửi tới {$to}. Chi tiết lỗi: " . $this->email->printDebugger(['headers']));
            return false;
        }
    }

    /**
     * Gửi email sử dụng Bản mẫu giao diện (Email Templates).
     * Đây là cách tiếp cận khuyên dùng để đảm bảo tính mỹ thuật và đồng bộ của thương hiệu.
     * 
     * @param string $to Email người nhận.
     * @param string $subject Tiêu đề thông báo.
     * @param string $viewPath Đường dẫn tới file View (Ví dụ: 'emails/welcome_guest').
     * @param array $data Mảng biến dữ liệu để "đổ" vào giao diện Template.
     */
    public function sendWithTemplate(string $to, string $subject, string $viewPath, array $data = [])
    {
        // Chế biến giao diện HTML từ file View của Ci4
        $message = view($viewPath, $data);
        
        // Chuyển lệnh cho hàm send() xử lý nốt phần còn lại.
        return $this->send($to, $subject, $message);
    }
}
