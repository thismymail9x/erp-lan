<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-scale-balanced"></i></div>
        <div class="stat-value">24</div>
        <div class="stat-label">Vụ việc đang xử lý</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
        <div class="stat-value">150</div>
        <div class="stat-label">Tổng khách hàng</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="stat-value">450M</div>
        <div class="stat-label">Doanh thu tháng này</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-value">98%</div>
        <div class="stat-label">Tỷ lệ chấm công</div>
    </div>
</div>

<div class="card" style="margin-top: 40px;">
    <h3 style="margin-top:0">Hoạt động gần đây</h3>
    <p style="color: var(--apple-text-muted)">Hệ thống đang hoạt động ổn định. Chào mừng bạn đến với hệ thống quản trị LawFirm ERP.</p>
</div>
<?= $this->endSection() ?>
