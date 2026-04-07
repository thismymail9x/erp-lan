<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class PermFixController extends BaseController
{
    public function index()
    {
        if (session()->get('role_name') !== \Config\AppConstants::ROLE_ADMIN && !has_permission('sys.admin')) {
            return "Unauthorized";
        }

        $db = \Config\Database::connect();
        $perms = [
            [
                'name' => 'customer.view_all', 
                'module_group' => 'Khách hàng', 
                'description' => 'Quyền xem toàn bộ khách hàng hệ thống (Bypass isolation)'
            ],
            [
                'name' => 'case.view_all', 
                'module_group' => 'Vụ việc', 
                'description' => 'Quyền xem toàn bộ vụ việc hệ thống (Bypass isolation)'
            ],
            [
                'name' => 'case.edit_all', 
                'module_group' => 'Vụ việc', 
                'description' => 'Quyền chỉnh sửa toàn bộ vụ việc hệ thống'
            ],
            [
                'name' => 'customer.edit_all', 
                'module_group' => 'Khách hàng', 
                'description' => 'Quyền chỉnh sửa toàn bộ khách hàng hệ thống'
            ],
        ];

        $results = [];
        foreach ($perms as $p) {
            $existing = $db->table('permissions')->where('name', $p['name'])->get()->getRow();
            if (!$existing) {
                $db->table('permissions')->insert($p);
                $results[] = "Đã thêm mới quyền: " . $p['name'];
            } else {
                $results[] = "Quyền đã tồn tại: " . $p['name'];
            }
        }

        return implode("<br>", $results);
    }

    public function dumpPerms()
    {
        $db = \Config\Database::connect();
        $roles = $db->table('roles')->get()->getResultArray();
        echo "<h3>ROLES TABLE:</h3>";
        foreach($roles as $r) {
            echo "ID: " . $r['id'] . " - Name: " . $r['name'] . "<br>";
        }

        $perms = $db->table('permissions')->get()->getResultArray();
        echo "<h3>PERMISSIONS DEFINITIONS:</h3>";
        foreach($perms as $p) {
            echo "ID: " . $p['id'] . " - Name: " . $p['name'] . " - Group: " . $p['module_group'] . "<br>";
        }

        $rp = $db->table('roles_permissions')->get()->getResultArray();
        echo "<h3>ROLES <-> PERMISSIONS (Default):</h3>";
        foreach($rp as $item) {
            echo "Role ID: " . $item['role_id'] . " - Perm ID: " . $item['permission_id'] . "<br>";
        }

        $uperm = $db->table('user_permissions')->get()->getResultArray();
        echo "<h3>USER PERMISSIONS OVERRIDES:</h3>";
        foreach($uperm as $up) {
            echo "User ID: " . $up['user_id'] . " - Perm ID: " . $up['permission_id'] . " - Granted: " . $up['is_granted'] . "<br>";
        }
        return "";
    }

    /**
     * Cỗ máy quét thông minh (Master Sync Engine): Tự động phát hiện Module mới và đồng bộ Master Registry.
     * Áp dụng Quy chuẩn Số 10 trong Bộ quy trình phát triển L.A.N ERP.
     * Anh truy cập: /perm-fix/sync để kích hoạt.
     */
    public function sync()
    {
        if (session()->get('role_name') !== \Config\AppConstants::ROLE_ADMIN && !has_permission('sys.admin')) {
            return "Unauthorized access.";
        }

        $service = new \App\Services\PermissionService();
        $controllersPath = APPPATH . 'Controllers';
        $files = glob($controllersPath . '/*.php');
        
        $allResults = [];
        $taggableModules = []; // Registry for Tag modules
        
        $allResults[] = "<div style='font-family: sans-serif; padding: 20px; max-width: 800px; margin: auto;'>";
        $allResults[] = "<h2 style='color: #1d1d1f;'>🚀 Hệ thống Tự động Đồng bộ Master (Compliance Rule #10)</h2>";
        $allResults[] = "<p style='color: #8e8e93;'>Đang quét toàn bộ hệ thống: <code>" . $controllersPath . "</code>...</p><hr style='border: 0; border-top: 1px solid #d2d2d7; margin: 20px 0;'>";

        foreach ($files as $file) {
            $className = 'App\\Controllers\\' . basename($file, '.php');
            
            try {
                $reflection = new \ReflectionClass($className);
                $moduleIdentified = false;
                
                // --- 1. ĐỒNG BỘ PHÂN QUYỀN (PERMISSIONS) ---
                if ($reflection->hasProperty('modulePermissions')) {
                    $prop = $reflection->getProperty('modulePermissions');
                    $prop->setAccessible(true);
                    $info = $prop->getValue();

                    if (!empty($info['group']) && !empty($info['permissions'])) {
                        $moduleIdentified = true;
                        $allResults[] = "<div style='margin-bottom: 10px;'><strong>[Module Permissions]</strong> " . basename($file) . " <span style='color: #007aff;'>[" . $info['group'] . "]</span>";
                        $syncRes = $service->registerModulePermissions($info['group'], $info['permissions']);
                        
                        if (empty($syncRes)) {
                            $allResults[] = "<div style='color: #888; font-size: 0.9em; margin-left: 20px;'>- Quyền hạn: Đã đồng bộ (Up-to-date).</div>";
                        } else {
                            foreach($syncRes as $res) $allResults[] = "<div style='color: #34c759; font-size: 0.9em; margin-left: 20px;'>- " . $res . "</div>";
                        }
                        $allResults[] = "</div>";
                    }
                }

                // --- 2. ĐỒNG BỘ MODULE GẮN NHÃN (SMART TAGS) ---
                if ($reflection->hasProperty('taggable')) {
                    $tagProp = $reflection->getProperty('taggable');
                    $tagProp->setAccessible(true);
                    $tagInfo = $tagProp->getValue();

                    if (!empty($tagInfo['type']) && !empty($tagInfo['label'])) {
                        $moduleIdentified = true;
                        $taggableModules[] = $tagInfo;
                        $allResults[] = "<div style='margin-bottom: 10px; color: #007aff;'><strong>[Smart Tags]</strong> Active: " . $tagInfo['label'] . " (<code>" . $tagInfo['type'] . "</code>)</div>";
                    }
                }

                if ($moduleIdentified) {
                    $allResults[] = "<div style='height: 1px; background: #f2f2f2; margin: 10px 0;'></div>";
                }

            } catch (\Exception $e) {
                continue;
            }
        }

        // PERSISTENCE: Lưu trữ danh sách Module được phép gắn nhãn vào Cache hệ thống
        if (!empty($taggableModules)) {
            file_put_contents(WRITEPATH . 'tag_modules.json', json_encode($taggableModules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $allResults[] = "<div style='background: #f5f5f7; padding: 15px; border-radius: 12px; margin-top: 20px;'>";
            $allResults[] = "<strong style='color: #34c759;'>✅ Đã cập nhật Registry Tagging với " . count($taggableModules) . " module active.</strong>";
            $allResults[] = "</div>";
        }

        $allResults[] = "<hr style='border: 0; border-top: 1px solid #d2d2d7; margin: 20px 0;'>";
        $allResults[] = "<p style='text-align: center; color: #8e8e93;'><strong>Hoàn tất quá trình đồng bộ Master!</strong><br><small>Hệ thống vận hành theo Rule #10: Auto-registry & Centralized Sync.</small></p>";
        $allResults[] = "</div>";

        return implode("\n", $allResults);
    }
}
