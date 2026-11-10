<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/employees.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $roleName = session()->get('role_name');
    $deptName = session()->get('department_name');

    $canEditSensitive = $canEditSensitive ?? (bool) (
        session()->get('is_admin') || session()->get('isadmin')
        || (int) session()->get('role_id') === 1
        || in_array($roleName, [\Config\AppConstants::ROLE_ADMIN, \Config\AppConstants::ROLE_MOD], true)
        || $deptName === \Config\AppConstants::DEPT_NAME_HANH_CHINH
        || has_permission('sys.admin') || has_permission('user.manage')
    );
    $selectedInitialPosition = old('initial_position', $employee['initial_position'] ?? '');

    $restrictedAttr = !$canEditSensitive ? 'readonly style="background: #f8f9fa; cursor: not-allowed;"' : '';
    $restrictedSelect = !$canEditSensitive ? 'disabled style="background: #f8f9fa; cursor: not-allowed;"' : '';
    $leaveBalance = $annualLeaveBalance ?? [
        'year' => (int)date('Y'),
        'entitled_days' => 0,
        'used_days' => 0,
        'pending_days' => 0,
        'remaining_days' => 0,
        'annual_leave_start_date' => null,
        'is_eligible_annual_leave' => false,
    ];

    $decodeLabel = static function (string $value): string {
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    };
    $formatLeaveDay = static function ($value): string {
        return rtrim(rtrim(number_format((float)$value, 1), '0'), '.');
    };
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
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-id-card m-r-8"></i> Thông tin cơ bản</h4>
                </div>

                <div class="form-group">
                    <label for="full_name">Họ và tên</label>
                    <input type="text" name="full_name" id="full_name" required value="<?= esc($employee['full_name']) ?>" placeholder="Nhập họ và tên...">
                </div>

                <div class="form-group">
                    <label for="initial_position">Vai trò lúc vào làm</label>
                    <select name="initial_position" id="initial_position" class="form-control-premium no-select2" <?= !$canEditSensitive ? 'disabled' : '' ?>>
                        <option value="">Đối chiếu lịch sử / vai trò hiện có</option>
                        <?php foreach (array_keys(\Config\AppConstants::PROBATION_RATE_DEFAULT) as $entryRole): ?>
                            <option value="<?= esc($entryRole) ?>" <?= $selectedInitialPosition === $entryRole ? 'selected' : '' ?>><?= esc($entryRole) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Mốc gốc để đối chiếu với ngày vào làm; không đổi khi tự động chuyển chức danh.</small>
                </div>

                <div class="form-group">
                    <label for="position">Chức vụ / Vị trí</label>
                    <input type="text" name="position" id="position" required value="<?= esc($employee['position']) ?>" placeholder="Ví dụ: Luật sư chính, Thư ký..." <?= $restrictedAttr ?>>
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
                        <input type="text" class="form-control-premium" value="<?= esc($employee['department_name'] ?? $decodeLabel('Văn phòng')) ?>" readonly style="background: #f8f9fa;">
                        <input type="hidden" name="department_id" value="<?= $employee['department_id'] ?>">
                    <?php } ?>
                </div>

                <div class="form-group">
                    <label for="manager_id">Người quản lý (Sếp)</label>
                    <?php if ($canEditSensitive) { ?>
                        <select name="manager_id" id="manager_id">
                            <option value="">-- Không có sếp --</option>
                            <?php foreach ($managers as $m) { ?>
                                <?php if ($m['id'] == $employee['id']) continue; ?>
                                <option value="<?= $m['id'] ?>" <?= ($employee['manager_id'] == $m['id']) ? 'selected' : '' ?>>
                                    <?= esc($m['full_name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    <?php } else { ?>
                        <?php
                            $myManager = $decodeLabel('Chưa thiết lập');
                            foreach ($managers as $m) {
                                if ($employee['manager_id'] == $m['id']) { $myManager = $m['full_name']; break; }
                            }
                        ?>
                        <input type="text" class="form-control-premium" value="<?= esc($myManager) ?>" readonly style="background: #f8f9fa;">
                    <?php } ?>
                </div>

                <div class="form-group">
                    <label for="join_date">Ngày vào làm</label>
                    <input type="date" name="join_date" id="join_date" required value="<?= $employee['join_date'] ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="annual_leave_start_date">Bắt đầu tính phép năm</label>
                    <?php if ($canEditSensitive) { ?>
                        <input type="date" name="annual_leave_start_date" id="annual_leave_start_date" value="<?= esc($employee['annual_leave_start_date'] ?? '') ?>">
                        <small class="annual-leave-note">Nhập ngày đầu tháng kế tiếp sau khi nhân sự thuộc vai trò Trưởng phòng hoặc Nhân viên chính thức.</small>
                    <?php } else { ?>
                        <input type="date" id="annual_leave_start_date" value="<?= esc($employee['annual_leave_start_date'] ?? '') ?>" readonly style="background: #f8f9fa; cursor: not-allowed;">
                    <?php } ?>
                </div>

                <div class="form-group form-group-full">
                    <div class="annual-leave-panel">
                        <div class="annual-leave-panel-head">
                            <h4><i class="fas fa-calendar-check m-r-8"></i> Phép năm <?= (int)$leaveBalance['year'] ?></h4>
                            <span class="annual-leave-status <?= $leaveBalance['is_eligible_annual_leave'] ? 'is-active' : 'is-inactive' ?>">
                                <?= $leaveBalance['is_eligible_annual_leave'] ? 'Đang tính phép' : 'Chưa đủ mốc tính phép' ?>
                            </span>
                        </div>
                        <div class="annual-leave-stats">
                            <div class="annual-leave-stat">
                                <span>Được hưởng</span>
                                <strong><?= esc($formatLeaveDay($leaveBalance['entitled_days'])) ?></strong>
                            </div>
                            <div class="annual-leave-stat">
                                <span>Đã sử dụng</span>
                                <strong><?= esc($formatLeaveDay($leaveBalance['used_days'])) ?></strong>
                            </div>
                            <div class="annual-leave-stat">
                                <span>Đang chờ duyệt</span>
                                <strong><?= esc($formatLeaveDay($leaveBalance['pending_days'])) ?></strong>
                            </div>
                            <div class="annual-leave-stat annual-leave-stat-primary">
                                <span>Còn lại</span>
                                <strong><?= esc($formatLeaveDay($leaveBalance['remaining_days'])) ?></strong>
                            </div>
                        </div>
                        <p class="annual-leave-note">
                            Hạn mức 12 ngày/năm, cộng 1 ngày cho mỗi tháng đủ điều kiện. Đơn nghỉ phép năm đã duyệt sẽ tính vào số đã sử dụng.
                        </p>
                    </div>
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
                    <label for="personal_email">Email cá nhân</label>
                    <input type="email" name="personal_email" id="personal_email" value="<?= esc($employee['personal_email'] ?? '') ?>" placeholder="name@gmail.com">
                </div>

                <div class="form-group">
                    <label for="user_id">Liên kết tài khoản hệ thống</label>
                    <?php if ($canEditSensitive) { ?>
                        <select name="user_id" id="user_id">
                            <option value="">-- Không liên kết --</option>
                            <?php foreach ($unlinkedUsers as $u) { ?>
                                <option value="<?= $u['id'] ?>" <?= ($employee['user_id'] == $u['id']) ? 'selected' : '' ?>><?= esc($u['email']) ?></option>
                            <?php } ?>
                        </select>
                    <?php } else { ?>
                        <input type="text" class="form-control-premium" value="<?= esc(session()->get('email')) ?>" readonly style="background: #f8f9fa;">
                    <?php } ?>
                </div>

                <div class="form-group form-group-full">
                    <label for="address">Địa chỉ thường trú</label>
                    <input type="text" name="address" id="address" value="<?= esc($employee['address']) ?>" placeholder="Địa chỉ liên lạc đầy đủ...">
                </div>

                <div class="form-group form-group-full">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-robot m-r-8"></i> Điều phối &amp; Phân công Chat tự động (Giai đoạn 2 &amp; 3)</h4>
                </div>

                <div class="form-group form-group-full">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Lĩnh vực chuyên môn pháp lý (Specialties)</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; background: #f8f9fa; padding: 12px 15px; border-radius: 8px; border: 1px solid #d2d2d7;">
                        <?php
                            $specs = json_decode($employee['specialties'] ?? '[]', true) ?: [];
                            $availableSpecs = [
                                ['value' => $decodeLabel('Đất đai'), 'label' => 'Đất đai'],
                                ['value' => $decodeLabel('Ly hôn'), 'label' => 'Ly hôn'],
                                ['value' => $decodeLabel('Doanh nghiệp'), 'label' => 'Doanh nghiệp'],
                                ['value' => $decodeLabel('Hình sự'), 'label' => 'Hình sự'],
                                ['value' => $decodeLabel('Dân sự'), 'label' => 'Dân sự'],
                                ['value' => $decodeLabel('Thủ tục'), 'label' => 'Thủ tục'],
                            ];
                            foreach ($availableSpecs as $spec) {
                                $checked = in_array($spec['value'], $specs) ? 'checked' : '';
                        ?>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="specialties[]" value="<?= esc($spec['value']) ?>" <?= $checked ?> <?= !$canEditSensitive ? 'disabled' : '' ?> style="width: auto; margin-right: 4px;">
                                <?= $spec['label'] ?>
                            </label>
                        <?php } ?>
                    </div>
                    <small style="color: #8e8e93; display: block; margin-top: 4px;">Hệ thống sẽ tự động gán lead thuộc các lĩnh vực này cho nhân sự nếu trùng khớp chuyên môn.</small>
                </div>

                <div class="form-group">
                    <label for="max_workload">Giới hạn tải công việc (Max Workload)</label>
                    <input type="number" name="max_workload" id="max_workload" min="1" max="100" value="<?= (int)($employee['max_workload'] ?? 15) ?>" placeholder="Mặc định: 15" <?= $restrictedAttr ?>>
                    <small style="color: #8e8e93; display: block; margin-top: 4px;">Số lead chat chưa phản hồi tối đa nhân sự được nhận đồng thời.</small>
                </div>

                <div class="form-group"></div>

                <div class="form-group form-group-full">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-university m-r-8"></i> Thông tin Tài chính &amp; Ngân hàng</h4>
                </div>

                <div class="form-group">
                    <label for="bank_name">Tên ngân hàng</label>
                    <input type="text" name="bank_name" id="bank_name" value="<?= esc($employee['bank_name'] ?? '') ?>" placeholder="Vietcombank, Techcombank...">
                </div>

                <div class="form-group">
                    <label for="bank_account">Số tài khoản ngân hàng</label>
                    <input type="text" name="bank_account" id="bank_account" value="<?= esc($employee['bank_account'] ?? '') ?>" placeholder="Nhập số tài khoản...">
                </div>

                <div class="form-group">
                    <label for="bank_owner">Tên chủ tài khoản</label>
                    <input type="text" name="bank_owner" id="bank_owner" value="<?= esc($employee['bank_owner'] ?? '') ?>" placeholder="VIẾT HOA KHÔNG DẤU">
                </div>

                <div class="form-group">
                    <label for="salary_base">Mức lương tháng (Lương CB)</label>
                    <input type="number" name="salary_base" id="salary_base" required value="<?= (int)$employee['salary_base'] ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="insurance_salary">Lương dùng Bảo hiểm</label>
                    <input type="number" name="insurance_salary" id="insurance_salary" value="<?= (int)($employee['insurance_salary'] ?? $employee['salary_base']) ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="diligence_allowance">Phụ cấp chuyên cần</label>
                    <input type="number" name="diligence_allowance" id="diligence_allowance" value="<?= (int)($employee['diligence_allowance'] ?? 0) ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="petrol_allowance">Phụ cấp xăng xe</label>
                    <input type="number" name="petrol_allowance" id="petrol_allowance" value="<?= (int)($employee['petrol_allowance'] ?? 0) ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="dependent_count">Số người phụ thuộc (Giảm trừ thuế)</label>
                    <input type="number" name="dependent_count" id="dependent_count" value="<?= (int)($employee['dependent_count'] ?? 0) ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="allowance_base">Lương trách nhiệm (VNĐ)</label>
                    <input type="number" name="allowance_base" id="allowance_base" value="<?= (int)($employee['allowance_base'] ?? 0) ?>" <?= $restrictedAttr ?> placeholder="Mức lương trách nhiệm...">
                </div>

                <?php if ($canEditSensitive) { ?>
                <div class="form-group form-group-full probation-section-header">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-user-clock m-r-8"></i> Giai đoạn thử việc / Thực tập</h4>
                    <p class="probation-section-hint">Thiết lập hệ số lương cho nhân viên đang trong giai đoạn thử việc, thực tập hoặc học việc. Hệ số này tác động trực tiếp đến tính năng tính lương tự động.</p>
                </div>

                <div class="form-group">
                    <label for="probation_rate">Hệ số lương hiện tại (%)</label>
                    <input type="number" name="probation_rate" id="probation_rate"
                        min="1" max="100" step="0.01"
                        value="<?= (float)($employee['probation_rate'] ?? 100) ?>"
                        placeholder="100 = chính thức">
                    <div class="probation-preset-btns">
                        <button type="button" class="probation-preset-btn" data-rate="40">40% Thực tập</button>
                        <button type="button" class="probation-preset-btn" data-rate="60">60% Học việc</button>
                        <button type="button" class="probation-preset-btn" data-rate="85">85% Thử việc</button>
                        <button type="button" class="probation-preset-btn probation-preset-official" data-rate="100">100% Chính thức</button>
                    </div>
                    <small class="probation-note-text">Nhân viên chính thức = 100%. Không cần điền ngày kết thúc bên dưới.</small>
                </div>

                <div class="form-group">
                    <label for="probation_end_date">Ngày kết thúc giai đoạn</label>
                    <input type="date" name="probation_end_date" id="probation_end_date"
                        value="<?= esc($employee['probation_end_date'] ?? '') ?>"
                        placeholder="Để trống nếu đã chính thức">
                    <small class="probation-note-text">Ngày hệ số lương thay đổi sang mức mới. Khi ngày này rơi vào giữa tháng, hệ thống tự động chia 2 phần để tính lương chính xác.</small>
                </div>

                <div class="form-group" id="new-rate-after-group">
                    <label for="new_rate_after">Hệ số lương sau khi kết thúc (%)</label>
                    <input type="number" name="new_rate_after" id="new_rate_after"
                        min="1" max="100" step="0.01"
                        value="<?= (float)($employee['new_rate_after'] ?? 100) ?>"
                        placeholder="Thường là 100 khi chuyển sang chính thức">
                    <small class="probation-note-text">Hệ số lương áp dụng cho các ngày làm việc SAU ngày kết thúc trong cùng tháng.</small>
                </div>
                <?php } ?>
            </div>

            <div class="form-actions-row">
                <button type="submit" class="btn-premium">
                    <i class="fas fa-save"></i>&nbsp; Cập nhật hồ sơ
                </button>
            </div>
        </form>

        <hr class="m-t-30 m-b-30">

        <h3 class="section-header-title m-b-20"><i class="fas fa-shield-alt m-r-8 text-apple-red"></i> Đổi mật khẩu tài khoản</h3>
        <form action="<?= base_url('employees/change-password/' . (int)$employee['id']) ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>
            <input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>">
            <div class="form-grid form-grid-3">
                <div class="form-group">
                    <label>Mật khẩu hiện tại</label>
                    <input type="password" name="old_password" <?= !$canEditSensitive ? 'required' : '' ?> placeholder="<?= $canEditSensitive ? 'Bỏ trống nếu đổi cho nhân viên...' : 'Nhập mật khẩu đang dùng...' ?>">
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

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/employees.js') ?>"></script>
<?= $this->endSection() ?>
