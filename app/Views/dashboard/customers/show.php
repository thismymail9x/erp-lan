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

    <div class="profile-grid" style="display: grid; grid-template-columns: 350px 1fr; gap: 25px;">
        <!-- Sidebar: Quick Info & Stats -->
        <div class="profile-sidebar">
            <div class="premium-card" style="padding: 20px; margin-bottom: 25px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: #f5f5f7; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 32px; color: #0071e3;">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 style="margin: 0; font-size: 18px;"><?= esc($customer['name']) ?></h3>
                    <?php if($customer['is_blacklist']): ?>
                        <span class="badge-log badge-danger-minimal" style="margin-top: 8px;">BLACKLIST</span>
                    <?php endif; ?>
                </div>

                <div class="info-list" style="font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f2f2f2;">
                        <span style="opacity: 0.6;">SĐT:</span>
                        <span style="font-weight: 600;"><?= esc($customer['phone']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f2f2f2;">
                        <span style="opacity: 0.6;">Email:</span>
                        <span style="font-weight: 600;"><?= esc($customer['email'] ?: '--') ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="opacity: 0.6;">Địa chỉ:</span>
                        <span style="font-weight: 500; text-align: right; max-width: 200px;"><?= esc($customer['address'] ?: '--') ?></span>
                    </div>
                </div>
            </div>

            <div class="premium-card" style="padding: 20px;">
                <h4 style="margin: 0 0 15px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.6;">Chỉ số sức khỏe</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="background: #f5f5f7; padding: 12px; border-radius: 12px; text-align: center;">
                        <div style="font-size: 20px; font-weight: 700; color: #0071e3;"><?= $customer['total_cases'] ?></div>
                        <div style="font-size: 10px; opacity: 0.6;">Vụ việc</div>
                    </div>
                    <div style="background: #f5f5f7; padding: 12px; border-radius: 12px; text-align: center;">
                        <div style="font-size: 16px; font-weight: 700; color: #34c759;"><?= number_format($customer['total_revenue'] / 1000000, 1) ?>M</div>
                        <div style="font-size: 10px; opacity: 0.6;">Doanh thu</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content: Tabs -->
        <div class="profile-main">
            <div class="premium-card premium-card-full" style="padding: 0;">
                <div class="tabs-navigation-wrapper" style="padding: 0 20px; border-bottom: 1px solid #d2d2d7;">
                    <div class="tabs-container" id="customerModuleTabs">
                        <button class="tab-btn active" data-tab="overview">Tổng quan</button>
                        <button class="tab-btn" data-tab="cases">Vụ việc (<?= count($cases) ?>)</button>
                        <button class="tab-btn" data-tab="interactions">Tương tác</button>
                        <button class="tab-btn" data-tab="finance">Tài chính</button>
                        <button class="tab-btn" data-tab="docs">Tài liệu</button>
                    </div>
                </div>

                <div class="tabs-content-wrapper" style="padding: 25px;">
                    <!-- Tab: Overview -->
                    <div class="tab-pane active" id="overview">
                        <div class="overview-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                            <div>
                                <h4 style="margin-bottom: 15px; font-size: 15px;"><i class="fas fa-id-card" style="margin-right: 8px;"></i>Thông tin định danh</h4>
                                <table class="info-table-minimal" style="width: 100%; font-size: 13px; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 8px 0; opacity: 0.6; width: 140px;">Loại định danh:</td>
                                        <td style="padding: 8px 0; font-weight: 600;"><?= strtoupper(esc($customer['identity_type'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; opacity: 0.6;">Số định danh:</td>
                                        <td style="padding: 8px 0; font-weight: 600;"><?= esc($customer['identity_number'] ?: '--') ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; opacity: 0.6;">Ngày cấp:</td>
                                        <td style="padding: 8px 0; font-weight: 500;"><?= $customer['issue_date'] ? date('d/m/Y', strtotime($customer['issue_date'])) : '--' ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; opacity: 0.6;">Nơi cấp:</td>
                                        <td style="padding: 8px 0; font-weight: 500;"><?= esc($customer['issued_by'] ?: '--') ?></td>
                                    </tr>
                                </table>
                            </div>
                            <?php if($customer['type'] == 'doanh_nghiep'): ?>
                            <div>
                                <h4 style="margin-bottom: 15px; font-size: 15px;"><i class="fas fa-building" style="margin-right: 8px;"></i>Thông tin doanh nghiệp</h4>
                                <table class="info-table-minimal" style="width: 100%; font-size: 13px;">
                                    <tr>
                                        <td style="padding: 8px 0; opacity: 0.6; width: 140px;">Tên công ty:</td>
                                        <td style="padding: 8px 0; font-weight: 600;"><?= esc($customer['company_name']) ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; opacity: 0.6;">Mã số thuế:</td>
                                        <td style="padding: 8px 0; font-weight: 600;"><?= esc($customer['tax_code']) ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; opacity: 0.6;">Đăng ký kinh doanh:</td>
                                        <td style="padding: 8px 0; font-weight: 500;"><?= esc($customer['biz_registration_number'] ?: '--') ?></td>
                                    </tr>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top: 30px;">
                            <h4 style="margin-bottom: 15px; font-size: 15px;"><i class="fas fa-tags" style="margin-right: 8px;"></i>Tags & Phân loại</h4>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <?php 
                                    $tags = explode(',', $customer['tags'] ?: '');
                                    foreach($tags as $tag): if(trim($tag)):
                                ?>
                                    <span class="badge-log badge-secondary-minimal"><?= esc(trim($tag)) ?></span>
                                <?php endif; endforeach; ?>
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
                                <?php foreach($cases as $case): ?>
                                <tr>
                                    <td><span class="badge-secondary-minimal"><?= esc($case['code']) ?></span></td>
                                    <td style="font-weight: 600;"><?= esc($case['title']) ?></td>
                                    <td><?= esc($case['status']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($case['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab: Interactions -->
                    <div class="tab-pane" id="interactions">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h4 style="margin: 0;">Lịch sử tương tác</h4>
                            <button class="btn-premium-sm" onclick="document.getElementById('modalInteraction').style.display='flex'">
                                <i class="fas fa-plus"></i> Ghi chú tương tác
                            </button>
                        </div>
                        <div class="interaction-timeline">
                            <?php if(empty($interactions)): ?>
                                <p style="text-align: center; opacity: 0.5; padding: 40px;">Chưa có lịch sử tương tác nào.</p>
                            <?php else: ?>
                                <?php foreach($interactions as $int): ?>
                                    <div class="timeline-item" style="padding-left: 20px; border-left: 2px solid #0071e3; position: relative; margin-bottom: 25px;">
                                        <div style="position: absolute; left: -7px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: #0071e3;"></div>
                                        <div style="font-size: 11px; opacity: 0.6; margin-bottom: 4px;"><?= date('d/m/Y H:i', strtotime($int['interaction_date'])) ?> • <?= esc($int['staff_email']) ?></div>
                                        <div style="font-weight: 600; font-size: 14px;"><?= esc($int['summary']) ?></div>
                                        <div style="font-size: 13px; margin-top: 6px; opacity: 0.8;"><?= esc($int['detailed_content']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
                                <?php foreach($payments as $pay): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($pay['payment_date'])) ?></td>
                                    <td style="font-weight: 700;"><?= number_format($pay['amount'], 0, ',', '.') ?></td>
                                    <td><?= esc($pay['method']) ?></td>
                                    <td><?= esc($pay['description']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab: Documents -->
                    <div class="tab-pane" id="docs">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h4 style="margin: 0;">Hồ sơ số hóa (Vault)</h4>
                            <button class="btn-premium-sm" onclick="document.getElementById('modalUpload').style.display='flex'">
                                <i class="fas fa-upload"></i> Tải lên tài liệu
                            </button>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                            <?php foreach($documents as $doc): ?>
                                <div class="premium-card" style="padding: 15px; background: #fafafa;">
                                    <div style="font-size: 24px; color: #0071e3; margin-bottom: 10px;">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div style="font-weight: 600; font-size: 13px; margin-bottom: 4px;"><?= esc($doc['document_type']) ?></div>
                                    <div style="font-size: 11px; opacity: 0.6;"><?= esc($doc['file_name']) ?></div>
                                    <div style="margin-top: 10px; display: flex; gap: 10px;">
                                        <a href="<?= base_url($doc['file_path']) ?>" class="btn-secondary-sm" target="_blank">Xem</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Interaction -->
<div id="modalInteraction" class="modal-premium-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="premium-card" style="width: 500px; padding: 30px;">
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
<div id="modalUpload" class="modal-premium-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="premium-card" style="width: 500px; padding: 30px;">
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
