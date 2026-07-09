<?php

namespace App\Models;

class PayrollModel extends BaseModel
{
    protected $table            = 'payrolls';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'employee_id', 'month', 'salary_base', 'insurance_salary', 'salary_kpi', 
        'diligence_allowance', 'petrol_allowance',
        'salary_bonus', 'salary_deduction', 'salary_other', 'notes_json',
        'total_standard_days', 'salary_per_day', 'actual_working_days',
        // Ngày công bù thủ công và snapshot hệ số lương tại thời điểm tính
        'manual_adjust_days', 'probation_rate_snapshot',
        'taxable_income', 'attendance_violations', 'si_employer', 'si_employee',
        'dependent_deduction', 'pit_tax', 'total_deductions', 'net_salary', 'status'
    ];
    protected $useTimestamps = true;
}
