<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/zalo.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="zalo-page-container" data-selected-zalo-id="<?= esc($selectedZaloId ?? '') ?>" data-last-msg-id="<?= !empty($messages) ? end($messages)['id'] : 0 ?>">
    <div class="dashboard-header-wrapper" style="margin-bottom: 24px;">
        <div class="header-title-container">
            <h2 class="content-title">Zalo OA - Quản lý tập trung</h2>
            <p class="content-subtitle hide-mobile">Chống mất khách & Đồng bộ hội thoại</p>
        </div>
        <div class="header-controls hide-mobile" style="display: flex; gap: 10px;">


            <?php if (has_permission('zalo.campaign')) { ?>
                <a href="<?= base_url('zalo/campaigns') ?>" class="btn-premium" style="background: #10b981;">
                    <i class="fas fa-bullhorn"></i> Remarketing (ZNS)
                </a>
            <?php } ?>

            <?php if (has_permission('zalo.performance')) { ?>
                <a href="<?= base_url('zalo/performance') ?>" class="btn-premium" style="background: #8b5cf6;">
                    <i class="fas fa-chart-line"></i> Hiệu suất
                </a>
            <?php } ?>

            <?php if (has_permission('zalo.config')) { ?>
                <a href="<?= base_url('zalo/quick-replies') ?>" class="btn-filter-secondary" style="background: #ffffff; color: #f59e0b; border: 1px solid #f59e0b;">
                    <i class="fas fa-bolt"></i> Câu trả lời nhanh
                </a>
                <a href="<?= base_url('zalo/config') ?>" class="btn-filter-secondary" style="background: #ffffff; color: #1e293b; border: 1px solid #cbd5e1;">
                    <i class="fas fa-cog"></i> Cấu hình
                </a>
            <?php } ?>
        </div>
    </div>

    <div class="zalo-dashboard-grid <?= $selectedZaloId ? 'has-selected' : '' ?>">
        <!-- Sidebar: Danh sách hội thoại -->
        <div class="zalo-sidebar" id="zaloSidebar">
            <?= view('dashboard/zalo/_sidebar', get_defined_vars()) ?>
        </div>
        <!-- Main Chat Area -->
        <div class="zalo-main-chat" id="zaloMainChat">
            <?= view('dashboard/zalo/_chat_area', get_defined_vars()) ?>
        </div>
    </div>

    <!-- Modal Quick Reply -->
    <div id="quickReplyModal" class="modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: none; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: #fff; width: 480px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); margin: auto;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #0ea5e9, #0284c7);">
                <h4 style="margin: 0; font-size: 16px; color: #fff;"><i class="fas fa-bolt" style="margin-right: 8px;"></i>Câu trả lời nhanh</h4>
                <i class="fas fa-times" style="cursor: pointer; color: rgba(255,255,255,0.8); font-size: 16px;" onclick="$('#quickReplyModal').fadeOut()"></i>
            </div>
            <div style="padding: 16px;">
                <div class="search-box-wrapper" style="position: relative; margin-bottom: 16px;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                    <input type="text" id="qrSearchInput" placeholder="Nhập từ khóa tìm câu trả lời..." style="width: 100%; padding: 10px 12px 10px 38px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#cbd5e1'">
                </div>
                <div id="qrSearchResults" style="max-height: 350px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                    <!-- Dữ liệu AJAX sẽ load ở đây -->
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/zalo_index.js') ?>"></script>
<?= $this->endSection() ?>
