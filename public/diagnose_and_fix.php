<?php
/**
/**
 * TẬP LỆNH CHẨN ĐOÁN VÀ TỰ ĐỘNG SỬA CƠ SỞ DỮ LIỆU
 * Chạy qua HTTP: http://localhost/diagnose_and_fix.php
 */

header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$db   = 'luatanborqy7_dev';
$user = 'luatanborqy7_dev';
$pass = 'YYXWTvGJSssB3aPWuWQ3';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

echo "<body style='font-family: monospace; background: #0f172a; color: #38bdf8; padding: 20px; font-size: 14px;'>";
echo "<h2 style='color: #fb7185;'>🔍 BÁO CÁO CHẨN ĐOÁN & SỬA LỖI DATABASE</h2>";
echo "<hr style='border-color: #334155; margin: 20px 0;'>";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "<span style='color: #4ade80;'>[OK] Kết nối MySQL thành công!</span><br><br>";

    // Hàm kiểm tra và thêm cột nếu thiếu
    function checkAndAddColumn($pdo, $table, $column, $definition) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
                echo "<span style='color: #fbbf24;'>➕ Đã thêm cột <b>`$column`</b> vào bảng <b>`$table`</b></span><br>";
                return true;
            } else {
                echo "<span style='color: #94a3b8;'>✔️ Cột <b>`$column`</b> trong <b>`$table`</b> đã tồn tại.</span><br>";
                return false;
            }
        } catch (Exception $e) {
            echo "<span style='color: #ef4444;'>❌ Lỗi khi xử lý cột `$column` trong bảng `$table`: " . $e->getMessage() . "</span><br>";
            return false;
        }
    }

    // 1. Kiểm tra & sửa zalo_followers
    echo "<h3 style='color: #e2e8f0;'>1. Kiểm tra bảng `zalo_followers`:</h3>";
    $zalo_followers_cols = [
        'zalo_id'                 => "VARCHAR(100) NOT NULL UNIQUE",
        'display_name'            => "VARCHAR(255) NOT NULL",
        'avatar_url'              => "TEXT NULL",
        'phone_number'            => "VARCHAR(20) NULL",
        'email'                   => "VARCHAR(255) NULL",
        'mid_code'                => "VARCHAR(50) NULL",
        'customer_id'             => "INT(11) UNSIGNED NULL",
        'assigned_to'             => "INT(11) UNSIGNED NULL",
        'assigned_at'             => "DATETIME NULL",
        'tags'                    => "TEXT NULL",
        'lead_warmth'             => "ENUM('hot', 'warm', 'cold') DEFAULT 'cold'",
        'is_duplicate'            => "TINYINT(1) DEFAULT 0",
        'duplicate_of'            => "INT(11) UNSIGNED NULL",
        'first_response_deadline' => "DATETIME NULL",
        'first_responded_at'      => "DATETIME NULL",
        'is_overdue'              => "TINYINT(1) DEFAULT 0",
        'created_at'              => "DATETIME NULL",
        'updated_at'              => "DATETIME NULL",
        'deleted_at'              => "DATETIME NULL",
    ];

    foreach ($zalo_followers_cols as $col => $def) {
        checkAndAddColumn($pdo, 'zalo_followers', $col, $def);
    }

    // 2. Kiểm tra & sửa messenger_contacts
    echo "<h3 style='color: #e2e8f0;'>2. Kiểm tra bảng `messenger_contacts`:</h3>";
    $messenger_contacts_cols = [
        'psid'                    => "VARCHAR(100) NOT NULL UNIQUE",
        'display_name'            => "VARCHAR(255) NOT NULL",
        'avatar_url'              => "TEXT NULL",
        'phone_number'            => "VARCHAR(20) NULL",
        'email'                   => "VARCHAR(255) NULL",
        'mid_code'                => "VARCHAR(50) NULL",
        'customer_id'             => "INT(11) UNSIGNED NULL",
        'assigned_to'             => "INT(11) UNSIGNED NULL",
        'assigned_at'             => "DATETIME NULL",
        'tags'                    => "TEXT NULL",
        'lead_warmth'             => "ENUM('hot', 'warm', 'cold') DEFAULT 'cold'",
        'is_duplicate'            => "TINYINT(1) DEFAULT 0",
        'duplicate_of'            => "INT(11) UNSIGNED NULL",
        'first_response_deadline' => "DATETIME NULL",
        'first_responded_at'      => "DATETIME NULL",
        'is_overdue'              => "TINYINT(1) DEFAULT 0",
        'locale'                  => "VARCHAR(20) DEFAULT 'vi_VN'",
        'timezone'                => "TINYINT(4) DEFAULT 7",
        'page_id'                 => "VARCHAR(100) NULL",
        'created_at'              => "DATETIME NULL",
        'updated_at'              => "DATETIME NULL",
        'deleted_at'              => "DATETIME NULL",
    ];

    foreach ($messenger_contacts_cols as $col => $def) {
        checkAndAddColumn($pdo, 'messenger_contacts', $col, $def);
    }

    // 3. Kiểm tra & sửa các bảng tin nhắn (zalo_messages & messenger_messages)
    echo "<h3 style='color: #e2e8f0;'>3. Kiểm tra các bảng tin nhắn:</h3>";
    checkAndAddColumn($pdo, 'zalo_messages', 'deleted_at', "DATETIME NULL");
    checkAndAddColumn($pdo, 'messenger_messages', 'deleted_at', "DATETIME NULL");

    // 4. In thông tin bản ghi hiện có để phục vụ phân tích
    echo "<h3 style='color: #e2e8f0;'>4. Thông tin dữ liệu hiện có:</h3>";
    
    $zalo_count = $pdo->query("SELECT COUNT(*) FROM `zalo_followers`")->fetchColumn();
    $messenger_count = $pdo->query("SELECT COUNT(*) FROM `messenger_contacts`")->fetchColumn();
    echo "Số lượng liên hệ Zalo: <b>$zalo_count</b><br>";
    echo "Số lượng liên hệ Messenger: <b>$messenger_count</b><br>";

    if ($zalo_count > 0) {
        echo "<br><b>Các liên hệ Zalo gần đây:</b><br>";
        $zalo_rows = $pdo->query("SELECT id, zalo_id, display_name, phone_number, customer_id, deleted_at FROM `zalo_followers` ORDER BY id DESC LIMIT 5")->fetchAll();
        foreach ($zalo_rows as $row) {
            echo " - ID: {$row['id']} | Zalo ID: {$row['zalo_id']} | Tên: {$row['display_name']} | SĐT: {$row['phone_number']} | CRM: " . ($row['customer_id'] ?? 'NULL') . " | Deleted: " . ($row['deleted_at'] ?? 'NULL') . "<br>";
        }
    }
    
    if ($messenger_count > 0) {
        echo "<br><b>Các liên hệ Messenger gần đây:</b><br>";
        $messenger_rows = $pdo->query("SELECT id, psid, display_name, phone_number, customer_id, deleted_at FROM `messenger_contacts` ORDER BY id DESC LIMIT 5")->fetchAll();
        foreach ($messenger_rows as $row) {
            echo " - ID: {$row['id']} | PSID: {$row['psid']} | Tên: {$row['display_name']} | SĐT: {$row['phone_number']} | CRM: " . ($row['customer_id'] ?? 'NULL') . " | Deleted: " . ($row['deleted_at'] ?? 'NULL') . "<br>";
        }
    }

    echo "<h3 style='color: #4ade80;'>🎉 HOÀN THÀNH KIỂM TRA VÀ TỰ ĐỘNG SỬA ĐỔI THÀNH CÔNG!</h3>";

} catch (Exception $e) {
    echo "<h3 style='color: #ef4444;'>❌ LỖI HỆ THỐNG:</h3>";
    echo "<p style='color: #ef4444; background: #fef2f2; padding: 15px; border-radius: 6px; border: 1px solid #fee2e2;'>" . $e->getMessage() . "</p>";
}

echo "</body>";
