<?php
$mysqli = new mysqli("localhost", "luatanborqy7_erp", "4EkhR7pvQUSJpxbxaLZV", "luatanborqy7_erp");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}
$res = $mysqli->query("SELECT * FROM users");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "---\n";
$res = $mysqli->query("SELECT * FROM roles");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "---\n";
$res = $mysqli->query("SELECT * FROM employees");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
