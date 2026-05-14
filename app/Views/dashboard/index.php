<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<style>
    /* 1. GIỮ NGUYÊN CSS GỐC CỦA DASHBOARD (KPI, Stats, Hero) */
    .attendance-hero { padding: 30px; border-radius: 20px; color: #fff; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .hero-primary { background: linear-gradient(135deg, #007aff 0%, #0056b3 100%); }
    .hero-warning { background: linear-gradient(135deg, #ff9500 0%, #ffcc00 100%); }
    .hero-success { background: linear-gradient(135deg, #34c759 0%, #28a745 100%); }
    .hero-content h2 { margin: 0 0 5px 0; font-size: 1.6rem; font-weight: 800; }
    .hero-content p { margin: 0; opacity: 0.9; font-weight: 500; }
    .btn-attendance-main { background: #fff; color: #007aff; padding: 12px 24px; border-radius: 12px; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(255,255,255,0.3); }
    .btn-attendance-main:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255,255,255,0.4); }

    .motivation-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
    .motivation-title h3 { margin: 0; font-size: 1.15rem; }
    .motivation-total { text-align: right; }
    .total-label { display: block; font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; }
    .total-value { font-size: 1.5rem; font-weight: 800; }
    .kpi-progress-container { margin-bottom: 25px; }
    .kpi-progress-info { display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 700; margin-bottom: 8px; color: #334155; }
    .kpi-progress-bar-bg { height: 14px; background: #f1f5f9; border-radius: 50px; overflow: hidden; }
    .kpi-progress-bar-fill { height: 100%; background: linear-gradient(90deg, #3b82f6 0%, #10b981 100%); border-radius: 50px; transition: width 1s ease-in-out; }
    .kpi-stats-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    .kpi-stat-item { background: #f8fafc; padding: 15px; border-radius: 12px; }
    .kpi-stat-label { font-size: 0.75rem; color: #64748b; margin-bottom: 5px; font-weight: 600; }
    .kpi-stat-val { font-size: 1.1rem; font-weight: 800; }

    /*.stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }*/
    .stat-card { background: #fff; padding: 20px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); transition: all 0.3s ease; border: 1px solid #f1f5f9; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.08); border-color: #007aff; }
    .stat-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(0,122,255,0.05); color: #007aff; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: 15px; }
    .stat-value { font-size: 1.5rem; font-weight: 800; color: #1d1d1f; margin-bottom: 2px; }
    .stat-label { font-size: 0.8rem; font-weight: 600; color: #8e8e93; }

    /* 2. CSS CHO PHẦN LỊCH TRÌNH (BÊ NGUYÊN TỪ MODULE CŨ) */
    .calendar-container-card { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); display: grid; grid-template-columns: 280px 1fr; height: 750px; overflow: hidden; margin-bottom: 24px; border: 1px solid #f1f5f9; }
    .calendar-sidebar { background: #f8fafc; border-right: 1px solid #eef2f6; padding: 16px; display: flex; flex-direction: column; gap: 16px; overflow-y: auto; }
    .calendar-main { padding: 24px; position: relative; }
    #calendar { height: 100%; }
    
    /* Select2 Custom Fix */
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--single { height: 40px !important; border: 1px solid #e2e8f0 !important; border-radius: 8px !important; transition: all 0.2s; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px !important; padding-left: 12px !important; color: #1e293b !important; font-size: 0.9rem; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px !important; }
    .select2-container--default.select2-container--focus .select2-selection--single { border-color: #007aff !important; }
    
    .sidebar-section-title { font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; }
    .btn-create-ws { background: #007aff; color: #fff; border: none; padding: 12px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2); }
    .btn-create-ws:hover { background: #0062cc; transform: translateY(-1px); }
    .btn-create-leave { background: #e74c3c; color: #fff; border: none; padding: 12px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(231, 76, 60, 0.2); text-decoration: none; margin-top: 10px; }
    
    .type-legend { display: flex; flex-direction: column; gap: 8px; }
    .legend-item { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: #475569; cursor: pointer; padding: 5px 8px; border-radius: 6px; transition: all 0.2s; opacity: 0.6; }
    .legend-item.active { opacity: 1; background: #fff; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.03); }
    .legend-color { width: 12px; height: 12px; border-radius: 3px; }
    
    /* FullCalendar Apple Style Fix */
    :root { --fc-border-color: #f1f5f9; --fc-daygrid-dot-event-hover-bg-color: #f8fafc; --fc-button-bg-color: #fff; --fc-button-border-color: #e2e8f0; --fc-button-text-color: #475569; }
    .fc .fc-toolbar-title { font-size: 1.1rem; font-weight: 700; color: #1d1d1f; }
    .fc .fc-button { padding: 8px 16px; font-size: 0.85rem; font-weight: 600; border-radius: 10px; transition: all 0.2s; }
    .fc .fc-button-primary { background-color: #fff !important; border-color: #e2e8f0 !important; color: #475569 !important; box-shadow: none !important; }
    .fc .fc-button-primary:hover { background-color: #f8fafc !important; border-color: #cbd5e1 !important; color: #1e293b !important; }
    .fc .fc-button-primary:not(:disabled).fc-button-active, 
    .fc .fc-button-primary:not(:disabled):active { background-color: #f1f5f9 !important; border-color: #94a3b8 !important; color: #0f172a !important; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05) !important; }
    .fc-event { border: none; padding: 2px 4px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

    /* Modal CSS (BÊ NGUYÊN) */
    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 1000; }
    .modal-content-custom { background: #fff; width: 500px; border-radius: 16px; padding: 24px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2); animation: modalFadeIn 0.3s ease-out; }
    @keyframes modalFadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-header h3 { margin: 0; font-size: 1.2rem; font-weight: 700; }
    .close-modal { background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 6px; }
    .form-control-custom { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; outline: none; transition: border 0.2s; }
    .form-control-custom:focus { border-color: #007aff; }
    .row-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
    .btn-secondary-custom { background: #f1f5f9; color: #475569; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
    .btn-primary-custom { background: #007aff; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
    
    /* Tippy Style */
    .tippy-box[data-theme~='light-border'] { background-color: #fff; color: #1d1d1f; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 2px solid #007aff; border-radius: 12px; }

    /* 3. MOBILE RESPONSIVE */
    @media (max-width: 991px) {
        .calendar-container-card { grid-template-columns: 1fr; height: auto; }
        .calendar-sidebar { border-right: none; border-bottom: 1px solid #eef2f6; padding: 16px; order: 2; gap: 12px; }
        .calendar-main { padding: 15px; height: 600px; order: 1; border-bottom: 1px solid #f1f5f9; }
        
        .filter-section { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .legend-section { margin-top: 5px; }
        .type-legend { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 8px; }
        
        .modal-content-custom { width: 95%; max-width: 95%; padding: 20px; }
        .row-grid { grid-template-columns: 1fr !important; gap: 0; }
        .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
        .hero-content h2 { font-size: 1.3rem; }
        .btn-attendance-main { padding: 10px 16px; font-size: 0.9rem; }
        
        /* FullCalendar Mobile Adjustments */
        .fc .fc-toolbar { flex-direction: column; gap: 10px; }
        .fc .fc-toolbar-title { font-size: 1rem; }
        .fc .fc-button { padding: 6px 10px; font-size: 0.75rem; }
    }
    
    @media (max-width: 576px) {
        .filter-section { grid-template-columns: 1fr; gap: 10px; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .calendar-main { height: 500px; }
        .attendance-hero { flex-direction: column; align-items: flex-start; gap: 15px; }
    }

    @media (max-width: 400px) {
        .stats-grid { grid-template-columns: 1fr; }
        .kpi-stats-row { grid-template-columns: 1fr; gap: 10px; }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- 1. HERO SECTION (GIỮ NGUYÊN) -->
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

<!-- 2. KPI WIDGET (GIỮ NGUYÊN CSS GỐC) -->
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
                            <option value="<?= $y ?>" <?= $kpiYear == $y ? 'selected' : '' ?>><?= $y ?>&nbsp;</option>
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
            <div class="kpi-stat-item" onclick="window.location.href='<?= base_url('cases?status=missed_kpi' . ($isAdmin ? '' : '&lawyer_id[]=' . session()->get('employee_id'))) ?>'" style="cursor: pointer;">
                <div class="kpi-stat-label">KPI bỏ lỡ <i class="fas fa-external-link-alt" style="font-size: 0.6rem;"></i></div>
                <div class="kpi-stat-val text-red">- <?= number_format($kpiStats['lost']) ?> vnđ</div>
            </div>
        </div>
    </div>
</div>

<!-- 3. STATS GRID (GIỮ NGUYÊN CSS GỐC) -->
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

<!-- 4. LỊCH TRÌNH SECTION (BÊ NGUYÊN TỪ MODULE CŨ) -->
<div class="calendar-container-card">
    <aside class="calendar-sidebar">
        <button class="btn-create-ws" id="btnOpenCreate">
            <i class="fas fa-plus"></i> Lịch trình công việc
        </button>
        <a href="<?= base_url('leave-requests/create') ?>" class="btn-create-leave">
            <i class="fas fa-calendar-minus"></i> Đơn nghỉ
        </a>

        <div class="filter-section">
            <div class="sidebar-section-title">Bộ lọc nhân sự</div>
            <div class="filter-group" style="margin-bottom: 8px;">
                <select id="filterDept" class="form-control-custom">
                    <option value="">Tất cả phòng ban</option>
                    <?php foreach ($departments as $dept) : ?>
                        <option value="<?= $dept['id'] ?>"><?= esc($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select id="filterEmployee" class="select2-basic">
                    <option value="">Tất cả nhân viên</option>
                    <?php foreach ($employees as $emp) : ?>
                        <option value="<?= $emp['id'] ?>" data-dept="<?= $emp['department_id'] ?>"><?= esc($emp['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="legend-section">
            <div class="sidebar-section-title">Loại lịch trình</div>
            <div class="type-legend">
                <div class="legend-item active" data-type="leave">
                    <div class="legend-color" style="background: #e74c3c;"></div>
                    <span>Ngày nghỉ (Đỏ)</span>
                </div>
                <div class="legend-item active" data-type="business_trip">
                    <div class="legend-color" style="background: #10b981;"></div>
                    <span>Công tác (Xanh lá)</span>
                </div>
                <div class="legend-item active" data-type="work">
                    <div class="legend-color" style="background: #f59e0b;"></div>
                    <span>Tại văn phòng (Vàng)</span>
                </div>
            </div>
        </div>
    </aside>

    <main class="calendar-main">
        <div id="calendar"></div>
    </main>
</div>

<!-- 5. MODAL TẠO/SỬA LỊCH TRÌNH (BÊ NGUYÊN FORM GỐC) -->
<div class="modal-overlay" id="wsModal">
    <div class="modal-content-custom">
        <div class="modal-header">
            <h3 id="modalTitle">Tạo lịch trình mới</h3>
            <button class="close-modal" id="btnCloseModal"><i class="fas fa-times"></i></button>
        </div>
        <form id="wsForm">
            <input type="hidden" name="id" id="wsId">
            
            <div class="form-group">
                <label>Tiêu đề / Mục đích</label>
                <input type="text" name="title" id="wsTitle" class="form-control-custom" placeholder="Ví dụ: Họp với khách hàng A, Công tác Hà Nội..." required>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label>Loại lịch trình</label>
                    <select name="type" id="wsType" class="form-control-custom">
                        <option value="business_trip">Đi công tác</option>
                        <option value="work">Tại văn phòng</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Địa điểm</label>
                    <input type="text" name="location" id="wsLocation" class="form-control-custom" placeholder="Địa chỉ hoặc tên văn phòng">
                </div>
            </div>

            <div class="row-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Ngày bắt đầu</label>
                    <input type="date" id="wsStartDate" class="form-control-custom" required>
                </div>
                <div class="form-group">
                    <label>Giờ</label>
                    <input type="time" id="wsStartTime" class="form-control-custom" value="08:00" required>
                </div>
            </div>

            <div class="row-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Ngày kết thúc</label>
                    <input type="date" id="wsEndDate" class="form-control-custom" required>
                </div>
                <div class="form-group">
                    <label>Giờ</label>
                    <input type="time" id="wsEndTime" class="form-control-custom" value="17:00" required>
                </div>
            </div>

            <input type="hidden" name="start_at" id="wsStartAt">
            <input type="hidden" name="end_at" id="wsEndAt">

            <div style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-top: 15px; border: 1px solid #e2e8f0;">
                <div class="form-group">
                    <label>Nhân sự thực hiện</label>
                    <select name="employee_id" id="wsEmployeeId" class="form-control-custom select2-basic">
                        <?php foreach ($employees as $emp) : ?>
                            <option value="<?= $emp['id'] ?>" <?= $emp['id'] == $current_employee_id ? 'selected' : '' ?>><?= esc($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label><i class="fas fa-user-tag"></i> Vụ việc cá nhân / Nhận phân công từ</label>
                    <select name="assigned_by_id" id="wsAssignedById" class="form-control-custom select2-basic">
                        <option value="">-- Vụ việc cá nhân --</option>
                        <?php foreach ($employees as $emp) : ?>
                            <option value="<?= $emp['id'] ?>"><?= esc($emp['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary-custom" id="btnDeleteWs" style="display: none; background: #fee2e2; color: #dc2626;">Xóa</button>
                <button type="button" class="btn-secondary-custom" id="btnCancelModal">Hủy</button>
                <button type="submit" class="btn-primary-custom" id="btnSaveWs">Lưu lịch trình</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const wsModal = document.getElementById('wsModal');
        const wsForm = document.getElementById('wsForm');
        
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: window.innerWidth < 768 ? 'listMonth' : 'dayGridMonth',
            locale: 'vi',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: {
                today: 'Hôm nay',
                month: 'Tháng',
                week: 'Tuần',
                day: 'Ngày'
            },
            firstDay: 1, navLinks: true, selectable: true, dayMaxEvents: true,
            events: function(info, successCallback, failureCallback) {
                const employeeId = $('#filterEmployee').val();
                const deptId = $('#filterDept').val();
                let types = [];
                $('.legend-item.active').each(function() { types.push($(this).data('type')); });
                $.ajax({
                    url: '<?= base_url('work-schedules/events') ?>',
                    data: { start: info.startStr, end: info.endStr, employee_id: employeeId, dept_id: deptId, types: types.join(',') },
                    success: function(data) { successCallback(data); }
                });
            },
            select: function(info) {
                openModal('create', {
                    start_at: info.startStr.includes('T') ? info.startStr.substring(0, 16) : info.startStr + 'T08:00',
                    end_at: info.endStr.includes('T') ? info.endStr.substring(0, 16) : info.startStr + 'T17:00'
                });
            },
            eventClick: function(info) {
                if (info.event.id.toString().startsWith('leave_')) return;
                openModal('edit', info.event.id);
            },
            eventDidMount: function(info) {
                const props = info.event.extendedProps;
                let sourceHtml = '';
                if (props.type === 'leave') {
                    sourceHtml = `<div style="display: flex; align-items: center;"><i class="fas fa-user-tag" style="width: 25px; color: #e74c3c; font-size: 1.2rem;"></i><span style="color: #e74c3c; font-weight: 700;">Nghỉ cá nhân</span></div>`;
                } else {
                    if (props.assigner_name) {
                        sourceHtml = `<div style="display: flex; align-items: center;"><i class="fas fa-user-tag" style="width: 25px; color: blue; font-size: 1.2rem;"></i>  <span style="color: orange; font-weight: 700;">Nhận phân công: ${props.assigner_name}</span></div>`;
                    } else {
                        sourceHtml = `<div style="display: flex; align-items: center;"><i class="fas fa-user-tag" style="width: 25px; color: blue; font-size: 1.2rem;"></i> <span style="color: green; font-weight: 700;">Vụ việc cá nhân</span></div>`;
                    }
                }

                tippy(info.el, {
                    content: `<div style="padding: 15px; min-width: 320px; font-size: 1rem; line-height: 1.6; background: #fff;">
                                <div style="font-weight: 800; color: #007aff; border-bottom: 2px solid #f2f2f7; margin-bottom: 12px; padding-bottom: 8px; font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                                    ${props.type === 'leave' ? '🏖️' : (props.type === 'work' ? '🏠' : '🚗')} <span>[${props.type_label}]</span>
                                </div>
                                <div style="font-weight: 700; color: #000; margin-bottom: 10px; font-size: 1.1rem;">
                                    ${info.event.title.split(': ').slice(1).join(': ') || info.event.title}
                                </div>
                                <div style="display: grid; gap: 8px; color: #333;">
                                    <div style="display: flex; align-items: center;"><i class="fas fa-clock" style="width: 25px; color: #007aff; font-size: 1.1rem;"></i> <b>Thời gian:</b> &nbsp; ${props.time_display}</div>
                                    <div style="display: flex; align-items: center;"><i class="fas fa-calendar-alt" style="width: 25px; color: #007aff; font-size: 1.1rem;"></i> <b>Ngày:</b> &nbsp; ${props.date_display}</div>
                                    <div style="display: flex; align-items: center;"><i class="fas fa-map-marker-alt" style="width: 25px; color: #ff3b30; font-size: 1.1rem;"></i> <b>Địa điểm:</b> &nbsp; ${props.location || 'N/A'}</div>
                                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ddd;">
                                        <div style="display: flex; align-items: center; margin-bottom: 5px;"><i class="fas fa-user-tie" style="width: 25px; color: #5856d6; font-size: 1.1rem;"></i> <b>Thực hiện:</b> &nbsp; <span style="color: #007aff; font-weight: 700;">${props.employee_name}</span></div>
                                        ${sourceHtml}
                                    </div>
                                </div>
                              </div>`,
                    allowHTML: true, theme: 'light-border', placement: 'top', animation: 'fade', maxWidth: 400, zIndex: 9999, appendTo: document.body
                });
            }
        });
        calendar.render();

        $('#filterDept, #filterEmployee').on('change', () => calendar.refetchEvents());
        $('.legend-item').click(function() { $(this).toggleClass('active'); calendar.refetchEvents(); });

        function openModal(mode, data) {
            wsForm.reset(); $('#wsId').val(''); $('#btnDeleteWs').hide();
            if (mode === 'create') {
                $('#modalTitle').text('Tạo lịch trình làm việc');
                $('#wsStartDate').val(data.start_at.substring(0, 10));
                $('#wsStartTime').val(data.start_at.substring(11, 16));
                $('#wsEndDate').val(data.end_at.substring(0, 10));
                $('#wsEndTime').val(data.end_at.substring(11, 16));
                $('#wsEmployeeId').val('<?= $current_employee_id ?>').trigger('change');
            } else {
                $('#modalTitle').text('Chi tiết lịch trình');
                $.get('<?= base_url('work-schedules/detail/') ?>' + data, function(res) {
                    if (res.status === 'success') {
                        const d = res.data; $('#wsId').val(d.id); $('#wsEmployeeId').val(d.employee_id).trigger('change'); $('#wsAssignedById').val(d.assigned_by_id || '').trigger('change');
                        $('#wsType').val(d.type); $('#wsTitle').val(d.title); $('#wsLocation').val(d.location);
                        $('#wsStartDate').val(d.start_at.substring(0, 10)); $('#wsStartTime').val(d.start_at.substring(11, 16));
                        $('#wsEndDate').val(d.end_at.substring(0, 10)); $('#wsEndTime').val(d.end_at.substring(11, 16));
                        if (d.can_edit) $('#btnSaveWs').show(); else $('#btnSaveWs').hide();
                        if (d.can_delete) $('#btnDeleteWs').show(); else $('#btnDeleteWs').hide();
                    }
                });
            }
            wsModal.style.display = 'flex';
        }

        $('#btnOpenCreate').click(function() {
            const now = new Date();
            const startStr = now.toISOString().substring(0, 10) + 'T08:00';
            const endStr = now.toISOString().substring(0, 10) + 'T17:00';
            openModal('create', { start_at: startStr, end_at: endStr });
        });

        $('#btnCloseModal, #btnCancelModal, .modal-overlay').click(function(e) {
            if (e.target === this || this.id === 'btnCloseModal' || this.id === 'btnCancelModal') wsModal.style.display = 'none';
        });

        $('#wsForm').submit(function(e) {
            e.preventDefault();
            $('#wsStartAt').val($('#wsStartDate').val() + ' ' + $('#wsStartTime').val());
            $('#wsEndAt').val($('#wsEndDate').val() + ' ' + $('#wsEndTime').val());
            const id = $('#wsId').val();
            const url = id ? '<?= base_url('work-schedules/update/') ?>' + id : '<?= base_url('work-schedules/store') ?>';
            $.post(url, $(this).serialize(), function(res) {
                if (res.status === 'success') { wsModal.style.display = 'none'; calendar.refetchEvents(); }
                else alert(res.message);
            });
        });

        $('#btnDeleteWs').click(function() {
            if (confirm('Xóa lịch trình này?')) {
                const id = $('#wsId').val();
                $.post('<?= base_url('work-schedules/delete/') ?>' + id, function(res) {
                    if (res.status === 'success') { wsModal.style.display = 'none'; calendar.refetchEvents(); }
                });
            }
        });
    });
</script>
<?= $this->endSection() ?>
