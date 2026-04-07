<div class="table-responsive">
    <table class="premium-table">
        <thead>
            <tr>
                <th class="table-cell-center logs-col-time">Thời gian</th>
                <th>Người thực hiện</th>
                <th>Thao tác</th>
                <th>Module</th>
                <th>Chi tiết</th>
                <th>Địa chỉ IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)) { ?>
                <tr>
                    <td colspan="6" class="empty-state-container">
                        <i class="fas fa-history logs-empty-icon" ></i>
                        Không có dữ liệu nhật ký nào được tìm thấy.
                    </td>
                </tr>
            <?php } else { ?>
                <?php foreach ($logs as $log) { ?>
                <tr>
                    <td class="table-cell-center">
                        <div class="log-time-main"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
                        <div class="log-time-sub"><?= date('d/m/Y', strtotime($log['created_at'])) ?></div>
                    </td>
                    <td>
                        <div class="log-entry-meta">
                            <span class="log-user-email"><?= esc($log['user_email'] ?? 'Hệ thống') ?></span>
                            <span class="log-user-id">ID: #<?= $log['user_id'] ?></span>
                        </div>
                    </td>
                    <td>
                        <?php 
                            $badgeClass = '';
                            switch($log['action']) {
                                case 'CREATE': $badgeClass = 'badge-success-minimal'; break;
                                case 'UPDATE': $badgeClass = 'badge-warning-minimal'; break;
                                case 'DELETE': $badgeClass = 'badge-danger-minimal'; break;
                                case 'LOGIN': $badgeClass = 'badge-info-minimal'; break;
                                default: $badgeClass = 'badge-secondary-minimal';
                            }
                        ?>
                        <span class="badge-log <?= $badgeClass ?>">
                            <?= $log['action'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="log-module-text"><?= esc($log['module']) ?></span>
                        <span class="log-entity-id">(ID: <?= $log['entity_id'] ?: '--' ?>)</span>
                    </td>
                    <td>
                        <div class="log-details-box">
                            <?php 
                                $details = json_decode($log['details'], true);
                                if (is_array($details)) {
                                    if (isset($details['before']) || isset($details['after'])) {
                                        $allKeys = array_unique(array_merge(array_keys($details['before'] ?? []), array_keys($details['after'] ?? [])));
                                        foreach ($allKeys as $key) {
                                            if (in_array($key, ['password'])) continue;
                                            $oldVal = $details['before'][$key] ?? '<span class="text-blue">Mới</span>';
                                            $newVal = $details['after'][$key] ?? '<span class="text-red">Xóa</span>';
                                            echo "<div class='log-change-item'><strong>" . esc($key) . "</strong>: " . $oldVal . " <i class='fas fa-arrow-right'></i> " . $newVal . "</div>";
                                        }
                                    } elseif (isset($details['deleted_record'])) {
                                        echo "<div class='text-red'><strong>Hồ sơ bị xóa</strong>: " . esc($details['deleted_record']['full_name'] ?? $details['deleted_record']['email'] ?? '...') . "</div>";
                                    } else {
                                        echo esc($log['details']);
                                    }
                                } else {
                                    echo esc($log['details'] ?: '---');
                                }
                            ?>
                        </div>
                    </td>
                    <td>
                        <div class="log-ip-display">
                            <i class="fas fa-network-wired"></i>
                            <?= $log['ip_address'] ?>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php if ($pager) { ?>
<div class="pagination-wrapper" id="logs-pagination">
    <?= $pager->links() ?>
</div>
<?php } ?>
