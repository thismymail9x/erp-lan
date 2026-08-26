<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/partners.css') ?>?v=<?= time() ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="partner-page">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Đối tác & hoa hồng vụ việc</h2>
            <p class="content-subtitle">Quản lý đối tác đăng nhập bằng tài khoản user, cấu hình %/số tiền theo từng vụ việc và chi trả theo tiến độ khách thanh toán.</p>
        </div>
    </div>

    <div class="partner-stats-grid">
        <div class="partner-stat"><span>Tổng phát sinh</span><strong><?= number_format($stats['total'] ?? 0, 0, ',', '.') ?>đ</strong></div>
        <div class="partner-stat"><span>Đã phát sinh</span><strong><?= number_format($stats['accrued'] ?? 0, 0, ',', '.') ?>đ</strong></div>
        <div class="partner-stat"><span>Đối tác yêu cầu</span><strong><?= number_format($stats['requested'] ?? 0, 0, ',', '.') ?>đ</strong></div>
        <div class="partner-stat"><span>Đã duyệt</span><strong><?= number_format($stats['approved'] ?? 0, 0, ',', '.') ?>đ</strong></div>
        <div class="partner-stat"><span>Đã thanh toán</span><strong><?= number_format($stats['paid'] ?? 0, 0, ',', '.') ?>đ</strong></div>
    </div>

    <?php if ($canManage) { ?>
        <div class="partner-panel">
            <div class="partner-panel-title"><i class="fas fa-user-plus"></i> Tạo / liên kết đối tác</div>
            <form action="<?= base_url('partners/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="partner-form-grid">
                    <div class="form-group-premium">
                        <label>Tên đối tác</label>
                        <input type="text" name="name" class="form-control-premium" required>
                    </div>
                    <div class="form-group-premium">
                        <label>Loại</label>
                        <select name="partner_type" class="form-control-premium">
                            <option value="individual">Cá nhân</option>
                            <option value="company">Công ty</option>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label>SĐT</label>
                        <input type="text" name="phone" class="form-control-premium">
                    </div>
                    <div class="form-group-premium">
                        <label>Email liên hệ</label>
                        <input type="email" name="email" class="form-control-premium">
                    </div>
                    <div class="form-group-premium partner-span-2">
                        <label>Liên kết user có sẵn</label>
                        <select name="user_id" class="form-control-premium partner-select2">
                            <option value="">-- Không chọn --</option>
                            <?php foreach ($selectableUsers as $user) { ?>
                                <option value="<?= esc($user['id']) ?>"><?= esc($user['email'] . (!empty($user['full_name']) ? ' - ' . $user['full_name'] : '')) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label>Hoặc tạo login email</label>
                        <input type="email" name="login_email" class="form-control-premium" placeholder="partner@email.com">
                    </div>
                    <div class="form-group-premium">
                        <label>Mật khẩu login</label>
                        <input type="password" name="password" class="form-control-premium" autocomplete="new-password">
                    </div>
                    <div class="form-group-premium">
                        <label>Ngân hàng</label>
                        <input type="text" name="bank_name" class="form-control-premium">
                    </div>
                    <div class="form-group-premium">
                        <label>Số tài khoản</label>
                        <input type="text" name="bank_account" class="form-control-premium">
                    </div>
                    <div class="form-group-premium">
                        <label>Chủ tài khoản</label>
                        <input type="text" name="bank_owner" class="form-control-premium">
                    </div>
                    <div class="form-group-premium">
                        <label>Trạng thái</label>
                        <select name="status" class="form-control-premium">
                            <option value="active">Đang hợp tác</option>
                            <option value="paused">Tạm dừng</option>
                            <option value="ended">Kết thúc</option>
                        </select>
                    </div>
                    <div class="form-group-premium partner-span-4">
                        <label>Ghi chú</label>
                        <textarea name="notes" class="form-control-premium"></textarea>
                    </div>
                </div>
                <div class="partner-actions">
                    <button type="submit" class="btn-premium-sm"><i class="fas fa-save"></i> Lưu</button>
                </div>
            </form>
        </div>

        <div class="partner-panel">
            <div class="partner-panel-title"><i class="fas fa-link"></i> Gắn đối tác vào vụ việc</div>
            <form action="<?= base_url('partners/case-partners/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="partner-form-grid">
                    <div class="form-group-premium partner-span-2">
                        <label>Vụ việc</label>
                        <select name="case_id" class="form-control-premium partner-select2" required>
                            <option value="">-- Chọn vụ việc --</option>
                            <?php foreach ($selectableCases as $caseOption) { ?>
                                <option value="<?= esc($caseOption['id']) ?>"><?= esc($caseOption['code'] . ' - ' . $caseOption['title'] . ' - ' . ($caseOption['customer_name'] ?? '')) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label>Đối tác</label>
                        <select name="partner_id" class="form-control-premium partner-select2" required>
                            <option value="">-- Chọn đối tác --</option>
                            <?php foreach ($allPartners as $partnerOption) { ?>
                                <option value="<?= esc($partnerOption['id']) ?>"><?= esc($partnerOption['name']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label>Vai trò</label>
                        <select name="role_label" class="form-control-premium">
                            <?php foreach ($roleLabels as $key => $label) { ?>
                                <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label>Cách tính</label>
                        <select name="calculation_base" class="form-control-premium">
                            <?php foreach ($baseLabels as $key => $label) { ?>
                                <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label>% hoa hồng</label>
                        <input type="number" step="0.01" min="0" name="percentage" class="form-control-premium" placeholder="VD: 10">
                    </div>
                    <div class="form-group-premium">
                        <label>Số tiền cố định</label>
                        <input type="text" name="fixed_amount" class="form-control-premium js-partner-money" placeholder="VD: 5000000">
                    </div>
                    <div class="form-group-premium partner-span-2">
                        <label>Ghi chú hợp tác</label>
                        <input type="text" name="notes" class="form-control-premium">
                    </div>
                </div>
                <div class="partner-actions">
                    <button type="submit" class="btn-premium-sm"><i class="fas fa-plus"></i> Gắn</button>
                </div>
            </form>
        </div>
    <?php } ?>

    <div class="partner-panel">
        <div class="partner-panel-title"><i class="fas fa-users"></i> Danh sách đối tác</div>
        <form method="GET" action="<?= base_url('partners') ?>" class="partner-filter">
            <input type="text" name="search" class="form-control-premium" value="<?= esc($filters['search'] ?? '') ?>" placeholder="Tìm đối tác, email, SĐT...">
            <select name="status" class="form-control-premium">
                <option value="">Tất cả đối tác</option>
                <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Đang hợp tác</option>
                <option value="paused" <?= ($filters['status'] ?? '') === 'paused' ? 'selected' : '' ?>>Tạm dừng</option>
                <option value="ended" <?= ($filters['status'] ?? '') === 'ended' ? 'selected' : '' ?>>Kết thúc</option>
            </select>
            <select name="entry_status" class="form-control-premium">
                <option value="">Tất cả chi trả</option>
                <?php foreach ($entryStatusLabels as $key => $label) { ?>
                    <option value="<?= esc($key) ?>" <?= ($filters['entry_status'] ?? '') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php } ?>
            </select>
            <button type="submit" class="btn-secondary-sm"><i class="fas fa-filter"></i> Lọc</button>
        </form>

        <div class="partner-table-wrap">
            <table class="partner-table">
                <thead>
                    <tr>
                        <th>Đối tác</th>
                        <th>Liên hệ</th>
                        <th>Tài khoản</th>
                        <th>Ngân hàng</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($partners)) { ?>
                        <tr><td colspan="5" class="text-center">Chưa có đối tác.</td></tr>
                    <?php } ?>
                    <?php foreach ($partners as $partner) { ?>
                        <tr>
                            <td data-label="Đối tác"><strong><?= esc($partner['name']) ?></strong><div class="partner-muted"><?= esc($partner['partner_type']) ?></div></td>
                            <td data-label="Liên hệ"><?= esc($partner['phone'] ?: '-') ?><div class="partner-muted"><?= esc($partner['email'] ?: '-') ?></div></td>
                            <td data-label="Tài khoản"><?= esc($partner['login_email'] ?: 'Chưa liên kết') ?></td>
                            <td data-label="Ngân hàng"><?= esc($partner['bank_name'] ?: '-') ?><div class="partner-muted"><?= esc($partner['bank_account'] ?: '') ?></div></td>
                            <td data-label="Trạng thái"><span class="partner-status partner-status-<?= esc($partner['status']) ?>"><?= esc($partner['status']) ?></span></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper"><?= $partnerPager->links('partners') ?></div>
    </div>

    <div class="partner-panel">
        <div class="partner-panel-title"><i class="fas fa-receipt"></i> Hoa hồng phát sinh & yêu cầu chi trả</div>
        <div class="partner-table-wrap">
            <table class="partner-table">
                <thead>
                    <tr>
                        <th>Đối tác</th>
                        <th>Vụ việc</th>
                        <th>Đợt thu</th>
                        <th>Cách tính</th>
                        <th>Số tiền</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)) { ?>
                        <tr><td colspan="7" class="text-center">Chưa có khoản hoa hồng phát sinh.</td></tr>
                    <?php } ?>
                    <?php foreach ($entries as $entry) { ?>
                        <tr>
                            <td data-label="Đối tác"><?= esc($entry['partner_name']) ?><div class="partner-muted"><?= esc($roleLabels[$entry['role_label']] ?? $entry['role_label']) ?></div></td>
                            <td data-label="Vụ việc"><a class="partner-case-link" href="<?= base_url('cases/show/' . $entry['case_id']) ?>"><?= esc($entry['case_code']) ?></a><div class="partner-muted"><?= esc($entry['case_title']) ?> - <?= esc($entry['customer_name']) ?></div></td>
                            <td data-label="Đợt thu"><?= esc($entry['payment_title']) ?><div class="partner-muted"><?= !empty($entry['payment_date']) ? date('d/m/Y', strtotime($entry['payment_date'])) : '-' ?></div></td>
                            <td data-label="Cách tính"><?= esc($baseLabels[$entry['calculation_base']] ?? $entry['calculation_base']) ?><div class="partner-muted"><?= number_format((float)$entry['percentage'], 2, ',', '.') ?>% + <?= number_format((int)$entry['fixed_amount'], 0, ',', '.') ?>đ</div></td>
                            <td data-label="Số tiền"><span class="partner-amount"><?= number_format((int)$entry['commission_amount'], 0, ',', '.') ?>đ</span></td>
                            <td data-label="Trạng thái"><span class="partner-status partner-status-<?= esc($entry['status']) ?>"><?= esc($entryStatusLabels[$entry['status']] ?? $entry['status']) ?></span></td>
                            <td data-label="Thao tác">
                                <?php if ($canPayout) { ?>
                                    <form action="<?= base_url('partners/entries/status/' . $entry['id']) ?>" method="POST" class="partner-mini-form">
                                        <?= csrf_field() ?>
                                        <select name="status" class="form-control-premium">
                                            <?php foreach ($entryStatusLabels as $key => $label) { ?>
                                                <option value="<?= esc($key) ?>" <?= $entry['status'] === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                                            <?php } ?>
                                        </select>
                                        <button type="submit" class="btn-premium-sm"><i class="fas fa-check"></i></button>
                                        <textarea name="admin_note" class="form-control-premium" placeholder="Ghi chú chi trả"><?= esc($entry['admin_note'] ?? '') ?></textarea>
                                    </form>
                                <?php } else { ?>
                                    <span class="partner-muted"><?= esc($entry['admin_note'] ?? '') ?></span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper"><?= $entryPager->links('entries') ?></div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/partners.js') ?>?v=<?= time() ?>"></script>
<?= $this->endSection() ?>
