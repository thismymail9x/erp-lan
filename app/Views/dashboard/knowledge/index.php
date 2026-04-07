<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/knowledge.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="knowledge-feed-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Cẩm nang Nội bộ</h2>
            <p class="content-subtitle">Chia sẻ, học hỏi và đóng góp kinh nghiệm nghiệp vụ Luật.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('knowledge/create') ?>" class="btn-premium">
                <i class="fas fa-pen-nib"></i> Thêm
            </a>
        </div>
    </div>

    <!-- Search/Filter Bar (Apple Style Row) -->
    <form action="<?= base_url('knowledge') ?>" method="GET" class="search-filter-bar m-b-24">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Tìm kiếm kinh nghiệm..." value="<?= esc(request()->getGet('search')) ?>">
        </div>
        
        <select name="category" class="filter-select">
            <option value="">Tất cả danh mục</option>
            <option value="case_study" <?= request()->getGet('category') == 'case_study' ? 'selected' : '' ?>>Case Study (Thực chiến)</option>
            <option value="skill" <?= request()->getGet('category') == 'skill' ? 'selected' : '' ?>>Kỹ năng chuyên môn</option>
            <option value="legal_update" <?= request()->getGet('category') == 'legal_update' ? 'selected' : '' ?>>Cập nhật Văn bản Pháp lý</option>
            <option value="general" <?= request()->getGet('category') == 'general' ? 'selected' : '' ?>>Chia sẻ chung</option>
        </select>

        <select name="tag_id" class="filter-select">
            <option value="">Theo Nhãn dán</option>
            <?php foreach ($availableTags as $t) { ?>
                <option value="<?= $t['id'] ?>" <?= request()->getGet('tag_id') == $t['id'] ? 'selected' : '' ?>><?= esc($t['name']) ?></option>
            <?php } ?>
        </select>
        
<!--        <button type="submit" class="btn-filter-submit">Tìm</button>-->
        <a href="<?= base_url('knowledge') ?>" class="btn-filter-secondary">Xóa</a>
    </form>

    <!-- Articles Feed -->
    <div id="knowledge-feed-results">
        <?= view('dashboard/knowledge/index_feed') ?>
    </div>
</div>

<script>
$(document).ready(function() {
    let debounceTimer;
    const filterForm = $('.search-filter-bar');
    const resultsContainer = $('#knowledge-feed-results');

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
                console.error('Lỗi khi tải kết quả tìm kiếm.');
            }
        });
    }

    // Sự kiện khi nhập liệu (Search)
    filterForm.find('input[name="search"]').on('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(performSearch, 500);
    });

    // Sự kiện khi thay đổi select (Category/Tag)
    filterForm.find('select').on('change', performSearch);

    // Xử lý chuyển trang (Pagination) qua AJAX
    $(document).on('click', '#knowledge-pagination a', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        resultsContainer.css('opacity', '0.5');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                resultsContainer.html(response);
                resultsContainer.css('opacity', '1');
                $('html, body').animate({ scrollTop: $(".knowledge-feed-container").offset().top - 100 }, 500);
            }
        });
    });
});
</script>
</div>
<?= $this->endSection() ?>
