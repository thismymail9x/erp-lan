<?php

namespace App\Models;

/**
 * ZnsTemplateModel
 * 
 * Quản lý mẫu tin ZNS (Zalo Notification Service).
 * Mỗi bản ghi tương ứng với một template đã được Zalo phê duyệt.
 */
class ZnsTemplateModel extends BaseModel
{
    protected $table            = 'zns_templates';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'template_id', 'template_name', 'template_content', 'template_params',
        'default_mappings', 'status', 'created_by'
    ];
    protected $returnType       = 'array';

    /**
     * Lấy danh sách template đang hoạt động (chưa bị xóa mềm, status = active)
     */
    public function getActiveTemplates()
    {
        return $this->where('status', 'active')
                    ->orderBy('template_name', 'ASC')
                    ->findAll();
    }
}
