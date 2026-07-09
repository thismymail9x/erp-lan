<?php
/**
 * DỊCH VỤ QUẢN LÝ KPI & ĐỘNG LỰC NHÂN VIÊN (KpiService)
 * Chịu trách nhiệm: Tính toán tiền thưởng dựa trên tiến độ hoàn thành các bước vụ việc.
 * Quy tắc: Dữ liệu được truy xuất trực tiếp từ bảng cases và case_steps.
 */

namespace App\Services;

use Config\Database;

class KpiService extends BaseService
{
    public const CONSULTING_MONTHLY_TARGET_VALUE = 150000000;
    public const CONSULTING_TARGET_REWARD = 5000000;
    public const CONSULTING_MONTHLY_PAYOUT_RATE = 0.4;
    public const CONSULTING_ANNUAL_ACCRUAL_RATE = 0.6;

    /**
     * TƯ DUY LOGIC KPI (Key Performance Indicator):
     * 1. KPI Nhận (Earned): Là dòng tiền thực tế đã phát sinh khi hoàn thành một bước công việc (Step).
     *    - Trong báo cáo, KPI này được lọc theo NĂM để phục vụ chốt thưởng định kỳ hàng năm.
     * 2. KPI Còn (Potential): Là dòng tiền dự kiến sẽ có được khi hoàn thành các bước dở dang.
     *    - Không lọc theo năm vì công việc dở dang là tài sản hiện hữu, không phụ thuộc mốc thời gian chốt thưởng.
     * 3. Tổng mục tiêu (Total Goal): Là cái đích cuối cùng của nhân sự/công ty.
     *    - Được tính bằng: [Tất cả KPI thực nhận lịch sử] + [Tất cả KPI tiềm năng hiện tại].
     *    - Số liệu này phải đứng yên (Global) khi thay đổi bộ lọc năm để giữ vững mục tiêu dài hạn.
     */
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function getMotivationStats(?int $employeeId, array $filters = [])
    {
        $currentYear = (int)($filters['year'] ?? date('Y'));
        $role = session()->get('role_name');
        $isAdmin = (in_array($role, [\Config\AppConstants::ROLE_ADMIN, \Config\AppConstants::ROLE_MOD]) || has_permission('sys.admin'));

        /**
         * LOGIC ADMIN: 
         * Để đảm bảo Dashboard Admin khớp 100% với Báo cáo KPI chi tiết, 
         * Admin sẽ được tính tổng bằng cách cộng dồn chỉ số của từng nhân viên.
         */
        if ($isAdmin && !$employeeId) {
            $allStats = $this->getAllEmployeesStats($filters);
            $earned = array_sum(array_column($allStats, 'earned'));
            $potential = array_sum(array_column($allStats, 'potential'));
            $lost = array_sum(array_column($allStats, 'lost'));
            $totalGlobal = array_sum(array_column($allStats, 'total'));
            $percent = ($totalGlobal > 0) ? round(($earned / $totalGlobal) * 100) : 0;

            return [
                'earned'    => $earned,
                'potential' => $potential,
                'lost'      => $lost,
                'total'     => $totalGlobal,
                'percent'   => $percent
            ];
        }

        if (!$employeeId && !$isAdmin) {
            return ['earned' => 0, 'potential' => 0, 'total' => 0, 'percent' => 0,'lost' => 0];
        }

        /**
         * LOGIC NHÂN VIÊN CÁ NHÂN:
         */
        // 1. TÍNH TIỀN ĐÃ ĐẠT (Earned Bonus)
        // Quy tắc: Chỉ được hưởng KPI nếu hoàn thành đúng hạn (completed_at <= deadline)


        $earnedBase = $this->db->table('case_steps')
            ->selectSum('case_steps.kpi_reward', 'total')
            ->join('cases', 'cases.id = case_steps.case_id')
            ->where('case_steps.status', 'completed')
            ->where('case_steps.deleted_at', null)
            ->where('cases.deleted_at', null)
            ->where('cases.status !=', 'huy')
            ->groupStart()
                ->where('case_steps.completed_at <= case_steps.deadline')
                ->orWhere('case_steps.kpi_override_approved', 1)
            ->groupEnd()
            ->where('case_steps.completed_by', $employeeId);
        // Trạng thái: Chỉ tính các bước đã completed (hoàn thành) và vụ việc không bị huy (hủy).
        // Quy tắc đúng hạn: Đây là logic mới chúng ta vừa cập nhật. Nếu completed_at > deadline, bước đó dù xong cũng có KPI = 0.
        //KPI Năm (earned): Dùng để thanh toán thưởng. Lọc theo YEAR(completed_at).
        // KPI Toàn thời gian (earnedAll): Dùng làm mốc tính "Tổng mục tiêu".
        // Query phụ: Tính KPI thực nhận của RIÊNG NĂM NÀY (Để thanh toán)
        $earnedYearQuery = clone $earnedBase;
        if ($currentYear > 0) {
            $earnedYearQuery->where('YEAR(case_steps.completed_at)', $currentYear);
        }
        $earned = (float)($earnedYearQuery->get()->getRow()->total ?? 0);

        // Query phụ: Tính KPI thực nhận TOÀN BỘ LỊCH SỬ (Để tính Tổng mục tiêu)
        $earnedAll = (float)($earnedBase->get()->getRow()->total ?? 0);

        // 3. TÍNH KPI BỊ MẤT (Lost Bonus)
        // Bao gồm: Các bước hoàn thành muộn HẬU QUẢ + Các bước đang quá hạn HIỆN TẠI
        $lostQuery = $this->db->table('case_steps')
            ->selectSum('case_steps.kpi_reward', 'total')
            ->join('cases', 'cases.id = case_steps.case_id')
            ->where('case_steps.deleted_at', null)
            ->where('cases.deleted_at', null)
            ->whereNotIn('cases.status', ['huy', 'tam_dung'])
            ->groupStart()
                // TH1: Đã xong nhưng muộn
                ->groupStart()
                    ->where('case_steps.status', 'completed')
                    ->where('case_steps.completed_at > case_steps.deadline')
                    ->where('case_steps.kpi_override_approved !=', 1)
                    ->where('case_steps.completed_by', $employeeId)
                ->groupEnd()
                // TH2: Chưa xong nhưng đã quá hạn (Dù đang active hay pending)
                ->orGroupStart()
                    ->whereIn('case_steps.status', ['active', 'pending', 'pending_approval'])
                    ->where('case_steps.deadline <', date('Y-m-d H:i:s'))
                    ->where('case_steps.assigned_to', $employeeId)
                ->groupEnd()
            ->groupEnd();
        
        if ($currentYear > 0) {
            // Đối với TH2 (chưa xong), ta dùng YEAR(deadline) để lọc theo năm
            $lostQuery->groupStart()
                ->where('YEAR(case_steps.completed_at)', $currentYear)
                ->orWhere('YEAR(case_steps.deadline)', $currentYear)
            ->groupEnd();
        }
        $lost = (float)($lostQuery->get()->getRow()->total ?? 0);

        // 4. TÍNH TIỀM NĂNG (Potential Bonus)
        // Quy tắc: Không tính các bước đã quá hạn (overdue) vào tiềm năng vì theo quy định sẽ bị hủy thưởng.
        $potentialQuery = $this->db->table('case_steps')
            ->selectSum('case_steps.kpi_reward', 'total')
            ->join('cases', 'cases.id = case_steps.case_id')
            ->whereIn('case_steps.status', ['pending', 'active', 'pending_approval'])
            ->where('case_steps.deadline >=', date('Y-m-d H:i:s')) // Chỉ tính các bước còn trong hạn
            ->where('case_steps.deleted_at', null)
            ->where('cases.deleted_at', null)
            ->whereNotIn('cases.status', ['da_hoan_thanh', 'huy', 'tam_dung']);

        $potentialQuery->where('case_steps.assigned_to', $employeeId);
        $potentialRes = $potentialQuery->get()->getRow();
        $potential = (float)($potentialRes->total ?? 0);

        /**
         * TỔNG HỢP:
         * totalGlobal = Đạt + Bỏ lỡ + Tiềm năng (Theo yêu cầu mới)
         */
        $totalGlobal = $earned + $lost + $potential;
        $percent = ($totalGlobal > 0) ? round(($earned / $totalGlobal) * 100) : 0;

        return [
            'earned'    => $earned,
            'potential' => $potential,
            'lost'      => $lost,
            'total'     => $totalGlobal,
            'percent'   => $percent
        ];
    }
    /**
     * Lấy thống kê KPI của TẤT CẢ nhân viên (Dành cho báo cáo Admin)
     */
    public function getAllEmployeesStats(array $filters = [])
    {
        $db = $this->db;

        // 1. Khởi tạo Query lấy danh sách nhân viên cơ bản (Theo quy tắc Common.php)
        $builder = $db->table('employees e')
            ->select('e.id, e.user_id, e.full_name, e.position, d.name as department_name')
            ->join('users u', 'u.id = e.user_id', 'inner')
            ->join('departments d', 'd.id = e.department_id', 'left')
            ->where('u.active_status', 1)
            ->where('u.deleted_at', null);

        if (!empty($filters['search'])) {
            $builder->like('e.full_name', $filters['search']);
        }
        if (!empty($filters['department_id'])) {
            $builder->where('e.department_id', $filters['department_id']);
        }
        if (!empty($filters['manager_id'])) {
            $builder->where('e.manager_id', $filters['manager_id']);
        }
        if (!empty($filters['employee_id'])) {
            $builder->where('e.id', $filters['employee_id']);
        }

        $employees = $builder->get()->getResultArray();
        if (empty($employees)) return [];

        $empIds = array_column($employees, 'id');
        $currentYear = (int)($filters['year'] ?? date('Y'));

        // 2. TÍNH KPI ĐÃ NHẬN (Earned): Nhắm thẳng vào bảng case_steps.completed_by
        $earnedBase = $db->table('case_steps cs')
            ->select('cs.completed_by as emp_id, SUM(cs.kpi_reward) as total_earned')
            ->join('cases c', 'c.id = cs.case_id')
            ->where('cs.status', 'completed')
            ->where('cs.deleted_at', null)
            ->where('c.deleted_at', null)
            ->where('c.status !=', 'huy')
            ->groupStart()
                ->where('cs.completed_at <= cs.deadline')
                ->orWhere('cs.kpi_override_approved', 1)
            ->groupEnd()
            ->whereIn('cs.completed_by', $empIds);

        // Clone để tính KPI lịch sử cho cột "Tổng mục tiêu"
        $earnedAllQuery = clone $earnedBase;
        
        if ($currentYear > 0) {
            $earnedBase->where('YEAR(cs.completed_at)', $currentYear);
        }

        $earnedData = $earnedBase->groupBy('cs.completed_by')->get()->getResultArray();
        $earnedAllData = $earnedAllQuery->groupBy('cs.completed_by')->get()->getResultArray();

        // 3. TÍNH KPI TIỀM NĂNG (Potential): Nhắm thẳng vào bảng case_steps.assigned_to
        $potentialData = $db->table('case_steps cs')
            ->select('cs.assigned_to as emp_id, SUM(cs.kpi_reward) as total_potential')
            ->join('cases c', 'c.id = cs.case_id')
            ->whereIn('cs.status', ['pending', 'active', 'pending_approval'])
            ->where('cs.deadline >=', date('Y-m-d H:i:s')) // Chỉ tính các bước còn trong hạn
            ->where('cs.deleted_at', null)
            ->where('c.deleted_at', null)
            ->whereNotIn('c.status', ['da_hoan_thanh', 'huy', 'tam_dung'])
            ->whereIn('cs.assigned_to', $empIds)
            ->groupBy('cs.assigned_to')
            ->get()->getResultArray();

        // 4. TÍNH KPI BỊ MẤT (Lost): Các bước hoàn thành muộn + Các bước đang quá hạn
        $lostDataQuery = $db->table('case_steps cs')
            ->select('IF(cs.status = "completed", cs.completed_by, cs.assigned_to) as emp_id', false)
            ->selectSum('cs.kpi_reward', 'total_lost')
            ->join('cases c', 'c.id = cs.case_id')
            ->where('cs.deleted_at', null)
            ->where('c.deleted_at', null)
            ->whereNotIn('c.status', ['huy', 'tam_dung'])
            ->groupStart()
                ->groupStart()
                    ->where('cs.status', 'completed')
                    ->where('cs.completed_at > cs.deadline')
                    ->where('cs.kpi_override_approved !=', 1)
                    ->whereIn('cs.completed_by', $empIds)
                ->groupEnd()
                ->orGroupStart()
                    ->whereIn('cs.status', ['active', 'pending', 'pending_approval'])
                    ->where('cs.deadline <', date('Y-m-d H:i:s'))
                    ->whereIn('cs.assigned_to', $empIds)
                ->groupEnd()
            ->groupEnd();

        if ($currentYear > 0) {
            $lostDataQuery->groupStart()
                ->where('YEAR(cs.completed_at)', $currentYear)
                ->orWhere('YEAR(cs.deadline)', $currentYear)
            ->groupEnd();
        }
        $lostResults = $lostDataQuery->groupBy('emp_id')->get()->getResultArray();

        // 4. MAPPING DỮ LIỆU
        $earnedMap = array_column($earnedData, 'total_earned', 'emp_id');
        $earnedAllMap = array_column($earnedAllData, 'total_earned', 'emp_id');
        $potentialMap = array_column($potentialData, 'total_potential', 'emp_id');
        $lostMap = array_column($lostResults, 'total_lost', 'emp_id');

        return array_map(function($emp) use ($earnedMap, $earnedAllMap, $potentialMap, $lostMap) {
            $earned = $earnedMap[$emp['id']] ?? 0;
            $earnedAll = $earnedAllMap[$emp['id']] ?? 0;
            $potential = $potentialMap[$emp['id']] ?? 0;
            $lost = $lostMap[$emp['id']] ?? 0;
            $totalGlobal = $earned + $lost + $potential; // Mục tiêu = Đạt + Bỏ lỡ + Tiềm năng
            
            return array_merge($emp, [
                'earned'    => $earned,
                'potential' => $potential,
                'lost'      => $lost,
                'total'     => $totalGlobal,
                'percent'   => ($totalGlobal > 0) ? round(($earned / $totalGlobal) * 100) : 0
            ]);
        }, $employees);
    }

