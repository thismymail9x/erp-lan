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
     * Hiển thị trung tâm thông báo tập trung.
     * Hỗ trợ 3 luồng dữ liệu: Đến (Inbox), Đi (Sent) và Quản trị (All - Admin Only).
     */
    public function index()
    {
        $userId = session()->get('user_id');
        $tab = $this->request->getGet('tab') ?: 'inbox';
        $search = (string) $this->request->getGet('q');
        $type = (string) $this->request->getGet('type');
        
        $data = [
            'title' => 'Thông báo & Chỉ đạo nội bộ | L.A.N ERP',
            'tab'   => $tab
        ];

        // LOGIC PHÂN LUỒNG TAB KÈM BỘ LỌC
        if ($tab === 'sent') {
            $data['notifications'] = $this->notificationModel->getSent($userId, 20, $search, $type);
        } elseif ($tab === 'all' && has_permission('sys.admin')) {
            $data['notifications'] = $this->notificationModel->getAllLogs(30, $search, $type);
        } else {
            $data['notifications'] = $this->notificationModel->getNotifications($userId, 20, $search, $type);
        }
        
        $data['pager'] = $this->notificationModel->pager;

        // PHẢN HỒI AJAX (PARTIAL VIEW UPDATE)
        if ($this->request->isAJAX()) {
            return view('dashboard/notifications/index_list', $data);
        }

        return view('dashboard/notifications/index', $data);
    }

    /**
     * Giao diện soạn thông báo/ý kiến mới.
     */
    public function create()
    {
        $data = [
            'staffs'      => get_available_employees(),
            'departments' => get_departments(),
            'title'       => 'Soạn thông báo & Ý kiến mới | L.A.N ERP'
        ];
        return view('dashboard/notifications/create', $data);
    }

    /**
     * Lưu và phát tán thông báo.
     */
    public function store()
    {
        $targetType = $this->request->getPost('target_type') ?: 'individual';
        $message = $this->request->getPost('message');
        $title = $this->request->getPost('title') ?: 'Trao đổi nội bộ mới';
        
        if (!$message) {
            return redirect()->back()->with('error', 'Vui lòng nhập nội dung trao đổi.');
        }

        $recipientUserIds = [];

        // XÁC ĐỊNH DANH SÁCH NGƯỜI NHẬN
        if ($targetType === 'individual') {
            $recipientUserIds = $this->request->getPost('user_ids'); // Dạng mảng
        } elseif ($targetType === 'department') {
            $deptId = $this->request->getPost('department_id');
            $roleName = session()->get('role_name');
            $myDeptId = session()->get('department_id');

            // Bảo mật: Chỉ cho phép Admin gửi đi bất kỳ phòng nào, 
            // HOẶC Trưởng phòng gửi cho chính phòng mình.
            if (has_permission('sys.admin') || ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG && $deptId == $myDeptId)) {
                if ($deptId) {
                    $recipientUserIds = model('EmployeeModel')->where('department_id', $deptId)->findColumn('user_id');
                }
            } else {
                return redirect()->back()->with('error', 'Bạn không có quyền gửi thông báo cho phòng ban này.');
            }
        } elseif ($targetType === 'all' && has_permission('sys.admin')) {
            $recipientUserIds = model('UserModel')->findColumn('id');
        }

        if (empty($recipientUserIds)) {
            return redirect()->back()->with('error', 'Không tìm thấy người nhận hợp lệ.');
        }

        // PHÁT TÁN THÔNG BÁO CHO TỪNG NGƯỜI (Batch insert if possible, but safe loop is fine here)
        $batchData = [];
        $senderId = session()->get('user_id');

        foreach ($recipientUserIds as $rid) {
            // Không tự gửi cho chính mình trong chế độ gửi hàng loạt/công ty
            if ($rid == $senderId && count($recipientUserIds) > 1) continue;

            $batchData[] = [
                'user_id'   => $rid,
                'sender_id' => $senderId,
                'type'      => 'message',
                'title'     => $title,
                'message'   => $message,
                'is_read'   => 0
            ];
        }

        if (!empty($batchData)) {
            $this->notificationModel->insertBatch($batchData);
        }

        return redirect()->to(base_url('notifications?tab=sent'))->with('success', 'Đã chuyển ' . count($batchData) . ' thông báo đi thành công.');
    }

    /**
     * Xem chi tiết nội dung thông báo.
     */
    public function show($id)
    {
        $userId = session()->get('user_id');
        $notif = $this->notificationModel->getFullDetail($id);

        if (!$notif) {
            return redirect()->to(base_url('notifications'))->with('error', 'Không tìm thấy thông báo.');
        }

        // BẢO MẬT: Chỉ người gửi, người nhận hoặc Admin mới được xem chi tiết
        if ($notif['user_id'] != $userId && $notif['sender_id'] != $userId && !has_permission('sys.admin')) {
             return redirect()->to(base_url('notifications'))->with('error', 'Bạn không có quyền xem thông báo này.');
        }

        // Đánh dấu đã đọc nếu là người nhận
        if ($notif['user_id'] == $userId) {
            $this->notificationModel->markAsRead($id, $userId);
        }

        $data = [
            'notif' => $notif,
            'title' => 'Chi tiết thông báo: ' . $notif['title']
        ];

        return view('dashboard/notifications/show', $data);
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
