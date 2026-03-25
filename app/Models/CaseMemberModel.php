<?php

namespace App\Models;

/**
 * CaseMemberModel
 * 
 * Lớp quản trị Danh sách nhân sự tham gia xử lý vụ việc pháp lý (Case Team Management).
 * 
 * Mô hình vận hành: Một vụ việc sẽ được chia thành 3 nhóm quyền hạn chính:
 * 1. Approver (Người duyệt): Trình duyệt/Ký duyệt các bước quan trọng, thường là ban giám định hoặc quản lý.
 * 2. Assignee (Luật sư/Nhân viên chính): Người chịu trách nhiệm thực hiện chính các tác vụ trong timeline.
 * 3. Supporter (Người hỗ trợ): Trợ lý hoặc nhân sự phối hợp thực hiện các phần việc phụ trợ.
 */
class CaseMemberModel extends BaseModel
{
    // Cấu hình hạ tầng bảng
    protected $table            = 'case_members';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    // Các trường dữ liệu cho phép biến động (White-listed fields)
    protected $allowedFields    = [
        'case_id',      // ID Hồ sơ vụ việc
        'employee_id',  // ID Hồ sơ nhân sự
        'role_in_case', // Vai trò: approver, assignee, supporter
        'created_at'    // Thời điểm gán quyền
    ];

    // Cơ chế quản lý thời gian hồ sơ
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // Bảng này không dùng updated_at vì mỗi lần đổi là sync lại (xóa đi nạp lại)
    
    /**
     * Truy xuất toàn bộ thành viên đang tham gia một vụ việc.
     * Kết hợp (Join) với bảng Nhân sự và Tài khoản để lấy đầy đủ hồ sơ hiển thị trên Front-end.
     * 
     * @param int $caseId ID của hồ sơ vụ việc cần tra cứu.
     * @return array Danh sách thành viên được phân nhóm theo vai trò.
     */
    public function getMembersByCase(int $caseId)
    {
        // Thực hiện truy vấn để lấy thông tin thành viên vụ việc
        return $this->select('case_members.*, employees.full_name, employees.position, users.role_id')
                    // Kết nối với bảng 'employees' để lấy thông tin chi tiết nhân sự
                    ->join('employees', 'employees.id = case_members.employee_id') 
                    // Kết nối với bảng 'users' (nếu có) để lấy thông tin tài khoản và vai trò người dùng
                    ->join('users', 'users.id = employees.user_id', 'left')       
                    // Lọc theo ID vụ việc cụ thể
                    ->where('case_members.case_id', $caseId)
                    ->orderBy('case_members.role_in_case', 'ASC')
                    ->findAll();
    }
    
    /**
     * Quy trình ĐỒNG BỘ HÓA thành viên (Sync Orchestration).
     * 
     * Hậu phương (Infrastructure logic): 
     * 1. Sử dụng Database Transaction để đảm bảo tính toàn vẹn (Không bao giờ xảy ra vụ việc có 0 thành viên do lỗi giữa chừng).
     * 2. Xóa bỏ cấu trúc cũ (Purge) và nạp cấu trúc mới (Insert) theo danh sách ID người thực dùng cung cấp.
     */
    public function syncMembers(int $caseId, string $role, array $employeeIds)
    {
        $db = \Config\Database::connect();
        $db->transStart(); // Mở giao dịch CSDL (Transaction)
        
        // Bước 1: Thu hồi toàn bộ quyền hạn cũ của nhóm vai trò này trong hồ sơ
        $this->where('case_id', $caseId)->where('role_in_case', $role)->delete();
        
        // Bước 2: Tái khởi tạo quyền hạn cho danh sách nhân viên mới
        foreach ($employeeIds as $empId) {
            if ($empId) {
                $this->insert([
                    'case_id' => $caseId,
                    'employee_id' => $empId,
                    'role_in_case' => $role
                ]);
            }
        }
        
        $db->transComplete(); // Hoàn tất và chốt kết quả (Commit)
        
        // Trả về trạng thái thành công/thất bại của phiên làm việc
        return $db->transStatus();
    }
}
