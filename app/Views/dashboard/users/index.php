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
    <div class="search-filter-wrapper m-b-16">
        <div class="search-input-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="user-search" class="input-premium" placeholder="Tìm theo tên hoặc email..." value="<?= esc($search) ?>" autocomplete="off">
        </div>
    </div>

    <div id="users-table-container">
        <?= view('dashboard/users/index_table', ['users' => $users, 'pager' => $pager, 'currentSort' => $currentSort, 'currentOrder' => $currentOrder]) ?>
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
</div>

<script>
    /**
     * L.A.N ERP - Quản lý Tài khoản & Phân quyền
     * Bao gồm các tính năng: Tìm kiếm AJAX, Thao tác hàng loạt (Bulk actions) và Ma trận phân quyền nâng cao.
     */

    // 1. Khai báo các đối tượng DOM chính
    const searchInput = document.getElementById('user-search');
    const tableContainer = document.getElementById('users-table-container');
    let searchTimeout; // Dùng cho kỹ thuật Debounce
    
    // Các phần tử phục vụ Thao tác hàng loạt (Bulk Actions)
    let checkAll, recordChecks, bulkBar, selectedCount;

    /**
     * Khởi tạo lại các tham chiếu DOM cho Bulk Actions mỗi khi bảng được làm mới bằng AJAX.
     */
    function initBulkElements() {
        checkAll = document.getElementById('check-all');
        recordChecks = document.querySelectorAll('.record-check');
        bulkBar = document.getElementById('bulk-bar');
        selectedCount = document.getElementById('selected-count');
    }

    /**
     * Cập nhật trạng thái hiển thị của Thanh thao tác hàng loạt (màu xanh dương phía dưới).
     */
    function updateBulkBar() {
        const checked = document.querySelectorAll('.record-check:checked');
        if (checked && bulkBar) {
            if (checked.length > 0) {
                bulkBar.style.display = 'flex';
                selectedCount.innerText = checked.length + ' mục đã chọn';
            } else {
                bulkBar.style.display = 'none';
            }
        }
    }

    /**
     * Gán lại các sự kiện (Event Listeners) cho các checkbox sau khi bảng cập nhật dữ liệu.
     */
    function rebindBulkActions() {
        initBulkElements();
        if (checkAll) {
            // Sự kiện 'Chọn tất cả'
            checkAll.addEventListener('change', function() {
                recordChecks.forEach(cb => cb.checked = checkAll.checked);
                updateBulkBar();
            });
        }
        // Sự kiện cho từng checkbox lẻ
        recordChecks.forEach(cb => {
            cb.addEventListener('change', updateBulkBar);
        });
        updateBulkBar();
    }

    /**
     * Xử lý tải lại bảng dữ liệu qua AJAX (Tìm kiếm, Sắp xếp, Phân trang).
     * @param {URL} url - Địa chỉ API
     */
    async function fetchByUrl(url) {
        try {
            tableContainer.style.opacity = '0.5';
            
            // Luôn đảm bảo giá trị tìm kiếm được giữ lại
            if (!url.searchParams.has('search')) {
                url.searchParams.set('search', searchInput.value);
            }

            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await response.text();
            
            // Cập nhật nội dung bảng và gán lại sự kiện
            tableContainer.innerHTML = html;
            tableContainer.style.opacity = '1';
            window.history.pushState(null, '', url);
            rebindBulkActions();
        } catch (err) {
            console.error('Lỗi khi tải dữ liệu người dùng:', err);
            tableContainer.style.opacity = '1';
        }
    }

    /**
     * Tìm kiếm Real-time (Debounce 300ms).
     */
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('search', this.value);
            url.searchParams.set('page', 1);
            fetchByUrl(url);
        }, 300);
    });

    /**
     * Lắng nghe click trên phân trang hoặc nút sắp xếp.
     */
    tableContainer.addEventListener('click', function(e) {
        const link = e.target.closest('.pagination a, .sort-link');
        if (link) {
            e.preventDefault();
            fetchByUrl(new URL(link.href));
        }
    });

    /**
     * Xử lý Xóa hàng loạt tài khoản.
     */
    async function applyBulkDelete() {
        const ids = Array.from(document.querySelectorAll('.record-check:checked')).map(cb => cb.value);
        if (!confirm('Hệ thống sẽ xóa vĩnh viễn ' + ids.length + ' tài khoản. Tiếp tục?')) return;

        try {
            const formData = new FormData();
            ids.forEach(id => formData.append('ids[]', id));
            const response = await fetch('<?= base_url('users/bulk-delete') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const res = await response.json();
            if (res.code === 0) location.reload();
            else alert('Lỗi: ' + res.error);
        } catch (err) {
            alert('Lỗi kết nối máy chủ khi thực hiện xóa hàng loạt');
        }
    }

    /**
     * Mở Modal Phân quyền nâng cao (Asynchronous - AJAX).
     * @param {number} userId - ID của người dùng cần cấu hình.
     */
    function openPermissionModal(userId) {
        document.getElementById('permissionModal').style.display = 'flex';
        document.getElementById('permissionMatrixContainer').innerHTML = '<div class="text-center p-20 text-muted-dark"><i class="fas fa-spinner fa-spin m-r-5"></i> Đang tải dữ liệu bộ máy quyền...</div>';
        
        fetch('<?= base_url('users/permissions/matrix') ?>/' + userId)
            .then(res => {
                if (!res.ok) throw new Error('Network');
                return res.text();
            })
            .then(html => {
                // Kiểm tra xem server có trả về JSON lỗi không (ví dụ: hết hạn session)
                try {
                    const json = JSON.parse(html);
                    if (json.status === 'error') {
                        alert(json.message);
                        closePermissionModal();
                        return;
                    }
                } catch(e) { /* Trả về HTML chuẩn */ }
                
                document.getElementById('permissionMatrixContainer').innerHTML = html;
            })
            .catch(err => {
                alert('Có lỗi xảy ra khi tải bảng cấu hình phân quyền.');
                closePermissionModal();
            });
    }

    /**
     * Đóng Modal phân quyền.
     */
    function closePermissionModal() {
        document.getElementById('permissionModal').style.display = 'none';
    }

    /**
     * Lưu thông tin Phân quyền nâng cao từ ma trận.
     */
    async function savePermissions(e, userId) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        const btn = form.querySelector('button[type="submit"]');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        btn.disabled = true;

        try {
            const response = await fetch('<?= base_url('users/permissions/save') ?>/' + userId, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const res = await response.json();
            if (res.status === 'success') {
                closePermissionModal();
                // Thông báo thành công và reload nhẹ nếu cần
                alert(res.message);
                if (typeof showToast === "function") showToast(res.message, 'success');
            } else {
                alert(res.message);
            }
        } catch (err) {
            alert('Lỗi kết nối máy chủ khi lưu phân quyền');
        } finally {
            btn.innerHTML = '<i class="fas fa-save m-r-8"></i> Áp dụng Phân Quyền';
            btn.disabled = false;
        }
    }

    // Khởi tạo các sự kiện khi trang tải hoàn tất
    document.addEventListener('DOMContentLoaded', rebindBulkActions);
</script>
<?= $this->endSection() ?>
