<?php
// Script to add Quick Replies table and Zalo specific tags
$host = 'localhost';
$db   = 'luatanborqy7_dev';
$user = 'luatanborqy7_dev';
$pass = 'YYXWTvGJSssB3aPWuWQ3';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected to DB.\n";

    // 1. Create zalo_quick_replies table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `zalo_quick_replies` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(100) NOT NULL,
        `content` text NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Created table zalo_quick_replies.\n";

    // 2. Seed some quick replies if empty
    $count = $pdo->query("SELECT count(*) FROM zalo_quick_replies")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO zalo_quick_replies (title, content) VALUES (?, ?)");
        $stmt->execute(['Chào hỏi', 'Chào bạn, chúng tôi có thể giúp gì cho bạn?']);
        $stmt->execute(['Địa chỉ', 'Văn phòng Luật sư L.A.N tại: Tầng 5, Tòa nhà X, Cầu Giấy, Hà Nội.']);
        $stmt->execute(['Giờ làm việc', 'Giờ làm việc của chúng tôi từ 8:00 đến 17:30, từ Thứ 2 đến Thứ 6 hàng tuần.']);
        $stmt->execute(['Yêu cầu SĐT', 'Bạn vui lòng để lại số điện thoại để luật sư có thể liên hệ tư vấn chi tiết hơn ạ.']);
        echo "Seeded quick replies.\n";
    }

    echo "Migration completed successfully.\n";
} catch (\PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
