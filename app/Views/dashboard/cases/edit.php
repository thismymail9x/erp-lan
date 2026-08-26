<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/cases.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="create-container case-edit-container case-form-container" data-payment-progress="<?= esc($case['payment_progress'] ?? '', 'attr') ?>">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title text-center">Chỉnh sửa hồ sơ vụ việc</h2>
            <p class="content-subtitle text-center">Cập nhật thông tin hành chính & phân công nhân sự cho hồ sơ: <strong><?= esc($case['code']) ?></strong></p>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('cases/show/' . $case['id']) ?>" class="btn-secondary-sm">
                <i class="fas fa-arrow-left"></i>&nbsp; Quay lại chi tiết
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-lg">
    <form action="<?= base_url('cases/update/' . $case['id']) ?>" method="POST" class="premium-form">
        <?= csrf_field() ?>
        
        <div class="form-grid case-edit-form-grid">
            <div class="form-group-premium">
                <label for="title">Tên vụ việc / Tiêu đề hồ sơ</label>
                <input type="text" name="title" id="title" required class="form-control-premium" value="<?= esc($case['title']) ?>">
            </div>
            
            <div class="form-group-premium">
                <label for="customer_id">Khách hàng chủ quản</label>
                <select name="customer_id" id="customer_id" required class="form-control-premium select2-enable" data-search="true">
                    <?php foreach ($customers as $c) { ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $case['customer_id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group-premium">
                <label for="workflow_template_id">Quy trình (Workflow) đang chạy</label>
                <?php 
                $roleName = session()->get('role_name');
                $deptId   = session()->get('department_id');
                $canChangeFlow = has_permission('sys.admin') || ($roleName === 'Trưởng phòng' && $deptId == 3);
                ?>
                <select name="workflow_template_id" id="workflow_template_id" class="form-control-premium <?= $canChangeFlow ? 'select2-enable' : '' ?>" <?= $canChangeFlow ? '' : 'disabled' ?>>
                    <?php foreach ($templates as $t) { ?>
                        <option value="<?= $t['id'] ?>" <?= $t['id'] == $case['workflow_template_id'] ? 'selected' : '' ?>><?= esc($t['name']) ?></option>
                    <?php } ?>
                </select>
                <?php if ($canChangeFlow) { ?>
                    <small class="text-apple-orange m-t-4 case-edit-help"><i class="fas fa-exclamation-triangle"></i> Đổi quy trình sẽ tự động thiết lập lại Timeline từ đầu.</small>
                <?php } else { ?>
                    <small class="text-danger-premium m-t-4"><i class="fas fa-lock"></i> Chỉ Trưởng phòng Pháp lý/Admin mới có quyền đổi quy trình.</small>
                <?php } ?>
            </div>

            <div class="form-group-premium">
                <label for="priority">Mức độ ưu tiên</label>
                <select name="priority" id="priority" class="form-control-premium">
                    <option value="low" <?= $case['priority'] == 'low' ? 'selected' : '' ?>>Thấp</option>
                    <option value="medium" <?= $case['priority'] == 'medium' ? 'selected' : '' ?>>Trung bình</option>
                    <option value="high" <?= $case['priority'] == 'high' ? 'selected' : '' ?>>Cao</option>
                    <option value="critical" <?= $case['priority'] == 'critical' ? 'selected' : '' ?>>Khẩn cấp</option>
                </select>
            </div>

            <div class="case-edit-span-2 case-edit-staff-grid">
                <div class="form-group-premium">
                    <label for="approvers">Người phê duyệt</label>
                    <select name="approvers[]" id="approvers" class="form-control-premium select2-multi" multiple="multiple">
                        <?php 
                        $currentApproverIds = array_column(array_filter($members, function($m) { return $m['role_in_case'] === 'approver'; }), 'employee_id');
                        foreach ($staffs as $s) { ?>
                            <option value="<?= $s['id'] ?>" <?= in_array($s['id'], $currentApproverIds) ? 'selected' : '' ?>><?= esc($s['full_name']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="assignees">Người thực hiện chính</label>
                    <select name="assignees[]" id="assignees" class="form-control-premium select2-multi" multiple="multiple">
                        <?php 
                        $currentAssigneeIds = array_column(array_filter($members, function($m) { return $m['role_in_case'] === 'assignee'; }), 'employee_id');
                        foreach ($staffs as $s) { ?>
                            <option value="<?= $s['id'] ?>" <?= in_array($s['id'], $currentAssigneeIds) ? 'selected' : '' ?>><?= esc($s['full_name']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="supporters">Nhân sự hỗ trợ nghiệp vụ</label>
                    <select name="supporters[]" id="supporters" class="form-control-premium select2-multi" multiple="multiple">
                        <?php 
                        $currentSupporterIds = array_column(array_filter($members, function($m) { return $m['role_in_case'] === 'supporter'; }), 'employee_id');
                        foreach ($staffs as $s) { ?>
                            <option value="<?= $s['id'] ?>" <?= in_array($s['id'], $currentSupporterIds) ? 'selected' : '' ?>><?= esc($s['full_name']) ?></option>
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
                            <option value="<?= $s['id'] ?>" <?= (int)($case['consultant_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group-premium">
                    <label for="consultation_closed_at">Ngày ghi nhận chốt</label>
                    <?php $closedAtValue = !empty($case['consultation_closed_at']) ? date('Y-m-d', strtotime($case['consultation_closed_at'])) : ''; ?>
                    <input type="date" name="consultation_closed_at" id="consultation_closed_at" class="form-control-premium" value="<?= esc($closedAtValue) ?>">
                    <small class="text-muted-dark m-t-4 case-kpi-note">KPI tháng được tính theo ngày ghi nhận chốt và giá trị hợp đồng.</small>
                </div>
            <?php } ?>

            <div class="form-group-premium case-edit-span-2">
                <label for="description">Kế hoạch vụ việc</label>
                <textarea name="description" id="description" class="form-control-premium" rows="4"><?= esc($case['description']) ?></textarea>
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
                    <input type="text" name="contract_value" id="contract_value" class="form-control-premium case-money-input js-vnd-input" value="<?= isset($case['contract_value']) ? number_format($case['contract_value'], 0, ',', '.') : '' ?>">
                    <small class="text-muted-dark m-t-4 case-note-block">Nhập số tiền đã chốt theo hợp đồng. Hệ thống sẽ tự động định dạng. Nhân viên pháp lý không nhìn thấy số liệu này.</small>
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
                <i class="fas fa-check-circle"></i>&nbsp; Cập nhật thông tin hồ sơ
            </button>
        </div>
    </form>
</div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/cases_edit.js') ?>"></script>
<?= $this->endSection() ?>



