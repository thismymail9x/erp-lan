<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/contacts.css') ?>">
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<?= $this->endSection() ?>

<?php 
    $isAdmin = has_permission('contact.admin'); 
?>

<?= $this->section('content') ?>

<div class="contacts-page-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Danh bạ liên hệ</h2>
            <p class="content-subtitle">Tra cứu nhanh thông tin liên lạc các cơ quan, đơn vị và cá nhân.</p>
        </div>
        <div class="header-controls">
            <?php if (has_permission('contact.create')) { ?>
            <button class="btn-premium" onclick="openContactModal()">
                <i class="fas fa-plus-circle"></i> Thêm liên hệ
            </button>
            <?php } ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="contacts-filter-bar">
        <div class="search-input-container filter-search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="contact-search" class="input-premium" placeholder="Tìm tên, số điện thoại, chức vụ..." value="<?= esc($filters['search'] ?? '') ?>">
            <i class="fas fa-times clear-search" id="clear-search" title="Xóa tìm kiếm" style="display: none;"></i>
            <div class="search-loader" id="search-loader" style="display: none;"></div>
        </div>
        
        <div class="filter-group-inline">
            <select id="source-filter" class="form-control-premium filter-select-source">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($sources as $source) { ?>
                    <option value="<?= $source ?>" <?= ($filters['source'] ?? '') == $source ? 'selected' : '' ?>><?= $source ?></option>
                <?php } ?>
            </select>
            
            <select id="province-filter" class="form-control-premium filter-select-province">
                <option value="">Tỉnh/thành</option>
                <?php foreach ($provinces as $p) { ?>
                    <option value="<?= $p ?>" <?= ($filters['province'] ?? '') == $p ? 'selected' : '' ?>><?= $p ?></option>
                <?php } ?>
            </select>

            <?php if ($isAdmin) { ?>
            <select id="private-filter" class="form-control-premium filter-select-private">
                <option value="">Trạng thái</option>
                <option value="0" <?= ($filters['is_private'] ?? '') === '0' ? 'selected' : '' ?>>Công khai</option>
                <option value="1" <?= ($filters['is_private'] ?? '') === '1' ? 'selected' : '' ?>>Riêng tư</option>
            </select>
            <?php } ?>

            <button type="button" id="btn-reset-filters" class="btn-secondary-sm" title="Đặt lại bộ lọc" style="flex: 0 0 32px; height: 32px; border-radius: 8px;">
                <i class="fas fa-undo"></i>
            </button>
        </div>
    </div>

    <!-- Contact Table -->
    <div class="premium-card premium-card-full" id="contact-table-container">
        <?= view('dashboard/contacts/index_table', [
            'contacts' => $contacts,
            'pager'    => $pager,
            'isAdmin'  => $isAdmin,
            'sources'  => $sources
        ]) ?>
    </div>
</div>

<!-- Batch Action Bar -->
<div id="contact-batch-bar" class="batch-action-bar">
    <div class="flex-row align-center gap-10">
        <i class="fas fa-check-circle text-apple-green"></i>
        <span>Đã chọn <strong id="selected-count">0</strong> liên hệ</span>
    </div>
    <div class="flex-row gap-10">
        <?php if ($isAdmin) { ?>
        <button class="btn-minimal-white" onclick="handleBatchAction(1)">
            <i class="fas fa-lock"></i> Gắn Private
        </button>
        <button class="btn-minimal-white" onclick="handleBatchAction(0)">
            <i class="fas fa-unlock"></i> Bỏ Private
        </button>
        <?php } ?>
    </div>
</div>

<!-- Contact Modal -->
<div id="contactModal" class="modal-overlay">
    <div class="modal-content-premium">
        <div class="flex-row justify-between align-center m-b-20">
            <h3 id="modal-title" class="section-header-title">Thêm liên hệ</h3>
            <span class="close-btn-minimal" onclick="closeContactModal()">&times;</span>
        </div>
        
        <form id="contact-form" class="flex-column gap-15">
            <?= csrf_field() ?>
            <div class="grid-2 gap-15">
                <div class="form-group-premium">
                    <label class="label-premium">Tên đơn vị / Người phụ trách <span class="text-red">*</span></label>
                    <input type="text" name="unit_name" class="input-premium" required>
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Số điện thoại</label>
                    <input type="text" name="phone" class="input-premium">
                </div>
            </div>
            
            <div class="grid-2 gap-15">
                <div class="form-group-premium">
                    <label class="label-premium">Chức vụ / Chức danh</label>
                    <input type="text" name="position" class="input-premium">
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Tỉnh / Khu vực</label>
                    <select name="province" class="form-control-premium">
                        <option value="">-- Chọn tỉnh thành --</option>
                        <?php foreach ($provinces as $p) { ?>
                            <option value="<?= $p ?>"><?= $p ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="grid-2 gap-15">
                <div class="form-group-premium">
                    <label class="label-premium">Địa chỉ / Cơ quan</label>
                    <input type="text" name="address" class="input-premium">
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Danh mục</label>
                    <select name="source" class="form-control-premium">
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($sources as $source) { ?>
                            <option value="<?= $source ?>"><?= $source ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="form-group-premium">
                <label class="label-premium">Địa bàn / Phạm vi quản lý</label>
                <textarea name="area" class="input-premium" rows="1"></textarea>
            </div>

            <div class="grid-2 gap-15">
                <div class="form-group-premium">
                    <label class="label-premium">Đơn vị tổ chức lại</label>
                    <input type="text" name="reorganized_unit" class="input-premium">
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Ghi chú</label>
                    <input type="text" name="notes" class="input-premium">
                </div>
            </div>

            <?php if ($isAdmin) { ?>
            <div class="form-group-premium checkbox-group-inline">
                <input type="checkbox" name="is_private" id="is_private_checkbox" value="1">
                <label for="is_private_checkbox" class="label-premium">Riêng tư (Chỉ Admin xem được SĐT và chỉnh sửa)</label>
            </div>
            <?php } ?>

            <div class="form-actions-row m-t-20 form-actions-container">
                <button type="button" class="btn-secondary" onclick="closeContactModal()">Hủy bỏ</button>
                <button type="submit" class="btn-premium">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/contacts.js') ?>?v=<?= time() ?>"></script>
<?= $this->endSection() ?>
