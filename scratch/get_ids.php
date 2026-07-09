<?php
// Script cực đơn giản để lấy ID khách Zalo
require 'app/Config/Database.php';
$db = \Config\Database::connect();
$query = $db->query("SELECT zalo_id, display_name FROM zalo_followers LIMIT 5");
foreach ($query->getResult() as $row) {
    echo "ID: " . $row->zalo_id . " | Name: " . $row->display_name . "\n";
}
