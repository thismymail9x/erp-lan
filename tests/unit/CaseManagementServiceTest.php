<?php

use App\Services\CaseManagementService;
use App\Exceptions\CaseManagementForbiddenException;
use CodeIgniter\Test\CIUnitTestCase;

/** Kiểm tra phân công quản lý bằng một cột và các bảng nhân sự/vụ việc đang có. */
final class CaseManagementServiceTest extends CIUnitTestCase
{
    private array $state = [];
    private array $tables = ['employees', 'users', 'roles', 'customers', 'cases', 'case_members', 'case_steps', 'case_history'];

    /** Dữ liệu bộ nhớ tách biệt với vận hành, đầu mối không có quyền hoặc chức danh quản lý. */
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = db_connect('tests');
        $schemas = [
            'employees' => 'id INTEGER PRIMARY KEY, user_id INTEGER, department_id INTEGER, full_name TEXT, specialties TEXT, deleted_at TEXT',
            'users' => 'id INTEGER PRIMARY KEY, role_id INTEGER, active_status INTEGER, deleted_at TEXT',
            'roles' => 'id INTEGER PRIMARY KEY, name TEXT',
            'customers' => 'id INTEGER PRIMARY KEY, name TEXT, deleted_at TEXT',
            'cases' => 'id INTEGER PRIMARY KEY, case_manager_id INTEGER, assigned_lawyer_id INTEGER, assigned_staff_id INTEGER, customer_id INTEGER, title TEXT, code TEXT, status TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT',
            'case_members' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, case_id INTEGER, employee_id INTEGER, role_in_case TEXT, created_at TEXT, updated_at TEXT',
            'case_steps' => 'id INTEGER PRIMARY KEY, case_id INTEGER, assigned_to INTEGER, completed_by INTEGER, status TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT',
            'case_history' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, case_id INTEGER, user_id INTEGER, action TEXT, old_value TEXT, new_value TEXT, note TEXT, created_at TEXT',
        ];
        foreach ($schemas as $table => $columns) {
            $this->db->query('CREATE TABLE ' . $this->db->prefixTable($table) . ' (' . $columns . ')');
        }
        for ($id = 1; $id <= 4; $id++) {
            $this->db->table('employees')->insert(['id' => $id, 'user_id' => $id < 4 ? $id + 100 : null,
                'department_id' => 3, 'full_name' => 'Nhân sự ' . $id]);
            if ($id < 4) {
                $this->db->table('users')->insert(['id' => $id + 100, 'role_id' => 1, 'active_status' => 1]);
            }
        }
        $this->db->table('roles')->insert(['id' => 1, 'name' => 'Nhân viên chính thức']);
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'Khách hàng']);
        foreach ([1 => 1, 2 => 2, 3 => 1, 4 => null] as $id => $managerId) {
            $this->db->table('cases')->insert(['id' => $id, 'case_manager_id' => $managerId, 'assigned_lawyer_id' => $id === 3 ? 3 : null,
                'customer_id' => 1, 'title' => 'Vụ việc ' . $id, 'code' => 'VV-' . $id, 'status' => 'cho_tiep_nhan']);
        }
        $session = $this->getMockBuilder(\CodeIgniter\Session\Session::class)->disableOriginalConstructor()->onlyMethods(['get'])->getMock();
        $session->method('get')->willReturnCallback(fn ($key) => $this->state[$key] ?? null);
        \Config\Services::injectMock('session', $session);
        $this->login(1);
    }

    /** Dọn bộ nhớ để các bộ kiểm thử phân quyền hiện có không bị ảnh hưởng. */
    protected function tearDown(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            $this->db->query('DROP TABLE ' . $this->db->prefixTable($table));
        }
        \Config\Services::resetSingle('session');
        parent::tearDown();
    }

    /** Đổi người dùng trong kiểm thử mà không cấp quyền quản lý qua chức danh. */
    private function login(int $id, array $permissions = []): void
    {
        $this->state = ['employee_id' => $id, 'user_id' => $id + 100, 'department_id' => 3,
            'role_name' => 'Nhân viên chính thức', 'permissions' => $permissions];
    }

    /** Đầu mối phải thấy cả vụ chưa phân người xử lý, dù chưa có chức danh hoặc RBAC quản lý. */
    public function testManagerSeesAllManagedCasesWithoutRole(): void
    {
        $service = new CaseManagementService();
        $query = new \App\Models\CaseModel();
        $service->scope($query);
        $this->assertSame([1, 3], array_map('intval', array_column($query->orderBy('id')->findAll(), 'id')));
        $this->assertTrue($service->canManage(model('CaseModel')->find(1)));
        $this->assertFalse($service->canView(model('CaseModel')->find(2)));
    }

    /** Nhân viên làm việc với nhiều đầu mối vẫn chỉ xem các vụ được giao/tham gia. */
    public function testEmployeeOnlySeesAssignedOrParticipatingCases(): void
    {
        $this->login(3);
        $service = new CaseManagementService();
        $this->assertFalse($service->canView(model('CaseModel')->find(1)));
        $this->assertTrue($service->canView(model('CaseModel')->find(3)));
        $this->db->table('case_members')->insert(['case_id' => 2, 'employee_id' => 3, 'role_in_case' => 'supporter']);
        $this->assertTrue($service->canView(model('CaseModel')->find(2)));
        $this->assertFalse($service->canManage(model('CaseModel')->find(2)));
    }

    /** Quyền giám đốc chuyên môn sẵn có chỉ mở xem, không được phân công hoặc chuyển quản lý. */
    public function testViewAllDoesNotGrantAssignmentRights(): void
    {
        $this->login(3, ['case.view_all']);
        $service = new CaseManagementService();
        $this->assertTrue($service->canView(model('CaseModel')->find(2)));
        $this->assertFalse($service->canManage(model('CaseModel')->find(2)));
        $this->assertFalse($service->canAssignManager());
    }

    /** Người được giao quản lý được thêm nhân sự chưa có vai trò mà không cần lập danh mục team. */
    public function testManagerCanAssignEmployeeWithoutAccountOrRole(): void
    {
        $this->db->table('case_steps')->insertBatch([
            ['id' => 1, 'case_id' => 1, 'assigned_to' => 1, 'status' => 'active', 'deleted_at' => null],
            ['id' => 2, 'case_id' => 1, 'assigned_to' => 1, 'status' => 'completed', 'deleted_at' => null],
            ['id' => 3, 'case_id' => 1, 'assigned_to' => 1, 'status' => 'active', 'deleted_at' => '2026-01-01'],
        ]);
        (new CaseManagementService())->updateMembers(1, ['assignees' => [4], 'supporters' => [3], 'approvers' => [2]]);
        $case = model('CaseModel')->find(1);
        $this->assertSame(4, (int) $case['assigned_lawyer_id']);
        $this->assertSame(1, (int) $case['case_manager_id']);
        $steps = $this->db->table('case_steps')->orderBy('id')->get()->getResultArray();
        $this->assertSame([4, 1, 1], array_map('intval', array_column($steps, 'assigned_to')));
        $this->assertContains(4, array_map('intval', array_column(get_available_employees(null, true), 'id')));
    }

    /** Nhân viên thực hiện không tự phân thêm người hoặc đổi đầu mối qua dữ liệu POST. */
    public function testAssigneeCannotAssignOtherEmployees(): void
    {
        $this->login(3);
        $this->expectException(CaseManagementForbiddenException::class);
        (new CaseManagementService())->updateMembers(3, ['assignees' => [4]]);
    }

    /** Trưởng đầu mối cũng không được tự chuyển quyền quản lý sang người khác. */
    public function testManagerCannotTransferOwnershipWithoutGlobalEdit(): void
    {
        $input = ['case_manager_id' => 2];
        $this->expectException(CaseManagementForbiddenException::class);
        (new CaseManagementService())->validateUpdate(model('CaseModel')->find(1), $input);
    }

    /** Phân công quản lý chỉ thay đổi cột đầu mối, không làm mất người đang thực hiện. */
    public function testBulkTransferPreservesAssignmentsAndLogsChanges(): void
    {
        $this->login(1, ['case.edit_all']);
        (new CaseManagementService())->assignManagers(['case_ids' => [1, 3], 'case_manager_id' => 2]);
        $case = model('CaseModel')->find(3);
        $this->assertSame(2, (int) $case['case_manager_id']);
        $this->assertSame(3, (int) $case['assigned_lawyer_id']);
        $this->assertSame(2, $this->db->table('case_history')->countAllResults());
        $this->login(1);
        $this->assertFalse((new CaseManagementService())->canView(model('CaseModel')->find(1)));
    }

    /** Một ID không tồn tại phải làm hủy cả lô thay vì chỉ chuyển một phần. */
    public function testInvalidBulkBatchLeavesEveryCaseUnchanged(): void
    {
        $this->login(1, ['case.edit_all']);
        try {
            (new CaseManagementService())->assignManagers(['case_ids' => [1, 999], 'case_manager_id' => 2]);
            $this->fail('Phải từ chối toàn bộ lô không hợp lệ.');
        } catch (InvalidArgumentException $error) {
            $this->assertSame(1, (int) model('CaseModel')->find(1)['case_manager_id']);
            $this->assertSame(0, $this->db->table('case_history')->countAllResults());
        }
    }

    /** ID nhân sự đã xóa không thể được dùng qua thao tác chỉnh sửa từ trình duyệt. */
    public function testDeletedEmployeeCannotBecomeManager(): void
    {
        $this->db->table('employees')->where('id', 2)->update(['deleted_at' => '2026-01-01']);
        $input = ['case_manager_id' => 2];
        $this->expectException(InvalidArgumentException::class);
        (new CaseManagementService())->validateCreation($input, $input);
    }

    /** Scope danh sách hiện hữu phải nhận biết đầu mối ngay cả khi vai trò là nhân viên. */
    public function testExistingCaseServiceIncludesManagedUnassignedCases(): void
    {
        $query = new \App\Models\CaseModel();
        (new ReflectionMethod(\App\Services\CaseService::class, 'applyAccessLimit'))->invokeArgs(new \App\Services\CaseService(), [&$query]);
        $this->assertSame([1, 3], array_map('intval', array_column($query->orderBy('id')->findAll(), 'id')));
    }

    /** URL chi tiết và bước công việc phải kiểm tra cùng phạm vi trước Controller. */
    public function testDirectUrlOfUnrelatedCaseIsForbidden(): void
    {
        $request = $this->getMockBuilder(\CodeIgniter\HTTP\IncomingRequest::class)->disableOriginalConstructor()
            ->onlyMethods(['getUri', 'getMethod'])->getMock();
        $request->method('getUri')->willReturn(new \CodeIgniter\HTTP\URI('http://example.com/cases/show/2'));
        $request->method('getMethod')->willReturn('GET');
        $response = (new \App\Filters\CaseManagerFilter())->before($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    /** Quyền kế toán do mình tạo không bị mất khi hồ sơ được giao cho đầu mối khác. */
    public function testAccountingOwnStillUsesOriginalCreator(): void
    {
        $this->login(3, ['case.accounting_own']);
        $this->db->table('case_history')->insert(['case_id' => 2, 'user_id' => 103, 'action' => 'tiep_nhan']);
        $this->assertTrue((new CaseManagementService())->canView(model('CaseModel')->find(2)));
    }

    /** Bản ghi lịch sử về sau không được thay đổi người tạo để chiếm quyền kế toán. */
    public function testAccountingCannotClaimCaseFromLaterHistoryEntry(): void
    {
        $this->login(3, ['case.accounting_own']);
        $this->db->table('case_history')->insertBatch([
            ['case_id' => 2, 'user_id' => 102, 'action' => 'tiep_nhan'],
            ['case_id' => 2, 'user_id' => 103, 'action' => 'tiep_nhan'],
        ]);
        $this->assertFalse((new CaseManagementService())->canView(model('CaseModel')->find(2)));
    }

    /** Hồ sơ cũ chỉ lưu người chính trên cases vẫn cho nhân viên sửa thông tin không đổi phân công. */
    public function testLegacyAssigneeCanKeepExistingAssignmentWhenEditing(): void
    {
        $this->login(3);
        $input = ['title' => 'Thông tin cập nhật', 'assignees' => [3]];
        (new CaseManagementService())->validateUpdate(model('CaseModel')->find(3), $input);
        $this->assertSame([3], $input['assignees']);
    }

    /** Đổi tham số lọc trên URL chỉ thu hẹp danh sách, không mở vụ của đầu mối khác. */
    public function testManagerFilterCannotExpandExistingScope(): void
    {
        $query = new \App\Models\CaseModel();
        $service = new \App\Services\CaseService();
        (new ReflectionMethod($service, 'applyAccessLimit'))->invokeArgs($service, [&$query]);
        (new ReflectionMethod($service, 'applyManagerFilter'))->invokeArgs($service, [$query, 2]);
        $this->assertSame([], $query->findAll());
    }

    /** Người quản lý một vụ không được phân nhân sự cho vụ do người khác quản lý. */
    public function testManagerCannotAssignMembersToOtherManagersCase(): void
    {
        $this->expectException(CaseManagementForbiddenException::class);
        (new CaseManagementService())->updateMembers(2, ['assignees' => [4]]);
    }
}
