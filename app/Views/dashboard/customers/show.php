<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customers.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/customer_care.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/customer_relationship.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/documents.css') ?>">
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    if (!function_exists('get_customer_doc_icon')) {
        function get_customer_doc_icon($ext) {
            $icons = [
                'pdf' => 'fa-file-pdf',
                'doc' => 'fa-file-word',
                'docx' => 'fa-file-word',
                'jpg' => 'fa-file-image',
                'jpeg' => 'fa-file-image',
                'png' => 'fa-file-image',
                'gif' => 'fa-file-image',
                'webp' => 'fa-file-image',
                'xls' => 'fa-file-excel',
                'xlsx' => 'fa-file-excel',
                'zip' => 'fa-file-archive',
                'rar' => 'fa-file-archive',
            ];

            return $icons[strtolower((string) $ext)] ?? 'fa-file-alt';
        }
    }

    if (!function_exists('format_customer_doc_bytes')) {
        function format_customer_doc_bytes($bytes) {
            if (!is_numeric($bytes) || $bytes < 0) return '0 B';
            if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
            if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
            if ($bytes >= 1024) return number_format($bytes / 1024, 0) . ' KB';
            return $bytes . ' B';
        }
    }

    $relationshipMetrics = $relationshipProfile['metrics'] ?? [];
    $relationshipScore = (int) ($relationshipMetrics['relationship_score'] ?? ($customer['relationship_score'] ?? 0));
    $relationshipStatus = $relationshipMetrics['status_key'] ?? ($customer['relationship_status'] ?? 'healthy');
    $relationshipStatusLabel = $relationshipMetrics['status_label'] ?? 'Ổn định';
    $opportunityStats = $relationshipProfile['opportunity_stats'] ?? ['active' => 0, 'estimated_value' => 0];
    $relationshipLevelLabels = [
        'lead' => 'Lead',
        'active' => 'Đang sử dụng dịch vụ',
        'loyal' => 'Thân thiết',
        'strategic' => 'Chiến lược',
    ];
    $relationshipStatusLabels = [
        'healthy' => 'Ổn định',
        'watch' => 'Cần chăm sóc',
        'risk' => 'Rủi ro nguội',
        'critical' => 'Cần kích hoạt lại',
    ];
    $profileDocumentFileCount = 0;
    if (!empty($documents) && is_array($documents)) {
        foreach ($documents as $profileDocument) {
            $profileDocumentFileCount += max(1, (int)($profileDocument['attachment_count'] ?? 1));
        }
    }
    $canDeleteDocuments = has_permission('sys.admin');
