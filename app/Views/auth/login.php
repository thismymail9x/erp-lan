<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="zalo-platform-site-verification" content="U_sP2OtN5o9mvQyEhk4f5qlrla2dWsiOCJ0o" />
    <title>Đăng nhập | L.A.N ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/premium_auth.css') ?>">
</head>
<body>
    <div class="hero-section">
        <div class="decor-circle circle-1"></div>
        <div class="decor-circle circle-2"></div>
    </div>
        
    <div class="floating-container">
        <h1 class="premium-title">LUẬT ÁNH NGỌC</h1>
        <p class="premium-subtitle">HỆ THỐNG QUẢN TRỊ DOANH NGHIỆP</p>

        <!-- Dynamic Quote Section -->
        <div class="quote-container">
            <i class="fas fa-quote-left quote-icon"></i>
            <p class="premium-quote"><?= $quote ?? 'Khởi đầu ngày mới với niềm đam mê và quyết tâm.' ?></p>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-error"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('message') ?></div>
        <?php endif; ?>

        <form action="<?= base_url('login') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="input-group">
                <input type="email" name="email" id="email" required value="<?= old('email') ?>" placeholder=" ">
                <label for="email">Email</label>
            </div>

            <div class="input-group">
                <input type="password" name="password" id="password" required placeholder=" ">
                <label for="password">Mật khẩu</label>
            </div>

            <button type="submit" class="btn-premium">Tiếp tục</button>
        </form>

        <div class="auth-link-row">
            <a href="<?= base_url('forgot-password') ?>" class="bottom-link">Bạn đã quên mật khẩu?</a>
        </div>

        <div class="auth-link-row compact">
            Chưa có tài khoản? <a href="<?= base_url('register') ?>" class="bottom-link">Tạo ngay</a>
        </div>
    </div>
</body>
</html>
