<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/notifications.css') ?>">
<?= $this->endSection() ?><?= $this->section('content') ?>
<div class="notifications-container">
    <div class="dashboard-header-wrapper notif-header m-b-24">
        <div class="header-title-container">
            <h2 class="content-title">Thông báo</h2>
            <p class="text-xs text-muted-dark italic">Nhắc nhở & Chỉ đạo.</p>
        </div>
        <div class="flex-item-center gap-12">
            <a href="<?= base_url('notifications/create') ?>" class="btn-premium">
                <i class="fas fa-edit m-r-8"></i> Soạn mới
            </a>
            <button id="markAllReadPage" class="btn-secondary-sm">
                <i class="fas fa-check-double m-r-8"></i> Đã đọc hết
            </button>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="tabs-premium m-b-24">
        <a href="<?= base_url('notifications?tab=inbox') ?>" class="tab-item <?= ($tab === 'inbox') ? 'active' : '' ?>">
            <i class="fas fa-inbox"></i> Đến
        </a>
        <a href="<?= base_url('notifications?tab=sent') ?>" class="tab-item <?= ($tab === 'sent') ? 'active' : '' ?>">
            <i class="fas fa-paper-plane"></i> Đi
        </a>
        <?php if (has_permission('sys.admin')) { ?>
            <a href="<?= base_url('notifications?tab=all') ?>" class="tab-item <?= ($tab === 'all') ? 'active' : '' ?>" style="border-left: 2px solid #ddd; padding-left: 20px; margin-left: 10px;">
                <i class="fas fa-shield-alt"></i> Hệ thống
            </a>
        <?php } ?>
    </div>

    <!-- SEARCH & FILTER BAR -->
    <form id="notif-filter-form" action="<?= base_url('notifications') ?>" method="get" class="search-filter-bar m-b-24">
        <input type="hidden" name="tab" value="<?= esc($tab) ?>">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Tìm tiêu đề hoặc nội dung..." value="<?= esc(service('request')->getGet('q')) ?>" class="ajax-filter-search">
        </div>

        <select name="type" class="filter-select ajax-filter">
            <option value="">Tất cả loại tin</option>
            <option value="system" <?= service('request')->getGet('type') === 'system' ? 'selected' : '' ?>>Hệ thống</option>
            <option value="approval" <?= service('request')->getGet('type') === 'approval' ? 'selected' : '' ?>>Phê duyệt</option>
            <option value="reminder" <?= service('request')->getGet('type') === 'reminder' ? 'selected' : '' ?>>Nhắc nhở</option>
            <option value="message" <?= service('request')->getGet('type') === 'message' ? 'selected' : '' ?>>Trao đổi</option>
        </select>

        <?php if (service('request')->getGet('q') || service('request')->getGet('type')) { ?>
            <a href="<?= base_url('notifications?tab=' . $tab) ?>" class="btn-filter-secondary">Xóa lọc</a>
        <?php } ?>
    </form>

    <div class="premium-card p-0" id="notif-list-container">
        <?= view('dashboard/notifications/index_list') ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
/**
 * L.A.N ERP - Quản lý Thông báo (Hỗ trợ AJAX Filter)
 */
$(document).ready(function() {
    const listContainer = $('#notif-list-container');
    const filterForm = $('#notif-filter-form');
    let searchTimeout = null;

    // AJAX Filter logic
    $(document).on('change', '.ajax-filter', function() {
        triggerFilter();
    });

    $(document).on('input', '.ajax-filter-search', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            triggerFilter();
        }, 500);
    });

    function triggerFilter() {
        const formData = filterForm.serialize();
        const baseUrl = filterForm.attr('action');
        const finalUrl = baseUrl + '?' + formData;
        
        fetchUpdate(finalUrl);
        window.history.pushState({path: finalUrl}, '', finalUrl);
    }

    async function fetchUpdate(url) {
        listContainer.css('opacity', '0.5');
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();
            listContainer.html(html);
        } catch (err) {
            console.error('Lỗi filter thông báo AJAX:', err);
        } finally {
            listContainer.css('opacity', '1');
        }
    }
    
    // 1. Đánh dấu một thông báo là đã đọc (Xử lý AJAX)
    $(document).on('click', '.btn-mark-read', function() {
        const btn = $(this);
        const id = btn.data('id');

        $.post('<?= base_url("notifications/read/") ?>' + id, function() {
            const row = btn.closest('.notif-item-page');
            row.removeClass('unread').addClass('read');
            row.find('.notif-title').removeClass('unread').addClass('read');
            btn.remove();
        });
    });

    // 2. Đánh dấu tất cả thông báo là đã đọc
    $('#markAllReadPage').click(function() {
        if (confirm('Đánh dấu tất cả là đã đọc?')) {
            $.post('<?= base_url("notifications/read-all") ?>', function() {
                location.reload();
            });
        }
    });
});
</script>
<?= $this->endSection() ?>
