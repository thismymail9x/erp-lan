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

<div class="motivation-widget premium-card m-b-24">
    <div class="motivation-header">
        <div class="motivation-title">
            <h3><i class="fas fa-coins text-gold"></i> KPI năm 
                <form action="<?= base_url('dashboard') ?>" method="GET" style="display: inline-block;">
                    <select name="year" onchange="this.form.submit()" style="border: none; background: transparent; font-size: 1.1rem; font-weight: 700; color: #1d1d1f; cursor: pointer; outline: none;">
                        <?php 
                        $startYear = 2026;
                        $endYear = max(date('Y') + 1, 2027);
                        for ($y = $startYear; $y <= $endYear; $y++) { ?>
                            <option value="<?= $y ?>" <?= $kpiYear == $y ? 'selected' : '' ?>>Năm <?= $y ?></option>
                        <?php } ?>
                    </select>
                </form>
            </h3>
            <p class="text-muted">Hoàn thành các bước trong hồ sơ để tối ưu hóa thu nhập.</p>
        </div>
        <div class="motivation-total">
            <span class="total-label">Tổng mục tiêu:</span>
            <span class="total-value text-blue"><?= number_format($kpiStats['total']) ?> vnđ</span>
        </div>
    </div>
    
    <div class="motivation-body">
        <div class="kpi-progress-container">
            <div class="kpi-progress-info">
                <span>Tiến độ mục tiêu</span>
                <span><?= $kpiStats['percent'] ?>%</span>
            </div>
            <div class="kpi-progress-bar-bg">
                <div class="kpi-progress-bar-fill" style="width: <?= $kpiStats['percent'] ?>%;"></div>
            </div>
        </div>
        
        <div class="kpi-stats-row">
            <div class="kpi-stat-item">
                <div class="kpi-stat-label">KPI nhận</div>
                <div class="kpi-stat-val text-green">+ <?= number_format($kpiStats['earned']) ?> vnđ</div>
            </div>
            <div class="kpi-stat-item">
                <div class="kpi-stat-label">KPI còn</div>
                <div class="kpi-stat-val text-orange">~ <?= number_format($kpiStats['potential']) ?> vnđ</div>
            </div>
            <div class="kpi-stat-item" onclick="window.location.href='<?= base_url('cases?status=missed_kpi' . ($isAdmin ? '' : '&lawyer_id[]=' . session()->get('employee_id'))) ?>'" style="cursor: pointer;" title="Xem các vụ việc khiến bạn bị mất KPI">
                <div class="kpi-stat-label">KPI bỏ lỡ <i class="fas fa-external-link-alt" style="font-size: 0.6rem;"></i></div>
                <div class="kpi-stat-val text-red">- <?= number_format($kpiStats['lost']) ?> vnđ</div>
            </div>
        </div>
    </div>
</div>

<div class="stats-grid">
    <?php 
    $canSeeAllStats = ($isAdmin || $isManager || $isLegalDept || has_permission('case.view_all')); 
    ?>

    <?php if ($canSeeAllStats): ?>
        <!-- WIDGETS TỔNG QUAN (ADMIN, MANAGER, LEGAL) -->
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases') ?>'" style="cursor: pointer;">
            <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
            <div class="stat-value"><?= number_format($stats['total_cases'] ?? 0) ?></div>
            <div class="stat-label">Tổng vụ việc</div>
        </div>
    <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=cho_tiep_nhan') ?>'" style="cursor: pointer;">
        <div class="stat-icon text-orange"><i class="fas fa-hourglass-start"></i></div>
        <div class="stat-value"><?= number_format($stats['waiting_cases'] ?? 0) ?></div>
        <div class="stat-label">Chờ tiếp nhận</div>
    </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=dang_xu_ly') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-blue"><i class="fas fa-spinner fa-spin"></i></div>
            <div class="stat-value"><?= number_format($stats['processing_cases'] ?? 0) ?></div>
            <div class="stat-label">Đang xử lý</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=da_hoan_thanh') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-green"><i class="fas fa-check-double"></i></div>
            <div class="stat-value"><?= number_format($stats['completed_cases'] ?? 0) ?></div>
            <div class="stat-label">Đã hoàn thành</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=overdue') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-red"><i class="fas fa-clock"></i></div>
            <div class="stat-value text-red"><?= number_format($stats['overdue_cases'] ?? 0) ?></div>
            <div class="stat-label">Quá hạn</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('customers') ?>'" style="cursor: pointer;">
            <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
            <div class="stat-value"><?= number_format($stats['customers'] ?? 0) ?></div>
            <div class="stat-label">Khách hàng</div>
        </div>

        <!-- Thống kê bộ phận bổ sung cho Manager (nếu không phải Legal) -->
        <?php if ($isManager && !$isLegalDept && !$isAdmin): ?>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users-viewfinder"></i></div>
                <div class="stat-value"><?= number_format($deptStats['total_members'] ?? 0) ?></div>
                <div class="stat-label">Nhân sự team</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-value"><?= $deptStats['attendance_percent'] ?? 0 ?>%</div>
                <div class="stat-label">Tỷ lệ công team</div>
            </div>
        <?php endif; ?>

    <?php elseif ($isHRDept): ?>
        <!-- WIDGETS HÀNH CHÍNH (HR) -->
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
    <?php else: ?>
        <!-- WIDGETS NHÂN VIÊN (STAFF) -->
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases') ?>'" style="cursor: pointer;">
            <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
            <div class="stat-value"><?= number_format($stats['total_cases'] ?? 0) ?></div>
            <div class="stat-label">Vụ việc của tôi</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=cho_tiep_nhan') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-orange"><i class="fas fa-hourglass-start"></i></div>
            <div class="stat-value"><?= number_format($stats['waiting_cases'] ?? 0) ?></div>
            <div class="stat-label">Chờ tiếp nhận</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=dang_xu_ly') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-blue"><i class="fas fa-spinner fa-spin"></i></div>
            <div class="stat-value"><?= number_format($stats['processing_cases'] ?? 0) ?></div>
            <div class="stat-label">Đang làm</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=da_hoan_thanh') ?>'" style="cursor: pointer;">
            <div class="stat-icon text-green"><i class="fas fa-check-double"></i></div>
            <div class="stat-value"><?= number_format($stats['completed_cases'] ?? 0) ?></div>
            <div class="stat-label">Đã xong</div>
        </div>
        <div class="stat-card" onclick="window.location.href='<?= base_url('cases?status=overdue' . ($isAdmin ? '' : '&lawyer_id[]=' . session()->get('employee_id'))) ?>'" style="cursor: pointer;">
            <div class="stat-icon text-red"><i class="fas fa-clock"></i></div>
            <div class="stat-value text-red"><?= number_format($stats['overdue_cases'] ?? 0) ?></div>
            <div class="stat-label">Trễ hạn</div>
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

