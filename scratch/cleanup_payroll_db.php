<?php
$hostname = 'localhost';
$username = 'luatanborqy7_dev';
$password = 'YYXWTvGJSssB3aPWuWQ3';
$database = 'luatanborqy7_dev';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Migrate data
    echo "Migrating salary_other to salary_bonus...\n";
    $pdo->exec("UPDATE payrolls SET salary_bonus = salary_bonus + IFNULL(salary_other, 0)");
    
    // 2. Drop columns
    echo "Dropping unused columns: salary_other, salary_allowance, notes...\n";
    $pdo->exec("ALTER TABLE payrolls DROP COLUMN salary_other");
    $pdo->exec("ALTER TABLE payrolls DROP COLUMN salary_allowance");
    $pdo->exec("ALTER TABLE payrolls DROP COLUMN notes");
    
    echo "Success!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
