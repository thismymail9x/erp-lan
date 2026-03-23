<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="employee-edit-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">Sửa nhân sự</h2>
            <p class="content-subtitle hide-mobile">Cập nhật hồ sơ: <strong><?= esc($employee['full_name']) ?></strong></p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('employees') ?>" class="btn-secondary-sm">
                <i class="fas fa-chevron-left"></i> Quay lại
            </a>
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
                    <input type="text" name="position" id="position" required value="<?= esc($employee['position']) ?>" placeholder="Ví dụ: Luật sư chính, Thư ký...">
                </div>

                <div class="form-group">
                    <label for="salary_base">Mức lương cơ bản (VNĐ)</label>
                    <input type="number" name="salary_base" id="salary_base" required value="<?= (int)$employee['salary_base'] ?>">
                </div>

                <div class="form-group">
                    <label for="department_id">Phòng ban công tác</label>
                    <select name="department_id" id="department_id" required>
                        <option value="" disabled>-- Chọn phòng ban --</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($employee['department_id'] == $d['id']) ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="join_date">Ngày vào làm</label>
                    <input type="date" name="join_date" id="join_date" required value="<?= $employee['join_date'] ?>">
                </div>

                <div class="form-group">
                    <label for="identity_card">Số CMND/CCCD</label>
                    <input type="text" name="identity_card" id="identity_card" value="<?= esc($employee['identity_card'] ?? '') ?>" placeholder="Nhập số định danh...">
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
                    <label for="user_id">Liên kết tài khoản hệ thống</label>
                    <select name="user_id" id="user_id">
                        <option value="">-- Không liên kết --</option>
                        <?php foreach ($unlinkedUsers as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($employee['user_id'] == $u['id']) ? 'selected' : '' ?>><?= esc($u['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-helper-text">Liên kết này giúp nhân viên khi đăng nhập có thể thấy đúng hồ sơ của mình.</p>
                </div>

                <div class="form-group form-group-full">
                    <label for="address">Địa chỉ thường trú</label>
                    <input type="text" name="address" id="address" value="<?= esc($employee['address']) ?>" placeholder="Địa chỉ liên lạc đầy đủ...">
                </div>
            </div>

            <div class="form-actions-row">
                <button type="submit" class="btn-premium">
                    <i class="fas fa-check"></i>&nbsp; Cập nhật hồ sơ
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
