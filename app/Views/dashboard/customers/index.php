<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customers.css') ?>">
<?= $this->endSection() ?>
<?php $tagService = new \App\Services\TagService(); ?>

<?= $this->section('content') ?>
<div class="customers-page-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Khách hàng</h2>
            <p class="content-subtitle hide-mobile">CRM</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('customers/create') ?>" class="btn-premium" title="Thêm khách hàng">
                <i class="fas fa-plus-circle"></i> Thêm
            </a>
        </div>
    </div>

    <!-- 
        CRM Stats Row:
        Bảng điều khiển các chỉ số kinh doanh chính (KPIs) của module CRM.
        Dữ liệu được lấy từ CustomerService để đảm bảo tính thời gian thực.
    -->
    <div class="stats-grid-premium">
        <!-- Tổng số khách hàng hiện có trong database -->
        <div class="stat-card-premium" title="Tổng số khách hàng đã đăng ký">
            <div class="stat-icon-wrapper stat-icon-blue">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div class="stat-label">Tổng</div>
                <div class="stat-value"><?= $stats['total_customers'] ?></div>
            </div>
        </div>
        <div class="stat-card-premium" title="Khách mới tháng này">
            <div class="stat-icon-wrapper stat-icon-green">
                <i class="fas fa-user-plus"></i>
            </div>
            <div>
                <div class="stat-label">Mới</div>
                <div class="stat-value"><?= $stats['new_this_month'] ?></div>
            </div>
        </div>
        <div class="stat-card-premium" title="Doanh nghiệp">
            <div class="stat-icon-wrapper stat-icon-orange">
                <i class="fas fa-building"></i>
            </div>
            <div>
                <div class="stat-label">Cty</div>
                <div class="stat-value"><?= $stats['total_corporate'] ?? 0 ?></div>
            </div>
        </div>
        <div class="stat-card-premium" title="Khách hàng VIP">
            <div class="stat-icon-wrapper stat-icon-purple">
                <i class="fas fa-crown"></i>
            </div>
            <div>
                <div class="stat-label">VIP</div>
                <div class="stat-value"><?= $stats['total_vip'] ?? 0 ?></div>
            </div>
        </div>
    </div>
    <form id="customer-filter-form" action="<?= base_url('customers') ?>" method="get" class="search-filter-bar">
        <!-- Ô tìm kiếm -->
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Tìm tên, SĐT, CCCD, MST..." value="<?= esc(service('request')->getGet('q')) ?>" class="ajax-filter-search">
        </div>

        <!-- Loại khách -->
        <select name="type" class="filter-select ajax-filter">
            <option value="">Tất cả loại khách</option>
            <option value="ca_nhan" <?= service('request')->getGet('type') == 'ca_nhan' ? 'selected' : '' ?>>Cá nhân/Hộ</option>
            <option value="doanh_nghiep" <?= service('request')->getGet('type') == 'doanh_nghiep' ? 'selected' : '' ?>>Doanh nghiệp</option>
        </select>

        <!-- Lọc theo Tag -->
        <select name="tag_id" class="filter-select ajax-filter">
            <option value="">Tất cả nhãn (Tags)</option>
            <?php foreach ($availableTags as $tag) { ?>
                <option value="<?= $tag['id'] ?>" <?= service('request')->getGet('tag_id') == $tag['id'] ? 'selected' : '' ?>>
                    <?= esc($tag['name']) ?>
                </option>
            <?php } ?>
        </select>

<!--        <button type="submit" class="btn-filter-submit">-->
<!--            <i class="fas fa-filter"></i> Lọc-->
<!--        </button>-->

        <?php if (service('request')->getUri()->getQuery() !== '') { ?>
            <a href="<?= base_url('customers') ?>" class="btn-filter-secondary">Xóa lọc</a>
        <?php } ?>
    </form>

    <!-- Filter & Table: Công cụ tìm kiếm và bảng dữ liệu chính -->
    <div class="premium-card premium-card-full" id="customer-table-container">
        <?= view('dashboard/customers/index_table') ?>
    </div>

    <!-- Phân trang hệ thống -->
    <div class="pagination-wrapper p-20 m-t-16">
        <?= $pager->links() ?>
    </div>
</div>
</div>

<!-- Modal: Quick Tagging -->
<div id="quickTagModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1100; align-items:center; justify-content:center;">
    <div class="premium-card p-24" style="width:400px;">
        <div class="flex-row justify-between align-center m-b-20">
            <h3 class="section-header-title">Gắn nhãn nhanh</h3>
            <span class="close-btn-minimal" onclick="document.getElementById('quickTagModal').style.display='none'">&times;</span>
        </div>
        <p class="text-sm m-b-15">Khách hàng: <strong id="quickTagName">--</strong></p>
        <form id="quickTagForm" class="flex-column gap-15">
            <input type="hidden" name="entity_id" id="quickTagEntityId">
            <input type="hidden" name="entity_type" value="customers">
            <div class="form-group-premium">
                <label class="label-premium">Lựa chọn nhãn dán</label>
                <select name="tag_ids[]" id="quickTagSelect" class="form-control-premium" multiple="multiple" style="width: 100%;">
                    <?php if (isset($availableTags)) { 
                        foreach ($availableTags as $tag) { ?>
                            <option value="<?= $tag['id'] ?>"><?= esc($tag['name']) ?></option>
                        <?php } 
                    } ?>
                </select>
            </div>
            <div class="form-actions-row m-t-15" style="justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('quickTagModal').style.display='none'">Hủy</button>
                <button type="submit" class="btn-premium">Cập nhật ngay</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/customers_index.js') ?>"></script>
<?= $this->endSection() ?>
