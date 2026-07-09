<?php
/**
 * TẬP LỆNH CHẨN ĐOÁN CƠ SỞ DỮ LIỆU CHÁT (DATABASE DIAGNOSTIC)
 * Chạy trực tiếp qua trình duyệt: http://localhost/db_diagnostic.php
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
echo "<h2 style='color: #fb7185;'>🔍 BÁO CÁO CHẨN ĐOÁN DATABASE CHÁT & CRM</h2>";
echo "<hr style='border-color: #334155; margin: 20px 0;'>";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "<span style='color: #4ade80;'>[OK] Kết nối MySQL thành công!</span>\n\n";

    // 1. Kiểm tra zalo_followers
    echo "<h3 style='color: #e2e8f0; margin-top: 20px;'>1. Kiểm tra cấu trúc & dữ liệu bảng `zalo_followers`:</h3>";
    try {
        $q = $pdo->query("DESCRIBE `zalo_followers`");
        $columns = $q->fetchAll();
        echo "<b>Cột trong bảng zalo_followers:</b>\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']}) - Null: {$col['Null']} - Key: {$col['Key']} - Default: {$col['Default']}\n";
        }
        
        $qData = $pdo->query("SELECT id, zalo_id, display_name, phone_number, customer_id, deleted_at FROM `zalo_followers` LIMIT 10");
        $data = $qData->fetchAll();
        echo "\n<b>10 bản ghi đầu tiên trong zalo_followers:</b>\n";
        if (empty($data)) {
            echo "  (Bảng rỗng)\n";
        } else {
            foreach ($data as $row) {
                print_r($row);
            }
        }
    } catch (Exception $e) {
        echo "<span style='color: #f87171;'>[ERROR] Lỗi khi truy vấn zalo_followers: " . $e->getMessage() . "</span>\n";
    }

    // 2. Kiểm tra messenger_contacts
    echo "<h3 style='color: #e2e8f0; margin-top: 20px;'>2. Kiểm tra cấu trúc & dữ liệu bảng `messenger_contacts`:</h3>";
    try {
        $q = $pdo->query("DESCRIBE `messenger_contacts`");
        $columns = $q->fetchAll();
        echo "<b>Cột trong bảng messenger_contacts:</b>\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']}) - Null: {$col['Null']} - Key: {$col['Key']} - Default: {$col['Default']}\n";
        }
        
        $qData = $pdo->query("SELECT id, psid, display_name, phone_number, customer_id, deleted_at FROM `messenger_contacts` LIMIT 10");
        $data = $qData->fetchAll();
        echo "\n<b>10 bản ghi đầu tiên trong messenger_contacts:</b>\n";
        if (empty($data)) {
            echo "  (Bảng rỗng)\n";
        } else {
            foreach ($data as $row) {
                print_r($row);
            }
        }
    } catch (Exception $e) {
        echo "<span style='color: #f87171;'>[ERROR] Lỗi khi truy vấn messenger_contacts: " . $e->getMessage() . "</span>\n";
    }

    // 3. Kiểm tra các bản di trú
    echo "<h3 style='color: #e2e8f0; margin-top: 20px;'>3. Kiểm tra lịch sử di trú (Migrations):</h3>";
    try {
        $q = $pdo->query("SELECT * FROM `migrations` ORDER BY id DESC LIMIT 10");
        $migrations = $q->fetchAll();
        foreach ($migrations as $m) {
            echo "  ID: {$m['id']} - Version: {$m['version']} - Class: {$m['class']} - Batch: {$m['batch']}\n";
        }
    } catch (Exception $e) {
        echo "<span style='color: #f87171;'>[ERROR] Lỗi khi truy vấn migrations: " . $e->getMessage() . "</span>\n";
    }

} catch (Exception $e) {
    echo "<span style='color: #f87171;'>[CRITICAL] Lỗi kết nối CSDL: " . $e->getMessage() . "</span>\n";
}

echo "</body >";
