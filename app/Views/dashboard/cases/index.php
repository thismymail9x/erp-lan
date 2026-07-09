<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/cases.css') ?>?v=20260708">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="cases-page-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Quản lý vụ việc</h2>
            <p class="content-subtitle hide-mobile">Theo dõi và xử lý các hồ sơ pháp lý của khách hàng.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('cases/create') ?>" class="btn-premium" title="Khởi tạo một vụ việc hoặc hồ sơ pháp lý mới">
                <i class="fas fa-plus"></i> Thêm vụ việc
            </a>
        </div>
    </div>

    <!-- Stats Row -->
    <!-- 
        Khối Thống kê (Stats Row):
        Hiển thị 4 chỉ số quan trọng nhất giúp Quản lý và Nhân viên nắm bắt nhanh khối lượng công việc.
        Bao gồm: Tổng số vụ, Đang xử lý, Hoàn thành trong tháng, và các Bước bị quá hạn.
    -->
    <div class="stats-grid-premium case-stats-grid">
        <!-- Card: Tổng số vụ việc hồ sơ trong hệ thống (Dựa theo quyền truy cập) -->
        <div class="stat-card-premium pointer case-stat-filter" data-status="" title="Tổng số vụ việc/hồ sơ pháp lý bạn có quyền truy cập">
            <div class="stat-icon-wrapper stat-icon-blue">
                <i class="fas fa-folder"></i>
            </div>
            <div>
                <div class="stat-label">Tổng số vụ việc</div>
                <div class="stat-value"><?= $stats['total'] ?></div>
            </div>
        </div>
        <!-- Vụ việc đang trong quá trình xử lý -->
        <div class="stat-card-premium pointer case-stat-filter" data-status="dang_xu_ly" title="Số lượng vụ việc đang trung các bước thực hiện">
            <div class="stat-icon-wrapper stat-icon-orange">
                <i class="fas fa-spinner"></i>
            </div>
            <div>
                <div class="stat-label">Đang xử lý</div>
                <div class="stat-value"><?= $stats['processing'] ?? 0 ?></div>
            </div>
        </div>
        <!-- Vụ việc Chờ tiếp nhận -->
        <div class="stat-card-premium pointer case-stat-filter" data-status="cho_tiep_nhan" title="Số lượng vụ việc mới khởi tạo, đang chờ tiếp nhận">
            <div class="stat-icon-wrapper stat-icon-blue stat-icon-waiting">
                <i class="fas fa-hourglass-start"></i>
            </div>
            <div>
                <div class="stat-label">Chờ tiếp nhận</div>
                <div class="stat-value"><?= $stats['waiting'] ?? 0 ?></div>
            </div>
        </div>
        <!-- Vụ việc tạm dừng -->
        <div class="stat-card-premium pointer case-stat-filter" data-status="tam_dung" title="Số lượng vụ việc đang tạm dừng">
            <div class="stat-icon-wrapper stat-icon-paused">
                <i class="fas fa-pause-circle"></i>
            </div>
            <div>
                <div class="stat-label">Tạm dừng</div>
                <div class="stat-value"><?= $stats['paused'] ?? 0 ?></div>
            </div>
        </div>
        <!-- Vụ việc đã hoàn thành (Tổng số) -->
        <div class="stat-card-premium pointer case-stat-filter" data-status="da_hoan_thanh" title="Số lượng vụ việc đã hoàn thành từ trước đến nay">
            <div class="stat-icon-wrapper stat-icon-green">
                <i class="fas fa-check-double"></i>
            </div>
            <div>
                <div class="stat-label">Đã hoàn thành</div>
                <div class="stat-value"><?= $stats['completed'] ?? 0 ?></div>
            </div>
        </div>
        <!-- Vụ việc có bước bị quá hạn -->
        <div class="stat-card-premium pointer case-stat-filter" data-status="overdue" title="Cảnh báo: Các vụ việc có bước công việc đã quá hạn chót">
            <div class="stat-icon-wrapper stat-icon-purple stat-icon-overdue">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <div class="stat-label case-overdue-label">Quá hạn</div>
                <div class="stat-value text-apple-red"><?= $stats['overdue'] ?? 0 ?></div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="search-filter-wrapper filter-bar">
        <div class="search-input-container case-search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="case-search" class="input-premium" placeholder="Tìm tên vụ việc, mã hoặc khách hàng..." value="<?= esc($search) ?>" autocomplete="off">
        </div>
        
        <div class="filter-select-container case-filter-status">
            <select id="status-filter" name="status" class="form-control-premium">
                <option value="">Trạng thái</option>
                <?php foreach ($statusLabels as $val => $label) { ?>
                    <option value="<?= $val ?>" <?= $currentStatus == $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php } ?>
                <option value="overdue" <?= $currentStatus == 'overdue' ? 'selected' : '' ?>>Có bước quá hạn</option>
                <option value="missed_kpi" <?= $currentStatus == 'missed_kpi' ? 'selected' : '' ?>>KPI bị bỏ lỡ</option>
            </select>
        </div>

        <div class="filter-select-container case-filter-month">
            <select id="month-year-filter" class="form-control-premium">
                <option value="">Chọn Tháng/Năm</option>
                <?php 
                $yNow = (int)date('Y');
                $mNow = (int)date('m');
                // Hiển thị danh sách các tháng từ hiện tại lùi về tháng 01/2026
                for ($y = $yNow; $y >= 2026; $y--) {
                    $startM = ($y == $yNow) ? $mNow : 12;
                    $endM = ($y == 2026) ? 1 : 1; 
                    for ($m = $startM; $m >= $endM; $m--) {
                        $val = sprintf('%04d-%02d', $y, $m);
                        $isSel = ($currentYear == $y && $currentMonth == $m) ? 'selected' : '';
                        echo "<option value='$val' $isSel>Tháng $m, $y</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div class="filter-select-container case-filter-tag">
            <select id="tag-filter" name="tag_id" class="form-control-premium">
                <option value="">Nhãn dán</option>
                <?php foreach ($availableTags as $tag) { ?>
                    <option value="<?= $tag['id'] ?>" <?= ($currentTagId ?? 0) == $tag['id'] ? 'selected' : '' ?>>
                        <?= esc($tag['name']) ?>
                    </option>
                <?php } ?>
            </select>
        </div>


        <?php if (has_permission('sys.admin') || strpos(strtolower(session()->get('role_name')), 'trưởng phòng') !== false) { ?>
        <div class="filter-select-container case-filter-lawyer">
            <select id="lawyer-filter" name="lawyer_id[]" class="form-control-premium" multiple="multiple">
                <?php foreach ($availableLawyers as $lawyer) { ?>
                    <option value="<?= $lawyer['id'] ?>" <?= in_array($lawyer['id'], $lawyerIds) ? 'selected' : '' ?>>
                        <?= esc($lawyer['full_name']) ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <?php } ?>
    </div>

    <!-- 
        Bảng danh sách Vụ việc:
        Sử dụng thiết kế Premium Table với các Badge màu sắc để phân biệt trạng thái và loại hình.
    -->
    <div class="premium-card premium-card-full" id="cases-table-container">
        <?= view('dashboard/cases/index_table', [
            'cases'         => $cases,
            'pager'         => $pager,
            'currentSort'   => $currentSort,
            'currentOrder'  => $currentOrder,
            'statusLabels'  => $statusLabels
        ]) ?>
    </div>
    <div id="quickTagModal" class="modal-overlay quick-tag-modal">
        <div class="premium-card p-24 quick-tag-card">
            <div class="flex-row justify-between align-center m-b-20">
                <h3 class="section-header-title">Gắn nhãn nhanh</h3>
                <span class="close-btn-minimal quick-tag-close">&times;</span>
            </div>
            <p class="text-sm m-b-15">Vụ việc: <strong id="quickTagName">--</strong></p>
            <form id="quickTagForm" class="flex-column gap-15">
                <input type="hidden" name="entity_id" id="quickTagEntityId">
                <input type="hidden" name="entity_type" value="cases">
                <div class="form-group-premium">
                    <label class="label-premium">Lựa chọn nhãn dán</label>
                    <select name="tag_ids[]" id="quickTagSelect" class="form-control-premium quick-tag-select" multiple="multiple">
                        <?php if (isset($availableTags)) { 
                            foreach ($availableTags as $tag) { ?>
                                <option value="<?= $tag['id'] ?>"><?= esc($tag['name']) ?></option>
                            <?php } 
                        } ?>
                    </select>
                </div>
                <div class="form-actions-row m-t-15 quick-tag-actions">
                    <button type="button" class="btn-secondary quick-tag-close">Hủy</button>
                    <button type="submit" class="btn-premium">Cập nhật ngay</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/cases_index.js') ?>"></script>
<?= $this->endSection() ?>
