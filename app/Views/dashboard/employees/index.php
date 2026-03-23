<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="employee-list-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Nhân sự</h2>
            <p class="content-subtitle hide-mobile">Quản lý hồ sơ nhân viên.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('employees/create') ?>" class="btn-premium" title="Thêm hồ sơ nhân sự mới vào hệ thống">
                <i class="fas fa-plus"></i> <span class="hide-mobile">Thêm nhân viên</span><span class="show-mobile-only">Thêm</span>
            </a>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="search-filter-wrapper m-b-16">
        <div class="search-input-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="employee-search" class="input-premium" placeholder="Tìm theo tên, chức vụ hoặc bộ phận..." value="<?= esc($search) ?>" autocomplete="off">
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

<script>
    const searchInput = document.getElementById('employee-search');
    const tableContainer = document.getElementById('employees-table-container');
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
