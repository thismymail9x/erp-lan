<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/chat.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$chatAppConfig = [
    'selectedChannel'   => $selectedChannel,
    'selectedContactId' => $selectedContactId,
    'lastMsgId'         => $lastMsgId,
    'urls'              => [
        'index'       => base_url('chat'),
        'ajaxChat'    => base_url('chat/ajax-chat'),
        'sendMessage' => base_url('chat/send-message'),
        'assignStaff' => base_url('chat/assign-staff'),
        'updateTags'  => base_url('chat/update-tags'),
        'createTag'   => base_url('chat/create-tag'),
        'loadMore'    => base_url('chat/load-more'),
        'uploadMedia' => base_url('chat/upload-media'),
        'syncHistory' => base_url('chat/sync-history'),
        'syncProfile' => base_url('chat/sync-profile'),
        'logCall'     => base_url('chat/log-call'),
        'logCallV2'   => base_url('chat/log-call-v2'),
        'bulkDelete'  => base_url('chat/bulk-delete'),
    ],
];
?>
<div class="chat-page-container" data-chat-config="<?= esc(json_encode($chatAppConfig), 'attr') ?>">
    <div class="dashboard-header-wrapper chat-page-header">
        <div class="header-title-container">
            <h2 class="content-title">Trung t&#226;m T&#432; v&#7845;n Kh&#225;ch h&#224;ng</h2>
            <p class="content-subtitle hide-mobile">T&#7893;ng h&#7907;p h&#7897;i tho&#7841;i &#273;a k&#234;nh &amp; Giao vi&#7879;c t&#432; v&#7845;n</p>
        </div>

        <div class="header-controls hide-mobile">
            <div class="channel-tabs">
                <?php
                    $baseUrl = base_url('chat');
                    $filterQs = '';
                    if (!empty($filter['search'])) {
                        $filterQs .= '&search=' . urlencode($filter['search']);
                    }
                    if (!empty($filter['tag'])) {
                        $filterQs .= '&filter_tag=' . urlencode($filter['tag']);
                    }
                    if (!empty($filter['staff'])) {
                        $filterQs .= '&filter_staff=' . urlencode($filter['staff']);
                    }
                    if (!empty($filter['creator'])) {
                        $filterQs .= '&filter_creator=' . urlencode($filter['creator']);
                    }
                ?>
                <a href="<?= $baseUrl . '?' . ltrim($filterQs, '&') ?>"
                   class="channel-tab <?= empty($selectedChannel) ? 'active' : '' ?>">
                    <i class="fas fa-layer-group"></i> T&#7845;t c&#7843;
                </a>
                <a href="<?= $baseUrl . '?channel=zalo' . $filterQs ?>"
                   class="channel-tab <?= ($selectedChannel === 'zalo') ? 'active' : '' ?>">
                    <span class="channel-badge-zalo chat-zalo-dot">Z</span>
                    Zalo
                </a>
                <a href="<?= $baseUrl . '?channel=messenger' . $filterQs ?>"
                   class="channel-tab <?= ($selectedChannel === 'messenger') ? 'active' : '' ?>">
                    <i class="fab fa-facebook-messenger chat-messenger-icon"></i>
                    Messenger
                </a>
            </div>

            <?php if (has_permission('zalo.campaign')) { ?>
                <a href="<?= base_url('zalo/campaigns') ?>" class="chat-action-btn chat-action-btn-green">
                    <i class="fas fa-bullhorn"></i> <span>ZNS</span>
                </a>
            <?php } ?>

            <?php if (has_permission('zalo.performance')) { ?>
                <a href="<?= base_url('zalo/performance') ?>" class="chat-action-btn chat-action-btn-purple">
                    <i class="fas fa-chart-line"></i> <span>Hi&#7879;u su&#7845;t</span>
                </a>
            <?php } ?>

            <?php if (has_permission('zalo.config') || has_permission('messenger.config')) { ?>
                <a href="<?= base_url('zalo/quick-replies') ?>" class="chat-action-btn chat-action-btn-outline">
                    <i class="fas fa-bolt chat-quick-reply-icon"></i> <span>Tr&#7843; l&#7901;i nhanh</span>
                </a>
            <?php } ?>
        </div>
    </div>

    <div class="chat-grid <?= $selectedContactId ? 'has-selected' : '' ?>">
        <div class="chat-sidebar" id="chatSidebar">
            <?= view('dashboard/chat/_sidebar', get_defined_vars()) ?>
        </div>
        <div class="chat-main chat-main-fill" id="chatMainArea">
            <?= view('dashboard/chat/_chat_area', get_defined_vars()) ?>
        </div>
    </div>
</div>

<div id="quickReplyModal" class="modal-backdrop chat-modal-hidden">
    <div class="modal-box">
        <div class="qr-modal-header">
            <h4><i class="fas fa-bolt"></i> C&#226;u tr&#7843; l&#7901;i nhanh</h4>
            <button class="modal-close chat-modal-close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="qr-modal-body">
            <div class="search-box-wrapper qr-search-wrapper">
                <i class="fas fa-search qr-search-icon"></i>
                <input type="text" id="qrSearchInput" class="qr-search-input" placeholder="Nh&#7853;p t&#7915; kh&#243;a t&#236;m c&#226;u tr&#7843; l&#7901;i...">
            </div>
            <div id="qrSearchResults" class="qr-search-results"></div>
        </div>
    </div>
</div>

<div id="callLogModal" class="modal-backdrop chat-modal-hidden">
    <div class="modal-box">
        <div class="modal-header">
            <h4><i class="fas fa-phone-alt call-log-title-icon"></i>Ghi nh&#7853;n cu&#7897;c g&#7885;i</h4>
            <button class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="modal-section-label">Ghi ch&#250; cu&#7897;c g&#7885;i</div>
            <textarea id="callNotes" class="call-notes-input" placeholder="Nh&#7853;p n&#7897;i dung trao &#273;&#7893;i qua &#273;i&#7879;n tho&#7841;i..."></textarea>
            <button id="btnSubmitCall" class="save-tags-btn call-log-submit-btn">
                <i class="fas fa-save"></i> L&#432;u cu&#7897;c g&#7885;i
            </button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/chat.js?v=') ?><?= time() ?>"></script>
<?= $this->endSection() ?>
