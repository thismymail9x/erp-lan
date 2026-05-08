<div class="table-responsive">
    <table class="premium-table">
        <thead>
            <tr>
                <?php if (in_array(session()->get('role_name'), \Config\AppConstants::PRIVILEGED_ROLES)) { ?>
                    <th class="table-cell-center" style="width: 40px;">
                        <input type="checkbox" id="check-all" style="width: 16px; height: 16px; cursor: pointer;" title="Chọn tất cả">
                    </th>
                <?php } ?>
                <th title="Ngày làm việc trong tháng">Ngày</th>
                <th class="table-cell-center" title="Chi tiết thời gian và hình ảnh đối soát">In/Out</th>
                <th class="hide-mobile table-cell-center" title="Tổng quỹ thời gian làm việc thực tế">Giờ</th>
                <th class="hide-mobile table-cell-center" title="Ghi chú nhân viên nhập khi chấm công">Note</th>
                <th class="table-cell-center" title="Phân loại tính hợp lệ của công">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($history)) { ?>
                <tr>
                    <td colspan="<?= in_array(session()->get('role_name'), \Config\AppConstants::PRIVILEGED_ROLES) ? 6 : 5 ?>" class="empty-state-container">
                        <i class="fas fa-calendar-times" style="font-size: 32px; display: block; margin-bottom: 15px; opacity: 0.2;"></i>
                        Không có dữ liệu trong tháng <?= date('m/Y', strtotime($currentMonth . '-01')) ?>
                    </td>
                </tr>
            <?php } else { ?>
                <?php foreach($history as $item) { ?>
                    <tr>
                        <?php if (in_array(session()->get('role_name'), \Config\AppConstants::PRIVILEGED_ROLES)) { ?>
                            <td class="table-cell-center" style="vertical-align: middle;">
                                <input type="checkbox" class="record-check" value="<?= $item['id'] ?>" style="width: 16px; height: 16px; cursor: pointer;">
                            </td>
                        <?php } ?>
                        <td class="att-table-td">
                            <div class="at-date-cell" title="Xem công ngày <?= date('d/m/Y', strtotime($item['attendance_date'])) ?>">
                                <div class="att-date-main"><?= date('d/m', strtotime($item['attendance_date'])) ?></div>
                                <div class="at-date-sub">
                                    <?php 
                                        $dayEn = date('D', strtotime($item['attendance_date']));
                                        $daysVi = ['Sun' => 'CN', 'Mon' => 'T2', 'Tue' => 'T3', 'Wed' => 'T4', 'Thu' => 'T5', 'Fri' => 'T6', 'Sat' => 'T7'];
                                        echo $daysVi[$dayEn] ?? $dayEn;
                                    ?>
                                </div>
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
                                    case 'INVALID_LOCATION':
                                        $hasNote = (!empty($item['check_in_note']) || !empty($item['check_out_note']));
                                        if ($hasNote && $item['status'] !== 'INVALID_LOCATION') {
                                            echo "<span class='att-badge-base att-badge-late' style='background-color: #ff9500; color: white;'>Chờ duyệt</span>";
                                        } else {
                                            echo "<span class='att-badge-base att-badge-late'>Vi phạm</span>";
                                        }
                                        break;
                                    case 'LEAVE_PERSONAL':
                                        echo "<span class='att-badge-base att-badge-neutral' style='background-color: #5856d6; color: white;'>Nghỉ</span>";
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

<?php if (in_array(session()->get('role_name'), \Config\AppConstants::PRIVILEGED_ROLES)) { ?>
    <div class="bulk-actions-bar" id="att-bulk-bar" style="display: none;">
        <span id="selected-count">0 mục đã chọn</span>
        <div style="display: flex; gap: 10px; align-items: center;">
            <select id="bulk-status" class="form-control-premium" style="height: 32px; font-size: 13px!important; padding: 0 10px; width: 150px;">
                <option value="">-- Đổi trạng thái --</option>
                <option value="REGULAR">Đúng giờ</option>
                <option value="LATE">Vi phạm</option>
            </select>

        </div>
        <button type="button" class="btn-premium" onclick="bulkUpdateAttendance()" style="height: 32px; line-height: 32px; padding: 0 15px;">
            Cập nhật
        </button>
    </div>
<?php } ?>
