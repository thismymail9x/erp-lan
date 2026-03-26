<?php

namespace App\Controllers;

use App\Models\NotificationModel;

/**
 * NotificationController
 * 
 * Bộ điều khiển trung tâm quản lý hệ thống Thông báo (In-app Notifications).
 * Đảm nhiệm:
 * 1. Hiển thị danh sách thông báo theo từng User.
 * 2. Cung cấp dữ liệu Real-time cho Header Dropdown.
 * 3. Xử lý logic đánh dấu Trạng thái đọc (Read Status).
 */
class NotificationController extends BaseController
{
    protected $notificationModel;

    public function __construct()
    {
        // Khởi tạo model quản lý thông báo
        $this->notificationModel = new NotificationModel();
    }

    /**
     * Hiển thị trang trung tâm thông báo.
     * Hỗ trợ phân trang để tối ưu tốc độ tải khi User có hàng ngàn thông báo.
     */
    public function index()
    {
        $userId = session()->get('user_id');
        $data = [
            // Lấy 20 thông báo gần nhất kèm theo phân trang (Pager)
            'notifications' => $this->notificationModel->getNotifications($userId, 20),
            'pager'         => $this->notificationModel->pager,
            'title'         => 'Trung tâm thông báo | L.A.N ERP'
        ];
        return view('dashboard/notifications/index', $data);
    }

    /**
     * API: Lấy số lượng thông báo chưa đọc.
     * Sử dụng cho việc cập nhật Badge (số đỏ) trên Header mà không cần Load lại trang.
     */
    public function getUnreadCount()
    {
        $userId = session()->get('user_id');
        $count = $this->notificationModel->countUnread($userId);
        
        $latest = [];
        if ($count > 0) {
            // Lấy thêm nội dung tóm tắt cho thanh chạy thông báo (Ticker)
            $latest = $this->notificationModel->getUnread($userId, 5);
        }
        
        return $this->response->setJSON([
            'status' => 'success', 
            'count'  => $count, 
            'latest' => $latest
        ]);
    }

    /**
     * API: Lấy danh sách thông báo mới nhất cho Menu Dropdown.
     * Chỉ lấy 5 thông báo gần nhất để hiển thị nhanh trên thanh công cụ.
     */
    public function getUnread()
    {
        $userId = session()->get('user_id');
        $notifications = $this->notificationModel->getUnread($userId, 5);
        return $this->response->setJSON(['status' => 'success', 'data' => $notifications]);
    }

    /**
     * API: Đánh dấu một thông báo cụ thể là đã đọc.
     * 
     * @param int|string $id ID của thông báo cần xử lý.
     */
    public function markAsRead($id)
    {
        $userId = session()->get('user_id');
        // Chỉ cho phép User sở hữu thông báo đó được thay đổi trạng thái
        $this->notificationModel->markAsRead($id, $userId);
        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * API: Đánh dấu tất cả thông báo của User hiện tại là đã đọc.
     * Sử dụng cho tính năng "Đánh dấu tất cả đã đọc" (Mark all as read).
     */
    public function markAllAsRead()
    {
        $userId = session()->get('user_id');
        $this->notificationModel->markAllAsRead($userId);
        return $this->response->setJSON(['status' => 'success']);
    }
}
