<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; }
        th, td { border: 1px solid black; padding: 5px; }
        th { background-color: #f2f2f2; }
        .text { mso-number-format: "\@"; }
        .amount { mso-number-format: "#,##0"; text-align: right; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Tên nhân sự</th>
                <th>Số tài khoản ngân hàng</th>
                <th>Tên chủ tài khoản</th>
                <th>Tên ngân hàng</th>
                <th>Lương thực lĩnh</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payrolls as $payroll): ?>
                <tr>
                    <td class="text"><?= esc($payroll['full_name']) ?></td>
                    <td class="text"><?= esc($payroll['bank_account'] ?? '') ?></td>
                    <td class="text"><?= esc($payroll['bank_owner'] ?? '') ?></td>
                    <td class="text"><?= esc($payroll['bank_name'] ?? '') ?></td>
                    <td class="amount"><?= number_format((float) $payroll['net_salary'], 0, '.', '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