    /**
     * Helper: Áp dụng bộ lọc nhân viên thống nhất cho KPI.
     */
    /**
     * Tính KPI tư vấn cá nhân hoặc tổng hợp các nhân viên tư vấn theo doanh thu thực thu.
     * Bảng KPI chuẩn và thưởng vượt mốc luôn được áp riêng cho từng nhân viên.
     */
    public function getConsultingMotivationStats(?int $employeeId, array $filters = []): array
    {
        $role = session()->get('role_name');
        $isAdmin = (in_array($role, [\Config\AppConstants::ROLE_ADMIN, \Config\AppConstants::ROLE_MOD]) || has_permission('sys.admin') || has_permission('kpi.view_all'));
        $canAggregate = $isAdmin || has_permission('kpi.view_team');

        if ($canAggregate && !$employeeId) {
            $allStats = $this->getConsultingAllEmployeesStats($filters);
            $contractValue = array_sum(array_column($allStats, 'contract_value'));
            $caseCount = array_sum(array_column($allStats, 'case_count'));
            $targetValue = array_sum(array_column($allStats, 'target_value'));
            $stats = $this->formatConsultingStats($contractValue, $caseCount, $targetValue);
            foreach (['standard_reward', 'monthly_payout', 'annual_accrual', 'milestone_bonus', 'next_payroll_payout', 'reward'] as $field) {
                $stats[$field] = array_sum(array_column($allStats, $field));
            }
            $stats['earned'] = $stats['reward'];

            return $stats;
        }

        if (!$employeeId) {
            return $this->formatConsultingStats(0, 0, 0);
        }

        $scopedStats = $this->getConsultingAllEmployeesStats(array_merge($filters, [
            'employee_id' => $employeeId,
        ]));

        if (empty($scopedStats)) {
            return $this->formatConsultingStats(0, 0, 0);
        }

        return $this->formatConsultingStats(
            (float)($scopedStats[0]['contract_value'] ?? 0),
            (int)($scopedStats[0]['case_count'] ?? 0),
            (float)($scopedStats[0]['target_value'] ?? self::CONSULTING_MONTHLY_TARGET_VALUE)
        );
    }

