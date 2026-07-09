<?php
require 'vendor/autoload.php';
// Define root path if needed
define('FCPATH', __DIR__ . '/public/');
require 'app/Config/Paths.php';
$paths = new \Config\Paths();
require $paths->systemDirectory . '/bootstrap.php';

$db = \Config\Database::connect();
$tables = $db->listTables();
echo "Tables:\n";
foreach ($tables as $table) {
    echo "- $table\n";
}

$columns = $db->getFieldNames('zalo_messages');
echo "\nColumns in zalo_messages:\n";
foreach ($columns as $col) {
    echo "- $col\n";
}
