<?php

namespace App\Services;

use App\Models\CaseModel;

/**
 * CaseService
 * 
 * Lớp dịch vụ quản lý Nghiệp vụ Vụ việc & Hồ sơ pháp lý.
 * Vai trò chính:
 * 1. Thực hiện các truy vấn dữ liệu phức tạp đi kèm với cơ chế Phân quyền dữ liệu (Data Scoping).
 * 2. Đảm bảo tính bảo mật: Nhân viên chỉ thấy hồ sơ họ tham gia, Quản lý thấy hồ sơ phòng ban/toàn cục.
 * 3. Hỗ trợ các tính năng lọc (Filtering), Sắp xếp (Sorting) và Phân trang (Pagination) ở cấp độ Logic.
 */
class CaseService extends BaseService
{
    protected $caseModel;
    protected $accessControl;

    public function __construct()
    {
        parent::__construct();
        // Khởi tạo các thành phần hỗ trợ: Model dữ liệu và Service kiểm soát truy cập (ACL)
        $this->caseModel = new CaseModel();
        $this->accessControl = new AccessControlService();
    }

    /**
     * Truy xuất danh sách Vụ việc với cơ chế lọc thông minh.
     * 
     * @param string $sort Tiêu chí sắp xếp.
     * @param string $order Hướng sắp xếp (Tăng/Giảm).
     * @param int $perPage Số lượng bản ghi cho phân trang.
     * @param string $search Từ khóa tìm kiếm đa năng.
     * @return array Danh sách vụ việc đã được lọc và làm sạch dữ liệu.
     */
    public function getCases(string $sort = 'id', string $order = 'desc', int $perPage = 10, string $search = '')
    {
        // 1. Phân tích bối cảnh người dùng (Authentication Context)
        $roleName = session()->get('role_name');
        $userId = session()->get('user_id');

        // 2. Chuyển đổi tên cột từ Giao diện sang Database (Mapping)
        $sortMap = [
            'code'     => 'cases.code',
            'title'    => 'cases.title',
            'customer' => 'customers.name',
            'type'     => 'cases.type',
            'lawyer'   => 'employees.full_name',
            'status'   => 'cases.status',
            'deadline' => 'cases.deadline',
            'id'       => 'cases.id'
        ];

        $orderField = $sortMap[$sort] ?? 'cases.id';
        $direction  = (strtolower($order) === 'asc') ? 'asc' : 'desc';

        // 3. Xây dựng Query Builder cốt lõi
        $query = $this->caseModel->select('cases.*, customers.name as customer_name, current_step.step_name as current_step_name, current_step.deadline as step_deadline')
                        ->join('customers', 'customers.id = cases.customer_id') // Join lấy thông tin chủ sở hữu (Khách hàng)
                        // Join lấy Bước (Step) hiện tại đang ở trạng thái Cần xử lý hoặc Chờ duyệt
                        ->join('case_steps as current_step', "current_step.case_id = cases.id AND current_step.status IN ('active', 'pending_approval')", 'left')
                        ->groupBy('cases.id'); // Đảm bảo tính duy nhất của bản ghi sau các phép Join

        // --- BỘ LỌC TÌM KIẾM (Search Engine) ---
        if (!empty($search)) {
            $query->groupStart()
                  ->like('cases.title', $search)   // Tiêu đề vụ việc
                  ->orLike('cases.code', $search)  // Mã hồ sơ nội bộ
                  ->orLike('customers.name', $search) // Tên đối tác/khách hàng
                  ->groupEnd();
        }

        // --- CƠ CHẾ PHÂN QUYỀN DỮ LIỆU (Security Scoping) ---
        // Nếu User không thuộc nhóm quyền Quản trị tối cao
        if (!$this->accessControl->canViewAllData($roleName)) {
            $employeeModel = model('EmployeeModel');
            $employee = $employeeModel->where('user_id', $userId)->first();
            
            if ($employee) {
                // Xác định danh sách ID các vụ việc mà nhân viên này tham gia với tư cách thành viên nhóm
                $caseIds = model('CaseMemberModel')->where('employee_id', $employee['id'])->findColumn('case_id');

                // Chỉ hiển thị:
                // 1. Là nhân viên/trợ ký được giao chính.
                // 2. Là luật sư phụ trách trực tiếp.
                // 3. Có tên trong danh sách thành viên tham gia (Case Member).
                $query->groupStart()
                      ->where('cases.assigned_staff_id', $employee['id'])
                      ->orWhere('cases.assigned_lawyer_id', $employee['id']);
                
                if (!empty($caseIds)) {
                    $query->orWhereIn('cases.id', $caseIds);
                }
                
                $query->groupEnd();
            } else {
                // TRƯỜNG HỢP AN TOÀN: Nếu không tìm thấy hồ sơ nhân sự liên kết -> Chặn mọi dữ liệu.
                return [];
            }
        }

        // 4. Thực thi truy vấn kèm theo Phân trang
        $cases = $query->orderBy($orderField, $direction)->paginate($perPage);

        // --- BỔ TÚC THÔNG TIN NHÂN SỰ (Data Enrichment) ---
        // Chuyển đổi Lawyer ID thành Danh sách tên các nhân sự đang thực hiện (Assignees) để hiển thị đầy đủ trên UI.
        if (!empty($cases)) {
            $caseIds = array_column($cases, 'id');
            // Lấy toàn bộ danh sách nhân sự tham gia xử lý cho tất cả vụ việc trong trang hiện tại
            $assignees = $this->caseModel->db->table('case_members')
                ->select('case_id, employees.full_name')
                ->join('employees', 'employees.id = case_members.employee_id')
                ->whereIn('case_id', $caseIds)
                ->where('role_in_case', 'assignee')
                ->get()->getResultArray();
            
            $assigneeMap = [];
            foreach ($assignees as $a) {
                $assigneeMap[$a['case_id']][] = $a['full_name'];
            }
            
            // Ghi đè thông tin hiển thị Lawyer cho đẹp trên giao diện danh sách
            foreach ($cases as &$case) {
                if (isset($assigneeMap[$case['id']])) {
                    $case['lawyer_name'] = implode(', ', $assigneeMap[$case['id']]);
                } else {
                    $case['lawyer_name'] = 'Chưa có người xử lý';
                }
            }
        }

        return $cases;
    }

