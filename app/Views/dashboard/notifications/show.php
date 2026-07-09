<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="notifications-container">
    <div class="dashboard-header-wrapper m-b-24">
        <div>
            <h2 class="content-title">Chi ti&#7871;t th&#244;ng b&#225;o n&#7897;i b&#7897;</h2>
            <p class="text-xs text-muted-dark italic">ID: #<?= $notif['id'] ?> | Lo&#7841;i: <span class="badge-secondary-minimal text-uppercase" style="font-size: 10px;"><?= $notif['type'] ?></span></p>
        </div>
        <div class="flex-item-center gap-10">
            <?php if ($notif['link']) { ?>
                <a href="<?= esc($notif['link']) ?>" class="btn-premium">
                    <i class="fas fa-external-link-alt m-r-8"></i> &#272;i t&#7899;i li&#234;n k&#7871;t
                </a>
            <?php } ?>
            <a href="<?= base_url('notifications') ?>" class="btn-secondary-sm">
                <i class="fas fa-arrow-left m-r-8"></i> Quay l&#7841;i danh s&#225;ch
            </a>
        </div>
    </div>

    <div class="premium-card p-40" style="max-width: 900px; margin: 0 auto;">
        <div class="notif-detail-meta m-b-30 p-b-20 border-bottom-light flex-item-center space-between">
            <div class="flex-item-center gap-15">
                <div class="user-avatar-large" style="width: 75px; height: 50px; background: var(--apple-blue); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700;">
                    <?= mb_strtoupper(mb_substr($notif['sender_name'] ?: 'H', 0, 1)) ?>
                </div>
                <div>
                    <h4 class="m-0 font-weight-700"><?= esc($notif['sender_name'] ?: html_entity_decode('H&#7879; th&#7889;ng', ENT_QUOTES, 'UTF-8')) ?></h4>
                    <p class="text-xs text-muted-dark m-0">G&#7917;i &#273;&#7871;n: <strong><?= esc($notif['recipient_name'] ?: 'N/A') ?></strong></p>
                </div>
            </div>
            <div class="text-right">
                <div class="font-weight-600 text-sm m-b-5">
                    <i class="far fa-clock m-r-5"></i>
                    <?= date('H:i, d F Y', strtotime($notif['created_at'])) ?>
                </div>
                <div class="text-xs <?= $notif['is_read'] ? 'text-apple-green' : 'text-apple-red' ?>">
                    <i class="fas <?= $notif['is_read'] ? 'fa-check' : 'fa-bell' ?> m-r-5"></i>
                    <?= $notif['is_read'] ? '&#272;&#227; xem' : 'Ch&#432;a xem' ?>
                </div>
            </div>
        </div>

        <div class="notif-detail-body">
            <h3 class="m-b-20 font-weight-700 text-apple-main" style="font-size: 1.4rem;">
                <?= esc($notif['title']) ?>
            </h3>

            <div class="notif-message-content p-20 bg-light-soft border-radius-12 m-b-30" style="line-height: 1.8; color: #333; font-size: 1rem; border: 1px solid #eee;">
                <?= nl2br(esc($notif['message'])) ?>
            </div>

            <?php if ($notif['type'] === 'reminder') { ?>
                <div class="alert-premium-info">
                    <i class="fas fa-bullhorn m-r-10"></i> &#272;&#226;y l&#224; nh&#7855;c nh&#7903; ch&#7881; &#273;&#7841;o nghi&#7879;p v&#7909; quan tr&#7885;ng. Vui l&#242;ng ph&#7843;n h&#7891;i ho&#7863;c x&#7917; l&#253; s&#7899;m nh&#7845;t c&#243; th&#7875;.
                </div>
            <?php } ?>
        </div>

        <div class="notif-detail-footer m-t-40 p-t-25 border-top-light flex-item-center justify-center gap-15">
            <?php if ($notif['user_id'] == session()->get('user_id')) { ?>
                <a href="<?= base_url('notifications/create?reply_to=' . $notif['sender_id'] . '&subject=Re: ' . urlencode($notif['title'])) ?>" class="btn-premium" style="min-width: 150px;">
                    <i class="fas fa-reply m-r-8"></i> Ph&#7843;n h&#7891;i
                </a>
            <?php } ?>
            <button onclick="window.print()" class="btn-secondary-sm">
                <i class="fas fa-print m-r-8"></i> In n&#7897;i dung
            </button>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
