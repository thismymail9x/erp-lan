<?php
require 'app/Config/Database.php';
$dbConfig = new \Config\Database();
$config = $dbConfig->default;

try {
    $dsn = "mysql:host={$config['hostname']};dbname={$config['database']};charset={$config['charset']};port={$config['port']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    
    // Check tables
    $q = $pdo->query("SHOW TABLES LIKE 'messenger%'");
    $tables = $q->fetchAll(PDO::FETCH_ASSOC);
    echo "Messenger tables in database:\n";
    foreach ($tables as $t) {
        echo "- " . current($t) . "\n";
    }
    
    // Also list system_settings values for messenger
    $q2 = $pdo->query("SELECT `key`, `value` FROM `system_settings` WHERE `key` LIKE 'messenger%'");
    $settings = $q2->fetchAll(PDO::FETCH_ASSOC);
    echo "\nMessenger settings in database:\n";
    foreach ($settings as $s) {
        echo "- {$s['key']}: '{$s['value']}'\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
