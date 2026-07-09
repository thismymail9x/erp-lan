<?php
$appId = '4318908317306358870';
$appSecret = '72XV962RcaBE87FWCd6N';
$accessToken = '-XV31KM7FGBp7Ci2JeGw1lKKnGPBacj8y0t-CXZcM5BS8leiOymCGuOkYZj6n1nlfWZh8cFb0LRS2842A9Cr1Fy9hLq3a2uYo57QG1EFMK75LTCWUvLtBg5_-dHZc4KdlsRsMtwYTm-7EkG0KATdRUjio2yVb50iwtcWAYpX7dp16eav9eP-P_8UxG5Dxc9xiN3AErs7UdpgM-uaCRLSOTXMhs8ZZri1mMpAOpscM0tmGUzy5ufC1V1mptePwrSY_ZNQKWRsS1_DQuHaUunqPgr6qJbmccPjdslfAppsNKR5AjqV8hOl4UvVl7KJZXaBpc2EPYgTEcFFSCGaSuzMOevNtmHwabvrl5BSEqF2QrI9BzuhJ-rnSPSry0z3pNK8Xoo7O7Zz56wFDkLhJjeKDRe3d3P-v6jiZgZ7Dq2HC04';

// Test with a dummy user_id if not provided
$zaloId = "zalo_user_id_here"; 

$url = "https://openapi.zalo.me/v2.0/oa/getconversation?data=" . urlencode(json_encode([
    'user_id' => $zaloId,
    'offset' => 0,
    'count' => 5
]));

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["access_token: {$accessToken}"]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
