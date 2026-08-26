<?php

namespace App\Services;

use App\Models\EmployeeModel;
use App\Models\UserModel;
use App\Models\ViolationFundModel;
use Config\AppConstants;

/**
 * Service xử lý nghiệp vụ quỹ vi phạm nội bộ.
 *
 * Logic tính mức tiền và gửi thông báo đặt ở Service để Controller chỉ còn nhận
 * request, validate đầu vào và trả phản hồi cho giao diện theo đúng kiến trúc MVC.
 */
class ViolationFundService extends BaseService
{
    public const CATEGORY_LABELS = [
        'attendance' => 'Vi phạm chấm công',
        'daily_report' => 'Vi phạm báo cáo công việc',
        'security' => 'Vi phạm bảo mật nội bộ',
        'work_rule' => 'Vi phạm quy trình - nội quy',
        'reputation' => 'Sai sót ảnh hưởng uy tín',
        'leave' => 'Vi phạm nghỉ phép - chuyên cần',
        'deadline' => 'Không hoàn thành / không báo cáo deadline',
        'other' => 'Khoản vi phạm khác',
    ];

    public const STATUS_LABELS = [
        'notified' => 'Đã thông báo',
        'collected' => 'Đã thu',
        'waived' => 'Miễn/không thu',
    ];

    public const COLLECTION_METHOD_LABELS = [
        'cash' => 'Tiền mặt',
        'transfer' => 'Chuyển khoản',
        'payroll' => 'Cấn trừ bảng lương',
        'other' => 'Khác',
    ];

    public const RANK_LABELS = [
        1 => 'Cấp 1 - TTS/Học việc/Thử việc',
        2 => 'Cấp 2 - Chuyên viên',
        3 => 'Cấp 3 - Trưởng nhóm/Trưởng phòng',
    ];

    protected ViolationFundModel $model;
    protected EmployeeModel $employeeModel;
    protected UserModel $userModel;
    protected NotificationService $notificationService;
    protected $db;

    public function __construct(
        ?ViolationFundModel $model = null,
        ?EmployeeModel $employeeModel = null,
        ?UserModel $userModel = null,
        ?NotificationService $notificationService = null
    ) {
        parent::__construct();
        $this->model = $model ?? new ViolationFundModel();
        $this->employeeModel = $employeeModel ?? new EmployeeModel();
        $this->userModel = $userModel ?? new UserModel();
        $this->notificationService = $notificationService ?? new NotificationService();
        $this->db = \Config\Database::connect();
    }

    public function canCreate(): bool
    {
        return has_permission('sys.admin')
            || session()->get('role_name') === AppConstants::ROLE_ADMIN
            || session()->get('role_name') === AppConstants::ROLE_TRUONG_PHONG
            || has_permission('violation_fund.manage')
            || (int)session()->get('department_id') === AppConstants::DEPT_HANH_CHINH;
    }

    public function canManage(): bool
    {
        return has_permission('sys.admin')
            || session()->get('role_name') === AppConstants::ROLE_ADMIN
            || has_permission('violation_fund.manage')
            || (int)session()->get('department_id') === AppConstants::DEPT_HANH_CHINH;
    }

    public function canCollect(): bool
    {
        return has_permission('sys.admin')
            || has_permission('violation_fund.collect')
            || (int)session()->get('department_id') === AppConstants::DEPT_HANH_CHINH;
    }

    public function canViewAll(): bool
    {
        return $this->canManage()
            || $this->canCreate()
            || $this->canCollect()
            || has_permission('violation_fund.view');
    }

    public function canView(): bool
    {
        return $this->canViewAll()
            || has_permission('violation_fund.view_own')
            || (int)session()->get('employee_id') > 0;
    }

    public function getDashboardData(array $filters = [], int $perPage = 20): array
    {
        $builder = $this->baseListBuilder();
        $this->applyVisibility($builder);
        $this->applyFilters($builder, $filters);

        $rows = $builder->orderBy('violation_funds.violation_date', 'DESC')
            ->orderBy('violation_funds.id', 'DESC')
            ->paginate($perPage);

        return [
            'rows' => $rows,
            'pager' => $this->model->pager,
            'summary' => $this->getSummary($filters),
            'category_breakdown' => $this->getCategoryBreakdown($filters),
            'employee_breakdown' => $this->getEmployeeBreakdown($filters),
            'employees' => $this->getEmployeeOptions(),
        ];
    }

