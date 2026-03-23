<?php

namespace App\Models;

/**
 * DocumentModel
 * 
 * Quản lý hồ sơ, tài liệu đính kèm vụ việc.
 */
/**
 * DocumentModel
 * 
 * Quản lý danh sách các tài liệu, hồ sơ, bằng chứng đính kèm trong từng vụ việc.
 * Phân loại và lưu trữ đường dẫn file phục vụ việc tra cứu nhanh của Luật sư.
 */
class DocumentModel extends BaseModel
{
    // 1. Cấu hình bảng
    protected $table            = 'documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    // 2. Các trường thông tin tài liệu hồ sơ
    protected $allowedFields    = [
        'case_id',     // Thuộc vụ việc nào
        'step_id',     // Thuộc bước nào (tùy chọn)
        'file_name',   // Tên tài liệu (Ví dụ: Đơn khởi kiện.pdf)
        'type',        // Loại tài liệu (Bằng chứng, Văn bản tòa án, Hợp đồng...)
        'file_path',   // Đường dẫn lưu trữ trên server
        'uploaded_by', // Người tải lên
        'created_at'   // Ngày tải lên
    ];

    // 3. Quản lý thời gian
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
