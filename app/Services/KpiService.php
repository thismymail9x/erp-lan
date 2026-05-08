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
            ->where('cases.deleted_at', null)
            ->where('cases.status !=', 'huy')
            ->where('case_steps.completed_at <= case_steps.deadline') // Chốt chặn: Chỉ tính nếu đúng hạn
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
        // Đây là những khoản tiền nhân viên ĐÁNG LẼ được nhận nếu không làm muộn hạn.
        $lostQuery = $this->db->table('case_steps')
            ->selectSum('case_steps.kpi_reward', 'total')
            ->join('cases', 'cases.id = case_steps.case_id')
            ->where('case_steps.status', 'completed')
            ->where('cases.deleted_at', null)
            ->where('cases.status !=', 'huy')
            ->where('case_steps.completed_at > case_steps.deadline') // Chỉ lấy bước muộn hạn
            ->where('case_steps.completed_by', $employeeId);
        
        if ($currentYear > 0) {
            $lostQuery->where('YEAR(case_steps.completed_at)', $currentYear);
        }
        $lost = (float)($lostQuery->get()->getRow()->total ?? 0);

        // 4. TÍNH TIỀM NĂNG (Potential Bonus)
        // Quy tắc: Không tính các bước đã quá hạn (overdue) vào tiềm năng vì theo quy định sẽ bị hủy thưởng.
        $potentialQuery = $this->db->table('case_steps')
            ->selectSum('case_steps.kpi_reward', 'total')
            ->join('cases', 'cases.id = case_steps.case_id')
            ->whereIn('case_steps.status', ['pending', 'active', 'pending_approval'])
            ->where('case_steps.overdue_notified', 0) // Chỉ tính các bước chưa bị báo quá hạn
            ->where('cases.deleted_at', null)
            ->whereNotIn('cases.status', ['da_hoan_thanh', 'huy']);

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

        $employees = $builder->get()->getResultArray();
        if (empty($employees)) return [];

        $empIds = array_column($employees, 'id');
        $currentYear = (int)($filters['year'] ?? date('Y'));

        // 2. TÍNH KPI ĐÃ NHẬN (Earned): Nhắm thẳng vào bảng case_steps.completed_by
        $earnedBase = $db->table('case_steps cs')
            ->select('cs.completed_by as emp_id, SUM(cs.kpi_reward) as total_earned')
            ->join('cases c', 'c.id = cs.case_id')
            ->where('cs.status', 'completed')
            ->where('c.deleted_at', null)
            ->where('c.status !=', 'huy')
            ->where('cs.completed_at <= cs.deadline') // Tuân thủ quy tắc đúng hạn
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
            ->where('cs.overdue_notified', 0)
            ->where('c.deleted_at', null)
            ->whereNotIn('c.status', ['da_hoan_thanh', 'huy'])
            ->whereIn('cs.assigned_to', $empIds)
            ->groupBy('cs.assigned_to')
            ->get()->getResultArray();

        // 4. TÍNH KPI BỊ MẤT (Lost): Nhắm thẳng vào bảng case_steps.completed_by
        $lostData = $db->table('case_steps cs')
            ->select('cs.completed_by as emp_id, SUM(cs.kpi_reward) as total_lost')
            ->join('cases c', 'c.id = cs.case_id')
            ->where('cs.status', 'completed')
            ->where('c.deleted_at', null)
            ->where('c.status !=', 'huy')
            ->where('cs.completed_at > cs.deadline') // Muộn hạn
            ->whereIn('cs.completed_by', $empIds);

        if ($currentYear > 0) {
            $lostData->where('YEAR(cs.completed_at)', $currentYear);
        }
        $lostResults = $lostData->groupBy('cs.completed_by')->get()->getResultArray();

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
