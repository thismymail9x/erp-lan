<?php

namespace App\Models;

use CodeIgniter\Model;

class ZaloFollowerModel extends Model
{
    protected $table            = 'zalo_followers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'zalo_id', 'display_name', 'avatar_url', 'phone_number', 'email',
        'mid_code', 'customer_id', 'assigned_to', 'tags', 'lead_warmth', 'is_duplicate', 'duplicate_of', 
        'assigned_at', 'first_response_deadline', 'first_responded_at', 'is_overdue', 
        'ongoing_response_deadline', 'last_customer_msg_at', 'ongoing_is_overdue',
        'created_at', 'updated_at', 'deleted_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
