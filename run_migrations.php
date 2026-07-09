<?php

// Emulate CI4 CLI request
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . 'app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

$db = \Config\Database::connect();
$forge = \Config\Database::forge();

// Create zalo tables
$db->query("CREATE TABLE IF NOT EXISTS `zalo_followers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `zalo_id` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `avatar_url` text,
  `phone_number` varchar(20) DEFAULT NULL,
  `mid_code` varchar(50) DEFAULT NULL,
  `customer_id` int(11) unsigned DEFAULT NULL,
  `tags` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zalo_id` (`zalo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$db->query("CREATE TABLE IF NOT EXISTS `zalo_messages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `zalo_msg_id` varchar(255) DEFAULT NULL,
  `follower_id` int(11) unsigned NOT NULL,
  `sender_type` enum('user','oa') NOT NULL DEFAULT 'user',
  `message_text` text NOT NULL,
  `attachments` text,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `follower_id` (`follower_id`),
  CONSTRAINT `zalo_messages_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `zalo_followers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

echo "Tables created successfully.\n";
