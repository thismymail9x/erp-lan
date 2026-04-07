<div class="table-responsive">
    <table class="premium-table">
        <thead>
            <tr>
                    <th title="Ngày làm việc trong tháng">Ngày</th>
                <th class="table-cell-center" title="Chi tiết thời gian và hình ảnh đối soát">In/Out</th>
                <th class="hide-mobile table-cell-center" title="Tổng quỹ thời gian làm việc thực tế">Giờ</th>
                <th class="hide-mobile" title="Ghi chú nhân viên nhập khi chấm công">Note</th>
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
                            <div class="att-note-cell-text" title="<?= esc($item['check_in_note'] . ($item['check_out_note'] ? ' | ' . $item['check_out_note'] : '')) ?>">
                                <?php if ($item['check_in_note']) { ?>
                                    <div class="note-item"><small>In:</small> <?= esc($item['check_in_note']) ?></div>
                                <?php } ?>
                                <?php if ($item['check_out_note']) { ?>
                                    <div class="note-item"><small>Out:</small> <?= esc($item['check_out_note']) ?></div>
                                <?php } ?>
                                <?php if (!$item['check_in_note'] && !$item['check_out_note']) { ?>
                                    <span style="color: #ccc;">---</span>
                                <?php } ?>
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
                                        $hasNote = (!empty($item['check_in_note']) || !empty($item['check_out_note']));
                                            if ($hasNote) {
                                                echo "<span class='att-badge-base att-badge-late' style='background-color: #ff9500; color: white;'>Chờ</span>";
                                            } else {
                                                echo "<span class='att-badge-base att-badge-late'>Trễ</span>";
                                            }
                                        break;
                                    case 'INVALID_LOCATION':
                                        echo "<span class='att-badge-base att-badge-invalid'>Sai VT</span>";
                                        break;
                                    default:
                                        echo "<span class='att-badge-base att-badge-neutral'>" . esc($item['status']) . "</span>";
                                }
                            ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
</div>