    /**
     * Cung cấp thư viện Phân trang (Pager Service).
     */
    public function getPager()
    {
        return $this->caseModel->pager;
    }

    /**
     * Truy xuất thông tin chi tiết một Vụ việc kèm theo Kiểm tra độc lập quyền truy cập.
     * Sử dụng để bảo vệ các API hoặc View chi tiết khỏi truy cập trái phép qua URL.
     * 
     * @param int $id ID hồ sơ.
     * @return mixed Bản ghi vụ việc hoặc Response lỗi.
     */
    public function getCaseDetails(int $id)
    {
        // 1. Lấy dữ liệu hồ sơ
        $case = $this->caseModel->find($id);
        if (!$case) {
            return $this->fail('Dữ liệu hồ sơ này không tồn tại hoặc đã bị xóa khỏi hệ thống.');
        }

        // 2. Kiểm duyệt quyền truy cập (Strict Access Check)
        $roleName = session()->get('role_name');
        if (!$this->accessControl->canViewAllData($roleName)) {
            $userId = session()->get('user_id');
            $employee = model('EmployeeModel')->where('user_id', $userId)->first();
            
            if (!$employee) {
                return $this->fail('Tài khoản của bạn chưa được liên kết với hồ sơ nhân sự để xem dữ liệu này.');
            }

            // Kiểm tra Logic: Phải là Người giao, Người nhận, hoặc Thành viên trong ban vụ việc
            if ($case['assigned_lawyer_id'] != $employee['id'] && $case['assigned_staff_id'] != $employee['id']) {
                $isMember = model('CaseMemberModel')->where('case_id', $id)->where('employee_id', $employee['id'])->first();
                if (!$isMember) {
                    return $this->fail('Bạn không nằm trong đội ngũ tham gia xử lý vụ việc này (Access Denied).');
                }
            }
        }

        return $this->success($case);
    }
}
