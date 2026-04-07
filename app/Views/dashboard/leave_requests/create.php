<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="create-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title text-center">Khởi tạo Đơn nghỉ phép</h2>
            <p class="content-subtitle text-center">Lưu ý: Đơn của bạn sẽ được gửi tới Ban quản lý hoặc Trưởng phòng để xem xét.</p>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('leave-requests') ?>" class="btn-secondary-sm">
                <i class="fas fa-arrow-left"></i>&nbsp; Quay lại
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-lg">
        <!-- Hiển thị thông báo (nếu có) -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="lan-status-box lan-status-success m-b-20">
                <i class="fas fa-check-circle lan-box-icon" style="font-size: 24px; color: #34c759;"></i>
                <p class="m-0"><?= session()->getFlashdata('success') ?></p>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="lan-status-box lan-status-error m-b-20">
                <i class="fas fa-exclamation-triangle lan-box-icon" style="font-size: 24px; color: #ff3b30;"></i>
                <p class="m-0"><?= session()->getFlashdata('error') ?></p>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="lan-status-box lan-status-error m-b-20 text-left">
                <ul class="m-0 p-l-20">
                    <?php foreach (session()->getFlashdata('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('leave-requests/store') ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>
            
            <div class="form-grid">
                <div class="form-group-premium" style="grid-column: span 2;">
                    <label for="leave_type">Hình thức nghỉ phép <span style="color: #ff3b30;">*</span></label>
                    <select name="leave_type" id="leave_type" required class="form-control-premium select2-enable shadow-sm">
                        <option value="" disabled <?= !old('leave_type') ? 'selected' : '' ?>>-- Chọn loại hình nghỉ --</option>
                        <?php foreach ($leaveTypes as $key => $label) { ?>
                            <option value="<?= $key ?>" <?= old('leave_type') == $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="start_date">Từ ngày (Bắt đầu) <span style="color: #ff3b30;">*</span></label>
                    <input type="date" name="start_date" id="start_date" required class="form-control-premium" 
                           min="<?= date('Y-m-d') ?>" value="<?= old('start_date', date('Y-m-d')) ?>">
                </div>

                <div class="form-group-premium">
                    <label for="end_date">Tới ngày (Kết thúc) <span style="color: #ff3b30;">*</span></label>
                    <input type="date" name="end_date" id="end_date" required class="form-control-premium"
                           min="<?= date('Y-m-d') ?>" value="<?= old('end_date', date('Y-m-d')) ?>">
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label for="reason">Lý do nghỉ phép <span style="color: #ff3b30;">*</span></label>
                    <textarea name="reason" id="reason" required class="form-control-premium" rows="5" 
                              placeholder="Tối thiểu 10 ký tự "
                              title="Lý do chi tiết giúp ban quản lý phê duyệt đơn nhanh hơn"><?= old('reason') ?></textarea>
                </div>
            </div>

            <div class="form-actions-premium">
                <div id="calcSummary" class="text-sm font-weight-600 color-secondary" style="display: none; margin-right: auto;">
                    <i class="fas fa-calendar-check color-success"></i>&nbsp; Tổng cộng: <span id="totalDays">0</span> ngày nghỉ
                </div>
                <button type="submit" class="btn-premium btn-submit-premium">
                    <i class="fas fa-paper-plane"></i>&nbsp; Gửi đơn nghỉ phép
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
    const totalDays = document.getElementById('totalDays');
    const calcSummary = document.getElementById('calcSummary');

    function calculateDiff() {
        const d1 = new Date(startDate.value);
        const d2 = new Date(endDate.value);

        if (d1 && d2 && d2 >= d1) {
            const diffTime = Math.abs(d2 - d1);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            totalDays.innerText = diffDays;
            calcSummary.style.display = 'block';
        } else {
            calcSummary.style.display = 'none';
        }
    }

    startDate.addEventListener('change', calculateDiff);
    endDate.addEventListener('change', calculateDiff);
    calculateDiff(); 

    // Select2 integration
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        $('.select2-enable').select2({
            width: '100%',
            placeholder: "-- Chọn một loại hình --"
        });
    }
</script>
<?= $this->endSection() ?>
