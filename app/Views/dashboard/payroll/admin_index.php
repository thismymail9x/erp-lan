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
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Lương CB</th>
                        <th>Phụ cấp</th>
                        <th>Thưởng / KPI</th>
                        <th>Khấu trừ / Phạt</th>
                        <th>Phát sinh</th>
                        <th>Ngày công</th>
                        <th>Vi phạm</th>
                        <th>Thực lĩnh</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payrolls)) { ?>
                        <tr><td colspan="10" class="text-center p-20">Chưa có dữ liệu bảng lương tháng này. Vui lòng bấm "Tính toán lương".</td></tr>
                    <?php } else { ?>
                        <?php foreach ($payrolls as $p) { ?>
                            <tr>
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
                                <td class="text-right"><?= number_format($p['salary_base']) ?></td>
                                <td class="text-right"><?= number_format($p['salary_allowance']) ?></td>
                                <td class="text-right text-green">
                                    <?php if (!$config['is_closed']) { ?>
                                        <input type="text" class="form-control-minimal edit-payroll-item format-vnd" 
                                               data-id="<?= $p['id'] ?>" data-field="salary_bonus" 
                                               value="<?= number_format($p['salary_bonus'] + $p['salary_kpi']) ?>" 
                                               style="width: 110px; text-align: right;">
                                    <?php } else { ?>
                                        +<?= number_format($p['salary_bonus'] + $p['salary_kpi']) ?>
                                    <?php } ?>
                                </td>
                                <td class="text-right text-red">
                                    <?php if (!$config['is_closed']) { ?>
                                        <input type="text" class="form-control-minimal edit-payroll-item format-vnd" 
                                               data-id="<?= $p['id'] ?>" data-field="salary_deduction" 
                                               value="<?= number_format($p['salary_deduction']) ?>" 
                                               style="width: 100px; text-align: right; color: #ff3b30;">
                                    <?php } else { ?>
                                        -<?= number_format($p['salary_deduction']) ?>
                                    <?php } ?>
                                </td>
                                <td class="text-right">
                                    <?php if (!$config['is_closed']) { ?>
                                        <input type="text" class="form-control-minimal edit-payroll-item format-vnd" 
                                               data-id="<?= $p['id'] ?>" data-field="salary_other" 
                                               value="<?= number_format($p['salary_other'] ?? 0) ?>" 
                                               style="width: 100px; text-align: right;">
                                    <?php } else { ?>
                                        <?= number_format($p['salary_other'] ?? 0) ?>
                                    <?php } ?>
                                </td>
                                <td class="text-center"><?= $p['actual_working_days'] ?>/<?= $p['total_standard_days'] ?></td>
                                <td class="text-center <?= ($p['attendance_violations'] > 0) ? 'text-red' : '' ?>"><?= $p['attendance_violations'] ?></td>
                                <td class="text-right"><strong><span id="net-<?= $p['id'] ?>"><?= number_format($p['net_salary']) ?></span> đ</strong></td>
                                <td class="text-center">
                                    <span class="badge <?= ($p['status'] === 'paid') ? 'badge-info-minimal' : 'badge-warning-minimal' ?>">
                                        <?= ($p['status'] === 'paid') ? 'Đã thanh toán' : 'Chờ duyệt' ?>
                                    </span>
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
        
        // Loại bỏ mọi ký tự không phải số và định dạng lại
        var formatted = formatNumber(input);
        $this.val(formatted);
    });

    // Sự kiện khi thay đổi giá trị (Blur hoặc Enter)
    $('.edit-payroll-item').on('change', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        
        // Lấy giá trị, loại bỏ dấu phẩy trước khi gửi
        const bonusRaw = row.find('[data-field="salary_bonus"]').val() || "0";
        const deductionRaw = row.find('[data-field="salary_deduction"]').val() || "0";
        const otherRaw = row.find('[data-field="salary_other"]').val() || "0";
        
        // Handle negative numbers for "Phát sinh"
        let other = otherRaw.replace(/,/g, '');
        if (other === '-' || other === '') other = '0';
        
        $.post('<?= base_url('payroll/update-item/') ?>' + id, {
            salary_bonus: bonusRaw.replace(/,/g, ''),
            salary_deduction: deductionRaw.replace(/,/g, ''),
            salary_other: other,
            salary_kpi: 0, // Reset KPI vì đã gộp vào bonus
            notes: ''
        }, function(resp) {
            if (resp.code === 0) {
                $('#net-' + id).text(resp.net_salary);
                // Hiển thị hiệu ứng thành công nhẹ
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
    
    // Toggle hiển thị dòng ghi chú khi click vào tên nhân viên
    $(document).on('click', '.employee-cell', function(e) {
        // Tránh toggle nếu user click vào input hoặc button bên trong cell (nếu có)
        if ($(e.target).closest('input, button, a.btn').length > 0) return;
        
        const id = $(this).data('id');
        $('#notes-edit-row-' + id).fadeToggle(150);
    });

    // Thêm ô input mới
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
        
        // Hiện nút xóa cho các ô cũ nếu trước đó chỉ có 1 ô
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

    // Hàm render lại danh sách ghi chú dưới tên nhân viên
    function updateNotesDisplay(id, notes) {
        let html = '';
        notes.forEach(n => {
            html += `<div class="text-xs text-muted" style="font-style: italic; margin-top: 2px;">
                        <i class="fas fa-level-up-alt fa-rotate-90 text-blue" style="margin-right: 4px;"></i>${n.text}
                     </div>`;
        });
        $('#notes-display-' + id).html(html);
    }

    // Lưu ghi chú
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
                notes.push({
                    id: Date.now() + Math.random(),
                    text: text,
                    date: dateStr
                });
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
                // Cập nhật textarea ẩn
                $('#raw-notes-' + id).val(notesJsonStr);
                // Cập nhật hiển thị dưới tên NV
                updateNotesDisplay(id, notes);
                
                // Thu gọn form (tùy chọn, ở đây ta có thể tự động ẩn đi hoặc giữ nguyên, tự động ẩn thì tiện hơn)
                $('#notes-edit-row-' + id).fadeOut(200);
                
                // Show success toast or visual cue
                $container.css('background-color', '#e8f5e9').delay(300).queue(function(next){
                    $(this).css('background-color', '');
                    next();
                });
            } else {
                alert('Có lỗi xảy ra khi lưu ghi chú: ' + (res.error || 'Unknown error'));
            }
        }).fail(function() {
            $btn.html(origHtml).prop('disabled', false);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        });
    });
});
</script>
<style>
.form-control-minimal {
    border: 1px solid #d2d2d7;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 13px;
    outline: none;
}
.form-control-minimal:focus {
    border-color: var(--apple-blue);
    box-shadow: 0 0 0 2px rgba(0, 113, 227, 0.1);
}
</style>
<style>
.form-control-minimal {
    border: 1px solid #d2d2d7;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 13px;
    outline: none;
}
.form-control-minimal:focus {
    border-color: var(--apple-blue);
    box-shadow: 0 0 0 2px rgba(0, 113, 227, 0.1);
}
</style>
<?= $this->endSection() ?>
<?= $this->endSection() ?>
