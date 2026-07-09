<?php if (empty($stats)) { ?>
    <tr>
        <td colspan="12" class="kpi-empty-state">
            <i class="fas fa-search m-b-10"></i>
            Kh&#244;ng t&#236;m th&#7845;y d&#7919; li&#7879;u ph&#249; h&#7907;p v&#7899;i b&#7897; l&#7885;c.
        </td>
    </tr>
<?php } else { ?>
    <?php
    $totalCases = array_sum(array_column($stats, 'case_count'));
    $totalContract = array_sum(array_column($stats, 'contract_value'));
    $totalStandardReward = array_sum(array_column($stats, 'standard_reward'));
    $totalMonthlyPayout = array_sum(array_column($stats, 'monthly_payout'));
    $totalAnnualAccrual = array_sum(array_column($stats, 'annual_accrual'));
    $totalMilestoneBonus = array_sum(array_column($stats, 'milestone_bonus'));
    $totalNextPayroll = array_sum(array_column($stats, 'next_payroll_payout'));
    $targetValue = array_sum(array_column($stats, 'target_value'));
    ?>
    <tr class="kpi-summary-row">
        <td colspan="2">T&#7892;NG C&#7896;NG (<?= count($stats) ?> nh&#226;n s&#7921; t&#432; v&#7845;n)</td>
        <td><?= number_format($totalCases) ?></td>
        <td><span class="amount-earned"><?= number_format($totalContract) ?></span></td>
        <td><span class="amount-total"><?= number_format($targetValue) ?></span></td>
        <td><span class="amount-total"><?= number_format($totalStandardReward) ?></span></td>
        <td><span class="amount-total"><?= number_format($totalMonthlyPayout) ?></span></td>
        <td><span class="amount-total"><?= number_format($totalAnnualAccrual) ?></span></td>
        <td><span class="amount-total"><?= number_format($totalMilestoneBonus) ?></span></td>
        <td><span class="amount-total"><?= number_format($totalNextPayroll) ?></span></td>
        <td colspan="2"></td>
    </tr>
    <?php foreach ($stats as $row) {
        $colorClass = 'percent-low';
        if ($row['percent'] >= 100) {
            $colorClass = 'percent-high';
        } elseif ($row['percent'] >= 70) {
            $colorClass = 'percent-mid';
        }
        $barWidth = min(100, (float)$row['percent']);
    ?>
        <tr>
            <td>
                <div class="emp-info">
                    <span class="name"><?= esc($row['full_name']) ?></span>
                    <span class="position"><?= esc($row['position'] ?? html_entity_decode('Nh&#226;n vi&#234;n', ENT_QUOTES, 'UTF-8')) ?></span>
                </div>
            </td>
            <td><?= esc($row['department_name'] ?? 'N/A') ?></td>
            <td><?= number_format($row['case_count']) ?></td>
            <td><span class="amount-earned"><?= number_format($row['contract_value']) ?></span></td>
            <td><span class="amount-potential"><?= number_format($row['target_value']) ?></span></td>
            <td><span class="amount-total"><?= number_format($row['standard_reward']) ?></span></td>
            <td><span class="amount-earned"><?= number_format($row['monthly_payout']) ?></span></td>
            <td><span class="amount-potential"><?= number_format($row['annual_accrual']) ?></span></td>
            <td><span class="amount-earned"><?= number_format($row['milestone_bonus']) ?></span></td>
            <td><span class="amount-total"><?= number_format($row['next_payroll_payout']) ?></span></td>
            <td>
                <div class="perf-progress-wrapper" title="<?= esc($row['percent']) ?>%">
                    <div class="perf-progress-bar">
                        <div class="perf-progress-fill <?= $colorClass ?>" style="width: <?= $barWidth ?>%"></div>
                    </div>
                    <span class="perf-percent-text <?= $colorClass ?>"><?= esc($row['percent']) ?>%</span>
                </div>
            </td>
            <td class="kpi-row-action">
                <?php if (!empty($row['id'])) { ?>
                    <a href="<?= base_url('cases?lawyer_id[]=' . $row['id']) ?>" class="btn-sm" title="Xem h&#7891; s&#417; li&#234;n quan">
                        <i class="fas fa-clipboard-list"></i>
                    </a>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
<?php } ?>
