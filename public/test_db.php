<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

$db = \Config\Database::connect();
$cases = $db->table('cases')->orderBy('id', 'DESC')->limit(1)->get()->getResultArray();
$members = $db->table('case_members')->orderBy('id', 'DESC')->limit(5)->get()->getResultArray();

echo "CASES COUNT: " . count($cases) . "\n";
print_r($cases);
echo "\nMEMBERS COUNT: " . count($members) . "\n";
print_r($members);
