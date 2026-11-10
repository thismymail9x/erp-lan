<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\CaseHistoryModel;
use InvalidArgumentException;

/**
 * Phân công đầu mối quản lý trực tiếp trên vụ việc, tái sử dụng nhân sự và case_members hiện có.
 * Người quản lý chịu trách nhiệm điều phối, còn các thành viên giữ vai trò thực hiện/duyệt/hỗ trợ.
 */
class CaseManagementService
{
    private ?bool $schemaReady = null;

    /** Chờ migration một cột, không làm hỏng các màn hình cũ trong lúc nâng cấp. */
    public function ready(): bool
    {
        return $this->schemaReady ??= db_connect()->fieldExists('case_manager_id', 'cases');
    }

    /** Tận dụng quyền quản lý toàn bộ hiện có để giao hoặc chuyển đầu mối, không tạo thêm RBAC. */
    public function canAssignManager(): bool
    {
        return (int) session()->get('user_id') > 0 && (has_permission('sys.admin') || has_permission('case.edit_all'));
    }

    /** Giám đốc chuyên môn dùng case.view_all hiện có, quyền xem không tự cấp quyền phân công. */
    public function canViewAll(): bool
    {
        return $this->canAssignManager() || ((int) session()->get('user_id') > 0 && has_permission('case.view_all'));
    }

    /** Bất kỳ nhân sự nào được giao đầu mối đều quản lý được vụ đó, không cần chức danh trưởng phòng. */
    public function canManage(array $case): bool
    {
        return $this->canAssignManager() || ((int) session()->get('user_id') > 0
            && (int) session()->get('employee_id') > 0
            && (int) ($case['case_manager_id'] ?? 0) === (int) session()->get('employee_id'));
    }

    /** Dùng phân công thực tế để hiện công cụ quản lý ngay cả khi nhân sự chưa có chức danh. */
    public function hasManagedCases(): bool
    {
        return $this->ready() && (int) session()->get('employee_id') > 0
            && (new CaseModel())->where('case_manager_id', session()->get('employee_id'))->where('deleted_at', null)->countAllResults() > 0;
    }

    /** Danh mục chọn đầu mối lấy từ nhân sự pháp lý hiện có, chuyên môn dùng để gợi ý trên form. */
    public function choices(): array
    {
        return $this->ready() && (int) session()->get('user_id') > 0
            ? get_available_employees(\Config\AppConstants::DEPT_PHAP_LY) : [];
    }

    /** Trả tên người quản lý sau khi Controller đã kiểm tra quyền xem hồ sơ. */
    public function managerInfo(array $case): ?array
    {
        return !empty($case['case_manager_id']) ? model('EmployeeModel')->select('id, full_name')->find($case['case_manager_id']) : null;
    }

    /** Phạm vi chỉ dựa vào quản lý vụ việc và tư cách tham gia, không lan sang mọi vụ của đồng nghiệp. */
    public function scope($query): void
    {
        if ($this->canViewAll()) {
            return;
        }
        $employeeId = (int) session()->get('employee_id');
        if ((int) session()->get('user_id') < 1) {
            $query->where('1 = 0');
            return;
        }
        $employeeId = $employeeId > 0 ? $employeeId : -1;
        $query->groupStart()->where('cases.case_manager_id', $employeeId)
            ->orWhere('cases.assigned_lawyer_id', $employeeId)->orWhere('cases.assigned_staff_id', $employeeId)
            ->orWhereIn('cases.id', static function ($builder) use ($employeeId) {
                // case_members là bảng cũ xóa thật, không có cột deleted_at.
                $builder->select('case_id')->from('case_members')->where('employee_id', $employeeId);
            });
        if (has_permission(CaseAccountingAccess::PERMISSION)) {
            $userId = (int) session()->get('user_id');
            $query->orWhereIn('cases.id', static function ($builder) use ($userId) {
                // case_history là bảng nhật ký cũ, chỉ có created_at và không có deleted_at.
                $builder->select('ch.case_id')->from('case_history ch')->where('ch.user_id', $userId)->where('ch.action', 'tiep_nhan')
                    ->where('ch.id', static function ($first) {
                        $first->from('case_history initial')->selectMin('initial.id')
                            ->where('initial.case_id = ch.case_id', null, false)->where('initial.action', 'tiep_nhan');
                    }, false);
            });
        }
        $query->groupEnd();
    }

