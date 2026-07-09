<?php
$host = 'localhost';
$user = 'luatanborqy7_dev';
$pass = 'YYXWTvGJSssB3aPWuWQ3';
$dbName = 'luatanborqy7_dev';

$conn = new mysqli($host, $user, $pass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT zalo_id FROM zalo_followers LIMIT 1";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$zaloId = $row['zalo_id'] ?? '';

if (empty($zaloId)) {
    die("No zalo_id found in database");
}

echo "Testing with Zalo ID: $zaloId\n";

$accessToken = '-XV31KM7FGBp7Ci2JeGw1lKKnGPBacj8y0t-CXZcM5BS8leiOymCGuOkYZj6n1nlfWZh8cFb0LRS2842A9Cr1Fy9hLq3a2uYo57QG1EFMK75LTCWUvLtBg5_-dHZc4KdlsRsMtwYTm-7EkG0KATdRUjio2yVb50iwtcWAYpX7dp16eav9eP-P_8UxG5Dxc9xiN3AErs7UdpgM-uaCRLSOTXMhs8ZZri1mMpAOpscM0tmGUzy5ufC1V1mptePwrSY_ZNQKWRsS1_DQuHaUunqPgr6qJbmccPjdslfAppsNKR5AjqV8hOl4UvVl7KJZXaBpc2EPYgTEcFFSCGaSuzMOevNtmHwabvrl5BSEqF2QrI9BzuhJ-rnSPSry0z3pNK8Xoo7O7Zz56wFDkLhJjeKDRe3d3P-v6jiZgZ7Dq2HC04';

$url = "https://openapi.zalo.me/v2.0/oa/getconversation?data=" . urlencode(json_encode([
    'user_id' => $zaloId,
    'offset' => 0,
    'count' => 5
]));

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["access_token: {$accessToken}"]);
$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($err) echo "Curl Error: $err\n";
echo "Response: $response\n";
