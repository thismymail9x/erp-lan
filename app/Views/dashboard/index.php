<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<?php if ($attendanceStatus && $attendanceStatus['status'] === 'CHECKED_OUT') { ?>
    <div class="attendance-hero hero-success">
        <div class="hero-content">
            <h2>Hoàn thành ngày làm việc!</h2>
            <p>Đã Check-out lúc <b><?= $attendanceStatus['check_out_time'] ?></b>. Chúc bạn buổi tối vui vẻ!</p>
        </div>
        <div class="hero-badge">ĐÃ XONG</div>
    </div>
<?php } elseif ($attendanceStatus && $attendanceStatus['status'] === 'CHECKED_IN') { ?>
    <div class="attendance-hero hero-warning">
        <div class="hero-content">
            <h2>Đang làm việc (In: <?= $attendanceStatus['check_in_time'] ?>)</h2>
            <p>Đừng quên Check-out trước khi ra về để ghi nhận đủ giờ làm nhé.</p>
        </div>
        <a href="<?= base_url('attendance') ?>" class="btn-attendance-main text-orange">
            <i class="fas fa-sign-out-alt"></i> Kết thúc ngày
        </a>
    </div>
<?php } else { ?>
    <div class="attendance-hero hero-primary">
        <div class="hero-content">
            <h2>Bắt đầu ngày làm việc?</h2>
            <p>Vui lòng ghi nhận vị trí và ảnh chụp để hoàn tất điểm danh.</p>
        </div>
        <a href="<?= base_url('attendance') ?>" class="btn-attendance-main">
            <i class="fas fa-camera"></i> Điểm danh
        </a>
    </div>
<?php } ?>

<div class="stats-grid">
    <?php if ($isAdmin || $isLegalDept): ?>
        <!-- WIDGETS PHÁP LÝ (ADMIN & PHÒNG PHÁP LÝ) -->
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-scale-balanced"></i></div>
            <div class="stat-value"><?= number_format($stats['cases'] ?? 0) ?></div>
            <div class="stat-label">Vụ việc <?= ($isAdmin) ? 'tổng' : 'bộ phận' ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
            <div class="stat-value"><?= number_format($stats['customers'] ?? 0) ?></div>
            <div class="stat-label">Khách hàng</div>
        </div>
    <?php elseif ($isHRDept): ?>
        <!-- WIDGETS HÀNH CHÍNH (HR/ADMIN) -->
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?= number_format($deptStats['total_company_employees'] ?? 0) ?></div>
            <div class="stat-label">Tổng nhân sự</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
            <div class="stat-value"><?= number_format($deptStats['new_hires_this_month'] ?? 0) ?></div>
            <div class="stat-label">Nhân sự mới</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-id-badge"></i></div>
            <div class="stat-value"><?= $deptStats['attendance_percent'] ?? 0 ?>%</div>
            <div class="stat-label">Tỷ lệ đi làm</div>
        </div>
    <?php elseif ($isManager): ?>
        <!-- WIDGETS QUẢN TRỊ (CÁC TRƯỞNG PHÒNG KHÁC) -->
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users-viewfinder"></i></div>
            <div class="stat-value"><?= number_format($deptStats['total_members'] ?? 0) ?></div>
            <div class="stat-label">Nhân sự team</div>
        </div>
        <?php if ($isSaleDept): ?>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-user-tag"></i></div>
            <div class="stat-value"><?= number_format($deptStats['dept_customers'] ?? 0) ?></div>
            <div class="stat-label">Khách hàng team</div>
        </div>
        <?php endif; ?>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-value"><?= $deptStats['attendance_percent'] ?? 0 ?>%</div>
            <div class="stat-label">Tỷ lệ công team</div>
        </div>
    <?php else: ?>
        <!-- WIDGETS NHÂN VIÊN (STAFF) -->
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
            <div class="stat-value"><?= number_format($stats['cases'] ?? 0) ?></div>
            <div class="stat-label">Vụ việc phụ trách</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-value"><?= $stats['attendance_rate'] ?? 0 ?>%</div>
            <div class="stat-label">Chuyên cần</div>
        </div>
    <?php endif; ?>
</div>

