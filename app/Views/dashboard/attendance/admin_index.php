<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/attendance.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="attendance-admin-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Nhật ký</h2>
            <p class="content-subtitle hide-mobile">Quản lý chuyên cần.</p>
        </div>
    </div>

    <!-- Attendance Filter Bar -->
    <form action="<?= base_url('attendance/list') ?>" method="get" class="search-filter-bar">
        <select name="view" class="filter-select" onchange="this.form.submit()">
            <option value="daily" <?= ($viewType ?? '') == 'daily' ? 'selected' : '' ?>>Theo ngày</option>
            <option value="monthly" <?= ($viewType ?? '') == 'monthly' ? 'selected' : '' ?>>Theo tháng</option>
        </select>

        <?php if (($viewType ?? 'daily') == 'daily') { ?>
            <div class="search-input-group">
                <i class="fas fa-calendar-day"></i>
                <input type="date" name="date" value="<?= $currentDate ?>" onchange="this.form.submit()">
            </div>
        <?php } else { ?>
            <div class="search-input-group">
                <i class="fas fa-calendar-alt"></i>
                <input type="month" name="month" value="<?= $currentMonth ?>" onchange="this.form.submit()">
            </div>
        <?php } ?>
        
        <select name="department_id" class="filter-select" onchange="this.form.submit()">
            <option value="">Tất cả phòng ban</option>
            <?php if (!empty($departments) && is_array($departments)) { ?>
                <?php foreach($departments as $d) { ?>
                    <option value="<?= $d['id'] ?>" <?= $currentDept == $d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                <?php } ?>
            <?php } ?>
        </select>

        <button type="submit" class="btn-filter-submit">Lọc</button>
        <a href="<?= base_url('attendance/export') ?>?month=<?= ($viewType == 'monthly' ? $currentMonth : date('Y-m', strtotime($currentDate))) ?>" class="btn-filter-secondary">Xuất Excel</a>
    </form>
    <div class="header-actions-row m-b-24" style="display: flex; justify-content: flex-end;">
        <a href="<?= base_url('attendance') ?>" class="btn-premium-sm hide-mobile" title="Chuyển sang giao diện chấm công cá nhân">
            <i class="fas fa-camera"></i> Chấm công tôi
        </a>
    </div>

    <div class="premium-card att-card-table">
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
                    <tr class="att-table-header-row">
                        <th class="att-table-th-center" style="width: 40px;">
                            <input type="checkbox" id="check-all" style="width: 18px; height: 18px; cursor: pointer;">
                        </th>
                        <?php if (($viewType ?? 'daily') == 'monthly') { ?>
                            <th class="att-table-th" style="width: 100px;">
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
                        <th class="att-table-th">
                            <a href="<?= base_url('attendance/list') ?><?= $baseQuery ?>&sort=name&order=<?= ($currentSort == 'name' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                Nhân viên
                                <?php if($currentSort == 'name') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th class="hide-mobile att-table-th">
                            <a href="<?= base_url('attendance/list') ?><?= $baseQuery ?>&sort=dept&order=<?= ($currentSort == 'dept' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                Bộ phận
                                <?php if($currentSort == 'dept') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th class="att-table-th-center">Thời gian</th>
                        <th class="hide-mobile att-table-th-center">
                            <a href="<?= base_url('attendance/list') ?><?= $baseQuery ?>&sort=hours&order=<?= ($currentSort == 'hours' && $currentOrder == 'asc') ? 'desc' : 'asc' ?>" style="color: inherit; text-decoration: none; justify-content: center; display: inline-flex; align-items: center; gap: 4px;">
                                Tổng giờ
                                <?php if($currentSort == 'hours') { ?>
                                    <i class="fas fa-sort-<?= $currentOrder == 'asc' ? 'up' : 'down' ?>"></i>
                                <?php } else { ?>
                                    <i class="fas fa-sort" style="opacity: 0.3;"></i>
                                <?php } ?>
                            </a>
                        </th>
                        <th class="hide-mobile att-table-th-center">Vị trí</th>
                        <th class="att-table-th-center">
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
                    <?php if (empty($records) || !is_array($records)) { ?>
                        <tr><td colspan="10" style="padding: 60px; text-align: center; color: var(--apple-text-muted);">Không tìm thấy dữ liệu phù hợp.</td></tr>
                    <?php } else { ?>
                        <?php foreach($records as $row) { ?>
                            <tr style="border-bottom: 1px solid #f8f8f8;">
                                <td class="att-table-td-center">
                                    <input type="checkbox" class="record-check" value="<?= $row['id'] ?>" style="width: 18px; height: 18px; cursor: pointer;">
                                </td>
                                <?php if (($viewType ?? 'daily') == 'monthly') { ?>
                                    <td class="att-table-td att-date-main">
                                        <?= isset($row['attendance_date']) ? date('d/m', strtotime($row['attendance_date'])) : '--' ?>
                                    </td>
                                <?php } ?>
                                <td class="att-table-td">
                                    <a href="<?= base_url('attendance/list') ?>?view=monthly&month=<?= date('Y-m', strtotime($row['attendance_date'] ?: $currentDate)) ?>&employee_id=<?= $row['emp_id'] ?>" class="att-emp-link" title="Xem lịch sử chấm công tháng của <?= esc($row['full_name']) ?>">
                                        <?= esc($row['full_name']) ?>
                                    </a>
                                    <div class="hide-mobile att-emp-dept" title="Phòng ban làm việc"><?= esc($row['dept_name'] ?: '---') ?></div>
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
                                                    echo "<span class='att-badge-base att-badge-late'>TRỄ/SỚM</span>";
                                                    break;
                                                case 'INVALID_LOCATION':
                                                    echo "<span class='att-badge-base att-badge-invalid'>SAI VỊ TRÍ</span>";
                                                    break;
                                                default:
                                                    echo "<span class='att-badge-base att-badge-neutral'>{$row['status']}</span>";
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
/**
 * L.A.N ERP - Quản lý Chuyên cần (Admin)
 * Xử lý các thao tác hàng loạt, cập nhật trạng thái và xem ảnh minh chứng.
 */

// 1. Khởi tạo các phần tử DOM phục vụ thao tác hàng loạt (Bulk Actions)
const checkAll = document.getElementById('check-all');
const recordChecks = document.querySelectorAll('.record-check');
const bulkBar = document.getElementById('bulk-bar');
const selectedCount = document.getElementById('selected-count');

/**
 * Cập nhật hiển thị của thanh công cụ hàng loạt.
 * Hiển thị thanh bar nếu có ít nhất 1 mục được chọn, ngược lại thì ẩn.
 */
function updateBulkBar() {
    const checked = document.querySelectorAll('.record-check:checked');
    if (checked.length > 0) {
        bulkBar.style.display = 'flex';
        selectedCount.innerText = checked.length + ' mục đã chọn';
    } else {
        bulkBar.style.display = 'none';
    }
}

// 2. Lắng nghe sự kiện Check-All (Chọn tất cả)
if (checkAll) {
    checkAll.addEventListener('change', function() {
        // Đồng bộ trạng thái của tất cả checkbox theo checkbox "Check All"
        recordChecks.forEach(cb => cb.checked = checkAll.checked);
        updateBulkBar();
    });
}

// 3. Lắng nghe thay đổi trên từng Checkbox dòng dữ liệu
recordChecks.forEach(cb => {
    cb.addEventListener('change', updateBulkBar);
});

/**
 * 4. Hàm thực thi Cập nhật trạng thái hàng loạt.
 * Thu thập các ID đã chọn, trạng thái mới và gửi via AJAX (POST).
 */
async function applyBulkUpdate() {
    const status = document.getElementById('bulk-status').value;
    if (!status) return alert('Vui lòng chọn trạng thái mới để cập nhật.');

    const ids = Array.from(document.querySelectorAll('.record-check:checked')).map(cb => cb.value);
    
    // Xác nhận từ quản trị viên trước khi thực hiện
    if (!confirm('Hệ thống sẽ cập nhật trạng thái cho ' + ids.length + ' nhân viên được chọn. Tiếp tục?')) return;

    try {
        const formData = new FormData();
        ids.forEach(id => formData.append('ids[]', id));
        formData.append('status', status);

        // Gửi yêu cầu cập nhật lên Server
        const response = await fetch('<?= base_url('attendance/bulk-update') ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const res = await response.json();
        if (res.code === 0) {
            // Reload trang để cập nhật giao diện sau khi thành công
            location.reload();
        } else {
            alert('Lỗi từ máy chủ: ' + res.error);
        }
    } catch (err) {
        alert('Lỗi kết nối máy chủ: Vui lòng kiểm tra lại đường truyền mạng.');
    }
}

/**
 * 5. Tiện ích Xem trước hình ảnh minh chứng.
 * Mở ảnh trong tab mới để xem chi tiết khuôn mặt hoặc vị trí chụp.
 */
function previewImage(src) {
    if (src) {
        window.open(src, '_blank', 'noopener,noreferrer');
    }
}
</script>
<?= $this->endSection() ?>
