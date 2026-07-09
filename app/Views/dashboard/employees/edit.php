<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/employees.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $roleName = session()->get('role_name');
    $deptName = session()->get('department_name');

    $canEditSensitive = (
        $roleName === \Config\AppConstants::ROLE_ADMIN ||
        $roleName === \Config\AppConstants::ROLE_MOD ||
        $deptName === \Config\AppConstants::DEPT_NAME_HANH_CHINH
    );

    $restrictedAttr = !$canEditSensitive ? 'readonly style="background: #f8f9fa; cursor: not-allowed;"' : '';
    $restrictedSelect = !$canEditSensitive ? 'disabled style="background: #f8f9fa; cursor: not-allowed;"' : '';

    $decodeLabel = static function (string $value): string {
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    };
?>
<div class="employee-edit-wrapper">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title">H&#7891; s&#417;</h2>
            <p class="content-subtitle hide-mobile">C&#7853;p nh&#7853;t h&#7891; s&#417;: <strong><?= esc($employee['full_name']) ?></strong></p>
        </div>
        <div class="header-controls">
            <?php if ($canEditSensitive) { ?>
                <a href="<?= base_url('employees') ?>" class="btn-secondary-sm">
                    <i class="fas fa-chevron-left"></i> Quay l&#7841;i
                </a>
            <?php } else { ?>
                <a href="<?= base_url('dashboard') ?>" class="btn-secondary-sm">
                    <i class="fas fa-home"></i> Trang ch&#7911;
                </a>
            <?php } ?>
        </div>
    </div>

    <div class="premium-card premium-card-centered-800">
        <form action="<?= base_url('employees/update/' . $employee['id']) ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="form-group form-group-full">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-id-card m-r-8"></i> Th&#244;ng tin c&#417; b&#7843;n</h4>
                </div>

                <div class="form-group">
                    <label for="full_name">H&#7885; v&#224; t&#234;n</label>
                    <input type="text" name="full_name" id="full_name" required value="<?= esc($employee['full_name']) ?>" placeholder="Nh&#7853;p h&#7885; v&#224; t&#234;n...">
                </div>

                <div class="form-group">
                    <label for="position">Ch&#7913;c v&#7909; / V&#7883; tr&#237;</label>
                    <input type="text" name="position" id="position" required value="<?= esc($employee['position']) ?>" placeholder="V&#237; d&#7909;: Lu&#7853;t s&#432; ch&#237;nh, Th&#432; k&#253;..." <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="department_id">Ph&#242;ng ban c&#244;ng t&#225;c</label>
                    <?php if ($canEditSensitive) { ?>
                        <select name="department_id" id="department_id" required>
                            <option value="" disabled>-- Ch&#7885;n ph&#242;ng ban --</option>
                            <?php foreach ($departments as $d) { ?>
                                <option value="<?= $d['id'] ?>" <?= ($employee['department_id'] == $d['id']) ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                            <?php } ?>
                        </select>
                    <?php } else { ?>
                        <input type="text" class="form-control-premium" value="<?= esc($employee['department_name'] ?? $decodeLabel('V&#259;n ph&#242;ng')) ?>" readonly style="background: #f8f9fa;">
                        <input type="hidden" name="department_id" value="<?= $employee['department_id'] ?>">
                    <?php } ?>
                </div>

                <div class="form-group">
                    <label for="manager_id">Ng&#432;&#7901;i qu&#7843;n l&#253; (S&#7871;p)</label>
                    <?php if ($canEditSensitive) { ?>
                        <select name="manager_id" id="manager_id">
                            <option value="">-- Kh&#244;ng c&#243; s&#7871;p --</option>
                            <?php foreach ($managers as $m) { ?>
                                <?php if ($m['id'] == $employee['id']) continue; ?>
                                <option value="<?= $m['id'] ?>" <?= ($employee['manager_id'] == $m['id']) ? 'selected' : '' ?>>
                                    <?= esc($m['full_name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    <?php } else { ?>
                        <?php
                            $myManager = $decodeLabel('Ch&#432;a thi&#7871;t l&#7853;p');
                            foreach ($managers as $m) {
                                if ($employee['manager_id'] == $m['id']) { $myManager = $m['full_name']; break; }
                            }
                        ?>
                        <input type="text" class="form-control-premium" value="<?= esc($myManager) ?>" readonly style="background: #f8f9fa;">
                    <?php } ?>
                </div>

                <div class="form-group">
                    <label for="join_date">Ng&#224;y v&#224;o l&#224;m</label>
                    <input type="date" name="join_date" id="join_date" required value="<?= $employee['join_date'] ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="dob">Ng&#224;y sinh</label>
                    <input type="date" name="dob" id="dob" value="<?= $employee['dob'] ?>">
                </div>

                <div class="form-group">
                    <label for="identity_card">S&#7889; CMND/CCCD</label>
                    <input type="text" name="identity_card" id="identity_card" value="<?= esc($employee['identity_card'] ?? '') ?>" placeholder="Nh&#7853;p s&#7889; &#273;&#7883;nh danh...">
                </div>

                <div class="form-group">
                    <label for="phone_number">S&#7889; &#273;i&#7879;n tho&#7841;i</label>
                    <input type="text" name="phone_number" id="phone_number" value="<?= esc($employee['phone_number'] ?? '') ?>" placeholder="090x.xxx.xxx">
                </div>

                <div class="form-group">
                    <label for="personal_email">Email c&#225; nh&#226;n</label>
                    <input type="email" name="personal_email" id="personal_email" value="<?= esc($employee['personal_email'] ?? '') ?>" placeholder="name@gmail.com">
                </div>

                <div class="form-group">
                    <label for="user_id">Li&#234;n k&#7871;t t&#224;i kho&#7843;n h&#7879; th&#7889;ng</label>
                    <?php if ($canEditSensitive) { ?>
                        <select name="user_id" id="user_id">
                            <option value="">-- Kh&#244;ng li&#234;n k&#7871;t --</option>
                            <?php foreach ($unlinkedUsers as $u) { ?>
                                <option value="<?= $u['id'] ?>" <?= ($employee['user_id'] == $u['id']) ? 'selected' : '' ?>><?= esc($u['email']) ?></option>
                            <?php } ?>
                        </select>
                    <?php } else { ?>
                        <input type="text" class="form-control-premium" value="<?= esc(session()->get('email')) ?>" readonly style="background: #f8f9fa;">
                    <?php } ?>
                </div>

                <div class="form-group form-group-full">
                    <label for="address">&#272;&#7883;a ch&#7881; th&#432;&#7901;ng tr&#250;</label>
                    <input type="text" name="address" id="address" value="<?= esc($employee['address']) ?>" placeholder="&#272;&#7883;a ch&#7881; li&#234;n l&#7841;c &#273;&#7847;y &#273;&#7911;...">
                </div>

                <div class="form-group form-group-full">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-robot m-r-8"></i> &#272;i&#7873;u ph&#7889;i &amp; Ph&#226;n c&#244;ng Chat t&#7921; &#273;&#7897;ng (Giai &#273;o&#7841;n 2 &amp; 3)</h4>
                </div>

                <div class="form-group form-group-full">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">L&#297;nh v&#7921;c chuy&#234;n m&#244;n ph&#225;p l&#253; (Specialties)</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; background: #f8f9fa; padding: 12px 15px; border-radius: 8px; border: 1px solid #d2d2d7;">
                        <?php
                            $specs = json_decode($employee['specialties'] ?? '[]', true) ?: [];
                            $availableSpecs = [
                                $decodeLabel('&#272;&#7845;t &#273;ai'),
                                $decodeLabel('Ly h&#244;n'),
                                $decodeLabel('Doanh nghi&#7879;p'),
                                $decodeLabel('H&#236;nh s&#7921;'),
                                $decodeLabel('D&#226;n s&#7921;'),
                                $decodeLabel('Th&#7911; t&#7909;c'),
                            ];
                            foreach ($availableSpecs as $spec) {
                                $checked = in_array($spec, $specs) ? 'checked' : '';
                        ?>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="specialties[]" value="<?= esc($spec) ?>" <?= $checked ?> <?= !$canEditSensitive ? 'disabled' : '' ?> style="width: auto; margin-right: 4px;">
                                <?= esc($spec) ?>
                            </label>
                        <?php } ?>
                    </div>
                    <small style="color: #8e8e93; display: block; margin-top: 4px;">H&#7879; th&#7889;ng s&#7869; t&#7921; &#273;&#7897;ng g&#225;n lead thu&#7897;c c&#225;c l&#297;nh v&#7921;c n&#224;y cho nh&#226;n s&#7921; n&#7871;u tr&#249;ng kh&#7899;p chuy&#234;n m&#244;n.</small>
                </div>

                <div class="form-group">
                    <label for="max_workload">Gi&#7899;i h&#7841;n t&#7843;i c&#244;ng vi&#7879;c (Max Workload)</label>
                    <input type="number" name="max_workload" id="max_workload" min="1" max="100" value="<?= (int)($employee['max_workload'] ?? 15) ?>" placeholder="M&#7863;c &#273;&#7883;nh: 15" <?= $restrictedAttr ?>>
                    <small style="color: #8e8e93; display: block; margin-top: 4px;">S&#7889; lead chat ch&#432;a ph&#7843;n h&#7891;i t&#7889;i &#273;a nh&#226;n s&#7921; &#273;&#432;&#7907;c nh&#7853;n &#273;&#7891;ng th&#7901;i.</small>
                </div>

                <div class="form-group"></div>

                <div class="form-group form-group-full">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-university m-r-8"></i> Th&#244;ng tin T&#224;i ch&#237;nh &amp; Ng&#226;n h&#224;ng</h4>
                </div>

                <div class="form-group">
                    <label for="bank_name">T&#234;n ng&#226;n h&#224;ng</label>
                    <input type="text" name="bank_name" id="bank_name" value="<?= esc($employee['bank_name'] ?? '') ?>" placeholder="Vietcombank, Techcombank...">
                </div>

                <div class="form-group">
                    <label for="bank_account">S&#7889; t&#224;i kho&#7843;n ng&#226;n h&#224;ng</label>
                    <input type="text" name="bank_account" id="bank_account" value="<?= esc($employee['bank_account'] ?? '') ?>" placeholder="Nh&#7853;p s&#7889; t&#224;i kho&#7843;n...">
                </div>

                <div class="form-group">
                    <label for="bank_owner">T&#234;n ch&#7911; t&#224;i kho&#7843;n</label>
                    <input type="text" name="bank_owner" id="bank_owner" value="<?= esc($employee['bank_owner'] ?? '') ?>" placeholder="VI&#7870;T HOA KH&#212;NG D&#7844;U">
                </div>

                <div class="form-group">
                    <label for="salary_base">M&#7913;c l&#432;&#417;ng th&#225;ng (L&#432;&#417;ng CB)</label>
                    <input type="number" name="salary_base" id="salary_base" required value="<?= (int)$employee['salary_base'] ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="insurance_salary">L&#432;&#417;ng d&#249;ng B&#7843;o hi&#7875;m</label>
                    <input type="number" name="insurance_salary" id="insurance_salary" value="<?= (int)($employee['insurance_salary'] ?? $employee['salary_base']) ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="diligence_allowance">Ph&#7909; c&#7845;p chuy&#234;n c&#7847;n</label>
                    <input type="number" name="diligence_allowance" id="diligence_allowance" value="<?= (int)($employee['diligence_allowance'] ?? 0) ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="petrol_allowance">Ph&#7909; c&#7845;p x&#259;ng xe</label>
                    <input type="number" name="petrol_allowance" id="petrol_allowance" value="<?= (int)($employee['petrol_allowance'] ?? 0) ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="dependent_count">S&#7889; ng&#432;&#7901;i ph&#7909; thu&#7897;c (Gi&#7843;m tr&#7915; thu&#7871;)</label>
                    <input type="number" name="dependent_count" id="dependent_count" value="<?= (int)($employee['dependent_count'] ?? 0) ?>" <?= $restrictedAttr ?>>
                </div>

                <div class="form-group">
                    <label for="allowance_base">L&#432;&#417;ng tr&#225;ch nhi&#7879;m (VN&#272;)</label>
                    <input type="number" name="allowance_base" id="allowance_base" value="<?= (int)($employee['allowance_base'] ?? 0) ?>" <?= $restrictedAttr ?> placeholder="M&#7913;c l&#432;&#417;ng tr&#225;ch nhi&#7879;m...">
                </div>

                <?php if ($canEditSensitive) { ?>
                <div class="form-group form-group-full probation-section-header">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-user-clock m-r-8"></i> Giai &#273;o&#7841;n th&#7917; vi&#7879;c / Th&#7921;c t&#7853;p</h4>
                    <p class="probation-section-hint">Thi&#7871;t l&#7853;p h&#7879; s&#7889; l&#432;&#417;ng cho nh&#226;n vi&#234;n &#273;ang trong giai &#273;o&#7841;n th&#7917; vi&#7879;c, th&#7921;c t&#7853;p ho&#7863;c h&#7885;c vi&#7879;c. H&#7879; s&#7889; n&#224;y t&#225;c &#273;&#7897;ng tr&#7921;c ti&#7871;p &#273;&#7871;n t&#237;nh n&#259;ng t&#237;nh l&#432;&#417;ng t&#7921; &#273;&#7897;ng.</p>
                </div>

                <div class="form-group">
                    <label for="probation_rate">H&#7879; s&#7889; l&#432;&#417;ng hi&#7879;n t&#7841;i (%)</label>
                    <input type="number" name="probation_rate" id="probation_rate"
                        min="1" max="100" step="0.01"
                        value="<?= (float)($employee['probation_rate'] ?? 100) ?>"
                        placeholder="100 = ch&#237;nh th&#7913;c">
                    <div class="probation-preset-btns">
                        <button type="button" class="probation-preset-btn" data-rate="40">40% Th&#7921;c t&#7853;p</button>
                        <button type="button" class="probation-preset-btn" data-rate="60">60% H&#7885;c vi&#7879;c</button>
                        <button type="button" class="probation-preset-btn" data-rate="85">85% Th&#7917; vi&#7879;c</button>
                        <button type="button" class="probation-preset-btn probation-preset-official" data-rate="100">100% Ch&#237;nh th&#7913;c</button>
                    </div>
                    <small class="probation-note-text">Nh&#226;n vi&#234;n ch&#237;nh th&#7913;c = 100%. Kh&#244;ng c&#7847;n &#273;i&#7873;n ng&#224;y k&#7871;t th&#250;c b&#234;n d&#432;&#7899;i.</small>
                </div>

                <div class="form-group">
                    <label for="probation_end_date">Ng&#224;y k&#7871;t th&#250;c giai &#273;o&#7841;n</label>
                    <input type="date" name="probation_end_date" id="probation_end_date"
                        value="<?= esc($employee['probation_end_date'] ?? '') ?>"
                        placeholder="&#272;&#7875; tr&#7889;ng n&#7871;u &#273;&#227; ch&#237;nh th&#7913;c">
                    <small class="probation-note-text">Ng&#224;y h&#7879; s&#7889; l&#432;&#417;ng thay &#273;&#7893;i sang m&#7913;c m&#7899;i. Khi ng&#224;y n&#224;y r&#417;i v&#224;o gi&#7919;a th&#225;ng, h&#7879; th&#7889;ng t&#7921; &#273;&#7897;ng chia 2 ph&#7847;n &#273;&#7875; t&#237;nh l&#432;&#417;ng ch&#237;nh x&#225;c.</small>
                </div>

                <div class="form-group" id="new-rate-after-group">
                    <label for="new_rate_after">H&#7879; s&#7889; l&#432;&#417;ng sau khi k&#7871;t th&#250;c (%)</label>
                    <input type="number" name="new_rate_after" id="new_rate_after"
                        min="1" max="100" step="0.01"
                        value="<?= (float)($employee['new_rate_after'] ?? 100) ?>"
                        placeholder="Th&#432;&#7901;ng l&#224; 100 khi chuy&#7875;n sang ch&#237;nh th&#7913;c">
                    <small class="probation-note-text">H&#7879; s&#7889; l&#432;&#417;ng &#225;p d&#7909;ng cho c&#225;c ng&#224;y l&#224;m vi&#7879;c SAU ng&#224;y k&#7871;t th&#250;c trong c&#249;ng th&#225;ng.</small>
                </div>
                <?php } ?>
            </div>

            <div class="form-actions-row">
                <button type="submit" class="btn-premium">
                    <i class="fas fa-save"></i>&nbsp; C&#7853;p nh&#7853;t h&#7891; s&#417;
                </button>
            </div>
        </form>

        <hr class="m-t-30 m-b-30">

        <h3 class="section-header-title m-b-20"><i class="fas fa-shield-alt m-r-8 text-apple-red"></i> &#272;&#7893;i m&#7853;t kh&#7849;u t&#224;i kho&#7843;n</h3>
        <form action="<?= base_url('employees/change-password') ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>
            <div class="form-grid form-grid-3">
                <div class="form-group">
                    <label>M&#7853;t kh&#7849;u hi&#7879;n t&#7841;i</label>
                    <input type="password" name="old_password" required placeholder="Nh&#7853;p m&#7853;t kh&#7849;u &#273;ang d&#249;ng...">
                </div>
                <div class="form-group">
                    <label>M&#7853;t kh&#7849;u m&#7899;i</label>
                    <input type="password" name="new_password" required minlength="6" placeholder="T&#7889;i thi&#7875;u 6 k&#253; t&#7921;...">
                </div>
                <div class="form-group">
                    <label>X&#225;c nh&#7853;n m&#7853;t kh&#7849;u m&#7899;i</label>
                    <input type="password" name="confirm_password" required placeholder="Nh&#7853;p l&#7841;i m&#7853;t kh&#7849;u m&#7899;i...">
                </div>
            </div>
            <div class="form-actions-row">
                <button type="submit" class="btn-premium">
                    <i class="fas fa-save"></i>&nbsp; C&#7853;p nh&#7853;t m&#7853;t kh&#7849;u
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/employees.js') ?>"></script>
<?= $this->endSection() ?>
