<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/kpi.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="header-title-container">
    <div class="kpi-header-title">
        <h1>B&#225;o c&#225;o KPI t&#432; v&#7845;n</h1>
        <p>KPI c&#225; nh&#226;n theo doanh thu th&#7921;c thu th&#225;ng. KPI chu&#7849;n t&#7889;i &#273;a <?= number_format($targetReward) ?> VN&#272;, ch&#432;a bao g&#7891;m th&#432;&#7903;ng v&#432;&#7907;t m&#7889;c.</p>
    </div>
</div>

<form id="kpiFilterForm" class="kpi-filters-bar filter-bar" data-kpi-url="<?= base_url('kpi/consulting') ?>">
    <div class="filter-group">
        <input type="text" name="search" id="filterSearch" class="filter-input filter-input-wide" placeholder="T&#234;n nh&#226;n vi&#234;n..." value="<?= esc($filters['search'] ?? '') ?>">
    </div>

    <div class="filter-group">
        <input type="month" name="month" id="filterMonth" class="filter-input" value="<?= esc($filters['month'] ?? date('Y-m')) ?>">
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
                <th>H&#7891; s&#417; ch&#7889;t</th>
                <th>Doanh thu th&#7921;c thu</th>
                <th>M&#7889;c KPI</th>
                <th>KPI chu&#7849;n</th>
                <th>Tr&#7843; th&#225;ng 40%</th>
                <th>T&#237;ch l&#361;y n&#259;m 60%</th>
                <th>Th&#432;&#7903;ng v&#432;&#7907;t m&#7889;c</th>
                <th>Tr&#7843; k&#7923; l&#432;&#417;ng t&#7899;i</th>
                <th>Ti&#7871;n &#273;&#7897;</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="kpiTableBody">
            <?= view('dashboard/kpi/consulting_table_partial', ['stats' => $stats]) ?>
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
