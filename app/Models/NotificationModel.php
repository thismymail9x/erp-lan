<?php

namespace App\Models;

class NotificationModel extends BaseModel
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id', 'sender_id', 'type', 'title', 
        'message', 'link', 'is_read', 'created_at', 'updated_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    /**
     * Lấy danh sách thông báo chưa đọc của user
     */
    public function getUnread($userId, $limit = 5)
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->orderBy('created_at', 'DESC')
                    ->findAll($limit);
    }
    
    /**
     * Đếm số lượng thông báo chưa đọc
     */
    public function countUnread($userId)
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->countAllResults();
    }
    
    /**
     * Hộp thư đến: Lấy toàn bộ thông báo của user (có phân trang & bộ lọc)
     */
    public function getNotifications($userId, $perPage = 10, $search = '', $type = '')
    {
        $query = $this->where('user_id', $userId);

        if (!empty($search)) {
            $query->groupStart()->like('title', $search)->orLike('message', $search)->groupEnd();
        }
        if (!empty($type)) {
            $query->where('type', $type);
        }

        return $query->orderBy('created_at', 'DESC')->paginate($perPage);
    }

    /**
     * Đánh dấu 1 thông báo là đã đọc
     */
    public function markAsRead($id, $userId)
    {
        return $this->where('id', $id)
                    ->where('user_id', $userId)
                    ->set(['is_read' => 1])
                    ->update();
    }
    
    /**
     * Đánh dấu toàn bộ là đã đọc
     */
    public function markAllAsRead($userId)
    {
        return $this->where('user_id', $userId)
                    ->set(['is_read' => 1])
                    ->update();
    }

    /**
     * HỘP THƯ ĐI: Lấy danh sách các thông báo/nhắc nhở mà user đã gửi.
     */
    public function getSent($userId, $perPage = 10, $search = '', $type = '')
    {
        $query = $this->select('notifications.*, recipients.full_name as recipient_name')
                    ->join('employees as recipients', 'recipients.user_id = notifications.user_id', 'left')
                    ->where('sender_id', $userId);

        if (!empty($search)) {
            $query->groupStart()->like('title', $search)->orLike('message', $search)->groupEnd();
        }
        if (!empty($type)) {
            $query->where('type', $type);
        }

        return $query->orderBy('created_at', 'DESC')->paginate($perPage);
    }

    /**
     * GIÁM SÁT HỆ THỐNG (ADMIN ONLY): Lấy toàn bộ luồng trao đổi trong công ty.
     */
    public function getAllLogs($perPage = 20, $search = '', $type = '')
    {
        $query = $this->select('notifications.*, senders.full_name as sender_name, recipients.full_name as recipient_name')
                    ->join('employees as senders', 'senders.user_id = notifications.sender_id', 'left')
                    ->join('employees as recipients', 'recipients.user_id = notifications.user_id', 'left');

        if (!empty($search)) {
            $query->groupStart()->like('title', $search)->orLike('message', $search)->groupEnd();
        }
        if (!empty($type)) {
            $query->where('type', $type);
        }

        return $query->orderBy('created_at', 'DESC')->paginate($perPage);
    }

    /**
     * Lấy chi tiết một thông báo kèm thông tin người gửi/nhận.
     */
    public function getFullDetail($id)
    {
        return $this->select('notifications.*, senders.full_name as sender_name, recipients.full_name as recipient_name')
                    ->join('employees as senders', 'senders.user_id = notifications.sender_id', 'left')
                    ->join('employees as recipients', 'recipients.user_id = notifications.user_id', 'left')
                    ->where('notifications.id', $id)
                    ->first();
    }
}
