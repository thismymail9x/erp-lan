<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/zalo.css') ?>">

<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="zalo-page-container">
    <div class="dashboard-header-wrapper" style="margin-bottom: 24px;">
        <div class="header-title-container">
            <h2 class="content-title">Cấu hình kết nối Zalo OA</h2>
            <p class="content-subtitle">Liên kết ERP với Official Account để bắt đầu quản lý</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; padding: 15px; border-radius: 12px; margin-bottom: 24px;">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <div class="config-card">
        <div style="text-align: center; margin-bottom: 30px;">
            <img src="https://stc-sp.zaloapp.com/v1/images/oa/logo-oa.png" alt="Zalo OA" style="height: 60px; margin-bottom: 15px;">
            <h3>Kết nối hệ thống</h3>
        </div>

        <?php if (empty($config->accessToken)): ?>
            <div style="text-align: center;">
                <span class="status-badge status-disconnected">
                    <i class="fas fa-unlink" style="margin-right: 8px;"></i> Chưa kết nối
                </span>
            </div>
        <?php else: ?>
            <div style="text-align: center;">
                <span class="status-badge status-connected">
                    <i class="fas fa-link" style="margin-right: 8px;"></i> Đã kết nối
                </span>
            </div>
        <?php endif; ?>

        <div class="credential-row">
            <div>
                <div class="credential-label">App ID</div>
                <div class="credential-value"><?= $config->appId ?: '<span style="color: #cbd5e1;">Chưa nhập</span>' ?></div>
            </div>
            <i class="fas fa-check-circle" style="color: <?= $config->appId ? '#10b981' : '#cbd5e1' ?>"></i>
        </div>

        <div class="credential-row">
            <div>
                <div class="credential-label">App Secret</div>
                <div class="credential-value"><?= $config->appSecret ? '••••••••••••••••' : '<span style="color: #cbd5e1;">Chưa nhập</span>' ?></div>
            </div>
            <i class="fas fa-check-circle" style="color: <?= $config->appSecret ? '#10b981' : '#cbd5e1' ?>"></i>
        </div>

        <div style="margin-top: 30px;">
            <?php if (!empty($config->appId) && !empty($config->appSecret)): ?>
                <a href="<?= base_url('zalo/auth') ?>" class="connect-btn">
                    <i class="fas fa-plug"></i> Kết nối Zalo OA ngay
                </a>
                <p style="text-align: center; font-size: 12px; color: #94a3b8; margin-top: 10px;">
                    Hệ thống sẽ chuyển hướng bạn đến trang xác thực của Zalo
                </p>
            <?php else: ?>
                <button class="connect-btn disabled">
                    <i class="fas fa-lock"></i> Vui lòng nhập App ID & Secret
                </button>
                <p style="text-align: center; font-size: 12px; color: #f43f5e; margin-top: 10px;">
                    Hãy cập nhật App ID và App Secret trong file <code>app/Config/Zalo.php</code> trước.
                </p>
            <?php endif; ?>
        </div>

        <div class="step-guide">
            <h4 style="margin-bottom: 20px; color: #334155;">Hướng dẫn kết nối:</h4>
            
            <div class="step-item">
                <div class="step-number">1</div>
                <div>
                    <div style="font-weight: 600;">Thiết lập Webhook</div>
                    <div style="font-size: 14px; color: #64748b;">Truy cập Zalo Developers, mục Webhook và dán URL này:</div>
                    <div style="background: #f1f5f9; padding: 8px 12px; border-radius: 8px; font-family: monospace; font-size: 12px; margin-top: 8px; border: 1px dashed #cbd5e1;">
                        <?= base_url('zalo/webhook') ?>
                    </div>
                </div>
            </div>

            <div class="step-item">
                <div class="step-number">2</div>
                <div>
                    <div style="font-weight: 600;">Cấp quyền Callback URL</div>
                    <div style="font-size: 14px; color: #64748b;">Trong phần Login & OAuth, thêm URL này vào danh sách callback:</div>
                    <div style="background: #f1f5f9; padding: 8px 12px; border-radius: 8px; font-family: monospace; font-size: 12px; margin-top: 8px; border: 1px dashed #cbd5e1;">
                        <?= base_url('zalo/callback') ?>
                    </div>
                </div>
            </div>

            <div class="step-item">
                <div class="step-number">3</div>
                <div>
                    <div style="font-weight: 600;">Bấm "Kết nối" phía trên</div>
                    <div style="font-size: 14px; color: #64748b;">Hệ thống sẽ lấy Access Token và Refresh Token để bắt đầu đồng bộ tin nhắn.</div>
                </div>
            </div>

            <div class="step-item">
                <div class="step-number">4</div>
                <div>
                    <div style="font-weight: 600; color: #b91c1c;"><i class="fas fa-exclamation-triangle"></i> Đồng bộ tin nhắn của nhân viên (Manual Portal Chats)</div>
                    <div style="font-size: 14px; color: #64748b;">Để ERP ghi nhận cả tin nhắn do nhân viên tư vấn trả lời trực tiếp trên Zalo OA Portal / App, hãy truy cập trang quản trị ứng dụng tại <b>Zalo Developers</b> -> mục <b>Webhook</b> -> tích chọn đầy đủ các sự kiện gửi đi:</div>
                    <ul style="font-size: 13px; color: #475569; margin-top: 8px; padding-left: 20px; line-height: 1.6;">
                        <li><code>oa_send_text</code> (OA gửi tin nhắn văn bản)</li>
                        <li><code>oa_send_image</code> (OA gửi hình ảnh)</li>
                        <li><code>oa_send_file</code> (OA gửi tệp tin)</li>
                        <li><code>oa_send_sticker</code> (OA gửi sticker)</li>
                    </ul>
                    <div style="font-size: 13px; color: #0284c7; margin-top: 6px; font-weight: 500;">
                        <i class="fas fa-info-circle"></i> <b>Mẹo hữu ích:</b> Trong trường hợp Webhook gặp sự cố mạng hoặc bạn vừa chat trên Zalo App, hãy mở khung chat của khách hàng đó trên ERP và bấm nút <b>"Đồng bộ tin nhắn"</b> ở góc phải để tự động kéo toàn bộ lịch sử hội thoại mới nhất về!
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
