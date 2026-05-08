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
     
     // Kiểm tra cột
     $stmt = $pdo->query("SHOW COLUMNS FROM case_steps LIKE 'last_overdue_notified_at'");
     $column = $stmt->fetch();
     
     if (!$column) {
         $pdo->exec("ALTER TABLE case_steps ADD COLUMN last_overdue_notified_at DATE NULL AFTER overdue_notified");
         echo "SUCCESS: Column 'last_overdue_notified_at' added.";
     } else {
         echo "INFO: Column already exists.";
     }
} catch (\PDOException $e) {
     echo "ERROR: " . $e->getMessage();
}
