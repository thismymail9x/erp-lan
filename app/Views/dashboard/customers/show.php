<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customers.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/customer_care.css') ?>">
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
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
                        <button class="tab-btn" data-tab="docs"><i class="fas fa-file-alt"></i> Hồ sơ</button>
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
                                    <div class="premium-card vault-card">
                                        <div class="vault-icon">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <div class="vault-doc-type" style="font-size: 11px; text-transform: uppercase; color: var(--apple-blue); font-weight: 600;">
                                            <?= esc($doc['document_category'] ?? 'Khác') ?>
                                        </div>
                                        <div class="vault-file-name" style="font-weight: 500; margin: 5px 0;"><?= esc($doc['file_name'] ?? 'Tài liệu') ?></div>
                                        <div class="vault-actions">
                                            <a href="<?= base_url('documents/view/' . ($doc['id'] ?? 0)) ?>" class="btn-secondary-sm" target="_blank">Xem / Tải về</a>
                                        </div>
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
                                                $currentStatusName = 'Chưa tư vấn';
                                                $currentStatusColor = '#8e8e93';
                                                foreach ($slaSettings as $s) {
                                                    if ($s['status_key'] === ($customer['care_status'] ?? 'chua_tu_van')) {
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
                                            <option value="<?= esc($s['status_key']) ?>" <?= ($customer['care_status'] ?? 'chua_tu_van') === $s['status_key'] ? 'selected' : '' ?>>
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
            <div class="form-group-premium">
                <label class="label-premium">Tóm lược (Summary)</label>
                <input type="text" name="summary" class="form-control-premium" required placeholder="Ví dụ: Gọi điện báo phí">
            </div>
            <div class="form-group-premium">
                <label class="label-premium">Chi tiết cuộc trao đổi</label>
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
        <form action="<?= base_url('customers/upload-doc/' . $customer['id']) ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-group-premium m-b-15">
                <label class="label-premium">Chọn tệp tin</label>
                <input type="file" name="document" class="form-control-premium" required>
            </div>
            <div class="form-group-premium m-b-15">
                <label class="label-premium">Tên tài liệu / Ghi chú</label>
                <input type="text" name="file_name" class="form-control-premium" required placeholder="Ví dụ: CCCD bản quét, Giấy ủy quyền...">
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
