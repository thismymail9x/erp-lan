<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customer_care.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?= base_url('customer-care/care-plan/' . $customer['id']) ?>" class="text-muted font-size-0.85 text-decoration-none">
                    Kế hoạch chăm sóc
                </a>
                <span class="text-muted font-size-0.75"><i class="fas fa-chevron-right"></i></span>
                <span class="text-dark font-size-0.85 font-weight-600">Thẻ VIP & Loyalty</span>
            </div>
            <h1 class="h3 font-weight-700 text-dark mb-0">Hạng Thành Viên & Thẻ VIP</h1>
        </div>
        <a href="<?= base_url('customer-care/care-plan/' . $customer['id']) ?>" class="btn btn-secondary d-flex align-items-center gap-2">
            <i class="fas fa-chevron-left"></i> <span>Quay lại Kế hoạch</span>
        </a>
    </div>

    <!-- Main Grid -->
    <div class="loyalty-grid">
        
        <!-- CỘT TRÁI: THẺ VIP & ĐIỂM TÍCH LŨY -->
        <div>
            <!-- Visual VIP Card -->
            <div class="vip-card-wrapper">
                <?php
                    $tierClass = '';
                    $tierLabel = 'Standard';
                    if ($loyalty['loyalty_tier'] === 'silver') {
                        $tierClass = 'card-silver';
                        $tierLabel = 'Silver';
                    } elseif ($loyalty['loyalty_tier'] === 'gold') {
                        $tierClass = 'card-gold';
                        $tierLabel = 'Gold';
                    } elseif ($loyalty['loyalty_tier'] === 'vip') {
                        $tierClass = 'card-vip';
                        $tierLabel = 'VIP';
                    }
                    
                    // Định dạng số thẻ từ Referral Code
                    $rawCode = $loyalty['referral_code'] ?? 'REF00000';
                    $formattedCardNum = wordwrap($rawCode, 4, ' ', true);
                ?>
                <div class="vip-card-visual <?= $tierClass ?>">
                    <div class="vip-card-header">
                        <span class="vip-card-logo">L.A.N ERP</span>
                        <span class="vip-card-tier"><?= esc($tierLabel) ?></span>
                    </div>
                    <div class="vip-card-chip"></div>
                    <div class="vip-card-body">
                        <div class="vip-card-number"><?= esc($formattedCardNum) ?></div>
                    </div>
                    <div class="vip-card-footer">
                        <div class="vip-card-holder">
                            CHỦ THẺ
                            <span><?= esc($customer['name']) ?></span>
                        </div>
                        <div class="vip-card-points">
                            ĐIỂM TÍCH LŨY
                            <span><?= number_format($loyalty['points'] ?? 0) ?> PTS</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loyalty Points & Tier Progress -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h5 class="card-title font-weight-700 mb-3">Tiến Trình Thăng Hạng</h5>
                    
                    <?php
                        $points = (int)($loyalty['points'] ?? 0);
                        $currentTier = $loyalty['loyalty_tier'] ?? 'standard';
                        
                        $nextTier = 'silver';
                        $targetPoints = 200;
                        $progressPercent = 0;
                        
                        if ($currentTier === 'standard') {
                            $nextTier = 'Bạc (Silver)';
                            $targetPoints = 200;
                            $progressPercent = min(100, round(($points / 200) * 100));
                            $pointsNeeded = 200 - $points;
                        } elseif ($currentTier === 'silver') {
                            $nextTier = 'Vàng (Gold)';
                            $targetPoints = 500;
                            $progressPercent = min(100, round((($points - 200) / 300) * 100));
                            $pointsNeeded = 500 - $points;
                        } elseif ($currentTier === 'gold') {
                            $nextTier = 'Văn phòng VIP (VIP)';
                            $targetPoints = 1000;
                            $progressPercent = min(100, round((($points - 500) / 500) * 100));
                            $pointsNeeded = 1000 - $points;
                        } else {
                            $nextTier = 'Tối Đa (Max)';
                            $targetPoints = 1000;
                            $progressPercent = 100;
                            $pointsNeeded = 0;
                        }
                    ?>
                    
                    <div class="loyalty-progress-container">
                        <div class="d-flex justify-content-between font-size-0.8 font-weight-600 mb-1">
                            <span class="text-secondary">Tích lũy hiện tại:</span>
                            <span class="text-dark font-weight-700"><?= number_format($points) ?> / <?= number_format($targetPoints) ?> PTS</span>
                        </div>
                        
                        <div class="progress-bar-premium">
                            <div class="progress-fill-premium" style="width: <?= $progressPercent ?>%;"></div>
                        </div>
                        
                        <div class="font-size-0.78 text-muted mt-2">
                            <?php if ($pointsNeeded > 0): ?>
                                Cần tích lũy thêm <strong class="text-dark"><?= number_format($pointsNeeded) ?> điểm</strong> hoặc tăng doanh thu khách hàng để nâng hạng lên <strong class="text-dark"><?= esc($nextTier) ?></strong>.
                            <?php else: ?>
                                <i class="fas fa-check-circle text-success"></i> Bạn đã đạt hạng thành viên cao nhất của hệ thống!
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Referral Code Box -->
            <div class="referral-box shadow-sm mb-4">
                <h5 class="font-weight-700 mb-2" style="color: #ffffff;">Mã Giới Thiệu Khách Hàng</h5>
                <p class="font-size-0.8 mb-4 text-white-50">Chia sẻ mã giới thiệu này cho đối tác hoặc khách hàng mới. Người giới thiệu sẽ nhận ngay 100 điểm thưởng khi khách hàng mới ký hợp đồng thành công.</p>
                
                <div class="referral-code-display mb-3">
                    <?= esc($loyalty['referral_code']) ?>
                </div>
                
                <button class="btn btn-primary btn-block btn-copy-referral rounded-pill py-2.5 font-weight-600 d-flex align-items-center justify-content-center gap-2" data-code="<?= esc($loyalty['referral_code']) ?>">
                    <i class="far fa-copy"></i> Sao chép mã giới thiệu
                </button>
            </div>

        </div>

        <!-- CỘT PHẢI: QUYỀN LỢI & LỊCH SỬ GIỚI THIỆU -->
        <div class="d-flex flex-column gap-4">
            
            <!-- Đặc quyền hạng thẻ -->
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h5 class="card-title font-weight-700 mb-3 d-flex align-items-center gap-2 text-dark">
                        <i class="fas fa-gem text-warning"></i> Đặc Quyền Hạng Thành Viên
                    </h5>
                    <p class="text-muted font-size-0.85 mb-4">Các ưu đãi đặc biệt áp dụng trực tiếp cho các dịch vụ pháp lý tại L.A.N Office.</p>
                    
                    <?php
                        $benefits = json_decode($loyalty['benefits'] ?? '[]', true);
                        if (empty($benefits)) {
                            $benefits = ['Ưu tiên hỗ trợ qua tổng đài hotline', 'Nhận tài liệu cập nhật quy định pháp luật miễn phí'];
                        }
                    ?>
                    
                    <div class="row">
                        <?php foreach ($benefits as $b): ?>
                            <div class="col-md-6">
                                <div class="benefit-item">
                                    <i class="fas fa-check-circle benefit-icon"></i>
                                    <span class="font-size-0.85 font-weight-600 text-secondary"><?= esc($b) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Lịch sử khách hàng giới thiệu -->
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title font-weight-700 m-0 d-flex align-items-center gap-2 text-dark">
                            <i class="fas fa-handshake text-primary"></i> Khách Hàng Được Giới Thiệu
                        </h5>
                        <span class="badge badge-primary font-size-0.75 px-3 py-1.5 rounded-pill font-weight-600">
                            Tổng giới thiệu: <?= count($referredCustomers) ?>
                        </span>
                    </div>
                    
                    <?php if (empty($referredCustomers)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-user-plus fa-3x mb-3 text-muted" style="opacity: 0.3;"></i>
                            <p class="m-0 font-weight-600">Chưa ghi nhận khách hàng nào được giới thiệu từ mã này.</p>
                            <p class="font-size-0.8 text-muted mt-1">Khi đối tác điền mã giới thiệu lúc đăng ký hồ sơ, thông tin sẽ tự động hiển thị tại đây.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table-report-cskh">
                                <thead>
                                    <tr>
                                        <th>Khách hàng</th>
                                        <th>Số điện thoại</th>
                                        <th>Giá trị mang lại</th>
                                        <th>Ngày đăng ký</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($referredCustomers as $ref): ?>
                                        <tr>
                                            <td>
                                                <span class="font-weight-700 text-dark"><?= esc($ref['name']) ?></span>
                                                <div class="font-size-0.75 text-muted"><?= esc($ref['code']) ?></div>
                                            </td>
                                            <td><?= esc($ref['phone']) ?></td>
                                            <td class="font-weight-700 text-success">
                                                <?= number_format($ref['total_revenue'] ?? 0) ?>đ
                                            </td>
                                            <td class="font-size-0.8 text-muted">
                                                <?= date('d/m/Y', strtotime($ref['created_at'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/customer_care.js') ?>"></script>
<?= $this->endSection() ?>