<div class="premium-card m-b-24">
    <div class="archive-header d-flex justify-content-between align-items-center m-b-20">
        <div>
            <h3 class="m-0"><i class="fas fa-calendar-alt color-primary"></i> Lịch nghỉ</h3>
            <p class="text-muted m-0">Tháng <?= $currentMonthDisplay ?> - Biết ai đang nghỉ để tối ưu phối hợp.</p>
        </div>
        <div class="tag-premium tag-info">Approved Only</div>
    </div>
    
    <div class="company-calendar-grid">
        <div class="cal-day-header">CN</div>
        <div class="cal-day-header">T2</div>
        <div class="cal-day-header">T3</div>
        <div class="cal-day-header">T4</div>
        <div class="cal-day-header">T5</div>
        <div class="cal-day-header">T6</div>
        <div class="cal-day-header">T7</div>
        
        <?php 
            // Render các ô trống đầu tháng
            for ($i = 0; $i < $firstDayOfWeek; $i++) {
                echo '<div class="cal-day-cell cal-day-empty"></div>';
            }
            
            // Render từng ngày trong tháng
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateKey = date('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
                $isToday = ($dateKey === date('Y-m-d'));
                $absents = $absentCalendar[$dateKey] ?? [];
        ?>
            <div class="cal-day-cell <?= $isToday ? 'cal-today' : '' ?>">
                <span class="cal-date-num"><?= $day ?></span>
                <div class="cal-absent-list">
                    <?php foreach ($absents as $person) { 
                        $typeClass = 'absent-annual';
                        if ($person['type'] === 'paid') $typeClass = 'absent-paid';
                        elseif ($person['type'] === 'unpaid') $typeClass = 'absent-unpaid';
                    ?>
                        <div class="absent-item <?= $typeClass ?>" title="<?= esc($person['name']) ?> (<?= esc($person['dept']) ?>) - <?= esc($person['type']) ?>">
                            <span class="absent-dot"></span>
                            <span class="absent-name"><?= explode(' ', $person['name'])[count(explode(' ', $person['name']))-1] ?></span>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<div class="premium-card">
    <h3 class="m-t-0">Hoạt động gần đây</h3>
    <p class="text-muted">Hệ thống đang hoạt động ổn định. Chào mừng bạn đến với hệ thống quản trị L.A.N ERP.</p>
</div>

<style>
.company-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #f0f2f5;
    border: 1px solid #eef1f5;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}
.cal-day-header {
    background: #ffffff;
    padding: 12px;
    text-align: center;
    font-weight: 700;
    color: #64748b;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #f1f5f9;
}
.cal-day-cell {
    background: #fff;
    min-height: 110px;
    height: 110px;
    padding: 8px;
    position: relative;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}
.cal-day-cell:hover {
    background: #f8fafc;
    z-index: 2;
}
.cal-date-num {
    font-size: 0.8rem;
    font-weight: 600;
    color: #94a3b8;
    margin-bottom: 4px;
}
.cal-today {
    background: #f8faff !important;
}
.cal-today .cal-date-num {
    color: #fff;
    background: var(--primary-color, #007bff);
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    box-shadow: 0 4px 10px rgba(0,123,255,0.3);
}
.cal-absent-list {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    gap: 3px;
    padding-right: 2px;
}
/* Tuỳ chỉnh thanh cuộn siêu nhỏ */
.cal-absent-list::-webkit-scrollbar {
    width: 3px;
}
.cal-absent-list::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 3px;
}

.absent-item {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.65rem;
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: default;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: transform 0.1s;
    min-height: 18px;
}
.absent-item:hover {
    transform: translateX(2px);
}
.absent-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Color schemes for Leave Types */
.absent-annual {
    background: #ecfdf5;
    color: #065f46;
    border-left: 3px solid #10b981;
}
.absent-annual .absent-dot { background: #10b981; }

.absent-paid {
    background: #eff6ff;
    color: #1e40af;
    border-left: 3px solid #3b82f6;
}
.absent-paid .absent-dot { background: #3b82f6; }

.absent-unpaid {
    background: #f1f5f9;
    color: #334155;
    border-left: 3px solid #64748b;
}
.absent-unpaid .absent-dot { background: #64748b; }

.absent-name {
    font-weight: 600;
}
.cal-day-empty {
    background: #fcfdfe;
}

@media (max-width: 768px) {
    .cal-day-cell {
        min-height: 70px;
        padding: 6px;
    }
    .absent-name {
        display: none;
    }
    .absent-item {
        justify-content: center;
        padding: 6px;
        border-left: none;
        border-radius: 50%;
        width: 14px;
        height: 14px;
        margin: 0 auto;
    }
    .absent-dot {
        width: 8px;
        height: 8px;
    }
}
</style>

<?= $this->endSection() ?>
