<?php

use App\Services\PayrollService;
use CodeIgniter\Test\CIUnitTestCase;

final class PayrollStageTest extends CIUnitTestCase
{
    public function testTimelineDoesNotDependOnCurrentProfileOrCalculationOrder(): void
    {
        $service = (new ReflectionClass(PayrollService::class))->newInstanceWithoutConstructor();
        $resolve = new ReflectionMethod($service, 'normalizeEmployeeProbationStageForPayroll');
        $employee = ['id' => 1, 'join_date' => '2026-01-15', 'probation_rate' => 100, 'initial_position' => 'Thực tập sinh'];
        foreach (['2026-09' => 100, '2026-01' => 40, '2026-05' => 60, '2026-07' => 85] as $month => $rate) {
            $result = $resolve->invoke($service, $employee, $month);
            $this->assertSame((float) $rate, $result['probation_rate']);
        }
        $this->assertSame(100, $employee['probation_rate']);
    }

    public function testTransitionUsesAttendanceOnEachSideOfJoinAnniversary(): void
    {
        $service = (new ReflectionClass(PayrollService::class))->newInstanceWithoutConstructor();
        $resolve = new ReflectionMethod($service, 'normalizeEmployeeProbationStageForPayroll');
        $calculate = new ReflectionMethod($service, 'calcTaxableIncome');
        $employee = $resolve->invoke($service, [
            'join_date' => '2026-01-15', 'salary_base' => 10000000, 'initial_position' => 'Thực tập sinh',
        ], '2026-04');
        $this->assertSame('2026-04-14', $employee['probation_end_date']);
        $income = $calculate->invoke($service, $employee, [
            '2026-04-14' => 1, '2026-04-15' => 0.5,
        ], 0.0, 20, '2026-04');
        $this->assertEqualsWithDelta(350000, $income['taxable_income'], 0.01);
    }

    public function testStartingInProbationRequiresOnlyTwoMonths(): void
    {
        $service = new \App\Services\EmployeeStageService();
        $employee = ['join_date' => '2026-08-15', 'initial_position' => 'Thử việc', 'position' => 'Thực tập sinh'];
        $before = $service->resolve($employee, '2026-10-14');
        $after = $service->resolve($employee, '2026-10-15');
        $this->assertSame(85.0, $before['probation_rate']);
        $this->assertSame('2026-10-14', $before['probation_end_date']);
        $this->assertSame(100.0, $after['probation_rate']);
        $this->assertNull($after['probation_end_date']);
    }

    public function testStartingAsApprenticeSkipsInternship(): void
    {
        $service = new \App\Services\EmployeeStageService();
        $employee = ['join_date' => '2026-01-15', 'initial_position' => 'Học việc'];
        foreach (['2026-01-15' => 60, '2026-03-15' => 85, '2026-05-15' => 100] as $date => $rate) {
            $this->assertSame((float) $rate, $service->resolve($employee, $date)['probation_rate']);
        }
    }

    public function testOfficialAndManagerAreNeverResetToInternship(): void
    {
        $service = new \App\Services\EmployeeStageService();
        foreach (['Nhân viên chính thức', 'Trưởng phòng', 'Admin'] as $role) {
            $employee = ['join_date' => '2026-10-01', 'initial_position' => $role];
            $this->assertSame(100.0, $service->resolve($employee, '2026-10-02')['probation_rate']);
        }
    }

    public function testAugustPayrollUsesAugustStageEvenWhenOctoberStageIsOfficial(): void
    {
        $service = (new ReflectionClass(PayrollService::class))->newInstanceWithoutConstructor();
        $resolve = new ReflectionMethod($service, 'normalizeEmployeeProbationStageForPayroll');
        $calculate = new ReflectionMethod($service, 'calcTaxableIncome');
        $employee = [
            'id' => 1, 'join_date' => '2026-06-15', 'initial_position' => 'Học việc',
            'position' => 'Học việc', 'probation_rate' => 60.0, 'salary_base' => 10000000,
        ];
        $october = $resolve->invoke($service, $employee, '2026-10');
        $august = $resolve->invoke($service, $employee, '2026-08');
        $current = (new \App\Services\EmployeeStageService())->resolve($employee, '2026-10-27');
        $this->assertSame(100.0, $current['probation_rate']);
        $this->assertSame(100.0, $october['new_rate_after']);
        $this->assertSame(60.0, $august['probation_rate']);
        $this->assertSame('2026-08-14', $august['probation_end_date']);
        $income = $calculate->invoke($service, $august, [
            '2026-08-14' => 1.0, '2026-08-15' => 1.0,
        ], 0.0, 20, '2026-08');
        $this->assertEqualsWithDelta(725000, $income['taxable_income'], 0.01);
        $this->assertSame('Học việc', $employee['position']);
        $this->assertSame(60.0, $employee['probation_rate']);
    }

    public function testUnknownEntryTitleDoesNotPromoteToOfficial(): void
    {
        $employee = ['join_date' => '2026-06-15', 'initial_position' => 'Unknown', 'probation_rate' => 60.0];
        $this->assertSame($employee, (new \App\Services\EmployeeStageService())->resolve($employee, '2026-10-27'));
    }

    public function testEmptyEntryRoleUsesRecognizedCurrentRole(): void
    {
        $employee = ['join_date' => '2026-08-15', 'initial_position' => '', 'role_name' => 'Học việc'];
        $this->assertSame(60.0, (new \App\Services\EmployeeStageService())->resolve($employee, '2026-08-31')['probation_rate']);
    }
}
