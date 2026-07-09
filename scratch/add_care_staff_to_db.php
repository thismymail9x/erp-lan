<?php
$hostname = 'localhost';
$username = 'luatanborqy7_dev';
$password = 'YYXWTvGJSssB3aPWuWQ3';
$database = 'luatanborqy7_dev';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM `customers` LIKE 'assigned_care_staff_id'");
    $column = $stmt->fetch();

    if (!$column) {
        echo "Adding column 'assigned_care_staff_id' to 'customers' table...\n";
        $pdo->exec("ALTER TABLE `customers` ADD COLUMN `assigned_care_staff_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'ID nhân sự phụ trách chăm sóc tư vấn (Liên kết bảng employees)' AFTER `created_by`");
        
        echo "Adding foreign key 'fk_customers_care_staff'...\n";
        $pdo->exec("ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_care_staff` FOREIGN KEY (`assigned_care_staff_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL");
        
        echo "Success! Column and foreign key added.\n";
    } else {
        echo "Column 'assigned_care_staff_id' already exists.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
