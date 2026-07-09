<?php
require_once 'app/Config/Zalo.php';
$config = new \Config\Zalo();
$accessToken = $config->accessToken;

$zaloId = $_GET['mid'] ?? '';

if (empty($zaloId)) {
    die("Missing mid parameter");
}

$url = "https://openapi.zalo.me/v2.0/oa/getconversation?data=" . urlencode(json_encode([
    'user_id' => $zaloId,
    'offset' => 0,
    'count' => 10
]));

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["access_token: {$accessToken}"]);
$response = curl_exec($ch);
curl_close($ch);

header('Content-Type: application/json');
echo $response;
