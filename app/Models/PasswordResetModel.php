<?php

namespace App\Models;

/**
 * PasswordResetModel
 * 
 * Quản lý các mã xác thực đặt lại mật khẩu.
 */
class PasswordResetModel extends BaseModel
{
    protected $table      = 'password_resets';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'email',
        'token',
        'created_at',
        'expires_at'
    ];

    protected $useTimestamps = false; // Chúng ta sẽ tự quản lý created_at
}
