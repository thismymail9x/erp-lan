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
                           min="<?= date('Y-m-d') ?>" value="<?= old('start_date', date('Y-m-d')) ?>">
                </div>

                <div class="form-group-premium" id="end_date_group">
                    <label class="label-premium"><i class="fas fa-sign-in-alt"></i> Kết thúc <span class="text-red">*</span></label>
                    <input type="date" name="end_date" id="end_date" required class="form-control-premium"
                           min="<?= date('Y-m-d') ?>" value="<?= old('end_date', date('Y-m-d')) ?>">
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
                    <input type="text" name="reason" required class="form-control-premium" placeholder="VD: Việc gia đình, ốm đau..." value="<?= old('reason') ?>">
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
        
        // --- RÀO CHẮN THÔNG MINH (Smart Fence) ---
        // Tự động đẩy min của ngày kết thúc theo ngày bắt đầu
        if (startVal) {
            endDate.setAttribute('min', startVal);
        }

        if (isHalfDay) {
            endDateGroup.style.display = 'none';
            if (startVal) {
                endDate.value = startVal; // Đồng bộ end_date
            }
        } else {
            endDateGroup.style.display = 'block';
        }

        const endValActual = endDate.value;
        const start = new Date(startVal);
        const end = new Date(endValActual);
        const today = new Date();
        today.setHours(0,0,0,0);

        // 1. Kiểm tra logic ngày kết thúc (Hard Block)
        if (endValActual && startVal && end < start) {
            endDate.style.borderColor = "#ff3b30";
            endDate.style.background = "rgba(255, 59, 48, 0.05)";
            warningMessage.innerText = "Lỗi: Ngày kết thúc không thể sớm hơn ngày bắt đầu!";
            noticeWarning.style.display = 'flex';
            noticeWarning.className = "lan-status-box lan-status-error";
            submitBtn.disabled = true;
            submitBtn.style.opacity = "0.5";
            submitBtn.style.cursor = "not-allowed";
            return;
        } else {
            endDate.style.borderColor = "";
            endDate.style.background = "";
            noticeWarning.className = "lan-status-box lan-status-warning";
            submitBtn.disabled = false;
            submitBtn.style.opacity = "1";
            submitBtn.style.cursor = "pointer";
        }

        const diffTime = Math.abs(end - start);
        let daysToLeave = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        if (isHalfDay) {
            daysToLeave = 0.5;
        }
        
        if (!isNaN(daysToLeave) && endValActual && startVal) {
            totalDaysSpan.innerText = daysToLeave;
            calcSummary.style.display = 'block';
        } else {
            calcSummary.style.display = 'none';
        }
        
        // 2. Kiểm tra thời gian báo trước (Notice Period)
        const noticeTime = start - today;
        const noticeDays = Math.floor(noticeTime / (1000 * 60 * 60 * 24));
        
        let isValidNotice = true;
        let errMsg = "";

        if (startVal && endVal && !isEmergency.checked) {
            if (daysToLeave === 1 && noticeDays < 1) {
                isValidNotice = false;
                errMsg = "Nghỉ 1 ngày cần báo trước ít nhất 1 ngày làm việc.";
            } else if (daysToLeave >= 2 && daysToLeave < 5 && noticeDays < 3) {
                isValidNotice = false;
                errMsg = "Nghỉ từ 2-4 ngày cần báo trước ít nhất 3 ngày làm việc.";
            } else if (daysToLeave >= 5 && noticeDays < 7) {
                isValidNotice = false;
                errMsg = "Nghỉ từ 5 ngày trở lên cần báo trước ít nhất 7 ngày làm việc.";
            }
        }

        if (!isValidNotice) {
            warningMessage.innerText = errMsg;
            noticeWarning.style.display = 'flex';
            submitBtn.disabled = true;
            submitBtn.style.opacity = "0.5";
        } else if (endVal && startVal && end >= start) {
            // Chỉ hiển thị warning nếu vi phạm notice, nếu không phải ẩn đi (trừ trường hợp đã bị lỗi end < start ở trên)
            if (errMsg === "") {
                noticeWarning.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.style.opacity = "1";
            }
        }
    }

    startDate.addEventListener('change', checkValidation);
    endDate.addEventListener('change', checkValidation);
    isEmergency.addEventListener('change', checkValidation);
    
    document.querySelectorAll('input[name="leave_duration"]').forEach(radio => {
        radio.addEventListener('change', checkValidation);
    });
    
    checkValidation(); // Khởi tạo lần đầu

    // Select2
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        $('.select2-enable').select2({
            width: '100%'
        });
    }
</script>
<?= $this->endSection() ?>
