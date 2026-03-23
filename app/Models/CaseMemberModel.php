<?php

namespace App\Models;

/**
 * CaseMemberModel
 * 
 * Quản lý danh sách nhân sự tham gia xử lý vụ việc pháp lý.
 * Gồm 3 nhóm quyền hạn chính: approver (Người duyệt), assignee (Luật sư chính), supporter (Người hỗ trợ).
 */
class CaseMemberModel extends BaseModel
{
    // 1. Cấu hình bảng
    protected $table            = 'case_members';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    // 2. Các trường thông tin cho phép thêm
    protected $allowedFields    = [
        'case_id', 'employee_id', 'role_in_case', 'created_at'
    ];

    // 3. Quản lý thời gian
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    
    /**
     * Lấy toàn bộ nhân sự theo từng nhóm quyền hạn của một vụ việc.
     */
    public function getMembersByCase(int $caseId)
    {
        return $this->select('case_members.*, employees.full_name, employees.position, users.role_id')
                    ->join('employees', 'employees.id = case_members.employee_id')
                    ->join('users', 'users.id = employees.user_id', 'left')
                    ->where('case_members.case_id', $caseId)
                    ->orderBy('case_members.role_in_case', 'ASC')
                    ->findAll();
    }
    
    /**
     * Đồng bộ hóa danh sách thành viên cho 1 nhóm quyền hạn của 1 vụ việc
     */
    public function syncMembers(int $caseId, string $role, array $employeeIds)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        // Xóa những người cũ thuộc role này
        $this->where('case_id', $caseId)->where('role_in_case', $role)->delete();
        
        // Thêm danh sách mới
        foreach ($employeeIds as $empId) {
            if ($empId) {
                $this->insert([
                    'case_id' => $caseId,
                    'employee_id' => $empId,
                    'role_in_case' => $role
                ]);
            }
        }
        
        $db->transComplete();
        return $db->transStatus();
    }
}
