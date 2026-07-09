<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/attendance.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="attendance-admin-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Nh&#7853;t k&#253;</h2>
            <p class="content-subtitle hide-mobile">Chuy&#234;n c&#7847;n.</p>
        </div>
        <div class="header-actions-row m-b-24" style="display: flex; justify-content: flex-end;">
            <a href="<?= base_url('attendance') ?>" class="btn-premium hide-mobile" title="Giao di&#7879;n c&#225; nh&#226;n">
                <i class="fas fa-user"></i> Check-in
            </a>
        </div>
    </div>

    <!-- Attendance Filter Bar -->
    <form id="attendance-filter-form" action="<?= base_url('attendance/list') ?>" method="get" class="search-filter-bar filter-bar">
        <select name="view" id="view-type" class="filter-select ajax-filter">
            <option value="daily" <?= ($viewType ?? '') == 'daily' ? 'selected' : '' ?>>Xem Ng&#224;y</option>
            <option value="monthly" <?= ($viewType ?? '') == 'monthly' ? 'selected' : '' ?>>Xem Th&#225;ng</option>
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

        <?php if (has_permission('sys.admin') || session()->get('role_name') === \Config\AppConstants::ROLE_MOD || has_permission('attendance.view_all')) { ?>
            <select name="department_id" id="dept-filter" class="filter-select ajax-filter">
                <option value="">T&#7845;t c&#7843; B&#7897; ph&#7853;n</option>
                <?php if (!empty($departments) && is_array($departments)) { ?>
                    <?php foreach($departments as $d) { ?>
                        <option value="<?= $d['id'] ?>" <?= $currentDept == $d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                    <?php } ?>
                <?php } ?>
            </select>
        <?php } ?>

        <div style="min-width: 250px;">
            <select name="employee_id" id="employee-filter" class="filter-select ajax-filter">
                <option value="">-- T&#7845;t c&#7843; nh&#226;n vi&#234;n --</option>
                <?php if (!empty($employees) && is_array($employees)) { ?>
                    <?php foreach ($employees as $emp) { ?>
                        <option value="<?= $emp['id'] ?>" <?= (isset($currentEmployee) && $currentEmployee == $emp['id']) ? 'selected' : '' ?>>
                            <?= esc($emp['full_name']) ?> (<?= esc($emp['position']) ?>)
                        </option>
                    <?php } ?>
                <?php } ?>
            </select>
        </div>

        <!-- <button type="submit" class="btn-filter-submit hide-mobile">L&#7885;c</button> -->
        <a href="<?= base_url('attendance/export') ?>?month=<?= ($viewType == 'monthly' ? $currentMonth : date('Y-m', strtotime($currentDate))) ?>" class="btn-filter-secondary">Xu&#7845;t Excel</a>
    </form>

    <div class="premium-card att-card-table" id="attendance-table-container">
        <?= view('dashboard/attendance/admin_table') ?>
    </div>
</div>

<!-- Bulk Actions Bar -->
<div class="bulk-actions-bar" id="bulk-bar">
    <span id="selected-count" style="font-weight: 600; font-size: 14px;">0 m&#7909;c &#273;&#227; ch&#7885;n</span>
    <div>
        <select id="bulk-status" class="form-control-premium" title="Ch&#7885;n tr&#7841;ng th&#225;i m&#7899;i cho c&#225;c m&#7909;c &#273;&#227; &#273;&#225;nh d&#7845;u">
            <option value="">Thay &#273;&#7893;i tr&#7841;ng th&#225;i...</option>
            <optgroup label="&#272;i l&#224;m">
                <option value="REGULAR">&#272;&#250;ng gi&#7901;</option>
                <option value="LATE">Tr&#7877; / V&#7873; s&#7899;m</option>
            </optgroup>
            <optgroup label="Ngh&#7881; ph&#233;p">
                <option value="LEAVE_MORNING">Ngh&#7881; bu&#7893;i s&#225;ng (0.5 c&#244;ng)</option>
                <option value="LEAVE_AFTERNOON">Ngh&#7881; bu&#7893;i chi&#7873;u (0.5 c&#244;ng)</option>
                <option value="LEAVE_FULL_DAY">Ngh&#7881; c&#7843; ng&#224;y (1 c&#244;ng ngh&#7881;)</option>
            </optgroup>
        </select>
    </div>
    <button type="button" class="btn-premium js-apply-bulk-update" style="padding: 0 20px; height: 40px;" title="&#193;p d&#7909;ng thay &#273;&#7893;i h&#224;ng lo&#7841;t">X&#225;c nh&#7853;n</button>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/attendance_admin.js') ?>"></script>
<?= $this->endSection() ?>
