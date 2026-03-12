<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-actions" style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center;">
    <div class="header-title-container">
        <h2 class="content-title" style="margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.02em;">Cập nhật tài khoản</h2>
        <p class="content-subtitle" style="margin: 4px 0 0; color: var(--apple-text-muted);">Điều chỉnh thông tin và quyền hạn cho <strong><?= esc($user['email']) ?></strong></p>
    </div>
    <a href="<?= base_url('users') ?>" class="btn-secondary-sm">
        <i class="fas fa-chevron-left" style="margin-right: 6px;"></i> Quay lại
    </a>
</div>

<div class="premium-card" style="max-width: 700px; margin: 0 auto;">
    <form action="<?= base_url('users/update/' . $user['id']) ?>" method="POST" class="premium-form">
        <?= csrf_field() ?>
        
        <div class="form-grid">
            <div class="form-group" style="grid-column: span 2;">
                <label>Email đăng nhập</label>
                <input type="text" value="<?= esc($user['email']) ?>" disabled style="background-color: var(--apple-gray); cursor: not-allowed; opacity: 0.7; color: var(--apple-text-muted);">
                <p style="font-size: 0.85rem; color: var(--apple-text-muted); margin-top: 4px;">Email là định danh duy nhất và không thể thay đổi.</p>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="full_name">Họ và tên thành viên</label>
                <input type="text" name="full_name" id="full_name" required value="<?= esc($user['full_name'] ?? '') ?>" placeholder="Cập nhật tên nhân viên...">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="department_id">Phòng ban công tác</label>
                <select name="department_id" id="department_id" required>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $user['department_id'] == $d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if(isset($currentRoleName) && $currentRoleName == \Config\AppConstants::ROLE_ADMIN): ?>
            <div class="form-group" style="grid-column: span 2;">
                <label for="password">Mật khẩu mới</label>
                <input type="password" name="password" id="password" placeholder="Chỉ nhập nếu muốn thay đổi mật khẩu">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="active_status">Tình trạng tài khoản</label>
                <select name="active_status" id="active_status">
                    <option value="1" <?= $user['active_status'] == 1 ? 'selected' : '' ?>>Đang hoạt động</option>
                    <option value="0" <?= $user['active_status'] == 0 ? 'selected' : '' ?>>Khóa truy cập</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group" style="grid-column: span 2;">
                <label for="role_id">Vai trò & Quyền hạn</label>
                <select name="role_id" id="role_id" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $user['role_id'] == $r['id'] ? 'selected' : '' ?>><?= $r['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="margin-top: 40px; border-top: 1px solid var(--border-color); padding-top: 25px; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn-premium">
                Lưu các thay đổi
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
