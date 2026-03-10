<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-actions">
    <div class="header-title-container">
        <h2 class="content-title">Chỉnh sửa nhân viên</h2>
        <p class="content-subtitle">Cập nhật thông tin hồ sơ cho: <?= $employee['full_name'] ?></p>
    </div>
    <a href="<?= base_url('employees') ?>" class="btn-secondary-sm">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>
</div>

<div class="premium-card" style="max-width: 800px;">
    <form action="<?= base_url('employees/update/' . $employee['id']) ?>" method="POST" class="premium-form">
        <?= csrf_field() ?>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="full_name">Họ và tên</label>
                <input type="text" name="full_name" id="full_name" required value="<?= $employee['full_name'] ?>">
            </div>

            <div class="form-group">
                <label for="position">Chức vụ</label>
                <input type="text" name="position" id="position" required value="<?= $employee['position'] ?>">
            </div>

            <div class="form-group">
                <label for="join_date">Ngày vào làm</label>
                <input type="date" name="join_date" id="join_date" required value="<?= $employee['join_date'] ?>">
            </div>

            <div class="form-group">
                <label for="salary_base">Mức lương cơ bản</label>
                <input type="number" name="salary_base" id="salary_base" required value="<?= $employee['salary_base'] ?>">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="address">Địa chỉ</label>
                <input type="text" name="address" id="address" value="<?= $employee['address'] ?>">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-premium">Cập nhật hồ sơ</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
