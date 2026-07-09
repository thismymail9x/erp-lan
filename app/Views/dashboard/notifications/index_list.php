        <?php if (empty($notifications)) { ?>
            <div class="empty-state-container p-40 text-center text-muted-dark">
                <i class="fas fa-bell-slash notif-empty-icon" style="font-size: 3rem; opacity: 0.1; display: block; margin-bottom: 20px;"></i>
                <p>Kh&#244;ng t&#236;m th&#7845;y k&#7871;t qu&#7843; n&#224;o kh&#7899;p v&#7899;i y&#234;u c&#7847;u.</p>
            </div>
        <?php } else { ?>
            <div class="notif-summary-header">
                <i class="fas fa-list-ol"></i> Danh s&#225;ch th&#244;ng b&#225;o (T&#7893;ng s&#7889;: <?= $pager->getDetails()['total'] ?> m&#7909;c)
            </div>
            <?php if (has_permission('sys.admin')) { ?>
            <!-- Floating Bulk Actions Bar -->
            <div class="bulk-actions-bar">
                <span id="selected-count">0 m&#7909;c &#273;&#227; ch&#7885;n</span>
                <button type="button" class="bulk-btn-delete" onclick="bulkDelete()" title="X&#243;a h&#224;ng lo&#7841;t">
                    <i class="fas fa-trash-alt"></i> X&#243;a v&#297;nh vi&#7877;n
                </button>
            </div>
            <?php } ?>
            <div class="notification-list">
                <?php $stt = isset($pager) ? ($pager->getCurrentPage() - 1) * $pager->getPerPage() : 0; ?>
                <?php foreach ($notifications as $n) { $stt++; ?>
                    <?php
                        $iconClass = 'fa-info-circle';
                        $typeClass = 'info';

                        if ($n['type'] === 'approval') { $iconClass = 'fa-check-circle'; $typeClass = 'approval'; }
                        elseif ($n['type'] === 'reminder') { $iconClass = 'fa-bell'; $typeClass = 'reminder'; }
                        elseif ($n['type'] === 'message') { $iconClass = 'fa-envelope-open-text'; $typeClass = 'message'; }

                        $readClass = ($n['is_read'] ?? 0) ? 'read' : 'unread';
                        $targetUrl = !empty($n['link']) ? $n['link'] : base_url('notifications/show/' . $n['id']);
                    ?>
                    <div class="notif-item-page <?= $readClass ?> js-notif-open" data-href="<?= esc($targetUrl) ?>">
                        <?php if (has_permission('sys.admin')) { ?>
                        <input type="checkbox" class="record-check" value="<?= $n['id'] ?>" style="width: 16px; height: 16px; cursor: pointer; margin-top: 4px; flex-shrink: 0;">
                        <?php } ?>
                        <span class="text-muted-dark text-xs font-weight-600" style="min-width: 28px; flex-shrink: 0;"><?= $stt ?>.</span>
                        <div class="notif-icon-wrapper <?= $typeClass ?>">
                            <i class="fas <?= $iconClass ?>"></i>
                        </div>
                        <div class="notif-content-wrapper">
                            <div class="notif-title <?= $readClass ?>">

                                <?= esc($n['title']) ?>
                                <?php if ($tab === 'inbox') { ?>
                                    <span class="text-xs text-muted-dark font-weight-600 m-r-5"> <?= esc($n['sender_name'] ?: html_entity_decode('H&#7879; th&#7889;ng', ENT_QUOTES, 'UTF-8')) ?></span>
                                <?php } ?>
                                <?php if ($tab === 'all') { ?>
                                    <span class="text-xs text-muted-dark font-weight-400">
                                         G&#7917;i: <strong><?= esc($n['sender_name'] ?: html_entity_decode('H&#7879; th&#7889;ng', ENT_QUOTES, 'UTF-8')) ?></strong> - Nh&#7853;n: <strong><?= esc($n['recipient_name'] ?: 'N/A') ?></strong>
                                    </span>
                                <?php } elseif ($tab === 'sent') { ?>
                                    <span class="text-xs text-muted-dark font-weight-400 m-l-10">
                                         &#272;&#7871;n: <strong><?= esc($n['recipient_name'] ?: 'N/A') ?></strong>
                                    </span>
                                <?php } ?>
                            </div>
                            <div class="notif-message limit-text-2">
                                <?= esc($n['message']) ?>
                            </div>
                            <div class="notif-time">
                                <i class="far fa-clock"></i> <?= date('H:i d/m/Y', strtotime($n['created_at'])) ?>
                                <span class="badge-secondary-minimal m-l-10 text-uppercase" style="font-size: 10px;"><?= $n['type'] ?></span>
                            </div>
                        </div>
                        <div class="notif-actions">
                            <a href="<?= base_url('notifications/show/' . $n['id']) ?>" class="btn-secondary-sm text-xs">
                                <i class="fas fa-eye m-r-5"></i> Xem
                            </a>
                            <?php if ($tab === 'inbox' && !($n['is_read'] ?? 0)) { ?>
                                <button class="btn-mark-read text-xs" data-id="<?= $n['id'] ?>">&#272;&#227; &#273;&#7885;c</button>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="pagination-wrapper p-20 border-top-light">
                <?= $pager->links() ?>
            </div>
        <?php } ?>
