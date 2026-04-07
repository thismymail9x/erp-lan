<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="create-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title text-center">Gửi Trao đổi & Ý kiến</h2>
            <p class="content-subtitle text-center">Gửi chỉ đạo, đóng góp ý kiến hoặc nhắc nhở đến nhân sự khác trong hệ thống.</p>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('notifications') ?>" class="btn-secondary-sm">
                <i class="fas fa-arrow-left"></i>&nbsp; Quay lại
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-lg">
        <form action="<?= base_url('notifications/store') ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>
            
            <?php if (has_permission('sys.admin')) { ?>
                <div class="form-group-premium m-b-24">
                    <label class="label-premium">Đối tượng nhận thông báo <span style="color: #ff3b30;">*</span></label>
                    <div style="display: flex; gap: 30px; margin-top: 10px;">
                        <label class="radio-apple">
                            <input type="radio" name="target_type" value="individual" checked onclick="toggleRecipientFields('individual')">
                            <span>Cá nhân</span>
                        </label>
                        <label class="radio-apple">
                            <input type="radio" name="target_type" value="department" onclick="toggleRecipientFields('department')">
                            <span>Phòng ban</span>
                        </label>
                        <label class="radio-apple">
                            <input type="radio" name="target_type" value="all" onclick="toggleRecipientFields('all')">
                            <span>Toàn công ty</span>
                        </label>
                    </div>
                </div>

                <div id="departmentField" class="form-group-premium m-b-24" style="display:none;">
                    <label class="label-premium">Chọn phòng ban <span style="color: #ff3b30;">*</span></label>
                    <select name="department_id" class="form-control-premium select2-enable" style="width:100%;">
                        <option value="">-- Chọn phòng ban nhận --</option>
                        <?php foreach ($departments as $d) { ?>
                            <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                        <?php } ?>
                    </select>
                </div>
            <?php } ?>

            <div id="individualField" class="form-group-premium m-b-24">
                <label class="label-premium">Người nhận (Chọn 1 hoặc nhiều) <span style="color: #ff3b30;">*</span></label>
                <select name="user_ids[]" class="form-control-premium select2-enable" multiple="multiple" required style="width:100%;">
                    <?php foreach ($staffs as $s) { ?>
                        <?php if ($s['user_id'] != session()->get('user_id')) { ?>
                            <option value="<?= $s['user_id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                        <?php } ?>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group-premium m-b-24">
                <label class="label-premium">Tiêu đề thông báo <span style="color: #ff3b30;">*</span></label>
                <input type="text" name="title" class="form-control-premium" required 
                       placeholder="Ví dụ: Góp ý về quy trình, Nhắc nhở hồ sơ gấp..." 
                       maxlength="200">
            </div>

            <div class="form-group-premium">
                <label class="label-premium">Nội dung chi tiết <span style="color: #ff3b30;">*</span></label>
                <textarea name="message" class="form-control-premium" required 
                          placeholder="Mô tả chi tiết ý kiến hoặc chỉ đạo của bạn..." 
                          style="height: 180px; line-height: 1.6;"></textarea>
            </div>

            <div class="form-actions-premium">
                <button type="submit" class="btn-premium btn-submit-premium">
                    <i class="fas fa-paper-plane"></i>&nbsp; Gửi thông báo đi
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        $('.select2-enable').select2({
            placeholder: "-- Vui lòng chọn --",
            allowClear: true,
            width: '100%'
        });
    }
});

function toggleRecipientFields(type) {
    if (type === 'individual') {
        $('#individualField').show();
        $('#departmentField').hide();
        $('.select2-enable[name="user_ids[]"]').attr('required', true);
    } else if (type === 'department') {
        $('#individualField').hide();
        $('#departmentField').show();
        $('.select2-enable[name="user_ids[]"]').attr('required', false);
    } else {
        $('#individualField').hide();
        $('#departmentField').hide();
        $('.select2-enable[name="user_ids[]"]').attr('required', false);
    }
}
</script>
<style>
.radio-apple {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: 500;
}
.radio-apple input {
    width: 20px;
    height: 20px;
    cursor: pointer;
}
</style>
<?= $this->endSection() ?>
