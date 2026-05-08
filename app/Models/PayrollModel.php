<?php

namespace App\Models;

class PayrollModel extends BaseModel
{
    protected $table            = 'payrolls';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'employee_id', 'month', 'salary_base', 'salary_kpi', 'salary_allowance', 
        'salary_bonus', 'salary_deduction', 'salary_other', 'notes_json',
        'total_standard_days', 'actual_working_days', 'attendance_violations', 
        'net_salary', 'status', 'notes'
    ];
    protected $useTimestamps = true;
}
