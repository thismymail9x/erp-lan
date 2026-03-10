<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-actions">
    <div class="header-title-container">
        <h2 class="content-title">Cập nhật tài khoản</h2>
        <p class="content-subtitle">Điều chỉnh thông tin và quyền hạn cho <?= esc($user['email']) ?></p>
    </div>
    <a href="<?= base_url('users') ?>" class="btn-secondary-sm">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>
</div>

<div class="premium-card" style="max-width: 600px;">
    <form action="<?= base_url('users/update/' . $user['id']) ?>" method="POST" class="premium-form">
        <?= csrf_field() ?>
        
        <div class="form-grid">
            <div class="form-group" style="grid-column: span 2;">
                <label>Email đăng nhập</label>
                <input type="text" value="<?= esc($user['email']) ?>" disabled style="background-color: #f5f5f7; cursor: not-allowed; opacity: 0.8;">
                <p style="font-size: 0.8rem; color: #86868b; margin-top: 5px;">Email không thể thay đổi sau khi tạo.</p>
            </div>

            <?php if($currentRoleName == \Config\AppConstants::ROLE_ADMIN): ?>
            <div class="form-group" style="grid-column: span 2;">
                <label for="password">Mật khẩu mới (Để trống nếu không muốn đổi)</label>
                <input type="password" name="password" id="password" placeholder="Nhập để đổi mật khẩu...">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="active_status">Trạng thái hoạt động</label>
                <select name="active_status" id="active_status">
                    <option value="1" <?= $user['active_status'] == 1 ? 'selected' : '' ?>>Đang hoạt động</option>
                    <option value="0" <?= $user['active_status'] == 0 ? 'selected' : '' ?>>Khóa tài khoản</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group" style="grid-column: span 2;">
                <label for="role_id">Thay đổi Vai trò</label>
                <select name="role_id" id="role_id" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $user['role_id'] == $r['id'] ? 'selected' : '' ?>><?= $r['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 25px;">
            <button type="submit" class="btn-premium">Lưu thay đổi</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
