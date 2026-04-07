<?php
$db_path = __DIR__ . '/writable/database.sqlite';
if (!file_exists($db_path)) {
    echo "SQLite DB not found at $db_path\n";
    exit;
}
try {
    $pdo = new PDO("sqlite:$db_path");
    $q = $pdo->query("PRAGMA table_info(workflow_templates)");
    $cols = $q->fetchAll(PDO::FETCH_ASSOC);
    echo "SQLite Columns for workflow_templates:\n";
    foreach ($cols as $c) {
        echo "- " . $c['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
