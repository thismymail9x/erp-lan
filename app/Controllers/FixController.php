<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class FixController extends Controller
{
    public function index()
    {
        $db = \Config\Database::connect();
        $session = session();
        
        echo "<h1>Trình chẩn đoán và sửa lỗi phân quyền</h1>";
        
        // 1. Thông tin session hiện tại
        echo "<h3>1. Thông tin phiên đăng nhập hiện tại:</h3>";
        echo "<ul>";
        echo "<li>User ID: " . $session->get('user_id') . "</li>";
        echo "<li>Role ID: " . $session->get('role_id') . "</li>";
        echo "<li>Role Name: " . $session->get('role_name') . "</li>";
        echo "<li>Permissions: <pre>" . print_r($session->get('permissions'), true) . "</pre></li>";
        echo "</ul>";

        // 2. Danh sách Chức danh trong DB
        echo "<h3>2. Danh sách Chức danh (Roles) thực tế trong Database:</h3>";
        $roles = $db->table('roles')->get()->getResultArray();
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Tên chức danh</th></tr>";
        foreach ($roles as $r) {
            echo "<tr><td>{$r['id']}</td><td>{$r['name']}</td></tr>";
        }
        echo "</table>";

        // 3. Thực hiện Fix dựa trên Tên Chức Danh
        echo "<h3>3. Tiến hành sửa lỗi:</h3>";
        try {
            // Đảm bảo quyền tồn tại
            $db->query("INSERT IGNORE INTO `permissions` (`name`, `description`, `module_group`) VALUES ('case.view', 'Xem danh sách hồ sơ cơ bản', 'Vụ việc pháp lý')");
            $perm = $db->table('permissions')->where('name', 'case.view')->get()->getRowArray();
            $permId = $perm['id'];

            // Danh sách các tên Role cần được View Vụ việc
            $targetRoles = ['Nhân viên', 'Luật sư', 'Nhân viên chính thức', 'Trưởng phòng', 'Mod', 'Admin'];
            
            foreach ($roles as $r) {
                if (in_array($r['name'], $targetRoles)) {
                    $db->query("INSERT IGNORE INTO `roles_permissions` (`role_id`, `permission_id`) VALUES ({$r['id']}, {$permId})");
                    echo "<li>Đã cấp quyền 'case.view' cho Role: <b>{$r['name']}</b> (ID: {$r['id']})</li>";
                }
            }

            // Xóa chặn nhầm
            $db->query("DELETE FROM `user_permissions` WHERE `permission_id` = {$permId} AND `is_granted` = 0");
            echo "<li>Đã xóa bỏ mọi lệnh cấm 'case.view' ở cấp độ tài khoản cá nhân.</li>";

            echo "<h4>=> KẾT QUẢ: ĐÃ SỬA XONG.</h4>";
            echo "<p><b>BƯỚC QUAN TRỌNG:</b> Bạn hãy <b>Đăng xuất</b> rồi <b>Đăng nhập lại</b> để hệ thống nạp lại quyền mới vào Session nhé!</p>";
            echo "<p>Nếu vẫn không thấy, hãy gửi lại cho mình toàn bộ nội dung hiển thị ở trang này (phần Session và bảng Roles).</p>";

        } catch (\Exception $e) {
            echo "Lỗi khi sửa: " . $e->getMessage();
        }

        echo "<hr><a href='" . base_url('dashboard') . "'>Quay lại Dashboard</a>";
    }
}
