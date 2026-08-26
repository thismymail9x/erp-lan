<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customer_care.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4 customer-care-shell">
    <div class="d-flex justify-content-between align-items-center mb-4 customer-care-header">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 font-weight-700 text-dark m-0">K&#7871; Ho&#7841;ch Ch&#259;m S&#243;c Kh&#225;ch H&#224;ng</h1>
                <span class="badge-segment <?= esc($customer['customer_segment'] ?? 'potential') ?>">
                    Nh&#243;m <?= esc($customer['customer_segment'] === 'vip' ? 'A (VIP)' : ($customer['customer_segment'] === 'regular' ? 'B (Phổ thông)' : 'C (Tiềm năng)')) ?>
                </span>
            </div>
            <p class="text-muted font-size-0.9">
                Kh&#225;ch h&#224;ng: <strong class="text-dark"><?= esc($customer['name']) ?></strong>
                (<?= esc($customer['code']) ?>) | S&#7889; &#273;i&#7879;n tho&#7841;i: <?= esc($customer['phone']) ?>
            </p>
        </div>
        <div class="d-flex gap-2 customer-care-actions">
            <a href="<?= base_url('customer-care/loyalty/' . $customer['id']) ?>" class="btn-premium d-flex align-items-center gap-2">
                <i class="fas fa-id-card"></i> <span>Th&#7867; VIP / Loyalty</span>
            </a>
            <a href="<?= base_url('customer-care') ?>" class="btn-secondary d-flex align-items-center gap-2">
                <i class="fas fa-chevron-left"></i> <span>Quay l&#7841;i</span>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h5 class="card-title font-weight-700 mb-4">L&#7883;ch Tr&#236;nh Ch&#259;m S&#243;c 3 Giai &#272;o&#7841;n</h5>

                    <?php
                    $activePlan = null;
                    foreach ($plans as $p) {
                        if ($p['status'] === 'in_progress') {
                            $activePlan = $p;
                            break;
                        }
                    }
                    ?>

                    <?php if (!$activePlan): ?>
                        <div class="alert alert-info border-0 rounded-lg p-3 d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fas fa-info-circle fa-2x text-info"></i>
                                <div>
                                    <h6 class="font-weight-700 m-0 text-dark">Ch&#432;a c&#243; k&#7871; ho&#7841;ch ch&#259;m s&#243;c n&#224;o &#273;ang ch&#7841;y</h6>
                                    <p class="m-0 font-size-0.85 text-secondary">H&#227;y kh&#7903;i t&#7841;o giai &#273;o&#7841;n ch&#259;m s&#243;c ti&#7871;p theo cho kh&#225;ch h&#224;ng n&#224;y.</p>
                                </div>
                            </div>

                            <form action="<?= base_url('customer-care/init-plan/' . $customer['id']) ?>" method="POST" class="d-flex gap-2 align-items-center">
                                <?= csrf_field() ?>
                                <select name="phase" class="form-control rounded-pill px-3 py-1 font-size-0.85" style="width: auto;" required>
                                    <option value="phase1">Giai &#273;o&#7841;n 1 (Sau ho&#224;n th&#224;nh)</option>
                                    <option value="phase2">Giai &#273;o&#7841;n 2 (Sau 7-30 ng&#224;y)</option>
                                    <option value="phase3">Giai &#273;o&#7841;n 3 (Remarketing d&#224;i h&#7841;n)</option>
                                </select>
                                <button type="submit" class="btn btn-primary rounded-pill px-4 font-size-0.85">
                                    K&#237;ch Ho&#7841;t
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="care-timeline">
                        <?php
                        $p1 = array_filter($plans, function($x) { return $x['phase'] === 'phase1'; });
                        $p1 = reset($p1);
                        $p1StatusClass = $p1 ? ($p1['status'] === 'completed' ? 'completed' : ($p1['status'] === 'in_progress' ? 'active' : '')) : '';
                        ?>
                        <div class="timeline-item <?= $p1StatusClass ?>">
                            <div class="timeline-dot">
                                <i class="fas <?= $p1 && $p1['status'] === 'completed' ? 'fa-check' : 'fa-circle' ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">
                                    <span>Giai &#273;o&#7841;n 1: Ch&#259;m s&#243;c sau ho&#224;n t&#7845;t d&#7883;ch v&#7909; (Ng&#224;y 1 - 7)</span>
                                    <?php if ($p1): ?>
                                        <span class="badge badge-light font-weight-600"><?= esc($p1['status'] === 'completed' ? 'Xong' : 'Đang làm') ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="timeline-desc">Th&#7921;c hi&#7879;n c&#7843;m &#417;n, kh&#7843;o s&#225;t &#273;&#225;nh gi&#225; &#273;&#7897; h&#224;i l&#242;ng, v&#224; &#273;&#432;a kh&#225;ch h&#224;ng v&#224;o d&#242;ng lu&#7891;ng ph&#226;n nh&#243;m ch&#259;m s&#243;c.</p>

                                <?php if ($p1): ?>
                                    <ul class="task-checklist">
                                        <?php foreach ($planTasks[$p1['id']] as $t): ?>
                                            <li class="task-item <?= $t['is_completed'] ? 'completed' : '' ?>">
                                                <div class="task-checkbox-wrapper">
                                                    <input type="checkbox" class="task-checkbox" data-id="<?= $t['id'] ?>" <?= $t['is_completed'] ? 'checked disabled' : '' ?>>
                                                </div>
                                                <div class="task-details">
                                                    <div class="task-title-text"><?= esc($t['title']) ?></div>
                                                    <div class="task-desc-text"><?= esc($t['description']) ?></div>
                                                    <div class="task-meta-row">
                                                        <span class="task-channel <?= esc($t['channel']) ?>"><?= esc($t['channel']) ?></span>
                                                        <span class="task-meta-item <?= strtotime($t['due_date']) < time() && !$t['is_completed'] ? 'overdue' : '' ?>">
                                                            <i class="fas fa-calendar-alt"></i> H&#7841;n: <?= date('d/m/Y', strtotime($t['due_date'])) ?>
                                                        </span>
                                                        <?php if ($t['is_completed']): ?>
                                                            <span class="task-meta-item text-success">
                                                                <i class="fas fa-check-circle"></i> &#272;&#227; ho&#224;n th&#224;nh l&#250;c <?= date('H:i d/m/Y', strtotime($t['completed_at'])) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php
                        $p2 = array_filter($plans, function($x) { return $x['phase'] === 'phase2'; });
                        $p2 = reset($p2);
                        $p2StatusClass = $p2 ? ($p2['status'] === 'completed' ? 'completed' : ($p2['status'] === 'in_progress' ? 'active' : '')) : '';
                        ?>
                        <div class="timeline-item <?= $p2StatusClass ?>">
                            <div class="timeline-dot">
                                <i class="fas <?= $p2 && $p2['status'] === 'completed' ? 'fa-check' : 'fa-circle' ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">
                                    <span>Giai &#273;o&#7841;n 2: Nu&#244;i d&#432;&#7905;ng &amp; H&#7895; tr&#7907; gi&#225; tr&#7883; (Ng&#224;y 7 - 30)</span>
                                    <?php if ($p2): ?>
                                        <span class="badge badge-light font-weight-600"><?= esc($p2['status'] === 'completed' ? 'Xong' : 'Đang làm') ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="timeline-desc">H&#7887;i th&#259;m t&#236;nh tr&#7841;ng v&#7853;n h&#224;nh th&#7921;c t&#7871; c&#7911;a kh&#225;ch h&#224;ng, c&#7853;p nh&#7853;t c&#225;c v&#259;n b&#7843;n ph&#225;p lu&#7853;t, t&#224;i li&#7879;u c&#7849;m nang h&#7919;u &#237;ch.</p>

                                <?php if ($p2): ?>
                                    <ul class="task-checklist">
                                        <?php foreach ($planTasks[$p2['id']] as $t): ?>
                                            <li class="task-item <?= $t['is_completed'] ? 'completed' : '' ?>">
                                                <div class="task-checkbox-wrapper">
                                                    <input type="checkbox" class="task-checkbox" data-id="<?= $t['id'] ?>" <?= $t['is_completed'] ? 'checked disabled' : '' ?>>
                                                </div>
                                                <div class="task-details">
                                                    <div class="task-title-text"><?= esc($t['title']) ?></div>
                                                    <div class="task-desc-text"><?= esc($t['description']) ?></div>
                                                    <div class="task-meta-row">
                                                        <span class="task-channel <?= esc($t['channel']) ?>"><?= esc($t['channel']) ?></span>
                                                        <span class="task-meta-item <?= strtotime($t['due_date']) < time() && !$t['is_completed'] ? 'overdue' : '' ?>">
                                                            <i class="fas fa-calendar-alt"></i> H&#7841;n: <?= date('d/m/Y', strtotime($t['due_date'])) ?>
                                                        </span>
                                                        <?php if ($t['is_completed']): ?>
                                                            <span class="task-meta-item text-success">
                                                                <i class="fas fa-check-circle"></i> &#272;&#227; ho&#224;n th&#224;nh l&#250;c <?= date('H:i d/m/Y', strtotime($t['completed_at'])) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php
                        $p3 = array_filter($plans, function($x) { return $x['phase'] === 'phase3'; });
                        $p3 = reset($p3);
                        $p3StatusClass = $p3 ? ($p3['status'] === 'completed' ? 'completed' : ($p3['status'] === 'in_progress' ? 'active' : '')) : '';
                        ?>
                        <div class="timeline-item <?= $p3StatusClass ?>">
                            <div class="timeline-dot">
                                <i class="fas <?= $p3 && $p3['status'] === 'completed' ? 'fa-check' : 'fa-circle' ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">
                                    <span>Giai &#273;o&#7841;n 3: K&#7871;t n&#7889;i &amp; Remarketing d&#224;i h&#7841;n (Tr&#234;n 30 ng&#224;y)</span>
                                    <?php if ($p3): ?>
                                        <span class="badge badge-light font-weight-600"><?= esc($p3['status'] === 'completed' ? 'Xong' : 'Đang làm') ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="timeline-desc">Remarketing gi&#7899;i thi&#7879;u c&#225;c d&#7883;ch v&#7909;/m&#7901;i &#432;u &#273;&#227;i m&#7899;i, duy tr&#236; t&#432;&#417;ng t&#225;c &#273;&#7883;nh k&#7923; v&#224; duy tr&#236; l&#242;ng trung th&#224;nh.</p>

                                <?php if ($p3): ?>
                                    <ul class="task-checklist">
                                        <?php foreach ($planTasks[$p3['id']] as $t): ?>
                                            <li class="task-item <?= $t['is_completed'] ? 'completed' : '' ?>">
                                                <div class="task-checkbox-wrapper">
                                                    <input type="checkbox" class="task-checkbox" data-id="<?= $t['id'] ?>" <?= $t['is_completed'] ? 'checked disabled' : '' ?>>
                                                </div>
                                                <div class="task-details">
                                                    <div class="task-title-text"><?= esc($t['title']) ?></div>
                                                    <div class="task-desc-text"><?= esc($t['description']) ?></div>
                                                    <div class="task-meta-row">
                                                        <span class="task-channel <?= esc($t['channel']) ?>"><?= esc($t['channel']) ?></span>
                                                        <span class="task-meta-item <?= strtotime($t['due_date']) < time() && !$t['is_completed'] ? 'overdue' : '' ?>">
                                                            <i class="fas fa-calendar-alt"></i> H&#7841;n: <?= date('d/m/Y', strtotime($t['due_date'])) ?>
                                                        </span>
                                                        <?php if ($t['is_completed']): ?>
                                                            <span class="task-meta-item text-success">
                                                                <i class="fas fa-check-circle"></i> &#272;&#227; ho&#224;n th&#224;nh l&#250;c <?= date('H:i d/m/Y', strtotime($t['completed_at'])) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <?php if ($loyalty): ?>
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; background: #ffffff;">
                    <div class="card-body p-4 text-center">
                        <h5 class="card-title font-weight-700 mb-4 text-left">Th&#7867; Kh&#225;ch H&#224;ng VIP</h5>

                        <div class="vip-card-wrapper">
                            <div class="vip-card-visual card-<?= esc($loyalty['loyalty_tier']) ?>">
                                <div class="vip-card-header">
                                    <span class="vip-card-logo">L.A.N ERP</span>
                                    <span class="vip-card-tier"><?= esc($loyalty['loyalty_tier']) ?></span>
                                </div>
                                <div class="vip-card-chip"></div>
                                <div class="vip-card-body">
                                    <div class="vip-card-number">**** **** **** <?= esc($customer['id']) ?></div>
                                </div>
                                <div class="vip-card-footer">
                                    <div class="vip-card-holder">
                                        CH&#7910; TH&#7866;
                                        <span><?= esc($customer['name']) ?></span>
                                    </div>
                                    <div class="vip-card-points">
                                        &#272;I&#7874;M T&#205;CH L&#360;Y
                                        <span><?= number_format($loyalty['points']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-lg">
                            <div class="text-left">
                                <div class="font-size-0.8 text-muted">M&#227; Gi&#7899;i Thi&#7879;u Kh&#225;ch</div>
                                <strong class="text-dark font-size-1.0"><?= esc($loyalty['referral_code']) ?></strong>
                            </div>
                            <button class="btn btn-outline-primary btn-copy-referral rounded-pill px-3 py-1 font-size-0.8" data-code="<?= esc($loyalty['referral_code']) ?>">
                                <i class="fas fa-copy"></i> Sao ch&#233;p
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h5 class="card-title font-weight-700 mb-3">Ng&#432;&#7901;i Ph&#7909; Tr&#225;ch CSKH</h5>
                    <form action="<?= base_url('customer-care/update-segment/' . $customer['id']) ?>" method="POST">
                        <?= csrf_field() ?>

                        <div class="form-group mb-3">
                            <label class="font-weight-600 font-size-0.85 text-muted mb-1">Nh&#226;n s&#7921; th&#7921;c hi&#7879;n ch&#259;m s&#243;c</label>
                            <select name="assigned_care_staff_id" class="form-control" style="border-radius: 10px;">
                                <option value="">-- Ch&#432;a giao vi&#7879;c ch&#259;m s&#243;c --</option>
                                <?php foreach ($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>" <?= $customer['assigned_care_staff_id'] == $e['id'] ? 'selected' : '' ?>>
                                        <?= esc($e['full_name']) ?> (<?= esc($e['position']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-600 font-size-0.85 text-muted mb-1">Ph&#226;n nh&#243;m kh&#225;ch h&#224;ng (A/B/C)</label>
                            <select name="customer_segment" class="form-control" style="border-radius: 10px;">
                                <option value="potential" <?= $customer['customer_segment'] === 'potential' ? 'selected' : '' ?>>Nh&#243;m C - Ti&#7873;m n&#259;ng</option>
                                <option value="regular" <?= $customer['customer_segment'] === 'regular' ? 'selected' : '' ?>>Nh&#243;m B - Ph&#7893; th&#244;ng</option>
                                <option value="vip" <?= $customer['customer_segment'] === 'vip' ? 'selected' : '' ?>>Nh&#243;m A - VIP (Doanh nghi&#7879;p l&#7899;n / Gi&#225; tr&#7883; cao)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block rounded-pill">
                            L&#432;u c&#7845;u h&#236;nh
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-task-notes" id="taskNoteModal">
    <div class="modal-task-notes-content">
        <h6 class="font-weight-700 mb-2"><i class="fas fa-clipboard-check text-success"></i> Ghi Nh&#7853;n Nh&#7853;t K&#253; Ch&#259;m S&#243;c</h6>
        <p class="font-size-0.78 text-muted mb-3">Nh&#7853;p t&#243;m t&#7855;t ph&#7843;n h&#7891;i t&#7915; kh&#225;ch h&#224;ng ho&#7863;c ghi ch&#250; k&#7871;t qu&#7843; ch&#259;m s&#243;c &#273;&#7875; c&#225;c nh&#226;n vi&#234;n sau ti&#7879;n theo d&#245;i.</p>

        <div class="form-group mb-4">
            <textarea id="taskNotesInput" class="form-control font-size-0.85" rows="4" placeholder="V&#237; d&#7909;: Kh&#225;ch h&#224;ng ph&#7843;n h&#7891;i r&#7845;t t&#7889;t v&#7873; th&#225;i &#273;&#7897; ph&#7909;c v&#7909; c&#7911;a lu&#7853;t s&#432;, c&#243; ti&#7873;m n&#259;ng t&#225;i k&#253; h&#7907;p &#273;&#7891;ng m&#7899;i..." style="border-radius: 8px;"></textarea>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <button id="btnCancelTaskNote" class="btn btn-sm btn-light px-3 rounded-pill">H&#7911;y</button>
            <button id="btnSkipTaskNote" class="btn btn-sm btn-outline-secondary px-3 rounded-pill">B&#7887; qua ghi ch&#250;</button>
            <button id="btnConfirmTaskNote" class="btn btn-sm btn-success px-4 rounded-pill">X&#225;c nh&#7853;n xong</button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/customer_care.js') ?>"></script>
<?= $this->endSection() ?>