    public function create(array $data): array
    {
        if (!$this->canCreate()) {
            return $this->fail('Bạn chưa có quyền ghi nhận vi phạm.');
        }

        $employeeId = (int)($data['employee_id'] ?? 0);
        $employee = $this->employeeModel
            ->select('employees.*')
            ->join('users', 'users.id = employees.user_id', 'inner')
            ->where('employees.deleted_at', null)
            ->where('users.active_status', 1)
            ->where('users.deleted_at', null)
            ->find($employeeId);

        if (!$employee) {
            return $this->fail('Không tìm thấy nhân sự vi phạm.');
        }

        $violationDate = $data['violation_date'] ?? date('Y-m-d');
        $dueMonth = $data['due_month'] ?? date('Y-m', strtotime($violationDate));
        $rankLevel = (int)($data['rank_level'] ?? $this->guessRankLevel($employee['position'] ?? ''));
        $category = $data['category'] ?? 'other';
        $explanation = trim((string)($data['explanation'] ?? ''));
        if (!array_key_exists($category, self::CATEGORY_LABELS)) {
            return $this->fail('Nhóm vi phạm không hợp lệ.');
        }

        if (!array_key_exists($rankLevel, self::RANK_LABELS)) {
            return $this->fail('Cấp bậc áp dụng không hợp lệ.');
        }

        $recurrenceCount = $this->calculateMonthlyRecurrence($employeeId, $dueMonth, $category);

        $payload = [
            'employee_id' => $employeeId,
            'violation_date' => $violationDate,
            'due_month' => $dueMonth,
            'category' => $category,
            'behavior' => $this->makeBehaviorFromExplanation($explanation, $category),
            'rank_level' => $rankLevel,
            'base_amount' => 0,
            'amount' => 0,
            'recurrence_count' => $recurrenceCount,
            'status' => 'notified',
            'collection_method' => $data['collection_method'] ?? 'cash',
            'explanation' => $explanation,
            'hr_note' => $data['hr_note'] ?? null,
            'admin_note' => $data['admin_note'] ?? null,
            'notified_at' => date('Y-m-d H:i:s'),
            'created_by' => (int)session()->get('employee_id'),
            'updated_by' => (int)session()->get('employee_id'),
        ];

        if ($payload['explanation'] === '') {
            return $this->fail('Vui lòng nhập giải trình/bối cảnh vi phạm.');
        }

        if (!$this->model->insert($payload)) {
            return $this->fail('Không thể lưu khoản vi phạm.');
        }

        $id = (int)$this->model->getInsertID();
        $this->notifyViolationCreated($id, $payload, $employee);

        return $this->success(['id' => $id], 'Đã ghi nhận vi phạm và gửi thông báo liên quan.');
    }

    public function updateCollection(int $id, array $data): array
    {
        if (!$this->canCollect()) {
            return $this->fail('Bạn chưa có quyền cập nhật trạng thái thu.');
        }

        $record = $this->model->find($id);
        if (!$record) {
            return $this->fail('Không tìm thấy khoản vi phạm.');
        }

        $status = $data['status'] ?? '';
        if (!in_array($status, ['notified', 'collected', 'waived'], true)) {
            return $this->fail('Trạng thái thu không hợp lệ.');
        }

        $amount = array_key_exists('amount', $data)
            ? $this->parseMoney($data['amount'])
            : (int)$record['amount'];
        if ($status === 'collected' && $amount <= 0) {
            return $this->fail('Vui lòng nhập số tiền thu trước khi xác nhận đã thu.');
        }

        $payload = [
            'status' => $status,
            'base_amount' => $amount,
            'amount' => $amount,
            'collection_method' => $data['collection_method'] ?? $record['collection_method'],
            'admin_note' => $data['admin_note'] ?? $record['admin_note'],
            'collected_at' => $status === 'collected' ? date('Y-m-d H:i:s') : null,
            'updated_by' => (int)session()->get('employee_id'),
        ];

        $this->model->update($id, $payload);

        if ($status === 'collected') {
            $this->notifyCollectionUpdated($id, array_merge($record, $payload));
        }

        return $this->success(null, 'Đã cập nhật trạng thái thu quỹ.');
    }

    public function delete(int $id): array
    {
        if (!$this->canManage()) {
            return $this->fail('Bạn chưa có quyền xóa khoản vi phạm.');
        }

        $record = $this->model->find($id);
        if (!$record) {
            return $this->fail('Không tìm thấy khoản vi phạm.');
        }

        $this->model->delete($id);
        return $this->success(null, 'Đã xóa khoản vi phạm.');
    }

