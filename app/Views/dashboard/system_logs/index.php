<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/logs.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dashboard-header-wrapper">
    <div class="header-title-container">
        <h2 class="content-title">Nhật ký hệ thống</h2>
        <p class="content-subtitle hide-mobile">Theo dõi dấu vết các thao tác của người dùng trên toàn hệ thống.</p>
    </div>
</div>

<form action="<?= base_url('system-logs') ?>" method="GET" class="search-filter-bar">
    <div class="search-input-group">
        <i class="fas fa-calendar-alt"></i>
        <input type="date" name="date" value="<?= $filters['date'] ?? '' ?>">
    </div>
    
    <select name="user_id" class="filter-select select2-basic">
        <option value="">Tất cả người dùng</option>
        <?php foreach($users as $u) { ?>
            <option value="<?= $u['id'] ?>" <?= ($filters['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= esc($u['email']) ?></option>
        <?php } ?>
    </select>
    
    <select name="action" class="filter-select">
        <option value="">Tất cả thao tác</option>
        <option value="LOGIN" <?= ($filters['action'] ?? '') == 'LOGIN' ? 'selected' : '' ?>>Đăng nhập</option>
        <option value="CREATE" <?= ($filters['action'] ?? '') == 'CREATE' ? 'selected' : '' ?>>Tạo mới</option>
        <option value="UPDATE" <?= ($filters['action'] ?? '') == 'UPDATE' ? 'selected' : '' ?>>Cập nhật</option>
        <option value="DELETE" <?= ($filters['action'] ?? '') == 'DELETE' ? 'selected' : '' ?>>Xóa</option>
    </select>
    
<!--    <button type="submit" class="btn-filter-submit">Lọc</button>-->
    
    <?php if(!empty($filters['date']) || !empty($filters['action']) || !empty($filters['user_id'])) { ?>
        <a href="<?= base_url('system-logs') ?>" class="btn-filter-secondary">Xóa</a>
    <?php } ?>
</form>

<div class="premium-card premium-card-full" id="logs-table-results">
    <?= view('dashboard/system_logs/index_table') ?>
</div>

<script>
$(document).ready(function() {
    const filterForm = $('.search-filter-bar');
    const resultsContainer = $('#logs-table-results');

    function performSearch() {
        const formData = filterForm.serialize();
        resultsContainer.css('opacity', '0.5');

        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            data: formData,
            success: function(response) {
                resultsContainer.html(response);
                resultsContainer.css('opacity', '1');
            },
            error: function() {
                resultsContainer.css('opacity', '1');
                console.error('Lỗi khi tải nhật ký.');
            }
        });
    }

    // Tự động lọc khi thay đổi bất kỳ ô input/select nào
    filterForm.find('input, select').on('change', performSearch);

    // Xử lý chuyển trang qua AJAX
    $(document).on('click', '#logs-pagination a', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        resultsContainer.css('opacity', '0.5');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                resultsContainer.html(response);
                resultsContainer.css('opacity', '1');
                $('html, body').animate({ scrollTop: 0 }, 500);
            }
        });
    });
});
</script>

<?= $this->endSection() ?>
