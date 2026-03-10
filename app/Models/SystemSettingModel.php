<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model quản lý cấu hình hệ thống
 * @package App\Models
 */
class SystemSettingModel extends Model
{
    protected $table            = 'system_settings';
    protected $primaryKey       = 'key';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['key', 'value', 'updated_at'];

    // Không sử dụng soft deletes cho cấu hình
    protected $useTimestamps = false;
}
