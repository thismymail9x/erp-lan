<?php
require_once __DIR__ . '/index.php';

$db = \Config\Database::connect();

try {
    // 1. Migrate data
    echo "Migrating salary_other to salary_bonus...<br>";
    $db->query("UPDATE payrolls SET salary_bonus = salary_bonus + IFNULL(salary_other, 0)");
    
    // 2. Drop columns
    echo "Dropping unused columns: salary_other, salary_allowance, notes...<br>";
    $db->query("ALTER TABLE payrolls DROP COLUMN salary_other");
    $db->query("ALTER TABLE payrolls DROP COLUMN salary_allowance");
    $db->query("ALTER TABLE payrolls DROP COLUMN notes");
    
    echo "<b>Success!</b>";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
