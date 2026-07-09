<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/workflows.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="workflow-steps-container" data-step-count="<?= count($steps) ?>">
    <div class="dashboard-header-wrapper m-b-24">
        <div class="header-title-container">
            <h2 class="content-title">Thiết lập bước: <?= esc($template['name']) ?></h2>
            <p class="content-subtitle">Xác định các giai đoạn, thời hạn và yêu cầu cho quy trình này.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('workflows') ?>" class="btn-secondary-sm">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <button type="button" class="btn-premium" onclick="saveWorkflowSteps()">
                <i class="fas fa-save"></i> Lưu quy trình
            </button>
        </div>
    </div>

    <div class="steps-builder-layout">
        <div class="steps-list-column">
            <div id="steps-container">
                <?php if (empty($steps)) { ?>
                    <div class="empty-steps-state premium-card p-30 text-center">
                        <p class="text-muted-dark m-b-15">Chưa có bước nào được thiết lập.</p>
                        <button type="button" class="btn-secondary-sm" onclick="addNewStep()">
                            <i class="fas fa-plus"></i> Thêm bước đầu tiên
                        </button>
                    </div>
                    </div>
                <?php } else { ?>
                    <?php foreach ($steps as $index => $step) { ?>
                        <div class="step-card premium-card m-b-15" data-index="<?= $index ?>">
                            <div class="step-card-header">
                                <div class="step-number">#<?= ($index + 1) ?></div>
                                <input type="text" name="steps[<?= $index ?>][step_name]" class="step-name-input" value="<?= esc($step['step_name']) ?>" placeholder="Tên bước (ví dụ: Soạn hồ sơ)" required>
                                <button type="button" class="btn-remove-step" onclick="removeStep(this)">
                                    <i class="far fa-trash-alt"></i>
                                </button>
                            </div>
                            <div class="step-card-body">
                                <div class="form-row-steps">
                                    <div class="form-group-mini">
                                        <label>Số ngày</label>
                                        <input type="number" name="steps[<?= $index ?>][duration_days]" value="<?= $step['duration_days'] ?>" min="1" required>
                                    </div>
                                    <div class="form-group-mini">
                                        <label>Thưởng hoàn thành (KPI)</label>
                                        <div class="currency-input-wrapper">
                                            <input type="text" name="steps[<?= $index ?>][kpi_reward]" class="input-currency" value="<?= number_format($step['kpi_reward'] ?? 0, 0, '.', ',') ?>" title="Thưởng hoàn thành (VND)">
                                            <span class="currency-label">VND</span>
                                        </div>
                                    </div>
                                    <div class="form-group-mini">
                                        <label>Người nhận thông báo</label>
                                        <?php 
                                            // Xử lý giá trị cũ (có thể là chuỗi hoặc json mảng)
                                            $selectedRoles = [];
                                            if (!empty($step['responsible_role'])) {
                                                $decoded = json_decode($step['responsible_role'], true);
                                                $selectedRoles = is_array($decoded) ? $decoded : [$step['responsible_role']];
                                            }
                                        ?>
                                        <select class="select2-multiple" name="steps[<?= $index ?>][responsible_role][]" multiple="multiple" style="width: 100%;">
                                             <optgroup label="Theo Vai trò">
                                                <?php foreach ($roles as $val => $lbl) { ?>
                                                    <option value="role:<?= $val ?>" <?= in_array("role:$val", $selectedRoles) ? 'selected' : '' ?>><?= $lbl ?></option>
                                                <?php } ?>
                                            </optgroup>
                                            <optgroup label="Cá nhân cụ thể">
                                                <?php foreach ($employees as $emp) { ?>
                                                    <option value="user:<?= $emp['id'] ?>" <?= in_array("user:{$emp['id']}", $selectedRoles) ? 'selected' : '' ?>>
                                                        <?= esc($emp['full_name']) ?> (<?= esc($emp['position']) ?>)
                                                    </option>
                                                <?php } ?>
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div class="form-group-mini flex-2">
                                        <label>Tài liệu bắt buộc (cách nhau bởi dấu phẩy)</label>
                                        <?php 
                                            // Chuyển mảng JSON sang string để dễ edit
                                            $docArray = json_decode($step['required_documents'], true) ?: [];
                                            $docString = implode(', ', $docArray);
                                        ?>
                                        <input type="text" name="steps[<?= $index ?>][required_documents_raw]" value="<?= esc($docString) ?>" placeholder="CMND, Đơn khởi kiện...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>

            <div class="add-step-wrapper m-t-20">
                <button type="button" class="btn-add-step-full" onclick="addNewStep()">
                    <i class="fas fa-plus-circle"></i> Thêm giai đoạn mới 
                </button>
            </div>
        </div>

        <div class="steps-info-column">
            <div class="premium-card p-20 sticky-top">
                <h4 class="m-0 m-b-15">Hướng dẫn thiết lập</h4>
                <ul class="guide-list">
                    <li><strong>Thứ tự:</strong> Các bước sẽ được thực hiện tuần tự theo danh sách bên trái.</li>
                    <li><strong>Ngày làm việc:</strong> Deadline sẽ tự động bỏ qua Thứ 7 và Chủ nhật.</li>
                    <li><strong>Tài liệu:</strong> Khi upload tài liệu, hệ thống sẽ tự động đối soát theo tên gợi nhớ bạn nhập.</li>
                    <li><strong>Vai trò:</strong> Xác định bộ phận sẽ nhận được thông báo khi đến bước này.</li>
                </ul>
            </div>
        </div>
    </div>
<template id="workflowRoleOptionsTemplate">
    <optgroup label="Theo Vai trò (Role)">
        <?php foreach ($roles as $val => $lbl) { ?>
            <option value="role:<?= $val ?>"><?= $lbl ?></option>
        <?php } ?>
    </optgroup>
    <optgroup label="Nhân viên cụ thể">
        <?php foreach ($employees as $emp) { ?>
            <option value="user:<?= $emp['id'] ?>">
                <?= esc($emp['full_name']) ?> (<?= esc($emp['position']) ?>)
            </option>
        <?php } ?>
    </optgroup>
</template>

    
    <form id="workflow-steps-form" action="<?= base_url('workflows/update-steps/' . $template['id']) ?>" method="POST" style="display:none;">
        <?= csrf_field() ?>
        <div id="hidden-inputs-container"></div>
    </form>
</div>



<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/workflows_steps.js') ?>"></script>
<?= $this->endSection() ?>
