<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MessengerContactModel
 * 
 * Quản lý dữ liệu người dùng tương tác qua Facebook Messenger (Page Inbox).
 * Tương đương ZaloFollowerModel nhưng dành cho kênh Facebook.
 * 
 * Mỗi bản ghi đại diện cho 1 người dùng (User PSID) đã từng nhắn tin vào Facebook Page.
 * PSID (Page-Scoped ID): Định danh duy nhất của người dùng Facebook với Page cụ thể.
 */
class MessengerContactModel extends Model
{
    protected $table            = 'messenger_contacts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'psid',            // Page-Scoped ID: Định danh Facebook duy nhất của người dùng với Page này
        'display_name',    // Tên hiển thị lấy từ Facebook Graph API
        'avatar_url',      // Ảnh đại diện
        'phone_number',    // Số điện thoại (nếu người dùng cung cấp, thường không có)
        'email',           // Địa chỉ email khách hàng cung cấp qua chat
        'mid_code',        // Mã định danh nội bộ hệ thống ERP (VD: FB-A1B2C3)
        'customer_id',     // Liên kết với bảng customers (CRM) nếu đã đồng bộ
        'assigned_to',     // user_id của nhân sự phụ trách hội thoại này
        'assigned_at',     // Thời điểm phân công nhân sự gần nhất
        'tags',            // JSON array các tag phân loại (VD: ["Tiềm năng", "Khiếu nại"])
        'lead_warmth',     // Độ nóng của lead: hot (Nóng), warm (Ấm), cold (Lạnh)
        'is_duplicate',    // Cờ báo trùng lặp (1: Trùng lặp, 0: Bình thường)
        'duplicate_of',    // ID liên hệ chính trong messenger_contacts bị trùng
        'first_response_deadline', // Hạn chót để phản hồi khách hàng lần đầu (2 tiếng)
        'first_responded_at',      // Thời điểm phản hồi thực tế lần đầu tiên
        'is_overdue',      // Cờ đánh dấu quá hạn phản hồi (1: Quá hạn, 0: Đúng hạn)
        'ongoing_response_deadline', // Hạn chót để phản hồi tin nhắn mới nhất
        'last_customer_msg_at',      // Thời điểm khách hàng gửi tin nhắn cuối cùng
        'ongoing_is_overdue',        // Cờ đánh dấu quá hạn phản hồi tin nhắn kế tiếp
        'locale',          // Ngôn ngữ của người dùng (VD: vi_VN)
        'timezone',        // Múi giờ người dùng
        'page_id',         // Facebook Page ID nhận tin nhắn (hỗ trợ multi-page sau này)
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Cấu hình timestamps và soft delete
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
