<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/employees.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="create-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title text-center">Th&#234;m nh&#226;n vi&#234;n m&#7899;i</h2>
            <p class="content-subtitle text-center">Kh&#7903;i t&#7841;o h&#7891; s&#417; nh&#226;n s&#7921; m&#7899;i v&#224;o h&#7879; th&#7889;ng.</p>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('employees') ?>" class="btn-secondary-sm" title="Quay l&#7841;i danh s&#225;ch nh&#226;n s&#7921;">
                <i class="fas fa-arrow-left"></i>&nbsp; Quay l&#7841;i
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-lg">
        <form action="<?= base_url('employees/store') ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="form-group-premium" style="grid-column: span 2;">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-id-card m-r-8"></i> Th&#244;ng tin c&#417; b&#7843;n</h4>
                </div>

                <div class="form-group-premium">
                    <label for="full_name">H&#7885; v&#224; t&#234;n <span style="color: #ff3b30;">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control-premium" required placeholder="Nh&#7853;p h&#7885; v&#224; t&#234;n &#273;&#7847;y &#273;&#7911;...">
                </div>

                <div class="form-group-premium">
                    <label for="position">Ch&#7913;c v&#7909; / V&#7883; tr&#237; <span style="color: #ff3b30;">*</span></label>
                    <input type="text" name="position" id="position" class="form-control-premium" required placeholder="V&#237; d&#7909;: Lu&#7853;t s&#432; ch&#237;nh, Th&#432; k&#253;...">
                </div>

                <div class="form-group-premium">
                    <label for="department_id">Ph&#242;ng ban c&#244;ng t&#225;c <span style="color: #ff3b30;">*</span></label>
                    <select name="department_id" id="department_id" class="form-control-premium" required>
                        <option value="" disabled selected>-- Ch&#7885;n ph&#242;ng ban --</option>
                        <?php foreach ($departments as $d) { ?>
                            <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="manager_id">Ng&#432;&#7901;i qu&#7843;n l&#253; (S&#7871;p)</label>
                    <select name="manager_id" id="manager_id" class="form-control-premium">
                        <option value="">-- Kh&#244;ng c&#243; s&#7871;p --</option>
                        <?php foreach ($managers as $m) { ?>
                            <option value="<?= $m['id'] ?>"><?= esc($m['full_name']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium">
                    <label for="join_date">Ng&#224;y v&#224;o l&#224;m <span style="color: #ff3b30;">*</span></label>
                    <input type="date" name="join_date" id="join_date" class="form-control-premium" required value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group-premium">
                    <label for="annual_leave_start_date">B&#7855;t &#273;&#7847;u t&#237;nh ph&#233;p n&#259;m</label>
                    <input type="date" name="annual_leave_start_date" id="annual_leave_start_date" class="form-control-premium">
                    <small class="annual-leave-note">N&#7871;u &#273;&#7875; tr&#7889;ng, h&#7879; th&#7889;ng s&#7869; t&#7921; l&#7845;y th&#225;ng k&#7871; ti&#7871;p sau ng&#224;y ch&#237;nh th&#7913;c/ng&#224;y v&#224;o l&#224;m khi vai tr&#242; &#273;&#7911; m&#7889;c.</small>
                </div>

                <div class="form-group-premium">
                    <label for="identity_card">S&#7889; CMND/CCCD</label>
                    <input type="text" name="identity_card" id="identity_card" class="form-control-premium" placeholder="Nh&#7853;p s&#7889; &#273;&#7883;nh danh...">
                </div>

                <div class="form-group-premium">
                    <label for="user_id">Li&#234;n k&#7871;t t&#224;i kho&#7843;n h&#7879; th&#7889;ng (N&#7871;u c&#243;)</label>
                    <select name="user_id" id="user_id" class="form-control-premium">
                        <option value="">-- Kh&#244;ng li&#234;n k&#7871;t / &#272;&#7875; sau --</option>
                        <?php foreach ($unlinkedUsers as $u) { ?>
                            <option value="<?= $u['id'] ?>"><?= esc($u['email']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label for="address">&#272;&#7883;a ch&#7881; th&#432;&#7901;ng tr&#250;</label>
                    <input type="text" name="address" id="address" class="form-control-premium" placeholder="&#272;&#7883;a ch&#7881; li&#234;n l&#7841;c &#273;&#7847;y &#273;&#7911;...">
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-robot m-r-8"></i> &#272;i&#7873;u ph&#7889;i &amp; Ph&#226;n c&#244;ng Chat t&#7921; &#273;&#7897;ng</h4>
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">L&#297;nh v&#7921;c chuy&#234;n m&#244;n ph&#225;p l&#253; (Specialties)</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; background: #f8f9fa; padding: 12px 15px; border-radius: 8px; border: 1px solid #d2d2d7;">
                        <?php
                            $availableSpecs = ['Đất đai', 'Ly hôn', 'Doanh nghiệp', 'Hình sự', 'Dân sự'];
                            foreach ($availableSpecs as $spec) {
                        ?>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="specialties[]" value="<?= esc($spec) ?>" style="width: auto; margin-right: 4px;">
                                <?= esc($spec) ?>
                            </label>
                        <?php } ?>
                    </div>
                    <small style="color: #8e8e93; display: block; margin-top: 4px;">H&#7879; th&#7889;ng s&#7869; t&#7921; &#273;&#7897;ng g&#225;n lead thu&#7897;c c&#225;c l&#297;nh v&#7921;c n&#224;y cho nh&#226;n s&#7921; n&#7871;u tr&#249;ng kh&#7899;p chuy&#234;n m&#244;n.</small>
                </div>

                <div class="form-group-premium">
                    <label for="max_workload">Gi&#7899;i h&#7841;n t&#7843;i c&#244;ng vi&#7879;c (Max Workload)</label>
                    <input type="number" name="max_workload" id="max_workload" class="form-control-premium" min="1" max="100" value="15" placeholder="M&#7863;c &#273;&#7883;nh: 15">
                    <small style="color: #8e8e93; display: block; margin-top: 4px;">S&#7889; lead chat ch&#432;a ph&#7843;n h&#7891;i t&#7889;i &#273;a nh&#226;n s&#7921; &#273;&#432;&#7907;c nh&#7853;n &#273;&#7891;ng th&#7901;i.</small>
                </div>

                <div class="form-group-premium"></div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <h4 class="m-b-10 text-apple-main"><i class="fas fa-university m-r-8"></i> Th&#244;ng tin T&#224;i ch&#237;nh &amp; Ng&#226;n h&#224;ng</h4>
                </div>

                <div class="form-group-premium">
                    <label for="bank_name">T&#234;n ng&#226;n h&#224;ng</label>
                    <input type="text" name="bank_name" id="bank_name" class="form-control-premium" placeholder="V&#237; d&#7909;: Vietcombank...">
                </div>

                <div class="form-group-premium">
                    <label for="bank_account">S&#7889; t&#224;i kho&#7843;n ng&#226;n h&#224;ng</label>
                    <input type="text" name="bank_account" id="bank_account" class="form-control-premium" placeholder="Nh&#7853;p s&#7889; t&#224;i kho&#7843;n...">
                </div>

                <div class="form-group-premium">
                    <label for="salary_base">M&#7913;c l&#432;&#417;ng c&#417; b&#7843;n (VN&#272;) <span style="color: #ff3b30;">*</span></label>
                    <input type="number" name="salary_base" id="salary_base" class="form-control-premium" required value="0">
                </div>

                <div class="form-group-premium" style="grid-column: span 2;">
                    <label for="allowance_base">L&#432;&#417;ng tr&#225;ch nhi&#7879;m (VN&#272;)</label>
                    <input type="number" name="allowance_base" id="allowance_base" class="form-control-premium" value="0" placeholder="M&#7913;c l&#432;&#417;ng tr&#225;ch nhi&#7879;m h&#224;ng th&#225;ng...">
                </div>
            </div>

            <div class="form-actions-premium">
                <button type="submit" class="btn-premium btn-submit-premium">
                    <i class="fas fa-save"></i>&nbsp; L&#432;u h&#7891; s&#417; nh&#226;n s&#7921;
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
