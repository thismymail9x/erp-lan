<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController
 * 
 * Lớp điều khiển cơ sở (Base Class) cho toàn bộ hệ thống ERP.
 * Đây là nơi tập trung các thành phần dùng chung như:
 * 1. Khởi tạo Request/Response.
 * 2. Nạp các Helper mặc định (Url, Form, Text...).
 * 3. Quản lý Session và các Service nòng cốt cho Controller con.
 */
abstract class BaseController extends Controller
{
    /**
     * Khai báo các thuộc tính để tránh lỗi dynamic property trong PHP 8.2+
     */
    protected $session;

    /**
     * Phương thức khởi tạo Controller (Thay thế cho __construct trong CI4).
     * 
     * @param RequestInterface  $request  Đối tượng yêu cầu HTTP.
     * @param ResponseInterface $response Đối tượng phản hồi HTTP.
     * @param LoggerInterface   $logger   Đối tượng ghi log hệ thống.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // 1. Thực thi khởi tạo từ lớp cha (CodeIgniter Core)
        parent::initController($request, $response, $logger);

        // 2. TỰ ĐỘNG NẠP HELPERS:
        // Các helper này sẽ khả dụng trong tất cả Controller và View kế thừa.
        $this->helpers = array_merge($this->helpers, ['url', 'form', 'html', 'auth', 'text']);

        // 3. KHỞI TẠO DỊCH VỤ DÙNG CHUNG:
        $this->session = \Config\Services::session();
        
        /**
         * LƯU Ý BẢO MẬT:
         * Mọi phương thức bổ sung trong BaseController KHÔNG NÊN để public 
         * để tránh việc bị gọi nhầm qua Routing (Security hardening).
         */
    }
}
