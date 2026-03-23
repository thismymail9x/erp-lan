<?php

namespace App\Models;

class WorkflowTemplateModel extends BaseModel
{
    protected $table            = 'workflow_templates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = ['code', 'name', 'case_type', 'version', 'is_active', 'total_estimated_days', 'created_by'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'code' => 'required|is_unique[workflow_templates.code,id,{id}]',
        'name' => 'required|min_length[3]',
    ];
}
