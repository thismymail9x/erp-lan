<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/employees.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="employee-list-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Nhân sự</h2>
            <p class="content-subtitle hide-mobile">Người lao động.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('employees/create') ?>" class="btn-premium" title="Thêm hồ sơ nhân sự mới vào hệ thống">
                <i class="fas fa-plus"></i> <span class="hide-mobile">Thêm</span><span class="show-mobile-only">Thêm</span>
            </a>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="search-filter-bar">
        <div class="search-input-group search-input-container">
            <i class="fas fa-search"></i>
            <input type="text" id="employee-search" placeholder="Tìm theo tên, chức vụ hoặc bộ phận..." value="<?= esc($search) ?>" autocomplete="off">
        </div>
    </div>

    <div id="employees-table-container">
        <?= view('dashboard/employees/index_table', [
            'employees'    => $employees,
            'pager'        => $pager,
            'currentSort'  => $currentSort,
            'currentOrder' => $currentOrder
        ]) ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/employees.js') ?>"></script>
<?= $this->endSection() ?>
