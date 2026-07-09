<?php if (empty($stats)) { ?>
    <tr>
        <td colspan="8" class="kpi-empty-state">
            <i class="fas fa-search m-b-10"></i>
            Kh&#244;ng t&#236;m th&#7845;y d&#7919; li&#7879;u ph&#249; h&#7907;p v&#7899;i b&#7897; l&#7885;c.
        </td>
    </tr>
<?php } else { ?>
    <tr class="kpi-summary-row">
        <td colspan="2">T&#7892;NG C&#7896;NG (<?= count($stats) ?> nh&#226;n s&#7921;)</td>
        <td><span class="amount-earned"><?= number_format(array_sum(array_column($stats, 'earned'))) ?></span></td>
        <td><span class="amount-potential"><?= number_format(array_sum(array_column($stats, 'potential'))) ?></span></td>
        <td><span class="amount-lost"><?= number_format(array_sum(array_column($stats, 'lost'))) ?></span></td>
        <td><span class="amount-total"><?= number_format(array_sum(array_column($stats, 'total'))) ?></span></td>
        <td colspan="2"></td>
    </tr>
    <?php foreach ($stats as $row) {
        $colorClass = 'percent-low';
        if ($row['percent'] >= 70) {
            $colorClass = 'percent-high';
        } elseif ($row['percent'] >= 30) {
            $colorClass = 'percent-mid';
        }
    ?>
        <tr>
            <td>
                <div class="emp-info">
                    <span class="name"><?= esc($row['full_name']) ?></span>
                    <span class="position"><?= esc($row['position'] ?? html_entity_decode('Nh&#226;n vi&#234;n', ENT_QUOTES, 'UTF-8')) ?></span>
                </div>
            </td>
            <td><?= esc($row['department_name'] ?? 'N/A') ?></td>
            <td><span class="amount-earned"><?= number_format($row['earned']) ?></span></td>
            <td><span class="amount-potential"><?= number_format($row['potential']) ?></span></td>
            <td><a href="<?= base_url('cases?status=missed_kpi&lawyer_id[]=' . $row['id']) ?>" class="amount-lost"><?= number_format($row['lost']) ?></a></td>
            <td><span class="amount-total"><?= number_format($row['total']) ?></span></td>
            <td>
                <div class="perf-progress-wrapper" title="<?= esc($row['percent']) ?>%">
                    <div class="perf-progress-bar">
                        <div class="perf-progress-fill <?= $colorClass ?>" style="width: <?= min(100, (float)$row['percent']) ?>%"></div>
                    </div>
                    <span class="perf-percent-text <?= $colorClass ?>"><?= esc($row['percent']) ?>%</span>
                </div>
            </td>
            <td class="kpi-row-action">
                <?php if (!empty($row['id'])) { ?>
                    <a href="<?= base_url('cases?lawyer_id[]=' . $row['id']) ?>" class="btn-sm" title="Xem chi ti&#7871;t danh s&#225;ch KPI nh&#226;n vi&#234;n">
                        <i class="fas fa-clipboard-list"></i>
                    </a>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
<?php } ?>
