<div class="table-responsive">
    <table class="premium-table">
        <thead>
            <?php 
            $baseQuery = "?view={$viewType}";
            if ($viewType == 'daily') $baseQuery .= "&date={$currentDate}";
            else $baseQuery .= "&month={$currentMonth}";
            if ($currentDept) $baseQuery .= "&department_id={$currentDept}";
            if ($currentEmployee) $baseQuery .= "&employee_id={$currentEmployee}";

            $currentSort = $currentSort ?? 'date';
            $currentOrder = $currentOrder ?? 'desc';
            ?>
            <tr class="att-table-header-row">
                <th class="att-table-th-center" style="width: 40px;">
                    <input type="checkbox" id="check-all" style="width: 18px; height: 18px; cursor: pointer;">
                </th>
                <th class="att-table-th-center" style="width: 75px;">STT (<?= count($records) ?>)</th>
                <?php if (($viewType ?? 'daily') == 'monthly') { ?>
                    <th class="att-table-th" style="width: 100px;">
                        <a href="<?= base_url('attendance/list') ?><?= $baseQuery ?>&sort=date&order=<?= ($currentSort == 'date' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" data-sort="date" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                            Ngày
                            <?php if($currentSort == 'date') { ?>
                                <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                            <?php } else { ?>
                                <i class="fas fa-sort" style="opacity: 0.3;"></i>
                            <?php } ?>
                        </a>
                    </th>
                <?php } ?>
                <th class="att-table-th">
                    <a href="<?= base_url('attendance/list') ?><?= $baseQuery ?>&sort=name&order=<?= ($currentSort == 'name' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" data-sort="name" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                        Nhân viên
                        <?php if($currentSort == 'name') { ?>
                            <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                        <?php } else { ?>
                            <i class="fas fa-sort" style="opacity: 0.3;"></i>
                        <?php } ?>
                    </a>
                </th>
                <th class="hide-mobile att-table-th">
                    <a href="<?= base_url('attendance/list') ?><?= $baseQuery ?>&sort=dept&order=<?= ($currentSort == 'dept' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" data-sort="dept" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                        Bộ phận
                        <?php if($currentSort == 'dept') { ?>
                            <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                        <?php } else { ?>
                            <i class="fas fa-sort" style="opacity: 0.3;"></i>
                        <?php } ?>
                    </a>
                </th>
                <th class="hide-mobile att-table-th-center">Giờ</th>
                <th class="hide-mobile att-table-th-center">VT</th>
                <th class="hide-mobile att-table-th">Note</th>
                <th class="att-table-th-center">
                    <a href="<?= base_url('attendance/list') ?><?= $baseQuery ?>&sort=status&order=<?= ($currentSort == 'status' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" class="sort-link" data-sort="status" style="color: inherit; text-decoration: none; justify-content: center; display: inline-flex; align-items: center; gap: 4px;">
                        Trạng thái
                        <?php if($currentSort == 'status') { ?>
                            <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                        <?php } else { ?>
                            <i class="fas fa-sort" style="opacity: 0.3;"></i>
                        <?php } ?>
                    </a>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php $stt = isset($pager) ? ($pager->getCurrentPage() - 1) * $pager->getPerPage() : 0; ?>
            <?php if (empty($records) || !is_array($records)) { ?>
                <tr><td colspan="12" style="padding: 60px; text-align: center; color: var(--apple-text-muted);">Không tìm thấy dữ liệu phù hợp.</td></tr>
            <?php } else { ?>
                <?php foreach($records as $row) { $stt++; ?>
                    <?php 
                        $hasNote = (!empty($row['check_in_note']) || !empty($row['check_out_note']));
                        $needsReview = ($row['status'] != 'REGULAR' && $row['status'] != 'LEAVE' && $row['check_in_time'] && $hasNote);
                        $rowStyle = $needsReview ? 'background-color: #fff9e6; border-left: 4px solid #ffcc00;' : 'border-bottom: 1px solid #f8f8f8;';
                    ?>
                    <tr style="<?= $rowStyle ?>">
                        <td class="att-table-td-center">
                            <input type="checkbox" class="record-check" value="<?= $row['id'] ?>" style="width: 18px; height: 18px; cursor: pointer;">
                        </td>
                        <td class="att-table-td-center text-muted-dark text-sm"><?= $stt ?></td>
                        <?php if (($viewType ?? 'daily') == 'monthly') { ?>
                            <td class="att-table-td att-date-main">
                                <?= isset($row['attendance_date']) ? date('d/m', strtotime($row['attendance_date'])) : '--' ?>
                            </td>
                        <?php } ?>
                        <td class="att-table-td">
                            <a href="<?= base_url('attendance/list') ?>?view=monthly&month=<?= date('Y-m', strtotime($row['attendance_date'] ?: $currentDate)) ?>&employee_id=<?= $row['emp_id'] ?>" class="att-emp-link" title="Xem lịch sử chấm công tháng của <?= esc($row['full_name']) ?>">
                                <?= esc($row['full_name']) ?>
                                <?php if ($needsReview) { ?>
                                    <i class="fas fa-flag m-l-5" style="color: #ff9500;" title="Nhân viên có để lại bình luận giải trình - Cần xem xét"></i>
                                <?php } ?>
                            </a>
                        </td>
                        <td class="hide-mobile att-table-td att-emp-dept">
                            <?= esc($row['dept_name'] ?: '---') ?>
                        </td>
                        <td class="att-table-td-center">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 12px;">
                                <div class="attendance-time-display">
                                    <div class="att-time-main"><?= $row['check_in_time'] ? date('H:i', strtotime($row['check_in_time'])) : '--:--' ?></div>
                                    <div class="att-time-sub"><?= $row['check_out_time'] ? date('H:i', strtotime($row['check_out_time'])) : '--:--' ?></div>
                                </div>
                                <div style="display: flex; gap: 4px;">
                                    <?php if($row['check_in_photo']) { ?>
                                        <img src="<?= base_url($row['check_in_photo']) ?>" class="att-thumb" title="Ảnh vào" onclick="previewImage(this.src)">
                                    <?php } ?>
                                    <?php if($row['check_out_photo']) { ?>
                                        <img src="<?= base_url($row['check_out_photo']) ?>" class="att-thumb" title="Ảnh ra" onclick="previewImage(this.src)">
                                    <?php } ?>
                                </div>
                            </div>
                        </td>
                        <td class="hide-mobile att-table-td-center att-hours-val" title="Tổng số giờ đã làm việc được tính toán tự động">
                            <?= $row['worked_hours'] ? $row['worked_hours'] . "h" : '---' ?>
                        </td>
                        <td class="hide-mobile att-table-td-center">
                            <?php if($row['check_in_time']) { ?>
                                <i class="fas <?= $row['is_valid_location'] ? 'fa-check-circle' : 'fa-times-circle' ?>" style="color: <?= $row['is_valid_location'] ? '#34c759' : '#ff3b30' ?>; font-size: 16px;" title="<?= $row['is_valid_location'] ? 'Vị trí hợp lệ' : 'Sai vị trí quy định' ?>"></i>
                            <?php } else { ?>
                                ---
                            <?php } ?>
                        </td>
                        <td class="hide-mobile att-table-td">
                            <div class="att-note-cell-text" title="<?= esc($row['check_in_note'] . ($row['check_out_note'] ? ' | ' . $row['check_out_note'] : '')) ?>">
                                <?php if ($row['check_in_note']) { ?>
                                    <div class="note-item"><small>In:</small> <?= esc($row['check_in_note']) ?></div>
                                <?php } ?>
                                <?php if ($row['check_out_note']) { ?>
                                    <div class="note-item"><small>Out:</small> <?= esc($row['check_out_note']) ?></div>
                                <?php } ?>
                                <?php if (!$row['check_in_note'] && !$row['check_out_note']) { ?>
                                    <span style="color: #ccc;">---</span>
                                <?php } ?>
                            </div>
                        </td>
                        <td class="att-table-td-center">
                            <?php 
                                if (!$row['check_in_time']) {
                                    echo "<span class='att-badge-base att-badge-absent'>VẮNG</span>";
                                } else {
                                    switch($row['status']) {
                                        case 'REGULAR':
                                            echo "<span class='att-badge-base att-badge-regular'>ĐÚNG GIỜ</span>";
                                            break;
                                        case 'LATE':
                                        case 'EARLY_LEAVE':
                                            if ($hasNote) {
                                                echo "<span class='att-badge-base att-badge-late' style='background-color: #ff9500; color: white;'>CHỜ DUYỆT</span>";
                                            } else {
                                                echo "<span class='att-badge-base att-badge-late'>TRỄ / SỚM</span>";
                                            }
                                            break;
                                        case 'INVALID_LOCATION':
                                            echo "<span class='att-badge-base att-badge-invalid'>SAI VỊ TRÍ</span>";
                                            break;
                                        default:
                                            echo "<span class='att-badge-base att-badge-neutral'>" . esc($row['status']) . "</span>";
                                    }
                                }
                            ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
</div>
