<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="attendance-history-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Lịch sử</h2>
            <p class="content-subtitle hide-mobile">Bảng chấm công chi tiết.</p>
        </div>
        
        <div class="header-controls">
            <form action="<?= base_url('attendance/list') ?>" method="get" class="filter-form">
                <input type="hidden" name="view" value="monthly">
                <input type="month" name="month" value="<?= $currentMonth ?>" class="form-control" onchange="this.form.submit()">
            </form>
            <a href="<?= base_url('attendance') ?>" class="btn-premium-sm">
                <i class="fas fa-camera"></i> Chấm công ngay
            </a>
        </div>
    </div>

    <div class="premium-card" style="padding: 0; overflow: hidden; border-radius: 20px;">
        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr style="background: #fbfbfd; border-bottom: 1px solid #eee;">
                        <th style="padding: 18px 20px; text-align: left;">Ngày</th>
                        <th style="padding: 18px 20px; text-align: center;">Vào/Ra</th>
                        <th class="hide-mobile" style="padding: 18px 20px; text-align: center;">Tổng giờ</th>
                        <th class="hide-mobile" style="padding: 18px 20px; text-align: left;">Ghi chú</th>
                        <th style="padding: 18px 20px; text-align: center;">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)) { ?>
                        <tr>
                            <td colspan="5" style="padding: 60px; text-align: center; color: var(--apple-text-muted);">
                                <i class="fas fa-calendar-times" style="font-size: 32px; display: block; margin-bottom: 15px; opacity: 0.2;"></i>
                                Không có dữ liệu trong tháng <?= date('m/Y', strtotime($currentMonth . '-01')) ?>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach($history as $item) { ?>
                            <tr style="border-bottom: 1px solid #f8f8f8;">
                                <td style="padding: 15px 20px;">
                                    <div style="font-weight: 600; color: #1d1d1f; font-size: 14px;"><?= date('d/m', strtotime($item['attendance_date'])) ?></div>
                                    <div style="font-size: 11px; color: var(--apple-text-muted);"><?= date('D', strtotime($item['attendance_date'])) ?></div>
                                </td>
                                <td style="padding: 15px 20px; text-align: center;">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                                        <div class="attendance-time-display">
                                            <div class="check-in"><?= $item['check_in_time'] ? date('H:i', strtotime($item['check_in_time'])) : '--:--' ?></div>
                                            <div class="check-out"><?= $item['check_out_time'] ? date('H:i', strtotime($item['check_out_time'])) : '--:--' ?></div>
                                        </div>
                                        <div style="display: flex; gap: 4px;">
                                            <?php if($item['check_in_photo']) { ?>
                                                <img src="<?= base_url($item['check_in_photo']) ?>" class="att-thumb" onclick="previewImage(this.src)">
                                            <?php } ?>
                                            <?php if($item['check_out_photo']) { ?>
                                                <img src="<?= base_url($item['check_out_photo']) ?>" class="att-thumb" onclick="previewImage(this.src)">
                                            <?php } ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="hide-mobile" style="padding: 15px 20px; text-align: center;">
                                    <span style="font-weight: 700; color: var(--apple-blue);"><?= $item['worked_hours'] ?: '0.00' ?>h</span>
                                </td>
                                <td class="hide-mobile" style="padding: 15px 20px;">
                                    <div style="font-size: 12px; color: #666; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= esc($item['check_in_note'] ?: $item['check_out_note']) ?>">
                                        <?= esc($item['check_in_note'] ?: $item['check_out_note'] ?: '---') ?>
                                    </div>
                                </td>
                                <td style="padding: 15px 20px; text-align: center;">
                                    <?php 
                                        $badgeStyle = "padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; display: inline-block;";
                                        switch($item['status']) {
                                            case 'REGULAR':
                                                echo "<span style='{$badgeStyle} background: #e3f9e5; color: #1a7f37;'>Đúng giờ</span>";
                                                break;
                                            case 'LATE':
                                            case 'EARLY_LEAVE':
                                                echo "<span style='{$badgeStyle} background: #fff4e6; color: #d97706;'>Trễ/Sớm</span>";
                                                break;
                                            case 'INVALID_LOCATION':
                                                echo "<span style='{$badgeStyle} background: #ffebeb; color: #cf222e;'>Sai vị trí</span>";
                                                break;
                                            default:
                                                echo "<span style='{$badgeStyle} background: #f5f5f7; color: #1d1d1f;'>{$item['status']}</span>";
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
