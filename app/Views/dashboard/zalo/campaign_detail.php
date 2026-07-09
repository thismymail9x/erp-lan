<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/zalo.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="zalo-page-container zns-campaign-detail-page zns-detail-page" data-execute-url="<?= base_url('zalo/campaigns/execute/') ?>">
    
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title zns-page-title-plain">Chi tiết Chiến dịch ZNS</h2>
            <p class="content-subtitle zns-page-help">Theo dõi tiến độ, thống kê kết quả gửi và lịch sử log tin nhắn</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('zalo/campaigns') ?>" class="btn-filter-secondary zns-back-action">
                <i class="fas fa-arrow-left"></i> Danh sách chiến dịch
            </a>
            <?php if ($campaign['status'] === 'draft'): ?>
                <button class="btn-premium zns-primary-action" id="btn-execute-campaign-detail" data-id="<?= $campaign['id'] ?>">
                    <i class="fas fa-paper-plane"></i> Thực thi chiến dịch ngay
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Metadata & Progress Card -->
    <div class="form-section-card zns-detail-card">
        <div class="zns-detail-summary-grid">
            <div>
                <h3 class="zns-detail-name"><?= esc($campaign['name']) ?></h3>
                <?php if (!empty($campaign['description'])): ?>
                    <p class="zns-detail-description"><?= esc($campaign['description']) ?></p>
                <?php endif; ?>

                <div class="zns-detail-meta-grid">
                    <div><strong>Mẫu tin Zalo:</strong> <?= esc($campaign['template_name']) ?> (<code class="zns-template-code"><?= esc($campaign['zalo_template_id']) ?></code>)</div>
                    <div>
                        <strong>Trạng thái:</strong> 
                        <span class="zns-status-text">
                            <?php 
                                switch($campaign['status']) {
                                    case 'draft': echo 'Bản nháp'; break;
                                    case 'sending': echo 'Đang gửi'; break;
                                    case 'completed': echo 'Hoàn thành'; break;
                                    case 'failed': echo 'Thất bại'; break;
                                    case 'cancelled': echo 'Đã hủy'; break;
                                    default: echo $campaign['status'];
                                }
                            ?>
                        </span>
                    </div>
                    <div><strong>Thời điểm tạo:</strong> <?= date('d/m/Y H:i:s', strtotime($campaign['created_at'])) ?></div>
                    <?php if ($campaign['started_at']): ?>
                        <div><strong>Bắt đầu gửi:</strong> <?= date('d/m/Y H:i:s', strtotime($campaign['started_at'])) ?></div>
                    <?php endif; ?>
                    <?php if ($campaign['completed_at']): ?>
                        <div><strong>Hoàn thành lúc:</strong> <?= date('d/m/Y H:i:s', strtotime($campaign['completed_at'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Progress Circle or Bar -->
            <?php 
                $percent = 0;
                if ($campaign['total_recipients'] > 0) {
                    $percent = round(($campaign['sent_count'] / $campaign['total_recipients']) * 100);
                }
            ?>
            <div class="zns-progress-card">
                <div class="zns-progress-heading">
                    <span>Tiến độ chiến dịch</span>
                    <span><?= $percent ?>%</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill <?= $campaign['status'] === 'failed' ? 'progress-bar-fill-failed' : 'progress-bar-fill-success' ?>" data-progress-percent="<?= $percent ?>"></div>
                </div>
                <div class="zns-progress-footer">
                    <span>Đã gửi: <strong><?= $campaign['sent_count'] ?> / <?= $campaign['total_recipients'] ?></strong></span>
                    <span>Tỷ lệ đạt</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Mini stats grid -->
    <div class="zns-mini-stats-grid">
        <div class="zns-mini-stat-card">
            <div class="zns-mini-stat-label">Khách hàng nhận tin</div>
            <div class="zns-mini-stat-value"><?= $campaign['total_recipients'] ?></div>
        </div>
        <div class="zns-mini-stat-card">
            <div class="zns-mini-stat-label">Tổng số cURL gửi đi</div>
            <div class="zns-mini-stat-value"><?= $campaign['sent_count'] ?></div>
        </div>
        <div class="zns-mini-stat-card zns-mini-stat-success">
            <div class="zns-mini-stat-label">Gửi thành công</div>
            <div class="zns-mini-stat-value"><?= $campaign['success_count'] ?></div>
        </div>
        <div class="zns-mini-stat-card zns-mini-stat-danger">
            <div class="zns-mini-stat-label">Gửi thất bại</div>
            <div class="zns-mini-stat-value"><?= $campaign['fail_count'] ?></div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="premium-card premium-card-full">
        <h4 class="zns-card-title">Nhật ký gửi tin nhắn chi tiết</h4>
        
        <div class="table-responsive">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>Khách hàng</th>
                        <th>Số điện thoại</th>
                        <th>Dữ liệu gửi (JSON)</th>
                        <th>Trạng thái</th>
                        <th>Thời gian gửi</th>
                        <th>Chi tiết lỗi (nếu có)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="zns-table-empty">
                                Chưa có lịch sử log nào được ghi nhận cho chiến dịch này.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php 
                                $templateData = [];
                                if (isset($log['template_data'])) {
                                    if (is_array($log['template_data'])) {
                                        $templateData = $log['template_data'];
                                    } elseif (is_object($log['template_data'])) {
                                        $templateData = (array)$log['template_data'];
                                    } elseif (is_string($log['template_data'])) {
                                        $templateData = json_decode($log['template_data'], true) ?: [];
                                    }
                                }
                                $dataDisplay = [];
                                foreach ($templateData as $key => $val) {
                                    $valStr = is_array($val) || is_object($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val;
                                    $dataDisplay[] = "<strong>{$key}</strong>: " . esc($valStr);
                                }
                            ?>
                            <tr>
                                <td>
                                    <?php if ($log['customer_name']): ?>
                                        <div class="zns-log-customer-name"><?= esc($log['customer_name']) ?></div>
                                        <div class="zns-log-customer-code">Mã: <?= esc($log['customer_code']) ?></div>
                                    <?php else: ?>
                                        <span class="zns-muted-italic">Không xác định (#<?= $log['customer_id'] ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= esc($log['phone']) ?>
                                </td>
                                <td>
                                    <div class="zns-log-data-text">
                                        <?= implode('<br>', $dataDisplay) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-zns badge-<?= $log['status'] ?>">
                                        <?php 
                                            switch($log['status']) {
                                                case 'pending': echo 'Chờ gửi'; break;
                                                case 'sent': echo 'Đã gửi'; break;
                                                case 'delivered': echo 'Đã nhận'; break;
                                                case 'failed': echo 'Thất bại'; break;
                                                default: echo $log['status'];
                                            }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $log['sent_at'] ? date('d/m/Y H:i:s', strtotime($log['sent_at'])) : date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($log['status'] === 'failed'): ?>
                                        <div class="zns-log-error-code">Lỗi [<?= $log['error_code'] ?>]</div>
                                        <div><?= esc($log['error_message']) ?></div>
                                    <?php else: ?>
                                        <span class="zns-placeholder-dash">-</span>
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
            <div class="zns-pagination-right">
                <?= $pager->links() ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/zalo_campaign_detail.js') ?>"></script>
<?= $this->endSection() ?>

