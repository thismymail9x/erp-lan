<?php

namespace App\Models;

/**
 * TagModel
 * 
 * Quản lý danh mục các thẻ (nhãn dán) trong hệ thống.
 * Hỗ trợ phân loại Thẻ chung (Global) và Thẻ cá nhân (Private).
 */
class TagModel extends BaseModel
{
    protected $table            = 'tags';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'name', 'color', 'type', 'owner_id', 'module_scope'
    ];

    // Validation
    protected $validationRules      = [
        'name' => 'required|min_length[2]|max_length[100]',
        'type' => 'required|in_list[global,private]',
    ];
}
