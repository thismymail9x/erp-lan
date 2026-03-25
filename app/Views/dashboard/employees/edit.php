<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<?php 
    /**
     * PHÂN QUYỀN CHỈNH SỬA HỒ SƠ
     * - Admin, Mod và phòng Hành chính được sửa toàn bộ.
     * - Nhân viên chỉ được sửa thông tin cá nhân cơ bản.
     */
    $roleName = session()->get('role_name');
    $deptName = session()->get('department_name');

    // Kiểm tra quyền tối cao (Admin, Mod) hoặc phòng ban Hành chính thực hiện quản trị
    $canEditSensitive = (
        $roleName === \Config\AppConstants::ROLE_ADMIN || 
        $roleName === \Config\AppConstants::ROLE_MOD || 
        $deptName === \Config\AppConstants::DEPT_NAME_HANH_CHINH
    );

    // Chuỗi thuộc tính readonly/disabled cho các trường nhạy cảm (Lương, Chức vụ...)
    $restrictedAttr = !$canEditSensitive ? 'readonly style="background: #f8f9fa; cursor: not-allowed;"' : '';
    $restrictedSelect = !$canEditSensitive ? 'disabled style="background: #f8f9fa; cursor: not-allowed;"' : '';
?>
<div class="employee-edit-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Hồ sơ</h2>
            <p class="content-subtitle hide-mobile">Cập nhật hồ sơ: <strong><?= esc($employee['full_name']) ?></strong></p>
        </div>
        <div class="header-controls">
            <?php if ($canEditSensitive) { ?>
                <a href="<?= base_url('employees') ?>" class="btn-secondary-sm">
                    <i class="fas fa-chevron-left"></i> Quay lại
                </a>
            <?php } else { ?>
                <a href="<?= base_url('dashboard') ?>" class="btn-secondary-sm">
                    <i class="fas fa-home"></i> Trang chủ
                </a>
            <?php } ?>
        </div>
    </div>

    <div class="premium-card premium-card-centered-800">
        <form action="<?= base_url('employees/update/' . $employee['id']) ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>
            

            <div class="form-grid">
                <div class="form-group form-group-full">
                    <label for="full_name">Họ và tên</label>
                    <input type="text" name="full_name" id="full_name" required value="<?= esc($employee['full_name']) ?>" placeholder="Nhập họ và tên...">
                </div>

                <div class="form-group">
                    <label for="position">Chức vụ / Vị trí</label>
                    <input type="text" name="position" id="position" required value="<?= esc($employee['position']) ?>" placeholder="Ví dụ: Luật sư chính, Thư ký..." <?= $restrictedAttr ?>>
                    <?php if (!$canEditSensitive) { ?><small class="text-muted">Liên hệ Admin để thay đổi chức danh</small><?php } ?>
                </div>

                <div class="form-group">
                    <label for="salary_base">Mức lương cơ bản (VNĐ)</label>
                    <input type="number" name="salary_base" id="salary_base" required value="<?= (int)$employee['salary_base'] ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="department_id">Phòng ban công tác</label>
                    <?php if ($canEditSensitive) { ?>
                        <select name="department_id" id="department_id" required>
                            <option value="" disabled>-- Chọn phòng ban --</option>
                            <?php foreach ($departments as $d) { ?>
                                <option value="<?= $d['id'] ?>" <?= ($employee['department_id'] == $d['id']) ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                            <?php } ?>
                        </select>
                    <?php } else { ?>
                        <input type="text" class="form-control-premium" value="<?= esc($employee['department_name'] ?? 'Văn phòng') ?>" readonly style="background: #f8f9fa;">
                        <input type="hidden" name="department_id" value="<?= $employee['department_id'] ?>">
                    <?php } ?>
                </div>

                <div class="form-group">
                    <label for="join_date">Ngày vào làm</label>
                    <input type="date" name="join_date" id="join_date" required value="<?= $employee['join_date'] ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="dob">Ngày sinh</label>
                    <input type="date" name="dob" id="dob" value="<?= $employee['dob'] ?>">
                </div>

                <div class="form-group">
                    <label for="identity_card">Số CMND/CCCD</label>
                    <input type="text" name="identity_card" id="identity_card" value="<?= esc($employee['identity_card'] ?? '') ?>" placeholder="Nhập số định danh...">
                </div>

                <div class="form-group">
                    <label for="phone_number">Số điện thoại</label>
                    <input type="text" name="phone_number" id="phone_number" value="<?= esc($employee['phone_number'] ?? '') ?>" placeholder="090x.xxx.xxx">
                </div>

                <div class="form-group">
                    <label for="personal_email">Email cá nhân (Nếu có)</label>
                    <input type="email" name="personal_email" id="personal_email" value="<?= esc($employee['personal_email'] ?? '') ?>" placeholder="name@gmail.com">
                </div>

                <div class="form-group form-group-full">
                    <h4 class="m-t-20 m-b-10 text-apple-main"><i class="fas fa-university m-r-8"></i> Thông tin ngân hàng</h4>
                </div>

                <div class="form-group">
                    <label for="bank_name">Tên ngân hàng</label>
                    <input type="text" name="bank_name" id="bank_name" value="<?= esc($employee['bank_name'] ?? '') ?>" placeholder="Ví dụ: Vietcombank, Techcombank...">
                </div>

                <div class="form-group">
                    <label for="bank_account">Số tài khoản ngân hàng</label>
                    <input type="text" name="bank_account" id="bank_account" value="<?= esc($employee['bank_account'] ?? '') ?>" placeholder="Nhập số tài khoản...">
                </div>

                <div class="form-group form-group-full">
                    <label for="bank_owner">Tên chủ tài khoản</label>
                    <input type="text" name="bank_owner" id="bank_owner" value="<?= esc($employee['bank_owner'] ?? '') ?>" placeholder="NHẬN VIẾT HOA KHÔNG DẤU">
                </div>

                <div class="form-group form-group-full">
                    <label for="user_id">Liên kết tài khoản hệ thống</label>
                    <?php if ($canEditSensitive) { ?>
                        <select name="user_id" id="user_id">
                            <option value="">-- Không liên kết --</option>
                            <?php foreach ($unlinkedUsers as $u) { ?>
                                <option value="<?= $u['id'] ?>" <?= ($employee['user_id'] == $u['id']) ? 'selected' : '' ?>><?= esc($u['email']) ?></option>
                            <?php } ?>
                        </select>
                    <?php } else { ?>
                        <input type="text" class="form-control-premium" value="<?= esc(session()->get('email')) ?> - [<?= esc($employee['role_name'] ?? 'Chưa kết nối') ?>]" readonly style="background: #f8f9fa;">
                    <?php } ?>
                </div>

                <div class="form-group form-group-full">
                    <label for="address">Địa chỉ thường trú</label>
                    <input type="text" name="address" id="address" value="<?= esc($employee['address']) ?>" placeholder="Địa chỉ liên lạc đầy đủ...">
                </div>
            </div>

            <div class="form-actions-row">
                <button type="submit" class="btn-premium">
                    <i class="fas fa-save"></i>&nbsp; Cập nhật hồ sơ
                </button>
            </div>
        </form>

        <hr class="m-t-30 m-b-30">

        <!-- ĐỔI MẬT KHẨU -->
        <h3 class="section-header-title m-b-20"><i class="fas fa-shield-alt m-r-8 text-apple-red"></i> Đổi mật khẩu tài khoản</h3>
        <form action="<?= base_url('employees/change-password') ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>
            <div class="form-grid form-grid-3">
                <div class="form-group">
                    <label>Mật khẩu hiện tại</label>
                    <input type="password" name="old_password" required placeholder="Nhập mật khẩu đang dùng...">
                </div>
                <div class="form-group">
                    <label>Mật khẩu mới</label>
                    <input type="password" name="new_password" required minlength="6" placeholder="Tối thiểu 6 ký tự...">
                </div>
                <div class="form-group">
                    <label>Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirm_password" required placeholder="Nhập lại mật khẩu mới...">
                </div>
            </div>
            <div class="form-actions-row">
                <button type="submit" class="btn-premium">
                    <i class="fas fa-save"></i>&nbsp; Cập nhật mật khẩu
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
