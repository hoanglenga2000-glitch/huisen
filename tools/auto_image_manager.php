<?php
/**
 * ==========================================
 * 汇森科技 - 全自动图片猎手系统 v2.0 (PHP版)
 * ==========================================
 *
 * 功能：
 * 1. 本地扫描17000+张图片，智能匹配到产品
 * 2. 确保每个产品有5张不重复的真实图片
 * 3. 智能入库product_images表
 */

require_once dirname(__DIR__) . '/config/config.php';

// 配置
define('REQUIRED_IMAGES', 5);
define('BASE_DIR', dirname(__DIR__));
define('IMAGES_DIR', BASE_DIR . '/images');

// 全局已使用图片集合
$used_images = [];

echo "==========================================\n";
echo "汇森科技 - 全自动图片猎手系统 v2.0\n";
echo "==========================================\n\n";

$db = Database::getInstance();
$conn = $db->getConnection();

// 检查表
$use_v4 = $conn->query("SHOW TABLES LIKE 'products_spu_v4'")->rowCount() > 0;
$spu_table = $use_v4 ? 'products_spu_v4' : 'products_spu_v3';

echo "使用表: $spu_table\n\n";

// ==================== 工具函数 ====================

function normalizeModelName($name) {
    $name = strtolower(trim($name));
    $keywords = [];

    // 提取数字
    preg_match_all('/\d+/', $name, $matches);
    $keywords = array_merge($keywords, $matches[0]);

    // 提取关键后缀
    preg_match_all('/(pro|max|ultra|plus|lite|rs|se|mini|fold|flip)/i', $name, $matches);
    $keywords = array_merge($keywords, array_map('strtolower', $matches[0]));

    // 提取品牌关键词
    $brands = ['iphone', 'mate', 'xiaomi', 'mi', 'redmi', 'honor', 'magic', 'vivo', 'oppo', 'find', 'samsung', 'galaxy', 'huawei', 'nova', 'p\d+'];
    foreach ($brands as $b) {
        if (preg_match("/$b/i", $name)) {
            $keywords[] = $b;
        }
    }

    return array_unique($keywords);
}

function imageMatchesProduct($filename, $keywords, $brand) {
    $filename_lower = strtolower($filename);

    // 品牌别名
    $brand_aliases = [
        '苹果' => ['apple', 'iphone', '苹果'],
        '华为' => ['huawei', 'mate', 'nova', '华为'],
        '小米' => ['xiaomi', 'mi', 'redmi', '小米', '红米'],
        '荣耀' => ['honor', 'magic', '荣耀'],
        'OPPO' => ['oppo', 'find', 'reno'],
        'vivo' => ['vivo', 'iqoo'],
        '三星' => ['samsung', 'galaxy', '三星'],
    ];

    // 检查品牌匹配
    $brand_matched = false;
    $aliases = $brand_aliases[$brand] ?? [strtolower($brand)];
    foreach ($aliases as $alias) {
        if (strpos($filename_lower, $alias) !== false) {
            $brand_matched = true;
            break;
        }
    }

    if (!$brand_matched) {
        return 0;
    }

    // 计算关键词匹配分数
    $score = 0;
    foreach ($keywords as $kw) {
        if (strpos($filename_lower, strtolower($kw)) !== false) {
            $score += 10;
        }
    }

    // 优先选择高质量图片
    if (preg_match('/(main|cover|主图|封面|_1\.|_1_|-1\.)/i', $filename_lower)) {
        $score += 20;
    }
    if (preg_match('/(hd|高清|large|big)/i', $filename_lower)) {
        $score += 10;
    }

    return $score;
}

function scanDirectory($dir, $baseDir) {
    $images = [];
    $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $extensions)) {
                $fullPath = $file->getPathname();
                // 跳过downloaded目录
                if (strpos($fullPath, 'downloaded') !== false) {
                    continue;
                }

                $relPath = str_replace('\\', '/', substr($fullPath, strlen($baseDir) + 1));
                $images[] = [
                    'filename' => $file->getFilename(),
                    'path' => $relPath,
                    'size' => $file->getSize()
                ];
            }
        }
    }

    return $images;
}

// ==================== 步骤1: 扫描本地图片 ====================
echo "[步骤1] 扫描本地图片库...\n";

