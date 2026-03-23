<?= $this->extend('layouts/dashboard') ?>

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
    <div class="stats-grid-premium">
        <!-- Card: Tổng số vụ việc hồ sơ trong hệ thống (Dựa theo quyền truy cập) -->
        <div class="stat-card-premium" title="Tổng số vụ việc/hồ sơ pháp lý bạn có quyền truy cập">
            <div class="stat-icon-wrapper stat-icon-blue">
                <i class="fas fa-folder"></i>
            </div>
            <div>
                <div class="stat-label">Tổng số vụ việc</div>
                <div class="stat-value"><?= $stats['total'] ?></div>
            </div>
        </div>
        <!-- Vụ việc đang trong quá trình xử lý -->
        <div class="stat-card-premium" title="Số lượng vụ việc đang trong các bước thực hiện">
            <div class="stat-icon-wrapper stat-icon-orange">
                <i class="fas fa-spinner"></i>
            </div>
            <div>
                <div class="stat-label">Đang xử lý</div>
                <div class="stat-value"><?= $stats['processing'] ?? 0 ?></div>
            </div>
        </div>
        <!-- Vụ việc đã hoàn thành trong tháng -->
        <div class="stat-card-premium" title="Số lượng vụ việc đã đóng hoặc giải quyết xong tháng này">
            <div class="stat-icon-wrapper stat-icon-green">
                <i class="fas fa-check-double"></i>
            </div>
            <div>
                <div class="stat-label">Đã hoàn thành</div>
                <div class="stat-value"><?= $stats['completed'] ?? 0 ?></div>
            </div>
        </div>
        <!-- Vụ việc có bước bị quá hạn -->
        <div class="stat-card-premium" title="Cảnh báo: Các vụ việc có bước công việc đã quá hạn chót">
            <div class="stat-icon-wrapper stat-icon-purple" style="color: var(--apple-red); background: rgba(255, 59, 48, 0.1);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <div class="stat-label" style="color: var(--apple-red);">Quá hạn</div>
                <div class="stat-value text-apple-red"><?= $stats['overdue'] ?? 0 ?></div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="search-filter-wrapper m-b-16">
        <div class="search-input-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="case-search" class="input-premium" placeholder="Tìm theo tên vụ việc, mã hồ sơ hoặc khách hàng..." value="<?= esc($search) ?>" autocomplete="off">
        </div>
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
</div>

<script>
    const searchInput = document.getElementById('case-search');
    const tableContainer = document.getElementById('cases-table-container');
    let searchTimeout;

    // Real-time Search
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('search', this.value);
            url.searchParams.set('page', 1);
            fetchByUrl(url);
        }, 300);
    });

    // AJAX Pagination & Sorting
    tableContainer.addEventListener('click', function(e) {
        const link = e.target.closest('.pagination a, .sort-link');
        if (link) {
            e.preventDefault();
            fetchByUrl(new URL(link.href));
        }
    });

    async function fetchByUrl(url) {
        try {
            tableContainer.style.opacity = '0.5';
            // Đảm bảo giữ lại giá trị search hiện tại nếu url không có
            if (!url.searchParams.has('search')) {
                url.searchParams.set('search', searchInput.value);
            }

            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();
            tableContainer.innerHTML = html;
            tableContainer.style.opacity = '1';
            
            window.history.pushState(null, '', url);
        } catch (err) {
            console.error('Fetch error:', err);
            tableContainer.style.opacity = '1';
        }
    }
</script>
</div>
<?= $this->endSection() ?>
