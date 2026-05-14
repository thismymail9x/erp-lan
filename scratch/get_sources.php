<?php
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/bootstrap.php';
$db = \Config\Database::connect();
$query = $db->query("SELECT DISTINCT source FROM contacts WHERE deleted_at IS NULL");
$results = $query->getResultArray();
foreach ($results as $row) {
    echo $row['source'] . PHP_EOL;
}
