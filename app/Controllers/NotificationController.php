<?php

namespace App\Controllers;

use App\Models\NotificationModel;

class NotificationController extends BaseController
{
    protected $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
    }

    /**
     * Trang xem toàn bộ thông báo (nếu có View)
     */
    public function index()
    {
        $userId = session()->get('user_id');
        $data = [
            'notifications' => $this->notificationModel->getNotifications($userId, 20),
            'pager'         => $this->notificationModel->pager,
            'title'         => 'Danh sách thông báo'
        ];
        return view('dashboard/notifications/index', $data);
    }

    /**
     * Lấy số lượng thông báo chưa đọc (Dành cho AJAX)
     */
    public function getUnreadCount()
    {
        $userId = session()->get('user_id');
        $count = $this->notificationModel->countUnread($userId);
        return $this->response->setJSON(['status' => 'success', 'count' => $count]);
    }

    /**
     * Lấy danh sách thông báo chưa đọc để hiển thị trên Dropdown (Dành cho AJAX)
     */
    public function getUnread()
    {
        $userId = session()->get('user_id');
        $notifications = $this->notificationModel->getUnread($userId, 5);
        return $this->response->setJSON(['status' => 'success', 'data' => $notifications]);
    }

    /**
     * Đánh dấu 1 thông báo là đã đọc (AJAX)
     */
    public function markAsRead($id)
    {
        $userId = session()->get('user_id');
        $this->notificationModel->markAsRead($id, $userId);
        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Đánh dấu toàn bộ là đã đọc (AJAX)
     */
    public function markAllAsRead()
    {
        $userId = session()->get('user_id');
        $this->notificationModel->markAllAsRead($userId);
        return $this->response->setJSON(['status' => 'success']);
    }
}
