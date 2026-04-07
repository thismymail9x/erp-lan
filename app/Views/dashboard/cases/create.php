<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="create-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title text-center">Khởi tạo vụ việc mới</h2>
            <p class="content-subtitle text-center">Thiết lập thông tin ban đầu cho hồ sơ pháp lý.</p>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('cases') ?>" class="btn-secondary-sm" title="Quay lại danh sách vụ việc">
                <i class="fas fa-arrow-left"></i>&nbsp; Quay lại
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-lg">
        <form action="<?= base_url('cases/store') ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>
            
            <div class="form-grid">
                <div class="form-group-premium">
                    <label for="title">Tên vụ việc / Tiêu đề hồ sơ <span style="color: #ff3b30;">*</span></label>
                    <input type="text" name="title" id="title" required class="form-control-premium" placeholder="Nhập tên vụ việc..." title="Tóm tắt ngắn gọn nội dung vụ việc">
                </div>
                <div class="form-group-premium">
                    <label for="customer_id">Khách hàng yêu cầu <span style="color: #ff3b30;">*</span></label>
                    <select name="customer_id" id="customer_id" required class="form-control-premium select2-enable" data-search="true" title="Chọn khách hàng chủ quản của vụ việc">
                        <option value="" disabled selected>-- Chọn khách hàng --</option>
                        <?php foreach ($customers as $c) { ?>
                            <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?> (<?= $c['phone'] ?: 'N/A' ?>)</option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="workflow_template_id">Quy trình xử lý (Template) <span style="color: #ff3b30;">*</span></label>
                    <select name="workflow_template_id" id="workflow_template_id" required class="form-control-premium select2-enable" data-search="true" title="Chọn quy trình nghiệp vụ áp dụng cho vụ việc này">
                        <option value="" disabled selected>-- Chọn quy trình mẫu --</option>
                        <?php foreach ($templates as $t) { ?>
                            <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?> (Dự kiến <?= $t['total_estimated_days'] ?> ngày)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group-premium">
                    <label for="priority">Mức độ ưu tiên <span style="color: #ff3b30;">*</span></label>
                    <select name="priority" id="priority" class="form-control-premium" title="Xác định độ khẩn cấp xử lý">
                        <option value="low">Thấp</option>
                        <option value="medium" selected>Trung bình</option>
                        <option value="high">Cao</option>
                        <option value="critical">Khẩn cấp</option>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="approvers">Người phê duyệt (Cấp Quản lý)</label>
                    <select name="approvers[]" id="approvers" class="form-control-premium select2-multi" multiple="multiple" style="width: 100%;" title="Cấp trên phê duyệt các bước quan trọng">
                        <?php foreach ($staffs as $s) { ?>
                            <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="assignees">Phụ trách chính (Chuyên môn)</label>
                    <select name="assignees[]" id="assignees" class="form-control-premium select2-multi" multiple="multiple" style="width: 100%;" title="Luật sư hoặc chuyên viên xử lý hồ sơ">
                        <?php foreach ($staffs as $s) { ?>
                            <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label for="supporters">Nhân sự hỗ trợ</label>
                    <select name="supporters[]" id="supporters" class="form-control-premium select2-multi" multiple="multiple" style="width: 100%;" title="Các cá nhân hỗ trợ thu thập hồ sơ, giấy tờ">
                        <?php foreach ($staffs as $s) { ?>
                            <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label for="description">Nội dung chi tiết vụ việc</label>
                    <textarea name="description" id="description" class="form-control-premium" rows="4" placeholder="Mô tả tóm tắt sự việc, yêu cầu của khách hàng..." title="Ghi chú chi tiết về bối cảnh và yêu cầu pháp lý"></textarea>
                </div>
            </div>

            <div class="form-actions-premium">
                <button type="submit" class="btn-premium btn-submit-premium">
                    <i class="fas fa-save"></i>&nbsp; Khởi tạo hồ sơ
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            $('.select2-multi').select2({
                placeholder: "Chọn nhân sự...",
                allowClear: true,
                width: '100%'
            });

            $('.select2-enable').select2({
                placeholder: "-- Chọn một lựa chọn --",
                allowClear: true,
                width: '100%'
            });
        }
    });
</script>
<?= $this->endSection() ?>
