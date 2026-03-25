<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-wrapper m-b-24">
    <div class="header-title-container">
        <h2 class="content-title">Khởi tạo vụ việc mới</h2>
        <p class="content-subtitle">Thiết lập thông tin ban đầu cho hồ sơ pháp lý.</p>
    </div>
    <div class="header-controls">
        <a href="<?= base_url('cases') ?>" class="btn-secondary-sm" title="Quay lại danh sách vụ việc">
            <i class="fas fa-chevron-left"></i> Quay lại
        </a>
    </div>
</div>

<div class="premium-card premium-card-centered-700">
    <form action="<?= base_url('cases/store') ?>" method="POST" class="premium-form">
        <?= csrf_field() ?>
        
        <div class="form-grid">
            <div class="form-group-premium">
                <label for="customer_id">Khách hàng yêu cầu</label>
                <select name="customer_id" id="customer_id" required class="form-control-premium select2-enable" title="Chọn khách hàng chủ quản của vụ việc">
                    <option value="" disabled selected>-- Chọn khách hàng --</option>
                    <?php foreach ($customers as $c) { ?>
                        <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?> (<?= $c['phone'] ?: 'N/A' ?>)</option>
                    <?php } ?>
                </select>
                <p class="form-helper-text">Chọn khách hàng đã tồn tại trong hệ thống.</p>
            </div>

            <div class="form-group-premium">
                <label for="title">Tên vụ việc / Tiêu đề hồ sơ</label>
                <input type="text" name="title" id="title" required class="form-control-premium" placeholder="Ví dụ: Giải quyết tranh chấp đất đai tại Quận 1..." title="Tóm tắt ngắn gọn nội dung vụ việc">
            </div>

            <div class="form-group-premium">
                <label for="code">Mã số hồ sơ</label>
                <input type="text" name="code" id="code" required class="form-control-premium" placeholder="TTDS-2024-001" value="<?= 'CASE-'.date('Y').'-'.rand(100,999) ?>" title="Mã định danh duy nhất của hồ sơ">
            </div>

            <div class="form-group-premium" style="grid-column: span 2;">
                <label for="workflow_template_id">Quy trình xử lý (Template)</label>
                <select name="workflow_template_id" id="workflow_template_id" required class="form-control-premium select2-enable" title="Chọn quy trình nghiệp vụ áp dụng cho vụ việc này">
                    <option value="" disabled selected>-- Chọn quy trình mẫu --</option>
                    <?php foreach ($templates as $t) { ?>
                        <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?> (Dự kiến <?= $t['total_estimated_days'] ?> ngày)</option>
                    <?php } ?>
                </select>
                <p class="form-helper-text">Quy trình này sẽ tự động xác định loại hình vụ việc, các bước thực hiện và thời hạn hoàn thành.</p>
            </div>

            <div class="form-group-premium">
                <label for="priority">Mức độ ưu tiên</label>
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

        <div class="form-actions-row">
            <button type="submit" class="btn-premium">
                <i class="fas fa-save"></i>&nbsp; Lưu và Khởi tạo hồ sơ
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            $('.select2-multi').select2({
                placeholder: "Chọn nhân sự...",
                allowClear: true
            });
            $('.select2-enable').select2({
                placeholder: "Chọn mục...",
                allowClear: true
            });
        }
    });
</script>
<?= $this->endSection() ?>
