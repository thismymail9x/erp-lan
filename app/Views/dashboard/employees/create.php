<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/employees.css') ?>">
<?= $this->endSection() ?>

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
                <div class="form-group-premium" style="grid-column: span 2;">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-id-card m-r-8"></i> Thông tin cơ bản</h4>
                </div>

                <div class="form-group-premium">
                    <label for="full_name">Họ và tên <span style="color: #ff3b30;">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control-premium" required placeholder="Nhập họ và tên đầy đủ...">
                </div>

                <div class="form-group-premium">
                    <label for="initial_position">Vai trò lúc vào làm</label>
                    <select name="initial_position" id="initial_position" class="form-control-premium no-select2">
                        <option value="">Theo vị trí / vai trò tài khoản</option>
                        <?php foreach (array_keys(\Config\AppConstants::PROBATION_RATE_DEFAULT) as $entryRole): ?>
                            <option value="<?= esc($entryRole) ?>" <?= old('initial_position', '') === $entryRole ? 'selected' : '' ?>><?= esc($entryRole) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Dùng ngày vào làm và vai trò ban đầu để tính thời điểm chuyển giai đoạn.</small>
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
                            <option value="<?= $m['id'] ?>"><?= esc($m['full_name']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="join_date">Ngày vào làm <span style="color: #ff3b30;">*</span></label>
                    <input type="date" name="join_date" id="join_date" class="form-control-premium" required value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group-premium">
                    <label for="annual_leave_start_date">Bắt đầu tính phép năm</label>
                    <input type="date" name="annual_leave_start_date" id="annual_leave_start_date" class="form-control-premium">
                    <small class="annual-leave-note">Nếu để trống, hệ thống sẽ tự lấy tháng kế tiếp sau ngày chính thức/ngày vào làm khi vai trò đủ mốc.</small>
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

                <div class="form-group-premium" style="grid-column: span 2;">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-robot m-r-8"></i> Điều phối &amp; Phân công Chat tự động</h4>
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Lĩnh vực chuyên môn pháp lý (Specialties)</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; background: #f8f9fa; padding: 12px 15px; border-radius: 8px; border: 1px solid #d2d2d7;">
                        <?php
                            $availableSpecs = [
                                ['value' => html_entity_decode('Đất đai', ENT_QUOTES, 'UTF-8'), 'label' => 'Đất đai'],
                                ['value' => html_entity_decode('Ly hôn', ENT_QUOTES, 'UTF-8'), 'label' => 'Ly hôn'],
                                ['value' => html_entity_decode('Doanh nghiệp', ENT_QUOTES, 'UTF-8'), 'label' => 'Doanh nghiệp'],
                                ['value' => html_entity_decode('Hình sự', ENT_QUOTES, 'UTF-8'), 'label' => 'Hình sự'],
                                ['value' => html_entity_decode('Dân sự', ENT_QUOTES, 'UTF-8'), 'label' => 'Dân sự'],
                            ];
                            foreach ($availableSpecs as $spec) {
                        ?>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="specialties[]" value="<?= esc($spec['value']) ?>" style="width: auto; margin-right: 4px;">
                                <?= $spec['label'] ?>
                            </label>
                        <?php } ?>
                    </div>
                    <small style="color: #8e8e93; display: block; margin-top: 4px;">Hệ thống sẽ tự động gán lead thuộc các lĩnh vực này cho nhân sự nếu trùng khớp chuyên môn.</small>
                </div>

                <div class="form-group-premium">
                    <label for="max_workload">Giới hạn tải công việc (Max Workload)</label>
                    <input type="number" name="max_workload" id="max_workload" class="form-control-premium" min="1" max="100" value="15" placeholder="Mặc định: 15">
                    <small style="color: #8e8e93; display: block; margin-top: 4px;">Số lead chat chưa phản hồi tối đa nhân sự được nhận đồng thời.</small>
                </div>

                <div class="form-group-premium"></div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-university m-r-8"></i> Thông tin Tài chính &amp; Ngân hàng</h4>
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
                    <label for="allowance_base">Lương trách nhiệm (VNĐ)</label>
                    <input type="number" name="allowance_base" id="allowance_base" class="form-control-premium" value="0" placeholder="Mức lương trách nhiệm hàng tháng...">
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
<?= $this->endSection() ?>
