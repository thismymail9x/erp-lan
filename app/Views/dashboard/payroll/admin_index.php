<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="payroll-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Quản lý Bảng lương</h2>
            <p class="content-subtitle">Tháng <?= $month ?></p>
        </div>
        <div class="header-controls">
            <form action="<?= base_url('payroll') ?>" method="get" class="d-flex gap-10">
                <input type="month" name="month" value="<?= $month ?>" class="form-control-premium" onchange="this.form.submit()">
            </form>
            <a href="<?= base_url('payroll/config/' . $month) ?>" class="btn-secondary">
                <i class="fas fa-calendar-alt"></i> Cấu hình ngày công
            </a>
            <a href="<?= base_url('payroll/calculate/' . $month) ?>" class="btn-premium">
                <i class="fas fa-calculator"></i> Tính toán lương
            </a>
            <a href="<?= base_url('payroll/export/' . $month) ?>" class="btn-secondary">
                <i class="fas fa-file-export"></i> Xuất file
            </a>
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
                        <th rowspan="2">STT</th>
                        <th rowspan="2">Họ và tên</th>
                        <th rowspan="2">Chức vụ</th>
                        <th rowspan="2">Lương đóng BH</th>
                        <th rowspan="2">Lương tháng</th>
                        <th rowspan="2">Ngày công chuẩn</th>
                        <th rowspan="2">Lương 1 ngày công</th>
                        <th colspan="3">Lương theo ngày công làm việc</th>
                        <th rowspan="2">Phụ cấp chuyên cần</th>
                        <th rowspan="2">Phụ cấp xăng xe</th>
                        <th rowspan="2">Lương trách nhiệm</th>
                        <th rowspan="2">Khác</th>
                        <th rowspan="2">Tổng lương</th>
                        <th colspan="5">Các khoản giảm trừ</th>
                        <th rowspan="2">Lương thực lĩnh</th>
                        <th rowspan="2">Ký nhận</th>
                    </tr>
                    <tr>
                        <th>Số công</th>
                        <th>Số tiền (TNCT)</th>
                        <th>Vi phạm</th>
                        <th>BHXH vào CP (21.5%)</th>
                        <th>BHXH trừ lương (10.5%)</th>
                        <th>Giảm trừ phụ thuộc</th>
                        <th>Thuế TNCN</th>
                        <th>Tổng cộng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payrolls)) { ?>
                        <tr><td colspan="20" class="text-center p-20">Chưa có dữ liệu bảng lương tháng này. Vui lòng bấm "Tính toán lương".</td></tr>
                    <?php } else { ?>
                        <?php $stt = 1; foreach ($payrolls as $p) { ?>
                            <tr>
                                <td class="text-center"><?= $stt++ ?></td>
                                <td class="employee-cell" data-id="<?= $p['id'] ?>" style="cursor: pointer;" title="Click để xem/sửa ghi chú">
                                    <strong><a href="javascript:void(0)" style="color: inherit; text-decoration: none; border-bottom: 1px dashed #ccc;"><?= esc($p['full_name']) ?></a></strong><br>
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
                                <td><?= esc($p['position'] ?? 'Nhân viên') ?></td>
                                <td class="text-right"><?= number_format($p['insurance_salary'] ?? 0) ?></td>
                                <td class="text-right"><?= number_format($p['salary_base'] ?? 0) ?></td>
                                <td class="text-center"><?= $p['total_standard_days'] ?? 26 ?></td>
                                <td class="text-right"><?= number_format($p['salary_per_day'] ?? 0, 0) ?></td>
                                <td class="text-center"><?= $p['actual_working_days'] ?? 0 ?></td>
                                <td class="text-right"><?= number_format($p['taxable_income'] ?? 0) ?></td>
                                <td class="text-center text-red">
                                    <small><?= $p['attendance_violations'] ?? 0 ?> lượt</small>
                                </td>
                                <td class="text-right">
                                    <?php if (!$config['is_closed']) { ?>
                                        <input type="text" class="form-control-minimal edit-payroll-item format-vnd" 
                                               data-id="<?= $p['id'] ?>" data-field="diligence_allowance" 
                                               value="<?= number_format($p['diligence_allowance'] ?? 0) ?>" 
                                               style="width: 85px; text-align: right;">
                                    <?php } else { ?>
                                        <?= number_format($p['diligence_allowance'] ?? 0) ?>
                                    <?php } ?>
                                </td>
                                <td class="text-right">
                                    <?php if (!$config['is_closed']) { ?>
                                        <input type="text" class="form-control-minimal edit-payroll-item format-vnd" 
                                               data-id="<?= $p['id'] ?>" data-field="petrol_allowance" 
                                               value="<?= number_format($p['petrol_allowance'] ?? 0) ?>" 
                                               style="width: 85px; text-align: right;">
                                    <?php } else { ?>
                                        <?= number_format($p['petrol_allowance'] ?? 0) ?>
                                    <?php } ?>
                                </td>
                                <td class="text-right">
                                    <?php if (!$config['is_closed']) { ?>
                                        <input type="text" class="form-control-minimal edit-payroll-item format-vnd" 
                                               data-id="<?= $p['id'] ?>" data-field="salary_kpi" 
                                               value="<?= number_format($p['salary_kpi'] ?? 0) ?>" 
                                               style="width: 85px; text-align: right;">
                                    <?php } else { ?>
                                        <?= number_format($p['salary_kpi'] ?? 0) ?>
                                    <?php } ?>
                                </td>
                                <td class="text-right">
                                    <?php if (!$config['is_closed']) { ?>
                                        <input type="text" class="form-control-minimal edit-payroll-item format-vnd" 
                                               data-id="<?= $p['id'] ?>" data-field="salary_bonus" 
                                               value="<?= number_format(($p['salary_bonus'] ?? 0) + ($p['salary_other'] ?? 0)) ?>" 
                                               style="width: 85px; text-align: right;">
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
                                               style="width: 90px; text-align: right; color: #ff3b30;">
                                    <?php } else { ?>
                                        <?= number_format($p['pit_tax'] ?? 0) ?>
                                    <?php } ?>
                                </td>
                                <td class="text-right text-red"><strong><span id="deduct-<?= $p['id'] ?>"><?= number_format($p['total_deductions'] ?? 0) ?></span></strong></td>

                                <td class="text-right"><strong><span id="net-<?= $p['id'] ?>"><?= number_format($p['net_salary'] ?? 0) ?></span> đ</strong></td>
                                <td class="text-center">
                                    <?php if ($p['status'] === 'paid') { ?>
                                        <i class="fas fa-check-circle text-green"></i>
                                    <?php } ?>
                                </td>
                            </tr>
                            <!-- Drop-down row for editing notes -->
                            <tr class="notes-edit-row" id="notes-edit-row-<?= $p['id'] ?>" style="display: none; background-color: #f8f9fa;">
                                <td colspan="10" style="padding: 12px 15px; border-bottom: 2px solid #e5e5ea; border-left: 3px solid var(--apple-blue);">
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

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Hàm định dạng số có dấu phẩy
    function formatNumber(n) {
        let isNegative = String(n).trim().startsWith('-');
        let numberStr = String(n).replace(/\D/g, "");
        if (!numberStr) return isNegative ? '-' : '';
        return (isNegative ? '-' : '') + numberStr.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Sự kiện khi gõ vào các ô có class format-vnd
    $('.format-vnd').on('input', function() {
        var selection = window.getSelection().toString();
        if (selection !== '') return;
        var $this = $(this);
        var input = $this.val();
        var formatted = formatNumber(input);
        $this.val(formatted);
    });

    // Sự kiện khi thay đổi giá trị (Blur hoặc Enter)
    $('.edit-payroll-item').on('change', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        
        const kpiRaw = row.find('[data-field="salary_kpi"]').val() || "0";
        const bonusRaw = row.find('[data-field="salary_bonus"]').val() || "0";
        const otherRaw = row.find('[data-field="salary_other"]').val() || "0";
        const pitRaw = row.find('[data-field="pit_tax"]').val() || "0";
        const petrolRaw = row.find('[data-field="petrol_allowance"]').val() || "0";
        const diligenceRaw = row.find('[data-field="diligence_allowance"]').val() || "0";
        
        $.post('<?= base_url('payroll/update-item/') ?>' + id, {
            salary_kpi: kpiRaw.replace(/,/g, ''),
            salary_bonus: bonusRaw.replace(/,/g, ''),
            salary_other: otherRaw.replace(/,/g, ''),
            pit_tax: pitRaw.replace(/,/g, ''),
            petrol_allowance: petrolRaw.replace(/,/g, ''),
            diligence_allowance: diligenceRaw.replace(/,/g, ''),
            salary_deduction: 0, 
            notes: ''
        }, function(resp) {
            if (resp.code === 0) {
                $('#net-' + id).text(resp.net_salary);
                if (resp.total_deductions) {
                    $('#deduct-' + id).text(formatNumber(resp.total_deductions));
                }
                if (resp.total_gross) {
                    $('#total-gross-' + id).text(formatNumber(resp.total_gross));
                }
                $('#net-' + id).css('color', '#34c759').delay(500).queue(function(next){
                    $(this).css('color', '');
                    next();
                });
            } else {
                alert(resp.error);
            }
        });
    });

    // === XỬ LÝ GHI CHÚ DROP-DOWN ===
    $(document).on('click', '.employee-cell', function(e) {
        if ($(e.target).closest('input, button, a.btn').length > 0) return;
        const id = $(this).data('id');
        $('#notes-edit-row-' + id).fadeToggle(150);
    });

    $(document).on('click', '.btn-add-note-input', function() {
        const $container = $(this).closest('.notes-editor-container');
        const $list = $container.find('.notes-inputs-list');
        const newHtml = `
            <div class="d-flex align-items-center gap-10 mb-2 note-input-item" style="display:none;">
                <input type="text" class="form-control form-control-sm note-text-input" placeholder="Nhập nội dung ghi chú..." style="max-width: 500px;">
                <button type="button" class="btn btn-sm btn-light btn-remove-note-input" title="Xóa" style="color: #ff3b30;"><i class="fas fa-trash"></i></button>
            </div>
        `;
        const $newEl = $(newHtml);
        $list.append($newEl);
        $newEl.fadeIn(150);
        $list.find('.btn-remove-note-input').show();
        $newEl.find('input').focus();
    });

    $(document).on('click', '.btn-remove-note-input', function() {
        const $list = $(this).closest('.notes-inputs-list');
        $(this).closest('.note-input-item').fadeOut(150, function() {
            $(this).remove();
            if ($list.find('.note-input-item').length === 1) {
                const $remainingInput = $list.find('.note-text-input');
                if ($remainingInput.val().trim() === '') {
                    $list.find('.btn-remove-note-input').hide();
                }
            } else if ($list.find('.note-input-item').length === 0) {
                $list.closest('.notes-editor-container').find('.btn-add-note-input').trigger('click');
            }
        });
    });

    function updateNotesDisplay(id, notes) {
        let html = '';
        notes.forEach(n => {
            html += `<div class="text-xs text-muted" style="font-style: italic; margin-top: 2px;">
                        <i class="fas fa-level-up-alt fa-rotate-90 text-blue" style="margin-right: 4px;"></i>${n.text}
                     </div>`;
        });
        $('#notes-display-' + id).html(html);
    }

    $(document).on('click', '.btn-save-notes', function() {
        const $btn = $(this);
        const $container = $btn.closest('.notes-editor-container');
        const id = $container.data('id');
        let notes = [];
        const now = new Date();
        const dateStr = now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});
        
        $container.find('.note-text-input').each(function() {
            const text = $(this).val().trim();
            if (text) {
                notes.push({ id: Date.now() + Math.random(), text: text, date: dateStr });
            }
        });
        
        const notesJsonStr = JSON.stringify(notes);
        const origHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...').prop('disabled', true);
        
        $.post('<?= base_url('payroll/save-notes/') ?>' + id, {
            notes_json: notesJsonStr
        }, function(res) {
            $btn.html(origHtml).prop('disabled', false);
            if (res.code === 0) {
                $('#raw-notes-' + id).val(notesJsonStr);
                updateNotesDisplay(id, notes);
                $('#notes-edit-row-' + id).fadeOut(200);
            } else {
                alert('Có lỗi xảy ra khi lưu ghi chú');
            }
        });
    });
});
</script>
<style>
.payroll-table-wide {
    min-width: 1500px; /* Đảm bảo bảng đủ rộng cho tất cả các cột */
}
.table-responsive {
    overflow-x: auto;
    border-radius: 12px;
}
.premium-table thead th {
    background-color: #f5f5f7;
    color: #1d1d1f;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
    border: 1px solid #d2d2d7;
}
.premium-table tbody td {
    vertical-align: middle;
    border: 1px solid #f5f5f7;
}
.text-red { color: #ff3b30 !important; }
.text-green { color: #34c759 !important; }
.form-control-minimal {
    border: 1px solid #d2d2d7;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 13px;
    outline: none;
    background-color: #fff;
}
.form-control-minimal:focus {
    border-color: var(--apple-blue);
    box-shadow: 0 0 0 2px rgba(0, 113, 227, 0.1);
}
</style>
<?= $this->endSection() ?>
<?= $this->endSection() ?>
