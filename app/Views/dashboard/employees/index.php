<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/employees.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="employee-list-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Nh&#226;n s&#7921;</h2>
            <p class="content-subtitle hide-mobile">Ng&#432;&#7901;i lao &#273;&#7897;ng.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('employees/create') ?>" class="btn-premium" title="Th&#234;m h&#7891; s&#417; nh&#226;n s&#7921; m&#7899;i v&#224;o h&#7879; th&#7889;ng">
                <i class="fas fa-plus"></i> <span class="hide-mobile">Th&#234;m</span><span class="show-mobile-only">Th&#234;m</span>
            </a>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="search-filter-bar">
        <div class="search-input-group search-input-container">
            <i class="fas fa-search"></i>
            <input type="text" id="employee-search" placeholder="T&#236;m theo t&#234;n, ch&#7913;c v&#7909; ho&#7863;c b&#7897; ph&#7853;n..." value="<?= esc($search) ?>" autocomplete="off">
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
