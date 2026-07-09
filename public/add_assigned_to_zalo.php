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
     
     // Thêm cột assigned_to vào bảng zalo_followers
     $pdo->exec("ALTER TABLE `zalo_followers` ADD COLUMN `assigned_to` int(11) unsigned DEFAULT NULL AFTER `customer_id`;");
     $pdo->exec("ALTER TABLE `zalo_followers` ADD CONSTRAINT `fk_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;");

     echo "SUCCESS: Đã thêm cột assigned_to vào bảng zalo_followers thành công.";
} catch (\PDOException $e) {
     echo "ERROR: " . $e->getMessage();
}
