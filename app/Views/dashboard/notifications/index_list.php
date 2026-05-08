        <?php if (empty($notifications)) { ?>
            <div class="empty-state-container p-40 text-center text-muted-dark">
                <i class="fas fa-bell-slash notif-empty-icon" style="font-size: 3rem; opacity: 0.1; display: block; margin-bottom: 20px;"></i>
                <p>Không tìm thấy kết quả nào khớp với yêu cầu.</p>
            </div>
        <?php } else { ?>
            <div class="notif-summary-header">
                <i class="fas fa-list-ol"></i> Danh sách thông báo (Tổng số: <?= $pager->getDetails()['total'] ?> mục)
            </div>
            <?php if (has_permission('sys.admin')) { ?>
            <!-- Floating Bulk Actions Bar -->
            <div class="bulk-actions-bar">
                <span id="selected-count">0 mục đã chọn</span>
                <button type="button" class="bulk-btn-delete" onclick="bulkDelete()" title="Xóa hàng loạt">
                    <i class="fas fa-trash-alt"></i> Xóa vĩnh viễn
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
                    ?>
                    <div class="notif-item-page <?= $readClass ?>">
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
                                    <span class="text-xs text-muted-dark font-weight-600 m-r-5"> <?= esc($n['sender_name'] ?: 'Hệ thống') ?></span>
                                <?php } ?>
                                <?php if ($tab === 'all') { ?>
                                    <span class="text-xs text-muted-dark font-weight-400">
                                         <strong><?= esc($n['sender_name'] ?: 'Hệ thống') ?></strong>  <strong><?= esc($n['recipient_name'] ?: 'N/A') ?></strong>)
                                    </span>
                                <?php } elseif ($tab === 'sent') { ?>
                                    <span class="text-xs text-muted-dark font-weight-400 m-l-10">
                                         <strong><?= esc($n['recipient_name'] ?: 'N/A') ?></strong>
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
