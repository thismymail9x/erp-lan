<?php

namespace App\Models;

/**
 * CustomerDocumentModel
 * 
 * Quản lý kho lưu trữ tài liệu số hóa của khách hàng (Identity Vault).
 * Lưu trữ metadata và đường dẫn đến các bản quét CCCD, GPKD, Hợp đồng mẫu...
 */
class CustomerDocumentModel extends BaseModel
{
    // 1. Cấu hình bảng
    protected $table            = 'customer_documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    // 2. Các trường thông tin tài liệu
    protected $allowedFields    = [
        'customer_id',   // ID khách hàng sở hữu tài liệu
        'document_type', // Loại: CCCD, GPKD, Passport...
        'file_name',     // Tên hiển thị của file
        'file_path',     // Đường dẫn vật lý trên server (uploads/...)
        'uploaded_by'    // ID người thực hiện tải lên
    ];

    // 3. Quản lý thời gian
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
