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
     * Lấy toàn bộ thông báo của user (có phân trang)
     */
    public function getNotifications($userId, $perPage = 10)
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->paginate($perPage);
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
}
