<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="payroll-config-container">
    <div class="dashboard-header-wrapper">
        <div class="header-back-btn">
            <a href="<?= base_url('payroll?month=' . $month) ?>" class="btn-secondary-sm">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
        <div class="header-title-container">
            <h2 class="content-title">Thiết lập ngày công</h2>
            <p class="content-subtitle">Tháng <?= $month ?></p>
        </div>
    </div>

    <div class="create-container">
        <div class="premium-card">
            <form action="<?= base_url('payroll/config/' . $month) ?>" method="post">
                <p class="m-b-20 text-muted-dark">
                    Chọn các ngày nhân viên bắt buộc phải đi làm trong tháng này. 
                    Mặc định hệ thống đã gợi ý Thứ 7 cách tuần. Bạn có thể tích thêm hoặc bỏ bớt các ngày nghỉ lễ.
                </p>

                <div class="calendar-grid">
                    <?php 
                        $firstDayOfWeek = date('N', strtotime($month . '-01'));
                        $daysInMonth = date('t', strtotime($month . '-01'));
                    ?>
                    <div class="calendar-header-day">T2</div>
                    <div class="calendar-header-day">T3</div>
                    <div class="calendar-header-day">T4</div>
                    <div class="calendar-header-day">T5</div>
                    <div class="calendar-header-day">T6</div>
                    <div class="calendar-header-day text-blue">T7</div>
                    <div class="calendar-header-day text-red">CN</div>

                    <?php 
                        // Empty cells for padding
                        for ($i = 1; $i < $firstDayOfWeek; $i++) {
                            echo '<div class="calendar-day-empty"></div>';
                        }

                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $dateStr = $month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
                            $isChecked = in_array($dateStr, $currentWorkingDays) ? 'checked' : '';
                            $noteValue = isset($currentHolidays[$dateStr]) ? esc($currentHolidays[$dateStr]) : '';
                            
                            $isSunday = date('N', strtotime($dateStr)) == 7;
                            $dayClass = $isSunday ? 'day-sun' : '';
                            ?>
                            <div class="calendar-day-cell <?= $dayClass ?>">
                                <div class="day-top">
                                    <label class="day-label">
                                        <input type="checkbox" name="working_days[]" value="<?= $dateStr ?>" <?= $isChecked ?>>
                                        <span><?= $d ?></span>
                                    </label>
                                </div>
                                <div class="day-note">
                                    <label class="text-xs text-muted m-b-2 d-block" style="font-size: 9px; font-weight: 700; color: #86868b;">GHI CHÚ:</label>
                                    <input type="text" name="day_notes[<?= $dateStr ?>]" value="<?= $noteValue ?>" placeholder="Lý do..." title="Ghi chú ngày này (ví dụ: Nghỉ lễ, Làm bù)">
                                </div>
                            </div>
                        <?php } ?>
                </div>

                <div class="form-actions-premium">
                    <button type="submit" class="btn-blue-apple">
                        <i class="fas fa-save"></i> Lưu cấu hình ngày công
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 10px;
        background: #fbfbfd;
        padding: 15px;
        border-radius: 12px;
        border: 1px solid #d2d2d7;
    }
    .calendar-header-day {
        text-align: center;
        font-weight: 700;
        font-size: 13px;
        padding-bottom: 10px;
        color: #86868b;
    }
    .calendar-day-cell {
        min-height: 95px;
        background: #fff;
        border-radius: 8px;
        border: 1px solid #d2d2d7;
        display: flex;
        flex-direction: column;
        padding: 5px;
        transition: all 0.2s;
    }
    .calendar-day-cell:hover {
        border-color: var(--apple-blue);
        background: rgba(0, 113, 227, 0.05);
    }
    .day-top {
        display: flex;
        justify-content: flex-start;
        margin-bottom: 5px;
    }
    .day-label {
        display: flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
    }
    .day-label input {
        width: 16px;
        height: 16px;
    }
    .day-label span {
        font-size: 14px;
        font-weight: 600;
    }
    .day-note {
        margin-top: auto;
    }
    .day-note input {
        width: 100%;
        border: 1px solid #d2d2d7;
        background: #fbfbfd;
        border-radius: 4px;
        padding: 4px 6px;
        font-size: 11px;
        color: #1d1d1f;
        outline: none;
        transition: all 0.2s;
    }
    .day-note input:focus {
        background: #fff;
        border-color: var(--apple-blue);
        box-shadow: 0 0 0 2px rgba(0, 113, 227, 0.1);
    }
    .day-sun { background: #fff5f5; border-color: #ffc9c9; }
    .day-sun span { color: var(--apple-red); }
</style>
<?= $this->endSection() ?>
