<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/employees.css') ?>">
<?= $this->endSection() ?>

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
    <div class="search-filter-bar">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" id="employee-search" placeholder="Tìm theo tên, chức vụ hoặc bộ phận..." value="<?= esc($search) ?>" autocomplete="off">
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
    /**
     * L.A.N ERP - Quản lý Danh sách Nhân sự
     * Điều khiển tìm kiếm Real-time và Phân trang AJAX cho bộ phận nhân sự.
     */

    // 1. Khởi tạo tham chiếu DOM
    const searchInput = document.getElementById('employee-search');
    const tableContainer = document.getElementById('employees-table-container');
    let searchTimeout; // Dùng cho Debounce (trì hoãn gửi request)

    /**
     * 2. Tìm kiếm Real-time (Thời gian thực).
     * Gửi yêu cầu lọc sau 300ms kể từ lần gõ phím cuối cùng.
     */
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('search', this.value);
            url.searchParams.set('page', 1); // Reset về trang 1
            fetchByUrl(url);
        }, 300);
    });

    /**
     * 3. Phân trang và Sắp xếp qua AJAX.
     * Lắng nghe sự kiện click trên các link phân trang hoặc sắp xếp.
     */
    tableContainer.addEventListener('click', function(e) {
        const link = e.target.closest('.pagination a, .sort-link');
        if (link) {
            e.preventDefault();
            const url = new URL(link.href);
            fetchByUrl(url);
        }
    });

    /**
     * 4. Hàm thực thi Tải dữ liệu bằng AJAX.
     * @param {URL} url - Địa chỉ API chứa các tham số lọc.
     */
    async function fetchByUrl(url) {
        try {
            // Hiệu ứng Visual: Làm mờ bảng khi đang tải
            tableContainer.style.opacity = '0.5';
            
            // Luôn đảm bảo giá trị search được gửi kèm
            if (!url.searchParams.has('search')) {
                url.searchParams.set('search', searchInput.value);
            }

            // Gửi yêu cầu với header XMLHttpRequest
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            // Lấy nội dung HTML trả về (từ index_table.php)
            const html = await response.text();
            
            // Cập nhật DOM và khôi phục độ mờ
            tableContainer.innerHTML = html;
            tableContainer.style.opacity = '1';
            
            // Cập nhật URL trên browser
            window.history.pushState(null, '', url);
        } catch (err) {
            console.error('Lỗi khi tải dữ liệu nhân sự:', err);
            tableContainer.style.opacity = '1';
        }
    }
</script>
</div>
<?= $this->endSection() ?>
