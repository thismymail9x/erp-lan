        <?php if (empty($notifications)) { ?>
            <div class="empty-state-container p-40 text-center text-muted-dark">
                <i class="fas fa-bell-slash notif-empty-icon" style="font-size: 3rem; opacity: 0.1; display: block; margin-bottom: 20px;"></i>
                <p>Không tìm thấy kết quả nào khớp với yêu cầu.</p>
            </div>
        <?php } else { ?>
            <div class="notification-list">
                <?php foreach ($notifications as $n) { ?>
                    <?php 
                        $iconClass = 'fa-info-circle';
                        $typeClass = 'info';
                        
                        if ($n['type'] === 'approval') { $iconClass = 'fa-check-circle'; $typeClass = 'approval'; }
                        elseif ($n['type'] === 'reminder') { $iconClass = 'fa-bell'; $typeClass = 'reminder'; }
                        elseif ($n['type'] === 'message') { $iconClass = 'fa-envelope-open-text'; $typeClass = 'message'; }

                        $readClass = ($n['is_read'] ?? 0) ? 'read' : 'unread';
                    ?>
                    <div class="notif-item-page <?= $readClass ?>">
                        <div class="notif-icon-wrapper <?= $typeClass ?>">
                            <i class="fas <?= $iconClass ?>"></i>
                        </div>
                        <div class="notif-content-wrapper">
                            <div class="notif-title <?= $readClass ?>">
                                <?= esc($n['title']) ?>
                                <?php if ($tab === 'all') { ?>
                                    <span class="text-xs text-muted-dark font-weight-400">
                                        (Gửi từ: <strong><?= esc($n['sender_name'] ?: 'Hệ thống') ?></strong> → Đến: <strong><?= esc($n['recipient_name'] ?: 'N/A') ?></strong>)
                                    </span>
                                <?php } elseif ($tab === 'sent') { ?>
                                    <span class="text-xs text-muted-dark font-weight-400 m-l-10">
                                        Đến: <strong><?= esc($n['recipient_name'] ?: 'N/A') ?></strong>
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
                                <button class="btn-mark-read text-xs" data-id="<?= $n['id'] ?>">Đã đọc</button>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
            
            <div class="pagination-wrapper p-20 border-top-light">
                <?= $pager->links() ?>
            </div>
        <?php } ?>
