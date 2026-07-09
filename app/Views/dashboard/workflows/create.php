<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/workflows.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="create-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title text-center">Tạo Quy trình nghiệp vụ mới</h2>
            <p class="content-subtitle text-center">Bắt đầu bằng cách định nghĩa các thông tin cơ bản của quy trình mẫu.</p>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('workflows') ?>" class="btn-secondary-sm">
                <i class="fas fa-arrow-left"></i>&nbsp; Quay lại
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-lg">
        <form action="<?= base_url('workflows/store') ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>
            
            <div class="form-grid">
                <div class="form-group-premium" style="grid-column: span 2;">
                    <label class="label-premium">Tên quy trình <span style="color: #ff3b30;">*</span></label>
                    <input type="text" name="name" class="form-control-premium" placeholder="Ví dụ: Quy trình Xóa án tích 2026" required>
                    <p class="form-help-text">Tên gọi giúp bạn phân biệt với các quy trình nghiệp vụ khác.</p>
                </div>

                <div class="form-group-premium">
                    <label class="label-premium">Mã định danh (Code) <span style="color: #ff3b30;">*</span></label>
                    <input type="text" name="code" class="form-control-premium text-monospace" placeholder="XOA_AN_TICH_V1" required>
                </div>

                <div class="form-group-premium">
                    <label class="label-premium">Trạng thái hoạt động</label>
                    <div style="display: flex; align-items: center; padding-top: 10px; gap: 12px;">
                        <label class="switch-minimal">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span class="slider-round"></span>
                        </label>
                        <span style="font-size: 14px; font-weight: 500; color: #1d1d1f;">Cho phép sử dụng ngay</span>
                    </div>
                </div>
            </div>

            <div class="form-actions-premium">
                <button type="submit" class="btn-premium btn-submit-premium" style="min-width: 220px;">
                    Tiếp tục thiết lập các bước <i class="fas fa-chevron-right m-l-8" style="font-size: 12px;"></i>
                </button>
            </div>
        </form>
    </div>
</div>


<?= $this->endSection() ?>
