<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<?php if ($attendanceStatus && $attendanceStatus['status'] === 'CHECKED_OUT'): ?>
    <div class="attendance-hero hero-success">
        <div class="hero-content">
            <h2>Tuyệt vời! Bạn đã hoàn thành công việc</h2>
            <p>Hệ thống ghi nhận bạn đã Check-out lúc <b><?= $attendanceStatus['check_out_time'] ?></b>. Chúc bạn buổi tối vui vẻ!</p>
        </div>
        <div class="hero-badge">HOÀN TẤT</div>
    </div>
<?php elseif ($attendanceStatus && $attendanceStatus['status'] === 'CHECKED_IN'): ?>
    <div class="attendance-hero hero-warning">
        <div class="hero-content">
            <h2>Bạn đang trong giờ làm việc (Check-in: <?= $attendanceStatus['check_in_time'] ?>)</h2>
            <p>Đừng quên thực hiện Check-out trước khi ra về để ghi nhận đủ giờ làm nhé.</p>
        </div>
        <a href="<?= base_url('attendance') ?>" class="btn-attendance-main text-orange">
            <i class="fas fa-sign-out-alt"></i> Kết thúc ngày làm việc
        </a>
    </div>
<?php else: ?>
    <div class="attendance-hero hero-primary">
        <div class="hero-content">
            <h2>Sẵn sàng bắt đầu ngày làm việc?</h2>
            <p>Ghi nhận vị trí và ảnh chụp của bạn để hoàn tất điểm danh hôm nay.</p>
        </div>
        <a href="<?= base_url('attendance') ?>" class="btn-attendance-main">
            <i class="fas fa-camera"></i> Chấm công ngay
        </a>
    </div>
<?php endif; ?>

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

<div class="premium-card">
    <h3 style="margin-top:0">Hoạt động gần đây</h3>
    <p class="text-muted">Hệ thống đang hoạt động ổn định. Chào mừng bạn đến với hệ thống quản trị L.A.N ERP.</p>
</div>
<?= $this->endSection() ?>
