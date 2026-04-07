<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/attendance.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="attendance-admin-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Nhật ký</h2>
            <p class="content-subtitle hide-mobile">Chuyên cần.</p>
        </div>
        <div class="header-actions-row m-b-24" style="display: flex; justify-content: flex-end;">
            <a href="<?= base_url('attendance') ?>" class="btn-premium hide-mobile" title="Giao diện cá nhân">
                <i class="fas fa-user"></i> Check-in
            </a>
        </div>
    </div>

    <!-- Attendance Filter Bar -->
    <form id="attendance-filter-form" action="<?= base_url('attendance/list') ?>" method="get" class="search-filter-bar">
        <select name="view" id="view-type" class="filter-select ajax-filter">
            <option value="daily" <?= ($viewType ?? '') == 'daily' ? 'selected' : '' ?>>Xem Ngày</option>
            <option value="monthly" <?= ($viewType ?? '') == 'monthly' ? 'selected' : '' ?>>Xem Tháng</option>
        </select>

        <?php if (($viewType ?? 'daily') == 'daily') { ?>
            <div class="search-input-group" id="date-group">
                <i class="fas fa-calendar-day"></i>
                <input type="date" name="date" value="<?= $currentDate ?>" class="ajax-filter">
            </div>
        <?php } else { ?>
            <div class="search-input-group" id="month-group">
                <i class="fas fa-calendar-alt"></i>
                <input type="month" name="month" value="<?= $currentMonth ?>" class="ajax-filter">
            </div>
        <?php } ?>
        
        <?php if (has_permission('sys.admin') || session()->get('role_name') === \Config\AppConstants::ROLE_MOD) { ?>
        <select name="department_id" id="dept-filter" class="filter-select ajax-filter">
            <option value="">Tất cả Bộ phận</option>
            <?php if (!empty($departments) && is_array($departments)) { ?>
                <?php foreach($departments as $d) { ?>
                    <option value="<?= $d['id'] ?>" <?= $currentDept == $d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                <?php } ?>
            <?php } ?>
        </select>
        <?php } ?>

        <!-- FILTER NHÂN VIÊN (SELECT2 AJAX-READY) -->
        <div style="min-width: 250px;">
            <select name="employee_id" id="employee-filter" class="filter-select ajax-filter">
                <option value="">-- Tất cả nhân viên --</option>
                <?php if (!empty($employees) && is_array($employees)) { ?>
                    <?php foreach ($employees as $emp) { ?>
                        <option value="<?= $emp['id'] ?>" <?= (isset($currentEmployee) && $currentEmployee == $emp['id']) ? 'selected' : '' ?>>
                            <?= esc($emp['full_name']) ?> (<?= esc($emp['position']) ?>)
                        </option>
                    <?php } ?>
                <?php } ?>
            </select>
        </div>

<!--        <button type="submit" class="btn-filter-submit hide-mobile">Lọc</button>-->
        <a href="<?= base_url('attendance/export') ?>?month=<?= ($viewType == 'monthly' ? $currentMonth : date('Y-m', strtotime($currentDate))) ?>" class="btn-filter-secondary">Xuất Excel</a>
    </form>
    <div class="premium-card att-card-table" id="attendance-table-container">
        <?= view('dashboard/attendance/admin_table') ?>
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

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/attendance_admin.js') ?>"></script>
<?= $this->endSection() ?>
<?= $this->endSection() ?>
