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
let stepCount = <?= count($steps) ?>;

function addNewStep() {
    const container = document.getElementById('steps-container');
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
                        <label>Số ngày</label>
                        <input type="number" name="steps[${index}][duration_days]" value="3" min="1" required>
                    </div>
                    <div class="form-group-mini">
                        <label>Người nhận thông báo</label>
                        <select class="select2-multiple" name="steps[${index}][responsible_role][]" multiple="multiple" style="width: 100%;">
                            <optgroup label="Theo Vai trò">
                                <?php foreach ($roles as $val => $lbl) { ?>
                                    <option value="role:<?= $val ?>"><?= $lbl ?></option>
                                <?php } ?>
                            </optgroup>
                            <optgroup label="Cá nhân cụ thể">
                                <?php foreach ($employees as $emp) { ?>
                                    <option value="user:<?= $emp['id'] ?>">
                                        <?= esc($emp['full_name']) ?> (<?= esc($emp['position']) ?>)
                                    </option>
                                <?php } ?>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-group-mini flex-2">
                        <label>Tài liệu bắt buộc (cách nhau bởi dấu phẩy)</label>
                        <input type="text" name="steps[${index}][required_documents_raw]" value="" placeholder="Ví dụ: Đơn, Chứng cứ...">
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', stepHtml);
    
    // Khởi tạo Select2 cho element mới
    $(`.step-card[data-index="${index}"] .select2-multiple`).select2({
        placeholder: "Chọn vai trò hoặc nhân sự...",
        allowClear: true
    });

    stepCount++;
    reorderSteps();
}

function removeStep(btn) {
    if (confirm('Xóa giai đoạn này?')) {
        btn.closest('.step-card').remove();
        reorderSteps();
    }
}

function reorderSteps() {
    const cards = document.querySelectorAll('.step-card');
    cards.forEach((card, i) => {
        card.querySelector('.step-number').textContent = `#${i + 1}`;
        card.dataset.index = i;
        // Update input names for proper indexing on server
        card.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                // Handle multiple select brackets []
                const baseName = name.split('[')[2].split(']')[0]; // gets step_name, responsible_role etc
                const isMultiple = name.endsWith('[]');
                input.setAttribute('name', `steps[${i}][${baseName}]${isMultiple ? '[]' : ''}`);
            }
        });
    });
    stepCount = cards.length;
}

function saveWorkflowSteps() {
    const form = document.getElementById('workflow-steps-form');
    const hiddenContainer = document.getElementById('hidden-inputs-container');
    hiddenContainer.innerHTML = '';

    const cards = document.querySelectorAll('.step-card');
    if (cards.length === 0) {
        alert('Hãy thiết lập ít nhất một bước quy trình.');
        return;
    }

    cards.forEach((card, i) => {
        const stepName = card.querySelector('input[name*="[step_name]"]').value;
        const duration = card.querySelector('input[name*="[duration_days]"]').value;
        const docsRaw = card.querySelector('input[name*="[required_documents_raw]"]').value;
        
        // Thu thập các giá trị từ Select2 multiple
        const selectedValues = $(card).find('select[name*="[responsible_role]"]').val() || [];

        // Process document list into array
        const docArray = docsRaw.split(',').map(d => d.trim()).filter(d => d !== '');

        appendHidden(hiddenContainer, `steps[${i}][step_name]`, stepName);
        appendHidden(hiddenContainer, `steps[${i}][duration_days]`, duration);
        
        // Gửi mảng người nhận
        selectedValues.forEach((val, valIdx) => {
            appendHidden(hiddenContainer, `steps[${i}][responsible_role][${valIdx}]`, val);
        });
        
        docArray.forEach((doc, docIdx) => {
            appendHidden(hiddenContainer, `steps[${i}][required_documents][${docIdx}]`, doc);
        });
    });

    form.submit();
}

// Khởi tạo Select2 cho các bản ghi đã có sẵn
$(document).ready(function() {
    $('.select2-multiple').select2({
        placeholder: "Chọn vai trò hoặc nhân sự...",
        allowClear: true
    });
});

function appendHidden(container, name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    container.appendChild(input);
}
</script>
<?= $this->endSection() ?>