?>
<div class="customer-profile-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Hồ sơ: <?= esc($customer['name']) ?></h2>
            <p class="content-subtitle"><?= esc($customer['code']) ?> • <?= ($customer['type'] == 'ca_nhan') ? 'Cá nhân' : 'Doanh nghiệp' ?></p>
        </div>
        <div class="header-controls">
            <?php if (isset($canEdit) && $canEdit) { ?>
            <a href="<?= base_url('customers/edit/' . $customer['id']) ?>" class="btn-secondary">
                <i class="fas fa-edit"></i> Sửa
            </a>
            <?php } ?>
            <a href="<?= base_url('cases/create?customer_id=' . $customer['id']) ?>" class="btn-premium">
                <i class="fas fa-plus-circle"></i> Thêm
            </a>
        </div>
    </div>

    <div class="profile-grid-layout">
        <!-- Sidebar: Quick Info & Stats -->
        <div class="profile-sidebar">
            <div class="premium-card prof-sidebar-info">
                <div class="prof-avatar-box">
                    <div class="prof-avatar-circle">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="prof-name-title"><?= esc($customer['name']) ?></h3>
                    <?php if ($customer['is_blacklist']) { ?>
                        <span class="badge-danger-minimal m-l-10"><i class="fas fa-user-slash"></i> Blacklist</span>
                    <?php } ?>
                </div>

                <div class="prof-info-list">
                    <?php 
                        $canSeePhone = has_permission('sys.admin') || ($customer['created_by'] == session()->get('employee_id'));
                        $maskedPhone = $canSeePhone ? $customer['phone'] : substr($customer['phone'], 0, 4) . '****' . substr($customer['phone'], -3);
                        $maskedEmail = $canSeePhone ? ($customer['email'] ?: '--') : '***@***.***';
                        $maskedIdentity = $canSeePhone ? ($customer['identity_number'] ?: '--') : '********';
                    ?>
                    <div class="prof-info-row">
                        <span class="prof-label-dim">SĐT:</span>
                        <span class="prof-val-bold"><?= esc($maskedPhone) ?></span>
                    </div>
                    <div class="prof-info-row">
                        <span class="prof-label-dim">Email:</span>
                        <span class="prof-val-bold"><?= esc($maskedEmail) ?></span>
                    </div>
                    <div class="prof-info-row">
                        <span class="prof-label-dim">Ng&#224;y sinh:</span>
                        <span class="prof-val-bold"><?= !empty($customer['date_of_birth']) && strtotime($customer['date_of_birth']) ? date('d/m/Y', strtotime($customer['date_of_birth'])) : '--' ?></span>
                    </div>
                    <div class="prof-info-row">
                        <span class="prof-label-dim">Nhân sự chăm sóc:</span>
                        <span class="prof-val-bold" style="color: var(--apple-blue);"><?= esc($careStaffName ?: 'Chưa phân bổ') ?></span>
                    </div>
                    <div class="prof-info-row">
                        <span class="prof-label-dim">Qu&#224; t&#7863;ng:</span>
                        <label class="gift-checkbox-option gift-checkbox-readonly">
                            <input type="checkbox" <?= !empty($customer['has_received_gift']) ? 'checked' : '' ?> disabled>
                            <span>&#272;&#227; t&#7863;ng qu&#224;</span>
                        </label>
                    </div>
                    <div class="prof-info-row-last">
                        <span class="prof-label-dim">Địa chỉ:</span>
                        <span class="prof-val-align-right"><?= esc($customer['address'] ?: '--') ?></span>
                    </div>
                </div>
            </div>

            <div class="premium-card prof-health-section">
                <h4 class="prof-health-title">Chỉ số</h4>
                <div class="prof-health-grid">
                    <div class="prof-stat-box">
                        <div class="prof-stat-val-blue"><?= $customer['total_cases'] ?></div>
                        <div class="prof-stat-label">Vụ việc</div>
                    </div>
                    <div class="prof-stat-box">
                        <div class="prof-stat-val-green"><?= number_format($customer['total_revenue'] / 1000000, 1) ?>M</div>
                        <div class="prof-stat-label">Doanh thu</div>
                    </div>
                </div>
            </div>

            <div class="premium-card relationship-score-card">
                <div class="relationship-score-header">
                    <div>
                        <h4 class="prof-health-title">Quan hệ</h4>
                        <span class="relationship-status-badge <?= esc($relationshipStatus) ?>">
                            <i class="fas fa-heartbeat"></i> <?= esc($relationshipStatusLabels[$relationshipStatus] ?? $relationshipStatusLabel) ?>
                        </span>
                    </div>
                    <div class="relationship-score-value"><?= esc($relationshipScore) ?></div>
                </div>
                <div class="relationship-progress"><span style="width: <?= min(100, max(0, $relationshipScore)) ?>%;"></span></div>
                <div class="relationship-row">
                    <span class="prof-label-dim">Tương tác cuối:</span>
                    <strong><?= !empty($customer['last_contact_date']) ? date('d/m/Y', strtotime($customer['last_contact_date'])) : 'Chưa có' ?></strong>
                </div>
                <div class="relationship-row">
                    <span class="prof-label-dim">Tương tác kế tiếp:</span>
                    <strong><?= !empty($customer['next_interaction_date']) ? date('d/m/Y', strtotime($customer['next_interaction_date'])) : 'Chưa đặt' ?></strong>
                </div>
                <div class="relationship-row">
                    <span class="prof-label-dim">Cơ hội mở:</span>
                    <strong><?= esc($opportunityStats['active'] ?? 0) ?></strong>
                </div>
            </div>
        </div>

        <!-- Main Content: Tabs -->
        <div class="profile-main">
            <div class="premium-card premium-card-full" style="padding: 0;">
                <div class="prof-tabs-nav">
                    <div class="tabs-container" id="customerModuleTabs">
                        <button class="tab-btn active" data-tab="overview"><i class="fas fa-info-circle"></i> Q.Sát</button>
                        <button class="tab-btn" data-tab="cases"><i class="fas fa-briefcase"></i> Vụ việc (<?= !empty($cases) && is_array($cases) ? count($cases) : 0 ?>)</button>
                        <button class="tab-btn" data-tab="interactions"><i class="fas fa-comments"></i> Tương tác</button>
                        <button class="tab-btn" data-tab="chat-consultation"><i class="fas fa-comments"></i> Tư vấn Chat (<?= !empty($chatHistory) ? count($chatHistory) : 0 ?>)</button>
                        <button class="tab-btn" data-tab="finance"><i class="fas fa-wallet"></i> Tài chính</button>
                        <button class="tab-btn" data-tab="docs"><i class="fas fa-file-alt"></i> Hồ sơ (<?= esc($profileDocumentFileCount) ?>)</button>
                        <button class="tab-btn" data-tab="relationship"><i class="fas fa-handshake"></i> Quan hệ</button>
                        <button class="tab-btn" data-tab="opportunities"><i class="fas fa-chart-line"></i> Cơ hội (<?= !empty($opportunities) && is_array($opportunities) ? count($opportunities) : 0 ?>)</button>
                        <button class="tab-btn" data-tab="customer-care"><i class="fas fa-hand-holding-heart"></i> CSKH</button>
                    </div>
                </div>

                <div class="prof-tabs-content">
                    <!-- Tab: Overview -->
                    <div class="tab-pane active" id="overview">
                        <div class="prof-overview-grid">
                            <div>
                                <h4 class="prof-section-h4"><i class="fas fa-id-card prof-section-icon"></i>Định danh</h4>
                                <table class="prof-info-table">
                                    <tr>
                                        <td class="prof-table-label-td">Loại định danh:</td>
                                        <td class="prof-table-val-td"><?= strtoupper(esc($customer['identity_type'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="prof-table-label-td">Số định danh:</td>
                                        <td class="prof-table-val-td"><?= esc($maskedIdentity) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="prof-table-label-td">Ngày cấp:</td>
                                        <td class="prof-table-val-td-med"><?= ($canSeePhone && $customer['issue_date']) ? date('d/m/Y', strtotime($customer['issue_date'])) : '--' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="prof-table-label-td">Nơi cấp:</td>
                                        <td class="prof-table-val-td-med"><?= $canSeePhone ? esc($customer['issued_by'] ?: '--') : '--' ?></td>
                                    </tr>
                                </table>
                            </div>
                            <?php if ($customer['type'] == 'doanh_nghiep') { ?>
                            <div>
                                <h4 class="prof-section-h4"><i class="fas fa-building prof-section-icon"></i>Doanh nghiệp</h4>
                                <table class="prof-info-table">
                                    <tr>
                                        <td class="prof-table-label-td">Tên công ty:</td>
                                        <td class="prof-table-val-td"><?= esc($customer['company_name']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="prof-table-label-td">Mã số thuế:</td>
                                        <td class="prof-table-val-td"><?= esc($customer['tax_code']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="prof-table-label-td">Đăng ký kinh doanh:</td>
                                        <td class="prof-table-val-td-med"><?= esc($customer['biz_registration_number'] ?: '--') ?></td>
                                    </tr>
                                </table>
                            </div>
                            <?php } ?>
                        </div>

                        <div class="prof-tags-box">
                            <h4 class="prof-section-h4"><i class="fas fa-tags prof-section-icon"></i>Nhãn & Phân loại</h4>
                            <div class="prof-tags-flex">
                                <?php if (empty($tags)) { ?>
                                    <span class="text-xs text-muted-dark" style="margin-left: 10px;">Chưa gắn nhãn.</span>
                                <?php } else { ?>
                                    <?php foreach ($tags as $tag) { ?>
                                        <span class="badge-log" style="background-color: <?= esc($tag['color']) ?>; color: white;">
                                            <i class="fas fa-tag"></i> <?= esc($tag['original_name'] ?? $tag['name']) ?>
                                            <?php if ($tag['type'] === 'private' && has_permission('sys.admin')) { ?>
                                                <span class="text-apple-red" title="Nhãn cá nhân của <?= esc($tag['owner_name']) ?>" style="font-weight: bold; margin-left: 2px;">*</span>
                                            <?php } ?>
                                        </span>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Cases -->
                    <div class="tab-pane" id="cases">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th>Mã hồ sơ</th>
                                    <th>Vụ việc</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($cases) && is_array($cases)) { ?>
                                    <?php foreach ($cases as $case) { ?>
                                    <tr>
                                        <td><span class="badge-secondary-minimal"><?= esc($case['code']) ?></span></td>
                                        <td class="prof-val-bold"><?= esc($case['title']) ?></td>
                                        <td><?= esc($case['status']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($case['created_at'])) ?></td>
                                    </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr><td colspan="4" style="text-align: center; opacity: 0.5; padding: 20px;">Chưa có vụ việc nào.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab: Interactions -->
                    <div class="tab-pane" id="interactions">
                        <div class="prof-interaction-header">
                            <h4 style="margin: 0;">Lịch sử tương tác</h4>
                            <button class="btn-premium-sm" onclick="document.getElementById('modalInteraction').style.display='flex'">
                                <i class="fas fa-plus"></i> Ghi chú tương tác
                            </button>
                        </div>
                        <div class="prof-interaction-timeline">
                            <?php if (empty($interactions) || !is_array($interactions)) { ?>
                                <p style="text-align: center; opacity: 0.5; padding: 40px;">Chưa có lịch sử tương tác nào.</p>
                            <?php } else { ?>
                                <?php foreach ($interactions as $int) { ?>
                                    <div class="prof-timeline-item">
                                        <div class="prof-timeline-dot"></div>
                                        <div class="prof-timeline-meta"><?= date('d/m/Y H:i', strtotime($int['interaction_date'])) ?> • <?= esc($int['staff_email'] ?? '--') ?></div>
                                        <div class="prof-timeline-summary"><?= esc($int['summary']) ?></div>
                                        <div class="opportunity-meta">
                                            <?php if (!empty($int['interaction_result'])): ?><span>Kết quả: <?= esc($int['interaction_result']) ?></span><?php endif; ?>
                                            <?php if (!empty($int['importance_level'])): ?><span>Ưu tiên: <?= esc($int['importance_level']) ?></span><?php endif; ?>
                                            <?php if (!empty($int['next_follow_up'])): ?><span>Hẹn lại: <?= date('d/m/Y H:i', strtotime($int['next_follow_up'])) ?></span><?php endif; ?>
                                        </div>
                                        <div class="prof-timeline-content ql-editor" style="padding: 0; min-height: auto; font-size: inherit; color: inherit;"><?= $int['detailed_content'] ?></div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Tab: Finance -->
                    <div class="tab-pane" id="finance">
                        <h4 style="margin-bottom: 20px;">Lịch sử thanh toán</h4>
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Số tiền (VND)</th>
                                    <th>Phương thức</th>
                                    <th>Nội dung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments) || !is_array($payments)) { ?>
                                    <tr><td colspan="4" style="text-align: center; opacity: 0.5; padding: 20px;">Chưa có lịch sử thanh toán.</td></tr>
                                <?php } else { ?>
                                    <?php foreach ($payments as $pay) { ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($pay['payment_date'] ?? 'now')) ?></td>
                                        <td class="prof-val-bold"><?= number_format($pay['amount'] ?? 0, 0, ',', '.') ?></td>
                                        <td><?= esc($pay['method'] ?? '--') ?></td>
                                        <td><?= esc($pay['description'] ?? '--') ?></td>
                                    </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="tab-pane" id="docs">
                        <div class="vault-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h4 style="margin: 0;">Hồ sơ số hóa (Vault)</h4>
                            <div style="display: flex; gap: 10px;">
                                <button class="btn-secondary-sm" onclick="openVaultModal()">
                                    <i class="fas fa-archive"></i> Kho tài liệu
                                </button>
                                <button class="btn-premium-sm" onclick="document.getElementById('modalUpload').style.display='flex'">
                                    <i class="fas fa-upload m-r-8"></i> Tải tài liệu mới
                                </button>
                            </div>
                        </div>
                        <div class="vault-grid">
                            <?php if (!empty($documents) && is_array($documents)) { ?>
                                <?php foreach ($documents as $doc) { ?>
                                    <?php
                                        $attachmentCount = (int)($doc['attachment_count'] ?? 1);
                                        $attachmentIds = !empty($doc['attachment_ids']) ? explode(',', $doc['attachment_ids']) : [];
                                        $attachmentNames = !empty($doc['attachment_names']) ? explode("\n", $doc['attachment_names']) : [];
                                        $docIcon = $attachmentCount > 1 ? 'fa-layer-group' : get_customer_doc_icon($doc['file_type'] ?? '');
                                    ?>
                                    <div class="premium-card vault-card">
                                        <div class="vault-icon">
                                            <i class="fas <?= esc($docIcon) ?>"></i>
                                        </div>
                                        <div class="vault-doc-type" style="font-size: 11px; text-transform: uppercase; color: var(--apple-blue); font-weight: 600;">
                                            <?= esc($doc['document_category'] ?? 'Khác') ?>
                                        </div>
                                        <div class="vault-file-name" style="font-weight: 500; margin: 5px 0;"><?= esc($doc['file_name'] ?? 'Tài liệu') ?></div>
                                        <div class="text-xs text-muted-dark">
                                            <?= esc(format_customer_doc_bytes($doc['total_size'] ?? $doc['size'] ?? 0)) ?>
                                            <?php if ($attachmentCount > 1) { ?>
                                                • <?= esc($attachmentCount) ?> tệp
                                            <?php } ?>
                                        </div>
                                        <div class="vault-actions">
                                            <?php if ($attachmentCount <= 1) { ?>
                                                <a href="<?= base_url('documents/view/' . ($doc['id'] ?? 0)) ?>?preview=1" class="btn-secondary-sm" target="_blank">
                                                    <i class="fas fa-eye"></i> Xem
                                                </a>
                                                <a href="<?= base_url('documents/view/' . ($doc['id'] ?? 0)) ?>" class="btn-secondary-sm">
                                                    <i class="fas fa-download"></i> Tải
                                                </a>
                                                <?php if ($canDeleteDocuments) { ?>
                                                    <form action="<?= base_url('documents/delete/' . ($doc['id'] ?? 0)) ?>" method="post" class="customer-document-delete-form" onsubmit="return confirm('Xóa vĩnh viễn tệp này khỏi hồ sơ khách hàng?');">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="customer-document-delete-btn" title="Xóa tệp">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php } ?>
                                            <?php } ?>
                                        </div>
                                        <?php if ($attachmentCount > 1 && !empty($attachmentNames)) { ?>
                                            <div class="document-attachment-list customer-document-attachment-list">
                                                <?php foreach ($attachmentNames as $fileIndex => $attachmentName) { ?>
                                                    <?php
                                                        $attachmentId = $attachmentIds[$fileIndex] ?? 0;
                                                        $attachmentUrl = base_url('documents/view/' . ($doc['id'] ?? 0) . '/file/' . $attachmentId);
                                                    ?>
                                                    <div class="document-attachment-item">
                                                        <span class="document-attachment-name">
                                                            <i class="fas <?= esc(get_customer_doc_icon(pathinfo($attachmentName, PATHINFO_EXTENSION))) ?>"></i>
                                                            <?= esc($attachmentName) ?>
                                                        </span>
                                                        <span class="document-attachment-actions">
                                                            <a href="<?= $attachmentUrl ?>?preview=1" target="_blank" class="document-attachment-action" title="Xem trước">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="<?= $attachmentUrl ?>" class="document-attachment-action" title="Tải xuống">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            <?php if ($canDeleteDocuments && !empty($attachmentId)) { ?>
                                                                <form action="<?= base_url('documents/delete/' . ($doc['id'] ?? 0) . '/file/' . $attachmentId) ?>" method="post" class="customer-document-delete-form" onsubmit="return confirm('Xóa vĩnh viễn tệp này khỏi bộ hồ sơ?');">
                                                                    <?= csrf_field() ?>
                                                                    <button type="submit" class="document-attachment-action customer-document-delete-btn" title="Xóa tệp">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </form>
                                                            <?php } ?>
                                                        </span>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <p style="grid-column: 1/-1; text-align: center; opacity: 0.5; padding: 20px;">Chưa có tài liệu nào.</p>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Tab: Chat Consultation -->
                    <div class="tab-pane" id="chat-consultation">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-comments" style="color: #0ea5e9;"></i> Lịch sử tư vấn Chat
                            </h4>
                            <span style="font-size: 12px; color: #64748b; background: #f1f5f9; padding: 4px 10px; border-radius: 20px; font-weight: 500;">
                                Tổng số tin nhắn: <?= !empty($chatHistory) ? count($chatHistory) : 0 ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($chatHistory) && is_array($chatHistory)): ?>
                            <div class="chat-timeline-container" style="display: flex; flex-direction: column; gap: 16px; max-height: 550px; overflow-y: auto; padding: 16px; background: #fafafa; border-radius: 12px; border: 1px solid #f1f5f9; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                                <?php foreach ($chatHistory as $msg): 
                                    $isStaff = !empty($msg['is_staff']);
                                    $bg = $isStaff ? '#e0f2fe' : '#ffffff';
                                    $align = $isStaff ? 'flex-end' : 'flex-start';
                                    $sender = $isStaff ? ($msg['staff_name'] ?: 'Nhân sự') : 'Khách hàng';
                                    $channelLogo = ($msg['channel'] === 'zalo') 
                                        ? '<span style="background: #0068ff; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; margin-left: 6px;">Zalo</span>' 
                                        : '<span style="background: #1877f2; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; margin-left: 6px;">Facebook</span>';
                                ?>
                                    <div style="align-self: <?= $align ?>; max-width: 80%; display: flex; flex-direction: column; align-items: <?= $isStaff ? 'flex-end' : 'flex-start' ?>;">
                                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px; font-size: 11px; color: #64748b;">
                                            <strong><?= esc($sender) ?></strong>
                                            <span><?= date('H:i d/m/Y', strtotime($msg['created_at'])) ?></span>
                                            <?= $channelLogo ?>
                                        </div>
                                        <div style="background: <?= $bg ?>; padding: 12px 16px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; font-size: 13px; color: #334155; line-height: 1.5; white-space: pre-wrap; word-break: break-word;">
                                            <?= esc($msg['message_text']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 60px 20px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px;">
                                <i class="fas fa-comment-slash" style="font-size: 40px; color: #94a3b8;"></i>
                                <p style="margin: 0; font-size: 14px; color: #64748b; font-weight: 500;">Chưa có lịch sử tin nhắn tư vấn từ Zalo OA hay Facebook Messenger cho khách hàng này.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane" id="relationship">
                        <div class="relationship-panel-grid">
                            <div>
                                <h4 class="prof-section-h4"><i class="fas fa-handshake prof-section-icon"></i>Hồ sơ quan hệ</h4>
                                <form action="<?= base_url('customers/update-relationship/' . $customer['id']) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <div class="relationship-form-grid">
                                        <div class="form-group-premium">
                                            <label class="label-premium">Cấp độ quan hệ</label>
                                            <select name="relationship_level" class="form-control-premium" <?= empty($canEdit) ? 'disabled' : '' ?>>
                                                <?php foreach ($relationshipLevelLabels as $key => $label): ?>
                                                    <option value="<?= esc($key) ?>" <?= ($customer['relationship_level'] ?? 'lead') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group-premium">
                                            <label class="label-premium">Trạng thái quan hệ</label>
                                            <select name="relationship_status" class="form-control-premium" <?= empty($canEdit) ? 'disabled' : '' ?>>
                                                <?php foreach ($relationshipStatusLabels as $key => $label): ?>
                                                    <option value="<?= esc($key) ?>" <?= ($customer['relationship_status'] ?? 'healthy') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group-premium">
                                            <label class="label-premium">Điểm quan hệ</label>
                                            <input type="number" min="0" max="100" name="relationship_score" class="form-control-premium" value="<?= esc($relationshipScore) ?>" <?= empty($canEdit) ? 'disabled' : '' ?>>
                                        </div>
                                        <div class="form-group-premium">
                                            <label class="label-premium">Health score</label>
                                            <input type="number" min="0" max="100" name="health_score" class="form-control-premium" value="<?= esc($customer['health_score'] ?? $relationshipScore) ?>" <?= empty($canEdit) ? 'disabled' : '' ?>>
                                        </div>
                                        <div class="form-group-premium">
                                            <label class="label-premium">Ngày tương tác kế tiếp</label>
                                            <input type="date" name="next_interaction_date" class="form-control-premium" value="<?= esc($customer['next_interaction_date'] ?? '') ?>" <?= empty($canEdit) ? 'disabled' : '' ?>>
                                        </div>
                                        <div class="form-group-premium">
                                            <label class="label-premium">Quản lý quan hệ</label>
                                            <select name="relationship_manager_id" class="form-control-premium" <?= empty($canEdit) ? 'disabled' : '' ?>>
                                                <option value="">Chưa phân công</option>
                                                <?php foreach ($employees as $employee): ?>
                                                    <option value="<?= esc($employee['id']) ?>" <?= ($customer['relationship_manager_id'] ?? '') == $employee['id'] ? 'selected' : '' ?>><?= esc($employee['full_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group-premium">
                                            <label class="label-premium">Khách giới thiệu</label>
                                            <select name="referred_by_customer_id" class="form-control-premium" <?= empty($canEdit) ? 'disabled' : '' ?>>
                                                <option value="">Chưa ghi nhận</option>
                                                <?php foreach ($referralCustomers as $refCustomer): ?>
                                                    <option value="<?= esc($refCustomer['id']) ?>" <?= ($customer['referred_by_customer_id'] ?? '') == $refCustomer['id'] ? 'selected' : '' ?>><?= esc($refCustomer['name']) ?> - <?= esc($refCustomer['code']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group-premium">
                                            <label class="label-premium">Điểm giới thiệu</label>
                                            <input type="number" min="0" max="100" name="referral_score" class="form-control-premium" value="<?= esc($customer['referral_score'] ?? 0) ?>" <?= empty($canEdit) ? 'disabled' : '' ?>>
                                        </div>
                                        <div class="form-group-premium span-2">
                                            <label class="label-premium">Mối quan tâm / nhu cầu</label>
                                            <textarea name="interests" class="form-control-premium" rows="3" <?= empty($canEdit) ? 'disabled' : '' ?>><?= esc($customer['interests'] ?? '') ?></textarea>
                                        </div>
                                        <div class="form-group-premium span-2">
                                            <label class="label-premium">Vấn đề đã nhận diện</label>
                                            <textarea name="identified_issues" class="form-control-premium" rows="3" <?= empty($canEdit) ? 'disabled' : '' ?>><?= esc($customer['identified_issues'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    <?php if (!empty($canEdit)): ?>
                                        <div class="form-actions-row m-t-20">
                                            <button type="submit" class="btn-premium"><i class="fas fa-save"></i> Lưu hồ sơ quan hệ</button>
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div>
                                <h4 class="prof-section-h4"><i class="fas fa-lightbulb prof-section-icon"></i>Next action</h4>
                                <ul class="relationship-action-list">
                                    <?php foreach (($relationshipProfile['suggestions'] ?? []) as $suggestion): ?>
                                        <li><i class="fas fa-check-circle text-success"></i><span><?= esc($suggestion) ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="opportunities">
                        <div class="relationship-row m-b-20">
                            <h4 class="prof-section-h4"><i class="fas fa-chart-line prof-section-icon"></i>Cơ hội phát triển dịch vụ</h4>
                            <strong><?= number_format((float) ($opportunityStats['estimated_value'] ?? 0), 0, ',', '.') ?> VND</strong>
                        </div>
                        <?php if (empty($opportunities)): ?>
                            <p class="text-xs text-muted-dark">Chưa có cơ hội phát triển dịch vụ nào.</p>
                        <?php else: ?>
                            <div class="relationship-action-list">
                                <?php foreach ($opportunities as $opportunity): ?>
                                    <div class="opportunity-card">
                                        <div class="opportunity-row">
                                            <strong><?= esc($opportunity['issue_title']) ?></strong>
                                            <span class="relationship-status-badge"><?= esc($opportunity['probability']) ?>%</span>
                                        </div>
                                        <p class="text-sm m-t-10"><?= esc($opportunity['service_suggestion'] ?: $opportunity['issue_description']) ?></p>
                                        <div class="opportunity-meta">
                                            <span><i class="fas fa-money-bill-wave"></i> <?= number_format((float) $opportunity['estimated_value'], 0, ',', '.') ?> VND</span>
                                            <span><i class="fas fa-user"></i> <?= esc($opportunity['assigned_staff_name'] ?? 'Chưa phân công') ?></span>
                                            <span><i class="fas fa-calendar"></i> <?= !empty($opportunity['follow_up_date']) ? date('d/m/Y', strtotime($opportunity['follow_up_date'])) : 'Chưa hẹn' ?></span>
                                            <span><?= esc($opportunity['stage']) ?> / <?= esc($opportunity['status']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($canEdit)): ?>
                            <form class="opportunity-form" action="<?= base_url('customers/store-opportunity/' . $customer['id']) ?>" method="post">
                                <?= csrf_field() ?>
                                <h4 class="prof-section-h4">Thêm cơ hội mới</h4>
                                <div class="relationship-form-grid">
                                    <div class="form-group-premium">
                                        <label class="label-premium">Vấn đề / nhu cầu</label>
                                        <input type="text" name="issue_title" class="form-control-premium" required>
                                    </div>
                                    <div class="form-group-premium">
                                        <label class="label-premium">Dịch vụ đề xuất</label>
                                        <input type="text" name="service_suggestion" class="form-control-premium">
                                    </div>
                                    <div class="form-group-premium">
                                        <label class="label-premium">Giá trị dự kiến</label>
                                        <input type="number" min="0" name="estimated_value" class="form-control-premium">
                                    </div>
                                    <div class="form-group-premium">
                                        <label class="label-premium">Xác suất (%)</label>
                                        <input type="number" min="0" max="100" name="probability" class="form-control-premium" value="30">
                                    </div>
                                    <div class="form-group-premium">
                                        <label class="label-premium">Nhân sự theo dõi</label>
                                        <select name="assigned_staff_id" class="form-control-premium">
                                            <option value="">Theo nhân sự chăm sóc</option>
                                            <?php foreach ($employees as $employee): ?>
                                                <option value="<?= esc($employee['id']) ?>"><?= esc($employee['full_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group-premium">
                                        <label class="label-premium">Ngày theo dõi</label>
                                        <input type="date" name="follow_up_date" class="form-control-premium">
                                    </div>
                                    <div class="form-group-premium span-2">
                                        <label class="label-premium">Mô tả chi tiết</label>
                                        <textarea name="issue_description" class="form-control-premium" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="form-actions-row m-t-20">
                                    <button type="submit" class="btn-premium"><i class="fas fa-plus"></i> Ghi nhận cơ hội</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Tab: CSKH (Customer Care) -->
                    <div class="tab-pane" id="customer-care">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                            <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-hand-holding-heart" style="color: #ff2d55;"></i> Quy trình Chăm sóc khách hàng cũ
                            </h4>
                            <a href="<?= base_url('customer-care/care-plan/' . $customer['id']) ?>" class="btn-premium-sm" style="background: var(--regular-blue-gradient); border: none; border-radius: 20px; color: #fff; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; padding: 6px 15px; text-decoration: none;">
                                <i class="fas fa-cog"></i> <span>Quản lý CSKH</span>
                            </a>
                        </div>

                        <div class="prof-overview-grid" style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 25px; margin-bottom: 25px;">
                            <!-- CSKH Meta Info -->
                            <div>
                                <h4 class="prof-section-h4" style="margin-top: 0; margin-bottom: 15px; font-size: 13px; font-weight: 700; color: #86868b; text-transform: uppercase;"><i class="fas fa-info-circle" style="color: #0071e3; margin-right: 8px;"></i>Thông tin phân nhóm & Trạng thái</h4>
                                <div class="premium-card" style="padding: 20px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.04); background: #fafafa; margin-bottom: 20px;">
                                    <table class="prof-info-table" style="width: 100%; border-collapse: collapse;">
                                        <tr style="border-bottom: 1px solid #e2e2e7;">
                                            <td class="prof-table-label-td" style="padding: 10px 0; color: #86868b; font-weight: 500; font-size: 13px; border: none;">Phân nhóm khách hàng:</td>
                                            <td class="prof-table-val-td" style="padding: 10px 0; font-weight: 600; text-align: right; border: none;">
                                                <span class="badge-segment <?= esc($customer['customer_segment'] ?? 'regular') ?>">
                                                    <?= esc(($customer['customer_segment'] ?? 'regular') === 'vip' ? 'VIP (Nhóm A)' : (($customer['customer_segment'] ?? 'regular') === 'regular' ? 'Phổ thông (Nhóm B)' : 'Tiềm năng (Nhóm C)')) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr style="border-bottom: 1px solid #e2e2e7;">
                                            <td class="prof-table-label-td" style="padding: 10px 0; color: #86868b; font-weight: 500; font-size: 13px; border: none;">Trạng thái tư vấn hiện tại:</td>
                                            <td class="prof-table-val-td" style="padding: 10px 0; font-weight: 600; text-align: right; border: none;">
                                                <?php 
                                                $currentCareStatusKey = \App\Services\CustomerSlaService::normalizeStatusKey($customer['care_status'] ?? 'chua_tu_van');
                                                $currentStatusName = 'Chưa tư vấn';
                                                $currentStatusColor = '#8e8e93';
                                                foreach ($slaSettings as $s) {
                                                    if ($s['status_key'] === $currentCareStatusKey) {
                                                        $currentStatusName = $s['status_name'];
                                                        $currentStatusColor = $s['color'];
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <span class="badge-care-status" style="background-color: <?= esc($currentStatusColor) ?>15; color: <?= esc($currentStatusColor) ?>; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid <?= esc($currentStatusColor) ?>30;">
                                                    <?= esc($currentStatusName) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr style="border-bottom: 1px solid #e2e2e7;">
                                            <td class="prof-table-label-td" style="padding: 10px 0; color: #86868b; font-weight: 500; font-size: 13px; border: none;">Số Zalo:</td>
                                            <td class="prof-table-val-td-med" style="padding: 10px 0; font-weight: 600; text-align: right; color: #1d1d1f; border: none;"><?= esc($customer['zalo_phone'] ?: 'Trùng số chính') ?></td>
                                        </tr>
                                        <tr style="border-bottom: 1px solid #e2e2e7;">
                                            <td class="prof-table-label-td" style="padding: 10px 0; color: #86868b; font-weight: 500; font-size: 13px; border: none;">Nghề nghiệp:</td>
                                            <td class="prof-table-val-td-med" style="padding: 10px 0; font-weight: 600; text-align: right; color: #1d1d1f; border: none;"><?= esc($customer['occupation'] ?: '--') ?></td>
                                        </tr>

                                        <tr>
                                            <td class="prof-table-label-td" style="padding: 10px 0; color: #86868b; font-weight: 500; font-size: 13px; border: none;">Hoàn thành dịch vụ gần nhất:</td>
                                            <td class="prof-table-val-td-med" style="padding: 10px 0; font-weight: 600; text-align: right; color: #1d1d1f; border: none;"><?= $customer['service_completed_date'] ? date('d/m/Y', strtotime($customer['service_completed_date'])) : '--' ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Loyalty/VIP Card visual representation -->
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding-top: 5px;">
                                <h4 class="prof-section-h4" style="margin-top: 0; margin-bottom: 15px; font-size: 13px; font-weight: 700; color: #86868b; text-transform: uppercase; align-self: flex-start;"><i class="fas fa-crown" style="color: #bf953f; margin-right: 8px;"></i>Thẻ Thành Viên VIP</h4>
                                
                                <?php if ($loyalty): ?>
                                    <div class="vip-card-wrapper" style="width: 100%; max-width: 290px; height: 180px; margin-bottom: 15px;">
                                        <div class="vip-card-visual card-<?= esc($loyalty['loyalty_tier'] ?? 'standard') ?>" style="border-radius: 12px; padding: 16px;">
                                            <div class="vip-card-header">
                                                <div class="vip-card-logo">L.A.N ERP</div>
                                                <div class="vip-card-tier" style="font-size: 0.65rem; padding: 2px 8px;"><?= esc($loyalty['loyalty_tier'] ?? 'standard') ?></div>
                                            </div>
                                            <div class="vip-card-chip" style="width: 32px; height: 24px; margin-top: 5px;"></div>
                                            <div class="vip-card-body">
                                                <div class="vip-card-number" style="font-size: 1rem; letter-spacing: 2px; margin-bottom: 6px;">
                                                    REF-<?= esc($loyalty['referral_code'] ?? 'DUYNEST') ?>
                                                </div>
                                            </div>
                                            <div class="vip-card-footer">
                                                <div class="vip-card-holder" style="font-size: 0.6rem; color: inherit;">
                                                    CHỦ THẺ
                                                    <span style="font-size: 0.75rem; font-weight: 700; margin-top: 1px; display: block;"><?= esc($customer['name']) ?></span>
                                                </div>
                                                <div class="vip-card-points" style="font-size: 0.6rem; color: inherit; text-align: right;">
                                                    TÍCH ĐIỂM
                                                    <span style="font-size: 0.95rem; font-weight: 700; margin-top: 1px; display: block;"><?= number_format($loyalty['points'] ?? 0) ?> đ</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="<?= base_url('customer-care/loyalty/' . $customer['id']) ?>" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" style="border-radius: 20px; font-weight: 600; padding: 6px 16px; font-size: 12px; text-decoration: none;">
                                        <i class="fas fa-gift"></i> Xem quyền lợi VIP
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- SLA & Status tracking dashboard (New Feature) -->
                        <div class="sla-dashboard-card" style="background: #ffffff; border: 1px solid rgba(0,0,0,0.06); border-radius: 16px; padding: 20px; margin-bottom: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.015);">
                            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f2f2f7; padding-bottom: 15px; margin-bottom: 15px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(0, 113, 227, 0.1); display: flex; align-items: center; justify-content: center; color: #0071e3;">
                                        <i class="fas fa-history" style="font-size: 16px;"></i>
                                    </div>
                                    <div>
                                        <h5 style="margin: 0; font-size: 14px; font-weight: 700; color: #1d1d1f;">Tiến độ & Trạng thái Chăm sóc</h5>
                                        <p style="margin: 2px 0 0 0; font-size: 11px; color: #86868b;">Quản lý hạn xử lý cho từng giai đoạn chăm sóc</p>
                                    </div>
                                </div>
                                
                                <!-- Chuyển trạng thái nhanh qua AJAX -->
                                <?php
                                $isAdminOrManager = false;
                                if (has_permission('sys.admin') || has_permission('customer.manage') || has_permission('customer.edit_all')) {
                                    $isAdminOrManager = true;
                                } else {
                                    $roleName = session()->get('role_name');
                                    if ($roleName === \Config\AppConstants::ROLE_TRUONG_PHONG) {
                                        $isAdminOrManager = true;
                                    }
                                }
                                $isCaretaker = (!empty($customer['assigned_care_staff_id']) && $customer['assigned_care_staff_id'] == session()->get('employee_id'));
                                $canChangeStatus = $isAdminOrManager || $isCaretaker;
                                ?>
                                <?php if ($canChangeStatus) { ?>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 12px; font-weight: 600; color: #86868b;">Chuyển nhanh:</span>
                                    <select id="quick-transition-status" class="form-control-premium" style="width: 200px; padding: 6px 12px; font-size: 12px; font-weight: 600; border-radius: 20px; height: auto;" onchange="transitionCustomerStatus(<?= $customer['id'] ?>, this.value)">
                                        <?php foreach ($slaSettings as $s): ?>
                                            <option value="<?= esc($s['status_key']) ?>" <?= $currentCareStatusKey === $s['status_key'] ? 'selected' : '' ?>>
                                                <?= esc($s['status_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php } ?>
                            </div>

                            <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 20px;">
                                <!-- Trạng thái hiện tại & Bộ đếm SLA -->
                                <div style="background: #fafafa; border-radius: 12px; padding: 15px; border: 1px solid rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: center; min-height: 100px;">
                                    <div style="font-size: 11px; font-weight: 700; color: #86868b; text-transform: uppercase; margin-bottom: 8px;">Thời gian xử lý & Hạn định chăm sóc</div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 15px;">
                                        <div>
                                            <div style="font-size: 16px; font-weight: 800; color: <?= esc($currentStatusColor) ?>; display: flex; align-items: center; gap: 6px;">
                                                <span class="status-indicator-dot" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: <?= esc($currentStatusColor) ?>;"></span>
                                                <?= esc($currentStatusName) ?>
                                            </div>
                                            <div style="font-size: 11px; color: #86868b; margin-top: 4px;">
                                                <?php if ($activeSla && $activeSla['due_time']): ?>
                                                    Hạn định: <b><?= date('d/m/Y H:i', strtotime($activeSla['due_time'])) ?></b> (<?= esc($activeSla['sla_duration']) ?> giờ)
                                                <?php else: ?>
                                                    Không giới hạn thời gian (Không áp dụng hạn chăm sóc)
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Countdown Timer -->
                                        <?php if ($activeSla && $activeSla['due_time']): ?>
                                            <?php 
                                            $dueTs = strtotime($activeSla['due_time']);
                                            $nowTs = time();
                                            $diffSeconds = $dueTs - $nowTs;
                                            $isOverdue = ($diffSeconds < 0 || $activeSla['sla_status'] === 'overdue');
                                            ?>
                                            <?php if ($isOverdue): ?>
                                                <?php 
                                                $absDiff = abs($diffSeconds);
                                                $overdueStr = format_seconds_to_duration($absDiff);
                                                ?>
                                                <div class="sla-overdue-alert-badge" style="text-align: right;">
                                                    <div style="color: #ff3b30; font-size: 11px; font-weight: 800; text-transform: uppercase; display: flex; align-items: center; gap: 4px; justify-content: flex-end;">
                                                        <i class="fas fa-exclamation-triangle"></i> TRỄ HẠN
                                                    </div>
                                                    <div style="font-size: 14px; font-weight: 800; color: #ff3b30;">Quá <?= esc($overdueStr) ?></div>
                                                </div>
                                            <?php else: ?>
                                                <?php 
                                                $remStr = format_seconds_to_duration($diffSeconds);
                                                ?>
                                                <div style="text-align: right;">
                                                    <div style="color: #34c759; font-size: 11px; font-weight: 800; text-transform: uppercase; display: flex; align-items: center; gap: 4px; justify-content: flex-end;">
                                                        <i class="fas fa-hourglass-half"></i> ĐANG TRONG HẠN
                                                    </div>
                                                    <div style="font-size: 14px; font-weight: 800; color: #0071e3;">Còn lại <?= esc($remStr) ?></div>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div style="text-align: right; color: #86868b; font-size: 11px; font-weight: 600;">
                                                <i class="fas fa-infinity"></i> Vô thời hạn
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Nhân sự phụ trách & Hiệu suất -->
                                <div style="background: #fafafa; border-radius: 12px; padding: 15px; border: 1px solid rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: center; min-height: 100px;">
                                    <div style="font-size: 11px; font-weight: 700; color: #86868b; text-transform: uppercase; margin-bottom: 8px;">Nhân sự & Kết quả chăm sóc</div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                        <div>
                                            <div style="font-size: 13px; font-weight: 700; color: #1d1d1f; display: flex; align-items: center; gap: 6px;">
                                                <i class="fas fa-user-tie" style="color: #86868b;"></i>
                                                <?= esc($careStaffName ?: 'Chưa phân công') ?>
                                            </div>
                                            <div style="font-size: 11px; color: #86868b; margin-top: 4px;">
                                                <?php if ($activeSla && $activeSla['start_time']): ?>
                                                    Bắt đầu bước: <?= date('d/m/Y H:i', strtotime($activeSla['start_time'])) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div style="text-align: right;">
                                            <?php 
                                            $achCount = 0;
                                            $lateCount = 0;
                                            foreach ($slaHistory as $hist) {
                                                if ($hist['sla_status'] === 'achieved') $achCount++;
                                                if (in_array($hist['sla_status'], ['overdue', 'completed_late'])) $lateCount++;
                                            }
                                            ?>
                                            <div style="font-size: 10px; font-weight: 700; color: #86868b; text-transform: uppercase; margin-bottom: 4px;">Lịch sử ca tư vấn</div>
                                            <span style="font-size: 10px; font-weight: 700; background: rgba(52, 199, 89, 0.12); color: #34c759; padding: 2px 6px; border-radius: 10px; margin-right: 4px;">Đạt: <?= esc($achCount) ?></span>
                                            <span style="font-size: 10px; font-weight: 700; background: rgba(255, 59, 48, 0.12); color: #ff3b30; padding: 2px 6px; border-radius: 10px;">Trễ: <?= esc($lateCount) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Timeline lịch sử tiến độ SLA động -->
                            <div style="margin-top: 20px; border-top: 1px solid #f2f2f7; padding-top: 15px;">
                                <div style="font-size: 12px; font-weight: 700; color: #1d1d1f; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-route" style="color: #ff9500;"></i> Nhật ký Tiến trình Chăm sóc (Lịch sử)
                                </div>
                                
                                <?php if (empty($slaHistory)): ?>
                                    <div style="text-align: center; padding: 20px; color: #86868b; font-size: 12px;">
                                        Không có nhật ký tiến trình chăm sóc.
                                    </div>
                                <?php else: ?>
                                    <div class="sla-timeline-container" style="position: relative; padding-left: 20px; margin-left: 10px; display: flex; flex-direction: column; gap: 15px;">
                                        <?php foreach ($slaHistory as $hist): ?>
                                            <?php 
                                            // Tra cứu setting tương ứng
                                            $histSetting = null;
                                            foreach ($slaSettings as $s) {
                                                if ($s['status_key'] === $hist['status']) {
                                                    $histSetting = $s;
                                                    break;
                                                }
                                            }
                                            $color = $histSetting ? $histSetting['color'] : '#8e8e93';
                                            $name = $histSetting ? $histSetting['status_name'] : $hist['status'];
                                            
                                            // Lấy tên người phụ trách tại thời điểm đó
                                            $staffName = 'Hệ thống';
                                            if (!empty($hist['assigned_staff_id'])) {
                                                $db = \Config\Database::connect();
                                                $st = $db->table('employees')->where('id', $hist['assigned_staff_id'])->select('full_name')->get()->getRow();
                                                if ($st) $staffName = $st->full_name;
                                            }
                                            ?>
                                            <div class="sla-timeline-item" style="position: relative;">
                                                <!-- Timeline Dot -->
                                                <div style="position: absolute; left: -26px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: <?= esc($color) ?>; border: 2px solid #fff; box-shadow: 0 0 0 2px <?= esc($color) ?>30;"></div>
                                                
                                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
                                                    <div>
                                                        <span style="font-size: 13px; font-weight: 700; color: #1d1d1f;"><?= esc($name) ?></span>
                                                        <span style="font-size: 11px; color: #86868b; margin-left: 6px;">nhân viên: <b><?= esc($staffName) ?></b></span>
                                                        
                                                        <div style="font-size: 11px; color: #86868b; margin-top: 2px;">
                                                            Bắt đầu: <?= date('d/m/Y H:i', strtotime($hist['start_time'])) ?>
                                                            <?php if ($hist['end_time']): ?>
                                                                | Kết thúc: <?= date('d/m/Y H:i', strtotime($hist['end_time'])) ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div>
                                                        <?php if ($hist['sla_status'] === 'achieved'): ?>
                                                            <span style="font-size: 10px; font-weight: 700; background: #e8f5e9; color: #2e7d32; padding: 2px 8px; border-radius: 12px; border: 1px solid #c8e6c9;">
                                                                <i class="fas fa-check-circle"></i> Đạt (Đúng hạn)
                                                            </span>
                                                        <?php elseif ($hist['sla_status'] === 'completed_late'): ?>
                                                            <span style="font-size: 10px; font-weight: 700; background: #fff3e0; color: #e65100; padding: 2px 8px; border-radius: 12px; border: 1px solid #ffe0b2;">
                                                                <i class="fas fa-history"></i> Xong trễ
                                                            </span>
                                                        <?php elseif ($hist['sla_status'] === 'overdue'): ?>
                                                            <span style="font-size: 10px; font-weight: 700; background: #ffebee; color: #c62828; padding: 2px 8px; border-radius: 12px; border: 1px solid #ffcdd2; animation: pulse 1.8s infinite alternate;">
                                                                <i class="fas fa-exclamation-triangle"></i> Bỏ lỡ (Quá hạn)
                                                            </span>
                                                        <?php else: ?>
                                                            <span style="font-size: 10px; font-weight: 700; background: #e3f2fd; color: #1565c0; padding: 2px 8px; border-radius: 12px; border: 1px solid #bbdefb;">
                                                                <i class="fas fa-spinner fa-spin"></i> Đang chăm sóc
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- CSKH Plan Timeline -->
                        <div style="border-top: 1px solid #f1f5f9; padding-top: 20px; margin-top: 10px;">
                            <h4 class="prof-section-h4" style="margin-bottom: 15px; font-size: 13px; font-weight: 700; color: #86868b; text-transform: uppercase;"><i class="fas fa-stream" style="color: #34c759; margin-right: 8px;"></i>Giai đoạn chăm sóc</h4>
                            <?php if (empty($carePlans)): ?>
                                <div class="text-center py-4 bg-white rounded-lg shadow-sm border" style="border-radius: 12px; border: 1px solid rgba(0,0,0,0.08);">
                                    <i class="fas fa-calendar-alt fa-2x text-muted mb-2" style="opacity: 0.3;"></i>
                                    <p class="text-muted font-size-0.85 mb-3">Chưa thiết lập kế hoạch CSKH cho khách hàng này.</p>
                                    <a href="<?= base_url('customer-care/care-plan/' . $customer['id']) ?>" class="btn btn-sm btn-primary" style="border-radius: 20px; font-weight: 600; padding: 6px 20px; text-decoration: none;">Thiết lập Giai đoạn 1</a>
                                </div>
                            <?php else: ?>
                                <div class="care-timeline" style="padding-left: 20px; margin-top: 10px;">
                                    <?php foreach ($carePlans as $plan): ?>
                                        <div class="timeline-item <?= $plan['status'] === 'in_progress' ? 'active' : ($plan['status'] === 'completed' ? 'completed' : '') ?>" style="margin-bottom: 20px;">
                                            <div class="timeline-dot" style="left: -20px; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas <?= $plan['status'] === 'completed' ? 'fa-check' : ($plan['status'] === 'skipped' ? 'fa-ban' : 'fa-clock') ?>" style="font-size: 0.55rem; color: #fff;"></i>
                                            </div>
                                            <div class="timeline-content" style="padding: 12px 16px; border-radius: 10px;">
                                                <div class="timeline-title" style="font-size: 0.88rem; font-weight: 700; display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span><?= esc($plan['title']) ?></span>
                                                    <span style="font-size: 0.72rem; padding: 2px 6px; border-radius: 10px; background: rgba(0,0,0,0.04); font-weight: 600;">
                                                        <?= esc($plan['status'] === 'completed' ? 'Hoàn thành' : ($plan['status'] === 'in_progress' ? 'Đang chạy' : 'Đóng')) ?>
                                                    </span>
                                                </div>
                                                <p class="timeline-desc text-muted font-size-0.78 mt-1 mb-2" style="margin: 0;"><?= esc($plan['description']) ?></p>
                                                
                                                <!-- Tasks of this plan -->
                                                <?php 
                                                    $tasks = array_filter($careTasks, function($t) use ($plan) {
                                                        return $t['care_plan_id'] == $plan['id'];
                                                    });
                                                ?>
                                                <?php if (!empty($tasks)): ?>
                                                    <div style="border-top: 1px solid rgba(0,0,0,0.04); padding-top: 8px; margin-top: 8px;">
                                                        <div style="font-size: 11px; font-weight: 700; color: #86868b; text-transform: uppercase; margin-bottom: 6px;">Checklist chi tiết:</div>
                                                        <?php foreach ($tasks as $t): ?>
                                                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; margin-bottom: 4px; padding: 3px 0;">
                                                                <span class="d-flex align-items-center gap-2">
                                                                    <i class="fas <?= $t['is_completed'] ? 'fa-check-circle text-success' : 'fa-circle text-muted' ?>" style="font-size: 12px;"></i>
                                                                    <span class="<?= $t['is_completed'] ? 'text-muted text-decoration-line-through' : 'font-weight-600 text-dark' ?>"><?= esc($t['title']) ?></span>
                                                                </span>
                                                                <span class="task-channel <?= esc($t['channel']) ?>" style="font-size: 9px; padding: 1px 4px; border-radius: 4px; font-weight: 600; text-transform: uppercase;"><?= esc($t['channel']) ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Interaction -->
<div id="modalInteraction" class="modal-overlay-cust">
    <div class="premium-card modal-content-500">
        <h3 style="margin-top: 0;">Ghi chú tương tác</h3>
        <form action="<?= base_url('customers/add-interaction/' . $customer['id']) ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group-premium">
                <label class="label-premium">Kênh liên lạc</label>
                <select name="channel" class="form-control-premium">
                    <option value="call">Điện thoại</option>
                    <option value="zalo">Zalo</option>
                    <option value="email">Email</option>
                    <option value="meeting">Gặp mặt trực tiếp</option>
                    <option value="facebook">Facebook</option>
                </select>
            </div>
            <div class="relationship-form-grid">
                <div class="form-group-premium">
                    <label class="label-premium">Kết quả</label>
                    <select name="interaction_result" class="form-control-premium">
                        <option value="positive">Tích cực</option>
                        <option value="neutral" selected>Trung tính</option>
                        <option value="negative">Tiêu cực</option>
                        <option value="no_response">Chưa phản hồi</option>
                    </select>
                </div>
                <div class="form-group-premium">
                    <label class="label-premium">Mức quan trọng</label>
                    <select name="importance_level" class="form-control-premium">
                        <option value="low">Thấp</option>
                        <option value="normal" selected>Bình thường</option>
                        <option value="high">Cao</option>
                        <option value="urgent">Khẩn cấp</option>
                    </select>
                </div>
            </div>
            <div class="form-group-premium">
                <label class="label-premium">Tóm lược (Summary)</label>
                <input type="text" name="summary" class="form-control-premium" required placeholder="Ví dụ: Gọi điện báo phí">
            </div>
            <div class="form-group-premium">
                <label class="label-premium">Chi tiết cuộc trao đổi</label>
                <div class="relationship-form-grid">
                    <div class="form-group-premium">
                        <label class="label-premium">Cần theo dõi lại</label>
                        <label class="gift-checkbox-option">
                            <input type="checkbox" name="requires_follow_up" value="1">
                            <span>Có hẹn xử lý tiếp</span>
                        </label>
                    </div>
                    <div class="form-group-premium">
                        <label class="label-premium">Ngày hẹn theo dõi</label>
                        <input type="datetime-local" name="next_follow_up" class="form-control-premium">
                    </div>
                </div>
                <div id="editor-container"></div>
                <input type="hidden" name="detailed_content" id="detailed_content_input">
            </div>
            <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-secondary" onclick="window.document.getElementById('modalInteraction').style.display='none'">Hủy</button>
                <button type="submit" class="btn-premium" id="btnSaveInteraction">Lưu tương tác</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Upload -->
<div id="modalUpload" class="modal-overlay-cust" style="display:none;">
    <div class="premium-card modal-content-500">
        <h3 style="margin-top: 0;">Số hóa tài liệu khách hàng</h3>
        <p class="text-xs text-muted-dark m-b-20">Tài liệu sẽ được lưu trữ tập trung tại kho DMS của công ty.</p>
        <form id="formCustomerUploadDocument" action="<?= base_url('customers/upload-doc/' . $customer['id']) ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="label-premium">Chọn tệp tin</label>
                <div class="dms-upload-zone customer-upload-zone">
                    <input type="file" name="document[]" id="customerDmsFileInput" multiple required>
                    <label for="customerDmsFileInput">
                        <i class="fas fa-file-export"></i>
                        <span>Click để chọn một hoặc nhiều tệp</span>
                        <small>Hỗ trợ PDF, DOCX, JPG, PNG (Max 20MB)</small>
                    </label>
                </div>
                <div id="customerDmsSelectedFiles" class="dms-selected-files"></div>
            </div>
            <div class="form-group-premium m-b-15">
                <label class="label-premium">Tên tài liệu / Ghi chú</label>
                <input type="text" name="file_name" class="form-control-premium" placeholder="Ví dụ: CCCD bản quét, Giấy ủy quyền...">
                <small class="text-muted">Để trống để hệ thống tự lấy tên tệp. Khi chọn nhiều tệp, tiêu đề này sẽ là tên tài liệu chung.</small>
            </div>
            <div class="form-group-premium m-b-15">
                <label class="label-premium">Mô tả thêm</label>
                <textarea name="description" class="form-control-premium" rows="2"></textarea>
            </div>
            <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('modalUpload').style.display='none'">Hủy</button>
                <button type="submit" class="btn-premium">Tải lên ngay</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Nhập từ kho DMS -->
<div id="vaultModal" class="modal-overlay-cust" style="display:none;">
    <div class="premium-card" style="width:650px; max-height: 80vh; display: flex; flex-direction: column;">
        <div class="modal-header-premium" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="section-title-premium" style="margin:0;">Kho tài liệu hệ thống (Vault)</h3>
            <button type="button" class="btn-close-minimal" onclick="document.getElementById('vaultModal').style.display='none'"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="m-b-15">
            <input type="text" id="vaultSearch" placeholder="Tìm kiếm tài liệu trong kho..." class="form-control-premium" onkeyup="filterVault()">
        </div>

        <div id="vaultListContainer" style="flex:1; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px;">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Tên tài liệu</th>
                        <th>Phân loại</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody id="vaultTableBody">
                    <tr><td colspan="4" class="text-center p-20">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="form-actions-row m-t-20" style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" class="btn-secondary" onclick="document.getElementById('vaultModal').style.display='none'">Đóng</button>
            <button type="button" id="btnConfirmImport" class="btn-premium" disabled onclick="confirmImport(<?= $customer['id'] ?>)">Thêm tài liệu</button>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script src="<?= base_url('js/customer_show.js') ?>"></script>
<?= $this->endSection() ?>