<?php if (!empty($upcomingTrips)): ?>
<div class="premium-card m-b-24" style="border-left: 4px solid #e74c3c;">
    <div class="archive-header d-flex justify-content-between align-items-center m-b-15">
        <div>
            <h3 class="m-0" style="color: #e74c3c;"><i class="fas fa-plane-departure"></i> Lịch công tác sắp tới</h3>
            <p class="text-muted m-0">Thông tin di chuyển của nhân sự trong 30 ngày tới.</p>
        </div>
        <a href="<?= base_url('work-schedules') ?>" class="btn-attendance-main" style="padding: 5px 12px; font-size: 0.75rem; background: #fef2f2; color: #e74c3c; border: 1px solid #fee2e2;">
            Xem tất cả
        </a>
    </div>
    <div class="trip-list-grid">
        <?php foreach ($upcomingTrips as $trip): ?>
            <div class="trip-item">
                <div class="trip-date">
                    <span class="day"><?= date('d', strtotime($trip['start_at'])) ?></span>
                    <span class="month">Th<?= date('m', strtotime($trip['start_at'])) ?></span>
                </div>
                <div class="trip-info">
                    <div class="trip-title"><?= esc($trip['title']) ?></div>
                    <div class="trip-meta">
                        <span class="trip-person"><i class="fas fa-user"></i> <?= esc($trip['employee_name']) ?></span>
                        <span class="trip-loc"><i class="fas fa-map-marker-alt"></i> <?= esc($trip['location'] ?: 'N/A') ?></span>
                    </div>
                </div>
                <div class="trip-badge">
                    <?= date('H:i', strtotime($trip['start_at'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.trip-list-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.trip-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    background: #fff5f5;
    border-radius: 12px;
    transition: transform 0.2s;
}
.trip-item:hover {
    transform: translateX(5px);
    background: #fff0f0;
}
.trip-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #e74c3c;
    color: #fff;
    width: 45px;
    height: 45px;
    border-radius: 10px;
    flex-shrink: 0;
}
.trip-date .day { font-size: 1.1rem; font-weight: 800; line-height: 1; }
.trip-date .month { font-size: 0.65rem; font-weight: 600; text-transform: uppercase; }
.trip-info { flex: 1; min-width: 0; }
.trip-title { font-weight: 700; font-size: 0.9rem; color: #1d1d1f; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
.trip-meta { display: flex; gap: 15px; font-size: 0.75rem; color: #64748b; }
.trip-meta span { display: flex; align-items: center; gap: 5px; }
.trip-badge { font-size: 0.8rem; font-weight: 700; color: #e74c3c; background: #fff; padding: 4px 8px; border-radius: 6px; border: 1px solid #fee2e2; }
</style>
<?php endif; ?>

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
        min-height: 75px;
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
/* KPI Motivation Widget */
.motivation-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 25px;
}
.motivation-title h3 { margin: 0; font-size: 1.15rem; }
.motivation-total { text-align: right; }
.total-label { display: block; font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; }
.total-value { font-size: 1.5rem; font-weight: 800; }

.kpi-progress-container { margin-bottom: 25px; }
.kpi-progress-info {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    font-weight: 700;
    margin-bottom: 8px;
    color: #334155;
}
.kpi-progress-bar-bg {
    height: 14px;
    background: #f1f5f9;
    border-radius: 50px;
    overflow: hidden;
}
.kpi-progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6 0%, #10b981 100%);
    border-radius: 50px;
    transition: width 1s ease-in-out;
}

.kpi-stats-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
}
.kpi-stat-item {
    background: #f8fafc;
    padding: 15px;
    border-radius: 12px;
}
.kpi-stat-label { font-size: 0.75rem; color: #64748b; margin-bottom: 5px; font-weight: 600; }
.kpi-stat-val { font-size: 1.1rem; font-weight: 800; }

.text-gold { color: #f59e0b; }
.text-green { color: #10b981; }
.text-orange { color: #f97316; }

@media (max-width: 768px) {
    .motivation-header { flex-direction: column; gap: 10px; }
    .motivation-total { text-align: left; }
    .kpi-stats-row { grid-template-columns: 1fr; }
}
</style>

<?= $this->endSection() ?>
