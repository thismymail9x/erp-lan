<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/case_expenses.css') ?>?v=<?= time() ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="case-expense-page">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Chi phí xử lý vụ việc</h2>
            <p class="content-subtitle">Nhân sự nhập chi phí theo vụ việc được phân công, người duyệt kiểm tra trước khi ghi nhận thanh toán.</p>
        </div>
    </div>

    <?php
    $baseStatFilters = array_filter([
        'search' => $filters['search'] ?? '',
        'month' => $filters['month'] ?? 0,
        'year' => $filters['year'] ?? date('Y'),
        'case_id' => $filters['case_id'] ?? 0,
        'employee_id' => $filters['employee_id'] ?? 0,
    ]);
    $allExpenseUrl = base_url('case-expenses?' . http_build_query($baseStatFilters));
    $approvedExpenseUrl = base_url('case-expenses?' . http_build_query(array_merge($baseStatFilters, ['status' => 'approved'])));
    $pendingExpenseUrl = base_url('case-expenses?' . http_build_query(array_merge($baseStatFilters, ['status' => 'pending'])));
    $selectedScheduleId = (int)($selectedScheduleId ?? 0);
    ?>
    <div class="expense-stats-grid">
        <a href="<?= $allExpenseUrl ?>" class="expense-stat expense-stat-link">
            <span>Đề nghị</span>
            <strong><?= number_format($stats['requested_total'] ?? 0, 0, ',', '.') ?>đ</strong>
        </a>
        <a href="<?= $approvedExpenseUrl ?>" class="expense-stat expense-stat-approved expense-stat-link">
            <span>Đã duyệt</span>
            <strong><?= number_format($stats['approved_total'] ?? 0, 0, ',', '.') ?>đ</strong>
        </a>
        <a href="<?= $pendingExpenseUrl ?>" class="expense-stat expense-stat-pending expense-stat-link">
            <span>Chờ duyệt</span>
            <strong><?= number_format($stats['pending_total'] ?? 0, 0, ',', '.') ?>đ</strong>
        </a>
        <a href="<?= $approvedExpenseUrl ?>" class="expense-stat expense-stat-link">
            <span>Giờ đã duyệt</span>
            <strong><?= number_format($stats['approved_hours'] ?? 0, 2, ',', '.') ?>h</strong>
        </a>
    </div>

    <?php if ($canSubmit) { ?>
        <div class="expense-panel">
            <div class="expense-panel-title">
                <i class="fas fa-plus-circle"></i> Nhập chi phí
            </div>
            <form action="<?= base_url('case-expenses/store') ?>" method="POST" enctype="multipart/form-data" class="expense-form">
                <?= csrf_field() ?>
                <div class="expense-form-grid">
                    <div class="form-group-premium">
                        <label>Vụ việc</label>
                        <select name="case_id" id="caseExpenseCaseId" class="form-control-premium select2-single" data-selected-schedule="<?= esc($selectedScheduleId) ?>" required>
                            <option value="">-- Chọn vụ việc được phân công --</option>
                            <?php $hasPrefillCaseOption = false; ?>
                            <?php foreach ($selectableCases as $caseOption) { ?>
                                <?php if ((int)($filters['case_id'] ?? 0) === (int)$caseOption['id']) { $hasPrefillCaseOption = true; } ?>
                                <option value="<?= $caseOption['id'] ?>" <?= ($filters['case_id'] ?? 0) == $caseOption['id'] ? 'selected' : '' ?>>
                                    <?= esc($caseOption['code'] . ' - ' . $caseOption['title']) ?>
                                </option>
                            <?php } ?>
                            <?php if (!empty($schedulePrefill['case_id']) && !$hasPrefillCaseOption) { ?>
                                <option value="<?= esc($schedulePrefill['case_id']) ?>" selected><?= esc($schedulePrefill['case_code'] . ' - ' . $schedulePrefill['case_title']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label>Lịch công tác liên quan</label>
                        <select name="work_schedule_id" id="caseExpenseScheduleId" class="form-control-premium">
                            <option value="">-- Không gắn lịch --</option>
                            <?php foreach (($scheduleOptions ?? []) as $scheduleOption) { ?>
                                <option
                                    value="<?= esc($scheduleOption['id']) ?>"
                                    data-expense-date="<?= esc($scheduleOption['expense_date']) ?>"
                                    data-start-at="<?= esc(date('Y-m-d\TH:i', strtotime($scheduleOption['start_at']))) ?>"
                                    data-end-at="<?= esc(date('Y-m-d\TH:i', strtotime($scheduleOption['end_at']))) ?>"
                                    data-hours="<?= esc($scheduleOption['actual_hours']) ?>"
                                    <?= $selectedScheduleId === (int)$scheduleOption['id'] ? 'selected' : '' ?>
                                >
                                    <?= esc($scheduleOption['label']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label>Ngày chi</label>
                        <input type="date" name="expense_date" id="caseExpenseDate" class="form-control-premium" value="<?= esc($schedulePrefill['expense_date'] ?? date('Y-m-d')) ?>" required>
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
                        <input type="text" name="amount" class="form-control-premium js-money-input" placeholder="VD: 250000" required>
                    </div>
                    <div class="form-group-premium">
                        <label>Bắt đầu thực tế</label>
                        <input type="datetime-local" name="actual_start_at" id="caseExpenseStartAt" class="form-control-premium" value="<?= !empty($schedulePrefill['start_at']) ? esc(date('Y-m-d\TH:i', strtotime($schedulePrefill['start_at']))) : '' ?>">
                    </div>
                    <div class="form-group-premium">
                        <label>Kết thúc thực tế</label>
                        <input type="datetime-local" name="actual_end_at" id="caseExpenseEndAt" class="form-control-premium" value="<?= !empty($schedulePrefill['end_at']) ? esc(date('Y-m-d\TH:i', strtotime($schedulePrefill['end_at']))) : '' ?>">
                    </div>
                    <div class="form-group-premium">
                        <label>Số giờ nếu nhập tay</label>
                        <input type="number" step="0.25" min="0" name="actual_hours" id="caseExpenseHours" class="form-control-premium" value="<?= esc($schedulePrefill['actual_hours'] ?? '') ?>" placeholder="VD: 2.5">
                    </div>
                    <div class="form-group-premium">
                        <label>Chứng từ</label>
                        <input type="file" name="attachments[]" class="form-control-premium" multiple accept="image/*,.pdf">
                    </div>
                    <div class="form-group-premium expense-note-field">
                        <label>Ghi chú</label>
                        <textarea name="note" class="form-control-premium" placeholder="Nội dung chi, tuyến đi, lý do phát sinh..."></textarea>
                    </div>
                </div>
                <div class="expense-form-actions">
                    <button type="submit" class="btn-premium-sm">
                        <i class="fas fa-paper-plane"></i> Gửi duyệt
                    </button>
                </div>
            </form>
        </div>
    <?php } ?>

    <div class="expense-panel">
        <form method="GET" action="<?= base_url('case-expenses') ?>" class="expense-filter" id="case-expense-filter-form" data-default-url="<?= base_url('case-expenses') ?>">
            <input type="text" name="search" class="form-control-premium" placeholder="Tìm vụ việc, khách hàng, nhân sự..." value="<?= esc($filters['search'] ?? '') ?>">
            <select name="status" class="form-control-premium js-expense-filter-select">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($statusLabels as $key => $label) { ?>
                    <option value="<?= esc($key) ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php } ?>
            </select>
            <select name="month" class="form-control-premium js-expense-filter-select">
                <option value="">Tất cả tháng</option>
                <?php for ($m = 1; $m <= 12; $m++) { ?>
                    <option value="<?= $m ?>" <?= ($filters['month'] ?? 0) == $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                <?php } ?>
            </select>
            <input type="number" name="year" class="form-control-premium" value="<?= esc($filters['year'] ?? date('Y')) ?>" min="2024" max="<?= date('Y') + 1 ?>">
            <a href="<?= base_url('case-expenses') ?>" class="btn-secondary-sm js-expense-filter-reset"><i class="fas fa-undo"></i> Reset</a>
        </form>

        <div class="expense-table-wrap">
            <table class="premium-table expense-table">
                <colgroup>
                    <col class="expense-col-date">
                    <col class="expense-col-case">
                    <col class="expense-col-person">
                    <col class="expense-col-category">
                    <col class="expense-col-money">
                    <col class="expense-col-approved">
                    <col class="expense-col-hours">
                    <col class="expense-col-status">
                    <col class="expense-col-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Vụ việc</th>
                        <th>Nhân sự</th>
                        <th>Loại</th>
                        <th class="text-right">Đề nghị</th>
                        <th class="text-right">Duyệt</th>
                        <th>Giờ</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)) { ?>
                        <tr><td colspan="9" class="text-center p-20 text-muted-dark">Chưa có dữ liệu chi phí.</td></tr>
                    <?php } ?>
                    <?php foreach ($expenses as $expense) { ?>
                        <tr>
                            <td data-label="Ngay" class="expense-date-cell"><?= date('d/m/Y', strtotime($expense['expense_date'])) ?></td>
                            <td data-label="Vu viec" class="expense-case-cell">
                                <a href="<?= base_url('cases/show/' . $expense['case_id']) ?>" class="expense-case-link">
                                    <?= esc($expense['case_code']) ?>
                                </a>
                                <div class="expense-muted-line"><?= esc($expense['case_title']) ?></div>
                                <?php if (!empty($expense['note'])) { ?>
                                    <div class="expense-note-line">
                                        <i class="fas fa-sticky-note"></i>
                                        <span><?= esc($expense['note']) ?></span>
                                    </div>
                                <?php } ?>
                                <?php if (!empty($expense['approval_note'])) { ?>
                                    <div class="expense-note-line expense-approval-note-line">
                                        <i class="fas fa-clipboard-check"></i>
                                        <span><?= esc($expense['approval_note']) ?></span>
                                    </div>
                                <?php } ?>
                            </td>
                            <td data-label="Nhan su" class="expense-person-cell"><?= esc($expense['employee_name']) ?></td>
                            <td data-label="Loai" class="expense-category-cell"><?= esc($categoryLabels[$expense['category']] ?? $expense['category']) ?></td>
                            <td data-label="De nghi" class="text-right expense-money-cell"><?= number_format($expense['amount'], 0, ',', '.') ?>đ</td>
                            <td data-label="Duyet" class="text-right expense-money-cell"><?= $expense['approved_amount'] !== null ? number_format($expense['approved_amount'], 0, ',', '.') . 'đ' : '-' ?></td>
                            <td data-label="Gio" class="expense-hours-cell"><?= number_format(abs((float)$expense['actual_hours']), 2, ',', '.') ?>h</td>
                            <td data-label="Trang thai" class="expense-status-cell"><span class="expense-status expense-status-<?= esc($expense['status']) ?>"><?= esc($statusLabels[$expense['status']] ?? $expense['status']) ?></span></td>
                            <td data-label="Thao tac" class="expense-actions-cell">
                                <?php if ($canApprove) { ?>
                                    <?php if ($expense['status'] === 'pending') { ?>
                                    <form action="<?= base_url('case-expenses/approve/' . $expense['id']) ?>" method="POST" class="expense-approval-form">
                                        <?= csrf_field() ?>
                                        <input type="text" name="approved_amount" class="form-control-premium js-money-input" value="<?= esc($expense['amount']) ?>">
                                        <input type="text" name="approval_note" class="form-control-premium" placeholder="Ghi chú">
                                        <button type="submit" name="status" value="approved" class="btn-approve-mini" title="Duyệt"><i class="fas fa-check"></i></button>
                                        <button type="submit" name="status" value="rejected" class="btn-reject-mini" title="Từ chối"><i class="fas fa-times"></i></button>
                                    </form>
                                    <?php } ?>
                                    <button type="button" class="expense-edit-toggle" data-edit-target="expense-edit-<?= esc($expense['id']) ?>" aria-expanded="false">
                                        <i class="fas fa-pen"></i>
                                        <span>Sửa</span>
                                    </button>
                                <?php } else { ?>
                                    <span class="text-xs text-muted-dark"><?= esc($expense['approver_name'] ?? '') ?></span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php if ($canApprove) { ?>
                            <tr class="expense-edit-row" id="expense-edit-<?= esc($expense['id']) ?>" hidden>
                                <td colspan="9" class="expense-edit-row-cell">
                                    <div class="expense-edit-shell">
                                        <div class="expense-edit-shell-header">
                                            <div>
                                                <strong>Sửa chi phí xử lý</strong>
                                                <span><?= esc($expense['case_code'] . ' - ' . $expense['employee_name']) ?></span>
                                            </div>
                                            <button type="button" class="expense-edit-close" data-edit-target="expense-edit-<?= esc($expense['id']) ?>" aria-label="Đóng form sửa">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <form action="<?= base_url('case-expenses/update/' . $expense['id']) ?>" method="POST" class="expense-edit-form">
                                            <?= csrf_field() ?>
                                            <div class="form-group-premium expense-edit-span-4">
                                                <label>Vụ việc</label>
                                                <select name="case_id" class="form-control-premium">
                                                    <?php $hasCurrentCaseOption = false; ?>
                                                    <?php foreach ($selectableCases as $caseOption) { ?>
                                                        <?php if ((int)$expense['case_id'] === (int)$caseOption['id']) { $hasCurrentCaseOption = true; } ?>
                                                        <option value="<?= $caseOption['id'] ?>" <?= (int)$expense['case_id'] === (int)$caseOption['id'] ? 'selected' : '' ?>>
                                                            <?= esc($caseOption['code'] . ' - ' . $caseOption['title']) ?>
                                                        </option>
                                                    <?php } ?>
                                                    <?php if (!$hasCurrentCaseOption) { ?>
                                                        <option value="<?= esc($expense['case_id']) ?>" selected><?= esc($expense['case_code'] . ' - ' . $expense['case_title']) ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-group-premium">
                                                <label>Ngày chi</label>
                                                <input type="date" name="expense_date" class="form-control-premium" value="<?= esc($expense['expense_date']) ?>" required>
                                            </div>
                                            <div class="form-group-premium">
                                                <label>Loại chi phí</label>
                                                <select name="category" class="form-control-premium">
                                                    <?php foreach ($categoryLabels as $key => $label) { ?>
                                                        <option value="<?= esc($key) ?>" <?= $expense['category'] === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-group-premium">
                                                <label>Số tiền đề nghị</label>
                                                <input type="text" name="amount" class="form-control-premium js-money-input" value="<?= esc($expense['amount']) ?>" required>
                                            </div>
                                            <div class="form-group-premium">
                                                <label>Số tiền duyệt</label>
                                                <input type="text" name="approved_amount" class="form-control-premium js-money-input" value="<?= esc($expense['approved_amount'] ?? '') ?>">
                                            </div>
                                            <div class="form-group-premium">
                                                <label>Bắt đầu thực tế</label>
                                                <input type="datetime-local" name="actual_start_at" class="form-control-premium" value="<?= !empty($expense['actual_start_at']) ? date('Y-m-d\TH:i', strtotime($expense['actual_start_at'])) : '' ?>">
                                            </div>
                                            <div class="form-group-premium">
                                                <label>Kết thúc thực tế</label>
                                                <input type="datetime-local" name="actual_end_at" class="form-control-premium" value="<?= !empty($expense['actual_end_at']) ? date('Y-m-d\TH:i', strtotime($expense['actual_end_at'])) : '' ?>">
                                            </div>
                                            <div class="form-group-premium">
                                                <label>Số giờ</label>
                                                <input type="number" step="0.25" min="0" name="actual_hours" class="form-control-premium" value="<?= esc(abs((float)$expense['actual_hours'])) ?>">
                                            </div>
                                            <div class="form-group-premium expense-edit-span-4">
                                                <label>Ghi chú</label>
                                                <textarea name="note" class="form-control-premium"><?= esc($expense['note'] ?? '') ?></textarea>
                                            </div>
                                            <div class="expense-edit-actions">
                                                <button type="button" class="btn-secondary-sm expense-edit-cancel" data-edit-target="expense-edit-<?= esc($expense['id']) ?>">Hủy</button>
                                                <button type="submit" class="btn-premium-sm"><i class="fas fa-save"></i> Lưu thay đổi</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="expense-list-summary">
            <div>
                <span>Tổng số dòng theo bộ lọc</span>
                <strong><?= number_format((int)($stats['total_count'] ?? 0), 0, ',', '.') ?></strong>
            </div>
            <div>
                <span>Tổng đề nghị</span>
                <strong><?= number_format((int)($stats['requested_total'] ?? 0), 0, ',', '.') ?>đ</strong>
            </div>
            <div>
                <span>Tổng đã duyệt</span>
                <strong><?= number_format((int)($stats['approved_total'] ?? 0), 0, ',', '.') ?>đ</strong>
            </div>
            <div>
                <span>Tổng giờ</span>
                <strong><?= number_format((float)($stats['total_hours'] ?? 0), 2, ',', '.') ?>h</strong>
            </div>
        </div>

        <div class="pagination-wrapper">
            <?= $pager->links() ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/case_expenses.js') ?>?v=<?= time() ?>"></script>
<?= $this->endSection() ?>