    private function baseListBuilder()
    {
        return $this->model
            ->select('violation_funds.*, employees.full_name as employee_name, employees.position as employee_position, creators.full_name as creator_name, updaters.full_name as updater_name')
            ->select("(SELECT COUNT(*) FROM violation_funds vf2 WHERE vf2.employee_id = violation_funds.employee_id AND vf2.due_month = violation_funds.due_month AND vf2.category = violation_funds.category AND vf2.deleted_at IS NULL AND (vf2.violation_date < violation_funds.violation_date OR (vf2.violation_date = violation_funds.violation_date AND vf2.id <= violation_funds.id))) as category_recurrence_count", false)
            ->join('employees', 'employees.id = violation_funds.employee_id AND employees.deleted_at IS NULL', 'left')
            ->join('employees as creators', 'creators.id = violation_funds.created_by AND creators.deleted_at IS NULL', 'left')
            ->join('employees as updaters', 'updaters.id = violation_funds.updated_by AND updaters.deleted_at IS NULL', 'left')
            ->where('violation_funds.deleted_at', null);
    }

    private function applyVisibility($builder): void
    {
        if ($this->canViewAll()) {
            return;
        }

        $builder->where('violation_funds.employee_id', (int)session()->get('employee_id'));
    }

    private function applyFilters($builder, array $filters): void
    {
        if (!empty($filters['due_month'])) {
            $builder->where('violation_funds.due_month', $filters['due_month']);
        }
        if (!empty($filters['category'])) {
            $builder->where('violation_funds.category', $filters['category']);
        }
        if (!empty($filters['status'])) {
            $builder->where('violation_funds.status', $filters['status']);
        }
        if (!empty($filters['employee_id']) && $this->canViewAll()) {
            $builder->where('violation_funds.employee_id', (int)$filters['employee_id']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('violation_funds.explanation', $search)
                ->orLike('violation_funds.behavior', $search)
                ->orLike('violation_funds.hr_note', $search)
                ->orLike('violation_funds.admin_note', $search)
                ->orLike('employees.full_name', $search)
                ->groupEnd();
        }
    }

    private function getSummary(array $filters): array
    {
        $builder = $this->db->table('violation_funds')
            ->select('COUNT(*) as total_count')
            ->select('COALESCE(SUM(amount), 0) as total_amount')
            ->select("COALESCE(SUM(CASE WHEN status = 'collected' THEN amount ELSE 0 END), 0) as collected_amount", false)
            ->select("COALESCE(SUM(CASE WHEN status = 'notified' THEN amount ELSE 0 END), 0) as pending_amount", false)
            ->select("COALESCE(SUM(CASE WHEN status = 'waived' THEN amount ELSE 0 END), 0) as waived_amount", false)
            ->join('employees', 'employees.id = violation_funds.employee_id AND employees.deleted_at IS NULL', 'left')
            ->where('violation_funds.deleted_at', null);

        $this->applyVisibility($builder);
        $this->applyFilters($builder, $filters);
        $row = $builder->get()->getRowArray() ?? [];

        return [
            'total_count' => (int)($row['total_count'] ?? 0),
            'total_amount' => (int)($row['total_amount'] ?? 0),
            'collected_amount' => (int)($row['collected_amount'] ?? 0),
            'pending_amount' => (int)($row['pending_amount'] ?? 0),
            'waived_amount' => (int)($row['waived_amount'] ?? 0),
        ];
    }

    private function getCategoryBreakdown(array $filters): array
    {
        $builder = $this->db->table('violation_funds')
            ->select('category')
            ->select('COUNT(*) as count')
            ->select('COALESCE(SUM(amount), 0) as total')
            ->join('employees', 'employees.id = violation_funds.employee_id AND employees.deleted_at IS NULL', 'left')
            ->where('violation_funds.deleted_at', null)
            ->groupBy('category')
            ->orderBy('total', 'DESC');

        $this->applyVisibility($builder);
        $this->applyFilters($builder, $filters);

        return $builder->get()->getResultArray();
    }

    private function getEmployeeBreakdown(array $filters): array
    {
        $builder = $this->db->table('violation_funds')
            ->select('employees.full_name')
            ->select('COUNT(*) as count')
            ->select('COALESCE(SUM(violation_funds.amount), 0) as total')
            ->join('employees', 'employees.id = violation_funds.employee_id AND employees.deleted_at IS NULL', 'left')
            ->where('violation_funds.deleted_at', null)
            ->groupBy('violation_funds.employee_id, employees.full_name')
            ->orderBy('total', 'DESC')
            ->limit(8);

        $this->applyVisibility($builder);
        $this->applyFilters($builder, $filters);

        return $builder->get()->getResultArray();
    }

    private function getEmployeeOptions(): array
    {
        if (!$this->canViewAll()) {
            return [];
        }

        return $this->employeeModel
            ->select('employees.id, employees.full_name, employees.position')
            ->join('users', 'users.id = employees.user_id', 'inner')
            ->where('employees.deleted_at', null)
            ->where('users.active_status', 1)
            ->where('users.deleted_at', null)
            ->orderBy('employees.full_name', 'ASC')
            ->findAll();
    }

    private function notifyViolationCreated(int $id, array $payload, array $employee): void
    {
        $link = base_url('violation-funds?due_month=' . $payload['due_month']);
        $violationText = $this->getViolationText($payload);
        $message = 'Bạn được ghi nhận lỗi vi phạm nội bộ: ' . $violationText . '. Hành chính sẽ rà soát số tiền thu nếu lỗi được xác nhận.';

        if (!empty($employee['user_id'])) {
            $this->notificationService->sendToUser(
                (int)$employee['user_id'],
                'Thông báo vi phạm nội bộ',
                $message,
                'alert',
                $link
            );
        }

        $hcUsers = $this->getAdministrativeUserIds();
        $this->notificationService->sendToMultiple(
            $hcUsers,
            'Lỗi vi phạm cần rà soát',
            ($employee['full_name'] ?? 'Nhân sự') . ' phát sinh lỗi vi phạm - ' . $violationText,
            'approval',
            base_url('violation-funds'),
            session()->get('user_id')
        );
    }

    private function notifyCollectionUpdated(int $id, array $record): void
    {
        $employee = $this->employeeModel->find((int)$record['employee_id']);
        if (!$employee || empty($employee['user_id'])) {
            return;
        }

        $money = number_format((int)$record['amount'], 0, ',', '.') . 'đ';
        $violationText = $this->getViolationText($record);
        $this->notificationService->sendToUser(
            (int)$employee['user_id'],
            'Đã ghi nhận thu quỹ vi phạm',
            'Hành chính đã ghi nhận khoản quỹ vi phạm ' . $money . ' cho nội dung: ' . $violationText . '.',
            'system',
            base_url('violation-funds')
        );
    }

    private function getAdministrativeUserIds(): array
    {
        $rows = $this->userModel
            ->select('users.id')
            ->join('employees', 'employees.user_id = users.id AND employees.deleted_at IS NULL')
            ->where('employees.department_id', AppConstants::DEPT_HANH_CHINH)
            ->where('users.active_status', 1)
            ->where('users.deleted_at', null)
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'id'));
    }

    private function calculateMonthlyRecurrence(int $employeeId, string $dueMonth, string $category): int
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->where('due_month', $dueMonth)
            ->where('category', $category)
            ->where('deleted_at', null)
            ->countAllResults() + 1;
    }

    private function parseMoney($value): int
    {
        return (int)preg_replace('/[^\d]/', '', (string)$value);
    }

    private function makeBehaviorFromExplanation(string $explanation, string $category): string
    {
        $fallback = self::CATEGORY_LABELS[$category] ?? 'Khoản vi phạm';
        $text = trim($explanation) !== '' ? trim($explanation) : $fallback;

        return mb_substr($text, 0, 500);
    }

    private function getViolationText(array $record): string
    {
        $text = trim((string)($record['explanation'] ?? ''));
        if ($text === '') {
            $text = trim((string)($record['behavior'] ?? ''));
        }

        return mb_substr($text, 0, 220);
    }

    private function guessRankLevel(string $position): int
    {
        $normalized = mb_strtolower($position);
        if (mb_strpos($normalized, 'trưởng') !== false || mb_strpos($normalized, 'leader') !== false) {
            return 3;
        }
        if (mb_strpos($normalized, 'thực tập') !== false || mb_strpos($normalized, 'thử việc') !== false || mb_strpos($normalized, 'học việc') !== false) {
            return 1;
        }

        return 2;
    }
}
