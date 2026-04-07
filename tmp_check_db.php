<?php
try {
    $dsn = 'mysql:host=localhost;dbname=erp_lan_db;charset=utf8';
    $user = 'root';
    $pass = ''; // Default for local
    $pdo = new PDO($dsn, $user, $pass);
    
    $query = $pdo->query("SHOW TABLES LIKE 'entity_tags'");
    $res = $query->fetch();
    echo "Entity Tags: " . ($res ? "Exists" : "Not Found") . "\n";

    $query = $pdo->query("SHOW TABLES LIKE 'tags'");
    $res = $query->fetch();
    echo "Tags: " . ($res ? "Exists" : "Not Found") . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
