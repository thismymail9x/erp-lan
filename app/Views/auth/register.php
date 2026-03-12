<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký | L.A.N ERP</title>
    <link rel="stylesheet" href="<?= base_url('css/premium_auth.css') ?>">
</head>
<body>
    <div class="hero-section">
        <div class="decor-circle circle-1"></div>
        <div class="decor-circle circle-2"></div>
    </div>
        
    <div class="floating-container">
        <h1 class="premium-title">ĐĂNG KÝ</h1>
        <p class="premium-subtitle">GIA NHẬP ĐỘI NGŨ LUẬT SƯ L.A.N</p>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-error"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <form action="<?= base_url('register') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="input-group">
                <input type="email" name="email" id="email" placeholder=" " required value="<?= old('email') ?>">
                <label for="email">Email mới</label>
            </div>

            <div class="input-group">
                <input type="password" name="password" id="password" placeholder=" " required>
                <label for="password">Mật khẩu mới</label>
            </div>

            <button type="submit" class="btn-premium">Tạo tài khoản</button>
        </form>

        <div style="text-align: center; margin-top: 24px; font-size: 14px; color: var(--apple-text-muted);">
            Đã có tài khoản? <a href="<?= base_url('login') ?>" class="bottom-link" style="display:inline; margin:0;">Đăng nhập</a>
        </div>
    </div>
</body>
</html>
