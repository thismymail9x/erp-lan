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
        <h1 class="premium-title">Quên mật khẩu?</h1>
        <p class="premium-subtitle">Nhập địa chỉ email để bắt đầu.</p>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-error"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
        <?php else: ?>
            <form action="<?= base_url('forgot-password') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="input-group">
                    <input type="email" name="email" id="email" placeholder=" " required>
                    <label for="email">Địa chỉ Email</label>
                </div>

                <button type="submit" class="btn-premium">Tiếp tục</button>
            </form>
        <?php endif; ?>

        <a href="<?= base_url('login') ?>" class="back-link">Hủy</a>
    </div>
</body>
</html>
