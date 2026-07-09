<?php
require 'app/Config/Database.php';
$dbConfig = new \Config\Database();
$config = $dbConfig->default;

try {
    $dsn = "mysql:host={$config['hostname']};dbname={$config['database']};charset={$config['charset']};port={$config['port']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $q = $pdo->query("SHOW TABLES LIKE 'zalo%'");
    $tables = $q->fetchAll(PDO::FETCH_ASSOC);
    echo "Zalo tables in database:\n";
    foreach ($tables as $t) {
        echo "- " . current($t) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
