<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= base_url('css/premium_auth.css') ?>">
</head>
<body>
    <div class="hero-section">
        <div class="decor-circle circle-1"></div>
        <div class="decor-circle circle-2"></div>
    </div>
        
    <div class="floating-container">
        <h1 class="premium-title">Mật khẩu mới</h1>
        <p class="premium-subtitle">Thiết lập lại mật khẩu đăng nhập của bạn.</p>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-error"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <form action="<?= base_url('reset-password') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= $token ?>">
            
            <div class="input-group">
                <input type="password" name="password" id="password" placeholder=" " required>
                <label for="password">Mật khẩu mới</label>
            </div>

            <div class="input-group">
                <input type="password" name="password_confirm" id="password_confirm" placeholder=" " required>
                <label for="password_confirm">Xác nhận mật khẩu</label>
            </div>

            <button type="submit" class="btn-premium">Đặt lại mật khẩu</button>
        </form>

        <a href="<?= base_url('login') ?>" class="back-link">Hủy</a>
    </div>
</body>
</html>
