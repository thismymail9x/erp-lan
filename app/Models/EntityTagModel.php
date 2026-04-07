<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * EntityTagModel
 * 
 * Bảng nối (Bridge) đa hình dùng để gắn Tag vào Vụ việc, Khách hàng, Tài liệu...
 * Không sử dụng Timestamps để giữ cho bảng dữ liệu cực kỳ nhẹ và truy vấn nhanh.
 */
class EntityTagModel extends Model
{
    protected $table            = 'entity_tags';
    protected $primaryKey       = 'tag_id'; // Note: Thực tế là composite PK (tag_id, entity_id, entity_type)
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['tag_id', 'entity_id', 'entity_type'];

    // Disable timestamps for performance on bridge table
    protected $useTimestamps = false;
}
