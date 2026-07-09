<?php
$hostname = 'localhost';
$username = 'luatanborqy7_dev';
$password = 'YYXWTvGJSssB3aPWuWQ3';
$database = 'luatanborqy7_dev';

$mysqli = new mysqli($hostname, $username, $password, $database);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$result = $mysqli->query("SHOW TABLES LIKE 'zalo%'");
echo "Zalo tables in database:\n";
if ($result->num_rows === 0) {
    echo "(None found)\n";
} else {
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }
}
$mysqli->close();
