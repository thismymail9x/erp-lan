<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="cases-page-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Vụ việc của tôi</h2>
            <p class="content-subtitle">Danh sách các hồ sơ đang được gán trực tiếp cho bạn xử lý.</p>
        </div>
    </div>

    <div class="premium-card premium-card-full">
        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th style="width: 120px;">Mã số</th>
                        <th>Vụ việc</th>
                        <th>Khách hàng</th>
                        <th>Bước hiện tại</th>
                        <th class="table-cell-center">Hạn bước</th>
                        <th class="table-cell-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cases)): ?>
                        <tr><td colspan="6" class="empty-state-container">Bạn chưa có vụ việc nào được gán.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cases as $case): ?>
                         <tr>
                            <td><span class="badge-secondary-minimal" title="Mã hiệu hồ sơ pháp lý"><?= esc($case['code']) ?></span></td>
                            <td>
                                <div class="font-weight-600" title="Tiêu đề vụ tranh chấp/hồ sơ"><?= esc($case['title']) ?></div>
                                <div class="text-xs opacity-06"><?= esc($case['type']) ?></div>
                            </td>
                            <td><div class="font-weight-500" title="Khách hàng đầu mối hồ sơ"><?= esc($case['customer_name']) ?></div></td>
                            <td>
                                <span class="text-apple-main font-weight-500" title="Công việc đang thực hiện">
                                    <?= esc($case['current_step_name'] ?: 'N/A') ?>
                                </span>
                            </td>
                            <td class="table-cell-center">
                                <?php if($case['step_deadline']): ?>
                                    <div class="font-weight-600" style="<?= (strtotime($case['step_deadline']) < time()) ? 'color: var(--apple-red);' : '' ?>" title="Hạn chót phải hoàn thành bước này">
                                        <?= date('d/m/Y', strtotime($case['step_deadline'])) ?>
                                    </div>
                                    <div class="text-xs opacity-05">
                                        <?= (strtotime($case['step_deadline']) < time()) ? 'Quá hạn' : 'Sắp tới' ?>
                                    </div>
                                <?php else: ?> -- <?php endif; ?>
                            </td>
                            <td class="table-cell-right">
                                <a href="<?= base_url('cases/show/' . $case['id']) ?>" class="btn-premium btn-sm" title="Truy cập chi tiết và xử lý hồ sơ">Xử lý</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
