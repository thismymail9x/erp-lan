<?php

namespace App\Models;

use CodeIgniter\Model;

class ZaloMessageModel extends Model
{
    protected $table            = 'zalo_messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'zalo_msg_id', 'follower_id', 'sender_type', 'message_text', 
        'attachments', 'is_read', 'created_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
