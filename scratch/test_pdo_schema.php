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
     
     $stmt = $pdo->query("SHOW COLUMNS FROM `zalo_followers`");
     echo "Columns in zalo_followers:\n";
     while ($row = $stmt->fetch()) {
         echo $row['Field'] . " - " . $row['Type'] . "\n";
     }

     $stmt = $pdo->query("SHOW COLUMNS FROM `zalo_messages`");
     echo "\nColumns in zalo_messages:\n";
     while ($row = $stmt->fetch()) {
         echo $row['Field'] . " - " . $row['Type'] . "\n";
     }
} catch (\PDOException $e) {
     echo "ERROR: " . $e->getMessage();
}
