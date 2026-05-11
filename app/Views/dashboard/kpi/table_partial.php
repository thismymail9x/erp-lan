<?php if (empty($stats)) { ?>
    <tr>
        <td colspan="7" style="text-align: center; padding: 50px; color: #888;">
            <i class="fas fa-search m-b-10" style="font-size: 2rem; display: block;"></i>
            Không tìm thấy dữ liệu phù hợp với bộ lọc.
        </td>
    </tr>
<?php } else { ?>
    <!-- Dòng Tổng cộng (Summary Row) -->
    <tr style="background: rgba(0, 113, 227, 0.05); font-weight: 700; border-bottom: 2px solid rgba(0, 113, 227, 0.1);">
        <td colspan="2" style="color: var(--kpi-accent-blue); padding: 18px 20px;">TỔNG CỘNG (<?= count($stats) ?> nhân sự)</td>
        <td><span class="amount-earned"><?= number_format(array_sum(array_column($stats, 'earned'))) ?></span></td>
        <td><span class="amount-potential"><?= number_format(array_sum(array_column($stats, 'potential'))) ?></span></td>
        <td><span class="amount-lost"><?= number_format(array_sum(array_column($stats, 'lost'))) ?></span></td>
        <td><span class="amount-total"><?= number_format(array_sum(array_column($stats, 'total'))) ?></span></td>
        <td colspan="2"></td>
    </tr>
    <?php foreach ($stats as $row) { 
        $colorClass = 'percent-low';
        if ($row['percent'] >= 70) $colorClass = 'percent-high';
        elseif ($row['percent'] >= 30) $colorClass = 'percent-mid';
?>
    <tr>
        <td>
            <div class="emp-info">
                <span class="name"><?= esc($row['full_name']) ?></span>
                <span class="position"><?= esc($row['position'] ?? 'Nhân viên') ?></span>
            </div>
        </td>
        <td><?= esc($row['department_name'] ?? 'N/A') ?></td>
        <td><span class="amount-earned"><?= number_format($row['earned']) ?></span></td>
        <td><span class="amount-potential"><?= number_format($row['potential']) ?></span></td>
        <td><a href="<?= base_url('cases?status=missed_kpi&lawyer_id[]=' . $row['id']) ?>" class="amount-lost" style="text-decoration:none;"><?= number_format($row['lost']) ?></a></td>
        <td><span class="amount-total"><?= number_format($row['total']) ?></span></td>
        <td>
            <div class="perf-progress-wrapper" title="<?= $row['percent'] ?>%">
                <div class="perf-progress-bar">
                    <div class="perf-progress-fill <?= $colorClass ?>" style="width: <?= $row['percent'] ?>%"></div>
                </div>
                <span class="perf-percent-text <?= $colorClass ?>"><?= $row['percent'] ?>%</span>
            </div>
        </td>
        <td style="text-align: right;">
            <?php if (!empty($row['id'])) { ?>
            <a href="<?= base_url('cases?lawyer_id[]=' . $row['id']) ?>" class="btn-sm" title="Xem chi tiết danh sách KPI nhân viên" style="color: var(--kpi-accent-blue);">
                <i class="fas fa-clipboard-list"></i>
            </a>
            <?php } ?>
        </td>
    </tr>
<?php } } ?>
