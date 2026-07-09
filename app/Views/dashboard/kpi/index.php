<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/kpi.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="header-title-container">
    <div class="kpi-header-title">
        <h1>B&#225;o c&#225;o Hi&#7879;u su&#7845;t (KPI)</h1>
        <p>Theo d&#245;i ti&#7871;n &#273;&#7897; th&#432;&#7903;ng v&#224; kh&#7889;i l&#432;&#7907;ng c&#244;ng vi&#7879;c c&#7911;a to&#224;n b&#7897; nh&#226;n vi&#234;n.</p>
    </div>
</div>

<form id="kpiFilterForm" class="kpi-filters-bar filter-bar" data-kpi-url="<?= base_url('kpi') ?>">
    <div class="filter-group">
        <input type="text" name="search" id="filterSearch" class="filter-input filter-input-wide" placeholder="T&#234;n nh&#226;n vi&#234;n..." value="<?= esc($filters['search'] ?? '') ?>">
    </div>

    <div class="filter-group">
        <select name="year" id="filterYear" class="filter-input">
            <?php
            $startYear = 2026;
            $endYear = max(date('Y') + 1, 2027);
            for ($y = $startYear; $y <= $endYear; $y++) { ?>
                <option value="<?= $y ?>" <?= ($filters['year'] ?? date('Y')) == $y ? 'selected' : '' ?>>N&#259;m <?= $y ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="filter-group">
        <select name="department_id" id="filterDept" class="filter-input">
            <option value="">-- T&#7845;t c&#7843; b&#7897; ph&#7853;n --</option>
            <?php foreach ($departments as $dept) { ?>
                <option value="<?= $dept['id'] ?>" <?= ($filters['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                    <?= esc($dept['name']) ?>
                </option>
            <?php } ?>
        </select>
    </div>
</form>

<div class="kpi-table-container">
    <table class="kpi-table">
        <thead>
            <tr>
                <th>Nh&#226;n vi&#234;n</th>
                <th>Ph&#242;ng ban</th>
                <th>&#272;&#227; &#273;&#7841;t (VN&#272;)</th>
                <th>Ti&#7873;m n&#259;ng (VN&#272;)</th>
                <th>B&#7887; l&#7905; (VN&#272;)</th>
                <th>T&#7893;ng th&#432;&#7903;ng</th>
                <th>T&#7881; l&#7879; ho&#224;n th&#224;nh</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="kpiTableBody">
            <?= view('dashboard/kpi/table_partial', ['stats' => $stats]) ?>
        </tbody>
    </table>
    <div id="kpiLoading" class="kpi-loading">
        <i class="fas fa-circle-notch fa-spin"></i>
        <p>&#272;ang c&#7853;p nh&#7853;t d&#7919; li&#7879;u...</p>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/kpi.js') ?>"></script>
<?= $this->endSection() ?>
