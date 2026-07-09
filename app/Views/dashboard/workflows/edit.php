<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/workflows.css') ?>">
<?= $this->endSection() ?>

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
                <label class="form-label-premium">Tên quy trình nghiệp vụ</label>
                <input type="text" name="name" class="form-control-premium <?= (session('errors') && isset(session('errors')['name'])) ? 'is-invalid' : '' ?>" value="<?= old('name', $template['name']) ?>" required>
                <?php if (session('errors') && isset(session('errors')['name'])) : ?>
                    <div class="text-apple-red text-xxs m-t-5"><i class="fas fa-exclamation-circle"></i> <?= session('errors')['name'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-row m-b-24">
                <div class="form-group-premium flex-1 m-r-12">
                    <label class="form-label-premium">Mã định danh hệ thống (Code)</label>
                    <input type="text" name="code" class="form-control-premium text-monospace <?= (session('errors') && isset(session('errors')['code'])) ? 'is-invalid' : '' ?>" value="<?= old('code', $template['code']) ?>" required>
                    <?php if (session('errors') && isset(session('errors')['code'])) : ?>
                        <div class="text-apple-red text-xxs m-t-5"><i class="fas fa-exclamation-circle"></i> <?= session('errors')['code'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group-premium flex-1 m-l-12">
                    <label class="form-label-premium">Trạng thái vận hành</label>
                    <div class="flex-row align-center p-t-10">
                        <label class="switch-minimal">
                            <input type="checkbox" name="is_active" value="1" <?= old('is_active', $template['is_active']) ? 'checked' : '' ?>>
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


<?= $this->endSection() ?>
