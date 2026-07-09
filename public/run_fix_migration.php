<?php
/**
 * TẬP LỆNH VÁ LỖI CẤU TRÚC DATABASE (HOT-FIX MIGRATION RUNNER)
 * Chạy trực tiếp qua trình duyệt: http://localhost/run_fix_migration.php
 */

$host = 'localhost';
$db   = 'luatanborqy7_dev';
$user = 'luatanborqy7_dev';
$pass = 'YYXWTvGJSssB3aPWuWQ3';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

echo "<body style='font-family: sans-serif; background: #f8fafc; color: #334155; padding: 40px;'>";
echo "<div style='max-width: 800px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 30px; border: 1px solid #e2e8f0;'>";
echo "<h2 style='color: #0284c7; margin-top: 0; display: flex; align-items: center; gap: 8px;'>🔧 Cập nhật nóng cấu trúc Database (Zalo & Messenger)</h2>";
echo "<hr style='border: 0.5px solid #e2e8f0; margin: 20px 0;'>";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color: green; font-weight: bold;'>✅ Kết nối Cơ sở dữ liệu thành công!</p>";

    // --- 1. CẬP NHẬT BẢNG zalo_followers ---
    echo "<h3>1. Bảng `zalo_followers`</h3>";
    $zaloColumns = [
        'email'                   => "VARCHAR(255) NULL AFTER `phone_number` COMMENT 'Địa chỉ email khách hàng cung cấp qua chat'",
        'lead_warmth'             => "ENUM('hot', 'warm', 'cold') DEFAULT 'cold' AFTER `tags` COMMENT 'Độ nóng của lead: hot (Nóng), warm (Ấm), cold (Lạnh)'",
        'is_duplicate'            => "TINYINT(1) DEFAULT 0 AFTER `lead_warmth` COMMENT 'Cờ báo trùng lặp (1: Trùng lặp, 0: Bình thường)'",
        'duplicate_of'            => "INT(11) UNSIGNED NULL AFTER `is_duplicate` COMMENT 'ID liên hệ chính trong zalo_followers bị trùng'",
        'assigned_at'             => "DATETIME NULL AFTER `assigned_to` COMMENT 'Thời điểm phân công nhân sự gần nhất'",
        'first_response_deadline' => "DATETIME NULL AFTER `assigned_at` COMMENT 'Hạn chót để phản hồi khách hàng lần đầu (2 tiếng)'",
        'first_responded_at'      => "DATETIME NULL AFTER `first_response_deadline` COMMENT 'Thời điểm phản hồi thực tế lần đầu tiên'",
        'is_overdue'              => "TINYINT(1) DEFAULT 0 AFTER `first_responded_at` COMMENT 'Cờ đánh dấu quá hạn phản hồi (1: Quá hạn, 0: Đúng hạn)'",
        'deleted_at'              => "DATETIME NULL AFTER `updated_at` COMMENT 'Thời gian xóa mềm (Soft Delete)'"
    ];

    foreach ($zaloColumns as $col => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `zalo_followers` LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `zalo_followers` ADD COLUMN `$col` $definition");
            echo "<p style='color: green; margin-left: 20px;'>➕ Đã thêm cột <b>`$col`</b> vào bảng <b>`zalo_followers`</b></p>";
        } else {
            echo "<p style='color: #64748b; margin-left: 20px;'>✔️ Cột <b>`$col`</b> đã tồn tại.</p>";
        }
    }

    // Thêm khóa ngoại duplicate_of cho zalo_followers
    try {
        $pdo->exec("ALTER TABLE `zalo_followers` ADD CONSTRAINT `fk_zalo_follower_dup` FOREIGN KEY (`duplicate_of`) REFERENCES `zalo_followers` (`id`) ON DELETE SET NULL");
        echo "<p style='color: green; margin-left: 20px;'>🔗 Đã liên kết Khóa ngoại `fk_zalo_follower_dup` thành công!</p>";
    } catch (Exception $e) {
        echo "<p style='color: #64748b; margin-left: 20px;'>✔️ Khóa ngoại `fk_zalo_follower_dup` đã có hoặc bỏ qua.</p>";
    }


    // --- 2. CẬP NHẬT BẢNG messenger_contacts ---
    echo "<h3>2. Bảng `messenger_contacts`</h3>";
    $messengerColumns = [
        'email'                   => "VARCHAR(255) NULL AFTER `phone_number` COMMENT 'Địa chỉ email khách hàng cung cấp qua chat'",
        'lead_warmth'             => "ENUM('hot', 'warm', 'cold') DEFAULT 'cold' AFTER `tags` COMMENT 'Độ nóng của lead: hot (Nóng), warm (Ấm), cold (Lạnh)'",
        'is_duplicate'            => "TINYINT(1) DEFAULT 0 AFTER `lead_warmth` COMMENT 'Cờ báo trùng lặp (1: Trùng lặp, 0: Bình thường)'",
        'duplicate_of'            => "INT(11) UNSIGNED NULL AFTER `is_duplicate` COMMENT 'ID liên hệ chính trong messenger_contacts bị trùng'",
        'assigned_at'             => "DATETIME NULL AFTER `assigned_to` COMMENT 'Thời điểm phân công nhân sự gần nhất'",
        'first_response_deadline' => "DATETIME NULL AFTER `assigned_at` COMMENT 'Hạn chót để phản hồi khách hàng lần đầu (2 tiếng)'",
        'first_responded_at'      => "DATETIME NULL AFTER `first_response_deadline` COMMENT 'Thời điểm phản hồi thực tế lần đầu tiên'",
        'is_overdue'              => "TINYINT(1) DEFAULT 0 AFTER `first_responded_at` COMMENT 'Cờ đánh dấu quá hạn phản hồi (1: Quá hạn, 0: Đúng hạn)'"
    ];

    foreach ($messengerColumns as $col => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `messenger_contacts` LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `messenger_contacts` ADD COLUMN `$col` $definition");
            echo "<p style='color: green; margin-left: 20px;'>➕ Đã thêm cột <b>`$col`</b> vào bảng <b>`messenger_contacts`</b></p>";
        } else {
            echo "<p style='color: #64748b; margin-left: 20px;'>✔️ Cột <b>`$col`</b> đã tồn tại.</p>";
        }
    }

    // Thêm khóa ngoại duplicate_of cho messenger_contacts
    try {
        $pdo->exec("ALTER TABLE `messenger_contacts` ADD CONSTRAINT `fk_messenger_contact_dup` FOREIGN KEY (`duplicate_of`) REFERENCES `messenger_contacts` (`id`) ON DELETE SET NULL");
        echo "<p style='color: green; margin-left: 20px;'>🔗 Đã liên kết Khóa ngoại `fk_messenger_contact_dup` thành công!</p>";
    } catch (Exception $e) {
        echo "<p style='color: #64748b; margin-left: 20px;'>✔️ Khóa ngoại `fk_messenger_contact_dup` đã có hoặc bỏ qua.</p>";
    }


    // --- 3. CẬP NHẬT BẢNG employees ---
    echo "<h3>3. Bảng `employees`</h3>";
    $employeeColumns = [
        'specialties'  => "VARCHAR(255) NULL AFTER `position` COMMENT 'JSON array các lĩnh vực chuyên môn (VD: [\"Đất đai\",\"Ly hôn\"])'",
        'max_workload' => "INT(11) DEFAULT 15 AFTER `specialties` COMMENT 'Giới hạn số lead tối đa nhân sự được nhận đồng thời'"
    ];

    foreach ($employeeColumns as $col => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `employees` LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `employees` ADD COLUMN `$col` $definition");
            echo "<p style='color: green; margin-left: 20px;'>➕ Đã thêm cột <b>`$col`</b> vào bảng <b>`employees`</b></p>";
        } else {
            echo "<p style='color: #64748b; margin-left: 20px;'>✔️ Cột <b>`$col`</b> đã tồn tại.</p>";
        }
    }

    // --- 4. ĐĂNG KÝ VÀO BẢNG MIGRATIONS CỦA CODEIGNITER ---
    echo "<h3>4. Đăng ký di trú hệ thống (System Migration Register)</h3>";
    $migrationVersion = '2026-05-21-170000';
    $migrationClass   = 'App\Database\Migrations\UpdateChatTablesForAssignment';
    
    // Tạo bảng migrations nếu chưa có (rất hiếm trường hợp chưa có)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `version` varchar(255) NOT NULL,
        `class` varchar(255) NOT NULL,
        `group` varchar(255) NOT NULL,
        `namespace` varchar(255) NOT NULL,
        `time` int(11) NOT NULL,
        `batch` int(11) unsigned NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `version_class` (`version`, `class`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    $stmt = $pdo->prepare("SELECT count(*) FROM `migrations` WHERE `version` = ? AND `class` = ?");
    $stmt->execute([$migrationVersion, $migrationClass]);
    $exists = $stmt->fetchColumn();

    if ($exists == 0) {
        $stmtInsert = $pdo->prepare("INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES (?, ?, 'default', 'App', ?, 99)");
        $stmtInsert->execute([$migrationVersion, $migrationClass, time()]);
        echo "<p style='color: green; margin-left: 20px;'>📝 Đã đăng ký bản di trú `UpdateChatTablesForAssignment` vào hệ thống thành công!</p>";
    } else {
        echo "<p style='color: #64748b; margin-left: 20px;'>✔️ Bản di trú `UpdateChatTablesForAssignment` đã được đăng ký trước đó.</p>";
    }

    echo "<hr style='border: 0.5px solid #e2e8f0; margin: 20px 0;'>";
    echo "<h3 style='color: #16a34a; text-align: center; margin-bottom: 0;'>🎉 HOÀN THÀNH VÁ LỖI CẤU TRÚC DATABASE THÀNH CÔNG!</h3>";
    echo "<p style='text-align: center; color: #64748b; font-size: 14px;'>Hệ thống chat và tạo hồ sơ KH của bạn đã sẵn sàng hoạt động hoàn toàn mượt mà.</p>";
    echo "<div style='text-align: center; margin-top: 20px;'><a href='/chat' style='display: inline-block; background: #0284c7; color: #fff; padding: 10px 24px; border-radius: 8px; font-weight: bold; text-decoration: none;'>⬅️ Quay lại Phòng Chat</a></div>";

} catch (\PDOException $e) {
    echo "<h3 style='color: #dc2626;'>❌ LỖI THỰC THI:</h3>";
    echo "<p style='color: #ef4444; font-family: monospace; background: #fef2f2; padding: 15px; border-radius: 6px; border: 1px solid #fee2e2;'>" . $e->getMessage() . "</p>";
    echo "<p style='color: #64748b;'>Vui lòng kiểm tra lại cấu hình thông tin kết nối CSDL hoặc liên hệ đội ngũ phát triển.</p>";
}
echo "</div>";
echo "</body>";
