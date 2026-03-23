<?php

namespace App\Models;

class WorkflowStepModel extends BaseModel
{
    protected $table            = 'workflow_template_steps';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'template_id', 'step_order', 'step_name', 'duration_days', 
        'is_working_day_only', 'required_documents', 'responsible_role', 
        'next_step_condition', 'notification_template'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // JSON Casting emulation if needed, or handle in Service
}
