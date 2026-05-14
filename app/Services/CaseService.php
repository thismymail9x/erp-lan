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
    public function getCases(string $sort = 'id', string $order = 'desc', int $perPage = 20, string $search = '', array $lawyerIds = [], string $status = '', int $tagId = 0, int $month = 0, int $year = 0, string $paymentStatus = '', bool $isFinance = false)
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
            ->select('(SELECT SUM(kpi_reward) FROM case_steps WHERE case_id = cases.id AND status = "completed" AND deleted_at IS NULL) as kpi_earned')
            ->select('(SELECT SUM(kpi_reward) FROM case_steps WHERE case_id = cases.id AND status IN ("pending", "active", "pending_approval", "overdue") AND deleted_at IS NULL) as kpi_remaining')
            ->select('(SELECT MIN(deadline) FROM case_steps WHERE case_id = cases.id AND status IN ("active", "pending_approval") AND deadline IS NOT NULL AND deleted_at IS NULL) as step_deadline')
            ->select('(SELECT step_name FROM case_steps WHERE case_id = cases.id AND status IN ("active", "pending_approval") AND deleted_at IS NULL ORDER BY deadline ASC LIMIT 1) as current_step_name')
            ->join('customers', 'customers.id = cases.customer_id', 'left')
            ->join('employees', 'employees.id = cases.assigned_lawyer_id', 'left')
            ->groupBy('cases.id');

        // 4. Áp dụng cơ chế Phân quyền dữ liệu (Security Scoping)
        $this->applyAccessLimit($query);

        // 5. Lọc theo trạng thái hồ sơ cụ thể (Nếu được yêu cầu)
        if (!empty($status)) {
            if ($status === 'overdue') {
                // Lọc các vụ việc ĐANG HOẠT ĐỘNG có bước công việc bị quá hạn
                $query->whereIn('cases.status', ['cho_tiep_nhan', 'dang_xu_ly']);
                
                if (!empty($lawyerIds)) {
                    $idsStr = implode(',', array_map('intval', $lawyerIds));
                    $query->where("cases.id IN (SELECT DISTINCT case_id FROM case_steps WHERE status = 'active' AND deadline < '".date('Y-m-d H:i:s')."' AND assigned_to IN ($idsStr) AND deleted_at IS NULL)");
                } else {
                    $query->where("cases.id IN (SELECT DISTINCT case_id FROM case_steps WHERE status = 'active' AND deadline < '".date('Y-m-d H:i:s')."' AND deleted_at IS NULL)");
                }
            } elseif ($status === 'missed_kpi') {
                // Lọc vụ việc có bước bị muộn (đã xong hoặc chưa xong)
                $targetIds = !empty($lawyerIds) ? $lawyerIds : [];
                $now = date('Y-m-d H:i:s');
                
                if (empty($targetIds)) {
                    // Nếu không truyền lawyer_id: Kiểm tra quyền để quyết định xem "tất cả bị muộn" hay "chỉ mình bị muộn"
                    $isAdminView = ($this->accessControl->canViewAllData($roleName) || session()->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH);
                    
                    if ($isAdminView) {
                        $query->where("cases.id IN (
                            SELECT DISTINCT case_id FROM case_steps 
                            WHERE deleted_at IS NULL AND (
                                (status = 'completed' AND completed_at > deadline)
                                OR 
                                (status IN ('active') AND deadline < '$now')
                            )
                        )");
                    } else {
                        $myEmpId = (int)session()->get('employee_id');
                        if ($myEmpId > 0) {
                            $query->where("cases.id IN (
                                SELECT DISTINCT case_id FROM case_steps 
                                WHERE deleted_at IS NULL AND (
                                    (status = 'completed' AND completed_at > deadline AND completed_by = $myEmpId)
                                    OR 
                                    (status IN ('active') AND deadline < '$now' AND assigned_to = $myEmpId)
                                )
                            )");
                        }
                    }
                } else {
                    $idsStr = implode(',', array_map('intval', array_filter($targetIds)));
                    if (!empty($idsStr)) {
                        $query->where("cases.id IN (
                            SELECT DISTINCT case_id FROM case_steps 
                            WHERE deleted_at IS NULL AND (
                                (status = 'completed' AND completed_at > deadline AND completed_by IN ($idsStr))
                                OR 
                                (status IN ('active') AND deadline < '$now' AND assigned_to IN ($idsStr))
                            )
                        )");
                    }
                }
            } elseif ($status === 'completed_month') {
                // Lọc vụ việc hoàn thành trong tháng
                $query->where('cases.status', 'da_hoan_thanh')
                      ->where('MONTH(cases.updated_at)', date('m'))
                      ->where('YEAR(cases.updated_at)', date('Y'));
            } else {
                $query->where('cases.status', $status);
            }
        }

        // 6. Áp dụng các bộ lọc chung (Search, Lawyers, Tags, Time, Payment Status)
        $this->applyFilters($query, $search, $lawyerIds, $tagId, $month, $year, $paymentStatus, $isFinance);
        // 7. Thực thi truy vấn kèm theo Phân trang
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
                ->select('case_id, role_in_case, employees.id as employee_id, employees.full_name')
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
                $case['assignees_data'] = array_values(array_map(function($m) {
                    return ['id' => $m['employee_id'], 'name' => $m['full_name']];
                }, array_filter($members, function($m) use ($case) {
                    return $m['case_id'] == $case['id'] && in_array($m['role_in_case'], ['assignee', 'main']);
                })));

                // Lọc Người duyệt (approver)
                $case['approvers_data'] = array_values(array_map(function($m) {
                    return ['id' => $m['employee_id'], 'name' => $m['full_name']];
                }, array_filter($members, function($m) use ($case) {
                    return $m['case_id'] == $case['id'] && $m['role_in_case'] === 'approver';
                })));
                
                $case['tags'] = array_filter($tags, function($t) use ($case) {
                    return $t['entity_id'] == $case['id'];
                });
                
                // Ghi đè thông tin hiển thị Lawyer cho đẹp trên giao diện danh sách
                if (!empty($case['assignees_data'])) {
                    $case['lawyer_name'] = implode(', ', array_column($case['assignees_data'], 'name'));
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
    /**
     * Tính toán bộ chỉ số thống kê (Stats) dựa trên quyền hạn và bộ lọc hiện tại.
     */
    public function getStats(string $search = '', array $lawyerIds = [], int $tagId = 0, int $month = 0, int $year = 0)
    {
        $session = session();
        $roleName = $session->get('role_name');
        
        // Query cơ sở bao gồm cả phân quyền và bộ lọc người dùng chọn
        $baseQuery = $this->caseModel->select('cases.id');
        
        // 1. Phân quyền
        $this->applyAccessLimit($baseQuery);
        
        // 2. Bộ lọc (Sync với danh sách)
        $this->applyFilters($baseQuery, $search, $lawyerIds, $tagId, $month, $year);
        
        $now = date('Y-m-d H:i:s');
        $overdueSubquery = "SELECT DISTINCT case_id FROM case_steps WHERE status = 'active' AND deadline < '$now' AND deleted_at IS NULL";
        
        // Nếu không phải Admin, chỉ đếm những vụ việc mà CHÍNH HỌ đang làm trễ
        $isAdminView = ($this->accessControl->canViewAllData($roleName) || $session->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH);
        if (!$isAdminView) {
            $myEmpId = (int)$session->get('employee_id');
            if ($myEmpId > 0) {
                $overdueSubquery .= " AND assigned_to = $myEmpId";
            }
        }

        return [
            'total'      => (clone $baseQuery)->countAllResults(),
            'processing' => (clone $baseQuery)->where('cases.status', 'dang_xu_ly')->countAllResults(),
            'waiting'    => (clone $baseQuery)->where('cases.status', 'cho_tiep_nhan')->countAllResults(),
            'completed'  => (clone $baseQuery)->where('cases.status', 'da_hoan_thanh')->countAllResults(),
            'overdue'    => (clone $baseQuery)
                                ->whereIn('cases.status', ['cho_tiep_nhan', 'dang_xu_ly'])
                                ->where("cases.id IN ($overdueSubquery)")
                                ->countAllResults()
        ];
    }

    /**
     * Áp dụng tập hợp các bộ lọc tìm kiếm và phân loại.
     * Phương thức này dùng để đồng bộ hóa Query giữa danh sách và bộ chỉ số thống kê.
     */
    private function applyFilters(&$query, $search, $lawyerIds, $tagId, $month, $year, $paymentStatus = '', $isFinance = false)
    {
        // 1. Lọc theo nhân sự phụ trách
        if (!empty($lawyerIds)) {
            $query->groupStart()
                  ->whereIn('cases.assigned_lawyer_id', $lawyerIds)
                  ->orWhereIn('cases.assigned_staff_id', $lawyerIds)
                  ->orWhereIn('cases.id', function($builder) use ($lawyerIds) {
                      return $builder->select('case_id')->from('case_members')->whereIn('employee_id', $lawyerIds);
                  })
            ->groupEnd();
        }

        // 2. Lọc theo tháng/năm
        if ($month > 0) {
            if ($year <= 0) $year = (int)date('Y');
            
            if ($isFinance) {
                // Trong module Tài chính: Lọc vụ việc được tạo trong tháng HOẶC có đợt thanh toán trong tháng đó
                $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
                $datePattern = $year . '-' . $monthStr;
                
                $query->groupStart()
                      ->where('MONTH(cases.created_at)', $month)
                      ->where('YEAR(cases.created_at)', $year)
                      ->orLike('cases.payment_progress', $datePattern)
                ->groupEnd();
            } else {
                // Module Vụ việc: Lọc theo ngày tạo (Mặc định)
                $query->where('MONTH(cases.created_at)', $month);
                $query->where('YEAR(cases.created_at)', $year);
            }
        } elseif ($year > 0) {
            if ($isFinance) {
                $query->groupStart()
                      ->where('YEAR(cases.created_at)', $year)
                      ->orLike('cases.payment_progress', (string)$year)
                ->groupEnd();
            } else {
                $query->where('YEAR(cases.created_at)', $year);
            }
        }

        // 2.1. Lọc theo trạng thái thanh toán (Finance special)
        if ($paymentStatus === 'paid') {
            // Đã thu đủ: Không còn phần nào chưa thu (is_paid:0) và có ít nhất 1 phần đã thu (is_paid:1)
            // Hoặc đơn giản là chuỗi JSON không chứa "is_paid":0
            $query->where('cases.payment_progress IS NOT NULL')
                  ->where('cases.payment_progress !=', '')
                  ->where('cases.payment_progress NOT LIKE', '%"is_paid":0%')
                  ->where('cases.payment_progress LIKE', '%"is_paid":1%');
        } elseif ($paymentStatus === 'unpaid') {
            // Chưa thu / Còn thiếu: Có ít nhất 1 phần chưa thu (is_paid:0)
            $query->where('(cases.payment_progress LIKE \'%"is_paid":0%\' OR cases.payment_progress IS NULL OR cases.payment_progress = \'\')');
        }

        // 3. Lọc theo Nhãn dán
        if ($tagId > 0) {
            $query->whereIn('cases.id', function($builder) use ($tagId) {
                return $builder->select('entity_id')
                               ->from('entity_tags')
                               ->where('tag_id', $tagId)
                               ->where('entity_type', 'cases');
            });
        }

        // 4. Tìm kiếm đa năng
        if (!empty($search)) {
            $query->groupStart();
            if (is_numeric($search)) {
                $query->where('cases.id', (int)$search);
                $query->orLike('cases.code', $search);
            } else {
                $query->like('cases.code', $search);
            }
            $query->orLike('cases.title', $search)
                  ->orLike('customers.name', $search)
                  ->orLike('employees.full_name', $search)
            ->groupEnd();
        }
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
    /**
     * Tính toán bộ chỉ số tài chính (Tổng HĐ, Đã thu, Còn lại) dựa trên bộ lọc.
     * 
     * Logic lọc thời gian (tháng/năm) theo quy tắc:
     * - Tổng HĐ  : deadline đợt lần 1 (index 0). Nếu trống → dùng created_at của vụ việc.
     * - Đã thu   : deadline đợt is_paid=1 đầu tiên. Nếu trống → dùng created_at.
     * - Chưa thu : deadline đợt is_paid=0 đầu tiên. Nếu trống → dùng created_at.
     */
    public function getFinanceStats(string $search = '', int $month = 0, int $year = 0, string $paymentStatus = '')
    {
        // Lấy toàn bộ dữ liệu (không lọc tháng/năm ở SQL vì sẽ xử lý trong PHP)
        // Chỉ áp dụng: phân quyền, tìm kiếm, trạng thái thanh toán
        $query = $this->caseModel->select('contract_value, payment_progress, created_at');
        $this->applyAccessLimit($query);
        $this->applyFilters($query, $search, [], 0, 0, 0, $paymentStatus, true);
        
        $results = $query->findAll();
        
        $totalContract = 0;
        $totalPaid     = 0;
        $totalUnpaid   = 0;

        // Chuẩn bị hiệu quả tháng/năm lọc
        $filterYear  = ($year > 0) ? $year : 0;
        $filterMonth = ($month > 0 && $filterYear > 0) ? $month : 0;

        foreach ($results as $row) {
            $createdAt = $row['created_at'] ?? null;
            $payments  = [];

            if (!empty($row['payment_progress'])) {
                $decoded = json_decode($row['payment_progress'], true);
                if (is_array($decoded)) {
                    $payments = $decoded;
                }
            }

            // ----------------------------------------------------------------
            // Xác định "mốc thời gian đại diện" cho từng chỉ số
            // ----------------------------------------------------------------

            // --- TỔNG HĐ: Dùng deadline đợt lần 1 (index 0), fallback → created_at ---
            $totalRepDate = null;
            if (isset($payments[0]) && !empty($payments[0]['deadline'])) {
                $totalRepDate = $payments[0]['deadline']; // Thời gian thu lần 1
            } else {
                $totalRepDate = $createdAt; // Fallback: ngày tạo vụ việc
            }

            // --- ĐÃ THU: Ưu tiên từ to đến bé (từ cuối lên). Lấy mốc cuối cùng có thời gian. Fallback → created_at ---
            $paidRepDate = null;
            $hasPaid = false;
            for ($i = count($payments) - 1; $i >= 0; $i--) {
                if (!empty($payments[$i]['is_paid']) && $payments[$i]['is_paid'] == 1) {
                    $hasPaid = true;
                    if ($paidRepDate === null && !empty($payments[$i]['deadline'])) {
                        $paidRepDate = $payments[$i]['deadline'];
                    }
                }
            }
            if ($hasPaid && $paidRepDate === null) {
                $paidRepDate = $createdAt;
            }

            // --- CHƯA THU: Thời gian lần thanh toán cuối cùng chưa thanh toán nếu có, fallback → created_at ---
            $unpaidRepDate = null;
            $hasUnpaid = false;
            for ($i = count($payments) - 1; $i >= 0; $i--) {
                if (empty($payments[$i]['is_paid']) || $payments[$i]['is_paid'] == 0) {
                    $hasUnpaid = true;
                    if (!empty($payments[$i]['deadline'])) {
                        $unpaidRepDate = $payments[$i]['deadline'];
                    }
                    break; // Chỉ lấy mốc của lần cuối cùng chưa thanh toán
                }
            }
            if ($hasUnpaid && $unpaidRepDate === null) {
                $unpaidRepDate = $createdAt;
            }

            // ----------------------------------------------------------------
            // Helper: Kiểm tra mốc thời gian có khớp bộ lọc tháng/năm không
            // ----------------------------------------------------------------
            $matchesFilter = function(?string $dateStr) use ($filterYear, $filterMonth): bool {
                if ($filterYear <= 0) return true; // Không lọc theo năm → luôn khớp
                if (empty($dateStr)) return false;
                try {
                    $dt = new \DateTime($dateStr);
                    if ((int)$dt->format('Y') !== $filterYear) return false;
                    if ($filterMonth > 0 && (int)$dt->format('n') !== $filterMonth) return false;
                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            };

            // ----------------------------------------------------------------
            // Cộng Tổng HĐ
            // ----------------------------------------------------------------
            if ($matchesFilter($totalRepDate)) {
                $totalContract += (float)($row['contract_value'] ?? 0);
            }

            // ----------------------------------------------------------------
            // Cộng Đã thu / Chưa thu
            // ----------------------------------------------------------------
            foreach ($payments as $p) {
                $amtStr = $p['amount'] ?? '0';
                $amt    = (float)str_replace(['.', ','], '', $amtStr);

                if (!empty($p['is_paid']) && $p['is_paid'] == 1) {
                    // Đã thu
                    if ($matchesFilter($paidRepDate)) {
                        $totalPaid += $amt;
                    }
                } else {
                    // Chưa thu
                    if ($matchesFilter($unpaidRepDate)) {
                        $totalUnpaid += $amt;
                    }
                }
            }
        }
        
        return [
            'total_contract' => $totalContract,
            'total_paid'     => $totalPaid,
            'total_unpaid'   => $totalUnpaid
        ];
    }
}
