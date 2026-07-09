<div class="table-responsive">
    <table class="premium-table">
        <thead>
            <tr>
                <?php
                $canManage = in_array(session()->get('role_name'), \Config\AppConstants::PRIVILEGED_ROLES) || has_permission('attendance.view_all');
                if ($canManage) { ?>
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
                <?php foreach($history as $item) {
                    $statusVal  = $item['status'] ?? '';
                    $isLeave    = str_starts_with($statusVal, 'LEAVE_');
                    $needsReview = (!$isLeave && $statusVal != 'REGULAR' && $statusVal != 'LEAVE' && $item['check_in_time'] && (!empty($item['check_in_note']) || !empty($item['check_out_note'])));

                    if ($isLeave) {
                        $rowStyle = 'background-color: #f0f9ff; border-left: 4px solid #007aff;';
                    } elseif ($needsReview) {
                        $rowStyle = 'background-color: #fff9e6; border-left: 4px solid #ffcc00;';
                    } else {
                        $rowStyle = 'border-bottom: 1px solid #f8f8f8;';
                    }
                ?>
                    <tr style="<?= $rowStyle ?>">
                        <?php if ($canManage) { ?>
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
                                        <img src="<?= base_url($item['check_in_photo']) ?>" class="att-thumb" title="Ảnh chụp lúc vào">
                                    <?php } ?>
                                    <?php if($item['check_out_photo']) { ?>
                                        <img src="<?= base_url($item['check_out_photo']) ?>" class="att-thumb" title="Ảnh chụp lúc ra">
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
                        <!-- ===== TRẠNG THÁI — Inline Edit cho Admin/Manager ===== -->
                        <td class="att-table-td-center" style="min-width: 145px; vertical-align: middle;">
                            <?php
                            // Badge mapping
                            $badgeMap = [
                                'REGULAR'          => ['label' => 'ĐÚNG GIỜ',     'class' => 'att-badge-regular'],
                                'LATE'             => ['label' => 'TRỄ / SỚM',    'class' => 'att-badge-late'],
                                'EARLY_LEAVE'      => ['label' => 'VỀ SỚM',       'class' => 'att-badge-late'],
                                'INVALID_LOCATION' => ['label' => 'SAI VỊ TRÍ',   'class' => 'att-badge-invalid'],
                                'LEAVE_MORNING'    => ['label' => 'NGHỈ SÁNG',    'class' => 'att-badge-leave-half'],
                                'LEAVE_AFTERNOON'  => ['label' => 'NGHỈ CHIỀU',   'class' => 'att-badge-leave-half'],
                                'LEAVE_FULL_DAY'   => ['label' => 'NGHỈ CẢ NGÀY', 'class' => 'att-badge-leave'],
                            ];

                            $badgeInfo = $badgeMap[$statusVal] ?? null;
                            if (!$badgeInfo) {
                                if (!$item['check_in_time'] && !$isLeave) {
                                    $badgeInfo = ['label' => 'VẮNG', 'class' => 'att-badge-absent'];
                                } elseif ($isLeave) {
                                    $badgeInfo = ['label' => 'NGHỈ PHÉP', 'class' => 'att-badge-leave'];
                                } else {
                                    $hasNote = (!empty($item['check_in_note']) || !empty($item['check_out_note']));
                                    if ($hasNote && $statusVal !== 'INVALID_LOCATION' && ($statusVal === 'LATE' || $statusVal === 'EARLY_LEAVE')) {
                                        $badgeInfo = ['label' => 'CHỜ DUYỆT', 'class' => 'att-badge-late'];
                                    } else {
                                        $badgeInfo = ['label' => esc($statusVal), 'class' => 'att-badge-neutral'];
                                    }
                                }
                            }

                            if ($canManage && $item['id']) {
                            ?>
                                <div class="inline-status-wrapper" data-att-id="<?= $item['id'] ?>" data-update-url="<?= base_url('attendance/update-status/' . $item['id']) ?>">
                                    <span class="att-badge-base <?= $badgeInfo['class'] ?> inline-status-badge"
                                          title="Click để chỉnh trạng thái"
                                          style="cursor: pointer;">
                                        <?= $badgeInfo['label'] ?>
                                        <i class="fas fa-pen" style="font-size: 9px; margin-left: 3px; opacity: 0.65;"></i>
                                    </span>
                                    <div class="status-inline-dropdown" id="status-drop-<?= $item['id'] ?>"
                                         style="display:none; position:absolute; right: 0; z-index:9999; background:#fff;
                                                border:1px solid #e5e5ea; border-radius:12px;
                                                box-shadow:0 6px 24px rgba(0,0,0,0.13); padding:6px; min-width:195px; text-align: left;">
                                        <div style="font-size:11px; color:#888; padding:4px 10px 6px; font-weight:700;
                                                    text-transform:uppercase; letter-spacing:0.5px;
                                                    border-bottom:1px solid #f2f2f7; margin-bottom:4px;">Chọn trạng thái</div>
                                        <?php
                                        $dropOptions = [
                                            'REGULAR'         => ['Đúng giờ',                      '#34c759'],
                                            'LATE'            => ['Trễ / Về sớm',                  '#ff9500'],
                                            'LEAVE_MORNING'   => ['Nghỉ buổi sáng (0.5 công)',      '#007aff'],
                                            'LEAVE_AFTERNOON' => ['Nghỉ buổi chiều (0.5 công)',     '#007aff'],
                                            'LEAVE_FULL_DAY'  => ['Nghỉ cả ngày (1 công nghỉ)',     '#5856d6'],
                                        ];
                                        foreach ($dropOptions as $val => $info) {
                                            $activeStyle = ($statusVal === $val) ? 'background:#f0f5ff;font-weight:700;' : '';
                                            echo "<div class='status-drop-item'
                                                       style='padding:8px 10px;border-radius:8px;cursor:pointer;font-size:13px;{$activeStyle}'
                                                       data-val='" . esc($val) . "'>";
                                            echo "<span style='display:inline-block;width:9px;height:9px;border-radius:50%;background:{$info[1]};margin-right:8px;vertical-align:middle;'></span>";
                                            echo esc($info[0]);
                                            echo "</div>";
                                        }
                                        ?>
                                        <div style="border-top:1px solid #f2f2f7; margin-top:4px; padding-top:4px;">
                                            <div class="status-drop-item status-drop-close"
                                                 style="padding:7px 10px;border-radius:8px;cursor:pointer;font-size:12px;color:#ff3b30;">
                                                <i class="fas fa-times" style="margin-right:6px;"></i>Hủy
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <span class="att-badge-base <?= $badgeInfo['class'] ?>"><?= $badgeInfo['label'] ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php if ($canManage) { ?>
    <div class="bulk-actions-bar" id="bulk-bar" style="display: none;">
        <span id="selected-count">0 mục đã chọn</span>
        <div style="display: flex; gap: 10px; align-items: center;">
            <select id="bulk-status" class="form-control-premium" style="height: 32px; font-size: 13px!important; padding: 0 10px; width: 150px;">
                <option value="">-- Đổi trạng thái --</option>
                <option value="REGULAR">Đúng giờ</option>
                <option value="LATE">Vi phạm</option>
            </select>

        </div>
        <button type="button" class="btn-premium js-apply-bulk-update" style="height: 32px; line-height: 32px; padding: 0 15px;">
            Cập nhật
        </button>
    </div>
<?php } ?>
