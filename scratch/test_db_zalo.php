<?php
require 'public/index.php'; // Boot CodeIgniter

$followerModel = new \App\Models\ZaloFollowerModel();
$messageModel = new \App\Models\ZaloMessageModel();

try {
    $followerId = $followerModel->insert([
        'zalo_id' => '123456789',
        'display_name' => 'Khách Zalo Test',
        'avatar_url' => 'http://example.com/avatar.jpg',
        'mid_code' => 'ZALO-TEST',
        'tags' => json_encode(['New']),
    ]);

    if (!$followerId) {
        echo "Failed to insert follower:\n";
        print_r($followerModel->errors());
    } else {
        echo "Inserted Follower ID: $followerId\n";
        
        $follower = $followerModel->find($followerId);
        
        $msgId = $messageModel->insert([
            'zalo_msg_id' => 'msg_' . time(),
            'follower_id' => $follower['id'],
            'sender_type' => 'user',
            'message_text' => 'Chào luật sư',
            'attachments' => null
        ]);
        
        if (!$msgId) {
            echo "Failed to insert message:\n";
            print_r($messageModel->errors());
        } else {
            echo "Inserted Message ID: $msgId\n";
        }
    }
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
