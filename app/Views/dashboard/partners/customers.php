<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/partners.css') ?>?v=<?= time() ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$money = static fn($value) => number_format((int)$value, 0, ',', '.') . 'đ';
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
            <h2 class="content-title">Khách hàng giới thiệu</h2>
            <p class="content-subtitle"><?= esc($partner['name']) ?> theo dõi tình trạng khách, hồ sơ và ghi chú chăm sóc.</p>
        </div>
    </div>

    <div class="partner-panel">
        <div class="partner-panel-title"><i class="fas fa-user-friends"></i> Danh sách khách hàng</div>
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
</div>
<?= $this->endSection() ?>
