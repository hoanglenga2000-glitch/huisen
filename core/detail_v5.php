<?php
/**
 * ==========================================
 * V5 专业详情页 - JD Pro 京东级别视觉
 * ==========================================
 * Stage 5 升级：
 * 1. 12栏格局 (5:7 黄金比例)
 * 2. 面包屑导航
 * 3. 价格区渐变背景 + 利润胶囊
 * 4. Pill Button SKU选择器
 * 5. 底部Tab详情区 + 热销推荐侧边栏
 */

require_once '../config/config.php';

// 设置 Base Path，用于 header.php 中的 CSS 路径
$base_path = '../';

$db = Database::getInstance();
$conn = $db->getConnection();

// 检查使用哪个表
$use_v4 = $conn->query("SHOW TABLES LIKE 'products_spu_v4'")->rowCount() > 0;
$spu_table = $use_v4 ? 'products_spu_v4' : 'products_spu_v3';
$sku_table = $use_v4 ? 'products_sku_v4' : 'products_sku_v3';

$spu_id = intval($_GET['spu'] ?? 0);

if ($spu_id <= 0) {
    header('Location: /core/quotes_v6.php');
    exit;
}

// 查询SPU
$stmt = $conn->prepare("SELECT * FROM $spu_table WHERE id = ?");
$stmt->execute([$spu_id]);
$spu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$spu) {
    header('Location: /core/quotes_v6.php');
    exit;
}

// 智能扫描匹配产品图片
function scanProductImages($brand, $model_name, $base_dir) {
    $images = [];
    $brand_dirs = [
        'Apple' => ['苹果', 'Apple'],
        '苹果' => ['苹果', 'Apple'],
        'Huawei' => ['华为', 'Huawei'],
        '华为' => ['华为', 'Huawei'],
        'Xiaomi' => ['小米', 'Xiaomi'],
        '小米' => ['小米', 'Xiaomi'],
        'Honor' => ['荣耀', 'Honor'],
        '荣耀' => ['荣耀', 'Honor'],
        'OPPO' => ['Oppo', 'OPPO'],
        'vivo' => ['Vivo', 'vivo'],
        'Samsung' => ['三星', 'Samsung'],
        '三星' => ['三星', 'Samsung'],
        'Redmi' => ['红米', 'Redmi'],
        '红米' => ['红米', 'Redmi'],
    ];

    $keywords = [];
    if (preg_match('/(\d+)/', $model_name, $m)) {
        $keywords[] = $m[1];
    }
    if (preg_match('/(Pro|Ultra|Max|Plus|Lite)/i', $model_name, $m)) {
        $keywords[] = strtolower($m[1]);
    }

    $search_dirs = [];
    $dirs_to_check = $brand_dirs[$brand] ?? [$brand];
    foreach ($dirs_to_check as $dir_name) {
        $search_dirs[] = $base_dir . '/图片素材/' . $dir_name;
    }

    foreach ($search_dirs as $dir) {
        if (!is_dir($dir)) continue;

        $files = scandir($dir);
        $matched = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            if (!preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) continue;

            $file_lower = strtolower($file);
            $score = 0;

            foreach ($keywords as $kw) {
                if (strpos($file_lower, strtolower($kw)) !== false) {
                    $score += 50;
                }
            }

            if ($score > 0) {
                if (preg_match('/_1\.(jpg|png)/i', $file)) {
                    $score += 100;
                }
                $matched[] = [
                    'path' => '图片素材/' . basename($dir) . '/' . $file,
                    'score' => $score
                ];
            }
        }

        usort($matched, fn($a, $b) => $b['score'] - $a['score']);
        foreach (array_slice($matched, 0, 5) as $m) {
            $images[] = $m['path'];
        }

        if (count($images) >= 5) break;
    }

    return array_slice($images, 0, 5);
}

// 获取产品图片
$product_images = [];
$main_image = $spu['image_url'] ?? '';

try {
    $img_stmt = $conn->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 5");
    $img_stmt->execute([$spu_id]);
    $db_images = $img_stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($db_images)) {
        $product_images = $db_images;
    }
} catch (Exception $e) {}

