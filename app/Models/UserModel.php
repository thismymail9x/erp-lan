<?php

namespace App\Models;

/**
 * UserModel
 * 
 * Quản lý dữ liệu người dùng trong hệ thống ERP.
 */
class UserModel extends BaseModel
{
    protected $table      = 'users';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'role_id', 
        'email', 
        'password', 
        'active_status'
    ];

    // Các quy tắc xác thực dữ liệu (Validation)
    protected $validationRules = [
        'email'    => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'required|min_length[6]',
        'role_id'  => 'required|is_not_unique[roles.id]',
    ];

    protected $validationMessages = [
        'email' => [
            'is_unique' => 'Email này đã được sử dụng.',
            'required'  => 'Email là bắt buộc.',
            'valid_email' => 'Email không hợp lệ.'
        ],
        'password' => [
            'min_length' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'required'   => 'Mật khẩu là bắt buộc.'
        ]
    ];
}
