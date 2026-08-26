<?php

namespace App\Models;

class CasePartnerModel extends BaseModel
{
    protected $table = 'case_partners';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'case_id',
        'partner_id',
        'role_label',
        'calculation_base',
        'percentage',
        'fixed_amount',
        'status',
        'notes',
    ];

    protected $validationRules = [
        'case_id' => 'required|numeric',
        'partner_id' => 'required|numeric',
        'calculation_base' => 'required|in_list[contract,paid]',
        'status' => 'permit_empty|in_list[active,paused,ended]',
    ];
}