    /** Dùng cùng phạm vi cho URL chi tiết và danh sách để tránh lọt dữ liệu khi sửa ID. */
    public function canView(array $case): bool
    {
        if (!$this->ready()) {
            return false;
        }
        if ($this->canViewAll()) {
            return true;
        }
        if (empty($case['case_manager_id'])) {
            return false;
        }
        $query = new CaseModel();
        $this->scope($query);
        return $query->where('cases.deleted_at', null)->find($case['id']) !== null;
    }

    /** Người tạo chọn đầu mối, không tự cấp thêm thành viên hay quyền quản lý cho chính mình. */
    public function validateCreation(array &$input, array $post): void
    {
        if (!$this->ready()) {
            unset($input['case_manager_id']);
            return;
        }
        $this->validateManager($input['case_manager_id'] ?? null);
    }

    /** Chỉ đầu mối hoặc quản lý toàn bộ được đổi nhân sự, còn đổi đầu mối dành quyền quản lý toàn bộ. */
    public function validateUpdate(array $case, array &$input): void
    {
        if (!$this->ready()) {
            unset($input['case_manager_id']);
            return;
        }
        if (array_key_exists('case_manager_id', $input)) {
            $this->validateManager($input['case_manager_id']);
            if ((int) $input['case_manager_id'] !== (int) ($case['case_manager_id'] ?? 0)) {
                $this->require($this->canAssignManager());
            }
        }
        if (empty($case['case_manager_id'])) {
            return;
        }
        foreach (['assignees' => 'assignee', 'supporters' => 'supporter', 'approvers' => 'approver'] as $field => $role) {
            $incoming = $input[$field] ?? [];
            $this->validateEmployees($incoming);
            $existing = model('CaseMemberModel')->where('case_id', $case['id'])->where('role_in_case', $role)->findColumn('employee_id') ?? [];
            // Hồ sơ cũ có thể chỉ lưu người chính trên cases, coi là cùng một phân công khi chuẩn hóa form.
            if ($role === 'assignee' && $existing === []) {
                $existing = array_filter([$case['assigned_lawyer_id'] ?: $case['assigned_staff_id']]);
            }
            $old = array_map('intval', $existing);
            $new = array_map('intval', $incoming);
            sort($old);
            sort($new);
            if ($old !== $new) {
                $this->require($this->canManage($case));
            }
        }
        foreach (['assigned_lawyer_id', 'assigned_staff_id'] as $field) {
            if (array_key_exists($field, $input) && (int) $input[$field] !== (int) $case[$field]) {
                $this->require($this->canManage($case));
                $this->validateEmployees(empty($input[$field]) ? [] : [$input[$field]]);
            }
        }
    }

    /** Kiểm tra nhân sự đã tồn tại, cho phép người chưa có vai trò/tài khoản được tham gia vụ việc. */
    public function validateEmployees($ids): void
    {
        if (!is_array($ids)) {
            throw new InvalidArgumentException('Danh sách nhân sự không hợp lệ.');
        }
        foreach ($ids as $id) {
            $this->validate(['employee_id' => $id], ['employee_id' => 'required|is_natural_no_zero']);
            if (!model('EmployeeModel')->find($id)) {
                throw new InvalidArgumentException('Nhân sự không tồn tại hoặc đã bị xóa.');
            }
        }
    }

    /** Đầu mối được để trống với hồ sơ chưa phân loại, nếu chọn thì phải là nhân sự pháp lý hiện hữu. */
    private function validateManager($id): void
    {
        $this->validate(['case_manager_id' => $id], ['case_manager_id' => 'permit_empty|is_natural_no_zero']);
        if ($id === null || $id === '') {
            return;
        }
        $employee = model('EmployeeModel')->find($id);
        if (!$employee || (int) $employee['department_id'] !== \Config\AppConstants::DEPT_PHAP_LY) {
            throw new InvalidArgumentException('Người quản lý phải là nhân sự pháp lý đang tồn tại.');
        }
    }

