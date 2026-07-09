<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/messenger.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="messenger-page-container"
     id="messengerAppConfig"
     data-selected-psid="<?= esc($selectedPsid) ?>"
     data-last-msg-id="<?= !empty($messages) ? esc(end($messages)['id']) : 0 ?>"
     data-url-index="<?= base_url('messenger') ?>"
     data-url-ajax-chat="<?= base_url('messenger/ajax-chat') ?>"
     data-url-send-message="<?= base_url('messenger/send-message') ?>"
     data-url-assign-staff="<?= base_url('messenger/assign-staff') ?>"
     data-url-update-tags="<?= base_url('messenger/update-tags') ?>"
     data-url-create-tag="<?= base_url('zalo/create-tag') ?>"
     data-url-load-more="<?= base_url('messenger/load-more') ?>">
    <div class="dashboard-header-wrapper" style="margin-bottom: 24px;">
        <div class="header-title-container">
            <h2 class="content-title">
                <i class="fab fa-facebook-messenger" style="color: #1877f2;"></i>
                Facebook Messenger — Tư vấn khách hàng
            </h2>
            <p class="content-subtitle hide-mobile">Tổng hợp hội thoại & Giao việc nhân sự tư vấn</p>
        </div>
        <div class="header-controls hide-mobile" style="display: flex; gap: 10px;">
            <a href="<?= base_url('messenger/simulate') ?>" class="btn-filter-secondary" title="Giả lập tin nhắn để test">
                <i class="fas fa-flask"></i> Giả lập
            </a>
            <?php if (has_permission('messenger.config')) { ?>
                <a href="<?= base_url('zalo/quick-replies') ?>" class="btn-filter-secondary" style="color: #f59e0b; border-color: #f59e0b;">
                    <i class="fas fa-bolt"></i> Câu trả lời nhanh
                </a>
                <a href="<?= base_url('messenger/config') ?>" class="btn-filter-secondary">
                    <i class="fas fa-cog"></i> Cấu hình
                </a>
            <?php } ?>
        </div>
    </div>

    <div class="zalo-dashboard-grid <?= $selectedPsid ? 'has-selected' : '' ?>">
        <!-- Sidebar: Danh sách hội thoại -->
        <div class="zalo-sidebar" id="messengerSidebar">
            <?= view('dashboard/messenger/_sidebar', get_defined_vars()) ?>
        </div>
        <!-- Main Chat Area -->
        <div class="zalo-main-chat" id="messengerMainChat">
            <?= view('dashboard/messenger/_chat_area', get_defined_vars()) ?>
        </div>
    </div>
</div>

<!-- Modal Quick Reply -->
<div id="quickReplyModal" class="modal-backdrop" style="display: none;">
    <div class="modal-content" style="background: #fff; width: 480px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #1877f2, #0d5bbf);">
            <h4 style="margin: 0; font-size: 16px; color: #fff;"><i class="fas fa-bolt" style="margin-right: 8px;"></i>Câu trả lời nhanh</h4>
            <i class="fas fa-times" style="cursor: pointer; color: rgba(255,255,255,0.8); font-size: 16px;" onclick="$('#quickReplyModal').fadeOut()"></i>
        </div>
        <div style="padding: 16px;">
            <div class="search-box-wrapper" style="position: relative; margin-bottom: 16px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                <input type="text" id="qrSearchInput" placeholder="Nhập từ khóa tìm câu trả lời..." style="width: 100%; padding: 10px 12px 10px 38px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#1877f2'" onblur="this.style.borderColor='#cbd5e1'">
            </div>
            <div id="qrSearchResults" style="max-height: 350px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                <!-- Dữ liệu AJAX sẽ load ở đây -->
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/messenger.js') ?>"></script>
<?= $this->endSection() ?>
