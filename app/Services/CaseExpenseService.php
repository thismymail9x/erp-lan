<?php

namespace App\Services;

use App\Models\CaseExpenseAttachmentModel;
use App\Models\CaseExpenseModel;
use App\Models\WorkScheduleModel;
use Config\AppConstants;

/**
 * CaseExpenseService
 *
 * Xử lý toàn bộ nghiệp vụ chi phí xử lý vụ việc:
 * - Phân lập dữ liệu theo người được phân công trong vụ việc.
 * - Không để lịch công tác công khai lộ mã vụ việc hoặc khách hàng.
 * - Cung cấp số liệu cho kế toán duyệt và cho tab chi phí trong chi tiết vụ việc.
 */
class CaseExpenseService extends BaseService
{
    public const CATEGORY_LABELS = [
        'travel'  => 'Vé xe / vé máy bay',
        'fuel'    => 'Xăng xe',
        'taxi'    => 'Taxi / Grab',
        'meal'    => 'Ăn uống',
        'lodging' => 'Lưu trú',
        'fee'     => 'Lệ phí',
        'other'   => 'Khác',
    ];

    public const STATUS_LABELS = [
        'draft'    => 'Nháp',
        'pending'  => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
    ];

    protected $model;
    protected $attachmentModel;
    protected $db;

