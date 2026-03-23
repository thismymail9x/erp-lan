<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="attendance-history-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Lịch sử</h2>
            <p class="content-subtitle hide-mobile">Bảng chấm công chi tiết.</p>
        </div>
        
        <div class="header-controls">
            <form action="<?= base_url('attendance/list') ?>" method="get" class="filter-form" title="Chọn tháng để xem bảng công">
                <input type="hidden" name="view" value="monthly">
                <input type="month" name="month" value="<?= $currentMonth ?>" class="form-control-premium" onchange="this.form.submit()">
            </form>
            <a href="<?= base_url('attendance') ?>" class="btn-premium-sm" title="Quay lại trang chấm công bằng Camera">
                <i class="fas fa-camera"></i> Chấm công ngay
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-full">
        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th title="Ngày làm việc trong tháng">Ngày</th>
                        <th class="table-cell-center" title="Chi tiết thời gian và hình ảnh đối soát">Vào/Ra</th>
                        <th class="hide-mobile table-cell-center" title="Tổng quỹ thời gian làm việc thực tế">Tổng giờ</th>
                        <th class="hide-mobile" title="Ghi chú nhân viên nhập khi chấm công">Ghi chú</th>
                        <th class="table-cell-center" title="Phân loại tính hợp lệ của công">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)) { ?>
                        <tr>
                            <td colspan="5" class="empty-state-container">
                                <i class="fas fa-calendar-times" style="font-size: 32px; display: block; margin-bottom: 15px; opacity: 0.2;"></i>
                                Không có dữ liệu trong tháng <?= date('m/Y', strtotime($currentMonth . '-01')) ?>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach($history as $item) { ?>
                            <tr>
                                <td>
                                    <div class="at-date-cell" title="Xem công ngày <?= date('d/m/Y', strtotime($item['attendance_date'])) ?>">
                                        <div class="at-date-main"><?= date('d/m', strtotime($item['attendance_date'])) ?></div>
                                        <div class="at-date-sub"><?= date('D', strtotime($item['attendance_date'])) ?></div>
                                    </div>
                                </td>
                                <td class="table-cell-center">
                                    <div class="attendance-time-display">
                                        <div class="at-time-stack">
                                            <div class="at-time-in"><?= $item['check_in_time'] ? date('H:i', strtotime($item['check_in_time'])) : '--:--' ?></div>
                                            <div class="at-time-out"><?= $item['check_out_time'] ? date('H:i', strtotime($item['check_out_time'])) : '--:--' ?></div>
                                        </div>
                                        <div class="at-thumb-container">
                                            <?php if($item['check_in_photo']) { ?>
                                                <img src="<?= base_url($item['check_in_photo']) ?>" class="at-thumb" onclick="previewImage(this.src)" title="Ảnh chụp lúc vào">
                                            <?php } ?>
                                            <?php if($item['check_out_photo']) { ?>
                                                <img src="<?= base_url($item['check_out_photo']) ?>" class="at-thumb" onclick="previewImage(this.src)" title="Ảnh chụp lúc ra">
                                            <?php } ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="hide-mobile table-cell-center">
                                    <span style="font-weight: 700; color: var(--apple-blue);"><?= $item['worked_hours'] ?: '0.00' ?>h</span>
                                </td>
                                <td class="hide-mobile">
                                    <div class="at-note-text" title="<?= esc($item['check_in_note'] ?: $item['check_out_note']) ?>">
                                        <?= esc($item['check_in_note'] ?: $item['check_out_note'] ?: '---') ?>
                                    </div>
                                </td>
                                <td class="table-cell-center">
                                    <?php 
                                        switch($item['status']) {
                                            case 'REGULAR':
                                                echo "<span class='at-badge at-badge-regular'>Đúng giờ</span>";
                                                break;
                                            case 'LATE':
                                            case 'EARLY_LEAVE':
                                                echo "<span class='at-badge at-badge-late'>Trễ/Sớm</span>";
                                                break;
                                            case 'INVALID_LOCATION':
                                                echo "<span class='at-badge at-badge-invalid'>Sai vị trí</span>";
                                                break;
                                            default:
                                                echo "<span class='at-badge at-badge-neutral'>{$item['status']}</span>";
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
