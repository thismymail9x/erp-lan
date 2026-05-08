<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="create-container" style="max-width: 900px; margin: 0 auto; padding: 10px;">
    <div class="dashboard-header-wrapper" style="margin-bottom: 15px;">
        <div class="header-title-container">
            <h2 class="content-title" style="font-size: 1.25rem;"><i class="fas fa-edit text-blue"></i>&nbsp; Chỉnh sửa đơn nghỉ phép</h2>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('leave-requests') ?>" class="btn-secondary-sm">
                <i class="fas fa-times"></i>&nbsp; Hủy
            </a>
        </div>
    </div>

    <div class="premium-card" style="padding: 20px;">
        <div class="alert alert-info-minimal m-b-20" style="padding: 12px; border-radius: 8px; font-size: 13px;">
            <i class="fas fa-user-shield text-blue"></i> <b>Chế độ Quản trị:</b> Bạn đang chỉnh sửa đơn của <b><?= esc($request['employee_name'] ?? 'Nhân viên') ?></b>. 
            Mọi thay đổi về thời gian sẽ tự động tính toán lại và đồng bộ sang Chấm công nếu đơn ở trạng thái <b>Đã phê duyệt</b>.
        </div>

        <form action="<?= base_url('leave-requests/update/' . $request['id']) ?>" method="POST" id="leaveForm" class="compact-form">
            <?= csrf_field() ?>
            
            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <!-- Trạng thái (Chỉ Admin mới có ở form edit này) -->
                <div class="form-group-premium" style="grid-column: span 2;">
                    <label class="label-premium">Trạng thái hiện tại <span class="text-red">*</span></label>
                    <select name="status" id="status" class="form-control-premium" required>
                        <option value="pending" <?= ($request['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Đang chờ duyệt</option>
                        <option value="approved" <?= ($request['status'] ?? '') == 'approved' ? 'selected' : '' ?>>Đã phê duyệt</option>
                        <option value="rejected" <?= ($request['status'] ?? '') == 'rejected' ? 'selected' : '' ?>>Đã từ chối</option>
                        <option value="cancelled" <?= ($request['status'] ?? '') == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                    </select>
                </div>

                <!-- Loại hình -->
                <div class="form-group-premium" style="grid-column: span 2;">
                    <label class="label-premium">Hình thức nghỉ <span class="text-red">*</span></label>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <select name="leave_type" required class="form-control-premium select2-enable" style="flex: 1;">
                            <?php foreach ($leaveTypes as $key => $label) { ?>
                                <option value="<?= $key ?>" <?= ($request['leave_type'] ?? '') == $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php } ?>
                        </select>
                        <label class="emergency-toggle" title="Chế độ nghỉ khẩn cấp: Bỏ qua Rule báo trước">
                            <input type="checkbox" name="is_emergency" value="1" id="is_emergency" <?= ($request['is_emergency'] ?? 0) ? 'checked' : '' ?>>
                            <span class="text-red" style="font-weight: 700; font-size: 0.85rem;"><i class="fas fa-bolt"></i> NGHỈ GẤP</span>
                        </label>
                    </div>
                </div>

                <!-- Thời lượng (Full day / Half day) -->
                <div class="form-group-premium" style="grid-column: span 2;">
                    <label class="label-premium"><i class="fas fa-clock"></i> Thời lượng nghỉ <span class="text-red">*</span></label>
                    <div style="display: flex; gap: 15px;">
                        <label style="cursor: pointer;"><input type="radio" name="leave_duration" value="full_day" <?= ($request['leave_duration'] ?? 'full_day') == 'full_day' ? 'checked' : '' ?>> Cả ngày (1 ngày)</label>
                        <label style="cursor: pointer;"><input type="radio" name="leave_duration" value="morning_half" <?= ($request['leave_duration'] ?? '') == 'morning_half' ? 'checked' : '' ?>> Nửa ngày sáng (0.5 ngày)</label>
                        <label style="cursor: pointer;"><input type="radio" name="leave_duration" value="afternoon_half" <?= ($request['leave_duration'] ?? '') == 'afternoon_half' ? 'checked' : '' ?>> Nửa ngày chiều (0.5 ngày)</label>
                    </div>
                </div>

                <!-- Thời gian -->
                <div class="form-group-premium">
                    <label class="label-premium"><i class="fas fa-sign-out-alt"></i> Bắt đầu <span class="text-red">*</span></label>
                    <input type="date" name="start_date" id="start_date" required class="form-control-premium" 
                           value="<?= !empty($request['start_date']) ? date('Y-m-d', strtotime($request['start_date'])) : '' ?>">
                </div>

                <div class="form-group-premium" id="end_date_group">
                    <label class="label-premium"><i class="fas fa-sign-in-alt"></i> Kết thúc <span class="text-red">*</span></label>
                    <input type="date" name="end_date" id="end_date" required class="form-control-premium"
                           value="<?= !empty($request['end_date']) ? date('Y-m-d', strtotime($request['end_date'])) : '' ?>">
                </div>

                <!-- Bàn giao -->
                <div class="form-group-premium">
                    <label class="label-premium"><i class="fas fa-user-friends"></i> Người nhận bàn giao</label>
                    <select name="handover_to" id="handover_to" class="form-control-premium select2-enable">
                        <option value="">-- Không bắt buộc --</option>
                        <?php foreach($staffs as $s) { ?>
                            <?php if ($s['id'] != $request['employee_id']) { ?>
                            <option value="<?= $s['id'] ?>" <?= ($request['handover_to'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= esc($s['full_name']) ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label class="label-premium"><i class="fas fa-comment-dots"></i> Lý do nghỉ <span class="text-red">*</span></label>
                    <input type="text" name="reason" required class="form-control-premium" value="<?= esc($request['reason'] ?? '') ?>">
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label class="label-premium"><i class="fas fa-tasks"></i> Công việc cần bàn giao</label>
                    <textarea name="handover_content" class="form-control-premium" rows="2"><?= esc($request['handover_content'] ?? '') ?></textarea>
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label class="label-premium"><i class="fas fa-sticky-note"></i> Ghi chú phê duyệt (Nếu có)</label>
                    <textarea name="approval_note" class="form-control-premium" rows="2" placeholder="Lý do thay đổi hoặc ghi chú phê duyệt..."><?= esc($request['approval_note'] ?? '') ?></textarea>
                </div>
            </div>

            <div id="noticeWarning" class="lan-status-box lan-status-warning" style="display: none; padding: 10px; margin: 15px 0;">
                <i class="fas fa-info-circle" style="color: #ff9500;"></i>&nbsp;
                <span id="warningMessage" style="font-size: 0.85rem; font-weight: 500;"></span>
            </div>

            <div class="form-actions-premium" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f2f2f2;">
                <div id="calcSummary" class="compact-summary" style="margin-right: auto;">
                    <span class="badge-success-minimal"><i class="fas fa-calendar-day"></i> <span id="totalDays"><?= $request['total_days'] ?? 0 ?></span> ngày</span>
                </div>
                <button type="submit" class="btn-premium" style="padding: 8px 25px;">
                    <i class="fas fa-save"></i>&nbsp; LƯU THAY ĐỔI
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const isEmergency = document.getElementById('is_emergency');
    const totalDaysSpan = document.getElementById('totalDays');
    const calcSummary = document.getElementById('calcSummary');
    const noticeWarning = document.getElementById('noticeWarning');
    const warningMessage = document.getElementById('warningMessage');
    const submitBtn = document.querySelector('.btn-premium[type="submit"]');

    function checkValidation() {
        const startVal = startDate.value;
        const endVal = endDate.value;

        const durationRadios = document.querySelectorAll('input[name="leave_duration"]');
        let isHalfDay = false;
        durationRadios.forEach(radio => {
            if (radio.checked && radio.value !== 'full_day') {
                isHalfDay = true;
            }
        });

        const endDateGroup = document.getElementById('end_date_group');

        if (startVal) {
            endDate.setAttribute('min', startVal);
        }

        if (isHalfDay) {
            endDateGroup.style.display = 'none';
            if (startVal) {
                endDate.value = startVal; // Đồng bộ
            }
        } else {
            endDateGroup.style.display = 'block';
        }

        const endValActual = endDate.value;
        const start = new Date(startVal);
        const end = new Date(endValActual);

        if (endValActual && startVal && end < start) {
            endDate.style.borderColor = "#ff3b30";
            warningMessage.innerText = "Lỗi: Ngày kết thúc không thể sớm hơn ngày bắt đầu!";
            noticeWarning.style.display = 'flex';
            noticeWarning.className = "lan-status-box lan-status-error";
            submitBtn.disabled = true;
            return;
        } else {
            endDate.style.borderColor = "";
            noticeWarning.style.display = 'none';
            submitBtn.disabled = false;
        }

        const diffTime = Math.abs(end - start);
        let daysToLeave = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        if (isHalfDay) {
            daysToLeave = 0.5;
        }
        
        if (!isNaN(daysToLeave) && endValActual && startVal) {
            totalDaysSpan.innerText = daysToLeave;
            calcSummary.style.display = 'block';
        }
    }

    startDate.addEventListener('change', checkValidation);
    endDate.addEventListener('change', checkValidation);
    
    document.querySelectorAll('input[name="leave_duration"]').forEach(radio => {
        radio.addEventListener('change', checkValidation);
    });
    
    checkValidation();

    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        $('.select2-enable').select2({
            width: '100%'
        });
    }
</script>
<?= $this->endSection() ?>
