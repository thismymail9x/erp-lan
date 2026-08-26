<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customer_care.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="sla-report-wrapper customer-care-shell">
    <div class="dashboard-header-wrapper" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div class="header-title-container">
            <h2 class="content-title">Ch&#259;m s&#243;c Kh&#225;ch h&#224;ng &amp; Theo d&#245;i ti&#7871;n &#273;&#7897;</h2>
            <p class="content-subtitle hide-mobile">Gi&#225;m s&#225;t hi&#7879;u su&#7845;t ch&#259;m s&#243;c kh&#225;ch h&#224;ng v&#224; ki&#7875;m so&#225;t th&#7901;i h&#7841;n &#273;&#7883;nh m&#7913;c t&#432; v&#7845;n.</p>
        </div>
    </div>

    <div class="sla-tabs-header">
        <button class="sla-tab-btn active" data-tab="sla-performance">
            <i class="fas fa-chart-line"></i> B&#225;o c&#225;o Hi&#7879;u su&#7845;t Ch&#259;m s&#243;c
        </button>
        <?php if (has_permission('care.manage') || has_permission('sys.admin')) { ?>
            <button class="sla-tab-btn" data-tab="sla-configuration">
                <i class="fas fa-sliders-h"></i> C&#7845;u h&#236;nh B&#432;&#7899;c Tr&#7841;ng th&#225;i CSKH
            </button>
            <button class="sla-tab-btn" data-tab="monitoring-configuration">
                <i class="fas fa-eye"></i> C&#7845;u h&#236;nh Gi&#225;m s&#225;t CSKH
            </button>
        <?php } ?>
    </div>

    <div class="sla-tab-pane active" id="sla-performance">
        <div class="kpi-cards-grid">
            <div class="kpi-card">
                <div class="kpi-card-title">C&#7843;nh b&#225;o &#273;&#7887; (Tr&#7877; h&#7841;n)</div>
                <div class="kpi-card-value" style="color: #ff3b30; display: flex; align-items: center; gap: 8px;">
                    <span class="alert-indicator"></span>
                    <?= count($overdueAlerts) ?>
                </div>
                <div class="kpi-card-sub">Tr&#432;&#7901;ng h&#7907;p ch&#259;m s&#243;c tr&#7877; h&#7841;n</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-card-title">Hi&#7879;u su&#7845;t trung b&#236;nh</div>
                <?php
                $sumEff = 0;
                $countEff = 0;
                foreach ($leaderboard as $row) {
                    $sumEff += $row['efficiency_rate'];
                    $countEff++;
                }
                $avgEff = $countEff > 0 ? round($sumEff / $countEff, 1) : 100;
                $colorAvg = $avgEff >= 85 ? '#34c759' : ($avgEff >= 70 ? '#ff9500' : '#ff3b30');
                ?>
                <div class="kpi-card-value" style="color: <?= $colorAvg ?>;"><?= $avgEff ?>%</div>
                <div class="kpi-card-sub">T&#7927; l&#7879; &#273;&#7841;t &#273;&#250;ng h&#7841;n trung b&#236;nh</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-card-title">T&#7893;ng s&#7889; ca t&#432; v&#7845;n</div>
                <?php
                $sumTotal = 0;
                foreach ($leaderboard as $row) {
                    $sumTotal += $row['total_assigned'];
                }
                ?>
                <div class="kpi-card-value"><?= esc($sumTotal) ?></div>
                <div class="kpi-card-sub">Ti&#7871;n tr&#236;nh &#273;&#227; &#273;&#432;&#7907;c l&#432;u v&#7871;t</div>
            </div>
        </div>

        <?php if (!empty($overdueAlerts)) { ?>
            <div class="red-alerts-box">
                <h4 class="red-alerts-header">
                    <span class="alert-indicator"></span>
                    <span>C&#7842;NH B&#193;O &#272;&#7886;: C&#193;C TR&#431;&#7900;NG H&#7906;P CH&#258;M S&#211;C QU&#193; H&#7840;N CH&#431;A X&#7916; L&#221;</span>
                </h4>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($overdueAlerts as $alert) { ?>
                        <div class="red-alert-item">
                            <div>
                                <span style="font-size: 11px; font-weight: 700; background: #ffebee; color: #ff3b30; padding: 2px 8px; border-radius: 20px; border: 1px solid rgba(255,59,48,0.15); text-transform: uppercase;">
                                    <?= esc($alert['status_name']) ?>
                                </span>
                                <strong style="font-size: 13.5px; margin-left: 8px; color: #1d1d1f;">
                                    <?= esc($alert['customer_name']) ?> (<?= esc($alert['customer_code']) ?>)
                                </strong>
                                <div style="font-size: 11px; color: #86868b; margin-top: 4px;">
                                    H&#7841;n ch&#259;m s&#243;c: <?= date('d/m/Y H:i', strtotime($alert['due_time'])) ?> (<?= esc($alert['sla_duration']) ?> gi&#7901;) |
                                    Nh&#226;n vi&#234;n ph&#7909; tr&#225;ch: <b style="color: #1d1d1f;"><?= esc($alert['staff_name'] ?: html_entity_decode('H&#7879; th&#7889;ng', ENT_QUOTES, 'UTF-8')) ?></b>
                                </div>
                            </div>

                            <div style="text-align: right; display: flex; align-items: center; gap: 15px;">
                                <div style="color: #ff3b30; font-size: 12px; font-weight: 800;">
                                    <i class="fas fa-exclamation-triangle"></i> Qu&#225; h&#7841;n <?= esc($alert['delay_string']) ?>
                                </div>
                                <a href="<?= base_url('customers/show/' . $alert['customer_id'] . '#customer-care') ?>" class="btn-premium-sm" style="border-radius: 20px; text-decoration: none; padding: 5px 12px; font-size: 11px; font-weight: 700; background: var(--regular-blue-gradient); border: none; color: #fff;">
                                    X&#7917; l&#253; ngay
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } else { ?>
            <div style="background: #f5f5f7; border-radius: 16px; padding: 20px; margin-bottom: 25px; text-align: center; border: 1px solid rgba(0,0,0,0.04);">
                <i class="fas fa-check-circle" style="font-size: 24px; color: #34c759; margin-bottom: 8px; display: block;"></i>
                <p style="margin: 0; font-size: 13px; color: #86868b; font-weight: 600;">Tuy&#7879;t v&#7901;i! Kh&#244;ng c&#243; kh&#225;ch h&#224;ng n&#224;o &#273;ang b&#7883; tr&#7877; h&#7841;n ch&#259;m s&#243;c.</p>
            </div>
        <?php } ?>

        <div class="premium-card" style="border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 4px 20px rgba(0,0,0,0.01);">
            <div style="padding: 16px 20px; border-bottom: 1px solid #f2f2f7; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin:0; font-size:14px; font-weight:700; color:#1d1d1f;"><i class="fas fa-trophy" style="color: #ff9500; margin-right: 8px;"></i>B&#7843;ng x&#7871;p h&#7841;ng Hi&#7879;u su&#7845;t Ch&#259;m s&#243;c Nh&#226;n s&#7921;</h4>
            </div>

            <div style="overflow-x: auto;">
                <table class="sla-performance-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">XH</th>
                            <th>Nh&#226;n vi&#234;n</th>
                            <th>Ch&#7913;c v&#7909;</th>
                            <th style="text-align: center;">T&#7893;ng ca giao</th>
                            <th style="text-align: center;">&#272;&#250;ng h&#7841;n (&#272;&#7841;t)</th>
                            <th style="text-align: center;">Qu&#225; h&#7841;n (B&#7883; l&#7905;)</th>
                            <th style="text-align: center;">&#272;ang x&#7917; l&#253;</th>
                            <th style="width: 200px;">T&#7927; l&#7879; &#272;&#250;ng H&#7841;n</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leaderboard)) { ?>
                            <tr>
                                <td colspan="8" class="text-center" style="padding: 40px; color: #86868b;">Ch&#432;a c&#243; d&#7919; li&#7879;u th&#7889;ng k&#234; hi&#7879;u su&#7845;t nh&#226;n s&#7921;.</td>
                            </tr>
                        <?php } else { ?>
                            <?php $rank = 1; ?>
                            <?php foreach ($leaderboard as $row) { ?>
                                <?php
                                $rate = $row['efficiency_rate'];
                                $barColor = $rate >= 85 ? '#34c759' : ($rate >= 70 ? '#ff9500' : '#ff3b30');
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($rank === 1) { ?>
                                            <span style="font-size:14px; font-weight:800; background:rgba(255,149,0,0.15); color:#ff9500; padding: 2px 7px; border-radius:50%;">1</span>
                                        <?php } elseif ($rank === 2) { ?>
                                            <span style="font-size:14px; font-weight:800; background:rgba(142,142,147,0.15); color:#8e8e93; padding: 2px 7px; border-radius:50%;">2</span>
                                        <?php } elseif ($rank === 3) { ?>
                                            <span style="font-size:14px; font-weight:800; background:rgba(162,132,94,0.15); color:#a2845e; padding: 2px 7px; border-radius:50%;">3</span>
                                        <?php } else { ?>
                                            <span style="font-size:13.5px; color:#86868b; padding-left: 6px;"><?= $rank ?></span>
                                        <?php } ?>
                                    </td>
                                    <td><strong style="color: #1d1d1f;"><?= esc($row['full_name']) ?></strong></td>
                                    <td><span style="font-size: 12px; color: #86868b;"><?= esc($row['position'] ?: '--') ?></span></td>
                                    <td style="text-align: center; font-weight: 600;"><?= esc($row['total_assigned']) ?></td>
                                    <td style="text-align: center; font-weight: 700; color: #34c759;">+<?= esc($row['achieved_count']) ?></td>
                                    <td style="text-align: center; font-weight: 700; color: #ff3b30;">-<?= esc($row['overdue_count']) ?></td>
                                    <td style="text-align: center; color: #0071e3; font-weight: 600;"><?= esc($row['in_progress_count']) ?></td>
                                    <td>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar-fill" style="width: <?= $rate ?>%; background-color: <?= $barColor ?>;"></div>
                                        </div>
                                        <span style="font-size: 13px; font-weight: 700; color: <?= $barColor ?>;"><?= $rate ?>%</span>
                                    </td>
                                </tr>
                                <?php $rank++; ?>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (has_permission('care.manage') || has_permission('sys.admin')) { ?>
        <div class="sla-tab-pane" id="sla-configuration">
            <div class="premium-card" style="border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 4px 20px rgba(0,0,0,0.01);">
                <div style="padding: 16px 20px; border-bottom: 1px solid #f2f2f7; display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="margin:0; font-size:14px; font-weight:700; color:#1d1d1f;"><i class="fas fa-cog" style="color: #8e8e93; margin-right: 8px;"></i>Danh s&#225;ch c&#225;c b&#432;&#7899;c tr&#7841;ng th&#225;i t&#432; v&#7845;n v&#224; H&#7841;n m&#7913;c Ch&#259;m s&#243;c</h4>
                    <button class="btn-premium-sm" onclick="openAddSlaModal()" style="border-radius: 20px; background: var(--regular-blue-gradient); border: none; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; color: #fff; padding: 6px 15px;">
                        <i class="fas fa-plus"></i> Th&#234;m tr&#7841;ng th&#225;i m&#7899;i
                    </button>
                </div>

                <div style="overflow-x: auto;">
                    <table class="sla-performance-table">
                        <thead>
                            <tr>
                                <th style="width: 80px; text-align: center;">Th&#7913; t&#7921;</th>
                                <th>T&#234;n tr&#7841;ng th&#225;i</th>
                                <th>M&#227; &#273;&#7883;nh danh (Key)</th>
                                <th style="text-align: center;">H&#7841;n &#273;&#7883;nh Ch&#259;m s&#243;c (Gi&#7901;)</th>
                                <th style="text-align: center;">M&#224;u &#273;&#7841;i di&#7879;n</th>
                                <th style="text-align: center;">Tr&#7841;ng th&#225;i</th>
                                <th style="width: 150px; text-align: center;">Thao t&#225;c</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($slaSettings)) { ?>
                                <tr>
                                    <td colspan="7" class="text-center" style="padding: 40px; color: #86868b;">Kh&#244;ng c&#243; tr&#7841;ng th&#225;i Ch&#259;m s&#243;c n&#224;o &#273;&#432;&#7907;c c&#7845;u h&#236;nh.</td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($slaSettings as $set) { ?>
                                    <tr>
                                        <td style="text-align: center; font-weight: 600;"><?= esc($set['sort_order']) ?></td>
                                        <td><strong style="color: #1d1d1f;"><?= esc($set['status_name']) ?></strong></td>
                                        <td><code><?= esc($set['status_key']) ?></code></td>
                                        <td style="text-align: center; font-weight: 700; color: #0071e3;">
                                            <?= esc($set['sla_hours']) ?> gi&#7901;
                                            <span style="font-size: 11px; color: #86868b; font-weight: normal; display: block;">
                                                <?= $set['sla_hours'] > 0 ? round($set['sla_hours'] / 24, 1) . ' ng&#224;y' : 'V&#244; th&#7901;i h&#7841;n' ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; background-color: <?= esc($set['color']) ?>15; color: <?= esc($set['color']) ?>; font-size: 11px; font-weight: 700; border: 1px solid <?= esc($set['color']) ?>30;">
                                                <span style="width: 6px; height: 6px; border-radius: 50%; background-color: <?= esc($set['color']) ?>;"></span>
                                                <?= esc($set['color']) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($set['is_active'] == 1) { ?>
                                                <span class="badge-status-completed" style="font-size: 10px; padding: 2px 8px; border-radius: 12px; font-weight: 600;">K&#237;ch ho&#7841;t</span>
                                            <?php } else { ?>
                                                <span class="badge-status-cancelled" style="font-size: 10px; padding: 2px 8px; border-radius: 12px; font-weight: 600;">&#272;ang kh&#243;a</span>
                                            <?php } ?>
                                        </td>
                                        <td style="text-align: center; display: flex; justify-content: center; gap: 8px;">
                                            <button class="btn-secondary-sm" style="border-radius: 20px; font-size: 11px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;"
                                                    data-id="<?= $set['id'] ?>"
                                                    data-key="<?= esc($set['status_key']) ?>"
                                                    data-name="<?= esc($set['status_name']) ?>"
                                                    data-hours="<?= esc($set['sla_hours']) ?>"
                                                    data-color="<?= esc($set['color']) ?>"
                                                    data-sort="<?= esc($set['sort_order']) ?>"
                                                    data-active="<?= esc($set['is_active']) ?>"
                                                    onclick="openEditSlaModal(this)">
                                                <i class="fas fa-edit"></i> S&#7917;a
                                            </button>
                                            <button class="btn-secondary-sm text-danger" style="border-radius: 20px; font-size: 11px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;"
                                                    data-name="<?= esc($set['status_name']) ?>"
                                                    onclick="deleteSlaSetting(<?= $set['id'] ?>, this.getAttribute('data-name'))">
                                                <i class="fas fa-trash"></i> X&#243;a
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="sla-tab-pane" id="monitoring-configuration">
            <div class="premium-card" style="border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 4px 20px rgba(0,0,0,0.01);">
                <div style="padding: 16px 20px; border-bottom: 1px solid #f2f2f7; display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="margin:0; font-size:14px; font-weight:700; color:#1d1d1f;"><i class="fas fa-eye" style="color: #8e8e93; margin-right: 8px;"></i>Danh s&#225;ch tr&#7841;ng th&#225;i gi&#225;m s&#225;t CSKH</h4>
                    <button class="btn-premium-sm" onclick="openAddMonitoringModal()" style="border-radius: 20px; background: var(--regular-blue-gradient); border: none; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; color: #fff; padding: 6px 15px;">
                        <i class="fas fa-plus"></i> Th&#234;m tr&#7841;ng th&#225;i m&#7899;i
                    </button>
                </div>

                <div style="overflow-x: auto;">
                    <table class="sla-performance-table">
                        <thead>
                            <tr>
                                <th style="width: 80px; text-align: center;">Th&#7913; t&#7921;</th>
                                <th>T&#234;n tr&#7841;ng th&#225;i</th>
                                <th>M&#227; &#273;&#7883;nh danh (Key)</th>
                                <th style="text-align: center;">M&#224;u &#273;&#7841;i di&#7879;n</th>
                                <th style="text-align: center;">Tr&#7841;ng th&#225;i</th>
                                <th style="width: 150px; text-align: center;">Thao t&#225;c</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($monitoringSettings)) { ?>
                                <tr>
                                    <td colspan="6" class="text-center" style="padding: 40px; color: #86868b;">Kh&#244;ng c&#243; tr&#7841;ng th&#225;i gi&#225;m s&#225;t n&#224;o &#273;&#432;&#7907;c c&#7845;u h&#236;nh.</td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($monitoringSettings as $set) { ?>
                                    <tr>
                                        <td style="text-align: center; font-weight: 600;"><?= esc($set['sort_order']) ?></td>
                                        <td><strong style="color: #1d1d1f;"><?= esc($set['status_name']) ?></strong></td>
                                        <td><code><?= esc($set['status_key']) ?></code></td>
                                        <td style="text-align: center;">
                                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; background-color: <?= esc($set['color']) ?>15; color: <?= esc($set['color']) ?>; font-size: 11px; font-weight: 700; border: 1px solid <?= esc($set['color']) ?>30;">
                                                <span style="width: 6px; height: 6px; border-radius: 50%; background-color: <?= esc($set['color']) ?>;"></span>
                                                <?= esc($set['color']) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($set['is_active'] == 1) { ?>
                                                <span class="badge-status-completed" style="font-size: 10px; padding: 2px 8px; border-radius: 12px; font-weight: 600;">K&#237;ch ho&#7841;t</span>
                                            <?php } else { ?>
                                                <span class="badge-status-cancelled" style="font-size: 10px; padding: 2px 8px; border-radius: 12px; font-weight: 600;">&#272;ang kh&#243;a</span>
                                            <?php } ?>
                                        </td>
                                        <td style="text-align: center; display: flex; justify-content: center; gap: 8px;">
                                            <button class="btn-secondary-sm" style="border-radius: 20px; font-size: 11px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;"
                                                    data-id="<?= $set['id'] ?>"
                                                    data-key="<?= esc($set['status_key']) ?>"
                                                    data-name="<?= esc($set['status_name']) ?>"
                                                    data-color="<?= esc($set['color']) ?>"
                                                    data-sort="<?= esc($set['sort_order']) ?>"
                                                    data-active="<?= esc($set['is_active']) ?>"
                                                    onclick="openEditMonitoringModal(this)">
                                                <i class="fas fa-edit"></i> S&#7917;a
                                            </button>
                                            <button class="btn-secondary-sm text-danger" style="border-radius: 20px; font-size: 11px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px;"
                                                    data-name="<?= esc($set['status_name']) ?>"
                                                    onclick="deleteMonitoringSetting(<?= $set['id'] ?>, this.getAttribute('data-name'))">
                                                <i class="fas fa-trash"></i> X&#243;a
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<div id="modalSlaSetting" class="modal-overlay-sla">
    <div class="modal-content-sla">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f2f2f7; padding-bottom: 15px; margin-bottom: 20px;">
            <h3 id="modalSlaTitle" style="margin: 0; font-size: 16px; font-weight: 700; color: #1d1d1f;">Th&#234;m c&#7845;u h&#236;nh tr&#7841;ng th&#225;i Ch&#259;m s&#243;c</h3>
            <button type="button" onclick="closeSlaModal()" style="border: none; background: none; font-size: 18px; color: #86868b; cursor: pointer; padding: 0;"><i class="fas fa-times"></i></button>
        </div>

        <form id="formSlaSetting">
            <input type="hidden" name="id" id="setting_id">

            <div class="form-group-premium m-b-15">
                <label class="label-premium" for="setting_status_name">T&#234;n tr&#7841;ng th&#225;i t&#432; v&#7845;n (Hi&#7875;n th&#7883;)</label>
                <input type="text" name="status_name" id="setting_status_name" class="form-control-premium" required placeholder="V&#237; d&#7909;: &#272;ang nghi&#234;n c&#7913;u b&#225;o ph&#237;, Ch&#7901; g&#7917;i h&#7891; s&#417;...">
            </div>

            <div class="form-group-premium m-b-15">
                <label class="label-premium" for="setting_status_key">M&#227; &#273;&#7883;nh danh (Key h&#7879; th&#7889;ng - Kh&#244;ng d&#7845;u/kh&#244;ng c&#225;ch)</label>
                <input type="text" name="status_key" id="setting_status_key" class="form-control-premium" required placeholder="V&#237; d&#7909;: dang_nghien_cuu_bao_phi">
                <small style="font-size: 11px; color: #86868b; margin-top: 4px; display: block;">M&#227; n&#224;y d&#249;ng &#273;&#7875; nh&#7853;n d&#7841;ng logic trong m&#227; ngu&#7891;n, vi&#7871;t li&#7873;n kh&#244;ng d&#7845;u, d&#249;ng g&#7841;ch d&#432;&#7899;i.</small>
            </div>

            <div class="form-group-premium m-b-15">
                <label class="label-premium" for="setting_sla_hours">Th&#7901;i h&#7841;n Ch&#259;m s&#243;c (S&#7889; gi&#7901; t&#7889;i &#273;a th&#7921;c hi&#7879;n)</label>
                <input type="number" name="sla_hours" id="setting_sla_hours" class="form-control-premium" required min="0" placeholder="V&#237; d&#7909;: 24, 48, 72...">
                <small style="font-size: 11px; color: #86868b; margin-top: 4px; display: block;">Nh&#7853;p s&#7889; 0 n&#7871;u b&#432;&#7899;c n&#224;y kh&#244;ng gi&#7899;i h&#7841;n th&#7901;i gian (kh&#244;ng &#225;p d&#7909;ng h&#7841;n ch&#259;m s&#243;c).</small>
            </div>

            <div class="form-group-premium m-b-15">
                <label class="label-premium" for="setting_color">M&#227; m&#224;u hi&#7875;n th&#7883; (Hex color)</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="color" id="color_picker" class="form-control-premium" style="width: 50px; padding: 0; height: 38px; border: 1px solid #d2d2d7;" oninput="document.getElementById('setting_color').value = this.value">
                    <input type="text" name="color" id="setting_color" class="form-control-premium" style="flex: 1;" required placeholder="#0071e3" oninput="document.getElementById('color_picker').value = this.value">
                </div>
            </div>

            <div class="form-group-premium m-b-15" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label class="label-premium" for="setting_sort_order">Th&#7913; t&#7921; hi&#7875;n th&#7883;</label>
                    <input type="number" name="sort_order" id="setting_sort_order" class="form-control-premium" required min="0" value="0">
                </div>
                <div>
                    <label class="label-premium" for="setting_is_active">Tr&#7841;ng th&#225;i v&#7853;n h&#224;nh</label>
                    <select name="is_active" id="setting_is_active" class="form-control-premium">
                        <option value="1">K&#237;ch ho&#7841;t</option>
                        <option value="0">T&#7841;m kh&#243;a</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px; border-top: 1px solid #f2f2f7; padding-top: 15px;">
                <button type="button" class="btn-secondary" onclick="closeSlaModal()" style="border-radius: 20px; font-size: 13px; font-weight: 600; padding: 8px 18px;">H&#7911;y b&#7887;</button>
                <button type="submit" class="btn-premium" style="border-radius: 20px; font-size: 13px; font-weight: 600; padding: 8px 18px; background: var(--regular-blue-gradient); border: none; color: #fff;">L&#432;u c&#7845;u h&#236;nh</button>
            </div>
        </form>
    </div>
</div>

<div id="modalMonitoringSetting" class="modal-overlay-sla">
    <div class="modal-content-sla">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f2f2f7; padding-bottom: 15px; margin-bottom: 20px;">
            <h3 id="modalMonitoringTitle" style="margin: 0; font-size: 16px; font-weight: 700; color: #1d1d1f;">Th&#234;m c&#7845;u h&#236;nh tr&#7841;ng th&#225;i gi&#225;m s&#225;t</h3>
            <button type="button" onclick="closeMonitoringModal()" style="border: none; background: none; font-size: 18px; color: #86868b; cursor: pointer; padding: 0;"><i class="fas fa-times"></i></button>
        </div>

        <form id="formMonitoringSetting">
            <input type="hidden" name="id" id="monitoring_setting_id">

            <div class="form-group-premium m-b-15">
                <label class="label-premium" for="monitoring_status_name">T&#234;n tr&#7841;ng th&#225;i gi&#225;m s&#225;t</label>
                <input type="text" name="status_name" id="monitoring_status_name" class="form-control-premium" required placeholder="V&#237; d&#7909;: Kh&#225;ch g&#7885;i ph&#224;n n&#224;n">
            </div>

            <div class="form-group-premium m-b-15">
                <label class="label-premium" for="monitoring_status_key">M&#227; &#273;&#7883;nh danh (Key h&#7879; th&#7889;ng)</label>
                <input type="text" name="status_key" id="monitoring_status_key" class="form-control-premium" required placeholder="V&#237; d&#7909;: khach_goi_phan_nan">
                <small style="font-size: 11px; color: #86868b; margin-top: 4px; display: block;">Vi&#7871;t li&#7873;n kh&#244;ng d&#7845;u, d&#249;ng g&#7841;ch d&#432;&#7899;i.</small>
            </div>

            <div class="form-group-premium m-b-15">
                <label class="label-premium" for="monitoring_color">M&#227; m&#224;u hi&#7875;n th&#7883; (Hex color)</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="color" id="monitoring_color_picker" class="form-control-premium" style="width: 50px; padding: 0; height: 38px; border: 1px solid #d2d2d7;" oninput="document.getElementById('monitoring_color').value = this.value">
                    <input type="text" name="color" id="monitoring_color" class="form-control-premium" style="flex: 1;" required placeholder="#ff3b30" oninput="document.getElementById('monitoring_color_picker').value = this.value">
                </div>
            </div>

            <div class="form-group-premium m-b-15" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label class="label-premium" for="monitoring_sort_order">Th&#7913; t&#7921; hi&#7875;n th&#7883;</label>
                    <input type="number" name="sort_order" id="monitoring_sort_order" class="form-control-premium" required min="0" value="0">
                </div>
                <div>
                    <label class="label-premium" for="monitoring_is_active">Tr&#7841;ng th&#225;i v&#7853;n h&#224;nh</label>
                    <select name="is_active" id="monitoring_is_active" class="form-control-premium">
                        <option value="1">K&#237;ch ho&#7841;t</option>
                        <option value="0">T&#7841;m kh&#243;a</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px; border-top: 1px solid #f2f2f7; padding-top: 15px;">
                <button type="button" class="btn-secondary" onclick="closeMonitoringModal()" style="border-radius: 20px; font-size: 13px; font-weight: 600; padding: 8px 18px;">H&#7911;y b&#7887;</button>
                <button type="submit" class="btn-premium" style="border-radius: 20px; font-size: 13px; font-weight: 600; padding: 8px 18px; background: var(--regular-blue-gradient); border: none; color: #fff;">L&#432;u c&#7845;u h&#236;nh</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/sla_report.js') ?>"></script>
<?= $this->endSection() ?>
