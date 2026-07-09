<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/zalo.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="zalo-page-container zalo-campaigns-page" data-campaign-execute-url="<?= base_url('zalo/campaigns/execute/') ?>">
    
    <!-- Flash Messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success zns-alert zns-alert-success">
            <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger zns-alert zns-alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title zns-page-title">Chiến dịch Zalo ZNS</h2>
            <p class="content-subtitle hide-mobile zns-page-subtitle">Gửi thông báo chăm sóc khách hàng tự động và hàng loạt qua hệ thống Zalo Notification Service</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('zalo') ?>" class="btn-filter-secondary zns-header-action zns-header-action-secondary">
                <i class="fas fa-arrow-left"></i> Trở về Zalo OA
            </a>
            <a href="<?= base_url('zalo/zns-templates') ?>" class="btn-filter-secondary zns-header-action zns-header-action-secondary">
                <i class="fas fa-file-code"></i> Quản lý Mẫu tin ZNS
            </a>
            <a href="<?= base_url('zalo/campaigns/create') ?>" class="btn-premium zns-header-action zns-header-action-primary">
                <i class="fas fa-plus"></i> Tạo chiến dịch mới
            </a>
        </div>
    </div>

    <!-- Premium Tabs Navigation -->
    <div class="premium-tabs-container">
        <div class="premium-tab-item active" data-target="tab-campaigns">
            <i class="fas fa-bullhorn"></i> Chiến dịch ZNS hàng loạt
        </div>
        <div class="premium-tab-item" data-target="tab-individual">
            <i class="fas fa-paper-plane"></i> Nhật ký gửi nhanh (Đơn lẻ & Hàng loạt)
        </div>
    </div>

    <!-- Tab 1: Campaigns Content -->
    <div id="tab-campaigns" class="tab-content-panel active">
        <!-- Stats -->
        <div class="stats-grid-premium">
            <div class="stat-card-premium">
                <div class="stat-icon-wrapper stat-icon-blue">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div>
                    <div class="stat-label">Tổng số chiến dịch</div>
                    <div class="stat-value"><?= number_format($stats['total_campaigns'] ?? 0) ?></div>
                </div>
            </div>
            <div class="stat-card-premium">
                <div class="stat-icon-wrapper stat-icon-green">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div>
                    <div class="stat-label">Tổng số tin đã gửi</div>
                    <div class="stat-value"><?= number_format($stats['total_sent'] ?? 0) ?></div>
                </div>
            </div>
            <div class="stat-card-premium">
                <div class="stat-icon-wrapper stat-icon-orange">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="stat-label">Gửi thành công</div>
                    <div class="stat-value"><?= number_format($stats['total_success'] ?? 0) ?></div>
                </div>
            </div>
            <div class="stat-card-premium">
                <div class="stat-icon-wrapper stat-icon-purple">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div>
                    <div class="stat-label">Gửi thất bại</div>
                    <div class="stat-value"><?= number_format($stats['total_fail'] ?? 0) ?></div>
                </div>
            </div>
        </div>

        <!-- Campaigns Table -->
        <div class="premium-card premium-card-full">
            <div class="table-responsive">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>Thông tin chiến dịch</th>
                            <th>Mẫu tin Zalo</th>
                            <th>Bộ lọc nhận tin</th>
                            <th>Tiến độ gửi</th>
                            <th>Trạng thái</th>
                            <th>Thời điểm tạo</th>
                            <th class="table-action-cell">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($campaigns)): ?>
                            <tr>
                                <td colspan="7" class="zns-empty-cell">
                                    <i class="fas fa-folder-open"></i>
                                    Chưa có chiến dịch nào được tạo. <a href="<?= base_url('zalo/campaigns/create') ?>" class="zns-empty-link">Tạo ngay!</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($campaigns as $campaign): ?>
                                <?php 
                                    $percent = 0;
                                    if ($campaign['total_recipients'] > 0) {
                                        $percent = round(($campaign['sent_count'] / $campaign['total_recipients']) * 100);
                                    }
                                     
                                     // Phân giải filter_criteria an toàn
                                     $filters = [];
                                     if (isset($campaign['filter_criteria'])) {
                                         if (is_array($campaign['filter_criteria'])) {
                                             $filters = $campaign['filter_criteria'];
                                         } elseif (is_object($campaign['filter_criteria'])) {
                                             $filters = (array)$campaign['filter_criteria'];
                                         } elseif (is_string($campaign['filter_criteria'])) {
                                             $filters = json_decode($campaign['filter_criteria'], true) ?: [];
                                         }
                                     }

                                     $filterText = '';
                                     if (!empty($filters)) {
                                         $filterParts = [];
                                         if (!empty($filters['care_status'])) $filterParts[] = 'Trạng thái: ' . $filters['care_status'];
                                         if (!empty($filters['customer_segment'])) $filterParts[] = 'Phân khúc: ' . $filters['customer_segment'];
                                         if (!empty($filters['tag_id'])) $filterParts[] = 'Tag #' . $filters['tag_id'];
                                         $filterText = implode(', ', $filterParts);
                                     }
                                     if (empty($filterText)) {
                                         // Phân giải customer_ids an toàn
                                         $manualIds = [];
                                         if (isset($campaign['customer_ids'])) {
                                             if (is_array($campaign['customer_ids'])) {
                                                 $manualIds = $campaign['customer_ids'];
                                             } elseif (is_object($campaign['customer_ids'])) {
                                                 $manualIds = (array)$campaign['customer_ids'];
                                             } elseif (is_string($campaign['customer_ids'])) {
                                                 $manualIds = json_decode($campaign['customer_ids'], true) ?: [];
                                             }
                                         }
                                         if (!empty($manualIds)) {
                                             $filterText = 'Chọn thủ công (' . count($manualIds) . ' KH)';
                                         } else {
                                             $filterText = 'Tất cả khách hàng';
                                         }
                                     }
                                ?>
                                <tr>
                                    <td class="zns-cell-campaign">
                                        <div class="text-strong-dark truncate-line" title="<?= esc($campaign['name']) ?>"><?= esc($campaign['name']) ?></div>
                                        <?php if (!empty($campaign['description'])): ?>
                                            <div class="text-subtle-small truncate-line" title="<?= esc($campaign['description']) ?>"><?= esc($campaign['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="zns-cell-template-wide">
                                        <div class="text-medium-dark truncate-line" title="<?= esc($campaign['template_name'] ?? 'Mẫu không tên') ?>"><?= esc($campaign['template_name'] ?? 'Mẫu không tên') ?></div>
                                        <div class="text-muted-small">ID: <?= esc($campaign['zalo_template_id'] ?? '') ?></div>
                                    </td>
                                    <td class="zns-cell-filter table-filter-cell">
                                        <div class="truncate-line" title="<?= esc($filterText) ?>"><?= esc($filterText) ?></div>
                                    </td>
                                    <td class="zns-cell-progress">
                                        <div class="progress-count-row">
                                            <span><?= $percent ?>%</span>
                                            <span><?= $campaign['sent_count'] ?> / <?= $campaign['total_recipients'] ?></span>
                                        </div>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar-fill <?= $campaign['status'] === 'failed' ? 'progress-bar-fill-failed' : 'progress-bar-fill-success' ?>" data-progress-percent="<?= $percent ?>"></div>
                                        </div>
                                        <div class="progress-result-row">
                                            <span class="progress-success"><i class="fas fa-check"></i> <?= $campaign['success_count'] ?></span>
                                            <span class="progress-fail"><i class="fas fa-times"></i> <?= $campaign['fail_count'] ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-zns badge-<?= $campaign['status'] ?>">
                                            <?php 
                                                switch($campaign['status']) {
                                                    case 'draft': echo 'Nháp'; break;
                                                    case 'sending': echo 'Đang gửi'; break;
                                                    case 'completed': echo 'Hoàn thành'; break;
                                                    case 'failed': echo 'Thất bại'; break;
                                                    case 'cancelled': echo 'Đã hủy'; break;
                                                    default: echo $campaign['status'];
                                                }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="table-time-cell">
                                        <?= date('d/m/Y H:i', strtotime($campaign['created_at'])) ?>
                                    </td>
                                    <td class="table-action-cell">
                                        <a href="<?= base_url('zalo/campaigns/detail/' . $campaign['id']) ?>" class="btn-action-custom btn-action-secondary" title="Xem chi tiết & Logs">
                                            <i class="fas fa-eye"></i> Chi tiết
                                        </a>
                                        <?php if ($campaign['status'] === 'draft'): ?>
                                            <button class="btn-action-custom btn-action-primary btn-execute-campaign" data-id="<?= $campaign['id'] ?>" title="Gửi hàng loạt ngay">
                                                <i class="fas fa-paper-plane"></i> Gửi ngay
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($pager)): ?>
                <div class="pagination-wrapper">
                    <?= $pager->links() ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab 2: Individual Quick Logs Content -->
    <div id="tab-individual" class="tab-content-panel">
        <!-- Stats -->
        <div class="stats-grid-premium">
            <div class="stat-card-premium">
                <div class="stat-icon-wrapper stat-icon-blue">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div>
                    <div class="stat-label">Tổng gửi nhanh</div>
                    <div class="stat-value"><?= number_format($individualStats['total_sent'] ?? 0) ?></div>
                </div>
            </div>
            <div class="stat-card-premium">
                <div class="stat-icon-wrapper stat-icon-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="stat-label">Gửi thành công</div>
                    <div class="stat-value"><?= number_format($individualStats['total_success'] ?? 0) ?></div>
                </div>
            </div>
            <div class="stat-card-premium">
                <div class="stat-icon-wrapper stat-icon-red">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div>
                    <div class="stat-label">Gửi thất bại</div>
                    <div class="stat-value"><?= number_format($individualStats['total_fail'] ?? 0) ?></div>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="premium-card premium-card-full">
            <div class="table-responsive">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Mẫu tin Zalo</th>
                            <th>Dữ liệu gửi</th>
                            <th>Người gửi</th>
                            <th>Thời điểm gửi</th>
                            <th class="table-action-cell">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($individualLogs)): ?>
                            <tr>
                                <td colspan="7" class="zns-empty-cell">
                                    <i class="fas fa-paper-plane"></i>
                                    Chưa có nhật ký gửi nhanh đơn lẻ nào được thực hiện.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($individualLogs as $log): ?>
                                <tr>
                                    <td class="zns-cell-customer">
                                        <div class="text-strong-dark truncate-line" title="<?= esc($log['customer_name'] ?? 'Khách vãng lai') ?>"><?= esc($log['customer_name'] ?? 'Khách vãng lai') ?></div>
                                        <div class="text-muted-small">Mã KH: <?= esc($log['customer_code'] ?? 'N/A') ?></div>
                                    </td>
                                    <td class="table-phone-cell">
                                        <?= esc($log['phone']) ?>
                                    </td>
                                    <td class="zns-cell-customer">
                                        <div class="text-strong-blue truncate-line" title="<?= esc($log['template_name'] ?? 'Mẫu không tên') ?>"><?= esc($log['template_name'] ?? 'Mẫu không tên') ?></div>
                                        <div class="text-muted-small">ID: <?= esc($log['template_id']) ?></div>
                                    </td>
                                    <td>
                                        <div class="zns-param-list">
                                             <?php 
                                                 $params = [];
                                                 if (isset($log['template_data'])) {
                                                     if (is_array($log['template_data'])) {
                                                         $params = $log['template_data'];
                                                     } elseif (is_object($log['template_data'])) {
                                                         $params = (array)$log['template_data'];
                                                     } elseif (is_string($log['template_data'])) {
                                                         $params = json_decode($log['template_data'], true) ?: [];
                                                     }
                                                 }
                                                 if (!empty($params)) {
                                                     foreach ($params as $k => $v) {
                                                         $vStr = is_array($v) || is_object($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
                                                         echo '<span class="param-badge param-badge-compact" title="'.esc($k).'">'.esc($k).': <strong>'.esc($vStr).'</strong></span>';
                                                     }
                                                 } else {
                                                     echo '<span class="zns-empty-param">Trống</span>';
                                                 }
                                             ?>
                                        </div>
                                    </td>
                                    <td class="table-sender-cell">
                                        <?= esc($log['sender_name'] ?? 'Hệ thống') ?>
                                    </td>
                                    <td class="table-time-cell">
                                        <?= !empty($log['sent_at']) ? date('d/m/Y H:i', strtotime($log['sent_at'])) : date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td class="table-action-cell">
                                        <?php if ($log['status'] === 'sent'): ?>
                                            <span class="badge-zns badge-completed"><i class="fas fa-check-circle"></i> Thành công</span>
                                        <?php else: ?>
                                            <span class="badge-zns badge-failed badge-help" title="<?= esc($log['error_message']) ?>"><i class="fas fa-times-circle"></i> Thất bại</span>
                                            <div class="zns-error-detail" title="<?= esc($log['error_message']) ?>">
                                                <?= esc($log['error_message']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination for Individual Logs -->
            <?php if (isset($individualPager)): ?>
                <div class="pagination-wrapper">
                    <?= $individualPager->links('individual_logs') ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/zalo_campaigns.js') ?>"></script>
<?= $this->endSection() ?>
