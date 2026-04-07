<?= $this->extend('layouts/dashboard') ?>

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

<style>
/* Minimal Switch Style */
.switch-minimal {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}
.switch-minimal input { opacity: 0; width: 0; height: 0; }
.slider-round {
  position: absolute;
  cursor: pointer;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: #e5e5ea;
  transition: .4s;
  border-radius: 24px;
}
.slider-round:before {
  position: absolute;
  content: "";
  height: 18px; width: 18px;
  left: 3px; bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
input:checked + .slider-round { background-color: #34c759; }
input:checked + .slider-round:before { transform: translateX(20px); }
</style>
<?= $this->endSection() ?>
