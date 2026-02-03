<?php
/**
 * 目录结构整理脚本
 * 整理根目录文件，移动到合适的目录
 */

$baseDir = dirname(__DIR__);

// 创建必要的目录
$dirs = [
    'data/logs',
    'storage/logs',
    'docs/archive'
];

foreach ($dirs as $dir) {
    $path = $baseDir . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "创建目录: $dir\n";
    }
}

// 移动日志文件
$logFiles = glob($baseDir . '/*.txt');
foreach ($logFiles as $file) {
    if (strpos(basename($file), 'log') !== false || strpos(basename($file), '价格') !== false) {
        $newPath = $baseDir . '/data/logs/' . basename($file);
        if (!file_exists($newPath)) {
            rename($file, $newPath);
            echo "移动日志文件: " . basename($file) . "\n";
        }
    }
}

// 移动说明文档到docs
$docFiles = [
    '价格同步分析使用说明.md',
    '链接修复完成说明.md',
    '链接修复说明.md',
    '资料.txt'
];

foreach ($docFiles as $file) {
    $oldPath = $baseDir . '/' . $file;
    $newPath = $baseDir . '/docs/' . $file;
    if (file_exists($oldPath) && !file_exists($newPath)) {
        rename($oldPath, $newPath);
        echo "移动文档: $file\n";
    }
}

echo "\n整理完成！\n";
echo "注意：核心文件保持原位置，确保网站正常运行。\n";
