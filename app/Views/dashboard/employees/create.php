<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-actions" style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center;">
    <div class="header-title-container">
        <h2 class="content-title" style="margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.02em;">Thêm nhân viên mới</h2>
        <p class="content-subtitle" style="margin: 4px 0 0; color: var(--apple-text-muted);">Khởi tạo hồ sơ nhân sự mới vào hệ thống.</p>
    </div>
    <a href="<?= base_url('employees') ?>" class="btn-secondary-sm">
        <i class="fas fa-chevron-left" style="margin-right: 6px;"></i> Quay lại
    </a>
</div>

<div class="premium-card" style="max-width: 800px; margin: 0 auto;">
    <form action="<?= base_url('employees/store') ?>" method="POST" class="premium-form">
        <?= csrf_field() ?>
        
        <div class="form-grid">
            <div class="form-group" style="grid-column: span 2;">
                <label for="full_name">Họ và tên</label>
                <input type="text" name="full_name" id="full_name" required placeholder="Nhập họ và tên đầy đủ...">
            </div>

            <div class="form-group">
                <label for="position">Chức vụ / Vị trí</label>
                <input type="text" name="position" id="position" required placeholder="Ví dụ: Luật sư chính, Thư ký...">
            </div>

            <div class="form-group">
                <label for="salary_base">Mức lương cơ bản (VNĐ)</label>
                <input type="number" name="salary_base" id="salary_base" required value="0">
            </div>

            <div class="form-group">
                <label for="department_id">Phòng ban công tác</label>
                <select name="department_id" id="department_id" required>
                    <option value="" disabled selected>-- Chọn phòng ban --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="join_date">Ngày vào làm</label>
                <input type="date" name="join_date" id="join_date" required value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label for="identity_card">Số CMND/CCCD</label>
                <input type="text" name="identity_card" id="identity_card" placeholder="Nhập số định danh...">
            </div>

            <div class="form-group">
                <label for="bank_name">Tên ngân hàng</label>
                <input type="text" name="bank_name" id="bank_name" placeholder="Ví dụ: Vietcombank, Techcombank...">
            </div>

            <div class="form-group">
                <label for="bank_account">Số tài khoản ngân hàng</label>
                <input type="text" name="bank_account" id="bank_account" placeholder="Nhập số tài khoản...">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="user_id">Liên kết tài khoản hệ thống (Nếu có)</label>
                <select name="user_id" id="user_id">
                    <option value="">-- Không liên kết / Để sau --</option>
                    <?php foreach ($unlinkedUsers as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= esc($u['email']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p style="font-size: 0.82rem; color: var(--apple-text-muted); margin-top: 4px;">Chỉ hiển thị các tài khoản chưa được gán cho nhân viên nào.</p>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="address">Địa chỉ thường trú</label>
                <input type="text" name="address" id="address" placeholder="Địa chỉ liên lạc đầy đủ...">
            </div>
        </div>

        <div class="form-actions" style="margin-top: 32px; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn-premium" style="min-width: 160px;">
                <i class="fas fa-save" style="margin-right: 8px;"></i> Lưu hồ sơ
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
