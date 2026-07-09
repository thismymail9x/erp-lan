<?php
$profile = null;
try {
    $displayName = $profile['display_name'] ?? 'Khách Zalo';
    $avatarUrl = $profile['avatars']['240'] ?? ($profile['avatar'] ?? null);
    echo "Success: $displayName";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
