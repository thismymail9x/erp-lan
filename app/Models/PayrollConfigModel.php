<?php

namespace App\Models;

class PayrollConfigModel extends BaseModel
{
    protected $table            = 'payroll_configs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'month', 'working_days_json', 'holidays_json', 
        'total_standard_days', 'is_closed'
    ];
    protected $useTimestamps = true;
}
