<?php

namespace App\Controllers;

class Migrator extends BaseController
{
    /**
     * Chạy duy nhất bản cập nhật mới nhất (Lách qua các bản cũ bị lỗi Table already exists)
     */
    public function index()
    {
        $db = \Config\Database::connect();
        
        echo "<h2>HỆ THỐNG CẬP NHẬT DATABASE TỰ ĐỘNG</h2>";
        echo "<hr>";
        
        try {
            $migrationsToRun = [
                [
                    'file'  => '2026-05-06-173000_AddOverdueTrackingToSteps.php',
                    'class' => 'App\Database\Migrations\AddOverdueTrackingToSteps',
                    'version' => '2026-05-06-173000'
                ],
                [
                    'file'  => '2026-05-06-180000_CreatePayrollTables.php',
                    'class' => 'App\Database\Migrations\CreatePayrollTables',
                    'version' => '2026-05-06-180000'
                ],
                [
                    'file'  => '2026-05-06-200000_AddOtherAndNotesToPayrolls.php',
                    'class' => 'App\Database\Migrations\AddOtherAndNotesToPayrolls',
                    'version' => '2026-05-06-200000'
                ]
            ];

            foreach ($migrationsToRun as $m) {
                $file = APPPATH . 'Database/Migrations/' . $m['file'];
                if (file_exists($file)) {
                    require_once $file;
                    $migration = new $m['class']();
                    $migration->up();
                    echo "<p style='color: green;'>✅ Thực thi thành công: <b>{$m['file']}</b></p>";
                    
                    // Đánh dấu vào bảng migrations
                    $db->query("INSERT INTO migrations (version, class, `group`, namespace, time, batch) 
                                VALUES (?, ?, 'default', 'App', ?, 99)
                                ON DUPLICATE KEY UPDATE time=time", [$m['version'], $m['class'], time()]);
                }
            }

            // 4. KIỂM TRA VÀ PHỤC HỒI QUYỀN ADMIN TỐI CAO
            $perm = $db->table('permissions')->where('name', 'sys.admin')->get()->getRow();
            if (!$perm) {
                $db->table('permissions')->insert([
                    'name' => 'sys.admin',
                    'module_group' => 'Hệ thống',
                    'description' => 'Đặc quyền TỐI CAO',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $permId = $db->insertID();
            } else {
                $permId = $perm->id;
            }
            
            // Gán cho Role ID 1 (Admin) nếu chưa có
            $hasLink = $db->table('roles_permissions')->where(['role_id' => 1, 'permission_id' => $permId])->countAllResults();
            if ($hasLink == 0) {
                $db->table('roles_permissions')->insert(['role_id' => 1, 'permission_id' => $permId]);
            }

            echo "<br><a href='".base_url('leave-requests')."'>⬅️ Quay lại hệ thống</a> | ";
            echo "<a href='".base_url('check-db')."'>🔍 Kiểm tra cấu trúc</a>";
            
        } catch (\Throwable $e) {
            echo "<h2 style='color: red;'>LỖI THỰC THI</h2>";
            echo "<p style='color: #a00; font-weight: bold;'>" . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
            echo "<br><a href='".base_url('check-db')."'>🔍 Kiểm tra cấu trúc hiện tại</a>";
        }
    }

    /**
     * Kiểm tra trực tiếp các cột có mặt trong DB hay chưa
     */
    public function check()
    {
        $db = \Config\Database::connect();
        
        // Lấy danh sách TOÀN BỘ bảng trong DB
        $allTables = $db->listTables();
        
        echo "<h2>KIỂM TRA TỔNG THỂ DATABASE (" . count($allTables) . " bảng)</h2>";
        echo "<hr>";
        
        $targetTables = ['leave_requests', 'employees', 'cases', 'workflow_templates', 'knowledge_base', 'roles'];
        
        foreach ($allTables as $t) {
            $isTarget = in_array($t, $targetTables);
            $rowCount = $db->table($t)->countAllResults();
            
            echo "<h3 id='$t' " . ($isTarget ? "style='color: #2e7d32;'" : "") . ">" . ($isTarget ? "⭐ " : "") . "Bảng: $t ($rowCount bản ghi)</h3>";
            
            if ($isTarget) {
                $fields = $db->getFieldData($t);
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 13px; font-family: sans-serif; min-width: 600px;'>
                        <tr style='background: #f4f4f4;'><th>Tên cột (Field)</th><th>Kiểu (Type)</th><th>Độ dài</th><th>Ghi chú (Comment)</th></tr>";
                foreach ($fields as $f) {
                    $isNew = in_array($f->name, ['is_emergency', 'handover_to', 'manager_id', 'approval_note', 'handover_content', 'summary', 'problem', 'solution', 'red_flags', 'contract_value', 'payment_progress']);
                    $style = $isNew ? "style='background: #e8f5e9; font-weight: bold; color: #2e7d32;'" : "";
                    
                    echo "<tr $style>
                            <td>{$f->name} " . ($isNew ? "✨ (MỚI)" : "") . "</td>
                            <td>{$f->type}</td>
                            <td>{$f->max_length}</td>
                            <td>" . (isset($f->comment) ? $f->comment : '-') . "</td>
                          </tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='font-size: 12px; color: #666;'>Ghi chú: Bảng hệ thống/ổn định. Nhấn để xem chi tiết (chức năng đang phát triển).</p>";
            }
            echo "<hr style='border: 0.5px dashed #ccc;'>";
        }
        
        echo "<br><a href='".base_url('run-migrations')."'>🚀 Ép buộc chạy cập nhật ngay</a> | ";
        echo "<a href='".base_url('debug-users')."'>👤 Kiểm tra tài khoản & Quyền</a>";
    }

    /**
     * Debug xem User đang có Role gì
     */
    public function debug_users()
    {
        $db = \Config\Database::connect();
        $users = $db->table('users')->select('users.id, users.email, users.role_id, roles.name as role_name')
                    ->join('roles', 'roles.id = users.role_id', 'left')
                    ->get()->getResultArray();
        
        echo "<h2>KIỂM TRA TÀI KHOẢN & VAI TRÒ</h2>";
        echo "<hr>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>
                <tr style='background: #eee;'><th>ID</th><th>Email</th><th>Role ID</th><th>Tên Vai Trò (Role)</th></tr>";
        foreach ($users as $u) {
            $isSysAdmin = ($u['role_id'] == 1);
            $style = $isSysAdmin ? "style='background: #e3f2fd; font-weight: bold;'" : "";
            echo "<tr $style>
                    <td>{$u['id']}</td>
                    <td>{$u['email']}</td>
                    <td>{$u['role_id']}</td>
                    <td>{$u['role_name']} " . ($isSysAdmin ? "⭐ (Admin tối cao)" : "") . "</td>
                  </tr>";
        }
        echo "</table>";
        
        echo "<h3>Chi tiết quyền hạn của Vai trò Admin (ID: 1)</h3>";
        $perms = $db->table('roles_permissions')
                    ->join('permissions', 'permissions.id = roles_permissions.permission_id')
                    ->where('role_id', 1)
                    ->get()->getResultArray();
        
        if (empty($perms)) {
            echo "<p style='color: red;'>⚠️ CẢNH BÁO: Vai trò Admin hiện KHÔNG có quyền nào trong database!</p>";
        } else {
            echo "<ul>";
            foreach ($perms as $p) {
                echo "<li>{$p['name']} - {$p['description']}</li>";
            }
            echo "</ul>";
        }
        
        echo "<br><a href='".base_url('run-migrations')."'>🚀 Chạy lại Migration phục hồi quyền</a>";
    }

    /**
     * Đồng bộ dữ liệu KPI từ bảng case_members (kiến trúc mới)
     */
    public function sync_kpi()
    {
        $db = \Config\Database::connect();
        
        // 1. Đồng bộ assigned_to
        $db->query("
            UPDATE case_steps cs
            INNER JOIN (
                SELECT case_id, MIN(employee_id) as employee_id
                FROM case_members
                WHERE role_in_case IN ('assignee', 'main')
                GROUP BY case_id
            ) cm ON cs.case_id = cm.case_id
            SET cs.assigned_to = cm.employee_id
            WHERE cs.assigned_to IS NULL
        ");

        // 2. Đồng bộ completed_by
        $db->query("
            UPDATE case_steps cs
            INNER JOIN (
                SELECT case_id, MIN(employee_id) as employee_id
                FROM case_members
                WHERE role_in_case IN ('assignee', 'main')
                GROUP BY case_id
            ) cm ON cs.case_id = cm.case_id
            SET cs.completed_by = cm.employee_id
            WHERE cs.completed_by IS NULL AND cs.status = 'completed'
        ");

        echo "<h2 style='color: green;'>Đồng bộ KPI thành công!</h2>";
        echo "<p>Đã cập nhật dữ liệu từ bảng case_members sang bảng case_steps.</p>";
        echo "<a href='".base_url('dashboard')."'>Quay lại Dashboard</a>";
    }
}
