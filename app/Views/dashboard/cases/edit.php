<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-wrapper m-b-24">
    <div class="header-title-container">
        <h2 class="content-title">Chỉnh sửa hồ sơ vụ việc</h2>
        <p class="content-subtitle">Cập nhật thông tin hành chính & phân công nhân sự cho hồ sơ: <strong><?= esc($case['code']) ?></strong></p>
    </div>
    <div class="header-controls">
        <a href="<?= base_url('cases/show/' . $case['id']) ?>" class="btn-secondary-sm">
            <i class="fas fa-chevron-left"></i> Quay lại chi tiết
        </a>
    </div>
</div>

<div class="premium-card premium-card-centered-700">
    <form action="<?= base_url('cases/update/' . $case['id']) ?>" method="POST" class="premium-form">
        <?= csrf_field() ?>
        
        <div class="form-grid">
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
                    <small class="text-apple-orange m-t-4" style="line-height: 1.4; display: block;"><i class="fas fa-exclamation-triangle"></i> Đổi quy trình sẽ tự động thiết lập lại Timeline từ đầu.</small>
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

            <div class="form-group-premium">
                <label for="approvers">Người phê duyệt (Manager/Leader)</label>
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

            <div class="form-group-premium" style="grid-column: span 2;">
                <label for="supporters">Nhân sự hỗ trợ nghiệp vụ</label>
                <select name="supporters[]" id="supporters" class="form-control-premium select2-multi" multiple="multiple">
                    <?php 
                    $currentSupporterIds = array_column(array_filter($members, function($m) { return $m['role_in_case'] === 'supporter'; }), 'employee_id');
                    foreach ($staffs as $s) { ?>
                        <option value="<?= $s['id'] ?>" <?= in_array($s['id'], $currentSupporterIds) ? 'selected' : '' ?>><?= esc($s['full_name']) ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group-premium" style="grid-column: span 2;">
                <label for="description">Mô tả tóm lược vụ việc</label>
                <textarea name="description" id="description" class="form-control-premium" rows="4"><?= esc($case['description']) ?></textarea>
            </div>
        </div>

        <div class="form-actions-row">
            <button type="submit" class="btn-premium">
                <i class="fas fa-check-circle"></i>&nbsp; Cập nhật thông tin hồ sơ
            </button>
        </div>
    </form>
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
