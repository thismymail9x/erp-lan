<?php

use App\Services\KpiService;
use App\Services\PayrollService;
use CodeIgniter\Test\CIUnitTestCase;

/** Kiểm tra chia thưởng, tháng thực thu và bảo toàn tiền khi tính lương lại. */
final class ConsultingPayrollTest extends CIUnitTestCase
{
    public function testMonthlyAndAnnualAmountsAtRevenueThresholds(): void
    {
        $service = (new ReflectionClass(KpiService::class))->newInstanceWithoutConstructor();
        $format = new ReflectionMethod($service, 'formatConsultingStats');
        foreach ([49999999 => [0, 0, 0], 50000000 => [400000, 600000, 0],
            150000000 => [2000000, 3000000, 0], 300000000 => [2000000, 3000000, 4000000],
            500000000 => [2000000, 3000000, 10000000]] as $revenue => $expected) {
            $stats = $format->invoke($service, (float)$revenue, 1);
            $this->assertEquals($expected, [$stats['monthly_payout'], $stats['annual_accrual'], $stats['milestone_bonus']]);
        }
    }

    public function testRevenueUsesActualPaidDateAndIgnoresDeadline(): void
    {
        $service = (new ReflectionClass(KpiService::class))->newInstanceWithoutConstructor();
        $resolve = new ReflectionMethod($service, 'resolveConsultingActualRevenue');
        $row = ['payment_progress' => json_encode([
            ['amount' => 10000000, 'is_paid' => 1, 'paid_at' => '2026-10-01 00:00:00'],
            ['amount' => 20000000, 'is_paid' => 1, 'paid_at' => '2026-10-31 23:59:59'],
            ['amount' => 90000000, 'is_paid' => 1, 'deadline' => '2026-10-15'],
            ['amount' => 90000000, 'is_paid' => 0, 'paid_at' => '2026-10-15'],
            ['amount' => 90000000, 'is_paid' => 1, 'paid_at' => '2026-09-30 23:59:59'],
            ['amount' => 90000000, 'is_paid' => 1, 'paid_at' => '2026-11-01 00:00:00'],
        ])];
        $this->assertEquals(30000000, $resolve->invoke($service, $row, '2026-10-01 00:00:00', '2026-10-31 23:59:59'));
    }

    public function testRecalculationPreservesManualOtherWithoutDuplicatingAutomaticAmounts(): void
    {
        $service = (new ReflectionClass(PayrollService::class))->newInstanceWithoutConstructor();
        $manual = new ReflectionMethod($service, 'getManualOtherAmount');
        $row = ['salary_other' => 6700000, 'consulting_monthly' => 2000000,
            'consulting_milestone' => 4000000, 'notes_json' => json_encode([
                ['text' => '[Truy lĩnh tự động] Lương tháng 09/2026: 1 ngày = 500,000 đ'],
            ])];
        $this->assertEquals(200000, $manual->invoke($service, $row));
        // Nguồn KPI thay đổi: chỉ thay phần tự động, giữ khoản nhập tay.
        $row['salary_other'] = $manual->invoke($service, $row) + 400000 + 500000;
        $row['consulting_monthly'] = 400000;
        $row['consulting_milestone'] = 0;
        $this->assertEquals(200000, $manual->invoke($service, $row));
    }

    public function testSavingNotesKeepsOriginalConsultingEvidence(): void
    {
        $service = (new ReflectionClass(PayrollService::class))->newInstanceWithoutConstructor();
        $row = ['notes_json' => json_encode([['text' => '[KPI tư vấn] 2,000,000 đ', 'auto' => true]])];
        $notes = json_decode($service->preserveConsultingNote($row, json_encode([
            ['text' => 'Ghi chú nhập tay'], ['text' => '[KPI tư vấn] số tiền giả'],
        ])), true);
        $this->assertCount(2, $notes);
        $this->assertSame('[KPI tư vấn] 2,000,000 đ', $notes[1]['text']);
    }
}
