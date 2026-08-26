<?php 
/**
 * GIAO DIỆN KHỞI TẠO ĐƠN NGHỈ PHÉP (Rule #8: Apple-Minimal & High Density)
 * Phụ trách: Tiếp nhận dữ liệu nghỉ phép, bàn giao công việc và chế độ khẩn cấp.
 * Áp dụng logic kiểm soát báo trước Rule 1.
 */
?>
<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="create-container" style="max-width: 900px; margin: 0 auto; padding: 10px;">
    <div class="dashboard-header-wrapper" style="margin-bottom: 15px;">
        <div class="header-title-container">
            <h2 class="content-title" style="font-size: 1.25rem;"><i class="fas fa-calendar-plus text-blue"></i>&nbsp; Tạo đơn nghỉ phép</h2>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('leave-requests') ?>" class="btn-secondary-sm">
                <i class="fas fa-times"></i>&nbsp; Hủy
            </a>
        </div>
    </div>

    <div class="premium-card" style="padding: 20px;">
        <form action="<?= base_url('leave-requests/store') ?>" method="POST" id="leaveForm" class="compact-form">
            <?= csrf_field() ?>
            
            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <?php if (!empty($canCreateForEmployee)) { ?>
                <div class="form-group-premium" style="grid-column: span 2;">
                    <label class="label-premium">Nh&#226;n s&#7921; ngh&#7881; <span class="text-red">*</span></label>
                    <select name="employee_id" required class="form-control-premium select2-enable">
                        <?php foreach ($staffs as $s) { ?>
                            <option value="<?= $s['id'] ?>" <?= old('employee_id', session()->get('employee_id')) == $s['id'] ? 'selected' : '' ?>>
                                <?= esc($s['full_name']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <?php } ?>
                <!-- Loại hình & Khẩn cấp -->
                <div class="form-group-premium" style="grid-column: span 2;">
                    <label class="label-premium">Hình thức nghỉ <span class="text-red">*</span></label>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <select name="leave_type" required class="form-control-premium select2-enable" style="flex: 1;">
                            <option value="">-- Chọn --</option>
                            <?php foreach ($leaveTypes as $key => $label) { ?>
                                <option value="<?= $key ?>" <?= old('leave_type') == $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php } ?>
                        </select>
                        <label class="emergency-toggle" title="Chế độ nghỉ khẩn cấp: Bỏ qua Rule báo trước">
                            <input type="checkbox" name="is_emergency" value="1" id="is_emergency">
                            <span class="text-red" style="font-weight: 700; font-size: 0.85rem;"><i class="fas fa-bolt"></i> NGHỈ GẤP</span>
                        </label>
                    </div>
                </div>

                <!-- Thời lượng (Full day / Half day) -->
                <div class="form-group-premium" style="grid-column: span 2;">
                    <label class="label-premium"><i class="fas fa-clock"></i> Thời lượng nghỉ <span class="text-red">*</span></label>
                    <div style="display: flex; gap: 15px;">
                        <label style="cursor: pointer;"><input type="radio" name="leave_duration" value="full_day" checked> Cả ngày (1 ngày)</label>
                        <label style="cursor: pointer;"><input type="radio" name="leave_duration" value="morning_half"> Nửa ngày sáng (0.5 ngày)</label>
                        <label style="cursor: pointer;"><input type="radio" name="leave_duration" value="afternoon_half"> Nửa ngày chiều (0.5 ngày)</label>
                    </div>
                </div>

                <!-- Thời gian -->
                <div class="form-group-premium">
                    <label class="label-premium"><i class="fas fa-sign-out-alt"></i> Bắt đầu <span class="text-red">*</span></label>
                    <input type="date" name="start_date" id="start_date" required class="form-control-premium"
                           <?= empty($canCreateForEmployee) ? 'min="' . date('Y-m-d') . '"' : '' ?> value="<?= old('start_date', date('Y-m-d')) ?>">
                </div>

                <div class="form-group-premium" id="end_date_group">
                    <label class="label-premium"><i class="fas fa-sign-in-alt"></i> Kết thúc <span class="text-red">*</span></label>
                    <input type="date" name="end_date" id="end_date" required class="form-control-premium"
                           <?= empty($canCreateForEmployee) ? 'min="' . date('Y-m-d') . '"' : '' ?> value="<?= old('end_date', date('Y-m-d')) ?>">
                </div>

                <!-- Bàn giao (Optional - Rule Update) -->
                <div class="form-group-premium">
                    <label class="label-premium"><i class="fas fa-user-friends"></i> Người nhận bàn giao</label>
                    <select name="handover_to" id="handover_to" class="form-control-premium select2-enable">
                        <option value="">-- Không bắt buộc --</option>
                        <?php foreach($staffs as $s) { ?>
                            <?php if ($s['id'] != session()->get('employee_id')) { ?>
                            <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label class="label-premium"><i class="fas fa-comment-dots"></i> Lý do nghỉ <span class="text-red">*</span></label>
                    <input type="text" name="reason" required class="form-control-premium" value="<?= old('reason') ?>">
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label class="label-premium"><i class="fas fa-tasks"></i> Công việc cần bàn giao</label>
                    <textarea name="handover_content" class="form-control-premium" rows="2" placeholder="Ghi chú các đầu việc cần hỗ trợ..."><?= old('handover_content') ?></textarea>
                </div>
            </div>

            <!-- Cảnh báo quy tắc (Rule 1) -->
            <div id="noticeWarning" class="lan-status-box lan-status-warning" style="display: none; padding: 10px; margin: 15px 0;">
                <i class="fas fa-info-circle" style="color: #ff9500;"></i>&nbsp;
                <span id="warningMessage" style="font-size: 0.85rem; font-weight: 500;"></span>
            </div>

            <div class="form-actions-premium" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f2f2f2;">
                <div id="calcSummary" class="compact-summary" style="display: none; margin-right: auto;">
                    <span class="badge-success-minimal"><i class="fas fa-calendar-day"></i> <span id="totalDays">0</span> ngày</span>
                </div>
                <button type="submit" class="btn-premium" style="padding: 8px 25px;">
                    <i class="fas fa-check"></i>&nbsp; XÁC NHẬN GỬI
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/leave_requests.js') ?>"></script>
<?= $this->endSection() ?>