$image_index = scanDirectory(IMAGES_DIR, BASE_DIR);
echo "    找到 " . count($image_index) . " 张本地图片\n";

// ==================== 步骤2: 获取已使用图片 ====================
echo "\n[步骤2] 获取已使用图片...\n";

try {
    $stmt = $conn->query("SELECT DISTINCT image_path FROM product_images");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $used_images[$row['image_path']] = true;
    }
} catch (Exception $e) {
    // 表可能不存在
}
echo "    已有 " . count($used_images) . " 张图片被使用\n";

// ==================== 步骤3: 匹配图片到产品 ====================
echo "\n[步骤3] 智能匹配图片到产品...\n";

$stmt = $conn->query("SELECT id, brand, model_name FROM $spu_table WHERE min_price > 0 ORDER BY id");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "    共 " . count($products) . " 个产品需要处理\n";

$product_matches = [];
$total_matched = 0;

foreach ($products as $product) {
    $pid = $product['id'];
    $brand = $product['brand'];
    $model = $product['model_name'];
    $keywords = normalizeModelName($model);

    // 计算每张图片的匹配分数
    $matches = [];
    foreach ($image_index as $img) {
        if (isset($used_images[$img['path']])) {
            continue; // 跳过已使用的图片
        }

        $score = imageMatchesProduct($img['filename'], $keywords, $brand);
        if ($score > 0) {
            $matches[] = [
                'path' => $img['path'],
                'score' => $score,
                'size' => $img['size']
            ];
        }
    }

    // 按分数排序，取前5张
    usort($matches, function($a, $b) {
        if ($b['score'] !== $a['score']) {
            return $b['score'] - $a['score'];
        }
        return $b['size'] - $a['size'];
    });

    $top_matches = array_slice($matches, 0, REQUIRED_IMAGES);

    // 标记为已使用
    foreach ($top_matches as $m) {
        $used_images[$m['path']] = true;
    }

    $product_matches[$pid] = [
        'brand' => $brand,
        'model' => $model,
        'images' => array_column($top_matches, 'path')
    ];

    if (count($top_matches) > 0) {
        $total_matched++;
        if ($total_matched <= 20 || $total_matched % 50 == 0) {
            echo "    [$pid] $model: 匹配到 " . count($top_matches) . " 张图片\n";
        }
    }
}

echo "    共 $total_matched 个产品匹配到图片\n";

// ==================== 步骤4: 写入数据库 ====================
echo "\n[步骤4] 写入数据库...\n";

$total_inserted = 0;
$products_with_images = 0;

foreach ($product_matches as $pid => $data) {
    if (empty($data['images'])) {
        continue;
    }

    // 删除该产品的旧图片记录
    $conn->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$pid]);

    // 插入新记录
    $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, sort_order, created_at) VALUES (?, ?, ?, NOW())");

    foreach ($data['images'] as $sort_order => $img_path) {
        try {
            $stmt->execute([$pid, $img_path, $sort_order]);
            $total_inserted++;
        } catch (Exception $e) {
            // 跳过错误
        }
    }
    $products_with_images++;
}

echo "    共插入 $total_inserted 条图片记录\n";
echo "    覆盖 $products_with_images 个产品\n";

// ==================== 生成报告 ====================
echo "\n==========================================\n";
echo "图片匹配报告\n";
echo "==========================================\n\n";

$stats = [];
$insufficient = [];

foreach ($product_matches as $pid => $data) {
    $count = count($data['images']);
    if (!isset($stats[$count])) $stats[$count] = 0;
    $stats[$count]++;

    if ($count < REQUIRED_IMAGES && $count > 0) {
        $insufficient[] = "  [$pid] {$data['model']}: {$count}张";
    }
}

krsort($stats);
echo "图片数量分布:\n";
foreach ($stats as $count => $num) {
    echo "  {$count}张: {$num}个产品\n";
}

if (count($insufficient) > 0 && count($insufficient) <= 30) {
    echo "\n图片不足(" . REQUIRED_IMAGES . "张)的产品:\n";
    foreach (array_slice($insufficient, 0, 20) as $item) {
        echo "$item\n";
    }
    if (count($insufficient) > 20) {
        echo "  ... 还有 " . (count($insufficient) - 20) . " 个\n";
    }
}

echo "\n==========================================\n";
echo "✓ 全部完成!\n";
echo "==========================================\n";
