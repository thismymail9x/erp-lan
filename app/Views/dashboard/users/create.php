<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-wrapper m-b-24">
    <div class="header-title-container">
        <h2 class="content-title">Tạo tài khoản</h2>
        <p class="content-subtitle">Thêm thông tin xác thực để nhân viên có thể truy cập hệ thống.</p>
    </div>
    <div class="header-controls">
        <a href="<?= base_url('users') ?>" class="btn-secondary-sm" title="Quay lại danh sách tài khoản">
            <i class="fas fa-chevron-left"></i> Quay lại
        </a>
    </div>
</div>

<div class="premium-card premium-card-centered-700">
    <form action="<?= base_url('users/store') ?>" method="POST" class="premium-form">
        <?= csrf_field() ?>
        
        <div class="form-grid">
            <div class="form-group-premium">
                <label for="email">Email đăng nhập</label>
                <input type="email" name="email" id="email" class="form-control-premium" required placeholder="nhanvien@lawfirm.erp" title="Địa chỉ email dùng để đăng nhập hệ thống">
            </div>

            <div class="form-group-premium">
                <label for="full_name">Họ và tên thành viên</label>
                <input type="text" name="full_name" id="full_name" class="form-control-premium" required placeholder="Ví dụ: Nguyễn Văn A" title="Tên đầy đủ của nhân sự sở hữu tài khoản">
            </div>

            <div class="form-group-premium">
                <label for="department_id">Phòng ban công tác</label>
                <select name="department_id" id="department_id" class="form-control-premium" required title="Lựa chọn phòng ban làm việc">
                    <option value="" disabled selected>-- Chọn phòng ban --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-premium">
                <label for="password">Mật khẩu ban đầu</label>
                <input type="password" name="password" id="password" class="form-control-premium" required placeholder="Tối thiểu 6 ký tự" title="Mật khẩu truy cập lần đầu">
            </div>

            <div class="form-group-premium">
                <label for="role_id">Gán vai trò hệ thống</label>
                <select name="role_id" id="role_id" class="form-control-premium" required title="Quyết định mức độ truy cập dữ liệu">
                    <option value="" disabled selected>-- Chọn vai trò --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-helper-text">Vai trò sẽ quyết định các khu vực chức năng nhân viên được phép truy cập.</p>
            </div>
        </div>

        <div class="form-actions-row">
            <button type="submit" class="btn-premium">
                <i class="fas fa-plus"></i>&nbsp; Khởi tạo tài khoản
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
