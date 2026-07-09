<?php
// BẢN KHẮC PHỤC MIỄN DỊCH HOÀN TOÀN (FAIL-PROOF) - KHÔNG NẠP CLASS - KHÔNG PHỤ THUỘC FRAMEWORK

header('Content-Type: text/html; charset=utf-8');

echo "<div style='font-family:sans-serif;padding:30px;max-width:650px;margin:50px auto;border:1px solid #d2d2d7;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.05);background:#fff;'>";
echo "<h2 style='color:#1d1d1f;margin-bottom:20px;border-bottom:1px solid #f2f2f2;padding-bottom:10px;'>🔍 Chẩn Đoán Verify Token Messenger (Chế độ An Toàn)</h2>";

$messengerConfigPath = __DIR__ . '/../app/Config/Messenger.php';
$defaultVerifyToken = 'lan_erp_messenger_verify_2026'; // Fallback cứng trong code

// 1. Đọc Verify Token mặc định bằng Regex từ tệp tin cấu hình
if (file_exists($messengerConfigPath)) {
    $content = file_get_contents($messengerConfigPath);
    if (preg_match('/public\s+string\s+\$verifyToken\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/', $content, $matches)) {
        $defaultVerifyToken = $matches[1];
        echo "<p style='margin:10px 0;'><b>1. Mã Verify Token mặc định trong code:</b> ";
        echo "<code style='background:#f5f5f7;padding:4px 8px;border-radius:4px;font-family:monospace;color:#333;'>'" . htmlspecialchars($defaultVerifyToken) . "'</code></p>";
    } else {
        echo "<p style='color:#ff9500;'><b>1. Mã Verify Token mặc định trong code:</b> Không thể parse qua regex, sử dụng mặc định: <code>'lan_erp_messenger_verify_2026'</code></p>";
    }
} else {
    echo "<p style='color:#ff3b30;'><b>1. Lỗi:</b> Không tìm thấy tệp tin cấu hình Messenger tại <code>app/Config/Messenger.php</code></p>";
}

// 2. Đọc cấu hình database bằng Regex để kết nối trực tiếp PDO
$databaseConfigPath = __DIR__ . '/../app/Config/Database.php';
$activeToken = $defaultVerifyToken;

if (file_exists($databaseConfigPath)) {
    $dbContent = file_get_contents($databaseConfigPath);
    
    // Trích xuất các tham số kết nối database bằng Regex
    $host = ''; $user = ''; $pass = ''; $dbName = ''; $port = '3306';
    
    // Tìm các dòng chứa cấu hình database dạng live
    if (preg_match('/\'hostname\'\s*=>\s*[\'"]([^\'"]+)[\'"]/', $dbContent, $m)) $host = $m[1];
    
    // Tìm username/password/database dòng live (bỏ qua dòng bị comment //)
    // Để an toàn, lấy khớp cụ thể cho cấu hình active
    if (preg_match('/^[ \t]*\'username\'\s*=>\s*[\'"]([^\'"]+)[\'"]/m', $dbContent, $m)) $user = $m[1];
    if (preg_match('/^[ \t]*\'password\'\s*=>\s*[\'"]([^\'"]+)[\'"]/m', $dbContent, $m)) $pass = $m[1];
    if (preg_match('/^[ \t]*\'database\'\s*=>\s*[\'"]([^\'"]+)[\'"]/m', $dbContent, $m)) $dbName = $m[1];
    if (preg_match('/^[ \t]*\'port\'\s*=>\s*(\d+)/m', $dbContent, $m)) $port = $m[1];

    if ($host && $user && $dbName) {
        try {
            $dsn = "mysql:host=$host;dbname=$dbName;port=$port;charset=utf8mb4";
            
            // Sử dụng Throwable để bắt toàn bộ lỗi kể cả class PDO không tồn tại hoặc lỗi kết nối driver
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            $stmt = $pdo->prepare("SELECT `value` FROM `system_settings` WHERE `key` = 'messenger_verify_token'");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p style='color:#34c759;margin:10px 0;'><b>2. Trạng thái kết nối Database:</b> <span style='font-weight:bold;'>Kết nối thành công (OK)</span></p>";
            
            if ($row && !empty($row['value'])) {
                $activeToken = $row['value'];
                echo "<p style='margin:10px 0;'><b>3. Mã Verify Token trong Database:</b> ";
                echo "<code style='background:#f5f5f7;padding:4px 8px;border-radius:4px;font-family:monospace;color:#bf4f74;'>'" . htmlspecialchars($activeToken) . "'</code></p>";
            } else {
                echo "<p style='margin:10px 0;color:#8e8e93;'><b>3. Mã Verify Token trong Database:</b> Không thiết lập hoặc trống (Hệ thống tự động sử dụng Token mặc định).</p>";
            }
        } catch (\Throwable $t) {
            echo "<p style='color:#ff9500;margin:10px 0;'><b>2. Trạng thái kết nối Database:</b> <span style='font-weight:bold;'>Không thể kết nối trực tiếp qua PDO</span> (Lỗi: " . htmlspecialchars($t->getMessage()) . ")</p>";
            echo "<p style='color:#8e8e93;'>Hệ thống sẽ chạy bằng Token mặc định trong code.</p>";
        }
    } else {
        echo "<p style='color:#ff9500;margin:10px 0;'><b>2. Trạng thái database:</b> Không thể trích xuất thông tin kết nối qua Regex. Sử dụng Token mặc định trong code.</p>";
    }
} else {
    echo "<p style='color:#ff9500;margin:10px 0;'><b>2. Trạng thái database:</b> Không tìm thấy cấu hình Database. Sử dụng Token mặc định trong code.</p>";
}

// 3. Hiển thị mã hoạt động chốt
echo "<div style='background:#fff0f5;padding:15px;border-radius:8px;margin-top:20px;border:1px solid #ffccd5;'>";
echo "<h3 style='margin:0 0 5px 0;color:#bf4f74;'>Mã Verify Token HIỆN TẠI đang kích hoạt:</h3>";
echo "<code style='background:#fff;padding:8px 12px;border-radius:6px;font-size:1.3em;border:1px solid #ffccd5;display:inline-block;margin-top:5px;font-family:monospace;color:#bf4f74;font-weight:bold;'>'" . htmlspecialchars($activeToken) . "'</code>";
echo " (Độ dài: " . strlen($activeToken) . " ký tự)";
echo "</div>";

echo "<div style='background:#f5f5f7;padding:15px;border-radius:8px;margin-top:20px;font-size:0.9em;color:#333;line-height:1.5;'>";
echo "⚠️ <b>Hướng dẫn dán vào Meta Console:</b> Bạn hãy copy phần chuỗi chữ nằm giữa hai dấu nháy đơn <code>'</code> ở dòng màu hồng trên kia và dán chính xác vào ô <b>Verify Token</b> trên trang cấu hình Webhook của Meta Developer Console.";
echo "</div>";

echo "</div>";
