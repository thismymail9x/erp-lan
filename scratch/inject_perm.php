<?php

$host = 'localhost';
$user = 'luatanborqy7_erp';
$pass = '4EkhR7pvQUSJpxbxaLZV';
$db   = 'luatanborqy7_erp';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$perm_name = 'attendance.view_all';
$group = 'Thời gian & Chấm công';
$desc = 'Quyền theo dõi chấm công tổng (Toàn công ty)';

// 1. Insert permission
$stmt = $conn->prepare("SELECT id FROM permissions WHERE name = ?");
$stmt->bind_param("s", $perm_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO permissions (name, module_group, description, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("sss", $perm_name, $group, $desc);
    $stmt->execute();
    $perm_id = $conn->insert_id;
    echo "Permission '$perm_name' created. ID: $perm_id\n";
} else {
    $row = $result->fetch_assoc();
    $perm_id = $row['id'];
    echo "Permission '$perm_name' already exists. ID: $perm_id\n";
}

// 2. Assign to Admin (Role 1) and Manager (Role 3)
$roles = [1, 3];
foreach ($roles as $role_id) {
    $stmt = $conn->prepare("SELECT * FROM roles_permissions WHERE role_id = ? AND permission_id = ?");
    $stmt->bind_param("ii", $role_id, $perm_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO roles_permissions (role_id, permission_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $role_id, $perm_id);
        $stmt->execute();
        echo "Assigned to Role ID: $role_id\n";
    } else {
        echo "Already assigned to Role ID: $role_id\n";
    }
}

$conn->close();