    /**
     * Lấy thống kê KPI tư vấn theo từng nhân sự để hiển thị bảng báo cáo.
     */
    public function getConsultingAllEmployeesStats(array $filters = []): array
    {
        $builder = $this->db->table('employees e')
            ->select('e.id, e.user_id, e.full_name, e.position, d.name as department_name')
            ->join('users u', 'u.id = e.user_id', 'inner')
            ->join('departments d', 'd.id = e.department_id', 'left')
            ->where('e.deleted_at', null)
            ->where('u.active_status', 1)
            ->where('u.deleted_at', null);

        if (!empty($filters['search'])) {
            $builder->like('e.full_name', $filters['search']);
        }
        if (!empty($filters['department_id'])) {
            $builder->where('e.department_id', $filters['department_id']);
        }
        if (!empty($filters['manager_id'])) {
            $builder->where('e.manager_id', $filters['manager_id']);
        }
        if (!empty($filters['employee_id'])) {
            $builder->where('e.id', $filters['employee_id']);
        }

        $employees = $builder->get()->getResultArray();
        if (empty($employees)) {
            return [];
        }

        $employeeIds = array_map('intval', array_column($employees, 'id'));
        $eligibleEmployeeIds = $this->getConsultingPermissionEmployeeIds($employeeIds);
        $eligibleMap = array_flip($eligibleEmployeeIds);
        $employees = array_values(array_filter($employees, function ($employee) use ($eligibleMap) {
            return isset($eligibleMap[(int)$employee['id']]);
        }));

        if (empty($employees)) {
            return [];
        }

        $employeeIds = array_map('intval', array_column($employees, 'id'));
        [$startAt, $endAt] = $this->resolveConsultingPeriod($filters);

        $caseRows = $this->db->table('cases c')
            ->select('c.id, c.consultant_id as employee_id, c.contract_value, c.payment_progress')
            ->where('c.deleted_at', null)
            ->where('c.status !=', 'huy')
            ->whereIn('c.consultant_id', $employeeIds)
            ->get()
            ->getResultArray();

        $contractMap = [];
        $caseCountMap = [];
        foreach ($caseRows as $caseRow) {
            $empId = (int)$caseRow['employee_id'];
            $actualRevenue = $this->resolveConsultingActualRevenue($caseRow, $startAt, $endAt);
            if ($actualRevenue <= 0) {
                continue;
            }

            $caseCountMap[$empId] = ($caseCountMap[$empId] ?? 0) + 1;
            $contractMap[$empId] = ($contractMap[$empId] ?? 0) + $actualRevenue;
        }

        return array_map(function ($employee) use ($contractMap, $caseCountMap) {
            $stats = $this->formatConsultingStats(
                (float)($contractMap[$employee['id']] ?? 0),
                (int)($caseCountMap[$employee['id']] ?? 0)
            );

            return array_merge($employee, $stats);
        }, $employees);
    }

