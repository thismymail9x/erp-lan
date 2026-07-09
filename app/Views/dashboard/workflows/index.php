<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/workflows.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="workflow-index-container">
    <div class="dashboard-header-wrapper m-b-24">
        <div class="header-title-container">
            <h2 class="content-title">Quản lý Quy trình mẫu</h2>
            <p class="content-subtitle">Thiết lập các giai đoạn chuẩn cho từng quy trình nghiệp vụ.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('workflows/create') ?>" class="btn-premium">
                <i class="fas fa-plus"></i> Tạo quy trình mới
            </a>
        </div>
    </div>

    <!-- SEARCH & FILTER BAR -->
    <form id="workflow-filter-form" action="<?= base_url('workflows') ?>" method="get" class="search-filter-bar filter-bar">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Tìm tên quy trình hoặc mã..." value="<?= esc(service('request')->getGet('q')) ?>" class="ajax-filter-search">
        </div>

        <select name="status" class="filter-select ajax-filter">
            <option value="">Tất cả trạng thái</option>
            <option value="1" <?= service('request')->getGet('status') === '1' ? 'selected' : '' ?>>Đang hoạt động</option>
            <option value="0" <?= service('request')->getGet('status') === '0' ? 'selected' : '' ?>>Tạm ngưng</option>
        </select>

        <?php if (service('request')->getUri()->getQuery() !== '') { ?>
            <a href="<?= base_url('workflows') ?>" class="btn-filter-secondary">Xóa lọc</a>
        <?php } ?>
    </form>

    <div class="workflow-grid" id="workflow-grid-container">
        <?= view('dashboard/workflows/index_grid') ?>
    </div>
</div>


<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/workflows_index.js') ?>"></script>
<?= $this->endSection() ?>
