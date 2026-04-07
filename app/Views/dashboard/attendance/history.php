<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/attendance.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="attendance-history-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title"><?php echo (isset($isViewingOthers) && $isViewingOthers) ? $title : 'Lịch sử chấm công'; ?></h2>
            <p class="content-subtitle hide-mobile">Bảng công chi tiết tháng <?= date('m/Y', strtotime($currentMonth . '-01')) ?></p>
        </div>
        
        <div class="header-back-btn">
            <form action="<?= base_url('attendance/list') ?>" method="get" style="display: inline-block; vertical-align: middle; margin-right: 12px;">
                <input type="hidden" name="view" value="monthly">
                <?php if (isset($targetEmployeeId)) { ?>
                    <input type="hidden" name="employee_id" value="<?= $targetEmployeeId ?>">
                <?php } ?>
                <input type="month" name="month" value="<?= $currentMonth ?>" class="form-control-premium" onchange="this.form.submit()" style="height: 38px; padding: 0 12px;">
            </form>
            <?php if (isset($isViewingOthers) && $isViewingOthers) { ?>
                <a href="<?= base_url('attendance/list') ?>" class="btn-secondary-sm">
                    <i class="fas fa-arrow-left"></i>&nbsp; Quay lại
                </a>
            <?php } else { ?>
                <a href="<?= base_url('attendance') ?>" class="btn-premium-sm">
                    <i class="fas fa-camera"></i>&nbsp; Check-in
                </a>
            <?php } ?>
        </div>
    </div>

    <div class="premium-card premium-card-full" id="history-table-container">
        <?= view('dashboard/attendance/history_table') ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/attendance_history.js') ?>"></script>
<?= $this->endSection() ?>
