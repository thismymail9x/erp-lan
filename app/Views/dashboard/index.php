<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<link rel="stylesheet" href="<?= base_url('css/dashboard_home.css') ?>?v=2026072402">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php if ($attendanceStatus && $attendanceStatus['status'] === 'CHECKED_OUT') { ?>
    <div class="attendance-hero hero-success">
        <div class="hero-content">
            <h2>Ho&#224;n th&#224;nh ng&#224;y l&#224;m vi&#7879;c!</h2>
            <p>&#272;&#227; Check-out l&#250;c <b><?= $attendanceStatus['check_out_time'] ?></b>. Ch&#250;c b&#7841;n bu&#7893;i t&#7889;i vui v&#7867;!</p>
        </div>
        <div class="hero-badge">&#272;&#195; XONG</div>
    </div>
<?php } elseif ($attendanceStatus && $attendanceStatus['status'] === 'CHECKED_IN') { ?>
    <div class="attendance-hero hero-warning">
        <div class="hero-content">
            <h2>&#272;ang l&#224;m vi&#7879;c (In: <?= $attendanceStatus['check_in_time'] ?>)</h2>
            <p>&#272;&#7915;ng qu&#234;n Check-out tr&#432;&#7899;c khi ra v&#7873; &#273;&#7875; ghi nh&#7853;n &#273;&#7911; gi&#7901; l&#224;m nh&#233;.</p>
        </div>
        <a href="<?= base_url('attendance') ?>" class="btn-attendance-main text-orange">
            <i class="fas fa-sign-out-alt"></i> K&#7871;t th&#250;c ng&#224;y
        </a>
    </div>
<?php } else { ?>
    <div class="attendance-hero hero-primary">
        <div class="hero-content">
            <h2>B&#7855;t &#273;&#7847;u ng&#224;y l&#224;m vi&#7879;c?</h2>
            <p>Vui l&#242;ng ghi nh&#7853;n v&#7883; tr&#237; v&#224; &#7843;nh ch&#7909;p &#273;&#7875; ho&#224;n t&#7845;t &#273;i&#7875;m danh.</p>
        </div>
        <a href="<?= base_url('attendance') ?>" class="btn-attendance-main">
            <i class="fas fa-camera"></i> &#272;i&#7875;m danh
        </a>
    </div>
<?php } ?>

