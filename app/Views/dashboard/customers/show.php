<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="customer-profile-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Hồ sơ khách hàng: <?= esc($customer['name']) ?></h2>
            <p class="content-subtitle"><?= esc($customer['code']) ?> • <?= ($customer['type'] == 'ca_nhan') ? 'Cá nhân' : 'Doanh nghiệp' ?></p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('customers/edit/' . $customer['id']) ?>" class="btn-secondary">
                <i class="fas fa-edit"></i> Chỉnh sửa
            </a>
            <a href="<?= base_url('cases/create?customer_id=' . $customer['id']) ?>" class="btn-premium">
                <i class="fas fa-folder-plus"></i> Tạo vụ việc mới
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
                    <div class="prof-info-row">
                        <span class="prof-label-dim">SĐT:</span>
                        <span class="prof-val-bold"><?= esc($customer['phone']) ?></span>
                    </div>
                    <div class="prof-info-row">
                        <span class="prof-label-dim">Email:</span>
                        <span class="prof-val-bold"><?= esc($customer['email'] ?: '--') ?></span>
                    </div>
                    <div class="prof-info-row-last">
                        <span class="prof-label-dim">Địa chỉ:</span>
                        <span class="prof-val-align-right"><?= esc($customer['address'] ?: '--') ?></span>
                    </div>
                </div>
            </div>

            <div class="premium-card prof-health-section">
                <h4 class="prof-health-title">Chỉ số sức khỏe</h4>
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
                        <button class="tab-btn active" data-tab="overview">Tổng quan</button>
                        <button class="tab-btn" data-tab="cases">Vụ việc (<?= count($cases) ?>)</button>
                        <button class="tab-btn" data-tab="interactions">Tương tác</button>
                        <button class="tab-btn" data-tab="finance">Tài chính</button>
                        <button class="tab-btn" data-tab="docs">Tài liệu</button>
                    </div>
                </div>

                <div class="prof-tabs-content">
                    <!-- Tab: Overview -->
                    <div class="tab-pane active" id="overview">
                        <div class="prof-overview-grid">
                            <div>
                                <h4 class="prof-section-h4"><i class="fas fa-id-card prof-section-icon"></i>Thông tin định danh</h4>
                                <table class="prof-info-table">
                                    <tr>
                                        <td class="prof-table-label-td">Loại định danh:</td>
                                        <td class="prof-table-val-td"><?= strtoupper(esc($customer['identity_type'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="prof-table-label-td">Số định danh:</td>
                                        <td class="prof-table-val-td"><?= esc($customer['identity_number'] ?: '--') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="prof-table-label-td">Ngày cấp:</td>
                                        <td class="prof-table-val-td-med"><?= $customer['issue_date'] ? date('d/m/Y', strtotime($customer['issue_date'])) : '--' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="prof-table-label-td">Nơi cấp:</td>
                                        <td class="prof-table-val-td-med"><?= esc($customer['issued_by'] ?: '--') ?></td>
                                    </tr>
                                </table>
                            </div>
                            <?php if ($customer['type'] == 'doanh_nghiep') { ?>
                            <div>
                                <h4 class="prof-section-h4"><i class="fas fa-building prof-section-icon"></i>Thông tin doanh nghiệp</h4>
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
                            <h4 class="prof-section-h4"><i class="fas fa-tags prof-section-icon"></i>Tags & Phân loại</h4>
                            <div class="prof-tags-flex">
                                <?php
                                    $tags = explode(',', $customer['tags'] ?: '');
                                    foreach ($tags as $tag) {
                                        if (trim($tag)) {
                                ?>
                                    <span class="badge-log badge-secondary-minimal"><?= esc(trim($tag)) ?></span>
                                <?php
                                        }
                                    }
                                ?>
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
                                <?php foreach ($cases as $case) { ?>
                                <tr>
                                    <td><span class="badge-secondary-minimal"><?= esc($case['code']) ?></span></td>
                                    <td class="prof-val-bold"><?= esc($case['title']) ?></td>
                                    <td><?= esc($case['status']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($case['created_at'])) ?></td>
                                </tr>
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
                            <?php if (empty($interactions)) { ?>
                                <p style="text-align: center; opacity: 0.5; padding: 40px;">Chưa có lịch sử tương tác nào.</p>
                            <?php } else { ?>
                                <?php foreach ($interactions as $int) { ?>
                                    <div class="prof-timeline-item">
                                        <div class="prof-timeline-dot"></div>
                                        <div class="prof-timeline-meta"><?= date('d/m/Y H:i', strtotime($int['interaction_date'])) ?> • <?= esc($int['staff_email']) ?></div>
                                        <div class="prof-timeline-summary"><?= esc($int['summary']) ?></div>
                                        <div class="prof-timeline-content"><?= esc($int['detailed_content']) ?></div>
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
                                <?php if (empty($payments)) { ?>
                                    <tr><td colspan="4" style="text-align: center; opacity: 0.5; padding: 20px;">Chưa có lịch sử thanh toán.</td></tr>
                                <?php } else { ?>
                                    <?php foreach ($payments as $pay) { ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($pay['payment_date'])) ?></td>
                                        <td class="prof-val-bold"><?= number_format($pay['amount'], 0, ',', '.') ?></td>
                                        <td><?= esc($pay['method']) ?></td>
                                        <td><?= esc($pay['description']) ?></td>
                                    </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab: Documents -->
                    <div class="tab-pane" id="docs">
                        <div class="vault-header">
                            <h4 style="margin: 0;">Hồ sơ số hóa (Vault)</h4>
                            <button class="btn-premium-sm" onclick="document.getElementById('modalUpload').style.display='flex'">
                                <i class="fas fa-upload"></i> Tải lên tài liệu
                            </button>
                        </div>
                        <div class="vault-grid">
                            <?php foreach ($documents as $doc) { ?>
                                <div class="premium-card vault-card">
                                    <div class="vault-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="vault-doc-type"><?= esc($doc['document_type']) ?></div>
                                    <div class="vault-file-name"><?= esc($doc['file_name']) ?></div>
                                    <div class="vault-actions">
                                        <a href="<?= base_url($doc['file_path']) ?>" class="btn-secondary-sm" target="_blank">Xem</a>
                                    </div>
                                </div>
                            <?php } ?>
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
                <label class="label-premium">Chi tiết</label>
                <textarea name="detailed_content" class="form-control-premium" rows="3"></textarea>
            </div>
            <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('modalInteraction').style.display='none'">Hủy</button>
                <button type="submit" class="btn-premium">Lưu tương tác</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Upload -->
<div id="modalUpload" class="modal-overlay-cust">
    <div class="premium-card modal-content-500">
        <h3 style="margin-top: 0;">Tải lên tài liệu</h3>
        <form action="<?= base_url('customers/upload-document/' . $customer['id']) ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-group-premium">
                <label class="label-premium">Loại tài liệu</label>
                <input type="text" name="document_type" class="form-control-premium" required placeholder="Ví dụ: CCCD, Hợp đồng, GPKD...">
            </div>
            <div class="form-group-premium">
                <label class="label-premium">Chọn file</label>
                <input type="file" name="document" class="form-control-premium" required>
            </div>
            <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('modalUpload').style.display='none'">Hủy</button>
                <button type="submit" class="btn-premium">Tải lên</button>
            </div>
        </form>
    </div>
</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('#customerModuleTabs .tab-btn');
    const panes = document.querySelectorAll('.tab-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;

            tabs.forEach(t => t.classList.remove('active'));
            panes.forEach(p => p.classList.remove('active'));

            tab.classList.add('active');
            document.getElementById(target).classList.add('active');
        });
    });
});
</script>
<?= $this->endSection() ?>
