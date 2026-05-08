<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="tag-view-page">
    <div class="dashboard-header-wrapper m-b-30">
        <div class="header-title-container">
            <h2 class="content-title">Dữ liệu được gắn nhãn: 
                <span class="tag-badge-premium" style="background-color: <?= esc($tag['color']) ?>15; color: <?= esc($tag['color']) ?>; border: 1px solid <?= esc($tag['color']) ?>30; vertical-align: middle; padding: 4px 12px; font-size: 1.2rem;">
                    <i class="fas fa-tag"></i> <?= esc($tag['name']) ?>
                </span>
            </h2>
            <p class="content-subtitle">Tất cả hồ sơ, vụ việc và tài liệu mang nhãn này.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('tags') ?>" class="btn-secondary-sm">
                <i class="fas fa-chevron-left"></i> Quay lại danh sách nhãn
            </a>
        </div>
    </div>

    <div class="premium-card">
        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th class="table-cell-center" style="width: 50px;">STT</th>
                        <th style="width: 130px;">Phân loại</th>
                        <th style="width: 120px;">Mã số</th>
                        <th>Tên / Nội dung</th>
                        <th>Khách hàng</th>
                        <th class="table-cell-center" style="width: 100px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entities)) { ?>
                        <tr>
                            <td colspan="6" class="empty-state-container p-40" style="text-align: center;">
                                <i class="fas fa-search-minus m-b-15" style="font-size: 3rem; opacity: 0.2;"></i>
                                <p>Chưa có dữ liệu nào được gắn nhãn dán này.</p>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php $stt = 0; foreach ($entities as $item) { $stt++; ?>
                            <tr>
                                <td class="table-cell-center text-muted-dark"><?= $stt ?></td>
                                <td>
                                    <span class="badge-log badge-secondary-minimal"><?= esc($item['type']) ?></span>
                                </td>
                                <td>
                                    <span class="text-monospace font-weight-600 text-xs"><?= esc($item['code']) ?></span>
                                </td>
                                <td>
                                    <div class="font-weight-600">
                                        <a href="<?= $item['url'] ?>" class="text-apple-main text-decoration-none hover-underline"><?= esc($item['name']) ?></a>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($item['customer_name'])) { ?>
                                        <a href="<?= $item['customer_url'] ?>" class="link-premium text-sm">
                                            <i class="fas fa-user-circle m-r-5 text-muted-dark"></i> <?= esc($item['customer_name']) ?>
                                        </a>
                                    <?php } else { ?>
                                        <span class="text-muted-dark italic">--</span>
                                    <?php } ?>
                                </td>
                                <td class="table-cell-center">
                                    <a href="<?= $item['url'] ?>" class="btn-secondary-sm" title="Xem chi tiết">
                                        <i class="fas fa-external-link-alt"></i> Mở
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
