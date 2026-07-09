<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/payroll.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="payroll-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Bảng lương cá nhân</h2>
            <p class="content-subtitle">Tháng <?= $month ?></p>
        </div>
        <div class="header-controls">
            <form action="<?= base_url('payroll') ?>" method="get">
                <input type="month" name="month" value="<?= $month ?>" class="form-control-premium" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    <?php if (!$payroll) { ?>
        <div class="premium-card text-center">
            <i class="fas fa-info-circle text-blue" style="font-size: 2rem;"></i>
            <p class="m-t-15">Bảng lương tháng này chưa được phát hành hoặc đang trong quá trình xử lý.</p>
        </div>
    <?php } else { ?>
        <?php 
            $holidays = json_decode($config['holidays_json'] ?: '{}', true);
            if (!empty($holidays)) { ?>
            <div class="premium-card m-b-20" style="padding: 15px;">
                <div class="d-flex align-items-center gap-10 m-b-10">
                    <i class="fas fa-info-circle text-blue"></i>
                    <strong class="text-xs uppercase text-muted-dark">Ghi chú & Ngày nghỉ tháng này:</strong>
                </div>
                <div class="d-flex flex-wrap gap-10">
                    <?php foreach ($holidays as $date => $reason) { ?>
                        <span class="badge badge-info-minimal">
                            <?= date('d/m', strtotime($date)) ?>: <?= esc($reason) ?>
                        </span>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <div class="create-container">
            <div class="premium-card">
                <div class="d-flex justify-content-between m-b-20 border-bottom p-b-10">
                    <h3 class="m-0">Phiếu lương chi tiết</h3>
                    <span class="badge badge-info-minimal"><?= $payroll['status'] ?></span>
                </div>

                <div class="payroll-details">
                    <div class="row m-b-15">
                        <div class="col-6"><strong>Lương đóng BH:</strong></div>
                        <div class="col-6 text-right"><?= number_format($payroll['insurance_salary'] ?? 0) ?> đ</div>
                    </div>
                    <div class="row m-b-15">
                        <div class="col-6"><strong>Lương tháng:</strong></div>
                        <div class="col-6 text-right"><?= number_format($payroll['salary_base'] ?? 0) ?> đ</div>
                    </div>
                    <hr>
                    <div class="row m-b-15">
                        <div class="col-6"><strong>Ngày công chuẩn:</strong></div>
                        <div class="col-6 text-right"><?= $payroll['total_standard_days'] ?? 26 ?> ngày</div>
                    </div>
                    <div class="row m-b-15">
                        <div class="col-6"><strong>Lương 1 ngày công:</strong></div>
                        <div class="col-6 text-right">
                            <?= number_format($payroll['salary_per_day'] ?? 0) ?> đ
                            <?php
                                // Hiển thị hệ số lương khi nhân viên đang trong giai đoạn thử việc/thực tập
                                $rateSnap = (float)($payroll['probation_rate_snapshot'] ?? 100);
                                if ($rateSnap < 100) {
                                    echo '<span class="pv-probation-badge" title="Lương 1 ngày công đã tính theo hệ số ' . $rateSnap . '% lương cơ bản">' . $rateSnap . '% lương CB</span>';
                                }
                            ?>
                        </div>
                    </div>
                    <div class="row m-b-15">
                        <div class="col-6"><strong>Số công thực tế:</strong></div>
                        <div class="col-6 text-right">
                            <?php
                                $actualWD = (float)($payroll['actual_working_days'] ?? 0);
                                $adjustWD = (float)($payroll['manual_adjust_days'] ?? 0);
                                echo ($actualWD + $adjustWD) . ' ngày';
                            ?>
                        </div>
                    </div>
                    <?php if ((float)($payroll['manual_adjust_days'] ?? 0) > 0) { ?>
                    <div class="row m-b-15">
                        <div class="col-6 text-muted"><strong>Trong đó — Ngày công bù:</strong></div>
                        <div class="col-6 text-right text-green">+ <?= (float)$payroll['manual_adjust_days'] ?> ngày (điều chỉnh)</div>
                    </div>
                    <?php } ?>
                    <div class="row m-b-15">
                        <div class="col-6"><strong>Lương theo ngày công (TNCT):</strong></div>
                        <div class="col-6 text-right"><strong><?= number_format($payroll['taxable_income'] ?? 0) ?> đ</strong></div>
                    </div>
                    <hr>
                    <div class="row m-b-15">
                        <div class="col-6"><strong>Phụ cấp chuyên cần:</strong></div>
                        <div class="col-6 text-right text-green">+ <?= number_format($payroll['diligence_allowance'] ?? 0) ?> đ</div>
                    </div>
                    <div class="row m-b-15">
                        <div class="col-6"><strong>Phụ cấp xăng xe:</strong></div>
                        <div class="col-6 text-right text-green">+ <span id="petrol-display"><?= number_format($payroll['petrol_allowance'] ?? 0) ?></span> đ</div>
                    </div>
                    <div class="row m-b-15">
                        <div class="col-6"><strong>Lương KPI (Tháng):</strong></div>
                        <div class="col-6 text-right text-green">+ <?= number_format($payroll['salary_kpi'] ?? 0) ?> đ</div>
                    </div>
                    <?php if (($payroll['salary_bonus'] ?? 0) > 0) { ?>
                    <div class="row m-b-15">
                        <div class="col-6"><strong>Thưởng khác:</strong></div>
                        <div class="col-6 text-right text-blue">+ <?= number_format($payroll['salary_bonus'] ?? 0) ?> đ</div>
                    </div>
                    <?php } ?>
                    <hr>
                    <div class="row m-b-15 text-muted">
                        <div class="col-6"><strong>BHXH (10.5%):</strong></div>
                        <div class="col-6 text-right text-red">- <?= number_format($payroll['si_employee'] ?? 0) ?> đ</div>
                    </div>
                    <?php if (($payroll['dependent_deduction'] ?? 0) > 0) { ?>
                    <div class="row m-b-15 text-muted">
                        <div class="col-6"><strong>Giảm trừ phụ thuộc (Giảm thuế TNCN):</strong></div>
                        <div class="col-6 text-right text-blue"><?= number_format($payroll['dependent_deduction'] ?? 0) ?> đ</div>
                    </div>
                    <?php } ?>
                    <?php if (($payroll['pit_tax'] ?? 0) > 0) { ?>
                    <div class="row m-b-15 text-muted">
                        <div class="col-6"><strong>Thuế TNCN:</strong></div>
                        <div class="col-6 text-right text-red">- <?= number_format($payroll['pit_tax'] ?? 0) ?> đ</div>
                    </div>
                    <?php } ?>
                    <?php if (isset($payroll['salary_other']) && $payroll['salary_other'] != 0) { ?>
                    <div class="row m-b-15">
                        <div class="col-6"><strong>Điều chỉnh khác:</strong></div>
                        <div class="col-6 text-right <?= ($payroll['salary_other'] ?? 0) > 0 ? 'text-green' : 'text-red' ?>">
                            <?= ($payroll['salary_other'] ?? 0) > 0 ? '+' : '' ?> <?= number_format($payroll['salary_other'] ?? 0) ?> đ
                        </div>
                    </div>
                    <?php } ?>
                    <hr>
                    <div class="row" style="font-size: 1.5rem; color: var(--apple-blue);">
                        <div class="col-6"><strong>THỰC LĨNH:</strong></div>
                        <div class="col-6 text-right"><strong><span id="net-salary-display"><?= number_format($payroll['net_salary'] ?? 0) ?></span> đ</strong></div>
                    </div>

                    <div class="m-t-20 p-15" style="background: #f9f9fb; border-radius: 8px; border: 1px dashed #d2d2d7;">
                        <strong class="text-muted-dark d-flex justify-content-between align-items-center m-b-10">
                            <span><i class="far fa-sticky-note mr-2"></i> Chi phí phát sinh (Xăng xe, vé xe...):</span>
                            <?php if (!$config['is_closed']) { ?>
                                <button type="button" class="btn btn-sm btn-light btn-toggle-notes" style="font-weight: normal;"><i class="fas fa-edit text-blue"></i> Thêm / Sửa chi phí</button>
                            <?php } ?>
                        </strong>
                        
                        <div id="notes-display-<?= $payroll['id'] ?>" class="notes-display-area" style="padding-left: 20px;">
                            <?php 
                                $notes = json_decode($payroll['notes_json'] ?? '[]', true);
                                if (!empty($notes)) {
                                    echo '<ul class="m-0 pl-0 text-sm" style="list-style-type: disc;">';
                                    foreach ($notes as $n) {
                                        $noteDate = $n['date'] ?? '';
                                        $dateHtml = $noteDate !== '' ? '<span class="text-muted" style="font-size: 11px;">[' . esc($noteDate) . ']</span> ' : '';
                                        echo '<li class="m-b-5">' . $dateHtml . esc($n['text'] ?? '') . '</li>';
                                    }
                                    echo '</ul>';
                                } else {
                                    echo '<div class="text-muted text-sm italic">Chưa có chi phí phát sinh.</div>';
                                }
                            ?>
                        </div>

                        <div class="notes-editor-container m-t-15" data-id="<?= $payroll['id'] ?>" style="display: none; padding-top: 15px; border-top: 1px dashed #e5e5ea;">
                            <div class="form-group mb-3 d-flex align-items-center gap-10" style="padding-left: 20px;">
                                <label style="min-width: 150px; font-weight: 600;">Phụ cấp xăng xe (đ):</label>
                                <input type="text" class="form-control form-control-sm format-vnd petrol-allowance-input" 
                                       value="<?= number_format($payroll['petrol_allowance']) ?>" 
                                       style="max-width: 200px;" <?= $config['is_closed'] ? 'disabled' : '' ?>>
                                <small class="text-muted italic">* Nhập số tiền xăng xe phát sinh trong tháng</small>
                            </div>
                            
                            <div class="notes-inputs-list">
                                <?php 
                                    if (!empty($notes)) {
                                        foreach ($notes as $idx => $n) {
                                ?>
                                        <div class="d-flex align-items-center gap-10 mb-2 note-input-item">
                                            <?php $noteDate = $n['date'] ?? ''; ?>
                                            <span class="text-muted" style="font-size: 11px; min-width: 120px; text-align: right; <?= $noteDate === '' ? 'display: none;' : '' ?>"><?= $noteDate !== '' ? '[' . esc($noteDate) . ']' : '' ?></span> 
                                            <input type="text" class="form-control form-control-sm note-text-input" value="<?= esc($n['text'] ?? '') ?>" style="flex-grow: 1; max-width: 500px;" <?= $config['is_closed'] ? 'disabled' : '' ?>>
                                            <?php if (!$config['is_closed']) { ?>
                                                <button type="button" class="btn btn-sm btn-light btn-remove-note-input" title="Xóa" style="color: #ff3b30;"><i class="fas fa-trash"></i></button>
                                            <?php } ?>
                                        </div>
                                <?php 
                                        }
                                    } else {
                                ?>
                                        <div class="d-flex align-items-center gap-10 mb-2 note-input-item">
                                            <span class="text-muted date-placeholder" style="font-size: 11px; min-width: 120px; text-align: right; display: none;"></span> 
                                            <input type="text" class="form-control form-control-sm note-text-input" placeholder="Nhập nội dung chi phí..." style="flex-grow: 1; max-width: 500px;" <?= $config['is_closed'] ? 'disabled' : '' ?>>
                                            <?php if (!$config['is_closed']) { ?>
                                                <button type="button" class="btn btn-sm btn-light btn-remove-note-input" title="Xóa" style="display:none; color: #ff3b30;"><i class="fas fa-trash"></i></button>
                                            <?php } ?>
                                        </div>
                                <?php } ?>
                            </div>
                            <?php if (!$config['is_closed']) { ?>
                                <div class="mt-2 d-flex gap-10" style="padding-left: 130px;">
                                    <button type="button" class="btn btn-sm btn-primary btn-save-notes"><i class="fas fa-save"></i> Lưu chi phí</button>
                                    <button type="button" class="btn btn-sm btn-light btn-add-note-input"><i class="fas fa-plus"></i> Thêm dòng mới</button>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="m-t-20 text-muted-dark text-xs italic">
                    * Mọi thắc mắc về bảng lương vui lòng liên hệ bộ phận Hành chính trong vòng 3 ngày kể từ khi nhận phiếu.
                </div>
            </div>
        </div>
    <?php } ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/payroll.js') ?>"></script>
<?= $this->endSection() ?>
