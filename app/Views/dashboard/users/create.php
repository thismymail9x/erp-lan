<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-actions" style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center;">
    <div class="header-title-container">
        <h2 class="content-title" style="margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.02em;">Tạo tài khoản mới</h2>
        <p class="content-subtitle" style="margin: 4px 0 0; color: var(--apple-text-muted);">Thêm thông tin xác thực để nhân viên có thể truy cập hệ thống.</p>
    </div>
    <a href="<?= base_url('users') ?>" class="btn-secondary-sm">
        <i class="fas fa-chevron-left" style="margin-right: 6px;"></i> Quay lại
    </a>
</div>

<div class="premium-card" style="max-width: 700px; margin: 0 auto;">
    <form action="<?= base_url('users/store') ?>" method="POST" class="premium-form">
        <?= csrf_field() ?>
        
        <div class="form-grid">
            <div class="form-group" style="grid-column: span 2;">
                <label for="email">Email đăng nhập</label>
                <input type="email" name="email" id="email" required placeholder="nhanvien@lawfirm.erp">
                <p style="font-size: 0.85rem; color: var(--apple-text-muted); margin-top: 4px;">Sử dụng email công ty để đảm bảo tính bảo mật.</p>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="full_name">Họ và tên thành viên</label>
                <input type="text" name="full_name" id="full_name" required placeholder="Nhập tên đầy đủ của nhân viên...">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="department_id">Phòng ban công tác</label>
                <select name="department_id" id="department_id" required>
                    <option value="" disabled selected>-- Chọn phòng ban --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="password">Mật khẩu ban đầu</label>
                <input type="password" name="password" id="password" required placeholder="Tối thiểu 6 ký tự">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="role_id">Gán vai trò hệ thống</label>
                <select name="role_id" id="role_id" required>
                    <option value="" disabled selected>-- Chọn vai trò --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <p style="font-size: 0.85rem; color: var(--apple-text-muted); margin-top: 4px;">Vai trò sẽ quyết định các khu vực chức năng nhân viên được phép truy cập.</p>
            </div>
        </div>

        <div style="margin-top: 40px; border-top: 1px solid var(--border-color); padding-top: 25px; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn-premium">
                <i class="fas fa-plus"></i>&nbsp; Khởi tạo tài khoản
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
