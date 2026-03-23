<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="attendance-admin-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Nhật ký</h2>
            <p class="content-subtitle hide-mobile">Quản lý chuyên cần.</p>
        </div>
        
        <div class="header-controls">
            <form action="<?= base_url('attendance/list') ?>" method="get" class="filter-form">
                <div class="form-group-apple">
                    <label>Chế độ</label>
                    <select name="view" class="form-control-premium" onchange="this.form.submit()" title="Chuyển đổi giữa xem chi tiết theo ngày hoặc tổng hợp theo tháng">
                        <option value="daily" <?= ($viewType ?? '') == 'daily' ? 'selected' : '' ?>>Theo ngày</option>
                        <option value="monthly" <?= ($viewType ?? '') == 'monthly' ? 'selected' : '' ?>>Theo tháng</option>
                    </select>
                </div>

                <?php if (($viewType ?? 'daily') == 'daily') { ?>
                <div class="form-group-apple">
                    <label>Ngày</label>
                    <input type="date" name="date" value="<?= $currentDate ?>" class="form-control-premium" onchange="this.form.submit()" title="Chọn ngày cụ thể để xem danh sách điểm danh">
                </div>
                <?php } else { ?>
                <div class="form-group-apple">
                    <label>Tháng</label>
                    <input type="month" name="month" value="<?= $currentMonth ?>" class="form-control-premium" onchange="this.form.submit()" title="Chọn tháng cần xem bảng công tổng hợp">
                </div>
                <?php } ?>
                
                <div class="form-group-apple">
                    <label>Phòng ban</label>
                    <select name="department_id" class="form-control-premium" onchange="this.form.submit()" title="Lọc nhân viên theo phòng ban chuyên môn">
                        <option value="">Tất cả phòng ban</option>
                        <?php foreach($departments as $d) { ?>
                            <option value="<?= $d['id'] ?>" <?= $currentDept == $d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                        <?php } ?>
                    </select>
                </div>
            </form>
            
            <div class="actions-group">
                <a href="<?= base_url('attendance') ?>" class="btn-premium-sm hide-mobile" title="Chuyển sang giao diện chấm công cá nhân">
                    <i class="fas fa-camera"></i> Chấm công tôi
                </a>
                <a href="<?= base_url('attendance/export') ?>?month=<?= date('Y-m', strtotime($currentDate)) ?>" class="btn-secondary-sm" title="Xuất dữ liệu bảng công ra file Excel/CSV">
                    <i class="fas fa-download"></i> Xuất Excel
                </a>
            </div>
        </div>
    </div>

    <div class="premium-card" style="padding: 0; overflow: hidden; border-radius: 18px;">
        <div class="table-responsive">
            <table class="premium-table">
                <thead>
<?php 
$baseQuery = "?view={$viewType}";
if ($viewType == 'daily') $baseQuery .= "&date={$currentDate}";
else $baseQuery .= "&month={$currentMonth}";
if ($currentDept) $baseQuery .= "&department_id={$currentDept}";

