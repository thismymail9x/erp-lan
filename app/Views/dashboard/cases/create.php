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
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
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

                <div style="grid-column: span 2; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div class="form-group-premium">
                        <label for="approvers">Người phê duyệt (Quản lý)</label>
                        <select name="approvers[]" id="approvers" class="form-control-premium select2-multi" multiple="multiple" style="width: 100%;" title="Cấp trên phê duyệt các bước quan trọng">
                            <?php foreach ($staffs as $s) { ?>
                                <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group-premium">
                        <label for="assignees">Phụ trách (Chuyên môn)</label>
                        <select name="assignees[]" id="assignees" class="form-control-premium select2-multi" multiple="multiple" style="width: 100%;" title="Luật sư hoặc chuyên viên xử lý hồ sơ">
                            <?php foreach ($staffs as $s) { ?>
                                <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group-premium">
                        <label for="supporters">Nhân sự hỗ trợ</label>
                        <select name="supporters[]" id="supporters" class="form-control-premium select2-multi" multiple="multiple" style="width: 100%;" title="Các cá nhân hỗ trợ thu thập hồ sơ, giấy tờ">
                            <?php foreach ($staffs as $s) { ?>
                                <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label for="description">Nội dung chi tiết vụ việc</label>
                    <textarea name="description" id="description" class="form-control-premium" rows="4" placeholder="Mô tả tóm tắt sự việc, yêu cầu của khách hàng..." title="Ghi chú chi tiết về bối cảnh và yêu cầu pháp lý"></textarea>
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
                        <input type="text" name="contract_value" id="contract_value" class="form-control-premium" style="font-size: 1.1rem; font-weight: 600; color: var(--apple-blue);" placeholder="Ví dụ: 50.000.000" onkeyup="this.value=this.value.replace(/[^\d]/g,'').replace(/\B(?=(\d{3})+(?!\d))/g, '.')">
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

        // --- Tài chính & Tiến độ thanh toán Repeater ---
        initPaymentRepeater();
    });

    function initPaymentRepeater() {
        const container = document.getElementById('payment-progress-container');
        const addBtn = document.getElementById('add-payment-btn');
        if (!container || !addBtn) return;

        let rowCount = 0;

        function addRow() {
            rowCount++;
            const div = document.createElement('div');
            div.className = 'payment-row m-b-8';
            div.style.display = 'flex';
            div.style.gap = '10px';
            div.innerHTML = `
                <input type="text" name="payments[${rowCount}][title]" class="form-control-premium text-sm" value="Lần ${rowCount}" placeholder="Tiêu đề (VD: Lần 1, Đặt cọc)">
                <input type="text" name="payments[${rowCount}][amount]" class="form-control-premium text-sm font-weight-600 text-apple-blue" placeholder="Số tiền" onkeyup="this.value=this.value.replace(/[^\\d]/g,'').replace(/\\B(?=(\\d{3})+(?!\\d))/g, '.')">
                <input type="date" name="payments[${rowCount}][deadline]" class="form-control-premium text-sm" title="Thời hạn (Không bắt buộc)">
                <div style="display:flex; align-items:center; gap: 5px;">
                    <input type="checkbox" name="payments[${rowCount}][is_paid]" value="1" id="paid_${rowCount}" style="width:16px; height:16px; cursor:pointer;">
                    <label for="paid_${rowCount}" style="margin:0; font-size:12px; cursor:pointer;" class="text-apple-main">Đã thu</label>
                </div>
                <button type="button" class="btn-secondary-sm text-apple-red" onclick="this.parentElement.remove()" title="Xóa đợt thanh toán"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(div);
        }

        addRow(); // Initial row
        addBtn.addEventListener('click', () => addRow());
    }
</script>
<?= $this->endSection() ?>
