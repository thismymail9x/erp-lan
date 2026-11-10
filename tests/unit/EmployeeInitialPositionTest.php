<?php

use App\Models\EmployeeModel;
use App\Services\EmployeeService;
use App\Services\EmployeeStageService;
use App\Services\SystemLogService;
use CodeIgniter\Test\CIUnitTestCase;

final class EmployeeInitialPositionTest extends CIUnitTestCase
{
    public function testUpdatingInitialPositionKeepsTheSubmittedRoleOnReload(): void
    {
        $record = [
            'id' => 1, 'full_name' => 'Test Employee', 'position' => 'Học việc',
            'initial_position' => 'Thực tập sinh', 'join_date' => '2026-08-15',
            'annual_leave_start_date' => '2026-11-01',
        ];
        $model = $this->getMockBuilder(EmployeeModel::class)->disableOriginalConstructor()
            ->onlyMethods(['find', 'update'])->getMock();
        $model->method('find')->willReturnCallback(static function () use (&$record) { return $record; });
        $model->expects($this->once())->method('update')->willReturnCallback(
            function ($id, $data) use (&$record) {
                $this->assertSame('Học việc', $data['initial_position']);
                $record = array_merge($record, $data);
                return true;
            }
        );
        $stages = $this->createMock(EmployeeStageService::class);
        $stages->expects($this->once())->method('synchronize')->with(1);
        $stages->expects($this->never())->method('findEntryPosition');
        $logs = $this->getMockBuilder(SystemLogService::class)->disableOriginalConstructor()
            ->onlyMethods(['log'])->getMock();
        $logs->expects($this->once())->method('log');
        $service = (new ReflectionClass(EmployeeService::class))->newInstanceWithoutConstructor();
        foreach (['employeeModel' => $model, 'stageService' => $stages, 'logService' => $logs] as $name => $value) {
            (new ReflectionProperty($service, $name))->setValue($service, $value);
        }
        $result = $service->updateEmployee(1, [
            'initial_position' => 'Học việc', 'annual_leave_start_date' => '2026-11-01',
        ]);
        $this->assertSame('success', $result['status']);
        $this->assertSame('Học việc', $service->getEmployeeById(1)['data']['initial_position']);
    }
}
