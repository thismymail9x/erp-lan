<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-actions">
    <div class="header-title-container">
        <h2 class="content-title">Tạo tài khoản mới</h2>
        <p class="content-subtitle">Thêm thông tin xác thực để có thể truy cập hệ thống.</p>
    </div>
    <a href="<?= base_url('users') ?>" class="btn-secondary-sm">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>
</div>

<div class="premium-card" style="max-width: 600px;">
    <form action="<?= base_url('users/store') ?>" method="POST" class="premium-form">
        <?= csrf_field() ?>
        
        <div class="form-grid">
            <div class="form-group" style="grid-column: span 2;">
                <label for="email">Email đăng nhập</label>
                <input type="email" name="email" id="email" required placeholder="example@lawfirm.erp">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="password">Mật khẩu</label>
                <input type="password" name="password" id="password" required placeholder="Ít nhất 6 ký tự">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="role_id">Chức danh (Vai trò)</label>
                <select name="role_id" id="role_id" required>
                    <option value="" disabled selected>-- Chọn chức danh --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 25px;">
            <button type="submit" class="btn-premium">Tạo tài khoản</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
