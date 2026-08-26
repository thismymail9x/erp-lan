<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/office_expenses.css') ?>?v=<?= time() ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$money = static fn($value) => number_format((int)$value, 0, ',', '.') . 'đ';
$changeClass = static fn($value) => $value > 0 ? 'is-up' : ($value < 0 ? 'is-down' : 'is-flat');
$changeText = static fn($value) => ($value > 0 ? '+' : '') . number_format((float)$value, 1, ',', '.') . '%';
$chartPayload = [
    'labels' => array_map(static fn($m) => 'T' . $m, range(1, 12)),
    'current' => array_map('intval', $monthly ?? []),
    'previous' => array_map('intval', $previousMonthly ?? []),
    'year' => (int)($filters['year'] ?? date('Y')),
    'previousYear' => (int)($filters['year'] ?? date('Y')) - 1,
    'categories' => array_map(static function($row) use ($categoryLabels) {
        return [
            'label' => $categoryLabels[$row['category']] ?? $row['category'],
            'total' => (int)$row['total'],
        ];
    }, $categoryBreakdown ?? []),
];
?>
<div class="office-expense-page">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Chi phí vận hành</h2>
            <p class="content-subtitle">Kế toán nhập các khoản điện, nước, internet, văn phòng phẩm và chi phí vận hành nội bộ; Admin theo dõi theo tháng, năm và cơ cấu chi phí.</p>
        </div>
    </div>

    <div class="office-stats-grid">
        <div class="office-stat">
            <span>Tổng theo bộ lọc</span>
            <strong><?= $money($summary['total'] ?? 0) ?></strong>
            <small><?= number_format((int)($summary['count'] ?? 0), 0, ',', '.') ?> khoản</small>
        </div>
        <div class="office-stat">
            <span>Trung bình/khoản</span>
            <strong><?= $money($summary['average'] ?? 0) ?></strong>
            <small>Dựa trên dữ liệu đang lọc</small>
        </div>
        <div class="office-stat">
            <span>So với năm trước</span>
            <strong class="<?= $changeClass($summary['year_change_percent'] ?? 0) ?>"><?= $changeText($summary['year_change_percent'] ?? 0) ?></strong>
            <small>Năm trước: <?= $money($summary['previous_year_total'] ?? 0) ?></small>
        </div>
        <div class="office-stat">
            <span>So với tháng trước</span>
            <strong class="<?= $changeClass($summary['month_change_percent'] ?? 0) ?>"><?= $changeText($summary['month_change_percent'] ?? 0) ?></strong>
            <small>Tháng trước: <?= $money($summary['previous_month_total'] ?? 0) ?></small>
        </div>
    </div>

    <?php if ($canManage) { ?>
        <div class="office-panel">
            <div class="office-panel-title"><i class="fas fa-plus-circle"></i> Nhập chi phí vận hành</div>
            <form action="<?= base_url('office-expenses/store') ?>" method="POST" enctype="multipart/form-data" class="office-form">
                <?= csrf_field() ?>
                <div class="office-form-grid">
                    <div class="form-group-premium">
                        <label>Ngày chi</label>
                        <input type="date" name="expense_date" class="form-control-premium" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group-premium">
                        <label>Loại chi phí</label>
                        <select name="category" class="form-control-premium" required>
                            <?php foreach ($categoryLabels as $key => $label) { ?>
                                <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label>Số tiền</label>
                        <input type="text" name="amount" class="form-control-premium js-money-input" inputmode="numeric" autocomplete="off" placeholder="VD: 1200000" required>
                    </div>
                    <div class="form-group-premium">
                        <label>Thanh toán</label>
                        <select name="payment_method" class="form-control-premium">
                            <?php foreach ($paymentMethodLabels as $key => $label) { ?>
                                <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label>Nhà cung cấp</label>
                        <input type="text" name="vendor" class="form-control-premium" placeholder="EVN, nước sạch, nhà mạng...">
                    </div>
                    <div class="form-group-premium">
                        <label>Chứng từ</label>
                        <input type="file" name="receipt" class="form-control-premium" accept="image/*,.pdf">
                    </div>
                    <div class="form-group-premium office-note-field">
                        <label>Ghi chú</label>
                        <textarea name="note" class="form-control-premium" placeholder="Kỳ thanh toán, mã hóa đơn, lý do phát sinh..."></textarea>
                    </div>
                </div>
                <div class="office-form-actions">
                    <button type="submit" class="btn-premium-sm"><i class="fas fa-save"></i> Lưu chi phí</button>
                </div>
            </form>
        </div>
    <?php } ?>

    <div class="office-analytics-grid">
        <div class="office-panel office-chart-panel">
            <div class="office-panel-heading">
                <div>
                    <div class="office-panel-title">Xu hướng chi phí theo tháng</div>
                    <p>So sánh <?= esc($filters['year'] ?? date('Y')) ?> với <?= esc(($filters['year'] ?? date('Y')) - 1) ?></p>
                </div>
            </div>
            <canvas id="officeMonthlyChart" height="260" aria-label="Biểu đồ chi phí theo tháng"></canvas>
        </div>
        <div class="office-panel office-chart-panel">
            <div class="office-panel-title">Cơ cấu chi phí</div>
            <canvas id="officeCategoryChart" height="260" aria-label="Biểu đồ cơ cấu chi phí"></canvas>
            <div class="office-category-list">
                <?php if (empty($categoryBreakdown)) { ?>
                    <div class="office-empty-mini">Chưa có dữ liệu.</div>
                <?php } ?>
                <?php foreach (($categoryBreakdown ?? []) as $row) { ?>
                    <div>
                        <span><?= esc($categoryLabels[$row['category']] ?? $row['category']) ?></span>
                        <strong><?= $money($row['total']) ?></strong>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="office-panel">
        <form method="GET" action="<?= base_url('office-expenses') ?>" class="office-filter">
            <input type="text" name="search" class="form-control-premium" placeholder="Tìm nhà cung cấp, ghi chú..." value="<?= esc($filters['search'] ?? '') ?>">
            <select name="category" class="form-control-premium">
                <option value="">Tất cả loại</option>
                <?php foreach ($categoryLabels as $key => $label) { ?>
                    <option value="<?= esc($key) ?>" <?= ($filters['category'] ?? '') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php } ?>
            </select>
            <select name="month" class="form-control-premium">
                <option value="">Tất cả tháng</option>
                <?php for ($m = 1; $m <= 12; $m++) { ?>
                    <option value="<?= $m ?>" <?= ($filters['month'] ?? 0) == $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                <?php } ?>
            </select>
            <input type="number" name="year" class="form-control-premium" value="<?= esc($filters['year'] ?? date('Y')) ?>" min="2024" max="<?= date('Y') + 1 ?>">
            <button type="submit" class="btn-secondary-sm"><i class="fas fa-filter"></i> Lọc</button>
        </form>

        <div class="office-table-layout">
            <div class="office-table-wrap">
                <table class="premium-table office-table">
                    <colgroup>
                        <col class="office-col-date">
                        <col class="office-col-category">
                        <col class="office-col-payment">
                        <col class="office-col-money">
                        <col class="office-col-person">
                        <col class="office-col-receipt">
                        <col class="office-col-note">
                        <col class="office-col-actions">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Loại</th>
                            <th>Thanh toán</th>
                            <th class="text-right">Số tiền</th>
                            <th>Người nhập</th>
                            <th>Chứng từ</th>
                            <th>Ghi chú</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)) { ?>
                            <tr><td colspan="8" class="text-center p-20 text-muted-dark">Chưa có chi phí vận hành.</td></tr>
                        <?php } ?>
                        <?php foreach ($expenses as $expense) { ?>
                            <tr>
                                <td data-label="Ngay" class="office-date-cell"><?= date('d/m/Y', strtotime($expense['expense_date'])) ?></td>
                                <td data-label="Loai" class="office-category-cell"><?= esc($categoryLabels[$expense['category']] ?? $expense['category']) ?></td>
                                <td data-label="Thanh toan" class="office-payment-cell"><?= esc($paymentMethodLabels[$expense['payment_method']] ?? $expense['payment_method']) ?></td>
                                <td data-label="So tien" class="text-right office-money-cell"><?= $money($expense['amount']) ?></td>
                                <td data-label="Nguoi nhap" class="office-person-cell"><?= esc($expense['creator_name'] ?? '') ?></td>
                                <td data-label="Chung tu" class="office-receipt-cell">
                                    <?php if (!empty($expense['receipt_file_path'])) { ?>
                                        <div class="office-receipt-actions">
                                            <a href="<?= base_url('office-expenses/receipt/' . $expense['id']) ?>" target="_blank" rel="noopener" title="Xem chứng từ"><i class="fas fa-eye"></i></a>
                                            <a href="<?= base_url('office-expenses/receipt/' . $expense['id'] . '/download') ?>" title="Tải chứng từ"><i class="fas fa-download"></i></a>
                                        </div>
                                        <small title="<?= esc($expense['receipt_file_name'] ?? '') ?>"><?= esc($expense['receipt_file_name'] ?? 'Chứng từ') ?></small>
                                    <?php } else { ?>
                                        <span class="office-no-receipt">--</span>
                                    <?php } ?>
                                </td>
                                <td data-label="Ghi chu" class="office-note-cell" data-full-note="<?= esc($expense['note'] ?? '') ?>"><div class="office-note-line"><?= esc($expense['note'] ?? '') ?></div></td>
                                <td data-label="Thao tac" class="office-actions-cell">
                                    <?php if ($canManage) { ?>
                                        <a href="<?= base_url('office-expenses/delete/' . $expense['id']) ?>" class="btn-delete-mini js-confirm-delete" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <aside class="office-top-list">
                <div class="office-panel-title">Khoản lớn nhất</div>
                <?php if (empty($topExpenses)) { ?>
                    <div class="office-empty-mini">Chưa có dữ liệu.</div>
                <?php } ?>
                <?php foreach (($topExpenses ?? []) as $item) { ?>
                    <div class="office-top-item">
                        <span><?= esc($categoryLabels[$item['category']] ?? $item['category']) ?></span>
                        <strong><?= $money($item['amount']) ?></strong>
                        <small><?= date('d/m/Y', strtotime($item['expense_date'])) ?><?= $item['vendor'] ? ' · ' . esc($item['vendor']) : '' ?></small>
                    </div>
                <?php } ?>
            </aside>
        </div>

        <div class="office-list-summary">
            <div>
                <span>Tổng số dòng theo bộ lọc</span>
                <strong><?= number_format((int)($summary['count'] ?? 0), 0, ',', '.') ?></strong>
            </div>
            <div>
                <span>Tổng chi phí</span>
                <strong><?= $money($summary['total'] ?? 0) ?></strong>
            </div>
            <div>
                <span>Trung bình/khoản</span>
                <strong><?= $money($summary['average'] ?? 0) ?></strong>
            </div>
        </div>

        <div class="pagination-wrapper">
            <?= $pager->links() ?>
        </div>
    </div>
</div>

<script type="application/json" id="officeExpenseChartData"><?= json_encode($chartPayload, JSON_UNESCAPED_UNICODE) ?></script>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/office_expenses.js') ?>?v=<?= time() ?>"></script>
<?= $this->endSection() ?>
