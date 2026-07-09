<?php
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

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     
     // Thêm cột is_read vào bảng zalo_messages
     $pdo->exec("ALTER TABLE `zalo_messages` ADD COLUMN `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: Chưa đọc, 1: Đã đọc' AFTER `attachments`;");

     echo "SUCCESS: Đã thêm cột is_read vào bảng zalo_messages thành công.";
} catch (\PDOException $e) {
     echo "ERROR: " . $e->getMessage();
}
