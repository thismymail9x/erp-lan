<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="dashboard-header-wrapper m-b-24">
    <div class="header-title-container">
        <h2 class="content-title">Thêm nhân viên mới</h2>
        <p class="content-subtitle">Khởi tạo hồ sơ nhân sự mới vào hệ thống.</p>
    </div>
    <div class="header-controls">
        <a href="<?= base_url('employees') ?>" class="btn-secondary-sm" title="Quay lại danh sách nhân sự">
            <i class="fas fa-chevron-left"></i> Quay lại
        </a>
    </div>
</div>

<div class="premium-card premium-card-centered-800">
    <form action="<?= base_url('employees/store') ?>" method="POST" class="premium-form">
        <?= csrf_field() ?>
        
        <div class="form-grid">
            <div class="form-group-premium">
                <label for="full_name">Họ và tên</label>
                <input type="text" name="full_name" id="full_name" class="form-control-premium" required placeholder="Nhập họ và tên đầy đủ..." title="Họ tên đầy đủ theo giấy tờ định danh">
            </div>

            <div class="form-group-premium">
                <label for="position">Chức vụ / Vị trí</label>
                <input type="text" name="position" id="position" class="form-control-premium" required placeholder="Ví dụ: Luật sư chính, Thư ký..." title="Vị trí công tác hiện tại">
            </div>

            <div class="form-group-premium">
                <label for="salary_base">Mức lương cơ bản (VNĐ)</label>
                <input type="number" name="salary_base" id="salary_base" class="form-control-premium" required value="0" title="Lương cứng hàng tháng chưa tính phụ cấp/thưởng">
            </div>

            <div class="form-group-premium">
                <label for="department_id">Phòng ban công tác</label>
                <select name="department_id" id="department_id" class="form-control-premium" required title="Phòng ban nhân viên trực thuộc">
                    <option value="" disabled selected>-- Chọn phòng ban --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-premium">
                <label for="join_date">Ngày vào làm</label>
                <input type="date" name="join_date" id="join_date" class="form-control-premium" required value="<?= date('Y-m-d') ?>" title="Ngày chính thức bắt đầu làm việc">
            </div>

            <div class="form-group-premium">
                <label for="identity_card">Số CMND/CCCD</label>
                <input type="text" name="identity_card" id="identity_card" class="form-control-premium" placeholder="Nhập số định danh..." title="Số thẻ căn cước hoặc chứng minh nhân dân">
            </div>

            <div class="form-group-premium">
                <label for="bank_name">Tên ngân hàng</label>
                <input type="text" name="bank_name" id="bank_name" class="form-control-premium" placeholder="Ví dụ: Vietcombank..." title="Ngân hàng nhận lương">
            </div>

            <div class="form-group-premium">
                <label for="bank_account">Số tài khoản ngân hàng</label>
                <input type="text" name="bank_account" id="bank_account" class="form-control-premium" placeholder="Nhập số tài khoản..." title="Số tài khoản thanh toán">
            </div>

            <div class="form-group-premium">
                <label for="user_id">Liên kết tài khoản hệ thống (Nếu có)</label>
                <select name="user_id" id="user_id" class="form-control-premium" title="Kết nối hồ sơ nhân sự với tài khoản đăng nhập">
                    <option value="">-- Không liên kết / Để sau --</option>
                    <?php foreach ($unlinkedUsers as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= esc($u['email']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-helper-text">Chỉ hiển thị các tài khoản chưa được gán cho nhân viên nào.</p>
            </div>

            <div class="form-group-premium">
                <label for="address">Địa chỉ thường trú</label>
                <input type="text" name="address" id="address" class="form-control-premium" placeholder="Địa chỉ liên lạc đầy đủ..." title="Địa chỉ cư trú hiện tại của nhân sự">
            </div>
        </div>

        <div class="form-actions-row">
            <button type="submit" class="btn-premium">
                <i class="fas fa-save"></i>&nbsp; Lưu hồ sơ
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
