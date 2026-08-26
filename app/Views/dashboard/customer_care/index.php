<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/customer_care.css') ?>">
<!-- Chart.js CDN -->
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4 customer-care-shell">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 customer-care-header">
        <div>
            <h1 class="h3 font-weight-700 text-dark mb-1">Chăm Sóc Khách Hàng Cũ (CSKH)</h1>
            <p class="text-muted font-size-0.9">Hệ thống đo lường hiệu suất chăm sóc sau dịch vụ, phân loại A/B/C và Loyalty/VIP.</p>
        </div>
        <div class="d-flex gap-2 customer-care-actions">
            <a href="<?= base_url('customer-care/sla-report') ?>" class="btn-secondary d-flex align-items-center gap-2">
                <i class="fas fa-history"></i> <span>Báo cáo & Hạn chăm sóc</span>
            </a>
            <a href="<?= base_url('customer-care/daily-checklist') ?>" class="btn-premium d-flex align-items-center gap-2">
                <i class="fas fa-tasks"></i> <span>Checklist Hôm Nay</span>
            </a>
            <a href="<?= base_url('customer-care/customers') ?>" class="btn-secondary d-flex align-items-center gap-2">
                <i class="fas fa-layer-group"></i> <span>Phân Nhóm Khách Hàng</span>
            </a>
        </div>
    </div>

    <!-- Cảnh báo đỏ SLA quá hạn (Red Alert) -->
    <?php if (isset($overdueSlaCount) && $overdueSlaCount > 0): ?>
        <div class="sla-pulsing-banner d-flex align-items-center justify-content-between mb-4 p-3 border" style="padding: 18px 24px !important;">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: rgba(255, 59, 48, 0.1); display: flex; align-items: center; justify-content: center; color: #ff3b30;">
                    <i class="fas fa-exclamation-triangle fa-lg"></i>
                </div>
                <div>
                    <h5 class="m-0 font-weight-700 text-dark" style="font-size: 14.5px;">🚨 CẢNH BÁO ĐỎ: Phát hiện <?= esc($overdueSlaCount) ?> khách bị trễ hạn chăm sóc!</h5>
                    <p class="m-0 text-muted" style="font-size: 12px; margin-top: 3px;">Yêu cầu kiểm tra, đôn đốc nhân viên liên hệ khách gấp để tránh vi phạm uy tín dịch vụ.</p>
                </div>
            </div>
            <a href="<?= base_url('customer-care/sla-report') ?>" class="btn btn-danger btn-sm rounded-pill px-4" style="background: #ff3b30; border: none; font-weight: 700; font-size: 11.5px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                Xử lý trễ hạn
            </a>
        </div>
    <?php endif; ?>

    <!-- 4 KPI Cards -->
    <div class="kpi-container">
        <div class="kpi-card success">
            <div class="kpi-header">
                <span class="kpi-title">Đang chăm sóc</span>
                <i class="fas fa-heartbeat kpi-icon text-success"></i>
            </div>
            <div class="kpi-value"><?= esc($kpis['active_plans_count'] ?? 0) ?></div>
            <div class="kpi-trend up">
                <i class="fas fa-sync-alt fa-spin"></i> <span>Kế hoạch đang chạy</span>
            </div>
        </div>

        <div class="kpi-card warning">
            <div class="kpi-header">
                <span class="kpi-title">Việc Quá Hạn</span>
                <i class="fas fa-clock kpi-icon text-warning"></i>
            </div>
            <div class="kpi-value text-danger"><?= esc($kpis['overdue_tasks_count'] ?? 0) ?></div>
            <div class="kpi-trend down">
                <i class="fas fa-exclamation-triangle"></i> <span>Cần xử lý gấp</span>
            </div>
        </div>

        <div class="kpi-card vip">
            <div class="kpi-header">
                <span class="kpi-title">Khách Hàng VIP (Nhóm A)</span>
                <i class="fas fa-crown kpi-icon text-warning"></i>
            </div>
            <div class="kpi-value"><?= esc($kpis['vip_customers_count'] ?? 0) ?></div>
            <div class="kpi-trend neutral">
                <i class="fas fa-gem"></i> <span>Được cá nhân hóa</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Tỷ Lệ Giới Thiệu</span>
                <i class="fas fa-users kpi-icon text-primary"></i>
            </div>
            <div class="kpi-value"><?= esc(number_format($kpis['referral_rate'] ?? 0, 1)) ?>%</div>
            <div class="kpi-trend up">
                <i class="fas fa-chevron-up"></i> <span>Mục tiêu >= 15%</span>
            </div>
        </div>
    </div>

    <!-- Charts & Quick Task List -->
    <div class="row mb-4">
        <!-- Biểu đồ phân nhóm A/B/C -->
        <div class="col-md-5 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h5 class="card-title font-weight-700 mb-4">Cơ Cấu Nhóm Khách Hàng (A/B/C)</h5>
                    <div style="height: 250px; position: relative;">
                        <canvas id="segmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Công việc cần xử lý gấp -->
        <div class="col-md-7 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; height: 100%;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title font-weight-700 m-0">Công Việc Quá Hạn & CSKH Cần Gấp</h5>
                        <span class="badge badge-danger" style="border-radius: 12px; padding: 4px 10px;">Chờ Xử Lý</span>
                    </div>

                    <?php if (empty($kpis['urgent_tasks'])): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <p class="m-0 font-weight-600">Tuyệt vời! Không có công việc CSKH quá hạn.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Khách hàng</th>
                                        <th>Công việc</th>
                                        <th>Hạn chót</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kpis['urgent_tasks'] as $t): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= base_url('customer-care/care-plan/' . $t['customer_id']) ?>" class="font-weight-600 text-dark">
                                                    <?= esc($t['customer_name']) ?>
                                                </a>
                                                <div class="font-size-0.75 text-muted"><?= esc($t['customer_code']) ?></div>
                                            </td>
                                            <td>
                                                <span class="task-channel <?= esc($t['channel']) ?> font-size-0.7 px-2 py-1 rounded mr-2">
                                                    <?= esc($t['channel']) ?>
                                                </span>
                                                <span class="font-size-0.85 font-weight-500 text-secondary"><?= esc($t['title']) ?></span>
                                            </td>
                                            <td class="text-danger font-size-0.8 font-weight-600">
                                                <?= date('d/m/Y', strtotime($t['due_date'])) ?>
                                            </td>
                                            <td>
                                                <a href="<?= base_url('customer-care/care-plan/' . $t['customer_id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    Chăm sóc
                                                </a>
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

    <!-- Sinh nhật trong tuần & Khách hàng bỏ quên -->
    <div class="row">
        <!-- Sinh nhật trong tuần -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h5 class="card-title font-weight-700 mb-3 text-primary d-flex align-items-center gap-2">
                        <i class="fas fa-birthday-cake"></i> <span>Sinh Nhật Sắp Tới (7 ngày)</span>
                    </h5>
                    
                    <?php if (empty($kpis['upcoming_birthdays'])): ?>
                        <div class="text-center py-4 text-muted">
                            <p class="m-0">Không có sinh nhật nào trong 7 ngày tới.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Khách hàng</th>
                                        <th>Ngày sinh</th>
                                        <th>Số điện thoại</th>
                                        <th>Tác vụ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kpis['upcoming_birthdays'] as $b): ?>
                                        <tr>
                                            <td>
                                                <span class="font-weight-600 text-dark"><?= esc($b['name']) ?></span>
                                                <span class="badge-segment <?= esc($b['customer_segment'] ?? 'potential') ?> font-size-0.6 ml-2">
                                                    <?= esc($b['customer_segment'] ?? 'Nhóm C') ?>
                                                </span>
                                            </td>
                                            <td class="font-weight-600 text-secondary">
                                                <?= date('d/m', strtotime($b['date_of_birth'])) ?>
                                            </td>
                                            <td><?= esc($b['phone']) ?></td>
                                            <td>
                                                <a href="https://zalo.me/<?= esc($b['phone']) ?>" target="_blank" class="btn btn-sm btn-info rounded-pill px-3">
                                                    <i class="fab fa-whatsapp"></i> Zalo
                                                </a>
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

        <!-- Khách hàng quá hạn 60 ngày chưa liên hệ -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h5 class="card-title font-weight-700 mb-3 text-warning d-flex align-items-center gap-2">
                        <i class="fas fa-user-slash"></i> <span>Khách Cần Kích Hoạt Lại (Chưa liên hệ > 60 ngày)</span>
                    </h5>
                    
                    <?php if (empty($kpis['dormant_customers'])): ?>
                        <div class="text-center py-4 text-muted">
                            <p class="m-0 text-success font-weight-600"><i class="fas fa-check-circle"></i> Đã liên hệ đều đặn tất cả khách hàng!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Khách hàng</th>
                                        <th>Liên hệ cuối</th>
                                        <th>Doanh thu</th>
                                        <th>Tác vụ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kpis['dormant_customers'] as $d): ?>
                                        <tr>
                                            <td>
                                                <span class="font-weight-600 text-dark"><?= esc($d['name']) ?></span>
                                                <div class="font-size-0.75 text-muted"><?= esc($d['code']) ?></div>
                                            </td>
                                            <td class="text-muted font-size-0.8">
                                                <?= $d['last_contact_date'] ? date('d/m/Y', strtotime($d['last_contact_date'])) : 'Chưa từng liên hệ' ?>
                                            </td>
                                            <td class="font-weight-600 text-dark">
                                                <?= number_format($d['total_revenue'] ?? 0) ?>đ
                                            </td>
                                            <td>
                                                <a href="<?= base_url('customer-care/care-plan/' . $d['id']) ?>" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                                                    Kết nối lại
                                                </a>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
window.customerCareDashboardConfig = {
    labels: ['Nhóm A (VIP)', 'Nhóm B (Phổ thông)', 'Nhóm C (Tiềm năng)'],
    segmentData: [
        <?= (int)($kpis['segments']['vip'] ?? 0) ?>,
        <?= (int)($kpis['segments']['regular'] ?? 0) ?>,
        <?= (int)($kpis['segments']['potential'] ?? 0) ?>
    ]
};
</script>
<script src="<?= base_url('js/customer_care.js') ?>"></script>
<?= $this->endSection() ?>
