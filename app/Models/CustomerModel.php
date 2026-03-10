<?php

namespace App\Models;

/**
 * CustomerModel
 * 
 * Quản lý thông tin khách hàng.
 */
class CustomerModel extends BaseModel
{
    protected $table            = 'customers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name', 'type', 'tax_code', 'representative', 
        'phone', 'email', 'address'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'name'  => 'required|min_length[3]',
        'email' => 'permit_empty|valid_email',
    ];
}