    public function __construct(
        CaseExpenseModel $model = null,
        CaseExpenseAttachmentModel $attachmentModel = null
    ) {
        parent::__construct();
        $this->model = $model ?? new CaseExpenseModel();
        $this->attachmentModel = $attachmentModel ?? new CaseExpenseAttachmentModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Người được xem mọi chi phí là Admin hoặc người có quyền được cấp thủ công.
     * Không khóa cứng theo phòng ban để anh có thể cấp quyền cho nhân sự xử lý.
     */
    public function canViewAll(): bool
    {
        return has_permission('sys.admin') || has_permission('case_expense.view_all');
    }

    public function canApprove(): bool
    {
        return has_permission('sys.admin') || has_permission('case_expense.approve');
    }

    public function canSubmit(): bool
    {
        return has_permission('sys.admin') || has_permission('case_expense.submit');
    }

    /**
     * Kiểm tra quyền nhìn thấy vụ việc theo nguyên tắc need-to-know.
     */
    public function canAccessCase(int $caseId, ?int $employeeId = null): bool
    {
        $employeeId = $employeeId ?: (int)session()->get('employee_id');
        if ($caseId <= 0 || $employeeId <= 0) {
            return false;
        }

        if (has_permission('sys.admin') || has_permission('case.view_all') || has_permission('case.edit_all')) {
            return true;
        }

        $case = $this->db->table('cases')
            ->select('id, assigned_lawyer_id, assigned_staff_id')
            ->where('id', $caseId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$case) {
            return false;
        }

        if ((int)$case['assigned_lawyer_id'] === $employeeId || (int)$case['assigned_staff_id'] === $employeeId) {
            return true;
        }

        $isMember = $this->db->table('case_members')
            ->where('case_id', $caseId)
            ->where('employee_id', $employeeId)
            ->where('deleted_at', null)
            ->countAllResults() > 0;

        if ($isMember) {
            return true;
        }

        if (has_permission('case_expense.view_team') || session()->get('role_name') === AppConstants::ROLE_TRUONG_PHONG) {
            $teamIds = $this->getManagedEmployeeIds($employeeId);
            if (!empty($teamIds)) {
                if (in_array((int)$case['assigned_lawyer_id'], $teamIds, true) || in_array((int)$case['assigned_staff_id'], $teamIds, true)) {
                    return true;
                }

                return $this->db->table('case_members')
                    ->where('case_id', $caseId)
                    ->whereIn('employee_id', $teamIds)
                    ->where('deleted_at', null)
                    ->countAllResults() > 0;
            }
        }

        return false;
    }

    /**
     * Danh sách vụ việc được phép chọn khi tạo lịch/chi phí. Dùng cho select để
     * người dùng thao tác nhanh nhưng vẫn không thấy vụ việc ngoài phạm vi.
     */
    public function getSelectableCases(?int $employeeId = null): array
    {
        $employeeId = $employeeId ?: (int)session()->get('employee_id');
        $builder = $this->db->table('cases')
            ->select('cases.id, cases.code, cases.title, customers.name as customer_name')
            ->join('customers', 'customers.id = cases.customer_id', 'left')
            ->where('cases.deleted_at', null)
            ->whereNotIn('cases.status', ['closed', 'cancelled', 'huy']);

        if (!$this->canViewAll() && !has_permission('case.view_all') && !has_permission('case.edit_all')) {
            $builder->join(
                'case_members',
                'case_members.case_id = cases.id AND case_members.employee_id = ' . (int)$employeeId . ' AND case_members.deleted_at IS NULL',
                'left'
            );
            $builder->groupStart()
                ->where('cases.assigned_lawyer_id', $employeeId)
                ->orWhere('cases.assigned_staff_id', $employeeId)
                ->orWhere('case_members.employee_id', $employeeId)
                ->groupEnd();
        }

        return $builder
            ->groupBy('cases.id')
            ->orderBy('cases.updated_at', 'DESC')
            ->limit(200)
            ->get()
            ->getResultArray();
    }

    public function getList(array $filters = [], int $perPage = 20): array
    {
        $builder = $this->model
            ->select('case_expenses.*, cases.code as case_code, cases.title as case_title, customers.name as customer_name, employees.full_name as employee_name, approver.full_name as approver_name')
            ->join('cases', 'cases.id = case_expenses.case_id', 'inner')
            ->join('customers', 'customers.id = cases.customer_id', 'left')
            ->join('employees', 'employees.id = case_expenses.employee_id', 'left')
            ->join('employees approver', 'approver.id = case_expenses.approved_by', 'left')
            ->where('case_expenses.deleted_at', null)
            ->where('cases.deleted_at', null);

        $this->applyScope($builder);

        if (!empty($filters['status'])) {
            $builder->where('case_expenses.status', $filters['status']);
        }
        if (!empty($filters['employee_id'])) {
            $builder->where('case_expenses.employee_id', (int)$filters['employee_id']);
        }
        if (!empty($filters['case_id'])) {
            $builder->where('case_expenses.case_id', (int)$filters['case_id']);
        }
        if (!empty($filters['month'])) {
            $builder->where('MONTH(case_expenses.expense_date)', (int)$filters['month']);
        }
        if (!empty($filters['year'])) {
            $builder->where('YEAR(case_expenses.expense_date)', (int)$filters['year']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('cases.code', $search)
                ->orLike('cases.title', $search)
                ->orLike('customers.name', $search)
                ->orLike('employees.full_name', $search)
                ->groupEnd();
        }

        $rows = $builder->orderBy('case_expenses.created_at', 'DESC')->paginate($perPage);
        return [
            'rows' => $this->appendAttachments($rows),
            'pager' => $this->model->pager,
            'stats' => $this->getStats($filters),
        ];
    }

    public function getByCase(int $caseId): array
    {
        if (!$this->canAccessCase($caseId) && !$this->canApprove()) {
            return ['rows' => [], 'stats' => $this->emptyStats()];
        }

        $rows = $this->model
            ->select('case_expenses.*, employees.full_name as employee_name, approver.full_name as approver_name')
            ->join('employees', 'employees.id = case_expenses.employee_id', 'left')
            ->join('employees approver', 'approver.id = case_expenses.approved_by', 'left')
            ->where('case_expenses.case_id', $caseId)
            ->where('case_expenses.deleted_at', null)
            ->orderBy('case_expenses.expense_date', 'DESC')
            ->findAll(200);

        return [
            'rows' => $this->appendAttachments($rows),
            'stats' => $this->getStats(['case_id' => $caseId], true),
        ];
    }

    public function create(array $data, array $files = []): array
    {
        $employeeId = (int)session()->get('employee_id');
        $caseId = (int)($data['case_id'] ?? 0);
        $workScheduleId = (int)($data['work_schedule_id'] ?? 0);
        $schedule = $workScheduleId > 0 ? $this->getSchedulePrefill($workScheduleId) : null;
        if ($schedule) {
            $caseId = (int)$schedule['case_id'];
        } elseif ($workScheduleId > 0) {
            return $this->fail('Lich cong tac lien quan khong hop le hoac khong thuoc pham vi duoc xem.');
        }
        if (!$this->canSubmit() || !$this->canAccessCase($caseId, $employeeId)) {
            return $this->fail('Bạn không có quyền ghi chi phí cho vụ việc này.');
        }

        $amount = $this->parseMoney($data['amount'] ?? 0);
        if ($amount <= 0) {
            return $this->fail('Số tiền chi phí phải lớn hơn 0.');
        }

        $actualHours = $this->calculateHours($data['actual_start_at'] ?? null, $data['actual_end_at'] ?? null, $data['actual_hours'] ?? null);
        $payload = [
            'case_id' => $caseId,
            'work_schedule_id' => $workScheduleId > 0 ? $workScheduleId : null,
            'employee_id' => !empty($schedule['employee_id']) ? (int)$schedule['employee_id'] : (!empty($data['employee_id']) ? (int)$data['employee_id'] : $employeeId),
            'created_by' => $employeeId,
            'expense_date' => $schedule['expense_date'] ?? ($data['expense_date'] ?? date('Y-m-d')),
            'category' => $data['category'] ?? 'other',
            'amount' => $amount,
            'actual_start_at' => $this->normalizeDateTime($schedule['start_at'] ?? ($data['actual_start_at'] ?? null)),
            'actual_end_at' => $this->normalizeDateTime($schedule['end_at'] ?? ($data['actual_end_at'] ?? null)),
            'actual_hours' => $schedule['actual_hours'] ?? $actualHours,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ];

        $duplicate = $this->findRecentDuplicate($payload);
        if ($duplicate) {
            return $this->success(['id' => (int)$duplicate['id']], 'Phiếu chi phí này đã được ghi nhận trước đó.');
        }

        if (!$this->model->insert($payload)) {
            return $this->fail('Không thể lưu phiếu chi phí.');
        }

        $expenseId = (int)$this->model->getInsertID();
        $this->storeAttachments($expenseId, $files, $employeeId);

        (new SystemLogService())->log('CASE_EXPENSE_CREATE', 'CaseExpense', $expenseId, [
            'case_id' => $caseId,
            'amount' => $amount,
        ]);

        return $this->success(['id' => $expenseId], 'Đã gửi chi phí chờ duyệt.');
    }

    public function approve(int $id, string $status, int $approvedAmount, ?string $note): array
    {
        if (!$this->canApprove()) {
            return $this->fail('Bạn không có quyền duyệt chi phí.');
        }

        $expense = $this->model->find($id);
        if (!$expense) {
            return $this->fail('Không tìm thấy phiếu chi phí.');
        }

        if (!in_array($status, ['approved', 'rejected'], true)) {
            return $this->fail('Trạng thái duyệt không hợp lệ.');
        }

        $approvedAmount = $status === 'approved' ? max(0, $approvedAmount) : 0;
        if ($status === 'approved' && $approvedAmount <= 0) {
            $approvedAmount = (int)$expense['amount'];
        }

        $this->model->update($id, [
            'status' => $status,
            'approved_amount' => $approvedAmount,
            'approval_note' => $note,
            'approved_by' => (int)session()->get('employee_id'),
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        (new SystemLogService())->log('CASE_EXPENSE_APPROVE', 'CaseExpense', $id, [
            'status' => $status,
            'approved_amount' => $approvedAmount,
        ]);

        return $this->success(null, 'Đã cập nhật trạng thái duyệt chi phí.');
    }

    public function update(int $id, array $data): array
    {
        if (!$this->canApprove()) {
            return $this->fail('Bạn không có quyền sửa chi phí.');
        }

        $expense = $this->model->find($id);
        if (!$expense) {
            return $this->fail('Không tìm thấy phiếu chi phí.');
        }

        $caseId = (int)($data['case_id'] ?? $expense['case_id']);
        $workScheduleId = array_key_exists('work_schedule_id', $data)
            ? (int)$data['work_schedule_id']
            : (int)($expense['work_schedule_id'] ?? 0);
        $schedule = $workScheduleId > 0 ? $this->getSchedulePrefill($workScheduleId) : null;
        if ($schedule) {
            $caseId = (int)$schedule['case_id'];
        } elseif ($workScheduleId > 0) {
            return $this->fail('Lich cong tac lien quan khong hop le hoac khong thuoc pham vi duoc xem.');
        }
        if (!$this->canAccessCase($caseId)) {
            return $this->fail('Bạn không có quyền sửa chi phí cho vụ việc này.');
        }

        $amount = $this->parseMoney($data['amount'] ?? $expense['amount']);
        if ($amount <= 0) {
            return $this->fail('Số tiền chi phí phải lớn hơn 0.');
        }

        $approvedAmount = isset($data['approved_amount']) && $data['approved_amount'] !== ''
            ? $this->parseMoney($data['approved_amount'])
            : null;

        $this->model->update($id, [
            'case_id' => $caseId,
            'work_schedule_id' => $workScheduleId > 0 ? $workScheduleId : null,
            'employee_id' => !empty($schedule['employee_id']) ? (int)$schedule['employee_id'] : (int)$expense['employee_id'],
            'expense_date' => $schedule['expense_date'] ?? ($data['expense_date'] ?? $expense['expense_date']),
            'category' => $data['category'] ?? $expense['category'],
            'amount' => $amount,
            'approved_amount' => $approvedAmount,
            'actual_start_at' => $this->normalizeDateTime($schedule['start_at'] ?? ($data['actual_start_at'] ?? null)),
            'actual_end_at' => $this->normalizeDateTime($schedule['end_at'] ?? ($data['actual_end_at'] ?? null)),
            'actual_hours' => $schedule['actual_hours'] ?? $this->calculateHours($data['actual_start_at'] ?? null, $data['actual_end_at'] ?? null, $data['actual_hours'] ?? $expense['actual_hours']),
            'note' => $data['note'] ?? null,
        ]);

        (new SystemLogService())->log('CASE_EXPENSE_UPDATE', 'CaseExpense', $id, [
            'case_id' => $caseId,
            'amount' => $amount,
        ]);

        return $this->success(null, 'Đã cập nhật chi phí.');
    }

    public function delete(int $id): array
    {
        $expense = $this->model->find($id);
        if (!$expense) {
            return $this->fail('Không tìm thấy phiếu chi phí.');
        }

        $employeeId = (int)session()->get('employee_id');
        $canDelete = $this->canApprove() || ((int)$expense['created_by'] === $employeeId && in_array($expense['status'], ['pending', 'rejected'], true));
        if (!$canDelete) {
            return $this->fail('Bạn không có quyền xóa phiếu chi phí này.');
        }

        $this->model->delete($id);
        return $this->success(null, 'Đã xóa phiếu chi phí.');
    }

    public function deleteByWorkSchedule(int $workScheduleId): void
    {
        if ($workScheduleId <= 0) {
            return;
        }

        $this->db->table('case_expenses')
            ->where('work_schedule_id', $workScheduleId)
            ->where('deleted_at', null)
            ->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function getScheduleCasePayload(?int $caseId): ?array
    {
        if (!$caseId || !$this->canAccessCase((int)$caseId)) {
            return null;
        }

        return $this->db->table('cases')
            ->select('cases.id, cases.code, cases.title, customers.name as customer_name')
            ->join('customers', 'customers.id = cases.customer_id', 'left')
            ->where('cases.id', (int)$caseId)
            ->where('cases.deleted_at', null)
            ->get()
            ->getRowArray();
    }

    public function getScheduleOptionsByCase(int $caseId): array
    {
        if ($caseId <= 0 || !$this->canAccessCase($caseId)) {
            return [];
        }

        $rows = $this->db->table('work_schedules')
            ->select('work_schedules.id, work_schedules.title, work_schedules.location, work_schedules.start_at, work_schedules.end_at, work_schedules.employee_id, employees.full_name as employee_name')
            ->join('employees', 'employees.id = work_schedules.employee_id', 'left')
            ->where('work_schedules.case_id', $caseId)
            ->where('work_schedules.deleted_at', null)
            ->orderBy('work_schedules.start_at', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();

        return array_map(function(array $row) {
            $row['actual_hours'] = $this->calculateHours($row['start_at'] ?? null, $row['end_at'] ?? null, 0);
            $row['expense_date'] = !empty($row['start_at']) ? date('Y-m-d', strtotime($row['start_at'])) : date('Y-m-d');
            $row['label'] = date('d/m/Y H:i', strtotime($row['start_at'])) . ' - ' . ($row['employee_name'] ?? '') . ' - ' . ($row['title'] ?? '');
            return $row;
        }, $rows);
    }

    public function getSchedulePrefill(int $workScheduleId): ?array
    {
        if ($workScheduleId <= 0) {
            return null;
        }

        $schedule = $this->db->table('work_schedules')
            ->select('work_schedules.id, work_schedules.case_id, work_schedules.employee_id, work_schedules.title, work_schedules.location, work_schedules.start_at, work_schedules.end_at, cases.code as case_code, cases.title as case_title, employees.full_name as employee_name')
            ->join('cases', 'cases.id = work_schedules.case_id AND cases.deleted_at IS NULL', 'inner')
            ->join('employees', 'employees.id = work_schedules.employee_id', 'left')
            ->where('work_schedules.id', $workScheduleId)
            ->where('work_schedules.deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$schedule || !$this->canAccessCase((int)$schedule['case_id'])) {
            return null;
        }

        $schedule['actual_hours'] = $this->calculateHours($schedule['start_at'] ?? null, $schedule['end_at'] ?? null, 0);
        $schedule['expense_date'] = !empty($schedule['start_at']) ? date('Y-m-d', strtotime($schedule['start_at'])) : date('Y-m-d');
        $schedule['label'] = date('d/m/Y H:i', strtotime($schedule['start_at'])) . ' - ' . ($schedule['employee_name'] ?? '') . ' - ' . ($schedule['title'] ?? '');

        return $schedule;
    }

    private function applyScope($builder): void
    {
        if ($this->canViewAll() || $this->canApprove()) {
            return;
        }

        $employeeId = (int)session()->get('employee_id');
        if (has_permission('case_expense.view_team') || session()->get('role_name') === AppConstants::ROLE_TRUONG_PHONG) {
            $teamIds = $this->getManagedEmployeeIds($employeeId);
            if (!empty($teamIds)) {
                $builder->groupStart()
                    ->where('case_expenses.employee_id', $employeeId)
                    ->orWhereIn('case_expenses.employee_id', $teamIds)
                    ->groupEnd();
                return;
            }
        }

        $builder->where('case_expenses.employee_id', $employeeId);
    }

    private function getManagedEmployeeIds(int $managerId): array
    {
        if ($managerId <= 0) {
            return [];
        }

        $rows = $this->db->table('employees')
            ->select('id')
            ->where('manager_id', $managerId)
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'id'));
    }

    private function getStats(array $filters = [], bool $trustedCaseScope = false): array
    {
        $builder = $this->db->table('case_expenses')
            ->select('COUNT(*) as total_count')
            ->select('COALESCE(SUM(case_expenses.amount), 0) as requested_total')
            ->select('COALESCE(SUM(CASE WHEN case_expenses.status = "approved" THEN COALESCE(case_expenses.approved_amount, case_expenses.amount) ELSE 0 END), 0) as approved_total')
            ->select('COALESCE(SUM(CASE WHEN case_expenses.status = "pending" THEN case_expenses.amount ELSE 0 END), 0) as pending_total')
            ->select('COALESCE(SUM(ABS(case_expenses.actual_hours)), 0) as total_hours')
            ->select('COALESCE(SUM(CASE WHEN case_expenses.status = "approved" THEN ABS(case_expenses.actual_hours) ELSE 0 END), 0) as approved_hours')
            ->join('cases', 'cases.id = case_expenses.case_id', 'inner')
            ->where('case_expenses.deleted_at', null)
            ->where('cases.deleted_at', null);

        if (!empty($filters['search'])) {
            $builder
                ->join('customers', 'customers.id = cases.customer_id', 'left')
                ->join('employees', 'employees.id = case_expenses.employee_id', 'left');
        }

        if (!$trustedCaseScope && !$this->canViewAll() && !$this->canApprove()) {
            $employeeId = (int)session()->get('employee_id');
            if (has_permission('case_expense.view_team') || session()->get('role_name') === AppConstants::ROLE_TRUONG_PHONG) {
                $teamIds = $this->getManagedEmployeeIds($employeeId);
                if (!empty($teamIds)) {
                    $builder->groupStart()
                        ->where('case_expenses.employee_id', $employeeId)
                        ->orWhereIn('case_expenses.employee_id', $teamIds)
                        ->groupEnd();
                } else {
                    $builder->where('case_expenses.employee_id', $employeeId);
                }
            } else {
                $builder->where('case_expenses.employee_id', $employeeId);
            }
        }

        if (!empty($filters['case_id'])) {
            $builder->where('case_expenses.case_id', (int)$filters['case_id']);
        }
        if (!empty($filters['employee_id'])) {
            $builder->where('case_expenses.employee_id', (int)$filters['employee_id']);
        }
        if (!empty($filters['month'])) {
            $builder->where('MONTH(case_expenses.expense_date)', (int)$filters['month']);
        }
        if (!empty($filters['year'])) {
            $builder->where('YEAR(case_expenses.expense_date)', (int)$filters['year']);
        }
        if (!empty($filters['status'])) {
            $builder->where('case_expenses.status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('cases.code', $search)
                ->orLike('cases.title', $search)
                ->orLike('customers.name', $search)
                ->orLike('employees.full_name', $search)
                ->groupEnd();
        }

        $row = $builder->get()->getRowArray();
        return $row ?: $this->emptyStats();
    }

    private function emptyStats(): array
    {
        return [
            'total_count' => 0,
            'requested_total' => 0,
            'approved_total' => 0,
            'pending_total' => 0,
            'total_hours' => 0,
            'approved_hours' => 0,
        ];
    }

    private function appendAttachments(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $ids = array_column($rows, 'id');
        $attachments = $this->attachmentModel
            ->whereIn('expense_id', $ids)
            ->where('deleted_at', null)
            ->findAll();

        foreach ($rows as &$row) {
            $row['actual_hours'] = abs((float)($row['actual_hours'] ?? 0));
            $row['attachments'] = array_values(array_filter($attachments, function($attachment) use ($row) {
                return (int)$attachment['expense_id'] === (int)$row['id'];
            }));
        }

        return $rows;
    }

    private function findRecentDuplicate(array $payload): ?array
    {
        $builder = $this->db->table('case_expenses')
            ->select('id')
            ->where('case_id', (int)$payload['case_id'])
            ->where('employee_id', (int)$payload['employee_id'])
            ->where('created_by', (int)$payload['created_by'])
            ->where('expense_date', $payload['expense_date'])
            ->where('category', $payload['category'])
            ->where('amount', (int)$payload['amount'])
            ->where('status', 'pending')
            ->where('deleted_at', null)
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 30));

        foreach (['work_schedule_id', 'actual_start_at', 'actual_end_at', 'note'] as $field) {
            if ($payload[$field] === null || $payload[$field] === '') {
                $builder->where($field, null);
            } else {
                $builder->where($field, $payload[$field]);
            }
        }

        $builder->where('ABS(actual_hours - ' . (float)$payload['actual_hours'] . ') <', 0.0001, false);

        return $builder->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray() ?: null;
    }

    private function storeAttachments(int $expenseId, array $files, int $employeeId): void
    {
        if (empty($files['attachments'])) {
            return;
        }

        $upload = $files['attachments'];
        $items = is_array($upload) ? $upload : [$upload];
        $targetDir = WRITEPATH . 'uploads/case_expenses';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        foreach ($items as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                continue;
            }

            $newName = $file->getRandomName();
            $file->move($targetDir, $newName);
            $this->attachmentModel->insert([
                'expense_id' => $expenseId,
                'file_name' => $file->getClientName(),
                'file_path' => 'case_expenses/' . $newName,
                'file_type' => $file->getMimeType(),
                'uploaded_by' => $employeeId,
            ]);
        }
    }

    private function parseMoney($value): int
    {
        return (int)preg_replace('/[^\d]/', '', (string)$value);
    }

    private function normalizeDateTime(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return str_replace('T', ' ', $value) . (strlen($value) === 16 ? ':00' : '');
    }

    private function calculateHours($start, $end, $fallback): float
    {
        if (!empty($start) && !empty($end)) {
            $startTime = strtotime($this->normalizeDateTime($start));
            $endTime = strtotime($this->normalizeDateTime($end));
            if ($startTime && $endTime) {
                return round(abs($endTime - $startTime) / 3600, 2);
            }
        }

        return abs((float)str_replace(',', '.', (string)$fallback));
    }
}
