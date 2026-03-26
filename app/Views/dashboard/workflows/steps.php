<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="workflow-steps-container">
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
    
    <form id="workflow-steps-form" action="<?= base_url('workflows/update-steps/' . $template['id']) ?>" method="POST" style="display:none;">
        <?= csrf_field() ?>
        <div id="hidden-inputs-container"></div>
    </form>
</div>

<style>
.steps-builder-layout {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}
.steps-list-column { flex: 3; }
.steps-info-column { flex: 1; }
.sticky-top { position: sticky; top: 24px; }

.step-card {
    border-left: 4px solid var(--apple-blue);
    padding: 0;
    overflow: hidden;
}
.step-card-header {
    background: rgba(0,0,0,0.02);
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}
.step-number {
    font-weight: 800;
    color: var(--apple-blue);
    font-size: 1.1rem;
}
.step-name-input {
    flex: 1;
    background: transparent;
    border: none;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--apple-main);
    padding: 5px;
}
.step-name-input:focus {
    outline: none;
    background: rgba(255,255,255,0.8);
    border-radius: 4px;
}
.btn-remove-step {
    background: transparent;
    border: none;
    color: var(--apple-red);
    cursor: pointer;
    font-size: 1.1rem;
    opacity: 0.6;
    transition: opacity 0.2s;
}
.btn-remove-step:hover { opacity: 1; }

.step-card-body { padding: 20px; }
.form-row-steps {
    display: flex;
    gap: 15px;
}
.form-group-mini {
    display: flex;
    flex-direction: column;
    flex: 1;
}
.form-group-mini.flex-2 { flex: 2; }
.form-group-mini label {
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 700;
    color: var(--text-muted-dark);
    margin-bottom: 8px;
}
.form-group-mini input, .form-group-mini select {
    padding: 10px;
    border: 1px solid #e5e5ea;
    border-radius: 8px;
    font-size: 0.95rem;
}

.btn-add-step-full {
    width: 100%;
    padding: 15px;
    border: 2px dashed #d1d1d6;
    background: transparent;
    color: var(--text-muted-dark);
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
.btn-add-step-full:hover {
    border-color: var(--apple-blue);
    color: var(--apple-blue);
    background: rgba(0, 122, 255, 0.03);
}

.guide-list {
    padding-left: 18px;
    font-size: 0.9rem;
    line-height: 1.6;
    color: var(--apple-main);
}
.guide-list li { margin-bottom: 12px; }
</style>

<script>
    /**
     * L.A.N ERP - Trình khởi tạo Quy trình nghiệp vụ
     * Điều khiển việc thêm, xóa, sắp xếp và lưu trữ các giai đoạn trong một quy trình mẫu.
     */
    let stepCount = <?= count($steps) ?>; // Đếm số lượng bước hiện có

    /**
     * Thêm một giai đoạn mới vào cuối danh sách.
     * Tạo cấu trúc HTML động và khởi tạo Select2 cho các trường chọn mới.
     */
    function addNewStep() {
        const container = document.getElementById('steps-container');
        
        // Ẩn thông báo "Trống" nếu đây là bước đầu tiên
        const emptyState = container.querySelector('.empty-steps-state');
        if (emptyState) emptyState.remove();

        const index = stepCount;
        const stepHtml = `
            <div class="step-card premium-card m-b-15" data-index="${index}">
                <div class="step-card-header">
                    <div class="step-number">#${index + 1}</div>
                    <input type="text" name="steps[${index}][step_name]" class="step-name-input" value="" placeholder="Tên bước mới..." required>
                    <button type="button" class="btn-remove-step" onclick="removeStep(this)">
                        <i class="far fa-trash-alt"></i>
                    </button>
                </div>
                <div class="step-card-body">
                    <div class="form-row-steps">
                        <div class="form-group-mini">
                            <label>Số ngày định mức</label>
                            <input type="number" name="steps[${index}][duration_days]" value="3" min="1" required title="Số ngày dự kiến hoàn thành bước này">
                        </div>
                        <div class="form-group-mini">
                            <label>Phân quyền/Người phụ trách</label>
                            <select class="select2-multiple" name="steps[${index}][responsible_role][]" multiple="multiple" style="width: 100%;">
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
                            </select>
                        </div>
                        <div class="form-group-mini flex-2">
                            <label>Tài liệu cần có (Phân tách bằng dấu phẩy)</label>
                            <input type="text" name="steps[${index}][required_documents_raw]" value="" placeholder="Ví dụ: Đơn khởi kiện, CCCD, Bản án...">
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Chèn HTML mới vào container
        container.insertAdjacentHTML('beforeend', stepHtml);
        
        // Khởi tạo thư viện Select2 cho các element vừa tạo động
        $(`.step-card[data-index="${index}"] .select2-multiple`).select2({
            placeholder: "Chọn đối tượng xử lý...",
            allowClear: true
        });

        stepCount++;
        reorderSteps(); // Cập nhật lại số hiệu #1, #2...
    }

    /**
     * Xóa một giai đoạn khỏi quy trình.
     * @param {HTMLElement} btn - Nút xóa được nhấn.
     */
    function removeStep(btn) {
        if (confirm('Xác nhận xóa giai đoạn này? Thứ tự các bước sau nó sẽ được cập nhật lại.')) {
            // Xóa khối cha gần nhất có class .step-card
            const card = btn.closest('.step-card');
            if (card) {
                card.remove();
                reorderSteps();
            }
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    container.appendChild(input);
}
</script>
<?= $this->endSection() ?>
