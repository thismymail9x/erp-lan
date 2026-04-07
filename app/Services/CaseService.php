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
    public function getCases(string $sort = 'id', string $order = 'desc', int $perPage = 10, string $search = '', array $lawyerIds = [])
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
        $query = $this->caseModel->select('cases.*, customers.name as customer_name, employees.full_name as lawyer_name, current_step.step_name as current_step_name, current_step.deadline as step_deadline')
            ->join('customers', 'customers.id = cases.customer_id', 'left')
            ->join('employees', 'employees.id = cases.assigned_lawyer_id', 'left')
            ->join('case_steps as current_step', "current_step.case_id = cases.id AND current_step.status IN ('active', 'pending_approval')", 'left')
            ->groupBy('cases.id');

        // 4. Lọc theo nhân viên phụ trách nếu có yêu cầu (Hỗ trợ lọc nhiều người cùng lúc)
        if (!empty($lawyerIds)) {
            $query->groupStart()
                ->whereIn('cases.assigned_lawyer_id', $lawyerIds)
                ->orWhereIn('cases.assigned_staff_id', $lawyerIds)
                ->orWhereIn('cases.id', function($builder) use ($lawyerIds) {
                    return $builder->select('case_id')->from('case_members')->whereIn('employee_id', $lawyerIds);
                })
            ->groupEnd();
        }

        // 5. Tìm kiếm đa năng (Mã, Tên, Khách hàng, Luật sư)
        if (!empty($search)) {
            $query->groupStart()
                ->like('cases.code', $search)
                ->orLike('cases.title', $search)
                ->orLike('customers.name', $search)
                ->orLike('employees.full_name', $search)
            ->groupEnd();
        }

        // --- CƠ CHẾ PHÂN QUYỀN DỮ LIỆU (Security Scoping) ---
        // Nếu User không thuộc nhóm quyền Quản trị tối cao (Admin/Mod)
        if (!$this->accessControl->canViewAllData($roleName)) {
            $employeeModel = model('EmployeeModel');
            $employee = $employeeModel->where('user_id', $userId)->first();
            
            if ($employee) {
                if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
                    $myEmpId = $employee['id'];
                    $myDeptId = $employee['department_id'];

                    // 1. Phân định quân số: Lấy danh sách nhân viên báo cáo trực tiếp
                    $myTeamIds = $employeeModel->where('manager_id', $myEmpId)->findColumn('id') ?? [];
                    $myTeamIds[] = $myEmpId; // Bao gồm cả sếp

                    $query->groupStart();
                        // A. Dữ liệu của TEAM mình
                        $query->groupStart()
                            ->whereIn('cases.assigned_lawyer_id', $myTeamIds)
                            ->orWhereIn('cases.assigned_staff_id', $myTeamIds)
                            ->orWhereIn('cases.id', function($builder) use ($myTeamIds) {
                                return $builder->select('case_id')->from('case_members')->whereIn('employee_id', $myTeamIds);
                            })
                        ->groupEnd();

                        // B. NGOẠI LỆ: Sếp Pháp lý xem thêm hồ sơ mồ côi để quản trị
                        if ($myDeptId == \Config\AppConstants::DEPT_PHAP_LY) {
                            $query->orGroupStart()
                                ->where('cases.assigned_lawyer_id', null)
                                ->where('cases.assigned_staff_id', null)
                            ->groupEnd();
                        }
                    $query->groupEnd();
                } else {
                    /**
                     * NHÂN VIÊN: Chỉ thấy hồ sơ cá nhân (Chính thức hoặc tham gia hỗ trợ).
                     */
                    $caseIds = model('CaseMemberModel')->where('employee_id', $employee['id'])->findColumn('case_id');
                    $query->groupStart()
                          ->where('cases.assigned_staff_id', $employee['id'])
                          ->orWhere('cases.assigned_lawyer_id', $employee['id']);
                    if (!empty($caseIds)) $query->orWhereIn('cases.id', $caseIds);
                    $query->groupEnd();
                }
            } else {
                return [];
            }
        }

        // 4. Thực thi truy vấn kèm theo Phân trang
        $cases = $query->orderBy($orderField, $direction)->paginate($perPage);

        // --- BỔ TÚC THÔNG TIN NHÂN SỰ & NHÃN DÁN (Data Enrichment) ---
        if (!empty($cases)) {
            $caseIds = array_column($cases, 'id');
            
            // 1. Lấy danh sách nhân sự tham gia (Assignees)
            $assignees = $this->caseModel->db->table('case_members')
                ->select('case_id, employees.full_name')
                ->join('employees', 'employees.id = case_members.employee_id')
                ->whereIn('case_id', $caseIds)
                ->get()->getResultArray();

            // 2. Lấy danh sách Nhãn dán (Tags)
            $tags = $this->caseModel->db->table('entity_tags')
                ->select('entity_tags.entity_id, tags.name, tags.color')
                ->join('tags', 'tags.id = entity_tags.tag_id')
                ->where('entity_type', 'cases')
                ->whereIn('entity_tags.entity_id', $caseIds)
                ->get()->getResultArray();

            // Ánh xạ dữ liệu vào từng vụ việc
            foreach ($cases as &$case) {
                $case['assignees'] = array_column(
                    array_filter($assignees, fn($a) => $a['case_id'] == $case['id']),
                    'full_name'
                );
                $case['tags'] = array_filter($tags, fn($t) => $t['entity_id'] == $case['id']);
                
                // Ghi đè thông tin hiển thị Lawyer cho đẹp trên giao diện danh sách
                if (!empty($case['assignees'])) {
                    $case['lawyer_name'] = implode(', ', $case['assignees']);
                } else {
                    $case['lawyer_name'] = 'Trống';
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

            // KIỂM TRA QUYỀN TRUY CẬP TỔ ĐỘI (Team Isolation Check)
            // 1. Phân định: Tôi có phải là sếp của những người phụ trách vụ này không?
            $myTeamIds = model('EmployeeModel')->where('manager_id', $employee['id'])->findColumn('id') ?? [];
            $myTeamIds[] = $employee['id']; // Tôi cũng là một thành viên

            $isAssignedToMyTeam = (in_array($case['assigned_lawyer_id'], $myTeamIds) || in_array($case['assigned_staff_id'], $myTeamIds));
            
            // 2. Kiểm tra tư cách thành viên ban vụ việc
            $isMember = model('CaseMemberModel')->where('case_id', $id)->whereIn('employee_id', $myTeamIds)->first();
            
            // 3. Ngoại lệ cho hồ sơ mồ côi (Legal Manager only)
            $isUnassignedLegal = ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG && $employee['department_id'] == \Config\AppConstants::DEPT_PHAP_LY && $case['assigned_lawyer_id'] == null && $case['assigned_staff_id'] == null);

            if (!$isAssignedToMyTeam && !$isMember && !$isUnassignedLegal) {
                return $this->fail('Bạn không có quyền truy cập hồ sơ này (Access Denied - Team Isolation).');
            }
        }

        return $this->success($case);
    }
}
