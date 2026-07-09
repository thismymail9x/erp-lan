<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/notifications.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="notifications-container">
    <div class="dashboard-header-wrapper notif-header m-b-24">
        <div class="header-title-container">
            <h2 class="content-title">Th&#244;ng b&#225;o</h2>
            <p class="text-xs text-muted-dark italic">Nh&#7855;c nh&#7903; &amp; Ch&#7881; &#273;&#7841;o.</p>
        </div>
        <div class="flex-item-center gap-12">
            <a href="<?= base_url('notifications/create') ?>" class="btn-premium">
                <i class="fas fa-edit m-r-8"></i> So&#7841;n m&#7899;i
            </a>
            <button id="markAllReadPage" class="btn-secondary-sm">
                <i class="fas fa-check-double m-r-8"></i> &#272;&#227; &#273;&#7885;c h&#7871;t
            </button>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="tabs-premium m-b-24">
        <a href="<?= base_url('notifications?tab=inbox') ?>" class="tab-item <?= ($tab === 'inbox') ? 'active' : '' ?>">
            <i class="fas fa-inbox"></i> &#272;&#7871;n
        </a>
        <a href="<?= base_url('notifications?tab=sent') ?>" class="tab-item <?= ($tab === 'sent') ? 'active' : '' ?>">
            <i class="fas fa-paper-plane"></i> &#272;i
        </a>
        <?php if (has_permission('sys.admin')) { ?>
            <a href="<?= base_url('notifications?tab=all') ?>" class="tab-item <?= ($tab === 'all') ? 'active' : '' ?>" style="border-left: 2px solid #ddd; padding-left: 20px; margin-left: 10px;">
                <i class="fas fa-shield-alt"></i> H&#7879; th&#7889;ng
            </a>
        <?php } ?>
    </div>

    <!-- SEARCH & FILTER BAR -->
    <form id="notif-filter-form" action="<?= base_url('notifications') ?>" method="get" class="search-filter-bar m-b-24">
        <input type="hidden" name="tab" value="<?= esc($tab) ?>">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="T&#236;m ti&#234;u &#273;&#7873; ho&#7863;c n&#7897;i dung..." value="<?= esc(service('request')->getGet('q')) ?>" class="ajax-filter-search">
        </div>

        <select name="type" class="filter-select ajax-filter">
            <option value="">T&#7845;t c&#7843; lo&#7841;i tin</option>
            <option value="system" <?= service('request')->getGet('type') === 'system' ? 'selected' : '' ?>>H&#7879; th&#7889;ng</option>
            <option value="approval" <?= service('request')->getGet('type') === 'approval' ? 'selected' : '' ?>>Ph&#234; duy&#7879;t</option>
            <option value="reminder" <?= service('request')->getGet('type') === 'reminder' ? 'selected' : '' ?>>Nh&#7855;c nh&#7903;</option>
            <option value="message" <?= service('request')->getGet('type') === 'message' ? 'selected' : '' ?>>Trao &#273;&#7893;i</option>
        </select>

        <?php if (service('request')->getGet('q') || service('request')->getGet('type')) { ?>
            <a href="<?= base_url('notifications?tab=' . $tab) ?>" class="btn-filter-secondary">X&#243;a l&#7885;c</a>
        <?php } ?>
    </form>

    <div class="premium-card p-0" id="notif-list-container">
        <?= view('dashboard/notifications/index_list') ?>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/notifications_page.js') ?>"></script>
<?= $this->endSection() ?>
