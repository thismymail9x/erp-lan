<?php

namespace App\Services;

use App\Models\CaseStepModel;
use App\Models\EmployeeModel;
use DateTime;

/**
 * PerformanceService
 * 
 * Quản lý tổng hợp kết quả công việc (KPI & Bonus Aggregator).
 */
class PerformanceService extends BaseService
{
    protected $stepModel;
    protected $employeeModel;

    public function __construct()
    {
        parent::__construct();
        $this->stepModel = new CaseStepModel();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Tổng hợp KPI và Thưởng cho một nhân viên trong một khoảng thời gian.
     */
    public function getEmployeeRewardSummary(int $employeeId, string $startDate, string $endDate)
    {
        // 1. Tìm các bước mà nhân viên này phụ trách và đã hoàn thành trong kỳ
        // Lưu ý: Cần join với bảng thực tế nếu có nhiều người phụ trách, ở đây giả định logic cơ bản theo case_id hoặc assigned roles.
        // Trong hệ thống hiện tại, thông tin người thực hiện được lưu vết ở CaseHistory hoặc phân công.
        
        // Logic: Lấy toàn bộ bước hoàn thành và tính thưởng dựa trên deadline
        $db = \Config\Database::connect();
        $builder = $db->table('case_steps cs');
        $builder->select('SUM(CASE WHEN cs.completed_at <= cs.deadline THEN cs.kpi_reward ELSE 0 END) as total_kpi, COUNT(cs.id) as completed_count');
        $builder->where('cs.completed_by', $employeeId);
        $builder->where('cs.status', 'completed');
        $builder->where('cs.deleted_at', null);
        $builder->where('cs.completed_at IS NOT NULL');
        $builder->where('cs.completed_at >=', $startDate);
        $builder->where('cs.completed_at <=', $endDate);
        
        $result = $builder->get()->getRowArray();

        return [
            'employee_id' => $employeeId,
            'period'      => ['from' => $startDate, 'to' => $endDate],
            'kpi_reward'  => (float)($result['total_kpi'] ?? 0),
            'steps_done'  => (int)($result['completed_count'] ?? 0)
        ];
    }

    /**
     * Xuất dữ liệu sang bảng lương (Mockup logic).
     */
    public function pushToPayroll(int $employeeId, int $month, int $year)
    {
        $start = (new DateTime("$year-$month-01"))->format('Y-m-d 00:00:00');
        $end = (new DateTime("$year-$month-01"))->modify('last day of this month')->format('Y-m-d 23:59:59');
        
        $summary = $this->getEmployeeRewardSummary($employeeId, $start, $end);
        
        // Logic đẩy vào bảng lương (case_salaries hoặc tương đương)
        // [Implementing code here...]
        
        return $summary;
    }
}
