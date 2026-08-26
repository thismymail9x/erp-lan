<?php

namespace App\Models;

class PartnerModel extends BaseModel
{
    protected $table = 'partners';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'user_id',
        'name',
        'partner_type',
        'phone',
        'email',
        'tax_code',
        'bank_name',
        'bank_account',
        'bank_owner',
        'status',
        'notes',
    ];

    protected $validationRules = [
        'name' => 'required|min_length[2]|max_length[255]',
        'status' => 'permit_empty|in_list[active,paused,ended]',
    ];
}
