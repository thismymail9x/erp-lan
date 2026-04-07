<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="create-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title text-center">Tạo tài khoản hệ thống</h2>
            <p class="content-subtitle text-center">Thêm thông tin xác thực để nhân viên có thể truy cập hệ thống.</p>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('users') ?>" class="btn-secondary-sm" title="Quay lại danh sách tài khoản">
                <i class="fas fa-arrow-left"></i>&nbsp; Quay lại
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-lg">
        <form action="<?= base_url('users/store') ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>
            
            <div class="form-grid">
                <div class="form-group-premium">
                    <label for="email">Email đăng nhập <span style="color: #ff3b30;">*</span></label>
                    <input type="email" name="email" id="email" class="form-control-premium" required placeholder="nhanvien@thismymail.com" title="Địa chỉ email dùng để đăng nhập hệ thống">
                </div>

                <div class="form-group-premium">
                    <label for="full_name">Họ và tên thành viên <span style="color: #ff3b30;">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control-premium" required placeholder="Nhập tên đầy đủ..." title="Tên đầy đủ của nhân sự sở hữu tài khoản">
                </div>

                <div class="form-group-premium">
                    <label for="department_id">Phòng ban công tác <span style="color: #ff3b30;">*</span></label>
                    <select name="department_id" id="department_id" class="form-control-premium" required title="Lựa chọn phòng ban làm việc">
                        <option value="" disabled selected>-- Chọn phòng ban --</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="password">Mật khẩu ban đầu <span style="color: #ff3b30;">*</span></label>
                    <input type="password" name="password" id="password" class="form-control-premium" required placeholder="Nhập mật khẩu..." title="Mật khẩu truy cập lần đầu">
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label for="role_id">Gán vai trò hệ thống <span style="color: #ff3b30;">*</span></label>
                    <select name="role_id" id="role_id" class="form-control-premium" required title="Quyết định mức độ truy cập dữ liệu">
                        <option value="" disabled selected>-- Chọn vai trò --</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-help-text">Vai trò sẽ quyết định các khu vực chức năng nhân viên được phép truy cập.</p>
                </div>
            </div>

            <div class="form-actions-premium">
                <button type="submit" class="btn-premium btn-submit-premium">
                    <i class="fas fa-plus-circle"></i>&nbsp; Khởi tạo tài khoản
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
