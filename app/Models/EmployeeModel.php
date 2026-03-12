<?php

namespace App\Models;

/**
 * EmployeeModel
 * 
 * Quản lý thông tin chi tiết hồ sơ nhân viên.
 */
class EmployeeModel extends BaseModel
{
    protected $table            = 'employees';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id', 'department_id', 'full_name', 'dob', 'identity_card', 
        'address', 'join_date', 'salary_base', 'position', 'bank_name', 'bank_account'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'full_name' => 'required|min_length[3]|max_length[255]',
        'position'  => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