if (empty($product_images)) {
    $scanned = scanProductImages($spu['brand'], $spu['model_name'], dirname(__DIR__) . '/images');
    if (!empty($scanned)) {
        $product_images = $scanned;
        if (empty($main_image) || !file_exists('../' . $main_image)) {
            $main_image = $product_images[0];
        }
    }
}

if (!empty($main_image) && !in_array($main_image, $product_images)) {
    array_unshift($product_images, $main_image);
}
$product_images = array_slice($product_images, 0, 5);

// 查询所有SKU
$stmt = $conn->prepare("SELECT * FROM $sku_table WHERE spu_id = ? ORDER BY price ASC");
$stmt->execute([$spu_id]);
$skus = $stmt->fetch(PDO::FETCH_ASSOC) ? [] : [];
$stmt->execute([$spu_id]);
$skus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 提取颜色和存储选项
$colors = [];
$storages = [];
$sku_matrix = [];

foreach ($skus as $sku) {
    $color = $sku['color'] ?? '标准';
    $storage = $sku['storage'] ?? '标准';

    if (!in_array($color, $colors)) $colors[] = $color;
    if (!in_array($storage, $storages)) $storages[] = $storage;

    $key = $color . '|' . $storage;
    $sku_matrix[$key] = [
        'id' => $sku['id'],
        'price' => floatval($sku['price']),
        'official_price' => floatval($sku['official_price'] ?? 0),
        'stock' => $sku['stock_status'] ?? 'in_stock',
        'full_name' => $sku['full_name']
    ];
}

// 颜色十六进制映射
$color_hex = [
    '黑色' => '#1a1a1a', '曜石黑' => '#0a0a0a', '雅丹黑' => '#1f1f1f', '羽砂黑' => '#2d2d2d',
    '午夜色' => '#1e293b', '深空灰' => '#4a4a4a', '深空黑' => '#1a1a1a', '钛金黑' => '#2d2d2d',
    '白色' => '#fafafa', '洛可可白' => '#fff8f0', '羽砂白' => '#f8f8f8', '星光色' => '#f5e6d3',
    '凝霜白' => '#f8f8f8', '雪域白' => '#ffffff', '冰川白' => '#f0f8ff',
    '蓝色' => '#3b82f6', '远峰蓝' => '#7dd3fc', '冰川蓝' => '#a5d8ff', '海蓝色' => '#0ea5e9',
    '深蓝色' => '#1e40af', '宝石蓝' => '#2563eb', '冰雪蓝' => '#bae6fd', '冰晶蓝' => '#7dd3fc',
    '绿色' => '#22c55e', '原野绿' => '#16a34a', '薄荷绿' => '#86efac', '翡冷翠' => '#059669',
    '紫色' => '#a855f7', '南糯紫' => '#7c3aed', '丁香紫' => '#c084fc', '烟紫色' => '#9333ea',
    '粉色' => '#ec4899', '樱花粉' => '#f9a8d4',
    '金色' => '#d4a574', '沙漠金' => '#c9a227', '玫瑰金' => '#e8b4b4',
    '银色' => '#c0c0c0', '星河银' => '#d1d5db', '原色钛金属' => '#8b8680',
    '灰色' => '#6b7280', '岩石灰' => '#52525b', '钛金灰' => '#71717a',
    '红色' => '#ef4444', '中国红' => '#dc2626',
    '棕色' => '#92400e', '琥珀棕' => '#b5651d',
    '青色' => '#4a9b8c', '雅川青' => '#4a9b8c',
    '橙色' => '#f97316', '星宇橙色' => '#fb923c',
];