    /**
     * Chuẩn hóa khoảng thời gian KPI tư vấn theo tháng yyyy-mm.
     */
    private function resolveConsultingPeriod(array $filters): array
    {
        $month = $filters['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $startAt = $month . '-01 00:00:00';
        $endAt = date('Y-m-t 23:59:59', strtotime($startAt));

        return [$startAt, $endAt];
    }

    /**
     * Lấy doanh thu thực thu dùng cho KPI tư vấn trong kỳ.
     */
    private function resolveConsultingActualRevenue(array $caseRow, string $startAt, string $endAt): float
    {
        $paymentProgress = $caseRow['payment_progress'] ?? null;
        if (!empty($paymentProgress)) {
            $payments = json_decode($paymentProgress, true);
            if (is_array($payments)) {
                $sum = 0;
                foreach ($payments as $payment) {
                    if (empty($payment['is_paid'])) {
                        continue;
                    }

                    $paidAt = $payment['paid_at'] ?? null;
                    if (empty($paidAt)) {
                        continue;
                    }

                    $paidAt = date('Y-m-d H:i:s', strtotime($paidAt));
                    if ($paidAt < $startAt || $paidAt > $endAt) {
                        continue;
                    }

                    $amount = $payment['amount'] ?? 0;
                    if (is_string($amount)) {
                        $amount = str_replace(['.', ','], '', $amount);
                    }
                    $sum += (float)$amount;
                }

                return $sum;
            }
        }

        return 0;
    }

    /**
     * Định dạng kết quả KPI tư vấn theo cùng cấu trúc dashboard đang dùng.
     */
    private function formatConsultingStats(float $contractValue, int $caseCount, ?float $targetValue = null): array
    {
        $targetValue = $targetValue ?? self::CONSULTING_MONTHLY_TARGET_VALUE;
        $employeeTargetCount = self::CONSULTING_MONTHLY_TARGET_VALUE > 0
            ? (int)round($targetValue / self::CONSULTING_MONTHLY_TARGET_VALUE)
            : 0;
        $targetReward = $employeeTargetCount * self::CONSULTING_TARGET_REWARD;
        $standardReward = $this->resolveConsultingStandardReward($contractValue);
        $milestoneBonus = $this->resolveConsultingMilestoneBonus($contractValue);
        $monthlyPayout = round($standardReward * self::CONSULTING_MONTHLY_PAYOUT_RATE);
        $annualAccrual = round($standardReward * self::CONSULTING_ANNUAL_ACCRUAL_RATE);
        $nextPayrollPayout = $monthlyPayout + $milestoneBonus;
        $reward = $standardReward + $milestoneBonus;
        $percent = $targetValue > 0
            ? round(($contractValue / $targetValue) * 100, 1)
            : 0;
        $remainingValue = max(0, $targetValue - $contractValue);

        return [
            'case_count'      => $caseCount,
            'contract_value'  => $contractValue,
            'target_value'    => $targetValue,
            'target_reward'   => $targetReward,
            'standard_reward' => $standardReward,
            'monthly_payout'  => $monthlyPayout,
            'annual_accrual'  => $annualAccrual,
            'milestone_bonus' => $milestoneBonus,
            'next_payroll_payout' => $nextPayrollPayout,
            'reward'          => $reward,
            'remaining_value' => $remainingValue,
            'percent'         => $percent,
            'earned'          => $reward,
            'potential'       => $remainingValue,
            'lost'            => 0,
            'total'           => $targetValue,
        ];
    }

    private function resolveConsultingStandardReward(float $actualRevenue): float
    {
        if ($actualRevenue >= 150000000) return 5000000;
        if ($actualRevenue >= 125000000) return 4000000;
        if ($actualRevenue >= 100000000) return 3000000;
        if ($actualRevenue >= 75000000) return 2000000;
        if ($actualRevenue >= 50000000) return 1000000;

        return 0;
    }

    private function resolveConsultingMilestoneBonus(float $actualRevenue): float
    {
        if ($actualRevenue >= 500000000) return 10000000;
        if ($actualRevenue >= 400000000) return 7000000;
        if ($actualRevenue >= 300000000) return 4000000;
        if ($actualRevenue >= 250000000) return 2000000;
        if ($actualRevenue >= 200000000) return 1000000;

        return 0;
    }

    /**
     * Danh sách nhân viên được cấp trực tiếp quyền kpi.consulting.
     * KPI tư vấn dùng danh sách này để tránh tính cả Admin/Trưởng phòng được kế thừa quyền theo vai trò.
     */
    private function getConsultingPermissionEmployeeIds(array $scopeEmployeeIds = []): array
    {
        $permission = $this->db->table('permissions')
            ->select('id')
            ->where('name', 'kpi.consulting')
            ->get()
            ->getRowArray();

        if (empty($permission['id'])) {
            return [];
        }

        $permissionId = (int)$permission['id'];
        $builder = $this->db->table('employees e')
            ->select('e.id')
            ->join('users u', 'u.id = e.user_id', 'inner')
            ->join('user_permissions up', 'up.user_id = u.id AND up.permission_id = ' . $permissionId, 'inner')
            ->where('e.deleted_at', null)
            ->where('u.active_status', 1)
            ->where('u.deleted_at', null)
            ->where('up.is_granted', 1);

        $scopeEmployeeIds = array_values(array_unique(array_filter(array_map('intval', $scopeEmployeeIds))));
        if (!empty($scopeEmployeeIds)) {
            $builder->whereIn('e.id', $scopeEmployeeIds);
        }

        $rows = $builder->get()->getResultArray();

        return array_map('intval', array_column($rows, 'id'));
    }

    private function applyEmployeeFilter(&$query, $employeeId)
    {
        // Ta tính KPI cho: Người phụ trách chính (Lawyer/Staff) HOẶC người được gán vai trò 'assignee' trong dự án
        $memberCaseIds = $this->db->table('case_members')
            ->where('employee_id', $employeeId)
            ->where('role_in_case', 'assignee')
            ->select('case_id')
            ->get()->getResultArray();
        $memberCaseIds = array_column($memberCaseIds, 'case_id');
        if (empty($memberCaseIds)) $memberCaseIds = [0];

        $query->groupStart()
            ->where('cases.assigned_lawyer_id', $employeeId)
            ->orWhere('cases.assigned_staff_id', $employeeId)
            ->orWhereIn('cases.id', $memberCaseIds)
        ->groupEnd();
    }
}
