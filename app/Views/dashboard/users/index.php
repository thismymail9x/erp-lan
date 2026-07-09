<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/users.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="user-list-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Tài khoản</h2>
            <p class="content-subtitle hide-mobile">Danh sách truy cập hệ thống.</p>
        </div>
        <div class="header-controls">
            <?php if(has_permission('user.manage')) { ?>
            <a href="<?= base_url('users/create') ?>" class="btn-premium" title="Tạo tài khoản người dùng mới truy cập hệ thống">
                <i class="fas fa-plus"></i> <span class="hide-mobile">Tạo tài khoản</span><span class="show-mobile-only">Tạo</span>
            </a>
            <?php } ?>
        </div>
    </div>

    <!-- Stats Row for Users -->
    <div class="stats-grid-premium">
        <!-- Card: Tổng số tài khoản -->
        <div class="stat-card-premium" title="Tổng số tài khoản người dùng đã đăng ký">
            <div class="stat-icon-wrapper stat-icon-blue">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div class="stat-label">Tổng tài khoản</div>
                <div class="stat-value"><?= $stats['total'] ?></div>
            </div>
        </div>
        <!-- Card: Thống kê vai trò chi tiết -->
        <div class="stat-card-premium" title="Chi tiết số lượng tài khoản theo từng vai trò">
            <div class="stat-icon-wrapper stat-icon-purple">
                <i class="fas fa-user-tag"></i>
            </div>
            <div style="flex: 1;">
                <div class="stat-label">Số lượng theo vai trò</div>
                <div class="stat-value-sm" style="font-size: 11px; line-height: 1.5; color: var(--apple-text); margin-top: 4px;">
                    <?php 
                    $roleTexts = [];
                    foreach($stats['role_breakdown'] as $rb) {
                        $roleTexts[] = "<span class='text-nowrap'><strong>" . esc($rb['role_name']) . "</strong>: " . $rb['count'] . "</span>";
                    }
                    echo implode(' <span class="opacity-03">|</span> ', $roleTexts);
                    ?>
                </div>
            </div>
        </div>
        <!-- Card: Tài khoản đang hoạt động -->
        <div class="stat-card-premium" title="Số lượng tài khoản đang có quyền truy cập bình thường">
            <div class="stat-icon-wrapper stat-icon-green">
                <i class="fas fa-user-check"></i>
            </div>
            <div>
                <div class="stat-label">Hoạt động</div>
                <div class="stat-value text-apple-green"><?= $stats['active'] ?></div>
            </div>
        </div>
        <!-- Card: Tài khoản bị khóa -->
        <div class="stat-card-premium" title="Số lượng tài khoản hiện đang bị đình chỉ hoặc khóa truy cập">
            <div class="stat-icon-wrapper stat-icon-red" style="background: rgba(255, 59, 48, 0.1); color: var(--apple-red);">
                <i class="fas fa-user-slash"></i>
            </div>
            <div>
                <div class="stat-label">Bị khóa</div>
                <div class="stat-value text-apple-red"><?= $stats['inactive'] ?></div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="user-status-tabs">
        <a href="<?= base_url('users') ?>?status=active" class="user-status-tab <?= ($status ?? 'active') === 'active' ? 'active' : '' ?>">
            <i class="fas fa-user-check"></i> Đang hoạt động
            <span><?= (int)($stats['active'] ?? 0) ?></span>
        </a>
        <a href="<?= base_url('users') ?>?status=archived" class="user-status-tab <?= ($status ?? 'active') === 'archived' ? 'active' : '' ?>">
            <i class="fas fa-user-slash"></i> Bị khóa / đã xóa
            <span><?= (int)($stats['archived'] ?? 0) ?></span>
        </a>
    </div>

    <!-- Search and Filter Bar -->
    <div class="search-filter-wrapper m-b-16">
        <div class="search-input-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="user-search" class="input-premium" placeholder="Tìm theo tên hoặc email..." value="<?= esc($search) ?>" autocomplete="off">
        </div>
    </div>

    <div id="users-table-container">
        <?= view('dashboard/users/index_table', ['users' => $users, 'pager' => $pager, 'currentSort' => $currentSort, 'currentOrder' => $currentOrder, 'status' => $status ?? 'active']) ?>
    </div>
</div>

<!-- Modal Phân Quyền Nâng Cao -->
<div id="permissionModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="premium-card p-20" style="width:700px; max-width: 95%; position:relative;">
        <h3 class="section-header-title">Thiết lập Phân Quyền Chi Tiết</h3>
        <div id="permissionMatrixContainer">
            <!-- AJAX CONTENT -->
            <div class="text-center p-20"><i class="fas fa-spinner fa-spin"></i> Đang tải ma trận quyền...</div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/users.js') ?>"></script>
<?= $this->endSection() ?>
