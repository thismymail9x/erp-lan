<?php

namespace App\Models;

/**
 * RoleModel
 * 
 * Quản lý các vai trò (chức danh) trong hệ thống.
 */
class RoleModel extends BaseModel
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = ['name', 'description'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deleted_at    = 'deleted_at';
}
