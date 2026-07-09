<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customer_care.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-700 text-dark mb-1">B&#225;o C&#225;o Hi&#7879;u Su&#7845;t CSKH</h1>
            <p class="text-muted font-size-0.9">&#272;o l&#432;&#7901;ng ch&#7845;t l&#432;&#7907;ng gi&#7919; ch&#226;n, t&#7927; l&#7879; kh&#7843;o s&#225;t ph&#7843;n h&#7891;i v&#224; hi&#7879;u qu&#7843; c&#7911;a c&#417; c&#7845;u VIP/Loyalty.</p>
        </div>
        <a href="<?= base_url('customer-care') ?>" class="btn btn-secondary d-flex align-items-center gap-2">
            <i class="fas fa-chevron-left"></i> <span>Quay l&#7841;i Dashboard</span>
        </a>
    </div>

    <div class="kpi-report-container">
        <div class="kpi-report-card retention">
            <div class="kpi-header">
                <span class="kpi-title">T&#7927; L&#7879; Gi&#7919; Ch&#226;n (Retention)</span>
                <i class="fas fa-user-check kpi-icon text-success"></i>
            </div>
            <div class="kpi-value"><?= esc($kpis['retention_rate'] ?? 0) ?>%</div>
            <p class="text-muted font-size-0.78 m-0 mt-2">
                T&#7927; l&#7879; kh&#225;ch h&#224;ng c&#361; t&#225;i k&#253; h&#7907;p &#273;&#7891;ng m&#7899;i ho&#7863;c ph&#225;t sinh v&#7909; vi&#7879;c &gt;= 2.
            </p>
        </div>

        <div class="kpi-report-card referral">
            <div class="kpi-header">
                <span class="kpi-title">T&#7927; L&#7879; Kh&#225;ch Gi&#7899;i Thi&#7879;u</span>
                <i class="fas fa-share-alt kpi-icon text-warning"></i>
            </div>
            <div class="kpi-value"><?= esc($kpis['referral_rate'] ?? 0) ?>%</div>
            <p class="text-muted font-size-0.78 m-0 mt-2">
                T&#7927; l&#7879; kh&#225;ch h&#224;ng &#273;&#227; chia s&#7867; m&#227; v&#224; gi&#7899;i thi&#7879;u &#273;&#7889;i t&#225;c m&#7899;i th&#224;nh c&#244;ng.
            </p>
        </div>

        <div class="kpi-report-card feedback">
            <div class="kpi-header">
                <span class="kpi-title">T&#7927; L&#7879; Kh&#7843;o S&#225;t (Feedback)</span>
                <i class="fas fa-poll-h kpi-icon text-primary"></i>
            </div>
            <div class="kpi-value"><?= esc($kpis['feedback_rate'] ?? 0) ?>%</div>
            <p class="text-muted font-size-0.78 m-0 mt-2">
                T&#7927; l&#7879; kh&#225;ch h&#224;ng ph&#7843;n h&#7891;i t&#237;ch c&#7921;c v&#224; ho&#224;n t&#7845;t phi&#7871;u kh&#7843;o s&#225;t ch&#7845;t l&#432;&#7907;ng.
            </p>
        </div>

        <div class="kpi-report-card">
            <div class="kpi-header">
                <span class="kpi-title">T&#7893;ng L&#432;&#7907;t Gi&#7899;i Thi&#7879;u</span>
                <i class="fas fa-medal kpi-icon text-info"></i>
            </div>
            <div class="kpi-value"><?= esc($kpis['total_referrals'] ?? 0) ?> L&#432;&#7907;t</div>
            <p class="text-muted font-size-0.78 m-0 mt-2">
                S&#7889; l&#432;&#7907;ng kh&#225;ch h&#224;ng m&#7899;i &#273;&#432;&#7907;c &#273;&#259;ng k&#253; th&#244;ng qua m&#227; gi&#7899;i thi&#7879;u VIP.
            </p>
        </div>
    </div>

    <div class="report-grid">
        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-body p-4">
                <h5 class="card-title font-weight-700 mb-4">C&#417; C&#7845;u Ph&#226;n Kh&#250;c Kh&#225;ch H&#224;ng (A/B/C)</h5>
                <div style="height: 280px; position: relative;">
                    <canvas id="segmentReportChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 16px;">
            <div class="card-body p-4">
                <h5 class="card-title font-weight-700 mb-4">Quy Tr&#236;nh CSKH Ho&#224;n Th&#224;nh Theo Th&#225;ng</h5>
                <div style="height: 280px; position: relative;">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; height: 100%;">
                <div class="card-body p-4">
                    <h5 class="card-title font-weight-700 mb-3">Chi Ti&#7871;t C&#417; C&#7845;u Kh&#225;ch H&#224;ng</h5>
                    <div class="table-responsive">
                        <table class="table-report-cskh">
                            <thead>
                                <tr>
                                    <th>Ph&#226;n Kh&#250;c</th>
                                    <th class="text-right">S&#7889; L&#432;&#7907;ng</th>
                                    <th class="text-right">T&#7927; Tr&#7885;ng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $segmentsData = [
                                        'vip'       => ['label' => html_entity_decode('Nh&#243;m A - VIP', ENT_QUOTES, 'UTF-8'), 'count' => 0, 'badge' => 'badge-segment vip'],
                                        'regular'   => ['label' => html_entity_decode('Nh&#243;m B - Ph&#7893; th&#244;ng', ENT_QUOTES, 'UTF-8'), 'count' => 0, 'badge' => 'badge-segment regular'],
                                        'potential' => ['label' => html_entity_decode('Nh&#243;m C - Ti&#7873;m n&#259;ng ngu&#7897;i', ENT_QUOTES, 'UTF-8'), 'count' => 0, 'badge' => 'badge-segment potential']
                                    ];

                                    $totalCount = 0;
                                    foreach ($monthlyStats['segments'] as $s) {
                                        $segKey = $s['customer_segment'] ?? 'potential';
                                        if (isset($segmentsData[$segKey])) {
                                            $segmentsData[$segKey]['count'] = (int)$s['count'];
                                            $totalCount += (int)$s['count'];
                                        }
                                    }
                                ?>
                                <?php foreach ($segmentsData as $key => $data): ?>
                                    <?php $percent = $totalCount > 0 ? round(($data['count'] / $totalCount) * 100, 1) : 0; ?>
                                    <tr>
                                        <td><span class="<?= $data['badge'] ?>"><?= esc($data['label']) ?></span></td>
                                        <td class="text-right font-weight-700 text-dark"><?= number_format($data['count']) ?></td>
                                        <td class="text-right text-secondary"><?= $percent ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; height: 100%;">
                <div class="card-body p-4">
                    <h5 class="card-title font-weight-700 mb-3">Hi&#7879;u Su&#7845;t CSKH Ho&#224;n Th&#224;nh Theo Th&#225;ng</h5>
                    <div class="table-responsive">
                        <table class="table-report-cskh">
                            <thead>
                                <tr>
                                    <th>Th&#7901;i Gian</th>
                                    <th class="text-right">K&#7871; Ho&#7841;ch CSKH &#272;&#227; Ho&#224;n Th&#224;nh</th>
                                    <th>&#272;&#225;nh Gi&#225; Ch&#7881; Ti&#234;u</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $monthlyArray = [];
                                    for ($m = 1; $m <= 12; $m++) {
                                        $monthlyArray[$m] = 0;
                                    }

                                    foreach ($monthlyStats['trends'] as $t) {
                                        $mNum = (int)$t['month'];
                                        if ($mNum >= 1 && $mNum <= 12) {
                                            $monthlyArray[$mNum] = (int)$t['count'];
                                        }
                                    }

                                    $currentMonth = (int)date('m');
                                ?>
                                <?php for ($m = 1; $m <= $currentMonth; $m++): ?>
                                    <tr>
                                        <td class="font-weight-600">Th&#225;ng <?= $m ?> / <?= date('Y') ?></td>
                                        <td class="text-right font-weight-700 text-dark"><?= number_format($monthlyArray[$m]) ?></td>
                                        <td>
                                            <?php if ($monthlyArray[$m] >= 15): ?>
                                                <span class="badge badge-success px-2.5 py-1 rounded-pill"><i class="fas fa-check"></i> &#272;&#7841;t m&#7909;c ti&#234;u</span>
                                            <?php elseif ($monthlyArray[$m] > 0): ?>
                                                <span class="badge badge-warning px-2.5 py-1 rounded-pill">&#272;ang t&#237;ch l&#361;y</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary px-2.5 py-1 rounded-pill">Ch&#432;a c&#243; d&#7919; li&#7879;u</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
window.customerCareMonthlyReportConfig = {
    segmentLabels: ['Nh\u00f3m A (VIP)', 'Nh\u00f3m B (Ph\u1ed5 th\u00f4ng)', 'Nh\u00f3m C (Ti\u1ec1m n\u0103ng)'],
    segmentData: [
        <?= (int)$segmentsData['vip']['count'] ?>,
        <?= (int)$segmentsData['regular']['count'] ?>,
        <?= (int)$segmentsData['potential']['count'] ?>
    ],
    monthLabels: [<?php for ($m = 1; $m <= $currentMonth; $m++): ?>'Th\u00e1ng <?= $m ?>'<?= $m < $currentMonth ? ', ' : '' ?><?php endfor; ?>],
    trendData: [<?php for ($m = 1; $m <= $currentMonth; $m++): ?><?= (int)$monthlyArray[$m] ?><?= $m < $currentMonth ? ', ' : '' ?><?php endfor; ?>],
    trendLabel: 'Quy tr\u00ecnh ho\u00e0n th\u00e0nh'
};
</script>
<script src="<?= base_url('js/customer_care.js') ?>"></script>
<?= $this->endSection() ?>
