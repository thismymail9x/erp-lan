<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="customers-stale-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Khách hàng cần chăm sóc</h2>
            <p class="content-subtitle">Danh sách khách hàng đã quá 30 ngày chưa có tương tác hoặc cập nhật.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('customers') ?>" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại CRM
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-full">
        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Khách hàng</th>
                        <th>Liên hệ</th>
                        <th class="table-cell-center">Ngày tương tác cuối</th>
                        <th class="table-cell-center">Số ngày "bỏ ngỏ"</th>
                        <th class="table-cell-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)) { ?>
                        <tr>
                            <td colspan="5" class="empty-state-container">
                                <i class="fas fa-check-circle stale-empty-icon"></i>
                                Tuyệt vời! Tất cả khách hàng đều được chăm sóc tốt.
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($customers as $customer) { 
                            $lastDate = $customer['last_contact_date'] ?: $customer['created_at'];
                            $daysDiff = floor((time() - strtotime($lastDate)) / (60 * 60 * 24));
                        ?>
                        <tr>
                            <td>
                                <div class="cust-name-main"><?= esc($customer['name']) ?></div>
                                <div class="cust-code-sub"><?= esc($customer['code']) ?></div>
                            </td>
                            <td>
                                <div class="cust-phone-val"><?= esc($customer['phone']) ?></div>
                            </td>
                            <td class="table-cell-center">
                                <span class="badge-log badge-secondary-minimal">
                                    <?= $customer['last_contact_date'] ? date('d/m/Y', strtotime($customer['last_contact_date'])) : 'Chưa có' ?>
                                </span>
                            </td>
                            <td class="table-cell-center">
                                <span class="<?= ($daysDiff > 60) ? 'stale-days-critical' : 'stale-days-warning' ?>">
                                    <?= $daysDiff ?> ngày
                                </span>
                            </td>
                            <td class="table-cell-center">
                                <a href="<?= base_url('customers/show/' . $customer['id']) ?>" class="btn-premium-sm">
                                    <i class="fas fa-phone"></i> Chăm sóc ngay
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
