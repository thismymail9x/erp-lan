<?php

use App\Services\CaseAccountingAccess;
use CodeIgniter\Test\CIUnitTestCase;

final class CaseAccountingAccessTest extends CIUnitTestCase
{
    public function testScopedPermissionDoesNotGrantOtherCreatorsAccountingAccess(): void
    {
        $state = ['user_id' => 12, 'role_name' => 'Nhân viên chính thức', 'department_id' => 3,
            'permissions' => [CaseAccountingAccess::PERMISSION]];
        $session = $this->getMockBuilder(\CodeIgniter\Session\Session::class)->disableOriginalConstructor()
            ->onlyMethods(['get'])->getMock();
        $session->method('get')->willReturnCallback(static function ($key) use (&$state) { return $state[$key] ?? null; });
        \Config\Services::injectMock('session', $session);
        try {
            $access = new CaseAccountingAccess();
            $this->assertTrue($access->canManage());
            $this->assertTrue($access->canManage(['id' => 1, 'creator_user_id' => 12]));
            $this->assertFalse($access->canManage(['id' => 2, 'creator_user_id' => 13]));
            $this->assertFalse($access->canManage(['id' => 3, 'creator_user_id' => 0]));
            $state['permissions'] = [];
            $this->assertFalse($access->canManage(['id' => 1, 'creator_user_id' => 12]));
        } finally {
            \Config\Services::resetSingle('session');
        }
    }

    public function testConsultingFieldsFollowPermissionAlongsideAccounting(): void
    {
        $state = ['user_id' => 12, 'role_name' => 'Nh?n vi?n ch?nh th?c', 'department_id' => 3,
            'permissions' => [CaseAccountingAccess::PERMISSION]];
        $session = $this->getMockBuilder(\CodeIgniter\Session\Session::class)->disableOriginalConstructor()
            ->onlyMethods(['get'])->getMock();
        $session->method('get')->willReturnCallback(static function ($key) use (&$state) { return $state[$key] ?? null; });
        \Config\Services::injectMock('session', $session);
        try {
            $data = ['case' => ['consultant_id' => 7, 'consultation_closed_at' => '2026-11-05 00:00:00'],
                'staffs' => [['id' => 7, 'full_name' => 'Consultant', 'position' => 'Staff']]];
            $this->assertStringNotContainsString('name="consultant_id"', view('dashboard/cases/consulting_fields', $data));
            $state['permissions'][] = 'kpi.consulting';
            $html = view('dashboard/cases/consulting_fields', $data);
            $this->assertStringContainsString('name="consultant_id"', $html);
            $this->assertStringContainsString('value="7" selected', $html);
            $this->assertStringContainsString('value="2026-11-05"', $html);
        } finally {
            \Config\Services::resetSingle('session');
        }
    }

    public function testPermissionRequiresAuthenticatedCreator(): void
    {
        $access = new CaseAccountingAccess();
        $this->assertTrue($access->ownsCase(true, 12, 12));
        $this->assertFalse($access->ownsCase(false, 12, 12));
        $this->assertFalse($access->ownsCase(true, 12, 13));
        $this->assertFalse($access->ownsCase(true, 0, 0));
        $this->assertFalse($access->ownsCase(true, 12, 0));
    }

    public function testAccountingOnlyInputCannotChangeCaseOrMembers(): void
    {
        $data = (new CaseAccountingAccess())->normalizeInput([
            'title' => 'Injected', 'assignees' => [99], 'workflow_template_id' => 99,
            'customer_id' => 99, 'creator_user_id' => 12,
            'contract_value' => '50.000.000', 'payment_progress' => 'forged',
            'payments' => [['amount' => '1.500.000', 'is_paid' => 'on', 'is_vat' => 'on']],
        ]);
        $this->assertSame(['contract_value', 'payment_progress'], array_keys($data));
        $this->assertSame('50000000', $data['contract_value']);
        $payment = json_decode($data['payment_progress'], true)[0];
        $this->assertSame('1500000', $payment['amount']);
        $this->assertSame(1, $payment['is_paid']);
        $this->assertSame(1, $payment['is_vat']);
    }

    public function testRemovingAllPaymentsClearsOnlyAccountingField(): void
    {
        $access = new CaseAccountingAccess();
        $this->assertSame(['payment_progress' => null], $access->normalizeInput(['accounting_section' => 1]));
        $this->assertSame([], $access->normalizeInput(['title' => 'Injected']));
    }
}
