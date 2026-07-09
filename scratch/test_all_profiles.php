<?php
require 'vendor/autoload.php';
define('ENVIRONMENT', 'development');

$config = new \Config\Zalo();
$db = \Config\Database::connect();

$followers = $db->table('zalo_followers')->limit(5)->get()->getResultArray();
if (empty($followers)) {
    die("No followers found in DB.\n");
}

$service = new \App\Services\ZaloService();

foreach ($followers as $f) {
    echo "Testing ID: " . $f['zalo_id'] . " (Name in DB: " . $f['display_name'] . ")\n";
    $profile = $service->getProfile($f['zalo_id']);
    if ($profile) {
        echo "SUCCESS: " . json_encode($profile) . "\n";
    } else {
        echo "FAILED.\n";
    }
    echo "-------------------\n";
}
