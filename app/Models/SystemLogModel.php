<?php

namespace App\Models;

class SystemLogModel extends BaseModel
{
    protected $table            = 'system_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id', 'action', 'module', 'entity_id', 'details', 'ip_address', 'user_agent', 'created_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // No updated_at for logs
    protected $deletedField  = '';
}
