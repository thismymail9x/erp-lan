<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/finance.css') ?>?v=<?= time() ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="cases-page-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Tài chính Vụ việc</h2>
            <p class="content-subtitle">Hành chính - Kế toán: Quản lý giá trị hợp đồng và tiến độ thanh toán khách hàng.</p>
        </div>
        <div class="header-actions finance-header-stats">
            <!-- Thống kê chi tiết -->
            <div class="finance-stat-badge finance-stat-total">
                <i class="fas fa-file-contract m-r-5"></i> Tổng HĐ: <span id="stat-total"><?= number_format($totalContracts, 0, ',', '.') ?>đ</span>
            </div>
            <div class="finance-stat-badge finance-stat-paid">
                <i class="fas fa-check-circle m-r-5"></i> Đã thu: <span id="stat-paid"><?= number_format($totalPaid, 0, ',', '.') ?>đ</span>
            </div>
            <div class="finance-stat-badge finance-stat-unpaid">
                <i class="fas fa-exclamation-circle m-r-5"></i> Chưa thu: <span id="stat-unpaid"><?= number_format($totalUnpaid, 0, ',', '.') ?>đ</span>
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="search-filter-wrapper m-b-16 finance-filter-container">
        <div class="finance-filter-row">
            <div class="search-input-container finance-search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="finance-search" class="input-premium" placeholder="Tìm tên vụ việc hoặc khách hàng..." value="<?= esc($filters['search'] ?? '') ?>" autocomplete="off" style="width: 100%;">
                <i class="fas fa-times clear-search finance-search-clear" id="clear-finance-search" title="Xóa tìm kiếm" style="display: none;"></i>
                <div id="finance-loader" class="finance-search-loader" style="display: none;">
                    <i class="fas fa-spinner fa-spin text-muted-dark"></i>
                </div>
            </div>

            <div class="filter-group-horizontal finance-filter-group">
                <select id="month-filter" class="input-premium">
                    <option value="">Tháng (Tất cả)</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($filters['month'] ?? 0) == $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                    <?php endfor; ?>
                </select>

                <select id="year-filter" class="input-premium">
                    <option value="">Năm</option>
                    <?php 
                    $currentYear = (int)date('Y');
                    for ($y = $currentYear - 2; $y <= $currentYear + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= (($filters['year'] ?? 0) == $y || (!($filters['year'] ?? 0) && $y == $currentYear)) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>

                <select id="payment-status-filter" class="input-premium">
                    <option value="">Trạng thái Thu (Tất cả)</option>
                    <option value="paid" <?= ($filters['payment_status'] ?? '') == 'paid' ? 'selected' : '' ?>>Đã thu đủ</option>
                    <option value="unpaid" <?= ($filters['payment_status'] ?? '') == 'unpaid' ? 'selected' : '' ?>>Chưa thu / Còn thiếu</option>
                </select>

                <button type="button" style="height: 32px" id="btn-reset-finance-filters" class="btn-secondary-sm" title="Đặt lại bộ lọc">
                    <i class="fas fa-undo"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="premium-card premium-card-full" id="finance-table-container">
        <?= view('dashboard/finance/index_table', ['cases' => $cases, 'pager' => $pager]) ?>
    </div>
</div>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/finance.js') ?>?v=<?= time() ?>"></script>
<?= $this->endSection() ?>
<?= $this->endSection() ?>