<div class="motivation-widget premium-card m-b-24">
    <div class="motivation-header">
        <div class="motivation-title">
            <h3><i class="fas fa-coins text-gold"></i> KPI n&#259;m
                <form action="<?= base_url('dashboard') ?>" method="GET" style="display: inline-block;">
                    <select name="year" onchange="this.form.submit()" style="border: none; background: transparent; font-size: 1.1rem; font-weight: 700; color: #1d1d1f; cursor: pointer; outline: none;">
                        <?php
                        $startYear = 2026;
                        $endYear = max(date('Y') + 1, 2027);
                        for ($y = $startYear; $y <= $endYear; $y++) { ?>
                            <option value="<?= $y ?>" <?= $kpiYear == $y ? 'selected' : '' ?>><?= $y ?>&nbsp;</option>
                        <?php } ?>
                    </select>
                </form>
            </h3>
            <p class="text-muted">Ho&#224;n th&#224;nh c&#225;c b&#432;&#7899;c trong h&#7891; s&#417; &#273;&#7875; t&#7889;i &#432;u h&#243;a thu nh&#7853;p.</p>
        </div>
        <div class="motivation-total">
            <span class="total-label">T&#7893;ng m&#7909;c ti&#234;u:</span>
            <span class="total-value text-blue"><?= number_format($kpiStats['total']) ?> vnd</span>
        </div>
    </div>

    <div class="motivation-body">
        <div class="kpi-progress-container">
            <div class="kpi-progress-info">
                <span>Ti&#7871;n &#273;&#7897; m&#7909;c ti&#234;u</span>
                <span><?= $kpiStats['percent'] ?>%</span>
            </div>
            <div class="kpi-progress-bar-bg">
                <div class="kpi-progress-bar-fill" style="width: <?= min(100, (float)$kpiStats['percent']) ?>%;"></div>
            </div>
        </div>

        <div class="kpi-stats-row">
            <div class="kpi-stat-item">
                <div class="kpi-stat-label">KPI nh&#7853;n</div>
                <div class="kpi-stat-val text-green">+ <?= number_format($kpiStats['earned']) ?> vnd</div>
            </div>
            <div class="kpi-stat-item">
                <div class="kpi-stat-label">KPI c&#242;n</div>
                <div class="kpi-stat-val text-orange">~ <?= number_format($kpiStats['potential']) ?> vnd</div>
            </div>
            <div class="kpi-stat-item" onclick="window.location.href='<?= base_url('cases?status=missed_kpi' . ($isAdmin ? '' : '&lawyer_id[]=' . session()->get('employee_id'))) ?>'" style="cursor: pointer;">
                <div class="kpi-stat-label">KPI b&#7887; l&#7905; <i class="fas fa-external-link-alt" style="font-size: 0.6rem;"></i></div>
                <div class="kpi-stat-val text-red">- <?= number_format($kpiStats['lost']) ?> vnd</div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($canViewConsultingKpi) && !empty($consultingKpiStats)) { ?>
    <div class="motivation-widget premium-card m-b-24">
        <div class="motivation-header">
            <div class="motivation-title">
                <h3><i class="fas fa-headset text-blue"></i> KPI t&#432; v&#7845;n th&#225;ng
                    <form action="<?= base_url('dashboard') ?>" method="GET" style="display: inline-block;">
                        <input type="month" name="month" value="<?= esc($kpiMonth) ?>" onchange="this.form.submit()" style="border: none; background: transparent; font-size: 1.1rem; font-weight: 700; color: #1d1d1f; cursor: pointer; outline: none;">
                    </form>
                </h3>
            </div>
            <div class="motivation-total">
                <span class="total-label">Doanh thu th&#7921;c thu:</span>
                <span class="total-value text-blue"><?= number_format($consultingKpiStats['contract_value']) ?> vnd</span>
            </div>
        </div>

        <div class="motivation-body">
            <div class="kpi-progress-container">
                <div class="kpi-progress-info">
                    <span>Ti&#7871;n &#273;&#7897; m&#7909;c ti&#234;u th&#225;ng</span>
                    <span><?= esc($consultingKpiStats['percent']) ?>%</span>
                </div>
                <div class="kpi-progress-bar-bg">
                    <div class="kpi-progress-bar-fill" style="width: <?= min(100, (float)$consultingKpiStats['percent']) ?>%;"></div>
                </div>
            </div>

            <div class="kpi-stats-row">
                <div class="kpi-stat-item">
                    <div class="kpi-stat-label">Tr&#7843; k&#7923; l&#432;&#417;ng t&#7899;i</div>
                    <div class="kpi-stat-val text-green">+ <?= number_format($consultingKpiStats['next_payroll_payout']) ?> vnd</div>
                </div>
                <div class="kpi-stat-item">
                    <div class="kpi-stat-label">T&#237;ch l&#361;y cu&#7889;i n&#259;m</div>
                    <div class="kpi-stat-val text-orange">~ <?= number_format($consultingKpiStats['annual_accrual']) ?> vnd</div>
                </div>
                <div class="kpi-stat-item" onclick="window.location.href='<?= base_url('kpi/consulting?month=' . urlencode($kpiMonth)) ?>'" style="cursor: pointer;">
                    <div class="kpi-stat-label">Th&#432;&#7903;ng v&#432;&#7907;t m&#7889;c <i class="fas fa-external-link-alt" style="font-size: 0.6rem;"></i></div>
                    <div class="kpi-stat-val text-blue"><?= number_format($consultingKpiStats['milestone_bonus']) ?> vnd</div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<div class="stats-grid dashboard-home-stats-grid">
    <?php
    $canSeeAllStats = ($isAdmin || $isManager || $isLegalDept || has_permission('case.view_all'));
    ?>

    <?php if ($canSeeAllStats): ?>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases') ?>'" style="cursor: pointer;">
            <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
            <div class="stat-value"><?= number_format($stats['total_cases'] ?? 0) ?></div>
            <div class="stat-label">T&#7893;ng v&#7909; vi&#7879;c</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=cho_tiep_nhan') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-orange"><i class="fas fa-hourglass-start"></i></div>
            <div class="stat-value"><?= number_format($stats['waiting_cases'] ?? 0) ?></div>
            <div class="stat-label">Ch&#7901; ti&#7871;p nh&#7853;n</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=dang_xu_ly') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-blue"><i class="fas fa-spinner fa-spin"></i></div>
            <div class="stat-value"><?= number_format($stats['processing_cases'] ?? 0) ?></div>
            <div class="stat-label">&#272;ang x&#7917; l&#253;</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=tam_dung') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-orange"><i class="fas fa-pause-circle"></i></div>
            <div class="stat-value"><?= number_format($stats['paused_cases'] ?? 0) ?></div>
            <div class="stat-label">T&#7841;m d&#7915;ng</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=da_hoan_thanh') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-green"><i class="fas fa-check-double"></i></div>
            <div class="stat-value"><?= number_format($stats['completed_cases'] ?? 0) ?></div>
            <div class="stat-label">&#272;&#227; ho&#224;n th&#224;nh</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=overdue') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-red"><i class="fas fa-clock"></i></div>
            <div class="stat-value text-red"><?= number_format($stats['overdue_cases'] ?? 0) ?></div>
            <div class="stat-label">Qu&#225; h&#7841;n</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('customers') ?>'" style="cursor: pointer;">
            <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
            <div class="stat-value"><?= number_format($stats['customers'] ?? 0) ?></div>
            <div class="stat-label">Kh&#225;ch h&#224;ng</div>
        </div>

        <?php if ($isManager && !$isLegalDept && !$isAdmin): ?>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users-viewfinder"></i></div>
                <div class="stat-value"><?= number_format($deptStats['total_members'] ?? 0) ?></div>
                <div class="stat-label">Nh&#226;n s&#7921; team</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-value"><?= $deptStats['attendance_percent'] ?? 0 ?>%</div>
                <div class="stat-label">T&#7927; l&#7879; c&#244;ng team</div>
            </div>
        <?php endif; ?>

    <?php elseif ($isHRDept): ?>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?= number_format($deptStats['total_company_employees'] ?? 0) ?></div>
            <div class="stat-label">T&#7893;ng nh&#226;n s&#7921;</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
            <div class="stat-value"><?= number_format($deptStats['new_hires_this_month'] ?? 0) ?></div>
            <div class="stat-label">Nh&#226;n s&#7921; m&#7899;i</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-id-badge"></i></div>
            <div class="stat-value"><?= $deptStats['attendance_percent'] ?? 0 ?>%</div>
            <div class="stat-label">T&#7927; l&#7879; &#273;i l&#224;m</div>
        </div>
    <?php else: ?>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases') ?>'" style="cursor: pointer;">
            <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
            <div class="stat-value"><?= number_format($stats['total_cases'] ?? 0) ?></div>
            <div class="stat-label">V&#7909; vi&#7879;c c&#7911;a t&#244;i</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=cho_tiep_nhan') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-orange"><i class="fas fa-hourglass-start"></i></div>
            <div class="stat-value"><?= number_format($stats['waiting_cases'] ?? 0) ?></div>
            <div class="stat-label">Ch&#7901; ti&#7871;p nh&#7853;n</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=dang_xu_ly') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-blue"><i class="fas fa-spinner fa-spin"></i></div>
            <div class="stat-value"><?= number_format($stats['processing_cases'] ?? 0) ?></div>
            <div class="stat-label">&#272;ang l&#224;m</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=da_hoan_thanh') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-green"><i class="fas fa-check-double"></i></div>
            <div class="stat-value"><?= number_format($stats['completed_cases'] ?? 0) ?></div>
            <div class="stat-label">&#272;&#227; xong</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=overdue' . ($isAdmin ? '' : '&lawyer_id[]=' . session()->get('employee_id'))) ?>'" style="cursor: pointer;">
            <div class="stat-icon text-red"><i class="fas fa-clock"></i></div>
            <div class="stat-value text-red"><?= number_format($stats['overdue_cases'] ?? 0) ?></div>
            <div class="stat-label">Tr&#7877; h&#7841;n</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-value"><?= $stats['attendance_rate'] ?? 0 ?>%</div>
            <div class="stat-label">Chuy&#234;n c&#7847;n</div>
        </div>
    <?php endif; ?>
</div>

<div class="calendar-container-card">
    <aside class="calendar-sidebar">
        <button class="btn-create-ws" id="btnOpenCreate">
            <i class="fas fa-plus"></i> L&#7883;ch tr&#236;nh c&#244;ng vi&#7879;c
        </button>
        <a href="<?= base_url('leave-requests/create') ?>" class="btn-create-leave">
            <i class="fas fa-calendar-minus"></i> &#272;&#417;n ngh&#7881;
        </a>

        <div class="filter-section">
            <div class="sidebar-section-title">B&#7897; l&#7885;c nh&#226;n s&#7921;</div>
            <div class="filter-group" style="margin-bottom: 8px;">
                <select id="filterDept" class="form-control-custom">
                    <option value="">T&#7845;t c&#7843; ph&#242;ng ban</option>
                    <?php foreach ($departments as $dept) : ?>
                        <option value="<?= $dept['id'] ?>"><?= esc($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select id="filterEmployee" class="select2-basic">
                    <option value="">T&#7845;t c&#7843; nh&#226;n vi&#234;n</option>
                    <?php foreach ($employees as $emp) : ?>
                        <option value="<?= $emp['id'] ?>" data-dept="<?= $emp['department_id'] ?>"><?= esc($emp['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="legend-section">
            <div class="sidebar-section-title">Lo&#7841;i l&#7883;ch tr&#236;nh</div>
            <div class="type-legend">
                <div class="legend-item active" data-type="leave">
                    <div class="legend-color" style="background: #e74c3c;"></div>
                    <span>Ng&#224;y ngh&#7881; (&#272;&#7887;)</span>
                </div>
                <div class="legend-item active" data-type="business_trip">
                    <div class="legend-color" style="background: #10b981;"></div>
                    <span>C&#244;ng t&#225;c (Xanh l&#225;)</span>
                </div>
                <div class="legend-item active" data-type="vehicle_hint">
                    <div class="legend-color" style="background: #2563eb;"></div>
                    <span>&#272;&#259;ng k&#253; xe</span>
                </div>
                <div class="legend-item active" data-type="work">
                    <div class="legend-color" style="background: #f59e0b;"></div>
                    <span>T&#7841;i v&#259;n ph&#242;ng (V&#224;ng)</span>
                </div>
            </div>
        </div>
    </aside>

    <main class="calendar-main">
        <div id="calendar"></div>
    </main>
</div>

<div class="modal-overlay" id="wsModal">
    <div class="modal-content-custom">
        <div class="modal-header">
            <h3 id="modalTitle">T&#7841;o l&#7883;ch tr&#236;nh m&#7899;i</h3>
            <button class="close-modal" id="btnCloseModal"><i class="fas fa-times"></i></button>
        </div>
        <form id="wsForm" data-current-employee-id="<?= esc($current_employee_id) ?>">
            <input type="hidden" name="id" id="wsId">

            <div class="ws-title-row">
                <div class="form-group">
                    <label>Ti&#234;u &#273;&#7873; / M&#7909;c &#273;&#237;ch</label>
                    <input type="text" name="title" id="wsTitle" class="form-control-custom" placeholder="V&#237; d&#7909;: H&#7885;p v&#7899;i kh&#225;ch h&#224;ng A, C&#244;ng t&#225;c H&#224; N&#7897;i..." required>
                </div>
                <label class="ws-vehicle-option" for="wsRequiresVehicle">
                    <input type="checkbox" name="requires_vehicle" id="wsRequiresVehicle" value="1">
                    <span>&#272;&#259;ng k&#253; xe</span>
                </label>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label>Lo&#7841;i l&#7883;ch tr&#236;nh</label>
                    <select name="type" id="wsType" class="form-control-custom">
                        <option value="business_trip">&#272;i c&#244;ng t&#225;c</option>
                        <option value="work">T&#7841;i v&#259;n ph&#242;ng</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&#272;&#7883;a &#273;i&#7875;m</label>
                    <input type="text" name="location" id="wsLocation" class="form-control-custom" placeholder="&#272;&#7883;a ch&#7881; ho&#7863;c t&#234;n v&#259;n ph&#242;ng">
                </div>
            </div>

            <div class="row-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Ng&#224;y b&#7855;t &#273;&#7847;u</label>
                    <input type="date" id="wsStartDate" class="form-control-custom" required>
                </div>
                <div class="form-group">
                    <label>Gi&#7901;</label>
                    <input type="time" id="wsStartTime" class="form-control-custom" value="08:00" required>
                </div>
            </div>

            <div class="row-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Ng&#224;y k&#7871;t th&#250;c</label>
                    <input type="date" id="wsEndDate" class="form-control-custom" required>
                </div>
                <div class="form-group">
                    <label>Gi&#7901;</label>
                    <input type="time" id="wsEndTime" class="form-control-custom" value="17:00" required>
                </div>
            </div>

            <input type="hidden" name="start_at" id="wsStartAt">
            <input type="hidden" name="end_at" id="wsEndAt">

            <div style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-top: 15px; border: 1px solid #e2e8f0;">
                <div class="form-group">
                    <label>Nh&#226;n s&#7921; th&#7921;c hi&#7879;n</label>
                    <select name="employee_id" id="wsEmployeeId" class="form-control-custom select2-basic">
                        <?php foreach ($employees as $emp) : ?>
                            <option value="<?= $emp['id'] ?>" <?= $emp['id'] == $current_employee_id ? 'selected' : '' ?>><?= esc($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-briefcase"></i> V&#7909; vi&#7879;c li&#234;n quan</label>
                    <select name="case_id" id="wsCaseId" class="form-control-custom select2-basic">
                        <option value="">-- Kh&#244;ng g&#7855;n v&#7909; vi&#7879;c --</option>
                        <?php foreach (($selectableCases ?? []) as $caseOption) : ?>
                            <option value="<?= $caseOption['id'] ?>">
                                <?= esc($caseOption['code'] . ' - ' . $caseOption['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="ws-private-hint">
                        <i class="fas fa-lock"></i> Th&#244;ng tin v&#7909; vi&#7879;c ch&#7881; hi&#7875;n th&#7883; cho ng&#432;&#7901;i c&#243; quy&#7873;n.
                    </div>
                </div>

                <?php if (!empty($canSubmitCaseExpense)) { ?>
                    <div class="ws-expense-quick-panel" id="wsExpenseQuickPanel">
                        <div class="ws-expense-quick-title">
                            <i class="fas fa-receipt"></i> Chi ph&#237; ph&#225;t sinh
                        </div>
                        <div class="row-grid ws-expense-quick-grid">
                            <div class="form-group">
                                <label>Lo&#7841;i chi ph&#237;</label>
                                <select name="expense_category" id="wsExpenseCategory" class="form-control-custom">
                                    <?php foreach (($caseExpenseCategoryLabels ?? []) as $key => $label) { ?>
                                        <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>S&#7889; ti&#7873;n</label>
                                <input type="text" name="expense_amount" id="wsExpenseAmount" class="form-control-custom" placeholder="VD: 250000">
                            </div>
                        </div>
                        <div class="form-group ws-expense-note-group">
                            <label>Ghi ch&#250; chi ph&#237;</label>
                            <textarea name="expense_note" id="wsExpenseNote" class="form-control-custom" placeholder="G&#7917;i xe, taxi, n&#432;&#7899;c u&#7889;ng, l&#7879; ph&#237;..."></textarea>
                        </div>
                    </div>
                <?php } ?>

                <div class="form-group ws-assigned-by-field">
                    <label><i class="fas fa-user-tag"></i> Nh&#7853;n ph&#226;n c&#244;ng t&#7915;</label>
                    <select name="assigned_by_id" id="wsAssignedById" class="form-control-custom select2-basic">
                        <option value="">-- Kh&#244;ng ch&#7885;n --</option>
                        <?php foreach ($employees as $emp) : ?>
                            <option value="<?= $emp['id'] ?>"><?= esc($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary-custom" id="btnDeleteWs" style="display: none; background: #fee2e2; color: #dc2626;">X&#243;a</button>
                <a href="#" class="btn-secondary-custom ws-expense-link" id="btnWsExpense">
                    <i class="fas fa-receipt"></i> Chi ph&#237;
                </a>
                <button type="button" class="btn-secondary-custom" id="btnCancelModal">H&#7911;y</button>
                <button type="submit" class="btn-primary-custom" id="btnSaveWs">L&#432;u l&#7883;ch tr&#236;nh</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
<script src="<?= base_url('js/dashboard_home.js') ?>?v=2026072402"></script>
<?= $this->endSection() ?>
