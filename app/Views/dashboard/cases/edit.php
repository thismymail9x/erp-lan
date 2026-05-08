<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="create-container">
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
        
        <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
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

            <div style="grid-column: span 2; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
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

            <div class="form-group-premium" style="grid-column: span 2;">
                <label for="description">Kế hoạch vụ việc</label>
                <textarea name="description" id="description" class="form-control-premium" rows="4"><?= esc($case['description']) ?></textarea>
            </div>

            <?php
            $isHanhChinhOrAdmin = (session()->get('role_name') === \Config\AppConstants::ROLE_ADMIN || session()->get('department_id') == \Config\AppConstants::DEPT_HANH_CHINH);
            if ($isHanhChinhOrAdmin) { 
            ?>
                <div class="form-group-premium" style="grid-column: span 2; margin-top: 10px; border-top: 1px dashed var(--border-color); padding-top: 20px;">
                    <h4 class="text-apple-main font-weight-600 m-b-15"><i class="fas fa-file-invoice-dollar m-r-8 text-apple-blue"></i> Chuyên mục Hành chính - Kế toán</h4>
                </div>
                <div class="form-group-premium" style="grid-column: span 2;">
                    <label for="contract_value">Giá trị hợp đồng (VNĐ)</label>
                    <input type="text" name="contract_value" id="contract_value" class="form-control-premium" style="font-size: 1.1rem; font-weight: 600; color: var(--apple-blue);" value="<?= isset($case['contract_value']) ? number_format($case['contract_value'], 0, ',', '.') : '' ?>" onkeyup="this.value=this.value.replace(/[^\d]/g,'').replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
                    <small class="text-muted-dark m-t-4" style="display:block;">Nhập số tiền đã chốt theo hợp đồng. Hệ thống sẽ tự động định dạng. Nhân viên pháp lý không nhìn thấy số liệu này.</small>
                </div>
                
                <div class="form-group-premium" style="grid-column: span 2;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
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
        
        // --- Tài chính & Tiến độ thanh toán Repeater ---
        initPaymentRepeater();
    });

    function initPaymentRepeater() {
        const container = document.getElementById('payment-progress-container');
        const addBtn = document.getElementById('add-payment-btn');
        if (!container || !addBtn) return;

        let rowCount = 0;
        let existingData = [];
        <?php if (!empty($case['payment_progress'])) { ?>
            try {
                let parsed = JSON.parse(<?= json_encode($case['payment_progress']) ?>);
                if(Array.isArray(parsed)) existingData = parsed;
            } catch(e) {}
        <?php } ?>

        function addRow(data = null) {
            rowCount++;
            let title = data ? data.title : ('Lần ' + rowCount);
            let amount = data ? data.amount : '';
            let deadline = data ? data.deadline : '';
            
            let isPaidHtml = '';
            if (data && data.is_paid == '1') {
                isPaidHtml = 'checked';
            }
            
            const div = document.createElement('div');
            div.className = 'payment-row m-b-8';
            div.style.display = 'flex';
            div.style.gap = '10px';
            div.innerHTML = `
                <input type="text" name="payments[${rowCount}][title]" class="form-control-premium text-sm" value="${title}" placeholder="Tiêu đề (VD: Lần 1, Đặt cọc)">
                <input type="text" name="payments[${rowCount}][amount]" class="form-control-premium text-sm font-weight-600 text-apple-blue" value="${amount}" placeholder="Số tiền" onkeyup="this.value=this.value.replace(/[^\\d]/g,'').replace(/\\B(?=(\\d{3})+(?!\\d))/g, '.')">
                <input type="date" name="payments[${rowCount}][deadline]" class="form-control-premium text-sm" value="${deadline}" title="Thời hạn (Không bắt buộc)">
                <div style="display:flex; align-items:center; gap: 5px;">
                    <input type="checkbox" name="payments[${rowCount}][is_paid]" value="1" id="paid_${rowCount}" ${isPaidHtml} style="width:16px; height:16px; cursor:pointer;">
                    <label for="paid_${rowCount}" style="margin:0; font-size:12px; cursor:pointer;" class="text-apple-main">Đã thu</label>
                </div>
                <button type="button" class="btn-secondary-sm text-apple-red" onclick="this.parentElement.remove()" title="Xóa đợt thanh toán"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(div);
        }

        if (existingData.length > 0) {
            existingData.forEach(item => addRow(item));
        } else {
            addRow(); // Initial row
        }

        addBtn.addEventListener('click', () => addRow());
    }
</script>
<?= $this->endSection() ?>
