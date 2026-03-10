<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu | LawFirm ERP</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f5f5f7;
            margin: 0;
            padding: 0;
            color: #1d1d1f;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .header {
            padding: 40px 20px;
            text-align: center;
            background-color: #ffffff;
            border-bottom: 1px solid #f5f5f7;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.02em;
        }
        .content {
            padding: 40px;
            line-height: 1.6;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn {
            background-color: #0066cc;
            color: #ffffff !important;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            display: inline-block;
        }
        .footer {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #86868b;
            background-color: #fbfbfd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>LAW FIRM ERP</h1>
        </div>
        <div class="content">
            <p>Xin chào,</p>
            <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại hệ thống LawFirm ERP. Vui lòng nhấn vào nút bên dưới để tiến hành thay đổi mật khẩu:</p>
            <div class="btn-container">
                <a href="<?= $resetLink ?>" class="btn">Đặt lại mật khẩu</a>
            </div>
            <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này. Link này sẽ hết hạn trong vòng 1 giờ.</p>
            <p>Trân trọng,<br>Đội ngũ hỗ trợ LawFirm ERP</p>
        </div>
        <div class="footer">
            &copy; <?= date('Y') ?> LawFirm ERP. All rights reserved.
        </div>
    </div>
</body>
</html>
