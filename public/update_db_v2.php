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
     
     // Xóa bảng cũ nếu bị sai cấu trúc
     $pdo->exec("DROP TABLE IF EXISTS `zalo_messages`");
     $pdo->exec("DROP TABLE IF EXISTS `zalo_followers`");

     // Create zalo_followers table
     $pdo->exec("CREATE TABLE IF NOT EXISTS `zalo_followers` (
       `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
       `zalo_id` varchar(255) NOT NULL,
       `display_name` varchar(255) NOT NULL,
       `avatar_url` text,
       `phone_number` varchar(20) DEFAULT NULL,
       `mid_code` varchar(50) DEFAULT NULL,
       `customer_id` int(11) unsigned DEFAULT NULL,
       `tags` text,
       `created_at` datetime DEFAULT NULL,
       `updated_at` datetime DEFAULT NULL,
       PRIMARY KEY (`id`),
       UNIQUE KEY `zalo_id` (`zalo_id`)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

     // Create zalo_messages table
     $pdo->exec("CREATE TABLE IF NOT EXISTS `zalo_messages` (
       `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
       `zalo_msg_id` varchar(255) DEFAULT NULL,
       `follower_id` int(11) unsigned NOT NULL,
       `sender_type` enum('user','oa') NOT NULL DEFAULT 'user',
       `message_text` text NOT NULL,
       `attachments` text,
       `created_at` datetime DEFAULT NULL,
       PRIMARY KEY (`id`),
       KEY `follower_id` (`follower_id`),
       CONSTRAINT `zalo_messages_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `zalo_followers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

     echo "SUCCESS: Các bảng Zalo đã được thiết lập lại thành công với cấu trúc chuẩn.";
} catch (\PDOException $e) {
     echo "ERROR: " . $e->getMessage();
}