// 热销推荐
$recommendations = $conn->prepare("
    SELECT p.*, pi.image_path as cover_image
    FROM $spu_table p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.sort_order = 0
    WHERE p.id != ? AND p.brand = ? AND p.min_price > 0
    ORDER BY RAND()
    LIMIT 6
");
$recommendations->execute([$spu_id, $spu['brand']]);
$recommended_products = $recommendations->fetchAll(PDO::FETCH_ASSOC);

// 计算默认利润
$default_official = $skus[0]['official_price'] ?? 0;
$default_price = $spu['min_price'];
$default_profit = $default_official > $default_price ? ($default_official - $default_price) : 0;

// 获取默认 SKU ID（用于跳转到结算页）
$default_sku_id = $skus[0]['id'] ?? 0;

// 模拟在线人数
$online_users = rand(128, 356);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($spu['model_name']); ?> - 汇森科技批发</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }

        /* SKU Pill Buttons - 京东标志性设计 */
        .sku-pill {
            position: relative;
            padding: 10px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .sku-pill:hover:not(.disabled) {
            border-color: #fca5a5;
            background: #fef2f2;
        }
        .sku-pill.active {
            border-color: #e1251b;
            color: #e1251b;
            background: #fef2f2;
            font-weight: 600;
        }
        /* 右下角对勾 */
        .sku-pill.active::after {
            content: '';
            position: absolute;
            right: -2px;
            bottom: -2px;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 0 20px 20px;
            border-color: transparent transparent #e1251b transparent;
        }
        .sku-pill.active::before {
            content: '✓';
            position: absolute;
            right: 2px;
            bottom: 1px;
            color: white;
            font-size: 10px;
            font-weight: bold;
            z-index: 1;
        }
        .sku-pill.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            text-decoration: line-through;
        }

        /* 颜色圆点 */
        .color-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid #d1d5db;
            flex-shrink: 0;
        }

        /* 价格区域渐变 */
        .price-box {
            background: linear-gradient(135deg, #fff5f5 0%, #fef2f2 50%, #fff8f0 100%);
            border-radius: 12px;
        }

        /* 缩略图 */
        .thumb-item {
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        .thumb-item:hover {
            border-color: #fca5a5;
        }
        .thumb-item.active {
            border-color: #e1251b;
            box-shadow: 0 0 0 2px rgba(225, 37, 27, 0.2);
        }

        /* Tab 样式 */
        .tab-btn {
            padding: 12px 24px;
            font-weight: 500;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            color: #e1251b;
        }
        .tab-btn.active {
            color: #e1251b;
            border-bottom-color: #e1251b;
        }

        /* 推荐卡片 */
        .recommend-card {
            transition: all 0.3s ease;
        }
        .recommend-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        /* 数量选择器 */
        .qty-btn {
            width: 32px;
            height: 32px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .qty-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        .qty-input {
            width: 50px;
            height: 32px;
            border: 1px solid #e5e7eb;
            border-left: none;
            border-right: none;
            text-align: center;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-50 pb-20 md:pb-0">
    <!-- ==========================================
         Mobile Header (手机端 - 沉浸式返回按钮)
         ========================================== -->
    <div class="md:hidden fixed top-0 left-0 z-50 p-3" style="padding-top: calc(env(safe-area-inset-top) + 12px);">
        <button onclick="history.back()"
                class="w-10 h-10 bg-black/30 backdrop-blur-sm rounded-full flex items-center justify-center text-white shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    <!-- PC Header (仅电脑端显示) -->
    <div class="hidden md:block">
        <?php include '../includes/header.php'; ?>
    </div>

    <!-- 面包屑导航 (仅电脑端显示) -->
    <div class="hidden md:block bg-white border-b">
        <div class="max-w-screen-xl mx-auto px-4 py-3">
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <a href="index_v4.php" class="hover:text-primary transition">首页</a>
                <span>></span>
                <a href="quotes_v6.php" class="hover:text-primary transition">手机数码</a>
                <span>></span>
                <a href="quotes_v6.php?brand=<?php echo urlencode($spu['brand']); ?>" class="hover:text-primary transition"><?php echo htmlspecialchars($spu['brand']); ?></a>
                <span>></span>
                <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($spu['model_name']); ?></span>
            </nav>
        </div>
    </div>

    <main class="max-w-screen-xl mx-auto px-0 md:px-4 py-0 md:py-8">
        <!-- Hero Section - 首屏核心区 -->
        <div class="bg-white md:rounded-xl md:shadow-sm p-0 md:p-6 mb-0 md:mb-8">
            <div class="grid grid-cols-12 gap-0 md:gap-8">

                <!-- 左侧：Gallery 图片区 (col-span-5) -->
                <div class="col-span-12 lg:col-span-5">
                    <!-- 主图 -->
                    <div class="aspect-square w-full md:rounded-lg border-0 md:border border-gray-100 bg-gray-50 flex items-center justify-center mb-0 md:mb-4 overflow-hidden" id="mainImageContainer">
                        <?php if (!empty($product_images)): ?>
                            <img src="../<?php echo htmlspecialchars($product_images[0]); ?>"
                                 alt="<?php echo htmlspecialchars($spu['model_name']); ?>"
                                 class="max-w-full max-h-full object-contain transition-all duration-300"
                                 id="mainImage"
                                 onerror="this.src='../images/default_phone_placeholder.svg'">
                        <?php else: ?>
                            <div class="text-center text-gray-300">
                                <div class="text-8xl mb-4">📱</div>
                                <span class="text-gray-400"><?php echo htmlspecialchars($spu['brand']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 缩略图列表 -->
                    <div class="flex gap-2 px-4 md:px-0 mt-2 md:mt-4 overflow-x-auto pb-2 md:pb-0">
                        <?php if (!empty($product_images)): ?>
                            <?php foreach ($product_images as $idx => $thumb): ?>
                            <div class="thumb-item w-14 h-14 md:w-16 md:h-16 rounded-lg bg-gray-50 flex items-center justify-center cursor-pointer p-1 flex-shrink-0 <?php echo $idx === 0 ? 'active' : ''; ?>"
                                 data-image="../<?php echo htmlspecialchars($thumb); ?>"
                                 onclick="switchImage(this)">
                                <img src="../<?php echo htmlspecialchars($thumb); ?>"
                                     alt="图片 <?php echo $idx + 1; ?>"
                                     class="max-w-full max-h-full object-contain"
                                     onerror="this.parentNode.innerHTML='<span class=\'text-gray-400 text-xs\'>图片</span>'">
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 右侧：Info 信息区 (col-span-7) -->
                <div class="col-span-12 lg:col-span-7 space-y-4 md:space-y-5 px-4 md:px-0 pt-4 md:pt-0">
                    <!-- Header: 标题区 -->
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900 leading-tight mb-2">
                            <?php echo htmlspecialchars($spu['model_name']); ?>
                        </h1>
                        <p class="text-red-500 text-xs md:text-sm font-medium">
                            🔥 限时直降500元，满10台包邮，正品保障
                        </p>
                    </div>

                    <!-- Price Box: 价格区 -->
                    <div class="price-box p-4 md:p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-baseline gap-2 md:gap-3 mb-2 flex-wrap">
                                    <span class="text-xs md:text-sm text-gray-500">批发价</span>
                                    <span class="text-2xl md:text-3xl font-bold text-primary" id="currentPrice">
                                        ¥<?php echo number_format($spu['min_price'], 0); ?>
                                    </span>
                                    <?php if (count($skus) > 1): ?>
                                    <span class="text-lg text-red-500" id="priceSuffix">起</span>
                                    <?php endif; ?>
                                    <span class="text-lg text-gray-400 line-through ml-2" id="officialPrice">
                                        <?php if ($default_official > 0): ?>
                                        官网价 ¥<?php echo number_format($default_official, 0); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <!-- 利润胶囊 -->
                                <div class="flex items-center gap-3">
                                    <?php if ($default_profit > 0): ?>
                                    <span class="bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full font-medium" id="profitBadge">
                                        💰 预估利润 ¥<?php echo number_format($default_profit, 0); ?>+
                                    </span>
                                    <?php endif; ?>
                                    <span class="bg-orange-100 text-orange-600 text-xs px-3 py-1 rounded-full font-medium">
                                        源头直供
                                    </span>
                                    <span class="bg-blue-100 text-blue-600 text-xs px-3 py-1 rounded-full font-medium">
                                        <?php echo $online_users; ?>人在看
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Specs: SKU 选择区 -->
                    <div class="space-y-5">
                        <?php if (count($colors) > 1 || (count($colors) == 1 && $colors[0] != '标准')): ?>
                        <!-- 颜色选择 -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-gray-700 font-medium">颜色</span>
                                <span class="text-gray-400 text-sm" id="selectedColorLabel"></span>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <?php foreach ($colors as $color):
                                    $hex = $color_hex[$color] ?? '#888888';
                                    $is_light = in_array($color, ['白色', '星光色', '洛可可白', '羽砂白', '银色', '凝霜白', '雪域白', '冰川白']);
                                ?>
                                <button type="button"
                                        class="sku-pill"
                                        data-color="<?php echo htmlspecialchars($color); ?>"
                                        onclick="selectColor(this)">
                                    <span class="color-dot" style="background-color: <?php echo $hex; ?>; <?php echo $is_light ? 'border-color: #9ca3af;' : ''; ?>"></span>
                                    <span><?php echo htmlspecialchars($color); ?></span>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (count($storages) > 1 || (count($storages) == 1 && $storages[0] != '标准')): ?>
                        <!-- 版本/内存选择 -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-gray-700 font-medium">内存</span>
                                <span class="text-gray-400 text-sm" id="selectedStorageLabel"></span>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <?php foreach ($storages as $storage): ?>
                                <button type="button"
                                        class="sku-pill"
                                        data-storage="<?php echo htmlspecialchars($storage); ?>"
                                        onclick="selectStorage(this)">
                                    <?php echo htmlspecialchars($storage); ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions: 数量 + 按钮 (仅PC端显示，移动端使用吸底购买栏) -->
                    <div class="hidden md:flex items-center gap-4 pt-4 border-t">
                        <!-- 数量选择器 -->
                        <div class="flex items-center">
                            <span class="text-gray-600 text-sm mr-3">数量</span>
                            <button class="qty-btn rounded-l" onclick="changeQty(-1)">−</button>
                            <input type="text" value="1" id="qtyInput" class="qty-input" readonly>
                            <button class="qty-btn rounded-r" onclick="changeQty(1)">+</button>
                        </div>

                        <!-- 按钮组 -->
                        <div class="flex gap-3 flex-1">
                            <a href="cart.php"
                                    class="flex-1 py-3 px-6 rounded-lg font-bold text-orange-600 bg-orange-100 border border-orange-200 hover:bg-orange-200 transition text-center">
                                加入进货单
                            </a>
                            <a href="checkout.php<?php echo $default_sku_id > 0 ? '?sku_id=' . $default_sku_id . '&qty=1' : ''; ?>"
                                    class="flex-1 py-3 px-6 rounded-lg font-bold text-white bg-primary hover:bg-primary-dark transition shadow-lg shadow-red-200 text-center">
                                立即下单
                            </a>
                        </div>
                    </div>

                    <!-- 服务保障 -->
                    <div class="flex items-center gap-6 text-sm text-gray-500 pt-4">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            正品保障
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            闪电发货
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            7天退换
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            全国联保
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Section: 底部详情区 -->
        <div class="grid grid-cols-12 gap-4 md:gap-6 px-4 md:px-0 mt-4 md:mt-0 pb-20 md:pb-0">
            <!-- 左侧: Sidebar 热销推荐 (col-span-3) -->
            <div class="col-span-12 lg:col-span-3">
                <div class="bg-white rounded-xl shadow-sm p-4 sticky top-20">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="text-orange-500">🔥</span>
                        热销推荐
                    </h3>
                    <div class="space-y-4">
                        <?php foreach ($recommended_products as $rec): ?>
                        <div class="recommend-card flex gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer"
                             onclick="location.href='detail_v5.php?spu=<?php echo $rec['id']; ?>'">
                            <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <?php
                                $rec_img = $rec['cover_image'] ?? $rec['image_url'] ?? '';
                                if (!empty($rec_img) && file_exists('../' . $rec_img)): ?>
                                    <img src="../<?php echo htmlspecialchars($rec_img); ?>"
                                         alt="<?php echo htmlspecialchars($rec['model_name']); ?>"
                                         class="max-w-full max-h-full object-contain">
                                <?php else: ?>
                                    <span class="text-2xl text-gray-300">📱</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($rec['model_name']); ?></h4>
                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($rec['brand']); ?></p>
                                <p class="text-primary font-bold mt-1">¥<?php echo number_format($rec['min_price'], 0); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 右侧: Main 详情内容 (col-span-9) -->
            <div class="col-span-12 lg:col-span-9">
                <!-- Tabs -->
                <div class="bg-white rounded-t-xl shadow-sm border-b sticky top-16 z-10">
                    <div class="flex">
                        <button class="tab-btn active" onclick="switchTab('intro')">商品介绍</button>
                        <button class="tab-btn" onclick="switchTab('specs')">规格参数</button>
                        <button class="tab-btn" onclick="switchTab('service')">售后保障</button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="bg-white rounded-b-xl shadow-sm p-6">
                    <!-- 商品介绍 -->
                    <div id="tab-intro" class="tab-content">
                        <div class="prose max-w-none">
                            <h3 class="text-lg font-bold mb-4">📋 全部配置报价</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 border">配置</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 border">颜色</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 border">版本</th>
                                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 border">批发价</th>
                                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 border">官网价</th>
                                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700 border">优惠</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($skus as $sku): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm border"><?php echo htmlspecialchars($sku['full_name']); ?></td>
                                            <td class="px-4 py-3 border">
                                                <?php if (!empty($sku['color']) && $sku['color'] !== '标准'): ?>
                                                <span class="inline-flex items-center gap-2 text-sm">
                                                    <span class="w-3 h-3 rounded-full border"
                                                          style="background: <?php echo $color_hex[$sku['color']] ?? '#888'; ?>"></span>
                                                    <?php echo htmlspecialchars($sku['color']); ?>
                                                </span>
                                                <?php else: ?>-<?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm border"><?php echo htmlspecialchars($sku['storage'] ?? '-'); ?></td>
                                            <td class="px-4 py-3 text-right font-bold text-primary border">
                                                ¥<?php echo number_format($sku['price'], 0); ?>
                                            </td>
                                            <td class="px-4 py-3 text-right text-gray-400 text-sm border">
                                                <?php echo ($sku['official_price'] ?? 0) > 0 ? '¥' . number_format($sku['official_price'], 0) : '-'; ?>
                                            </td>
                                            <td class="px-4 py-3 text-right text-green-600 text-sm font-medium border">
                                                <?php
                                                $save = ($sku['official_price'] ?? 0) - $sku['price'];
                                                echo $save > 0 ? '省 ¥' . number_format($save, 0) : '-';
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 规格参数 -->
                    <div id="tab-specs" class="tab-content hidden">
                        <h3 class="text-lg font-bold mb-4">📐 规格参数</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex justify-between py-3 border-b">
                                <span class="text-gray-500">品牌</span>
                                <span class="font-medium"><?php echo htmlspecialchars($spu['brand']); ?></span>
                            </div>
                            <div class="flex justify-between py-3 border-b">
                                <span class="text-gray-500">型号</span>
                                <span class="font-medium"><?php echo htmlspecialchars($spu['model_name']); ?></span>
                            </div>
                            <div class="flex justify-between py-3 border-b">
                                <span class="text-gray-500">可选颜色</span>
                                <span class="font-medium"><?php echo implode('、', $colors); ?></span>
                            </div>
                            <div class="flex justify-between py-3 border-b">
                                <span class="text-gray-500">可选版本</span>
                                <span class="font-medium"><?php echo implode('、', $storages); ?></span>
                            </div>
                            <div class="flex justify-between py-3 border-b">
                                <span class="text-gray-500">价格区间</span>
                                <span class="font-medium text-primary">¥<?php echo number_format($spu['min_price'], 0); ?> - ¥<?php echo number_format($spu['max_price'] ?? $spu['min_price'], 0); ?></span>
                            </div>
                            <div class="flex justify-between py-3 border-b">
                                <span class="text-gray-500">SKU数量</span>
                                <span class="font-medium"><?php echo count($skus); ?> 款</span>
                            </div>
                        </div>
                    </div>

                    <!-- 售后保障 -->
                    <div id="tab-service" class="tab-content hidden">
                        <h3 class="text-lg font-bold mb-4">🛡️ 售后保障</h3>
                        <div class="space-y-6">
                            <div class="flex gap-4 p-4 bg-green-50 rounded-lg">
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-xl">✓</div>
                                <div>
                                    <h4 class="font-bold text-gray-900">正品保障</h4>
                                    <p class="text-gray-600 text-sm mt-1">所有产品均为100%原装正品，支持官方验证</p>
                                </div>
                            </div>
                            <div class="flex gap-4 p-4 bg-blue-50 rounded-lg">
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xl">⚡</div>
                                <div>
                                    <h4 class="font-bold text-gray-900">闪电发货</h4>
                                    <p class="text-gray-600 text-sm mt-1">当日14:00前下单，当日发货；支持顺丰/京东物流</p>
                                </div>
                            </div>
                            <div class="flex gap-4 p-4 bg-orange-50 rounded-lg">
                                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 text-xl">↩</div>
                                <div>
                                    <h4 class="font-bold text-gray-900">7天无理由退换</h4>
                                    <p class="text-gray-600 text-sm mt-1">未激活产品7天内支持无理由退换货</p>
                                </div>
                            </div>
                            <div class="flex gap-4 p-4 bg-purple-50 rounded-lg">
                                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-xl">🛡</div>
                                <div>
                                    <h4 class="font-bold text-gray-900">全国联保</h4>
                                    <p class="text-gray-600 text-sm mt-1">享受官方售后服务，全国联保</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/sidebar-tools.php'; ?>
    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script>
    const skuMatrix = <?php echo json_encode($sku_matrix, JSON_UNESCAPED_UNICODE); ?>;
    const allColors = <?php echo json_encode($colors, JSON_UNESCAPED_UNICODE); ?>;
    const allStorages = <?php echo json_encode($storages, JSON_UNESCAPED_UNICODE); ?>;

    let selectedColor = null;
    let selectedStorage = null;
    let quantity = 1;

    function selectColor(btn) {
        if (btn.classList.contains('disabled')) return;
        document.querySelectorAll('[data-color]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedColor = btn.dataset.color;
        document.getElementById('selectedColorLabel').textContent = selectedColor;
        updateAvailableStorages();
        updatePrice();
    }

    function selectStorage(btn) {
        if (btn.classList.contains('disabled')) return;
        document.querySelectorAll('[data-storage]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedStorage = btn.dataset.storage;
        document.getElementById('selectedStorageLabel').textContent = selectedStorage;
        updateAvailableColors();
        updatePrice();
    }

    function updateAvailableStorages() {
        document.querySelectorAll('[data-storage]').forEach(btn => {
            const storage = btn.dataset.storage;
            const key = selectedColor + '|' + storage;
            if (skuMatrix[key]) {
                btn.classList.remove('disabled');
            } else {
                btn.classList.add('disabled');
                if (selectedStorage === storage) {
                    selectedStorage = null;
                    btn.classList.remove('active');
                    document.getElementById('selectedStorageLabel').textContent = '';
                }
            }
        });
    }

    function updateAvailableColors() {
        document.querySelectorAll('[data-color]').forEach(btn => {
            const color = btn.dataset.color;
            const key = color + '|' + selectedStorage;
            if (skuMatrix[key]) {
                btn.classList.remove('disabled');
            } else {
                btn.classList.add('disabled');
            }
        });
    }

    function updatePrice() {
        const priceEl = document.getElementById('currentPrice');
        const officialEl = document.getElementById('officialPrice');
        const profitEl = document.getElementById('profitBadge');
        const suffixEl = document.getElementById('priceSuffix');

        if (selectedColor && selectedStorage) {
            const key = selectedColor + '|' + selectedStorage;
            const sku = skuMatrix[key];

            if (sku) {
                // 数字滚动动画
                animateValue(priceEl, sku.price);
                if (suffixEl) suffixEl.style.display = 'none';

                if (sku.official_price > 0) {
                    officialEl.textContent = '官网价 ¥' + sku.official_price.toLocaleString();
                    const profit = sku.official_price - sku.price;
                    if (profit > 0 && profitEl) {
                        profitEl.textContent = '💰 预估利润 ¥' + profit.toLocaleString() + '+';
                        profitEl.style.display = 'inline-block';
                    }
                } else {
                    officialEl.textContent = '';
                    if (profitEl) profitEl.style.display = 'none';
                }
            }
        }
    }

    // 数字滚动动画
    function animateValue(el, newValue) {
        el.style.transform = 'translateY(-10px)';
        el.style.opacity = '0';
        setTimeout(() => {
            el.textContent = '¥' + newValue.toLocaleString();
            el.style.transform = 'translateY(0)';
            el.style.opacity = '1';
        }, 150);
    }

    function changeQty(delta) {
        quantity = Math.max(1, quantity + delta);
        document.getElementById('qtyInput').value = quantity;
    }

    function switchImage(thumbDiv) {
        const newSrc = thumbDiv.dataset.image;
        const mainImg = document.getElementById('mainImage');
        if (mainImg) {
            mainImg.style.opacity = '0.5';
            setTimeout(() => {
                mainImg.src = newSrc;
                mainImg.style.opacity = '1';
            }, 150);
        }
        document.querySelectorAll('.thumb-item').forEach(div => div.classList.remove('active'));
        thumbDiv.classList.add('active');
    }

    function switchTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));

        event.target.classList.add('active');
        document.getElementById('tab-' + tabId).classList.remove('hidden');
    }

    function contact() {
        let msg = '咨询产品: <?php echo addslashes($spu['model_name']); ?>';
        if (selectedColor) msg += '\n颜色: ' + selectedColor;
        if (selectedStorage) msg += '\n版本: ' + selectedStorage;
        msg += '\n数量: ' + quantity + ' 台';
        msg += '\n\n客服微信: huisen_tech\n电话: 400-XXX-XXXX';
        alert(msg);
    }

    async function addToCart() {
        // 自动选择
        if (!selectedColor && allColors.length > 0) {
            if (allColors.length === 1) {
                selectedColor = allColors[0];
                const colorBtn = document.querySelector(`[data-color="${selectedColor}"]`);
                if (colorBtn) colorBtn.classList.add('active');
            } else if (allColors[0] !== '标准') {
                alert('请先选择颜色');
                return;
            }
        }

        if (!selectedStorage && allStorages.length > 0) {
            if (allStorages.length === 1) {
                selectedStorage = allStorages[0];
                const storageBtn = document.querySelector(`[data-storage="${selectedStorage}"]`);
                if (storageBtn) storageBtn.classList.add('active');
            } else if (allStorages[0] !== '标准') {
                alert('请先选择版本');
                return;
            }
        }

        const color = selectedColor || (allColors.length > 0 ? allColors[0] : '标准');
        const storage = selectedStorage || (allStorages.length > 0 ? allStorages[0] : '标准');

        let key = color + '|' + storage;
        let sku = skuMatrix[key];

        if (!sku) {
            for (const c of allColors) {
                for (const s of allStorages) {
                    const testKey = c + '|' + s;
                    if (skuMatrix[testKey]) {
                        sku = skuMatrix[testKey];
                        break;
                    }
                }
                if (sku) break;
            }
        }

        if (!sku || !sku.id) {
            alert('无法获取商品信息，请刷新页面重试');
            return;
        }

        try {
            const response = await fetch('/api/cart.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sku_id: sku.id, qty: quantity })
            });

            const result = await response.json();

            if (result.success) {
                alert('✓ 已加入询价单！\n\n<?php echo addslashes($spu['model_name']); ?>' +
                      (color !== '标准' ? ' - ' + color : '') +
                      (storage !== '标准' ? ' - ' + storage : '') +
                      '\n数量: ' + quantity + ' 台');
            } else {
                alert('添加失败：' + (result.error || '未知错误'));
            }
        } catch (error) {
            alert('网络错误，请稍后重试');
        }
    }
    </script>

    <!-- ==========================================
         Mobile Sticky Action Bar (吸底购买栏)
         仅手机端显示
         ========================================== -->
    <div class="md:hidden fixed bottom-0 left-0 w-full z-50 bg-white border-t border-gray-100 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]"
         style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="h-14 flex items-center px-4 gap-3">
            <!-- 客服图标 -->
            <a href="javascript:contact()" class="flex flex-col items-center justify-center w-12 text-gray-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span class="text-[10px] mt-0.5">客服</span>
            </a>

            <!-- 店铺图标 -->
            <a href="../core/index_v4.php" class="flex flex-col items-center justify-center w-12 text-gray-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-[10px] mt-0.5">店铺</span>
            </a>

            <!-- 加入进货单按钮 -->
            <a href="cart.php"
                    class="flex-1 h-10 bg-orange-400 text-white font-bold rounded-full
                           active:bg-orange-500 transition-colors flex items-center justify-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                加入进货单
            </a>

            <!-- 立即下单按钮 -->
            <a href="checkout.php<?php echo $default_sku_id > 0 ? '?sku_id=' . $default_sku_id . '&qty=1' : ''; ?>"
                    class="flex-1 h-10 bg-primary text-white font-bold rounded-full
                           active:bg-primary-dark transition-colors flex items-center justify-center">
                立即下单
            </a>
        </div>
    </div>
</body>
</html>
