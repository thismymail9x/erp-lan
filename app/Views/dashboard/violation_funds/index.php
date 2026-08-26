<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/violation_funds.css') ?>?v=<?= time() ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$money = static fn($value) => number_format((int)$value, 0, ',', '.') . 'đ';
$chartPayload = [
    'categories' => array_map(static function($row) use ($categoryLabels) {
        return [
            'label' => $categoryLabels[$row['category']] ?? $row['category'],
            'total' => (int)$row['total'],
        ];
    }, $categoryBreakdown ?? []),
    'employees' => array_map(static function($row) {
        return [
            'label' => $row['full_name'] ?? 'Không rõ',
            'total' => (int)$row['total'],
        ];
    }, $employeeBreakdown ?? []),
];
?>
<div class="violation-page">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Quỹ vi phạm nội bộ</h2>
            <p class="content-subtitle">Nhân sự ghi nhận vi phạm, hệ thống thông báo người liên quan và hành chính theo dõi từng khoản cần thu.</p>
        </div>
    </div>

    <div class="violation-stats-grid">
        <div class="violation-stat">
            <span>Tổng khoản</span>
            <strong><?= $money($summary['total_amount'] ?? 0) ?></strong>
            <small><?= number_format((int)($summary['total_count'] ?? 0), 0, ',', '.') ?> vi phạm</small>
        </div>
        <div class="violation-stat">
            <span>Đã thu</span>
            <strong class="is-collected"><?= $money($summary['collected_amount'] ?? 0) ?></strong>
            <small>Đã xác nhận bởi hành chính</small>
        </div>
        <div class="violation-stat">
            <span>Cần thu</span>
            <strong class="is-pending"><?= $money($summary['pending_amount'] ?? 0) ?></strong>
            <small>Đã thông báo, chưa thu</small>
        </div>
        <div class="violation-stat">
            <span>Miễn/không thu</span>
            <strong><?= $money($summary['waived_amount'] ?? 0) ?></strong>
            <small>Có ghi chú xử lý riêng</small>
        </div>
    </div>

    <?php if ($canCreate) { ?>
        <div class="violation-panel">
            <div class="violation-panel-title"><i class="fas fa-user-shield"></i> Ghi nhận vi phạm</div>
            <form action="<?= base_url('violation-funds/store') ?>" method="POST" class="violation-form">
                <?= csrf_field() ?>
                <div class="violation-form-grid">
                    <div class="form-group-premium">
                        <label>Nhân sự vi phạm</label>
                        <select name="employee_id" class="form-control-premium" required>
                            <option value="">Chọn nhân sự</option>
                            <?php foreach ($employees as $employee) { ?>
                                <option value="<?= (int)$employee['id'] ?>"><?= esc($employee['full_name']) ?><?= $employee['position'] ? ' - ' . esc($employee['position']) : '' ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label>Ngày vi phạm</label>
                        <input type="date" name="violation_date" class="form-control-premium" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group-premium">
                        <label>Tháng thu</label>
                        <input type="month" name="due_month" class="form-control-premium" value="<?= esc($filters['due_month'] ?? date('Y-m')) ?>" required>
                    </div>
                    <div class="form-group-premium">
                        <label>Danh sách lỗi</label>
                        <select name="category" id="violationCategory" class="form-control-premium" required>
                            <?php foreach ($categoryLabels as $key => $label) { ?>
                                <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label>Cấp bậc áp dụng</label>
                        <select name="rank_level" id="violationRank" class="form-control-premium" required>
                            <?php foreach ($rankLabels as $rank => $label) { ?>
                                <option value="<?= (int)$rank ?>"><?= esc($label) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium violation-wide">
                        <label>Giải trình / bối cảnh</label>
                        <textarea name="explanation" class="form-control-premium" placeholder="Ghi rõ nội dung vi phạm, nguyên nhân, mức độ ảnh hưởng, trao đổi với nhân sự nếu có..." required></textarea>
                    </div>
                    <div class="form-group-premium violation-wide">
                        <label>Note nhân sự</label>
                        <textarea name="hr_note" class="form-control-premium" placeholder="Nội dung note để lưu hồ sơ và báo hành chính..."></textarea>
                    </div>
                </div>
                <div class="violation-form-actions">
                    <button type="submit" class="btn-premium-sm"><i class="fas fa-bell"></i> Lưu & thông báo</button>
                </div>
            </form>
        </div>
    <?php } ?>

    <div class="violation-analytics-grid">
        <div class="violation-panel">
            <div class="violation-panel-title">Cơ cấu theo nhóm vi phạm</div>
            <canvas id="violationCategoryChart" height="240"></canvas>
        </div>
        <div class="violation-panel">
            <div class="violation-panel-title">Nhân sự phát sinh nhiều khoản</div>
            <div class="violation-rank-list">
                <?php if (empty($employeeBreakdown)) { ?>
                    <div class="violation-empty-mini">Chưa có dữ liệu.</div>
                <?php } ?>
                <?php foreach (($employeeBreakdown ?? []) as $item) { ?>
                    <div>
                        <span><?= esc($item['full_name'] ?? 'Không rõ') ?></span>
                        <strong><?= $money($item['total']) ?></strong>
                        <small><?= number_format((int)$item['count'], 0, ',', '.') ?> khoản</small>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="violation-panel">
        <form method="GET" action="<?= base_url('violation-funds') ?>" class="violation-filter">
            <input type="text" name="search" class="form-control-premium" placeholder="Tìm giải trình, ghi chú, nhân sự..." value="<?= esc($filters['search'] ?? '') ?>">
            <input type="month" name="due_month" class="form-control-premium" value="<?= esc($filters['due_month'] ?? date('Y-m')) ?>">
            <?php if ($canViewAll) { ?>
                <select name="employee_id" class="form-control-premium">
                    <option value="">Tất cả nhân sự</option>
                    <?php foreach ($employees as $employee) { ?>
                        <option value="<?= (int)$employee['id'] ?>" <?= (int)($filters['employee_id'] ?? 0) === (int)$employee['id'] ? 'selected' : '' ?>><?= esc($employee['full_name']) ?></option>
                    <?php } ?>
                </select>
            <?php } ?>
            <select name="category" class="form-control-premium">
                <option value="">Tất cả nhóm</option>
                <?php foreach ($categoryLabels as $key => $label) { ?>
                    <option value="<?= esc($key) ?>" <?= ($filters['category'] ?? '') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php } ?>
            </select>
            <select name="status" class="form-control-premium">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($statusLabels as $key => $label) { ?>
                    <option value="<?= esc($key) ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php } ?>
            </select>
            <button type="submit" class="btn-secondary-sm"><i class="fas fa-filter"></i> Lọc</button>
        </form>

        <div class="violation-table-wrap">
            <table class="premium-table violation-table">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Nhân sự</th>
                        <th>Nhóm lỗi</th>
                        <th>Giải trình / note</th>
                        <th class="text-right">Số tiền</th>
                        <th>Trạng thái</th>
                        <th>Người ghi</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)) { ?>
                        <tr><td colspan="8" class="text-center p-20 text-muted-dark">Chưa có khoản vi phạm theo bộ lọc.</td></tr>
                    <?php } ?>
                    <?php foreach ($records as $record) { ?>
                        <tr>
                            <td data-label="Ngày"><?= date('d/m/Y', strtotime($record['violation_date'])) ?><small><?= esc($record['due_month']) ?></small></td>
                            <td data-label="Nhân sự"><strong><?= esc($record['employee_name'] ?? '') ?></strong><small><?= esc($record['employee_position'] ?? '') ?></small></td>
                            <td data-label="Nhóm lỗi"><?= esc($categoryLabels[$record['category']] ?? $record['category']) ?><small>Lần <?= (int)($record['category_recurrence_count'] ?? $record['recurrence_count'] ?? 1) ?> trong mục này/tháng</small></td>
                            <td data-label="Giải trình" class="violation-note-cell">
                                <div><?= esc($record['explanation'] ?: $record['behavior']) ?></div>
                                <?php if (!empty($record['hr_note'])) { ?><small>NS: <?= esc($record['hr_note']) ?></small><?php } ?>
                                <?php if (!empty($record['admin_note'])) { ?><small>HC: <?= esc($record['admin_note']) ?></small><?php } ?>
                            </td>
                            <td data-label="Số tiền" class="text-right violation-money"><?= (int)$record['amount'] > 0 ? $money($record['amount']) : '<span class="violation-money-pending">Chưa nhập</span>' ?></td>
                            <td data-label="Trạng thái"><span class="violation-status status-<?= esc($record['status']) ?>"><?= esc($statusLabels[$record['status']] ?? $record['status']) ?></span></td>
                            <td data-label="Người ghi"><?= esc($record['creator_name'] ?? '') ?><small><?= date('d/m/Y H:i', strtotime($record['created_at'])) ?></small></td>
                            <td data-label="Thao tác" class="violation-actions">
                                <?php if ($canCollect) { ?>
                                    <form action="<?= base_url('violation-funds/collect/' . $record['id']) ?>" method="POST" class="violation-collect-form">
                                        <?= csrf_field() ?>
                                        <select name="status" class="form-control-premium">
                                            <?php foreach ($statusLabels as $key => $label) { ?>
                                                <option value="<?= esc($key) ?>" <?= $record['status'] === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                                            <?php } ?>
                                        </select>
                                        <select name="collection_method" class="form-control-premium">
                                            <?php foreach ($collectionMethodLabels as $key => $label) { ?>
                                                <option value="<?= esc($key) ?>" <?= $record['collection_method'] === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                                            <?php } ?>
                                        </select>
                                        <input type="text" name="amount" class="form-control-premium js-money-input" value="<?= (int)$record['amount'] > 0 ? esc(number_format((int)$record['amount'], 0, ',', '.')) : '' ?>" placeholder="Số tiền">
                                        <input type="text" name="admin_note" class="form-control-premium" value="<?= esc($record['admin_note'] ?? '') ?>" placeholder="Note thu">
                                        <button type="submit" class="btn-secondary-sm" title="Lưu trạng thái"><i class="fas fa-save"></i></button>
                                    </form>
                                <?php } ?>
                                <?php if ($canManage) { ?>
                                    <a href="<?= base_url('violation-funds/delete/' . $record['id']) ?>" class="btn-delete-mini js-confirm-delete" title="Xóa"><i class="fas fa-trash"></i></a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">
            <?= $pager->links() ?>
        </div>
    </div>
</div>

<script type="application/json" id="violationChartData"><?= json_encode($chartPayload, JSON_UNESCAPED_UNICODE) ?></script>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/violation_funds.js') ?>?v=<?= time() ?>"></script>
<?= $this->endSection() ?>
