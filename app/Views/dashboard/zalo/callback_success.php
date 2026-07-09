<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<div class="zalo-page-container" style="display: flex; align-items: center; justify-content: center; min-height: 80vh;">
    <div style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); max-width: 600px; width: 100%; text-align: center;">
        <div style="width: 80px; height: 80px; background: #dcfce7; color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 24px;">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h2 style="color: #0f172a; margin-bottom: 12px;">Kết nối thành công!</h2>
        <p style="color: #64748b; margin-bottom: 32px;">Bạn đã xác thực thành công với Zalo OA. Dưới đây là các mã truy cập cần thiết để hệ thống hoạt động.</p>
        
        <div style="text-align: left; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 30px;">
            <div style="margin-bottom: 15px;">
                <label style="font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase;">Access Token</label>
                <div style="display: flex; gap: 10px; margin-top: 5px;">
                    <input type="text" value="<?= $tokens['access_token'] ?>" readonly style="flex: 1; background: white; border: 1px solid #cbd5e1; padding: 8px 12px; border-radius: 8px; font-family: monospace; font-size: 13px;">
                    <button onclick="navigator.clipboard.writeText('<?= $tokens['access_token'] ?>')" class="btn-filter-secondary" style="padding: 8px 12px;"><i class="fas fa-copy"></i></button>
                </div>
            </div>

            <div>
                <label style="font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase;">Refresh Token</label>
                <div style="display: flex; gap: 10px; margin-top: 5px;">
                    <input type="text" value="<?= $tokens['refresh_token'] ?>" readonly style="flex: 1; background: white; border: 1px solid #cbd5e1; padding: 8px 12px; border-radius: 8px; font-family: monospace; font-size: 13px;">
                    <button onclick="navigator.clipboard.writeText('<?= $tokens['refresh_token'] ?>')" class="btn-filter-secondary" style="padding: 8px 12px;"><i class="fas fa-copy"></i></button>
                </div>
            </div>
        </div>

        <div style="background: #fff7ed; border-left: 4px solid #f97316; padding: 15px; text-align: left; margin-bottom: 30px;">
            <div style="font-weight: 600; color: #9a3412; font-size: 14px; margin-bottom: 5px;">Lưu ý quan trọng:</div>
            <div style="font-size: 13px; color: #c2410c;">Bạn cần cập nhật hai mã này vào file <code>app/Config/Zalo.php</code> để hệ thống có thể duy trì kết nối.</div>
        </div>

        <div style="display: flex; gap: 12px;">
            <a href="<?= base_url('zalo/config') ?>" class="btn-filter-secondary" style="flex: 1; padding: 12px; text-decoration: none; display: inline-block;">
                Quay lại cấu hình
            </a>
            <a href="<?= base_url('zalo') ?>" class="btn-premium" style="flex: 1; padding: 12px; text-decoration: none; display: inline-block;">
                Vào Dashboard Zalo
            </a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
