<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customer_care.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$segmentCounts = $segmentCounts ?? [
    'vip'       => count($vipCustomers ?? []),
    'regular'   => count($regularCustomers ?? []),
    'potential' => count($potentialCustomers ?? []),
];
$filters = $filters ?? ['q' => '', 'care_status' => '', 'segment' => 'potential', 'per_page' => 12];
$activeSegment = $filters['segment'] ?? 'potential';
$careStatusOptions = [
    ''          => 'Tất cả trạng thái',
    'new'       => 'Mới',
    'phase1'    => 'Giai đoạn 1',
    'phase2'    => 'Giai đoạn 2',
    'phase3'    => 'Giai đoạn 3',
    'completed' => 'Hoàn thành',
    'dormant'   => 'Ngủ đông',
];
?>
<div class="container-fluid py-4 customer-care-shell">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 customer-care-header">
        <div>
            <h1 class="h3 font-weight-700 text-dark mb-1">Phân Nhóm Khách Hàng (A/B/C)</h1>
            <p class="text-muted font-size-0.9">Quản lý và thiết kế các chiến dịch CSKH chuyên biệt cho từng phân nhóm khách hàng.</p>
        </div>
        <a href="<?= base_url('customer-care') ?>" class="btn-secondary d-flex align-items-center gap-2">
            <i class="fas fa-chevron-left"></i> <span>Quay lại Dashboard</span>
        </a>
    </div>

    <form action="<?= base_url('customer-care/customers') ?>" method="get" class="customer-care-filter-bar">
        <input type="hidden" name="segment" id="customerCareSegmentInput" value="<?= esc($filters['segment'] ?? 'potential') ?>">
        <input
            type="search"
            name="q"
            class="form-control"
            value="<?= esc($filters['q'] ?? '') ?>"
            placeholder="Tìm theo tên, mã, SĐT, email"
        >
        <select name="care_status" class="form-control">
            <?php foreach ($careStatusOptions as $value => $label): ?>
                <option value="<?= esc($value) ?>" <?= (($filters['care_status'] ?? '') === $value) ? 'selected' : '' ?>>
                    <?= esc($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="per_page" class="form-control">
            <?php foreach ([12, 24, 48] as $option): ?>
                <option value="<?= $option ?>" <?= ((int) ($filters['per_page'] ?? 12) === $option) ? 'selected' : '' ?>>
                    <?= $option ?>/trang
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-premium">
            <i class="fas fa-filter"></i> <span>Lọc</span>
        </button>
        <a href="<?= base_url('customer-care/customers') ?>" class="btn-secondary">
            <i class="fas fa-times"></i> <span>Xóa lọc</span>
        </a>
    </form>

    <!-- Tabs header -->
    <div class="segment-tabs">
        <button class="segment-tab-btn vip-tab <?= $activeSegment === 'vip' ? 'active' : '' ?>" data-target="vip">
            <i class="fas fa-crown"></i> Nhóm A — VIP (<?= esc($segmentCounts['vip'] ?? 0) ?>)
        </button>
        <button class="segment-tab-btn <?= $activeSegment === 'regular' ? 'active' : '' ?>" data-target="regular">
            <i class="fas fa-user-friends"></i> Nhóm B — Phổ thông (<?= esc($segmentCounts['regular'] ?? 0) ?>)
        </button>
        <button class="segment-tab-btn <?= $activeSegment === 'potential' ? 'active' : '' ?>" data-target="potential">
            <i class="fas fa-snowflake"></i> Nhóm C — Tiềm năng nguội (<?= esc($segmentCounts['potential'] ?? 0) ?>)
        </button>
    </div>

    <!-- Segment contents -->
    <div class="segment-content-wrapper">
        
        <!-- NHÓM A - VIP -->
        <div class="segment-tab-pane" id="pane-vip" style="<?= $activeSegment === 'vip' ? '' : 'display:none;' ?>">
            <?php if (empty($vipCustomers)): ?>
                <div class="text-center py-5 bg-white rounded-lg shadow-sm">
                    <i class="fas fa-crown fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                    <p class="m-0 text-muted font-weight-600">Chưa có khách hàng nào thuộc nhóm VIP (Nhóm A).</p>
                </div>
            <?php else: ?>
                <div class="segment-grid">
                    <?php foreach ($vipCustomers as $c): ?>
                        <div class="customer-premium-card customer-care-item" data-segment="vip">
                            <div>
                                <div class="customer-card-header">
                                    <span class="badge-segment vip">VIP</span>
                                    <span class="customer-card-code"><?= esc($c['code']) ?></span>
                                </div>
                                <h3 class="customer-card-name"><?= esc($c['name']) ?></h3>
                                <p class="text-muted font-size-0.78 mt-1 mb-3">
                                    <i class="fas fa-building"></i> <?= $c['type'] === 'doanh_nghiep' ? 'Doanh nghiệp' : 'Cá nhân' ?>
                                </p>
                                
                                <div class="customer-card-body">
                                    <div class="customer-info-row">
                                        <i class="fas fa-phone-alt"></i>
                                        <span><?= esc($c['phone']) ?></span>
                                    </div>
                                    <div class="customer-info-row">
                                        <i class="fab fa-whatsapp text-success"></i>
                                        <span>Zalo: <?= esc($c['zalo_phone'] ?? $c['phone']) ?></span>
                                    </div>
                                    <div class="customer-info-row">
                                        <i class="fas fa-wallet"></i>
                                        <span class="font-weight-600">Doanh thu: <?= number_format($c['total_revenue'] ?? 0) ?>đ</span>
                                    </div>
                                    <div class="customer-info-row">
                                        <i class="fas fa-chart-line"></i>
                                        <span>Số vụ việc: <?= esc($c['total_cases'] ?? 0) ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-light">
                                <span class="badge-care-status <?= esc($c['care_status']) ?>">
                                    <?= esc($c['care_status'] === 'new' ? 'Mới' : ($c['care_status'] === 'phase1' ? 'GĐ 1' : ($c['care_status'] === 'phase2' ? 'GĐ 2' : ($c['care_status'] === 'phase3' ? 'GĐ 3' : 'Xong')))) ?>
                                </span>
                                <a href="<?= base_url('customer-care/care-plan/' . $c['id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                    Chi tiết CSKH
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($pager)): ?>
                    <div class="customer-care-pagination">
                        <?= $pager->only(['q', 'care_status', 'segment', 'per_page'])->links('vip', 'default_full') ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- NHÓM B - REGULAR -->
        <div class="segment-tab-pane" id="pane-regular" style="<?= $activeSegment === 'regular' ? '' : 'display:none;' ?>">
            <?php if (empty($regularCustomers)): ?>
                <div class="text-center py-5 bg-white rounded-lg shadow-sm">
                    <i class="fas fa-user-friends fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                    <p class="m-0 text-muted font-weight-600">Chưa có khách hàng nào thuộc nhóm Phổ thông (Nhóm B).</p>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle m-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 pl-4">Khách hàng</th>
                                        <th class="border-0">Số điện thoại</th>
                                        <th class="border-0">Trạng thái CSKH</th>
                                        <th class="border-0">Tổng doanh thu</th>
                                        <th class="border-0">Ngày tạo</th>
                                        <th class="border-0 pr-4 text-right">Tác vụ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($regularCustomers as $c): ?>
                                        <tr class="customer-care-item" data-segment="regular">
                                            <td class="pl-4">
                                                <div class="font-weight-600 text-dark"><?= esc($c['name']) ?></div>
                                                <span class="badge-segment regular font-size-0.6"><?= esc($c['code']) ?></span>
                                            </td>
                                            <td><?= esc($c['phone']) ?></td>
                                            <td>
                                                <span class="badge-care-status <?= esc($c['care_status']) ?>">
                                                    <?= esc($c['care_status'] === 'new' ? 'Mới' : ($c['care_status'] === 'phase1' ? 'Giai đoạn 1' : ($c['care_status'] === 'phase2' ? 'Giai đoạn 2' : ($c['care_status'] === 'phase3' ? 'Giai đoạn 3' : 'Hoàn thành')))) ?>
                                                </span>
                                            </td>
                                            <td class="font-weight-600"><?= number_format($c['total_revenue'] ?? 0) ?>đ</td>
                                            <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                                            <td class="pr-4 text-right">
                                                <a href="<?= base_url('customer-care/care-plan/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    Thiết lập CSKH
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php if (!empty($pager)): ?>
                    <div class="customer-care-pagination">
                        <?= $pager->only(['q', 'care_status', 'segment', 'per_page'])->links('regular', 'default_full') ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- NHÓM C - POTENTIAL -->
        <div class="segment-tab-pane" id="pane-potential" style="<?= $activeSegment === 'potential' ? '' : 'display:none;' ?>">
            <?php if (empty($potentialCustomers)): ?>
                <div class="text-center py-5 bg-white rounded-lg shadow-sm">
                    <i class="fas fa-snowflake fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                    <p class="m-0 text-muted font-weight-600">Chưa có khách tiềm năng nguội nào (Nhóm C).</p>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle m-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 pl-4">Khách hàng</th>
                                        <th class="border-0">Nguồn</th>
                                        <th class="border-0">Trạng thái CSKH</th>
                                        <th class="border-0">Ngày tạo</th>
                                        <th class="border-0 pr-4 text-right">Tác vụ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($potentialCustomers as $c): ?>
                                        <tr class="customer-care-item" data-segment="potential">
                                            <td class="pl-4">
                                                <div class="font-weight-600 text-dark"><?= esc($c['name']) ?></div>
                                                <span class="badge-segment potential font-size-0.6"><?= esc($c['code']) ?></span>
                                            </td>
                                            <td class="text-capitalize"><?= esc($c['source'] ?? 'Khác') ?></td>
                                            <td>
                                                <span class="badge-care-status <?= esc($c['care_status']) ?>">
                                                    <?= esc($c['care_status'] === 'new' ? 'Mới' : ($c['care_status'] === 'phase1' ? 'GĐ 1' : ($c['care_status'] === 'phase2' ? 'GĐ 2' : ($c['care_status'] === 'phase3' ? 'GĐ 3' : 'Hoàn thành')))) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                                            <td class="pr-4 text-right">
                                                <a href="<?= base_url('customer-care/care-plan/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    Kích hoạt CSKH
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php if (!empty($pager)): ?>
                    <div class="customer-care-pagination">
                        <?= $pager->only(['q', 'care_status', 'segment', 'per_page'])->links('potential', 'default_full') ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/customer_care.js') ?>"></script>
<?= $this->endSection() ?>
