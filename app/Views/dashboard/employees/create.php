<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="create-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title text-center">Thêm nhân viên mới</h2>
            <p class="content-subtitle text-center">Khởi tạo hồ sơ nhân sự mới vào hệ thống.</p>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('employees') ?>" class="btn-secondary-sm" title="Quay lại danh sách nhân sự">
                <i class="fas fa-arrow-left"></i>&nbsp; Quay lại
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-lg">
        <form action="<?= base_url('employees/store') ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>
            
            <div class="form-grid">
                <!-- THÔNG TIN CƠ BẢN -->
                <div class="form-group-premium" style="grid-column: span 2;">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-id-card m-r-8"></i> Thông tin cơ bản</h4>
                </div>

                <div class="form-group-premium">
                    <label for="full_name">Họ và tên <span style="color: #ff3b30;">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control-premium" required placeholder="Nhập họ và tên đầy đủ...">
                </div>

                <div class="form-group-premium">
                    <label for="position">Chức vụ / Vị trí <span style="color: #ff3b30;">*</span></label>
                    <input type="text" name="position" id="position" class="form-control-premium" required placeholder="Ví dụ: Luật sư chính, Thư ký...">
                </div>

                <div class="form-group-premium">
                    <label for="department_id">Phòng ban công tác <span style="color: #ff3b30;">*</span></label>
                    <select name="department_id" id="department_id" class="form-control-premium" required>
                        <option value="" disabled selected>-- Chọn phòng ban --</option>
                        <?php foreach ($departments as $d) { ?>
                            <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="manager_id">Người quản lý (Sếp)</label>
                    <select name="manager_id" id="manager_id" class="form-control-premium">
                        <option value="">-- Không có sếp --</option>
                        <?php foreach ($managers as $m) { ?>
                            <option value="<?= $m['id'] ?>">
                                <?= esc($m['full_name']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="join_date">Ngày vào làm <span style="color: #ff3b30;">*</span></label>
                    <input type="date" name="join_date" id="join_date" class="form-control-premium" required value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group-premium">
                    <label for="identity_card">Số CMND/CCCD</label>
                    <input type="text" name="identity_card" id="identity_card" class="form-control-premium" placeholder="Nhập số định danh...">
                </div>

                <div class="form-group-premium">
                    <label for="user_id">Liên kết tài khoản hệ thống (Nếu có)</label>
                    <select name="user_id" id="user_id" class="form-control-premium">
                        <option value="">-- Không liên kết / Để sau --</option>
                        <?php foreach ($unlinkedUsers as $u) { ?>
                            <option value="<?= $u['id'] ?>"><?= esc($u['email']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label for="address">Địa chỉ thường trú</label>
                    <input type="text" name="address" id="address" class="form-control-premium" placeholder="Địa chỉ liên lạc đầy đủ...">
                </div>

                <!-- THÔNG TIN TÀI CHÍNH -->
                <div class="form-group-premium" style="grid-column: span 2;">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-university m-r-8"></i> Thông tin Tài chính & Ngân hàng</h4>
                </div>

                <div class="form-group-premium">
                    <label for="bank_name">Tên ngân hàng</label>
                    <input type="text" name="bank_name" id="bank_name" class="form-control-premium" placeholder="Ví dụ: Vietcombank...">
                </div>

                <div class="form-group-premium">
                    <label for="bank_account">Số tài khoản ngân hàng</label>
                    <input type="text" name="bank_account" id="bank_account" class="form-control-premium" placeholder="Nhập số tài khoản...">
                </div>

                <div class="form-group-premium">
                    <label for="salary_base">Mức lương cơ bản (VNĐ) <span style="color: #ff3b30;">*</span></label>
                    <input type="number" name="salary_base" id="salary_base" class="form-control-premium" required value="0">
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label for="allowance_base">Phụ cấp cố định (VNĐ)</label>
                    <input type="number" name="allowance_base" id="allowance_base" class="form-control-premium" value="0" placeholder="Ví dụ: 500000">
                </div>
            </div>

            <div class="form-actions-premium">
                <button type="submit" class="btn-premium btn-submit-premium">
                    <i class="fas fa-save"></i>&nbsp; Lưu hồ sơ nhân sự
                </button>
            </div>
        </form>
    </div>
</div>
<style>
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px 25px;
    }
    .form-group-premium[style*="grid-column: span 2"] {
        grid-column: span 2 !important;
    }
    .create-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        .form-group-premium {
            grid-column: span 1 !important;
        }
    }
</style>
<?= $this->endSection() ?>
