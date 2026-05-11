<?= $this->extend('layouts/dashboard') ?>

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
                        <div class="col-6 text-right"><?= number_format($payroll['salary_per_day'] ?? 0) ?> đ</div>
                    </div>
                    <div class="row m-b-15">
                        <div class="col-6"><strong>Số công thực tế:</strong></div>
                        <div class="col-6 text-right"><?= $payroll['actual_working_days'] ?? 0 ?> ngày</div>
                    </div>
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
                        <div class="col-6"><strong>Giảm trừ phụ thuộc:</strong></div>
                        <div class="col-6 text-right text-red">- <?= number_format($payroll['dependent_deduction'] ?? 0) ?> đ</div>
                    </div>
                    <?php } ?>
                    <?php if (($payroll['pit_tax'] ?? 0) > 0) { ?>
                    <div class="row m-b-15 text-muted">
                        <div class="col-6"><strong>Thuế TNCN:</strong></div>
                        <div class="col-6 text-right text-red">- <?= number_format($payroll['pit_tax'] ?? 0) ?> đ</div>
                    </div>
                    <?php } ?>
                    <?php if (($payroll['salary_deduction'] ?? 0) > 0) { ?>
                    <div class="row m-b-15 text-muted">
                        <div class="col-6"><strong>Khấu trừ vi phạm:</strong></div>
                        <div class="col-6 text-right text-red">- <?= number_format($payroll['salary_deduction'] ?? 0) ?> đ</div>
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
                                        echo '<li class="m-b-5"><span class="text-muted" style="font-size: 11px;">[' . esc($n['date']) . ']</span> ' . esc($n['text']) . '</li>';
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
                                            <span class="text-muted" style="font-size: 11px; min-width: 120px; text-align: right;">[<?= esc($n['date']) ?>]</span> 
                                            <input type="text" class="form-control form-control-sm note-text-input" value="<?= esc($n['text']) ?>" style="flex-grow: 1; max-width: 500px;" <?= $config['is_closed'] ? 'disabled' : '' ?>>
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

<style>
    .row { display: flex; flex-wrap: wrap; }
    .col-6 { width: 50%; }
    .border-bottom { border-bottom: 1px solid #f2f2f7; }
    .p-b-10 { padding-bottom: 10px; }
    .italic { font-style: italic; }
</style>
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
    $(document).on('input', '.format-vnd', function() {
        var selection = window.getSelection().toString();
        if (selection !== '') return;
        var $this = $(this);
        var input = $this.val();
        var formatted = formatNumber(input);
        $this.val(formatted);
    });

    // Toggle hiển thị editor
    $(document).on('click', '.btn-toggle-notes', function() {
        const $container = $(this).closest('.p-15');
        const $editor = $container.find('.notes-editor-container');
        const $display = $container.find('.notes-display-area');
        
        if ($editor.is(':visible')) {
            $editor.slideUp(200);
            $display.slideDown(200);
            $(this).html('<i class="fas fa-edit text-blue"></i> Thêm / Sửa chi phí');
        } else {
            $display.slideUp(200);
            $editor.slideDown(200);
            $(this).html('<i class="fas fa-times text-muted"></i> Đóng');
        }
    });

    // Thêm ô input mới
    $(document).on('click', '.btn-add-note-input', function() {
        const $container = $(this).closest('.notes-editor-container');
        const $list = $container.find('.notes-inputs-list');
        
        const newHtml = `
            <div class="d-flex align-items-center gap-10 mb-2 note-input-item" style="display:none;">
                <span class="text-muted date-placeholder" style="font-size: 11px; min-width: 120px; text-align: right;"></span> 
                <input type="text" class="form-control form-control-sm note-text-input" placeholder="Nhập nội dung chi phí..." style="flex-grow: 1; max-width: 500px;">
                <button type="button" class="btn btn-sm btn-light btn-remove-note-input" title="Xóa" style="color: #ff3b30;"><i class="fas fa-trash"></i></button>
            </div>
        `;
        const $newEl = $(newHtml);
        $list.append($newEl);
        $newEl.fadeIn(150);
        
        // Hiện nút xóa cho các ô cũ
        $list.find('.btn-remove-note-input').show();
        $newEl.find('input').focus();
    });

    // Xóa ô input
    $(document).on('click', '.btn-remove-note-input', function() {
        const $list = $(this).closest('.notes-inputs-list');
        $(this).closest('.note-input-item').fadeOut(150, function() {
            $(this).remove();
            // Nếu chỉ còn 1 ô trống, ẩn nút xóa
            if ($list.find('.note-input-item').length === 1) {
                const $remainingInput = $list.find('.note-text-input');
                if ($remainingInput.val().trim() === '') {
                    $list.find('.btn-remove-note-input').hide();
                }
            } else if ($list.find('.note-input-item').length === 0) {
                // Nếu xóa hết, tự động thêm lại 1 ô trống
                $list.closest('.notes-editor-container').find('.btn-add-note-input').trigger('click');
            }
        });
    });

    // Lưu ghi chú
    $(document).on('click', '.btn-save-notes', function() {
        const $btn = $(this);
        const $container = $btn.closest('.notes-editor-container');
        const id = $container.data('id');
        
        let notes = [];
        const now = new Date();
        const dateStr = now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});
        
        $container.find('.note-input-item').each(function() {
            const $input = $(this).find('.note-text-input');
            const text = $input.val().trim();
            // Preserve existing date if any
            let date = $(this).find('span').text().replace('[', '').replace(']', '').trim();
            if (!date) date = dateStr;

            if (text) {
                notes.push({
                    id: Date.now() + Math.random(),
                    text: text,
                    date: date
                });
                // Update the visual date to the current format if it was empty
                $(this).find('span').text('[' + date + ']').show();
            }
        });
        
        const notesJsonStr = JSON.stringify(notes);
        const petrolVal = $container.find('.petrol-allowance-input').val().replace(/,/g, '');
        
        const origHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...').prop('disabled', true);
        
        $.post('<?= base_url('payroll/save-notes/') ?>' + id, {
            notes_json: notesJsonStr,
            petrol_allowance: petrolVal
        }, function(res) {
            $btn.html(origHtml).prop('disabled', false);
            if (res.code === 0) {
                // Update displays
                if (res.net_salary) $('#net-salary-display').text(res.net_salary);
                $('#petrol-display').text(formatNumber(petrolVal));

                // Update display list
                let displayHtml = '';
                if (notes.length > 0) {
                    displayHtml = '<ul class="m-0 pl-0 text-sm" style="list-style-type: disc;">';
                    notes.forEach(function(n) {
                        displayHtml += '<li class="m-b-5"><span class="text-muted" style="font-size: 11px;">[' + n.date + ']</span> ' + n.text + '</li>';
                    });
                    displayHtml += '</ul>';
                } else {
                    displayHtml = '<div class="text-muted text-sm italic">Chưa có chi phí phát sinh.</div>';
                }
                const $display = $container.closest('.p-15').find('.notes-display-area');
                $display.html(displayHtml);

                // Show success toast or visual cue
                $container.closest('div[style*="background"]').css('background-color', '#e8f5e9').delay(300).queue(function(next){
                    $(this).css('background-color', '#f9f9fb');
                    next();
                });
                
                // Toggle back to view mode
                $container.closest('.p-15').find('.btn-toggle-notes').trigger('click');
            } else {
                alert('Có lỗi xảy ra khi lưu: ' + (res.error || 'Unknown error'));
            }
        }).fail(function() {
            $btn.html(origHtml).prop('disabled', false);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        });
    });
});
</script>
<?= $this->endSection() ?>
<?= $this->endSection() ?>
