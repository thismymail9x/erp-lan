<?php
/**
 * sync_permissions.php
 * Script tự động đồng bộ Phân Quyền (RBAC) & Tag Registry từ CLI.
 * Tuân thủ Rule #1 (Việt hóa 100%), Rule #10 (Master Sync & Permissions Registry).
 */

define('FCPATH', __DIR__ . '/public/');
require __DIR__ . '/app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';

use CodeIgniter\Boot;

// Khởi tạo hạt nhân CodeIgniter 4
$app = Boot::setup($paths);
$app->initialize();

$service = new \App\Services\PermissionService();
$controllersPath = APPPATH . 'Controllers';
$files = glob($controllersPath . '/*.php');

echo "🚀 Bắt đầu chạy Đồng bộ Master Sync Engine (Rule #10) từ CLI...\n";
echo "Thư mục Controllers quét: " . $controllersPath . "\n";
echo "---------------------------------------------------------\n";

$taggableModules = [];

foreach ($files as $file) {
    $className = 'App\\Controllers\\' . basename($file, '.php');
    
    try {
        if (!class_exists($className)) {
            continue;
        }
        
        $reflection = new \ReflectionClass($className);
        
        // 1. ĐỒNG BỘ PHÂN QUYỀN (PERMISSIONS)
        if ($reflection->hasProperty('modulePermissions')) {
            $prop = $reflection->getProperty('modulePermissions');
            $prop->setAccessible(true);
            $info = $prop->getValue();

            if (!empty($info['group']) && !empty($info['permissions'])) {
                echo "✔ Phát hiện Module: [" . $info['group'] . "] trong class " . basename($file) . "\n";
                $syncRes = $service->registerModulePermissions($info['group'], $info['permissions']);
                
                if (empty($syncRes)) {
                    echo "  -> Đã đồng bộ (Up-to-date).\n";
                } else {
                    foreach ($syncRes as $res) {
                        echo "  -> " . strip_tags($res) . "\n";
                    }
                }
            }
        }

        // 2. ĐỒNG BỘ MODULE GẮN NHÃN (SMART TAGS)
        if ($reflection->hasProperty('taggable')) {
            $tagProp = $reflection->getProperty('taggable');
            $tagProp->setAccessible(true);
            $tagInfo = $tagProp->getValue();

            if (!empty($tagInfo['type']) && !empty($tagInfo['label'])) {
                $taggableModules[] = $tagInfo;
                echo "✔ Phát hiện Tag Active: " . $tagInfo['label'] . " (" . $tagInfo['type'] . ")\n";
            }
        }

    } catch (\Exception $e) {
        echo "⚠️ Lỗi khi quét lớp $className: " . $e->getMessage() . "\n";
    }
}

if (!empty($taggableModules)) {
    file_put_contents(WRITEPATH . 'tag_modules.json', json_encode($taggableModules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo "---------------------------------------------------------\n";
    echo "✅ Đã lưu Tag Registry thành công với " . count($taggableModules) . " module active.\n";
}

echo "---------------------------------------------------------\n";
echo "🎉 Đồng bộ hoàn tất thành công! Mọi phân quyền đã được cập nhật.\n";