    /** Chuyển hàng loạt chỉ cập nhật một cột quản lý, giữ nguyên thành viên và tiến độ sẵn có. */
    public function assignManagers(array $input): void
    {
        $this->require($this->canAssignManager() && $this->ready());
        $this->validate($input, ['case_ids' => 'required', 'case_ids.*' => 'required|is_natural_no_zero']);
        if (!is_array($input['case_ids']) || count($input['case_ids']) > 100) {
            throw new InvalidArgumentException('Mỗi lần chọn từ 1 đến 100 vụ việc.');
        }
        $managerId = $input['case_manager_id'] ?? null;
        $this->validateManager($managerId);
        $db = db_connect();
        $db->transBegin();
        try {
            $ids = array_unique(array_map('intval', $input['case_ids']));
            $query = $db->table('cases')->whereIn('id', $ids)->where('deleted_at', null)->orderBy('id');
            $sql = $query->getCompiledSelect();
            $cases = $db->query($sql . ($db->DBDriver === 'MySQLi' ? ' FOR UPDATE' : ''))->getResultArray();
            if (count($ids) !== count($cases)) {
                throw new InvalidArgumentException('Có vụ việc đã bị xóa hoặc không tồn tại. Không vụ nào được thay đổi.');
            }
            foreach ($cases as $case) {
                if ((int) ($case['case_manager_id'] ?? 0) === (int) $managerId) {
                    continue;
                }
                if (!(new CaseModel())->update($case['id'], ['case_manager_id' => $managerId ?: null])) {
                    throw new InvalidArgumentException('Không thể cập nhật người quản lý vụ việc.');
                }
                $this->recordChange($case, $managerId);
            }
            if (!$db->transStatus()) {
                throw new InvalidArgumentException('Không thể lưu phân công. Toàn bộ thao tác đã được hoàn tác.');
            }
            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();
            throw $error;
        }
    }

    /** Phân công bằng case_members cũ, đồng bộ người phụ trách chính và bước đang làm trong cùng giao dịch. */
    public function updateMembers(int $caseId, array $input): void
    {
        $case = (new CaseModel())->find($caseId);
        $this->require($case && $this->canManage($case));
        $this->validateUpdate($case, $input);
        $groups = ['approvers' => 'approver', 'assignees' => 'assignee', 'supporters' => 'supporter'];
        foreach ($groups as $field => $role) {
            $this->validateEmployees($input[$field] ?? []);
        }
        $db = db_connect();
        $db->transBegin();
        try {
            foreach ($groups as $field => $role) {
                model('CaseMemberModel')->syncMembers($caseId, $role, array_unique($input[$field] ?? []));
            }
            $primary = array_values($input['assignees'] ?? [])[0] ?? null;
            if (!(new CaseModel())->update($caseId, ['assigned_lawyer_id' => $primary, 'assigned_staff_id' => null])) {
                throw new InvalidArgumentException('Không thể cập nhật người phụ trách vụ việc.');
            }
            model('CaseStepModel')->where('case_id', $caseId)->where('deleted_at', null)
                ->whereIn('status', ['pending', 'active', 'pending_approval', 'overdue'])->set(['assigned_to' => $primary])->update();
            (new CaseHistoryModel())->insert(['case_id' => $caseId, 'user_id' => session()->get('user_id'), 'action' => 'phan_cong_nhan_su',
                'note' => 'Người quản lý cập nhật nhân sự thực hiện, hỗ trợ và phê duyệt vụ việc.']);
            if (!$db->transStatus()) {
                throw new InvalidArgumentException('Không thể lưu phân công. Toàn bộ thao tác đã được hoàn tác.');
            }
            $db->transCommit();
        } catch (\Throwable $error) {
            $db->transRollback();
            throw $error;
        }
    }

    /** Nhật ký dùng bảng lịch sử hiện có, không tạo bảng hay hệ thống theo dõi mới. */
    public function recordChange(array $case, $managerId): void
    {
        if ((int) ($case['case_manager_id'] ?? 0) !== (int) $managerId) {
            if (!(new CaseHistoryModel())->insert(['case_id' => $case['id'], 'user_id' => session()->get('user_id'),
                'action' => 'phan_cong_quan_ly', 'old_value' => $case['case_manager_id'] ?? null,
                'new_value' => $managerId ?: null, 'note' => 'Cập nhật đầu mối quản lý vụ việc.'])) {
                throw new InvalidArgumentException('Không thể ghi lịch sử phân công quản lý.');
            }
        }
    }

    /** Validator kiểm tra ID trước khi truy vấn, tránh tin dữ liệu gửi từ trình duyệt. */
    private function validate(array $input, array $rules): void
    {
        $validation = service('validation');
        $validation->reset();
        if (!$validation->setRules($rules)->run($input)) {
            throw new InvalidArgumentException(implode(' ', $validation->getErrors()));
        }
    }

    /** Lỗi phân quyền được trả HTTP 403 thay vì lỗi hệ thống hoặc âm thầm cập nhật. */
    private function require(bool $allowed): void
    {
        if (!$allowed) {
            throw new \App\Exceptions\CaseManagementForbiddenException('Bạn không có quyền thay đổi phân công vụ việc này.');
        }
    }
}
