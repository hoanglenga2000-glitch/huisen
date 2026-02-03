<?php
/**
 * ============================================
 * 清理临时文件脚本
 * ============================================
 * 功能：删除所有 .tmp 文件和无关的临时文件
 */

$deleted_files = [];
$deleted_dirs = [];
$errors = [];

// 要清理的文件和目录
$cleanup_items = [
    // 临时文件
    'images/covers/*.tmp',
    'images/details/**/*.tmp',
    
    // 临时 JSON 文件（如果不需要）
    'image_map.json',
    'image_mapping_report.json',
    
    // 临时 SQL 文件（如果不需要）
    'update_images.sql',
];

// 要删除的脚本文件（开发/测试用）
$dev_scripts = [
    'map_images_to_products.php',
    'execute_image_updates.php',
    'get_phone_images.php',
];

echo "开始清理临时文件...\n\n";

// 删除临时文件
foreach ($cleanup_items as $pattern) {
    $files = glob($pattern, GLOB_BRACE);
    foreach ($files as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                $deleted_files[] = $file;
                echo "✓ 已删除: $file\n";
            } else {
                $errors[] = "✗ 无法删除: $file\n";
            }
        }
    }
}

// 删除开发脚本
foreach ($dev_scripts as $script) {
    if (file_exists($script)) {
        if (unlink($script)) {
            $deleted_files[] = $script;
            echo "✓ 已删除开发脚本: $script\n";
        } else {
            $errors[] = "✗ 无法删除: $script\n";
        }
    }
}

// 删除空的临时目录
$temp_dirs = [
    'images/covers',
    'images/details',
];

foreach ($temp_dirs as $dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        $has_files = false;
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $has_files = true;
                break;
            }
        }
        if (!$has_files) {
            if (rmdir($dir)) {
                $deleted_dirs[] = $dir;
                echo "✓ 已删除空目录: $dir\n";
            }
        }
    }
}

echo "\n清理完成！\n";
echo "共删除 " . count($deleted_files) . " 个文件";
if (count($deleted_dirs) > 0) {
    echo "，" . count($deleted_dirs) . " 个空目录";
}
echo "\n";

if (count($errors) > 0) {
    echo "\n错误：\n";
    foreach ($errors as $error) {
        echo $error;
    }
}
