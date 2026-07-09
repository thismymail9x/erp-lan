<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div style="max-width: 800px; margin: 0 auto;">
    <div class="dashboard-header-wrapper" style="margin-bottom: 24px;">
        <div class="header-title-container">
            <h2 class="content-title"><i class="fab fa-facebook-messenger" style="color:#1877f2;"></i> Cấu hình Facebook Messenger</h2>
            <p class="content-subtitle">Kết nối Facebook Page với hệ thống ERP để nhận & trả lời tin nhắn tập trung</p>
        </div>
        <a href="<?= base_url('messenger') ?>" class="btn-filter-secondary"><i class="fas fa-arrow-left"></i> Quay lại Chat</a>
    </div>

    <?php if (session()->getFlashdata('success')) { ?>
        <div class="alert alert-success" style="padding:12px 16px; background:#d1fae5; border-radius:8px; border-left:4px solid #10b981; margin-bottom:16px; color:#065f46;">
            <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?>
        </div>
    <?php } ?>
    <?php if (session()->getFlashdata('error')) { ?>
        <div class="alert alert-danger" style="padding:12px 16px; background:#fee2e2; border-radius:8px; border-left:4px solid #ef4444; margin-bottom:16px; color:#991b1b;">
            <i class="fas fa-exclamation-circle"></i> <?= session()->getFlashdata('error') ?>
        </div>
    <?php } ?>

    <!-- Trạng thái kết nối -->
    <?php if ($pageInfo) { ?>
        <div style="background: linear-gradient(135deg, #1877f2, #0d5bbf); border-radius: 12px; padding: 20px; color: #fff; margin-bottom: 24px; display: flex; align-items: center; gap: 16px;">
            <?php if (!empty($pageInfo['picture']['data']['url'])) { ?>
                <img src="<?= $pageInfo['picture']['data']['url'] ?>" style="width: 56px; height: 56px; border-radius: 50%; border: 3px solid rgba(255,255,255,0.4);" alt="Page">
            <?php } ?>
            <div>
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">Đã kết nối thành công</div>
                <div style="font-size: 20px; font-weight: 700;"><?= esc($pageInfo['name']) ?></div>
                <div style="font-size: 12px; opacity: 0.7;">Page ID: <?= esc($pageInfo['id']) ?></div>
            </div>
            <i class="fas fa-check-circle" style="font-size: 32px; margin-left: auto; opacity: 0.7;"></i>
        </div>
    <?php } else { ?>
        <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 12px; padding: 16px; margin-bottom: 24px; display: flex; gap: 12px; align-items: flex-start;">
            <i class="fas fa-exclamation-triangle" style="color: #f59e0b; font-size: 20px; flex-shrink: 0; margin-top: 2px;"></i>
            <div>
                <div style="font-weight: 600; color: #92400e;">Chưa kết nối Facebook Page</div>
                <div style="font-size: 13px; color: #b45309; margin-top: 4px;">Nhập Page Access Token bên dưới để bắt đầu nhận tin nhắn từ khách hàng qua Messenger.</div>
            </div>
        </div>
    <?php } ?>

    <!-- Form cấu hình -->
    <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 16px; color: #0f172a;"><i class="fas fa-key" style="color: #1877f2; margin-right: 8px;"></i>Thông tin kết nối API</h3>
        </div>
        <form action="<?= base_url('messenger/save-config') ?>" method="POST" style="padding: 24px;">
            <?= csrf_field() ?>

            <div style="margin-bottom: 20px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                    Page Access Token <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" name="page_access_token"
                    value="<?php
                        $settingModel = new \App\Models\SystemSettingModel();
                        $tok = $settingModel->find('messenger_page_access_token');
                        echo esc($tok['value'] ?? '');
                    ?>"
                    style="width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; font-family:monospace;"
                    placeholder="EAAxxxx... (Lấy từ Meta for Developers > Your App > Messenger > Settings)" required>
                <p style="font-size:12px; color:#94a3b8; margin-top:6px;">Token này cho phép ERP gửi và nhận tin nhắn thay mặt cho Facebook Page của bạn.</p>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">App ID</label>
                    <input type="text" name="app_id"
                        value="<?php $aid = $settingModel->find('messenger_app_id'); echo esc($aid['value'] ?? ''); ?>"
                        style="width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px;"
                        placeholder="Lấy từ Meta App Dashboard">
                </div>
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">App Secret</label>
                    <input type="password" name="app_secret"
                        value="<?php $as = $settingModel->find('messenger_app_secret'); echo esc($as['value'] ?? ''); ?>"
                        style="width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px;"
                        placeholder="Dùng để xác minh Webhook">
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Verify Token (Webhook)</label>
                <input type="text" name="verify_token"
                    value="<?php $vt = $settingModel->find('messenger_verify_token'); echo esc($vt['value'] ?? 'lan_erp_messenger_verify_2026'); ?>"
                    style="width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; font-family:monospace;"
                    placeholder="lan_erp_messenger_verify_2026">
                <p style="font-size:12px; color:#94a3b8; margin-top:6px;">Nhập cùng giá trị này vào trường "Verify Token" khi đăng ký Webhook trên Meta App Dashboard.</p>
            </div>

            <button type="submit" class="btn-premium" style="background: #1877f2; padding: 12px 32px;">
                <i class="fas fa-save"></i> Lưu cấu hình
            </button>
        </form>
    </div>

    <!-- Hướng dẫn thiết lập Webhook -->
    <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 24px; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 16px; color: #0f172a;"><i class="fas fa-plug" style="color: #1877f2; margin-right: 8px;"></i>Hướng dẫn thiết lập Webhook</h3>
        </div>
        <div style="padding: 24px;">
            <ol style="font-size: 14px; color: #374151; line-height: 2; padding-left: 20px;">
                <li>Truy cập <a href="https://developers.facebook.com" target="_blank" style="color:#1877f2;">Meta for Developers</a> → Chọn App của bạn.</li>
                <li>Vào <strong>Messenger → Settings → Webhooks → Add Callback URL</strong>.</li>
                <li>Nhập <strong>Callback URL</strong>:
                    <div style="background:#f1f5f9; padding:8px 12px; border-radius:6px; font-family:monospace; font-size:12px; margin-top:6px; word-break:break-all; color:#0d5bbf;">
                        <?= base_url('messenger/webhook') ?>
                    </div>
                </li>
                <li>Nhập <strong>Verify Token</strong> giống giá trị đã cấu hình ở trên.</li>
                <li>Bấm <strong>Verify and Save</strong> → Chọn Subscribe: <code>messages</code>, <code>messaging_postbacks</code>.</li>
                <li>Vào <strong>Messenger → Settings → Subscribed Pages</strong> → Chọn Page cần liên kết.</li>
            </ol>
            <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:12px 16px; margin-top:12px; font-size:13px; color:#92400e;">
                <i class="fas fa-lightbulb"></i> <strong>Lưu ý:</strong> Webhook yêu cầu HTTPS. Trên localhost, dùng <strong>ngrok</strong> để tạo tunnel: <code>ngrok http 80</code>. Sau đó dùng URL ngrok làm Callback URL.
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
