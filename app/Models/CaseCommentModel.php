<?php

namespace App\Models;

/**
 * CaseCommentModel
 * 
 * Lưu trữ các bình luận và ghi chú nội bộ của nhân viên về vụ việc.
 */
/**
 * CaseCommentModel
 * 
 * Lưu trữ các thảo luận, ghi chú nghiệp vụ và trao đổi nội bộ giữa các nhân sự.
 * Giúp cộng tác hiệu quả trên cùng một hồ sơ vụ việc.
 */
class CaseCommentModel extends BaseModel
{
    // 1. Cấu hình bảng
    protected $table            = 'case_comments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    // 2. Các trường thông tin bình luận
    protected $allowedFields    = [
        'case_id',     // ID vụ việc thảo luận
        'user_id',     // ID nhân viên viết bình luận
        'content',     // Nội dung ghi chú/thảo luận
        'is_internal'  // Đánh dấu nội bộ (Chỉ nhân viên mới thấy)
    ];

    // 3. Quản lý thời gian
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Lấy toàn bộ thảo luận của một vụ việc kèm theo tên nhân viên
     * 
     * @param int $caseId ID của hồ sơ cần lấy bình luận
     * @return array Danh sách bình luận sắp xếp theo thứ tự thời gian tăng dần
     */
    public function getCommentsByCase(int $caseId)
    {
        return $this->select('case_comments.*, employees.full_name as user_name')
                    ->join('employees', 'employees.user_id = case_comments.user_id', 'left')
                    ->where('case_id', $caseId)
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
}
