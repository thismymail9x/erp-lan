<?php

namespace App\Models;

/**
 * CaseHistoryModel
 * 
 * Theo dõi lịch sử thay đổi của vụ việc.
 */
/**
 * CaseHistoryModel
 * 
 * Nhật ký ghi lại mọi biến động và tác động lên hồ sơ vụ việc (Audit Trail).
 * Đảm bảo tính minh bạch bằng cách lưu vết ai đã làm gì, lúc nào và thay đổi gì.
 */
class CaseHistoryModel extends BaseModel
{
    // 1. Cấu hình bảng
    protected $table            = 'case_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    // 2. Các trường thông tin lịch sử
    protected $allowedFields    = [
        'case_id',    // ID vụ việc bị tác động
        'user_id',    // ID người thực hiện hành động
        'action',     // Loại hành động (ví dụ: tạo mới, cập nhật trạng thái, upload file)
        'old_value',  // Giá trị cũ trước khi thay đổi (nếu có)
        'new_value',  // Giá trị mới sau khi thay đổi (nếu có)
        'note',       // Ghi chú chi tiết hoặc lý do thay đổi
        'created_at'  // Thời điểm ghi nhận
    ];

    // 3. Quản lý thời gian
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // Lịch sử là dữ liệu chỉ đọc, không cần cập nhật
}
