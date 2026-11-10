<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/cases.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="create-container case-edit-container case-form-container" data-payment-progress="<?= esc($case['payment_progress'] ?? '', 'attr') ?>">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title text-center">Hành chính - Kế toán vụ việc</h2>
            <p class="content-subtitle text-center">Cập nhật giá trị hợp đồng và tiến độ thanh toán: <strong><?= esc($case['code']) ?></strong></p>
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
            <?= view('dashboard/cases/consulting_fields', ['case' => $case, 'staffs' => $staffs ?? []]) ?>
            <?php
            $isHanhChinhOrAdmin = (new \App\Services\CaseAccountingAccess())->canManage($case);
            if ($isHanhChinhOrAdmin) { 
            ?>
                <div class="form-group-premium case-edit-span-2 case-edit-section-heading">
                    <h4 class="text-apple-main font-weight-600 m-b-15"><i class="fas fa-file-invoice-dollar m-r-8 text-apple-blue"></i> Chuyên mục Hành chính - Kế toán</h4>
                </div>
                <div class="form-group-premium case-edit-span-2">
                    <input type="hidden" name="accounting_section" value="1">
                    <label for="contract_value">Giá trị hợp đồng (VNĐ)</label>
                    <input type="text" name="contract_value" id="contract_value" class="form-control-premium case-money-input js-vnd-input" value="<?= isset($case['contract_value']) ? number_format($case['contract_value'], 0, ',', '.') : '' ?>">
                    <small class="text-muted-dark m-t-4 case-note-block">Nhập số tiền đã chốt theo hợp đồng. Hệ thống sẽ tự động định dạng.</small>
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
<script src="<?= base_url('js/cases_edit.js') ?>?v=<?= time() ?>"></script>
<?= $this->endSection() ?>


