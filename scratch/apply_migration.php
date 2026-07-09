<?php

// Thiết lập môi trường chạy CodeIgniter 4
define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
define('APPPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR);

require dirname(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

$db = \Config\Database::connect();

echo "Running UpdateChatTablesForAssignment migration...\n";

require_once APPPATH . 'Database/Migrations/2026-05-21-170000_UpdateChatTablesForAssignment.php';
$migration = new \App\Database\Migrations\UpdateChatTablesForAssignment();

try {
    $migration->up();
    echo "Migration completed successfully!\n";
    
    // Thêm vào bảng migrations của CI4 để đánh dấu
    $db->query("INSERT INTO migrations (version, class, `group`, namespace, time, batch) 
                VALUES ('2026-05-21-170000', 'App\\\\Database\\\\Migrations\\\\UpdateChatTablesForAssignment', 'default', 'App', ?, 99)
                ON DUPLICATE KEY UPDATE time=time", [time()]);
    echo "Migration marked as done in the system.\n";
} catch (\Throwable $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
