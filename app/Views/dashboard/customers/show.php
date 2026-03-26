<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customers.css') ?>">
<?= $this->endSection() ?>

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
                        <button class="tab-btn" data-tab="cases">Vụ việc (<?= !empty($cases) && is_array($cases) ? count($cases) : 0 ?>)</button>
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
                                    <i class="fas fa-archive"></i> Kho tài liệu (DMS)
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
                <button type="button" class="btn-secondary" onclick="window.document.getElementById('modalInteraction').style.display='none'">Hủy</button>
                <button type="submit" class="btn-premium">Lưu tương tác</button>
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
            <button type="button" id="btnConfirmImport" class="btn-premium" disabled onclick="confirmImport()">Thêm tài liệu</button>
        </div>
    </div>
</div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * L.A.N ERP - Hồ sơ khách hàng 360 độ
 * Quản lý chuyển đổi Tab nội dung: Tổng quan, Vụ việc, Tương tác, Tài chính, Tài liệu.
 */
document.addEventListener('DOMContentLoaded', function() {
    // 1. Lấy danh sách các nút Tab và các khối nội dung (Pane) tương ứng
    const tabs = document.querySelectorAll('#customerModuleTabs .tab-btn');
    const panes = document.querySelectorAll('.tab-pane');

    // 2. Lắng nghe sự kiện Click trên từng Tab
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab; // Lấy ID của pane cần hiển thị (ví dụ: 'overview')

            // Gỡ bỏ trạng thái 'active' của tất cả các tab và pane hiện tại
            tabs.forEach(t => t.classList.remove('active'));
            panes.forEach(p => p.classList.remove('active'));

            // Kích hoạt tab được nhấn và hiển thị pane nội dung tương ứng
            tab.classList.add('active');
            const targetPane = document.getElementById(target);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });
});

let selectedVaultDocId = null;

function openVaultModal() {
    const modal = document.getElementById('vaultModal');
    modal.style.display = 'flex';
    selectedVaultDocId = null;
    document.getElementById('btnConfirmImport').disabled = true;

    fetch('<?= base_url("documents/vault-list") ?>?category=internal')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('vaultTableBody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center p-20">Kho tài liệu hiện tại đang trống.</td></tr>';
                return;
            }

            data.forEach(doc => {
                const tr = document.createElement('tr');
                tr.style.cursor = 'pointer';
                tr.onclick = () => selectVaultDoc(doc.id, tr);
                tr.innerHTML = `
                    <td><input type="radio" name="vault_doc" value="${doc.id}"></td>
                    <td><strong>${doc.file_name}</strong></td>
                    <td><span class="badge-secondary-minimal text-xs">${doc.document_category}</span></td>
                    <td class="text-sm">${new Date(doc.created_at).toLocaleDateString('vi-VN')}</td>
                `;
                tbody.appendChild(tr);
            });
        });
}

function selectVaultDoc(id, row) {
    selectedVaultDocId = id;
    document.querySelectorAll('#vaultTableBody tr').forEach(r => r.style.background = 'white');
    row.style.background = 'rgba(0, 113, 227, 0.05)';
    row.querySelector('input[type="radio"]').checked = true;
    document.getElementById('btnConfirmImport').disabled = false;
}

function confirmImport() {
    if (!selectedVaultDocId) return;

    const formData = new FormData();
    formData.append('document_id', selectedVaultDocId);
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch('<?= base_url("customers/import-doc/" . $customer['id']) ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            alert('Đã thêm tài liệu thành công.');
            location.reload();
        } else {
            alert('Có lỗi xảy ra: ' + result.message);
        }
    });
}

function filterVault() {
    let input = document.getElementById('vaultSearch');
    let filter = input.value.toUpperCase();
    let tr = document.querySelectorAll('#vaultTableBody tr');
    tr.forEach(row => {
        let text = row.textContent || row.innerText;
        row.style.display = text.toUpperCase().indexOf(filter) > -1 ? "" : "none";
    });
}
</script>
<?= $this->endSection() ?>
