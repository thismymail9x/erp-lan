<?php
define('ENVIRONMENT', 'development');
require 'vendor/autoload.php';
require 'app/Config/Constants.php';

$db = \Config\Database::connect();
$followers = $db->table('zalo_followers')->limit(1)->get()->getResultArray();
echo json_encode($followers);
