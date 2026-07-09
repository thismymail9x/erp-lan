<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/cases.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="create-container case-create-container case-form-container">
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
            
            <?php if (session()->getFlashdata('errors')) { ?>
                <div class="lan-status-box lan-status-error m-b-24">
                    <i class="fas fa-exclamation-circle lan-box-icon"></i>
                    <div>
                        <?php foreach (session()->getFlashdata('errors') as $err) { ?>
                            <p class="m-0"><?= esc($err) ?></p>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <?php if (session()->getFlashdata('error')) { ?>
                <div class="lan-status-box lan-status-error m-b-24">
                    <i class="fas fa-exclamation-circle lan-box-icon"></i>
                    <p class="m-0"><?= esc(session()->getFlashdata('error')) ?></p>
                </div>
            <?php } ?>
            <div class="form-grid case-edit-form-grid">
                <div class="form-group-premium">
                    <label for="title">Tên vụ việc / Tiêu đề hồ sơ <span class="text-apple-red">*</span></label>
                    <input type="text" name="title" id="title" required class="form-control-premium" placeholder="Nhập tên vụ việc..." title="Tóm tắt ngắn gọn nội dung vụ việc">
                </div>
                <div class="form-group-premium">
                    <label for="customer_id">Khách hàng yêu cầu <span class="text-apple-red">*</span></label>
                    <select name="customer_id" id="customer_id" required class="form-control-premium select2-enable" data-search="true" title="Chọn khách hàng chủ quản của vụ việc">
                        <option value="" disabled selected>-- Chọn khách hàng --</option>
                        <?php foreach ($customers as $c) { ?>
                            <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?> (<?= $c['phone'] ?: 'N/A' ?>)</option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="workflow_template_id">Quy trình xử lý (Template) <span class="text-apple-red">*</span></label>
                    <select name="workflow_template_id" id="workflow_template_id" required class="form-control-premium select2-enable" data-search="true" title="Chọn quy trình nghiệp vụ áp dụng cho vụ việc này">
                        <option value="" disabled selected>-- Chọn quy trình mẫu --</option>
                        <?php foreach ($templates as $t) { ?>
                            <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?> (Dự kiến <?= $t['total_estimated_days'] ?> ngày)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group-premium">
                    <label for="priority">Mức độ ưu tiên <span class="text-apple-red">*</span></label>
                    <select name="priority" id="priority" class="form-control-premium" title="Xác định độ khẩn cấp xử lý">
                        <option value="low">Thấp</option>
                        <option value="medium" selected>Trung bình</option>
                        <option value="high">Cao</option>
                        <option value="critical">Khẩn cấp</option>
                    </select>
                </div>

                <div class="case-edit-span-2 case-edit-staff-grid">
                    <div class="form-group-premium">
                        <label for="approvers">Người phê duyệt (Quản lý)</label>
                        <select name="approvers[]" id="approvers" class="form-control-premium select2-multi" multiple="multiple" title="Cấp trên phê duyệt các bước quan trọng">
                            <?php foreach ($staffs as $s) { ?>
                                <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group-premium">
                        <label for="assignees">Phụ trách (Chuyên môn)</label>
                        <select name="assignees[]" id="assignees" class="form-control-premium select2-multi" multiple="multiple" title="Luật sư hoặc chuyên viên xử lý hồ sơ">
                            <?php foreach ($staffs as $s) { ?>
                                <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group-premium">
                        <label for="supporters">Nhân sự hỗ trợ</label>
                        <select name="supporters[]" id="supporters" class="form-control-premium select2-multi" multiple="multiple" title="Các cá nhân hỗ trợ thu thập hồ sơ, giấy tờ">
                            <?php foreach ($staffs as $s) { ?>
                                <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <?php if (has_permission('sys.admin') || has_permission('kpi.consulting')) { ?>
                    <div class="form-group-premium case-kpi-section">
                        <h4 class="text-apple-main font-weight-600 m-b-15"><i class="fas fa-chart-line m-r-8 text-apple-blue"></i> KPI tư vấn</h4>
                    </div>
                    <div class="form-group-premium">
                        <label for="consultant_id">Nhân sự tư vấn chốt khách</label>
                        <select name="consultant_id" id="consultant_id" class="form-control-premium select2-enable" data-search="true">
                            <option value="">-- Chưa ghi nhận --</option>
                            <?php foreach ($staffs as $s) { ?>
                                <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group-premium">
                        <label for="consultation_closed_at">Ngày ghi nhận chốt</label>
                        <input type="date" name="consultation_closed_at" id="consultation_closed_at" class="form-control-premium">
                        <small class="text-muted-dark m-t-4 case-kpi-note">Nếu để trống, hệ thống tự lấy thời điểm lưu hồ sơ khi đã chọn nhân sự tư vấn.</small>
                    </div>
                <?php } ?>

                <div class="form-group-premium case-edit-span-2">
                    <label for="description">Nội dung chi tiết vụ việc</label>
                    <textarea name="description" id="description" class="form-control-premium" rows="4" placeholder="Mô tả tóm tắt sự việc, yêu cầu của khách hàng..." title="Ghi chú chi tiết về bối cảnh và yêu cầu pháp lý"></textarea>
                </div>

                <?php 
                $isHanhChinhOrAdmin = (session()->get('role_name') === \Config\AppConstants::ROLE_ADMIN || session()->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH);
                if ($isHanhChinhOrAdmin) { 
                ?>
                    <div class="form-group-premium case-edit-span-2 case-edit-section-heading">
                        <h4 class="text-apple-main font-weight-600 m-b-15"><i class="fas fa-file-invoice-dollar m-r-8 text-apple-blue"></i> Chuyên mục Hành chính - Kế toán</h4>
                    </div>
                    <div class="form-group-premium case-edit-span-2">
                        <label for="contract_value">Giá trị hợp đồng (VNĐ)</label>
                        <input type="text" name="contract_value" id="contract_value" class="form-control-premium case-money-input js-vnd-input" placeholder="Ví dụ: 50.000.000">
                    </div>
                    
                    <div class="form-group-premium case-edit-span-2">
                        <div class="case-payment-header">
                            <label>Tiến độ thanh toán</label>
                            <button type="button" class="btn-secondary-sm text-xs" id="add-payment-btn"><i class="fas fa-plus"></i> Thêm</button>
                        </div>
                        <div id="payment-progress-container">
                            <!-- Dynamic fields injected via JS -->
                        </div>
                    </div>
                <?php } ?>
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
<script src="<?= base_url('js/cases_edit.js') ?>"></script>
<?= $this->endSection() ?>

