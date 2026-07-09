<?php
// Since I can't easily bootstrap CI4 here, I'll just check the mysql.sql file
echo "Reading mysql.sql...\n";
$sql = file_get_contents('mysql.sql');
if (preg_match('/CREATE TABLE `users` \((.*?)\)/s', $sql, $matches)) {
    echo "Users table:\n" . $matches[1] . "\n";
}
if (preg_match('/CREATE TABLE `employees` \((.*?)\)/s', $sql, $matches)) {
    echo "Employees table:\n" . $matches[1] . "\n";
}
