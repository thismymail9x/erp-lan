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
    public function getCases(string $sort = 'id', string $order = 'desc', int $perPage = 20, string $search = '', array $lawyerIds = [], string $status = '', int $tagId = 0, int $month = 0, int $year = 0)
    {
        // 1. Phân tích bối cảnh người dùng (Authentication Context)
        $roleName = session()->get('role_name');
        $userId = session()->get('user_id');

        // 2. Chuyển đổi tên cột từ Giao diện sang Database (Mapping)
        $sortMap = [
            'code'     => 'cases.code',
            'title'    => 'cases.title',
            'customer' => 'customers.name',
            'lawyer'   => 'employees.full_name',
            'status'   => 'cases.status',
            'kpi'      => 'kpi_earned',
            'deadline' => 'cases.deadline',
            'id'       => 'cases.id'
        ];

        $orderField = $sortMap[$sort] ?? 'cases.id';
        $direction  = (strtolower($order) === 'asc') ? 'asc' : 'desc';

        // 3. Xây dựng Query Builder cốt lõi (Bổ sung kpi_earned & kpi_remaining)
        $isHanhChinhOrAdmin = ($roleName === \Config\AppConstants::ROLE_ADMIN || session()->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH);
        $selectData = '
            cases.id, 
            cases.customer_id, 
            cases.code, 
            cases.title, 
            cases.status, 
            cases.priority, 
            cases.assigned_lawyer_id, 
            cases.assigned_staff_id, 
            cases.deadline, 
            cases.current_step, 
            cases.created_at, 
            cases.updated_at,
            customers.name as customer_name, 
            employees.full_name as lawyer_name';
        
        if ($isHanhChinhOrAdmin) {
            $selectData .= ', cases.contract_value, cases.payment_progress';
        }

        // 3. Xây dựng Query Builder cốt lõi
        $query = $this->caseModel->select($selectData)
            ->select('(SELECT SUM(kpi_reward) FROM case_steps WHERE case_id = cases.id AND status = "completed") as kpi_earned')
            ->select('(SELECT SUM(kpi_reward) FROM case_steps WHERE case_id = cases.id AND status IN ("pending", "active", "pending_approval", "overdue")) as kpi_remaining')
            ->select('(SELECT MIN(deadline) FROM case_steps WHERE case_id = cases.id AND status IN ("active", "pending_approval") AND deadline IS NOT NULL) as step_deadline')
            ->select('(SELECT step_name FROM case_steps WHERE case_id = cases.id AND status IN ("active", "pending_approval") ORDER BY deadline ASC LIMIT 1) as current_step_name')
            ->join('customers', 'customers.id = cases.customer_id', 'left')
            ->join('employees', 'employees.id = cases.assigned_lawyer_id', 'left')
            ->groupBy('cases.id');

        // 4. Lọc theo trạng thái hồ sơ cụ thể (Nếu được yêu cầu)
        if (!empty($status)) {
            if ($status === 'overdue') {
                // Lọc các vụ việc ĐANG HOẠT ĐỘNG có bước công việc bị quá hạn
                $query->whereIn('cases.status', ['cho_tiep_nhan', 'dang_xu_ly'])
                      ->where('cases.id IN (SELECT DISTINCT case_id FROM case_steps WHERE completed_at IS NULL AND deadline < "'.date('Y-m-d H:i:s').'")');
            } elseif ($status === 'completed_month') {
                // Lọc vụ việc hoàn thành trong tháng
                $query->where('cases.status', 'da_hoan_thanh')
                      ->where('MONTH(cases.updated_at)', date('m'))
                      ->where('YEAR(cases.updated_at)', date('Y'));
            } else {
                $query->where('cases.status', $status);
            }
        }

        // 5. Lọc theo nhân viên phụ trách nếu có yêu cầu (Hỗ trợ lọc nhiều người cùng lúc)
        if (!empty($lawyerIds)) {
            $query->groupStart()
                ->whereIn('cases.assigned_lawyer_id', $lawyerIds)
                ->orWhereIn('cases.assigned_staff_id', $lawyerIds)
                ->orWhereIn('cases.id', function($builder) use ($lawyerIds) {
                    return $builder->select('case_id')->from('case_members')->whereIn('employee_id', $lawyerIds);
                })
            ->groupEnd();
        }

        // 6. Lọc theo nhãn dán (Tags)
        if ($tagId > 0) {
            $query->whereIn('cases.id', function($builder) use ($tagId) {
                return $builder->select('entity_id')
                               ->from('entity_tags')
                               ->where('tag_id', $tagId)
                               ->where('entity_type', 'cases');
            });
        }

        // 7. Lọc theo tháng/năm tiếp nhận
        if ($month > 0) {
            $query->where('MONTH(cases.created_at)', $month);
            // Nếu có tháng mà không có năm -> Mặc định lấy năm hiện tại
            if ($year <= 0) {
                $year = (int)date('Y');
            }
        }
        
        if ($year > 0) {
            $query->where('YEAR(cases.created_at)', $year);
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
        $this->applyAccessLimit($query);

        // 4. Thực thi truy vấn kèm theo Phân trang
        // Ưu tiên các vụ việc đang làm ('dang_xu_ly') lên đầu khi xem danh sách mặc định (Sắp xếp theo ID)
        if ($sort === 'id') {
            $query->orderBy('CASE WHEN cases.status = "dang_xu_ly" THEN 0 ELSE 1 END', 'ASC', false);
        }
        $cases = $query->orderBy($orderField, $direction)->paginate($perPage);

        // --- BỔ TÚC THÔNG TIN NHÂN SỰ & NHÃN DÁN (Data Enrichment) ---
        if (!empty($cases)) {
            $caseIds = array_column($cases, 'id');
            
            // 1. Lấy danh sách nhân sự tham gia (Các Member)
            $members = $this->caseModel->db->table('case_members')
                ->select('case_id, role_in_case, employees.full_name')
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
                // Tách riêng Người phụ trách (assignee)
                $case['assignees'] = array_column(
                    array_filter($members, fn($m) => $m['case_id'] == $case['id'] && in_array($m['role_in_case'], ['assignee', 'main'])),
                    'full_name'
                );

                // Lọc Người duyệt (approver)
                $case['approvers'] = array_column(
                    array_filter($members, fn($m) => $m['case_id'] == $case['id'] && $m['role_in_case'] === 'approver'),
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
        // 08/05/2026 kiểm tra chưa thấy sử dụng
        // 1. Lấy dữ liệu hồ sơ
        $case = $this->caseModel->find($id);
        if (!$case) {
            return $this->fail('Dữ liệu hồ sơ này không tồn tại hoặc đã bị xóa khỏi hệ thống.');
        }
        $roleName = session()->get('role_name');
        // Kiểm duyệt bảo mật dữ liệu nhạy cảm (Tài chính)
        $isHanhChinhOrAdmin = ($roleName === \Config\AppConstants::ROLE_ADMIN || session()->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH);
        if (!$isHanhChinhOrAdmin) {
            unset($case['contract_value'], $case['payment_progress']);
        }

        // 2. Kiểm duyệt quyền truy cập hồ sơ (Strict Access Check)

        $isHanhChinh = (session()->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH);
        if (!$this->accessControl->canViewAllData($roleName) && !$isHanhChinh) {
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

    /**
     * Tính toán bộ chỉ số thống kê (Stats) dựa trên quyền hạn của người dùng.
     */
    public function getStats()
    {
        $db = \Config\Database::connect();
        $baseQuery = $db->table('cases')->where('cases.deleted_at', null);

        $this->applyAccessLimit($baseQuery);

        return [
            'total'      => (clone $baseQuery)->select('cases.id')->countAllResults(),
            'processing' => (clone $baseQuery)->select('cases.id')->where('cases.status', 'dang_xu_ly')->countAllResults(),
            'waiting'    => (clone $baseQuery)->select('cases.id')->where('cases.status', 'cho_tiep_nhan')->countAllResults(),
            'completed'  => (clone $baseQuery)->select('cases.id')->where('cases.status', 'da_hoan_thanh')->countAllResults(),
            'overdue'    => (clone $baseQuery)->select('cases.id')
                                ->whereIn('cases.status', ['cho_tiep_nhan', 'dang_xu_ly'])
                                ->where('cases.id IN (SELECT DISTINCT case_id FROM case_steps WHERE completed_at IS NULL AND deadline < "'.date('Y-m-d H:i:s').'")')
                                ->countAllResults()
        ];
    }

    /**
     * Cơ chế Phân quyền dữ liệu (Security Scoping) dùng chung.
     */
    private function applyAccessLimit(&$query)
    {
        $session = session();
        $roleName = $session->get('role_name');
        $employeeId = $session->get('employee_id');

        // Nếu là Admin/Mod hoặc người có quyền xem toàn hệ thống hoặc là phòng ban Hành chính Kế toán -> Trả về luôn để lấy Full data
        if ($this->accessControl->canViewAllData($roleName) || $session->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH) {
            return;
        }

        if ($employeeId) {
            $employeeModel = model('EmployeeModel');
            $employee = $employeeModel->find($employeeId);
            
            if ($employee) {
                // LOGIC TRƯỞNG PHÒNG: Xem của bản thân + nhân viên cấp dưới (Team)
                if (strpos(strtolower($roleName), 'trưởng phòng') !== false) {
                    $myEmpId = $employee['id'];
                    $myDeptId = $employee['department_id'];
                    $myTeamIds = $employeeModel->where('manager_id', $myEmpId)->findColumn('id') ?? [];
                    $myTeamIds[] = $myEmpId;

                    $query->groupStart();
                        $query->groupStart()
                            ->whereIn('cases.assigned_lawyer_id', $myTeamIds)
                            ->orWhereIn('cases.assigned_staff_id', $myTeamIds)
                            ->orWhereIn('cases.id', function($builder) use ($myTeamIds) {
                                return $builder->select('case_id')->from('case_members')->whereIn('employee_id', $myTeamIds);
                            })
                        ->groupEnd();

                        // Trưởng phòng Pháp lý: Thấy thêm hồ sơ chưa được gán để tiếp nhận
                        if ($myDeptId == \Config\AppConstants::DEPT_PHAP_LY) {
                            $query->orGroupStart()
                                ->where('cases.assigned_lawyer_id', null)
                                ->where('cases.assigned_staff_id', null)
                            ->groupEnd();
                        }
                    $query->groupEnd();
                } else {
                    // LOGIC NHÂN VIÊN: Chỉ thấy hồ sơ được phân công trực tiếp hoặc có tham gia (Member)
                    $caseIds = model('CaseMemberModel')->where('employee_id', $employee['id'])->findColumn('case_id');
                    $query->groupStart()
                          ->where('cases.assigned_staff_id', $employee['id'])
                          ->orWhere('cases.assigned_lawyer_id', $employee['id']);
                    if (!empty($caseIds)) {
                        $query->orWhereIn('cases.id', $caseIds);
                    }
                    $query->groupEnd();
                }
            } else {
                $query->where('1 = 0');
            }
        } else {
            $query->where('1 = 0'); 
        }
    }
}
