<?php
/**
 * ==========================================
 * 汇森科技 - 图片索引扫描器 v1.0
 * ==========================================
 *
 * 功能：
 * 1. 递归扫描images目录下所有图片
 * 2. 生成JSON格式的图片索引
 * 3. 尝试匹配图片到产品数据库
 *
 * 使用方法：
 * php image_indexer.php
 * 或通过浏览器访问
 */

// 允许CLI和Web访问
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}

// 配置
define('BASE_DIR', dirname(__DIR__));
define('IMAGES_DIR', BASE_DIR . '/images');
define('INDEX_FILE', IMAGES_DIR . '/image_index.json');

// 支持的图片格式
$supported_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];

// 品牌关键词映射（用于识别）
$brand_keywords = [
    'Apple' => ['iphone', 'ipad', 'airpods', 'macbook', 'apple watch'],
    'Huawei' => ['huawei', 'mate', 'p60', 'p70', 'nova', 'pura', '华为'],
    'Xiaomi' => ['xiaomi', 'mi ', 'redmi', '小米', '红米'],
    'Honor' => ['honor', 'magic', '荣耀'],
    'OPPO' => ['oppo', 'find', 'reno'],
    'vivo' => ['vivo', 'iqoo', 'x100', 'x200'],
    'Samsung' => ['samsung', 'galaxy', '三星'],
    'OnePlus' => ['oneplus', '一加'],
    'Realme' => ['realme', '真我'],
    'Motorola' => ['motorola', 'moto', '摩托罗拉'],
    'Nokia' => ['nokia', '诺基亚'],
];

/**
 * 递归扫描目录
 */
function scanDirectory($dir, $base_path = '') {
    global $supported_extensions;
    $results = [];

    if (!is_dir($dir)) {
        return $results;
    }

    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $full_path = $dir . '/' . $file;
        $relative_path = $base_path ? $base_path . '/' . $file : $file;

        if (is_dir($full_path)) {
            // 递归扫描子目录
            $results = array_merge($results, scanDirectory($full_path, $relative_path));
        } else {
            // 检查是否是图片文件
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $supported_extensions)) {
                $results[] = [
                    'path' => $relative_path,
                    'filename' => $file,
                    'extension' => $ext,
                    'size' => filesize($full_path),
                    'modified' => filemtime($full_path),
                    'brand' => identifyBrand($file, $relative_path),
                    'type' => identifyImageType($file, $relative_path),
                ];
            }
        }
    }

    return $results;
}

/**
 * 识别品牌
 */
function identifyBrand($filename, $path) {
    global $brand_keywords;

    $search_text = strtolower($filename . ' ' . $path);

    foreach ($brand_keywords as $brand => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($search_text, strtolower($keyword)) !== false) {
                return $brand;
            }
        }
    }

    // 根据目录名识别
    $parts = explode('/', $path);
    foreach ($parts as $part) {
        foreach ($brand_keywords as $brand => $keywords) {
            if (strtolower($part) === strtolower($brand)) {
                return $brand;
            }
        }
    }

    return null;
}

/**
 * 识别图片类型
 */
function identifyImageType($filename, $path) {
    $filename_lower = strtolower($filename);
    $path_lower = strtolower($path);

    // Banner图
    if (strpos($path_lower, 'banner') !== false) {
        return 'banner';
    }

    // 缩略图
    if (strpos($filename_lower, 'thumb') !== false || strpos($filename_lower, '_s.') !== false) {
        return 'thumbnail';
    }

    // 详情图
    if (strpos($path_lower, 'detail') !== false) {
        return 'detail';
    }

    // 主图（根据序号判断）
    if (preg_match('/_(\d+)\.(jpg|png|jpeg)$/i', $filename)) {
        return 'gallery';
    }

    // 默认为产品图
    return 'product';
}

/**
 * 生成索引统计
 */
function generateStats($images) {
    $stats = [
        'total' => count($images),
        'by_brand' => [],
        'by_type' => [],
        'by_extension' => [],
        'unidentified' => 0,
    ];

    foreach ($images as $img) {
        // 按品牌统计
        $brand = $img['brand'] ?? 'Unknown';
        if (!isset($stats['by_brand'][$brand])) {
            $stats['by_brand'][$brand] = 0;
        }
        $stats['by_brand'][$brand]++;

        if ($brand === null) {
            $stats['unidentified']++;
        }

        // 按类型统计
        $type = $img['type'] ?? 'unknown';
        if (!isset($stats['by_type'][$type])) {
            $stats['by_type'][$type] = 0;
        }
        $stats['by_type'][$type]++;

        // 按扩展名统计
        $ext = $img['extension'];
        if (!isset($stats['by_extension'][$ext])) {
            $stats['by_extension'][$ext] = 0;
        }
        $stats['by_extension'][$ext]++;
    }

    // 排序
    arsort($stats['by_brand']);
    arsort($stats['by_type']);
    arsort($stats['by_extension']);

    return $stats;
}

/**
 * 主函数
 */
function main() {
    echo "========================================\n";
    echo "汇森科技 - 图片索引扫描器\n";
    echo "========================================\n";
    echo "扫描目录: " . IMAGES_DIR . "\n\n";

    // 扫描图片
    echo "正在扫描图片...\n";
    $images = scanDirectory(IMAGES_DIR);
    echo "发现 " . count($images) . " 张图片\n\n";

    // 生成统计
    $stats = generateStats($images);

    // 构建索引
    $index = [
        'generated_at' => date('Y-m-d H:i:s'),
        'base_path' => 'images/',
        'stats' => $stats,
        'images' => $images,
    ];

    // 保存索引文件
    $json = json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents(INDEX_FILE, $json);
    echo "索引已保存到: " . INDEX_FILE . "\n\n";

    // 输出统计
    echo "=== 统计信息 ===\n";
    echo "总图片数: {$stats['total']}\n";
    echo "未识别品牌: {$stats['unidentified']}\n\n";

    echo "按品牌分布:\n";
    foreach ($stats['by_brand'] as $brand => $count) {
        echo "  - {$brand}: {$count}\n";
    }

    echo "\n按类型分布:\n";
    foreach ($stats['by_type'] as $type => $count) {
        echo "  - {$type}: {$count}\n";
    }

    return $index;
}

// 运行
if (php_sapi_name() === 'cli') {
    main();
} else {
    // Web访问返回JSON
    $images = scanDirectory(IMAGES_DIR);
    $stats = generateStats($images);
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'sample' => array_slice($images, 0, 50),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
