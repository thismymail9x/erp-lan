<?php

namespace App\Models;

/**
 * ContactModel
 * 
 * Quản lý dữ liệu danh bạ liên hệ.
 * Kế thừa BaseModel để hỗ trợ Soft Delete và tự động chuyển chuỗi rỗng sang NULL.
 */
class ContactModel extends BaseModel
{
    protected $table      = 'contacts';
    protected $primaryKey = 'id';

    protected $returnType     = 'array';
    protected $allowedFields = [
        'source',
        'unit_name',
        'phone',
        'address',
        'position',
        'area',
        'reorganized_unit',
        'notes',
        'province',
        'is_private',
        'created_by'
    ];

    // Quy tắc validation (Tiếng Việt 100%)
    protected $validationRules = [
        'unit_name' => 'required|min_length[2]|max_length[255]',
        'phone'     => 'permit_empty|max_length[100]',
    ];

    protected $validationMessages = [
        'unit_name' => [
            'required'   => 'Tên đơn vị/Người phụ trách không được để trống.',
            'min_length' => 'Tên quá ngắn, vui lòng nhập ít nhất 2 ký tự.',
            'max_length' => 'Tên quá dài, tối đa 255 ký tự.'
        ],
        'phone' => [
            'max_length' => 'Số điện thoại không được vượt quá 100 ký tự.'
        ]
    ];
}
