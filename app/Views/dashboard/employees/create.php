<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-actions">
    <div class="header-title-container">
        <h2 class="content-title">Thêm nhân viên mới</h2>
        <p class="content-subtitle">Nhập thông tin hồ sơ để khởi tạo nhân viên mới.</p>
    </div>
    <a href="<?= base_url('employees') ?>" class="btn-secondary-sm">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>
</div>

<div class="premium-card" style="max-width: 800px;">
    <form action="<?= base_url('employees/store') ?>" method="POST" class="premium-form">
        <?= csrf_field() ?>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="full_name">Họ và tên</label>
                <input type="text" name="full_name" id="full_name" required placeholder="Nhập họ và tên...">
            </div>

            <div class="form-group">
                <label for="position">Chức vụ</label>
                <input type="text" name="position" id="position" required placeholder="Ví dụ: Luật sư chính, Thư ký...">
            </div>

            <div class="form-group">
                <label for="join_date">Ngày vào làm</label>
                <input type="date" name="join_date" id="join_date" required value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label for="salary_base">Mức lương cơ bản</label>
                <input type="number" name="salary_base" id="salary_base" required value="0">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="address">Địa chỉ</label>
                <input type="text" name="address" id="address" placeholder="Địa chỉ liên lạc...">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-premium">Lưu hồ sơ</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
