<?php
$db_path = __DIR__ . '/writable/database.sqlite';
try {
    $pdo = new PDO("sqlite:$db_path");
    $q = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $q->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tables as $t) {
        echo $t['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
