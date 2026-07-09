<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/payroll.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="payroll-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Quản lý Bảng lương</h2>
            <p class="content-subtitle">Tháng <?= $month ?></p>
        </div>
        </div>
    <div class="stats-grid-premium m-b-20">
        <div class="stat-card-premium">
            <div class="stat-icon-wrapper stat-icon-blue">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <div class="stat-label">Ngày công chuẩn</div>
                <div class="stat-value"><?= $config['total_standard_days'] ?></div>
            </div>
        </div>
        <div class="stat-card-premium">
            <div class="stat-icon-wrapper stat-icon-green">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div>
                <div class="stat-label">Tổng quỹ lương thực lĩnh</div>
                <div class="stat-value">
                    <?php
                    $total = array_sum(array_column($payrolls, 'net_salary'));
                    echo number_format($total) . ' đ';
                    ?>
                </div>
            </div>
        </div>
        <div class="stat-card-premium">
            <div class="stat-icon-wrapper stat-icon-orange">
                <i class="fas fa-user-clock"></i>
            </div>
            <div>
                <div class="stat-label">Trạng thái chốt sổ</div>
                <div class="stat-value"><?= $config['is_closed'] ? '<span class="text-green">Đã chốt</span>' : '<span class="text-orange">Chưa chốt</span>' ?></div>
            </div>
        </div>
    </div>
    <div class="header-controls filter-bar" style="justify-content: flex-end">
            <form action="<?= base_url('payroll') ?>" method="get" class="d-flex gap-10 align-items-center">
                <select name="department_id" class="form-control-premium" onchange="this.form.submit()" style="max-width: 200px;">
                    <option value="">-- Tất cả phòng ban --</option>
                    <?php foreach ($departments ?? [] as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= (isset($department_id) && $department_id == $dept['id']) ? 'selected' : '' ?>><?= esc($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="employee_id" class="form-control-premium" onchange="this.form.submit()" style="max-width: 200px;">
                    <option value="">-- Tất cả nhân sự --</option>
                    <?php foreach ($employees ?? [] as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= (isset($employee_id) && $employee_id == $emp['id']) ? 'selected' : '' ?>><?= esc($emp['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="month" name="month" value="<?= $month ?>" class="form-control-premium" onchange="this.form.submit()">
            </form>
            <a href="<?= base_url('payroll/config/' . $month) ?>" class="btn-secondary">
                <i class="fas fa-calendar-alt"></i> Cấu hình ngày công
            </a>
            <form action="<?= base_url('payroll/calculate/' . $month) ?>" method="post" id="form-calculate" class="d-inline">
                <button type="submit" class="btn-premium">
                    <i class="fas fa-calculator"></i> Tính toán lương
                </button>
            </form>
            <a href="<?= base_url('payroll/export/' . $month) ?>" class="btn-secondary">
                <i class="fas fa-file-export"></i> Xuất file
            </a>
        </div>
    <?php 
        $holidays = json_decode($config['holidays_json'] ?: '{}', true);
        if (!empty($holidays)) { ?>
        <div class="premium-card m-b-20" style="padding: 15px;">
            <div class="d-flex align-items-center gap-10 m-b-10">
                <i class="fas fa-info-circle text-blue"></i>
                <strong class="text-xs uppercase text-muted-dark">Ghi chú tháng này:</strong>
            </div>
            <div class="d-flex flex-wrap gap-10">
                <?php foreach ($holidays as $date => $reason) { ?>
                    <span class="badge badge-info-minimal" title="<?= $date ?>">
                        <?= date('d/m', strtotime($date)) ?>: <?= esc($reason) ?>
                    </span>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

    <div class="premium-card premium-card-full">
        <div class="table-responsive">
            <table class="premium-table payroll-table-wide">
                <thead>
                    <tr>
                        <th rowspan="2"><input type="checkbox" id="checkAll"></th>
                        <th rowspan="2" class="col-name">Họ và tên</th>
                        <th rowspan="2">Lương đóng BH</th>
                        <th rowspan="2">Lương tháng</th>
                        <th rowspan="2">Ngày công chuẩn</th>
                        <th rowspan="2">Lương 1 ngày công</th>
                        <th colspan="3">Lương theo ngày công làm việc</th>
                        <th rowspan="2" class="col-allowance-diligence">Phụ cấp chuyên cần</th>
                        <th rowspan="2" class="col-allowance-petrol">Phụ cấp xăng xe</th>
                        <th rowspan="2" class="col-salary-kpi">Lương trách nhiệm</th>
                        <th rowspan="2" class="col-salary-other">Khác</th>
                        <th rowspan="2">Tổng lương</th>
                        <th colspan="5">Các khoản giảm trừ</th>
                        <th rowspan="2" class="col-net-salary">Lương thực lĩnh</th>
                    </tr>
                    <tr>
                        <th>Số công</th>
                        <th>Ngày bù</th>
                        <th>Thu nhập chịu thuế</th>
                        <th>BHXH vào CP (21.5%)</th>
                        <th>BHXH trừ lương (10.5%)</th>
                        <th>Giảm trừ phụ thuộc</th>
                        <th class="col-pit-tax">Thuế TNCN</th>
                        <th>Tổng cộng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payrolls)) { ?>
                        <tr><td colspan="20" class="text-center p-20">Chưa có dữ liệu bảng lương tháng này. Vui lòng bấm "Tính toán lương".</td></tr>
                    <?php } else { ?>
                        <?php foreach ($payrolls as $p) { ?>
                            <tr>
                                <td class="text-center"><input type="checkbox" class="emp-checkbox" value="<?= $p['employee_id'] ?>"></td>
                                <td class="employee-cell" data-id="<?= $p['id'] ?>" style="cursor: pointer;" title="Click để xem/sửa ghi chú">
                                     <strong><a href="javascript:void(0)" style="color: inherit; text-decoration: none; border-bottom: 1px dashed #ccc;"><?= esc($p['full_name']) ?></a></strong>
                                     <?php
                                         $snapshot = (float)($p['probation_rate_snapshot'] ?? 100);
                                         if ($snapshot < 100) {
                                             echo '<span class="probation-badge" title="Hệ số lương thử việc/thực tập">' . $snapshot . '%</span>';
                                         }
                                     ?><br>
                                     <small class="text-muted"><?= esc($p['dept_name']) ?></small>
                                     
                                     <div class="notes-display-wrapper mt-1" id="notes-display-<?= $p['id'] ?>">
                                         <?php 
                                             $notes = json_decode($p['notes_json'] ?? '[]', true);
                                             if (!empty($notes)) {
                                                 foreach ($notes as $n) {
                                                     echo '<div class="text-xs text-muted" style="font-style: italic; margin-top: 2px;"><i class="fas fa-level-up-alt fa-rotate-90 text-blue" style="margin-right: 4px;"></i>' . esc($n['text']) . '</div>';
                                                 }
                                             }
                                         ?>
                                     </div>
                                     <textarea class="raw-notes-data" id="raw-notes-<?= $p['id'] ?>" style="display:none;"><?= esc($p['notes_json'] ?? '[]') ?></textarea>
                                </td>
                                <td class="text-right"><?= number_format($p['insurance_salary'] ?? 0) ?></td>
                                <td class="text-right"><?= number_format($p['salary_base'] ?? 0) ?></td>
                                <td class="text-center"><?= $p['total_standard_days'] ?? 26 ?></td>
                                <td class="text-right"><?= number_format($p['salary_per_day'] ?? 0, 0) ?></td>
                                <?php
                                     $actualWD = (float)($p['actual_working_days'] ?? 0);
                                     $adjustWD = (float)($p['manual_adjust_days'] ?? 0);
                                     $totalWD  = $actualWD + $adjustWD;
                                 ?>
                                <td class="text-center" id="td-total-wd-<?= $p['id'] ?>" title="Chấm công: <?= $actualWD ?> ngày<?= $adjustWD > 0 ? ' + Bù thủ công: ' . $adjustWD . ' ngày' : '' ?>">
                                     <span id="total-wd-val-<?= $p['id'] ?>"><?= $totalWD ?></span><?= $adjustWD > 0 ? '<sup class="adjust-days-sup" id="adjust-wd-sup-' . $p['id'] . '">+' . $adjustWD . '</sup>' : '<sup class="adjust-days-sup" id="adjust-wd-sup-' . $p['id'] . '" style="display:none;"></sup>' ?>
                                </td>
                                <td class="text-center">
                                     <?php if (!$config['is_closed']) { ?>
                                         <input type="number" class="form-control-minimal edit-payroll-item"
                                                data-id="<?= $p['id'] ?>"
                                                data-field="manual_adjust_days"
                                                value="<?= (float)($p['manual_adjust_days'] ?? 0) ?>"
                                                min="0" max="31" step="0.5"
                                                title="Nhập số ngày công cần bù thêm">
                                     <?php } else { ?>
                                         <?= (float)($p['manual_adjust_days'] ?? 0) ?>
                                     <?php } ?>
                                </td>
                                <td class="text-right"><span id="taxable-income-<?= $p['id'] ?>"><?= number_format($p['taxable_income'] ?? 0) ?></span></td>
                                <td class="text-right">
                                    <?php if (!$config['is_closed']) { ?>
                                        <input type="text" class="form-control-minimal edit-payroll-item format-vnd" 
                                               data-id="<?= $p['id'] ?>" data-field="diligence_allowance" 
                                               value="<?= number_format($p['diligence_allowance'] ?? 0) ?>" 
                                               style="width: 75px; text-align: right;">
                                    <?php } else { ?>
                                        <?= number_format($p['diligence_allowance'] ?? 0) ?>
                                    <?php } ?>
                                </td>
                                <td class="text-right">
                                    <?php if (!$config['is_closed']) { ?>
                                        <input type="text" class="form-control-minimal edit-payroll-item format-vnd" 
                                               data-id="<?= $p['id'] ?>" data-field="petrol_allowance" 
                                               value="<?= number_format($p['petrol_allowance'] ?? 0) ?>" 
                                               style="width: 75px; text-align: right;">
                                    <?php } else { ?>
                                        <?= number_format($p['petrol_allowance'] ?? 0) ?>
                                    <?php } ?>
                                </td>
                                <td class="text-right">
                                    <?php if (!$config['is_closed']) { ?>
                                        <input type="text" class="form-control-minimal edit-payroll-item format-vnd" 
                                               data-id="<?= $p['id'] ?>" data-field="salary_kpi" 
                                               value="<?= number_format($p['salary_kpi'] ?? 0) ?>" 
                                               style="width: 75px; text-align: right;">
                                    <?php } else { ?>
                                        <?= number_format($p['salary_kpi'] ?? 0) ?>
                                    <?php } ?>
                                </td>
                                <td class="text-right">
                                    <?php if (!$config['is_closed']) { ?>
                                        <input type="text" class="form-control-minimal edit-payroll-item format-vnd" 
                                               data-id="<?= $p['id'] ?>" data-field="salary_bonus" 
                                               value="<?= number_format(($p['salary_bonus'] ?? 0) + ($p['salary_other'] ?? 0)) ?>" 
                                               style="width: 75px; text-align: right;">
                                    <?php } else { ?>
                                        <?= number_format(($p['salary_bonus'] ?? 0) + ($p['salary_other'] ?? 0)) ?>
                                    <?php } ?>
                                    <input type="hidden" data-field="salary_other" value="0">
                                </td>
                                <td class="text-right"><strong><span id="total-gross-<?= $p['id'] ?>"><?= number_format(($p['taxable_income'] ?? 0) + ($p['diligence_allowance'] ?? 0) + ($p['petrol_allowance'] ?? 0) + ($p['salary_kpi'] ?? 0) + ($p['salary_bonus'] ?? 0) + ($p['salary_other'] ?? 0)) ?></span></strong></td>
                                
                                <td class="text-right text-muted"><?= number_format($p['si_employer'] ?? 0) ?></td>
                                <td class="text-right text-red"><?= number_format($p['si_employee'] ?? 0) ?></td>
                                <td class="text-right"><?= number_format($p['dependent_deduction'] ?? 0) ?></td>
                                <td class="text-right">
                                    <?php if (!$config['is_closed']) { ?>
                                        <input type="text" class="form-control-minimal edit-payroll-item format-vnd" 
                                               data-id="<?= $p['id'] ?>" data-field="pit_tax" 
                                               value="<?= number_format($p['pit_tax'] ?? 0) ?>" 
                                               style="width: 80px; text-align: right; color: #ff3b30;">
                                    <?php } else { ?>
                                        <?= number_format($p['pit_tax'] ?? 0) ?>
                                    <?php } ?>
                                </td>
                                <td class="text-right text-red"><strong><span id="deduct-<?= $p['id'] ?>"><?= number_format($p['total_deductions'] ?? 0) ?></span></strong></td>

                                <td class="text-right"><strong><span id="net-<?= $p['id'] ?>"><?= number_format($p['net_salary'] ?? 0) ?></span> đ</strong></td>
                            </tr>
                            <!-- Drop-down row for editing notes -->
                            <tr class="notes-edit-row" id="notes-edit-row-<?= $p['id'] ?>" style="display: none; background-color: #f8f9fa;">
                                <td colspan="19" style="padding: 12px 15px; border-bottom: 2px solid #e5e5ea; border-left: 3px solid var(--apple-blue);">
                                    <div class="d-flex">
                                        <div style="min-width: 150px; font-weight: 600; font-size: 13px; color: #555; padding-top: 6px;">
                                            <i class="fas fa-comment-dots text-blue mr-1"></i> Ghi chú:
                                        </div>
                                        <div class="flex-grow-1 notes-editor-container" data-id="<?= $p['id'] ?>">
                                            <div class="notes-inputs-list">
                                                <?php 
                                                    if (!empty($notes)) {
                                                        foreach ($notes as $idx => $n) {
                                                ?>
                                                        <div class="d-flex align-items-center gap-10 mb-2 note-input-item">
                                                            <input type="text" class="form-control form-control-sm note-text-input" value="<?= esc($n['text']) ?>" style="max-width: 500px;" <?= $config['is_closed'] ? 'disabled' : '' ?>>
                                                            <?php if (!$config['is_closed']) { ?>
                                                                <button type="button" class="btn btn-sm btn-light btn-remove-note-input" title="Xóa" style="color: #ff3b30;"><i class="fas fa-trash"></i></button>
                                                            <?php } ?>
                                                        </div>
                                                <?php 
                                                        }
                                                    } else {
                                                ?>
                                                        <div class="d-flex align-items-center gap-10 mb-2 note-input-item">
                                                            <input type="text" class="form-control form-control-sm note-text-input" placeholder="Nhập nội dung ghi chú..." style="max-width: 500px;" <?= $config['is_closed'] ? 'disabled' : '' ?>>
                                                            <?php if (!$config['is_closed']) { ?>
                                                                <button type="button" class="btn btn-sm btn-light btn-remove-note-input" title="Xóa" style="display:none; color: #ff3b30;"><i class="fas fa-trash"></i></button>
                                                            <?php } ?>
                                                        </div>
                                                <?php } ?>
                                            </div>
                                            <?php if (!$config['is_closed']) { ?>
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-sm btn-primary btn-save-notes"><i class="fas fa-save"></i> Lưu ghi chú</button>
                                                    <button type="button" class="btn btn-sm btn-light btn-add-note-input ml-2"><i class="fas fa-plus"></i> Thêm nội dung khác</button>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
                <?php if (!empty($payrolls)) { ?>
                <tfoot>
                    <tr style="background-color: #f5f5f7; font-weight: bold; border-top: 2px solid #d2d2d7;">
                        <td colspan="2" class="text-center text-uppercase">TỔNG CỘNG</td>
                        <td class="text-right" id="footer-insurance-salary"><?= number_format(array_sum(array_column($payrolls, 'insurance_salary'))) ?></td>
                        <td class="text-right" id="footer-salary-base"><?= number_format(array_sum(array_column($payrolls, 'salary_base'))) ?></td>
                        <td class="text-center"></td>
                        <td class="text-right"></td>
                        <td class="text-center" id="footer-total-working-days"><?= array_sum(array_column($payrolls, 'actual_working_days')) + array_sum(array_column($payrolls, 'manual_adjust_days')) ?></td>
                        <td class="text-center"></td><!-- Ngày bù: không cộng tổng -->
                        <td class="text-right" id="footer-taxable-income"><?= number_format(array_sum(array_column($payrolls, 'taxable_income'))) ?></td>
                        <td class="text-right" id="footer-diligence-allowance"><?= number_format(array_sum(array_column($payrolls, 'diligence_allowance'))) ?></td>
                        <td class="text-right" id="footer-petrol-allowance"><?= number_format(array_sum(array_column($payrolls, 'petrol_allowance'))) ?></td>
                        <td class="text-right" id="footer-salary-kpi"><?= number_format(array_sum(array_column($payrolls, 'salary_kpi'))) ?></td>
                        <td class="text-right" id="footer-salary-bonus">
                            <?php 
                                $totalBonus = array_sum(array_column($payrolls, 'salary_bonus')) + array_sum(array_column($payrolls, 'salary_other'));
                                echo number_format($totalBonus);
                            ?>
                        </td>
                        <td class="text-right" id="footer-total-gross">
                            <?php
                                $totalGross = array_sum(array_column($payrolls, 'taxable_income')) +
                                              array_sum(array_column($payrolls, 'diligence_allowance')) +
                                              array_sum(array_column($payrolls, 'petrol_allowance')) +
                                              array_sum(array_column($payrolls, 'salary_kpi')) +
                                              $totalBonus;
                                echo number_format($totalGross);
                            ?>
                        </td>
                        <td class="text-right text-muted" id="footer-si-employer"><?= number_format(array_sum(array_column($payrolls, 'si_employer'))) ?></td>
                        <td class="text-right text-red" id="footer-si-employee"><?= number_format(array_sum(array_column($payrolls, 'si_employee'))) ?></td>
                        <td class="text-right" id="footer-dependent-deduction"><?= number_format(array_sum(array_column($payrolls, 'dependent_deduction'))) ?></td>
                        <td class="text-right text-red" id="footer-pit-tax"><?= number_format(array_sum(array_column($payrolls, 'pit_tax'))) ?></td>
                        <td class="text-right text-red" id="footer-total-deductions"><?= number_format(array_sum(array_column($payrolls, 'total_deductions'))) ?></td>
                        <td class="text-right text-green" id="footer-net-salary" style="font-size: 14px;"><?= number_format(array_sum(array_column($payrolls, 'net_salary'))) ?> đ</td>
                    </tr>
                </tfoot>
                <?php } ?>
            </table>
        </div>
        
        <?php if (isset($pager)) { ?>
            <div class="m-t-20">
                <?= $pager->links() ?>
            </div>
        <?php } ?>
    </div>
    
    <?php if (!$config['is_closed'] && !empty($payrolls)) { ?>
        <div class="m-t-20 text-right">
            <a href="<?= base_url('payroll/close/' . $month) ?>" class="btn-blue-apple" onclick="return confirm('Bạn có chắc chắn muốn chốt sổ tháng này? Sau khi chốt sẽ không thể chỉnh sửa.')">
                <i class="fas fa-lock"></i> Chốt sổ tháng <?= $month ?>
            </a>
        </div>
    <?php } ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/payroll.js') ?>"></script>
<?= $this->endSection() ?>
