<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="workflow-edit-container">
    <div class="dashboard-header-wrapper m-b-24">
        <div class="header-title-container">
            <h2 class="content-title">Chỉnh sửa Quy trình</h2>
            <p class="content-subtitle">Cập nhật thông tin cơ bản cho quy trình <strong><?= esc($template['name']) ?></strong>.</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('workflows') ?>" class="btn-secondary-sm">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="premium-card p-30 max-w-700 m-auto">
        <form action="<?= base_url('workflows/update/' . $template['id']) ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>
            
            <div class="form-group-premium m-b-24">
                <label class="form-label-premium">Tên quy trình</label>
                <input type="text" name="name" class="form-control-premium" value="<?= esc($template['name']) ?>" required>
            </div>

            <div class="form-row m-b-24">
                <div class="form-group-premium flex-1 m-r-12">
                    <label class="form-label-premium">Mã định danh (Code)</label>
                    <input type="text" name="code" class="form-control-premium text-monospace" value="<?= esc($template['code']) ?>" required>
                </div>
                <div class="form-group-premium flex-1 m-l-12">
                    <label class="form-label-premium">Trạng thái</label>
                    <div class="flex-row align-center p-t-10">
                        <label class="switch-minimal">
                            <input type="checkbox" name="is_active" value="1" <?= $template['is_active'] ? 'checked' : '' ?>>
                            <span class="slider-round"></span>
                        </label>
                        <span class="m-l-10 text-sm">Đang hoạt động</span>
                    </div>
                </div>
            </div>

            <div class="form-actions-row m-t-40">
                <button type="submit" class="btn-premium w-100">
                    Cập nhật thông tin <i class="fas fa-check m-l-8"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.max-w-700 { max-width: 700px; }
.m-auto { margin-left: auto; margin-right: auto; }
.flex-row { display: flex; }
.flex-1 { flex: 1; }
.align-center { align-items: center; }

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
