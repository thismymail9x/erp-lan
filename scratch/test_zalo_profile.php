<?php
// Tự nạp config để lấy token
require 'app/Config/Zalo.php';
$config = new \Config\Zalo();
$accessToken = $config->accessToken;

if (empty($accessToken)) {
    die("Error: Access Token is empty. Please configure Zalo in ERP first.\n");
}

$zaloId = $argv[1] ?? 'zalo_user_id_here'; // Truyền ID vào khi chạy: php test_zalo.php <id>

echo "Testing Zalo getProfile for ID: $zaloId\n";
echo "Using Access Token: " . substr($accessToken, 0, 10) . "...\n";

$url = "https://openapi.zalo.me/v2.0/oa/getprofile?data=" . urlencode(json_encode(['user_id' => $zaloId]));

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "access_token: $accessToken"
]);

$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Response Body: \n";
echo $response . "\n";

$result = json_decode($response, true);
if (isset($result['error'])) {
    echo "Error Code: " . $result['error'] . "\n";
    echo "Message: " . ($result['message'] ?? 'N/A') . "\n";
}
