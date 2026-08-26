<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/partners.css') ?>?v=<?= time() ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$money = static fn($value) => number_format((int)$value, 0, ',', '.') . 'đ';
$paymentStatusLabels = [
    'paid' => 'Đã thu',
    'pending' => 'Chưa thu',
    'overdue' => 'Quá hạn',
];
$careStatusLabels = [
    'new' => 'Mới',
    'chua_tu_van' => 'Chưa tư vấn',
    'dang_tu_van' => 'Đang tư vấn',
    'da_tu_van' => 'Đã tư vấn',
    'khong_chot' => 'Không chốt',
    'da_chot' => 'Đã chốt',
    'phase1' => 'Giai đoạn 1',
    'phase2' => 'Giai đoạn 2',
    'phase3' => 'Giai đoạn 3',
    'completed' => 'Hoàn thành',
    'dormant' => 'Tạm ngưng',
];
?>
<div class="partner-page">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Cổng đối tác</h2>
            <p class="content-subtitle"><?= esc($partner['name']) ?> theo dõi doanh thu, tiến độ thanh toán của khách và tình trạng hồ sơ.</p>
        </div>
    </div>

    <div class="partner-stats-grid">
        <div class="partner-stat"><span>Tổng dự kiến</span><strong><?= $money($stats['expected_total'] ?? 0) ?></strong></div>
        <div class="partner-stat"><span>Đã nhận</span><strong><?= $money($stats['paid'] ?? 0) ?></strong></div>
        <div class="partner-stat"><span>Có thể yêu cầu</span><strong><?= $money($stats['accrued'] ?? 0) ?></strong></div>
        <div class="partner-stat"><span>Đã gửi yêu cầu</span><strong><?= $money($stats['requested'] ?? 0) ?></strong></div>
        <div class="partner-stat"><span>Đã duyệt</span><strong><?= $money($stats['approved'] ?? 0) ?></strong></div>
        <div class="partner-stat"><span>Dự kiến tương lai</span><strong><?= $money($stats['future_estimated'] ?? 0) ?></strong></div>
    </div>

    <div class="partner-panel">
        <div class="partner-panel-title"><i class="fas fa-user-friends"></i> Khách hàng đối tác giới thiệu</div>
        <?php if (empty($referredCustomers)) { ?>
            <div class="partner-muted">Chưa có khách hàng nào được gán cho đối tác này.</div>
        <?php } else { ?>
            <div class="partner-referral-list">
                <?php foreach ($referredCustomers as $customer) { ?>
                    <article class="partner-referral-card">
                        <div class="partner-referral-head">
                            <div>
                                <span class="badge-secondary-minimal"><?= esc($customer['code'] ?: ('KH-' . $customer['id'])) ?></span>
                                <strong><?= esc($customer['name']) ?></strong>
                                <div class="partner-muted">
                                    <?= esc($customer['phone'] ?: '-') ?><?= !empty($customer['email']) ? ' · ' . esc($customer['email']) : '' ?>
                                </div>
                            </div>
                            <span class="partner-status partner-status-active"><?= isset($careStatusLabels[$customer['care_status'] ?? '']) ? $careStatusLabels[$customer['care_status']] : esc($customer['care_status'] ?: '-') ?></span>
                        </div>
                        <div class="partner-referral-meta">
                            <span>Nguồn: <?= esc($customer['source'] ?: '-') ?></span>
                            <span>Nhóm: <?= esc($customer['customer_segment'] ?: '-') ?></span>
                            <span>Phụ trách: <?= esc($customer['care_staff_name'] ?: '-') ?></span>
                        </div>
                        <?php if (!empty($customer['notes_internal'])) { ?>
                            <div class="partner-referral-note">
                                <strong>Note khách:</strong> <?= nl2br(esc($customer['notes_internal'])) ?>
                            </div>
                        <?php } ?>

                        <div class="partner-referral-columns">
                            <div>
                                <div class="partner-referral-title">Hồ sơ / vụ việc</div>
                                <?php if (empty($customer['cases'])) { ?>
                                    <div class="partner-muted">Chưa phát sinh hồ sơ.</div>
                                <?php } ?>
                                <?php foreach (($customer['cases'] ?? []) as $case) { ?>
                                    <div class="partner-referral-item">
                                        <strong><?= esc($case['code'] . ' - ' . $case['title']) ?></strong>
                                        <div class="partner-muted">
                                            <?= esc($case['status_label'] ?: '-') ?>
                                            <?= !empty($case['deadline']) ? ' · Hạn: ' . date('d/m/Y', strtotime($case['deadline'])) : '' ?>
                                            <?= !empty($case['contract_value']) ? ' · HĐ: ' . $money($case['contract_value']) : '' ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                            <div>
                                <div class="partner-referral-title">Trao đổi / lý do</div>
                                <?php if (empty($customer['interactions'])) { ?>
                                    <div class="partner-muted">Chưa có ghi nhận trao đổi.</div>
                                <?php } ?>
                                <?php foreach (($customer['interactions'] ?? []) as $interaction) { ?>
                                    <div class="partner-referral-item">
                                        <strong><?= !empty($interaction['interaction_date']) ? date('d/m/Y H:i', strtotime($interaction['interaction_date'])) : '-' ?> · <?= esc($interaction['channel'] ?: '-') ?></strong>
                                        <div><?= esc($interaction['summary'] ?: $interaction['interaction_result'] ?: '-') ?></div>
                                        <?php if (!empty($interaction['detailed_content'])) { ?>
                                            <div class="partner-muted"><?= esc($interaction['detailed_content']) ?></div>
                                        <?php } ?>
                                        <?php if (!empty($interaction['next_follow_up'])) { ?>
                                            <div class="partner-muted">Hẹn lại: <?= date('d/m/Y', strtotime($interaction['next_follow_up'])) ?></div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </article>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <div class="partner-panel">
        <div class="partner-panel-title"><i class="fas fa-handshake"></i> Vụ việc đang hợp tác</div>
        <div class="partner-agreement-grid">
            <?php if (empty($agreements)) { ?>
                <div class="partner-muted">Chưa có vụ việc được gắn với tài khoản đối tác này.</div>
            <?php } ?>
            <?php foreach ($agreements as $agreement) { ?>
                <div class="partner-agreement-card">
                    <strong><?= esc($agreement['case_code'] . ' - ' . $agreement['case_title']) ?></strong>
                    <div class="partner-muted"><?= esc($agreement['customer_name']) ?></div>
                    <div class="partner-muted">
                        Trạng thái: <?= esc(\Config\AppConstants::CASE_STATUS_LABELS[$agreement['case_status']] ?? $agreement['case_status'] ?? '-') ?>
                        <?= !empty($agreement['case_deadline']) ? ' · Hạn: ' . date('d/m/Y', strtotime($agreement['case_deadline'])) : '' ?>
                    </div>
                    <div class="partner-muted"><?= esc($roleLabels[$agreement['role_label']] ?? $agreement['role_label']) ?> · <?= esc($baseLabels[$agreement['calculation_base']] ?? $agreement['calculation_base']) ?></div>
                    <div class="partner-amount"><?= number_format((float)$agreement['percentage'], 2, ',', '.') ?>% + <?= $money($agreement['fixed_amount']) ?></div>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="partner-panel">
        <div class="partner-panel-title"><i class="fas fa-chart-line"></i> Tiến độ thanh toán khách hàng</div>
        <?php if (empty($paymentPlans)) { ?>
            <div class="partner-muted">Chưa có tiến độ thanh toán cho các vụ việc đang hợp tác.</div>
        <?php } else { ?>
            <div class="partner-table-wrap">
                <table class="partner-table partner-payment-table">
                    <thead>
                        <tr>
                            <th>Mã số</th>
                            <th>Vụ việc / Khách hàng</th>
                            <th>Trạng thái hồ sơ</th>
                            <th>Giá trị HĐ</th>
                            <th>Đợt thanh toán</th>
                            <th>Hạn thanh toán</th>
                            <th>Tình trạng thu</th>
                            <th>Xuất VAT & Ghi chú</th>
                            <th>Hoa hồng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($paymentPlans ?? []) as $plan) { ?>
                            <?php
                            $payments = $plan['payments'] ?? [];
                            $rowCount = max(1, count($payments));
                            $summaryHtml = '<div class="partner-muted">Đã thu: ' . $money($plan['paid_amount']) . '</div>'
                                . '<div class="partner-muted">Chưa thu: ' . $money($plan['pending_amount']) . '</div>';
                            ?>
                            <tr>
                                <td rowspan="<?= $rowCount ?>" data-label="Mã số"><span class="badge-secondary-minimal"><?= esc($plan['case_code']) ?></span></td>
                                <td rowspan="<?= $rowCount ?>" data-label="Vụ việc">
                                    <strong><?= esc($plan['case_title']) ?></strong>
                                    <div class="partner-muted"><?= esc($plan['customer_name'] ?: '-') ?></div>
                                </td>
                                <td rowspan="<?= $rowCount ?>" data-label="Hồ sơ">
                                    <span class="partner-status partner-status-active"><?= esc($plan['case_status_label'] ?: '-') ?></span>
                                    <?= !empty($plan['case_deadline']) ? '<div class="partner-muted">Hạn: ' . date('d/m/Y', strtotime($plan['case_deadline'])) . '</div>' : '' ?>
                                </td>
                                <td rowspan="<?= $rowCount ?>" data-label="Giá trị HĐ">
                                    <span class="partner-amount"><?= !empty($plan['contract_value']) ? $money($plan['contract_value']) : 'Chưa chốt' ?></span>
                                    <?= $summaryHtml ?>
                                </td>
                                <?php if (empty($payments)) { ?>
                                    <td colspan="5" class="text-center">Chưa có đợt thanh toán.</td>
                                <?php } else { ?>
                                    <?php $payment = $payments[0]; ?>
                                    <td data-label="Đợt">
                                        <strong><?= esc($payment['title']) ?></strong>
                                        <div class="partner-muted">Số tiền: <?= $money($payment['amount']) ?></div>
                                    </td>
                                    <td data-label="Hạn">
                                        <?= !empty($payment['deadline']) ? date('d/m/Y', strtotime($payment['deadline'])) : '--' ?>
                                        <?php if ($payment['status'] === 'overdue') { ?><i class="fas fa-exclamation-triangle text-apple-red" title="Đã trễ hạn"></i><?php } ?>
                                        <?php if (!empty($payment['paid_at'])) { ?><div class="partner-muted">Thu: <?= date('d/m/Y', strtotime($payment['paid_at'])) ?></div><?php } ?>
                                    </td>
                                    <td data-label="Tình trạng">
                                        <span class="partner-status partner-status-<?= esc($payment['status']) ?>"><?= $paymentStatusLabels[$payment['status']] ?? esc($payment['status']) ?></span>
                                    </td>
                                    <td data-label="VAT / ghi chú">
                                        <?= !empty($payment['is_vat'])
                                            ? '<span class="badge-success-minimal text-xs"><i class="fas fa-file-invoice-dollar"></i> Đã xuất VAT</span>'
                                            : '<span class="badge-warning-minimal text-xs"><i class="fas fa-file-invoice"></i> Chưa xuất VAT</span>' ?>
                                        <?= !empty($payment['note']) ? '<div class="partner-muted">' . esc($payment['note']) . '</div>' : '' ?>
                                    </td>
                                    <td data-label="Hoa hồng"><span class="partner-amount"><?= $money($payment['commission_amount']) ?></span></td>
                                <?php } ?>
                            </tr>
                            <?php for ($i = 1; $i < $rowCount; $i++) { ?>
                                <?php $payment = $payments[$i]; ?>
                                <tr>
                                    <td data-label="Đợt">
                                        <strong><?= esc($payment['title']) ?></strong>
                                        <div class="partner-muted">Số tiền: <?= $money($payment['amount']) ?></div>
                                    </td>
                                    <td data-label="Hạn">
                                        <?= !empty($payment['deadline']) ? date('d/m/Y', strtotime($payment['deadline'])) : '--' ?>
                                        <?php if ($payment['status'] === 'overdue') { ?><i class="fas fa-exclamation-triangle text-apple-red" title="Đã trễ hạn"></i><?php } ?>
                                        <?php if (!empty($payment['paid_at'])) { ?><div class="partner-muted">Thu: <?= date('d/m/Y', strtotime($payment['paid_at'])) ?></div><?php } ?>
                                    </td>
                                    <td data-label="Tình trạng">
                                        <span class="partner-status partner-status-<?= esc($payment['status']) ?>"><?= $paymentStatusLabels[$payment['status']] ?? esc($payment['status']) ?></span>
                                    </td>
                                    <td data-label="VAT / ghi chú">
                                        <?= !empty($payment['is_vat'])
                                            ? '<span class="badge-success-minimal text-xs"><i class="fas fa-file-invoice-dollar"></i> Đã xuất VAT</span>'
                                            : '<span class="badge-warning-minimal text-xs"><i class="fas fa-file-invoice"></i> Chưa xuất VAT</span>' ?>
                                        <?= !empty($payment['note']) ? '<div class="partner-muted">' . esc($payment['note']) . '</div>' : '' ?>
                                    </td>
                                    <td data-label="Hoa hồng"><span class="partner-amount"><?= $money($payment['commission_amount']) ?></span></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>

    <div class="partner-panel">
        <div class="partner-panel-title"><i class="fas fa-wallet"></i> Doanh thu được nhận</div>
        <form method="GET" action="<?= base_url('partner-portal') ?>" class="partner-filter">
            <select name="status" class="form-control-premium">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($entryStatusLabels as $key => $label) { ?>
                    <option value="<?= esc($key) ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php } ?>
            </select>
            <button type="submit" class="btn-secondary-sm"><i class="fas fa-filter"></i> Lọc</button>
            <a href="<?= base_url('partner-portal') ?>" class="btn-secondary-sm"><i class="fas fa-undo"></i> Reset</a>
        </form>

        <div class="partner-table-wrap">
            <table class="partner-table">
                <thead>
                    <tr>
                        <th>Vụ việc / khách</th>
                        <th>Đợt khách thanh toán</th>
                        <th>Công thức</th>
                        <th>Số tiền</th>
                        <th>Trạng thái</th>
                        <th>Yêu cầu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)) { ?>
                        <tr><td colspan="6" class="text-center">Chưa có khoản hoa hồng phát sinh.</td></tr>
                    <?php } ?>
                    <?php foreach ($entries as $entry) { ?>
                        <tr>
                            <td data-label="Vụ việc"><strong><?= esc($entry['case_code'] . ' - ' . $entry['case_title']) ?></strong><div class="partner-muted"><?= esc($entry['customer_name']) ?></div></td>
                            <td data-label="Đợt thu"><?= esc($entry['payment_title']) ?><div class="partner-muted"><?= !empty($entry['payment_date']) ? date('d/m/Y', strtotime($entry['payment_date'])) : '-' ?></div></td>
                            <td data-label="Công thức"><?= esc($baseLabels[$entry['calculation_base']] ?? $entry['calculation_base']) ?><div class="partner-muted"><?= number_format((float)$entry['percentage'], 2, ',', '.') ?>% + <?= $money($entry['fixed_amount']) ?></div></td>
                            <td data-label="Số tiền"><span class="partner-amount"><?= $money($entry['commission_amount']) ?></span></td>
                            <td data-label="Trạng thái"><span class="partner-status partner-status-<?= esc($entry['status']) ?>"><?= esc($entryStatusLabels[$entry['status']] ?? $entry['status']) ?></span></td>
                            <td data-label="Yêu cầu">
                                <?php if ($entry['status'] === 'accrued') { ?>
                                    <form action="<?= base_url('partner-portal/request-payment/' . $entry['id']) ?>" method="POST" class="partner-mini-form">
                                        <?= csrf_field() ?>
                                        <textarea name="request_note" class="form-control-premium" placeholder="Ghi chú yêu cầu thanh toán"></textarea>
                                        <button type="submit" class="btn-premium-sm"><i class="fas fa-paper-plane"></i> Gửi</button>
                                    </form>
                                <?php } else { ?>
                                    <span class="partner-muted"><?= esc($entry['request_note'] ?? '') ?></span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper"><?= $pager->links() ?></div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/partners.js') ?>?v=<?= time() ?>"></script>
<?= $this->endSection() ?>
