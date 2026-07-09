<?php
$hostname = 'localhost';
$username = 'luatanborqy7_dev';
$password = 'YYXWTvGJSssB3aPWuWQ3';
$database = 'luatanborqy7_dev';

try {
    $dsn = "mysql:host=$hostname;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $q = $pdo->query("SHOW TABLES LIKE 'zalo%'");
    $tables = $q->fetchAll(PDO::FETCH_ASSOC);
    echo "Zalo tables in database:\n";
    if (empty($tables)) {
        echo "(None found)\n";
    }
    foreach ($tables as $t) {
        echo "- " . current($t) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
