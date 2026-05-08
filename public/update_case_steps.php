<?php
require __DIR__ . '/../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/bootstrap.php';
$app = Config\Services::codeigniter(new Config\App());
$app->initialize();

$db = \Config\Database::connect();
$forge = \Config\Database::forge();

echo "<h3>Cập nhật Database: case_steps</h3>";

$fields = $db->getFieldNames('case_steps');

if (!in_array('last_overdue_notified_at', $fields)) {
    $forge->addColumn('case_steps', [
        'last_overdue_notified_at' => [
            'type' => 'DATE',
            'null' => true,
            'after' => 'overdue_notified'
        ]
    ]);
    echo "<span style='color: green;'>Đã bổ sung cột last_overdue_notified_at thành công!</span>";
} else {
    echo "<span style='color: blue;'>Cột last_overdue_notified_at đã tồn tại.</span>";
}