$currentSort = $currentSort ?? 'date';
$currentOrder = $currentOrder ?? 'desc';
?>
                    <tr style="background: #fbfbfd; border-bottom: 1px solid #eee;">
                        <th style="padding: 18px 20px; text-align: center; width: 40px;">
                            <input type="checkbox" id="check-all" style="width: 18px; height: 18px; cursor: pointer;">
                        </th>
                        <?php if (($viewType ?? 'daily') == 'monthly') { ?>
                            <th style="padding: 18px 20px; text-align: left; width: 100px;">
                                <a href="<?= base_url('attendance/list') ?><?= $baseQuery ?>&sort=date&order=<?= ($currentSort == 'date' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                    Ngày
                                    <?php if($currentSort == 'date') { ?>
                                        <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                    <?php } else { ?>
                                        <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                    <?php } ?>
                                </a>
                            </th>
                        <?php } ?>
                        <th style="padding: 18px 20px; text-align: left;">
                            <a href="<?= base_url('attendance/list') ?><?= $baseQuery ?>&sort=name&order=<?= ($currentSort == 'name' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                Nhân viên
                                <?php if($currentSort == 'name') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th class="hide-mobile" style="padding: 18px 20px; text-align: left;">
                            <a href="<?= base_url('attendance/list') ?><?= $baseQuery ?>&sort=dept&order=<?= ($currentSort == 'dept' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                Bộ phận
                                <?php if($currentSort == 'dept') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th style="padding: 18px 20px; text-align: center;">Thời gian</th>
                        <th class="hide-mobile" style="padding: 18px 20px; text-align: center;">
                            <a href="<?= base_url('attendance/list') ?><?= $baseQuery ?>&sort=hours&order=<?= ($currentSort == 'hours' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; justify-content: center; display: inline-flex; align-items: center; gap: 4px;">
                                Tổng giờ
                                <?php if($currentSort == 'hours') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th class="hide-mobile" style="padding: 18px 20px; text-align: center;">Vị trí</th>
                        <th style="padding: 18px 20px; text-align: center;">
                            <a href="<?= base_url('attendance/list') ?><?= $baseQuery ?>&sort=status&order=<?= ($currentSort == 'status' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; justify-content: center; display: inline-flex; align-items: center; gap: 4px;">
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
                    <?php if (empty($records)) { ?>
                        <tr><td colspan="10" style="padding: 60px; text-align: center; color: var(--apple-text-muted);">Không tìm thấy dữ liệu phù hợp.</td></tr>
                    <?php } else { ?>
                        <?php foreach($records as $row) { ?>
                            <tr style="border-bottom: 1px solid #f8f8f8;">
                                <td style="padding: 15px 20px; text-align: center;">
                                    <input type="checkbox" class="record-check" value="<?= $row['id'] ?>" style="width: 18px; height: 18px; cursor: pointer;">
                                </td>
                                <?php if (($viewType ?? 'daily') == 'monthly') { ?>
                                    <td style="padding: 15px 20px; font-weight: 600; font-size: 13px;">
                                        <?= date('d/m', strtotime($row['attendance_date'])) ?>
                                    </td>
                                <?php } ?>
                                <td style="padding: 15px 20px;">
                                    <div style="font-weight: 600; color: #1d1d1f; font-size: 14px;" title="Tên nhân viên"><?= esc($row['full_name']) ?></div>
                                    <div class="hide-mobile" style="font-size: 11px; color: var(--apple-text-muted);" title="Phòng ban làm việc"><?= esc($row['dept_name'] ?: '---') ?></div>
                                </td>
                                <td class="hide-mobile" style="padding: 15px 20px; color: var(--apple-text-muted); font-size: 13px;">
                                    <?= esc($row['dept_name'] ?: '---') ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: center;">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 12px;">
                                        <div class="attendance-time-display">
                                            <div class="check-in"><?= $row['check_in_time'] ? date('H:i', strtotime($row['check_in_time'])) : '--:--' ?></div>
                                            <div class="check-out"><?= $row['check_out_time'] ? date('H:i', strtotime($row['check_out_time'])) : '--:--' ?></div>
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
                                <td class="hide-mobile" style="padding: 15px 20px; text-align: center; font-weight: 700; color: #1d1d1f;" title="Tổng số giờ đã làm việc được tính toán tự động">
                                    <?= $row['worked_hours'] ? $row['worked_hours'] . "h" : '---' ?>
                                </td>
                                <td class="hide-mobile" style="padding: 15px 20px; text-align: center;">
                                    <?php if($row['check_in_time']) { ?>
                                        <i class="fas <?= $row['is_valid_location'] ? 'fa-check-circle' : 'fa-times-circle' ?>" style="color: <?= $row['is_valid_location'] ? '#34c759' : '#ff3b30' ?>; font-size: 16px;" title="<?= $row['is_valid_location'] ? 'Vị trí hợp lệ' : 'Sai vị trí quy định' ?>"></i>
                                    <?php } else { ?>
                                        ---
                                    <?php } ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: center;">
                                    <?php 
                                        $badgeStyle = "padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap;";
                                        if (!$row['check_in_time']) {
                                            echo "<span style='{$badgeStyle} background: #f5f5f7; color: #8e8e93;'>VẮNG</span>";
                                        } else {
                                            switch($row['status']) {
                                                case 'REGULAR':
                                                    echo "<span style='{$badgeStyle} background: #e3f9e5; color: #1a7f37;'>ĐÚNG GIỜ</span>";
                                                    break;
                                                case 'LATE':
                                                case 'EARLY_LEAVE':
                                                    echo "<span style='{$badgeStyle} background: #fff4e6; color: #d97706;'>TRỄ/SỚM</span>";
                                                    break;
                                                case 'INVALID_LOCATION':
                                                    echo "<span style='{$badgeStyle} background: #ffebeb; color: #cf222e;'>SAI VỊ TRÍ</span>";
                                                    break;
                                                default:
                                                    echo "<span style='{$badgeStyle} background: #f1f1f1; color: #666;'>{$row['status']}</span>";
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
    </div>
</div>

<!-- Bulk Actions Bar -->
<div class="bulk-actions-bar" id="bulk-bar">
    <span id="selected-count" style="font-weight: 600; font-size: 14px;">0 mục đã chọn</span>
    <div style="display: flex; gap: 12px; align-items: center;">
        <select id="bulk-status" class="form-control-premium" style="height: 40px; min-width: 180px; background-color: #333; color: white; border: none; font-size: 13px;" title="Chọn trạng thái mới cho các mục đã đánh dấu">
            <option value="">Thay đổi trạng thái...</option>
            <option value="REGULAR">Xác nhận ĐÚNG GIỜ</option>
            <option value="LATE">Đánh dấu TRỄ GIỜ</option>
            <option value="EARLY_LEAVE">Đánh dấu VỀ SỚM</option>
            <option value="LEAVE">Nghỉ CÓ PHÉP</option>
            <option value="INVALID_LOCATION">Sai VỊ TRÍ</option>
        </select>
        <button onclick="applyBulkUpdate()" class="btn-premium-sm" style="padding: 0 20px; height: 40px;" title="Áp dụng thay đổi hàng loạt">Xác nhận</button>
    </div>
</div>

<script>
// Bulk Actions Logic
const checkAll = document.getElementById('check-all');
const recordChecks = document.querySelectorAll('.record-check');
const bulkBar = document.getElementById('bulk-bar');
const selectedCount = document.getElementById('selected-count');

function updateBulkBar() {
    const checked = document.querySelectorAll('.record-check:checked');
    if (checked.length > 0) {
        bulkBar.style.display = 'flex';
        selectedCount.innerText = checked.length + ' mục đã chọn';
    } else {
        bulkBar.style.display = 'none';
    }
}

if (checkAll) {
    checkAll.addEventListener('change', function() {
        recordChecks.forEach(cb => cb.checked = checkAll.checked);
        updateBulkBar();
    });
}

recordChecks.forEach(cb => {
    cb.addEventListener('change', updateBulkBar);
});

async function applyBulkUpdate() {
    const status = document.getElementById('bulk-status').value;
    if (!status) return alert('Vui lòng chọn trạng thái mới');

    const ids = Array.from(document.querySelectorAll('.record-check:checked')).map(cb => cb.value);
    
    if (!confirm('Hệ thống sẽ cập nhật trạng thái cho ' + ids.length + ' nhân viên được chọn. Tiếp tục?')) return;

    try {
        const formData = new FormData();
        ids.forEach(id => formData.append('ids[]', id));
        formData.append('status', status);

        const response = await fetch('<?= base_url('attendance/bulk-update') ?>', {
            method: 'POST',
            body: formData
        });

        const res = await response.json();
        if (res.code === 0) {
            location.reload();
        } else {
            alert('Lỗi: ' + res.error);
        }
    } catch (err) {
        alert('Lỗi kết nối máy chủ');
    }
}
</script>
<?= $this->endSection() ?>
