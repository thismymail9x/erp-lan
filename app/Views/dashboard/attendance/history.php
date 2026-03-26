<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/attendance.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="attendance-history-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title"><?php echo (isset($isViewingOthers) && $isViewingOthers) ? $title : 'Lịch sử chấm công cá nhân'; ?></h2>
            <p class="content-subtitle hide-mobile">Bảng chấm công chi tiết tháng <?= date('m/Y', strtotime($currentMonth . '-01')) ?>.</p>
        </div>
        
        <div class="header-controls">
            <form action="<?= base_url('attendance/list') ?>" method="get" class="filter-form" title="Chọn tháng để xem bảng công">
                <input type="hidden" name="view" value="monthly">
                <?php if (isset($targetEmployeeId)) { ?>
                    <input type="hidden" name="employee_id" value="<?= $targetEmployeeId ?>">
                <?php } ?>
                <input type="month" name="month" value="<?= $currentMonth ?>" class="form-control-premium" onchange="this.form.submit()">
            </form>
            <?php if (isset($isViewingOthers) && $isViewingOthers) { ?>
                <a href="<?= base_url('attendance/list') ?>" class="btn-secondary-sm" title="Quay lại danh sách tổng quát">
                    <i class="fas fa-chevron-left"></i> Quay lại
                </a>
            <?php } else { ?>
                <a href="<?= base_url('attendance') ?>" class="btn-premium-sm" title="Quay lại trang chấm công bằng Camera">
                    <i class="fas fa-camera"></i> Chấm công ngay
                </a>
            <?php } ?>
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
                                <td class="att-table-td">
                                    <div class="at-date-cell" title="Xem công ngày <?= date('d/m/Y', strtotime($item['attendance_date'])) ?>">
                                        <div class="att-date-main"><?= date('d/m', strtotime($item['attendance_date'])) ?></div>
                                        <div class="at-date-sub"><?= date('D', strtotime($item['attendance_date'])) ?></div>
                                    </div>
                                </td>
                                <td class="att-table-td-center">
                                    <div class="attendance-time-display">
                                        <div class="at-time-stack">
                                            <div class="att-time-main"><?= $item['check_in_time'] ? date('H:i', strtotime($item['check_in_time'])) : '--:--' ?></div>
                                            <div class="at-time-sub"><?= $item['check_out_time'] ? date('H:i', strtotime($item['check_out_time'])) : '--:--' ?></div>
                                        </div>
                                        <div class="at-thumb-container">
                                            <?php if($item['check_in_photo']) { ?>
                                                <img src="<?= base_url($item['check_in_photo']) ?>" class="att-thumb" onclick="previewImage(this.src)" title="Ảnh chụp lúc vào">
                                            <?php } ?>
                                            <?php if($item['check_out_photo']) { ?>
                                                <img src="<?= base_url($item['check_out_photo']) ?>" class="att-thumb" onclick="previewImage(this.src)" title="Ảnh chụp lúc ra">
                                            <?php } ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="hide-mobile att-table-td-center">
                                    <span class="att-hours-val" style="color: var(--apple-blue);"><?= $item['worked_hours'] ?: '0.00' ?>h</span>
                                </td>
                                <td class="hide-mobile att-table-td">
                                    <div class="at-note-text" title="<?= esc($item['check_in_note'] ?: $item['check_out_note']) ?>">
                                        <?= esc($item['check_in_note'] ?: $item['check_out_note'] ?: '---') ?>
                                    </div>
                                </td>
                                <td class="att-table-td-center">
                                    <?php 
                                        switch($item['status']) {
                                            case 'REGULAR':
                                                echo "<span class='att-badge-base att-badge-regular'>Đúng giờ</span>";
                                                break;
                                            case 'LATE':
                                            case 'EARLY_LEAVE':
                                                echo "<span class='att-badge-base att-badge-late'>Trễ/Sớm</span>";
                                                break;
                                            case 'INVALID_LOCATION':
                                                echo "<span class='att-badge-base att-badge-invalid'>Sai vị trí</span>";
                                                break;
                                            default:
                                                echo "<span class='att-badge-base att-badge-neutral'>{$item['status']}</span>";
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
<script>
/**
 * L.A.N ERP - Lịch sử Chấm công Cá nhân
 * Quản lý các tương tác xem lại dữ liệu chấm công.
 */

/**
 * Tiện ích Xem trước hình ảnh minh chứng.
 * Mở ảnh trong tab mới để xem chi tiết khuôn mặt hoặc vị trí chụp.
 * @param {string} src - Đường dẫn (URL) tới hình ảnh cần xem.
 */
function previewImage(src) {
    if (src) {
        // Mở cửa sổ mới với các thuộc tính bảo mật phù hợp
        window.open(src, '_blank', 'noopener,noreferrer');
    }
}
</script>
<?= $this->endSection() ?>
