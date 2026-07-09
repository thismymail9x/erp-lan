<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/notifications.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="create-container">
    <div class="dashboard-header-wrapper">
        <div class="header-title-container">
            <h2 class="content-title text-center">G&#7917;i Trao &#273;&#7893;i &amp; &#221; ki&#7871;n</h2>
            <p class="content-subtitle text-center">G&#7917;i ch&#7881; &#273;&#7841;o, &#273;&#243;ng g&#243;p &#253; ki&#7871;n ho&#7863;c nh&#7855;c nh&#7903; &#273;&#7871;n nh&#226;n s&#7921; kh&#225;c trong h&#7879; th&#7889;ng.</p>
        </div>
        <div class="header-back-btn">
            <a href="<?= base_url('notifications') ?>" class="btn-secondary-sm">
                <i class="fas fa-arrow-left"></i>&nbsp; Quay l&#7841;i
            </a>
        </div>
    </div>

    <div class="premium-card premium-card-lg">
        <form action="<?= base_url('notifications/store') ?>" method="POST" class="premium-form">
            <?= csrf_field() ?>

            <?php if (has_permission('sys.admin')) { ?>
                <div class="form-group-premium m-b-24">
                    <label class="label-premium">&#272;&#7889;i t&#432;&#7907;ng nh&#7853;n th&#244;ng b&#225;o <span style="color: #ff3b30;">*</span></label>
                    <div style="display: flex; gap: 30px; margin-top: 10px;">
                        <label class="radio-apple">
                            <input type="radio" name="target_type" value="individual" checked onclick="toggleRecipientFields('individual')">
                            <span>C&#225; nh&#226;n</span>
                        </label>
                        <label class="radio-apple">
                            <input type="radio" name="target_type" value="department" onclick="toggleRecipientFields('department')">
                            <span>Ph&#242;ng ban</span>
                        </label>
                        <label class="radio-apple">
                            <input type="radio" name="target_type" value="all" onclick="toggleRecipientFields('all')">
                            <span>To&#224;n c&#244;ng ty</span>
                        </label>
                    </div>
                </div>

                <div id="departmentField" class="form-group-premium m-b-24" style="display:none;">
                    <label class="label-premium">Ch&#7885;n ph&#242;ng ban <span style="color: #ff3b30;">*</span></label>
                    <select name="department_id" class="form-control-premium select2-enable" style="width:100%;">
                        <option value="">-- Ch&#7885;n ph&#242;ng ban nh&#7853;n --</option>
                        <?php foreach ($departments as $d) { ?>
                            <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                        <?php } ?>
                    </select>
                </div>
            <?php } ?>

            <div id="individualField" class="form-group-premium m-b-24">
                <label class="label-premium">Ng&#432;&#7901;i nh&#7853;n (Ch&#7885;n 1 ho&#7863;c nhi&#7873;u) <span style="color: #ff3b30;">*</span></label>
                <select name="user_ids[]" class="form-control-premium select2-enable" multiple="multiple" required style="width:100%;">
                    <?php foreach ($staffs as $s) { ?>
                        <?php if ($s['user_id'] != session()->get('user_id')) { ?>
                            <option value="<?= $s['user_id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['position']) ?>)</option>
                        <?php } ?>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group-premium m-b-24">
                <label class="label-premium">Ti&#234;u &#273;&#7873; th&#244;ng b&#225;o <span style="color: #ff3b30;">*</span></label>
                <input type="text" name="title" class="form-control-premium" required
                       placeholder="V&#237; d&#7909;: G&#243;p &#253; v&#7873; quy tr&#236;nh, Nh&#7855;c nh&#7903; h&#7891; s&#417; g&#7845;p..."
                       maxlength="200">
            </div>

            <div class="form-group-premium">
                <label class="label-premium">N&#7897;i dung chi ti&#7871;t <span style="color: #ff3b30;">*</span></label>
                <textarea name="message" class="form-control-premium" required
                          placeholder="M&#244; t&#7843; chi ti&#7871;t &#253; ki&#7871;n ho&#7863;c ch&#7881; &#273;&#7841;o c&#7911;a b&#7841;n..."
                          style="height: 180px; line-height: 1.6;"></textarea>
            </div>

            <div class="form-actions-premium">
                <button type="submit" class="btn-premium btn-submit-premium">
                    <i class="fas fa-paper-plane"></i>&nbsp; G&#7917;i th&#244;ng b&#225;o &#273;i
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('js/notifications_page.js') ?>"></script>
<?= $this->endSection() ?>
