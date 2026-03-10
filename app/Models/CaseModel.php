<?php

namespace App\Models;

/**
 * CaseModel
 * 
 * Quản lý các vụ việc pháp lý.
 */
class CaseModel extends BaseModel
{
    protected $table            = 'cases';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'customer_id', 'title', 'internal_code', 'description', 
        'status', 'priority', 'assigned_lawyer_id', 'start_date', 'end_date'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'customer_id'   => 'required|is_not_unique[customers.id]',
        'title'         => 'required|min_length[3]',
        'internal_code' => 'required|is_unique[cases.internal_code,id,{id}]',
    ];
}
