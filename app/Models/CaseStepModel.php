<?php

namespace App\Models;

/**
 * CaseStepModel
 * 
 * Quản lý các bước cụ thể trong quy trình xử lý hồ sơ (Timeline & Deadline Tracker).
 * Đóng vai trò kiểm soát tiến độ và nhắc hẹn cho từng giai đoạn của vụ việc.
 */
class CaseStepModel extends BaseModel
{
    // 1. Cấu hình bảng
    protected $table            = 'case_steps';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    // 2. Các trường thông tin về bước quy trình
    protected $allowedFields    = [
        'case_id', 'template_id', 'template_step_id', 'step_name', 
        'duration_days', 'is_working_day_only', 'deadline', 
        'completed_at', 'status', 'sort_order', 'required_documents',
        'responsible_role', 'next_step_condition', 'notification_template'
    ];

    // 3. Quản lý thời gian
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Lấy bước đang thực hiện (chưa hoàn thành) của một vụ việc
     * 
     * @param int $caseId ID vụ việc
     * @return array|null Trình trả về bước đầu tiên chưa xong theo thứ tự ưu tiên
     */
    public function getCurrentStep(int $caseId)
    {
        return $this->where('case_id', $caseId)
                    ->where('completed_at', null)
                    ->orderBy('sort_order', 'ASC')
                    ->first();
    }
}
