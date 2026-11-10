<?php

use App\Services\CaseAccountingAccess;
use CodeIgniter\Test\CIUnitTestCase;

final class CaseCreateViewTest extends CIUnitTestCase
{
    public function testGrantedEmployeeSeesAccountingFieldsInCreateView(): void
    {
        $html = $this->renderAsEmployee([CaseAccountingAccess::PERMISSION]);
        $this->assertStringContainsString('Chuyên mục Hành chính - Kế toán', $html);
        $this->assertStringContainsString('name="contract_value"', $html);
        $this->assertStringContainsString('id="payment-progress-container"', $html);
        $this->assertStringContainsString('name="accounting_section"', $html);
    }

    public function testEmployeeWithoutPermissionDoesNotSeeAccountingFields(): void
    {
        $html = $this->renderAsEmployee([]);
        $this->assertStringNotContainsString('name="contract_value"', $html);
        $this->assertStringNotContainsString('id="payment-progress-container"', $html);
    }

    public function testGeneralCasePermissionDoesNotGrantAccountingFields(): void
    {
        $html = $this->renderAsEmployee(['case.manage', 'case.view']);
        $this->assertStringNotContainsString('name="contract_value"', $html);
        $this->assertStringNotContainsString('id="payment-progress-container"', $html);
    }

    private function renderAsEmployee(array $permissions): string
    {
        helper(['url', 'form']);
        $state = ['user_id' => 12, 'role_id' => 4, 'role_name' => 'Nhân viên chính thức',
            'department_id' => 3, 'permissions' => $permissions];
        $session = $this->getMockBuilder(\CodeIgniter\Session\Session::class)->disableOriginalConstructor()
            ->onlyMethods(['get', 'getFlashdata'])->getMock();
        $session->method('get')->willReturnCallback(static fn ($key) => $state[$key] ?? null);
        $session->method('getFlashdata')->willReturn(null);
        \Config\Services::injectMock('session', $session);
        try {
            $view = new \CodeIgniter\View\View(config('View'), APPPATH . 'Views', service('loader'), CI_DEBUG, service('logger'));
            return $view->setData(['customers' => [], 'templates' => [], 'staffs' => []])
                ->render('dashboard/cases/create');
        } finally {
            \Config\Services::resetSingle('session');
        }
    }
}
