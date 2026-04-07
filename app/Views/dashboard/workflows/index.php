<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="workflow-index-container">
    <div class="dashboard-header-wrapper m-b-24">
        <div class="header-title-container">
            <h2 class="content-title">Quản lý Quy trình mẫu</h2>
            <p class="content-subtitle">Thiết lập các giai đoạn chuẩn cho từng quy trình nghiệp vụ.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('workflows/create') ?>" class="btn-premium">
                <i class="fas fa-plus"></i> Tạo quy trình mới
            </a>
        </div>
    </div>

    <!-- SEARCH & FILTER BAR -->
    <form id="workflow-filter-form" action="<?= base_url('workflows') ?>" method="get" class="search-filter-bar m-b-24">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Tìm tên quy trình hoặc mã..." value="<?= esc(service('request')->getGet('q')) ?>" class="ajax-filter-search">
        </div>

        <select name="status" class="filter-select ajax-filter">
            <option value="">Tất cả trạng thái</option>
            <option value="1" <?= service('request')->getGet('status') === '1' ? 'selected' : '' ?>>Đang hoạt động</option>
            <option value="0" <?= service('request')->getGet('status') === '0' ? 'selected' : '' ?>>Tạm ngưng</option>
        </select>

        <?php if (service('request')->getUri()->getQuery() !== '') { ?>
            <a href="<?= base_url('workflows') ?>" class="btn-filter-secondary">Xóa lọc</a>
        <?php } ?>
    </form>

    <div class="workflow-grid" id="workflow-grid-container">
        <?= view('dashboard/workflows/index_grid') ?>
    </div>
</div>

<style>
.grid-layout-premium {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}
.workflow-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    padding: 24px;
    display: flex;
    flex-direction: column;
}
.workflow-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.08);
}
.card-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.workflow-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 10px 0;
    color: var(--apple-main);
}
.workflow-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    color: var(--text-muted-dark);
    font-size: 0.9rem;
}
.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
}
.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
.bg-apple-green { background-color: #34c759; }
.bg-apple-gray { background-color: #8e8e93; }

.btn-icon-only-minimal {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: rgba(0,0,0,0.03);
    color: var(--apple-main);
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}
.btn-icon-only-minimal:hover {
    background: rgba(0,0,0,0.08);
}
.btn-icon-duplicate {
    color: var(--apple-blue);
    background: rgba(0, 122, 255, 0.05);
}
.btn-icon-duplicate:hover {
    background: rgba(0, 122, 255, 0.15);
}
.flex-row { display: flex; }
.align-center { align-items: center; }
.m-l-8 { margin-left: 8px; }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
/**
 * L.A.N ERP - Quản lý Quy trình mẫu (AJAX Auto-Filter)
 */
$(document).ready(function() {
    const gridContainer = $('#workflow-grid-container');
    const filterForm = $('#workflow-filter-form');
    let searchTimeout = null;

    $(document).on('change', '.ajax-filter', function() {
        triggerFilter();
    });

    $(document).on('input', '.ajax-filter-search', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            triggerFilter();
        }, 500);
    });

    $(document).on('click', '.btn-filter-secondary', function(e) {
        if ($(this).attr('href') === filterForm.attr('action')) {
            e.preventDefault();
            filterForm[0].reset();
            $('.ajax-filter-search').val('');
            triggerFilter();
        }
    });

    function triggerFilter() {
        const formData = filterForm.serialize();
        const baseUrl = filterForm.attr('action');
        const finalUrl = baseUrl + '?' + formData;
        
        fetchUpdate(finalUrl);
        window.history.pushState({path: finalUrl}, '', finalUrl);
    }

    async function fetchUpdate(url) {
        gridContainer.css('opacity', '0.5');
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();
            gridContainer.html(html);
        } catch (err) {
            console.error('Lỗi filter quy trình AJAX:', err);
        } finally {
            gridContainer.css('opacity', '1');
        }
    }
});
</script>
<?= $this->endSection() ?>
