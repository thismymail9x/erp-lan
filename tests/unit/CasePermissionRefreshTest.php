<?php

use App\Controllers\CaseController;
use App\Services\CaseAccountingAccess;
use App\Services\PermissionService;
use CodeIgniter\Test\CIUnitTestCase;

final class CasePermissionRefreshTest extends CIUnitTestCase
{
    public function testCaseRequestReloadsPermissionsAndShowsNewGrant(): void
    {
        $state = ['user_id' => 12, 'role_id' => 4, 'role_name' => 'Nhân viên chính thức',
            'department_id' => 3, 'permissions' => []];
        $session = $this->getMockBuilder(\CodeIgniter\Session\Session::class)->disableOriginalConstructor()
            ->onlyMethods(['get'])->getMock();
        $session->method('get')->willReturnCallback(static function ($key) use (&$state) { return $state[$key] ?? null; });
        \Config\Services::injectMock('session', $session);
        try {
            $access = new CaseAccountingAccess();
            $this->assertFalse($access->canManage());
            $permissions = $this->getMockBuilder(PermissionService::class)->disableOriginalConstructor()
                ->onlyMethods(['loadUserPermissions'])->getMock();
            $loads = 0;
            $permissions->expects($this->exactly(2))->method('loadUserPermissions')->with(12, 4)
                ->willReturnCallback(static function () use (&$state, &$loads) {
                    $state['permissions'] = ++$loads === 1 ? [CaseAccountingAccess::PERMISSION] : [];
                });
            $controller = (new ReflectionClass(CaseController::class))->newInstanceWithoutConstructor();
            (new ReflectionProperty($controller, 'permissionService'))->setValue($controller, $permissions);
            $controller->initController(service('request'), service('response'), service('logger'));
            $this->assertTrue($access->canManage());
            $this->assertFalse($access->canManage(['id' => 2, 'creator_user_id' => 13]));
            $controller->initController(service('request'), service('response'), service('logger'));
            $this->assertFalse($access->canManage());
        } finally {
            \Config\Services::resetSingle('session');
        }
    }
}
