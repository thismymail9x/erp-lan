<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MessengerMessageModel
 * 
 * Lưu trữ lịch sử tin nhắn trao đổi qua Facebook Messenger.
 * Tương đương ZaloMessageModel nhưng dành cho kênh Facebook.
 * 
 * sender_type:
 *   - 'user'  = Tin nhắn do KH gửi vào Page
 *   - 'page'  = Tin nhắn do Page (nhân sự ERP) gửi ra cho KH
 */
class MessengerMessageModel extends Model
{
    protected $table            = 'messenger_messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'contact_id',       // Khóa ngoại tới messenger_contacts.id
        'fb_msg_id',        // ID tin nhắn từ Facebook (dùng chống trùng lặp webhook)
        'sender_type',      // 'user' | 'page'
        'message_text',     // Nội dung văn bản
        'attachments',      // JSON: [{type: 'image'|'file'|'sticker', payload: {...}}]
        'is_read',          // 0 = Chưa đọc, 1 = Đã đọc
        'mid_staff_id',     // user_id của nhân sự gửi tin (nếu sender_type = 'page')
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Cấu hình timestamps và soft delete
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $dateFormat     = 'datetime';
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';
}
