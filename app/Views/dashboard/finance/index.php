<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="cases-page-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Tài chính Vụ việc</h2>
            <p class="content-subtitle">Hành chính - Kế toán: Quản lý giá trị hợp đồng và tiến độ thanh toán khách hàng.</p>
        </div>
        <div class="header-actions">
            <!-- Thống kê nhỏ góc trên -->
            <div class="stat-badge" style="background: rgba(var(--apple-blue-rgb), 0.1); color: var(--apple-blue); padding: 8px 15px; border-radius: 8px; font-weight: 600; font-size: 0.9rem;">
                <i class="fas fa-coins m-r-5"></i> Tổng giá trị khả dụng: <?= number_format($totalContracts, 0, ',', '.') ?> VNĐ
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="search-filter-wrapper m-b-16" style="display: flex; gap: 12px; align-items: center; flex-wrap: nowrap;">
        <form action="" method="GET" style="flex: 1; min-width: 200px; margin: 0;">
            <div class="search-input-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="input-premium" placeholder="Tìm tên vụ việc hoặc khách hàng..." value="<?= esc(request()->getGet('search') ?? '') ?>" autocomplete="off" style="width: 100%;">
            </div>
        </form>
    </div>

    <div class="premium-card premium-card-full">

        <div class="table-responsive">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th style="width: 100px;">Mã số</th>
                        <th style="width: 25%">Vụ việc / Khách hàng</th>
                        <th>Giá trị Hợp đồng</th>
                        <th>Tiến độ Thanh toán</th>
                        <th class="table-cell-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cases)): ?>
                        <tr><td colspan="5" class="empty-state-container">Không có dữ liệu tài chính hồ sơ nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cases as $case): ?>
                        <tr>
                            <td><span class="badge-secondary-minimal"><?= esc($case['code']) ?></span></td>
                            <td>
                                <div class="font-weight-600" style="color: var(--apple-main);"><?= esc($case['title']) ?></div>
                                <div class="text-xs text-muted-dark"><i class="fas fa-user-circle m-r-4"></i> <?= esc($case['customer_name'] ?: 'N/A') ?></div>
                            </td>
                            <td>
                                <div class="font-weight-700" style="color: var(--apple-blue);">
                                    <?= !empty($case['contract_value']) ? number_format($case['contract_value'], 0, ',', '.') . 'đ' : '<span class="text-muted-dark font-weight-400 italic text-xs">Chưa chốt</span>' ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                if (!empty($case['payment_progress'])) {
                                    $payments = json_decode($case['payment_progress'], true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($payments)) {
                                        echo '<div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 4px;">';
                                        foreach ($payments as $p) {
                                            $amt = !empty($p['amount']) ? number_format($p['amount'], 0, ',', '.') . 'đ' : '';
                                            $warnHtml = '';
                                            $isPaid = (!empty($p['is_paid']) && $p['is_paid'] == 1);
                                            if (!$isPaid && !empty($p['deadline']) && strtotime($p['deadline']) < time()) {
                                                $warnHtml = ' <i class="fas fa-exclamation-triangle text-apple-red" title="Đã trễ hạn"></i>';
                                            }
                                            $paidBadge = $isPaid ? ' <span class="badge-success-minimal text-xs"><i class="fas fa-check"></i> Thu</span>' : '';
                                            echo '<div style="background: rgba(var(--apple-gray-rgb), 0.5); padding: 5px 8px; border-radius: 4px; display: flex; align-items: center; justify-content: space-between;">' . 
                                                 '<span><strong>' . esc($p['title']) . ':</strong> <span style="color:var(--apple-blue); font-weight:600;">' . $amt . '</span>' .
                                                 (!empty($p['deadline']) ? ' <em class="text-xs text-muted-dark">(Hạn: '.date('d/m', strtotime($p['deadline'])).')</em>' : '') . 
                                                 $warnHtml . '</span>' . $paidBadge .
                                                 '</div>';
                                        }
                                        echo '</div>';
                                    } else {
                                        echo '<span class="text-muted-dark text-xs italic">Đã ghi chú (cũ)</span>';
                                    }
                                } else {
                                    echo '<span class="text-muted-dark text-xs italic">--</span>';
                                }
                                ?>
                            </td>
                            <td class="table-cell-right">
                                <a href="<?= base_url('cases/edit/' . $case['id']) ?>#payment-progress-container" class="btn-premium btn-sm" title="Vào trang sửa hồ sơ để cập nhật tài chính">
                                    <i class="fas fa-edit m-r-4"></i> Cập nhật
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($pager)) : ?>
            <div class="pagination-wrapper">
                <?= $pager->links() ?>
            </div>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
