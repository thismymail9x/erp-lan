<?php

namespace App\Models;

/**
 * KnowledgeModel
 * 
 * Lưu trữ cẩm nang tri thức, bài học kinh nghiệm và kiến thức nghiệp vụ của nhân viên.
 */
class KnowledgeModel extends BaseModel
{
    protected $table            = 'knowledge_base';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields    = [
        'case_id', 'author_id', 'title', 'content', 'category', 
        'view_count', 'helpful_count', 'is_pinned'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Ràng buộc tính toàn vẹn của một bài viết Knowledge Base
    protected $validationRules      = [
        'author_id' => 'required|is_not_unique[employees.id]',
        'case_id'   => 'permit_empty|is_not_unique[cases.id]',
        'title'     => 'required|min_length[5]|max_length[255]',
        'content'   => 'required|min_length[10]',
        'category'  => 'required|in_list[case_study,skill,legal_update,general]'
    ];

    protected $validationMessages   = [
        'title' => [
            'required' => 'Tiêu đề bài viết không được để trống.',
            'min_length' => 'Tiêu đề quá ngắn, cần chi tiết hơn để dễ tìm kiếm.'
        ],
        'content' => [
            'required' => 'Nội dung chia sẻ không được để trống.',
            'min_length' => 'Nội dung quá ngắn.'
        ]
    ];
}
