<?php
/**
 * ==========================================
 * V4.5 专业详情页 - 京东级别视觉
 * ==========================================
 * 
 * 新增特性：
 * 1. Hero价格盒子（浅红背景+已省金额）
 * 2. 京东风格SKU选择器（右下角对勾SVG）
 * 3. 参数图标栏（芯片/屏幕/像素/电池）
 * 4. 紧迫感提示（库存紧张/在线人数）
 */

require_once '../config/config.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$spu_id = intval($_GET['spu'] ?? 0);

if ($spu_id <= 0) {
    header('Location: /core/quotes_v6.php');
    exit;
}

// 查询SPU
$stmt = $conn->prepare("SELECT * FROM products_spu_v3 WHERE id = ?");
$stmt->execute([$spu_id]);
$spu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$spu) {
    header('Location: /core/quotes_v6.php');
    exit;
}

// 查询所有SKU
$stmt = $conn->prepare("SELECT * FROM products_sku_v3 WHERE spu_id = ? ORDER BY price ASC");
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
    '黑色' => '#1a1a1a', '曜石黑' => '#0a0a0a', '雅丹黑' => '#1f1f1f', '羽砂黑' => '#2d2d2d', '午夜色' => '#1e293b', '深空灰' => '#4a4a4a',
    '白色' => '#fafafa', '洛可可白' => '#fff8f0', '羽砂白' => '#f8f8f8', '星光色' => '#f5e6d3',
    '蓝色' => '#3b82f6', '远峰蓝' => '#7dd3fc', '冰川蓝' => '#a5d8ff', '海蓝色' => '#0ea5e9',
    '绿色' => '#22c55e', '原野绿' => '#16a34a', '薄荷绿' => '#86efac',
    '紫色' => '#a855f7', '南糯紫' => '#7c3aed', '丁香紫' => '#c084fc', '烟紫色' => '#9333ea',
    '粉色' => '#ec4899', '樱花粉' => '#f9a8d4',
    '金色' => '#d4a574', '沙漠金' => '#c9a227', '玫瑰金' => '#e8b4b4',
    '银色' => '#c0c0c0', '星河银' => '#d1d5db',
    '灰色' => '#6b7280', '岩石灰' => '#52525b', '钛金灰' => '#71717a',
    '红色' => '#ef4444',
    '棕色' => '#92400e', '琥珀棕' => '#b5651d',
    '青色' => '#4a9b8c', '雅川青' => '#4a9b8c', '翡冷翠' => '#059669',
];

// 智能推荐算法
$current_price = $spu['min_price'];
$price_range_min = $current_price * 0.8;
$price_range_max = $current_price * 1.2;

$recommendations = $conn->prepare("
    SELECT * FROM products_spu_v3 
    WHERE id != ? 
    AND (
        (min_price BETWEEN ? AND ?)
        OR (brand = ? AND min_price > ?)
    )
    ORDER BY RAND()
    LIMIT 4
");
$recommendations->execute([$spu_id, $price_range_min, $price_range_max, $spu['brand'], $current_price]);
$recommended_products = $recommendations->fetchAll(PDO::FETCH_ASSOC);

// 模拟在线人数
$online_users = rand(128, 356);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($spu['model_name']); ?> - 汇森科技</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --brand-red: #e1251b; }
        
        /* 京东风格SKU选择器 */
        .sku-option {
            position: relative;
            padding: 10px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .sku-option:hover:not(.disabled) {
            border-color: var(--brand-red);
        }
        .sku-option.active {
            border-color: var(--brand-red);
            color: var(--brand-red);
            font-weight: 600;
        }
        
        /* 京东标志性的右下角对勾 */
        .sku-option.active::after {
            content: '';
            position: absolute;
            right: -2px;
            bottom: -2px;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 0 20px 20px;
            border-color: transparent transparent var(--brand-red) transparent;
        }
        .sku-option.active::before {
            content: '✓';
            position: absolute;
            right: 1px;
            bottom: -1px;
            color: white;
            font-size: 11px;
            font-weight: bold;
            z-index: 1;
        }
        
        .sku-option.disabled {
            opacity: 0.35;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        
        /* 颜色圆点 */
        .color-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 1.5px solid #d1d5db;
            flex-shrink: 0;
        }
        
        /* Hero价格盒子 */
        .hero-price-box {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe4e6 100%);
            border-left: 4px solid var(--brand-red);
            border-radius: 12px;
        }
        
        /* 参数图标 */
        .param-icon {
            width: 40px;
            height: 40px;
            background: #f3f4f6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
        }
        
        /* 推荐卡片 */
        .recommend-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .recommend-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }
        
        /* 紧迫感闪烁 */
        @keyframes pulse-red {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .urgency-badge {
            animation: pulse-red 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- 顶部导航 -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 h-14 flex items-center gap-4">
            <a href="/core/index_v4.php" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition" title="返回主页">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                主页
            </a>
            <span class="text-gray-300">|</span>
            <a href="/core/quotes_v6.php" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                返回列表
            </a>
            <span class="text-gray-300">|</span>
            <span class="text-gray-500 text-sm"><?php echo htmlspecialchars($spu['brand']); ?></span>
            <span class="text-gray-300">></span>
            <span class="text-gray-700 text-sm font-medium"><?php echo htmlspecialchars($spu['model_name']); ?></span>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid lg:grid-cols-2 gap-10 mb-10">
            <!-- 左侧：产品图片 -->
            <div>
                <div class="bg-white rounded-xl p-10 aspect-square flex items-center justify-center mb-4 shadow-sm">
                    <?php 
                    $image = $spu['image_url'] ?? '';
                    $has_image = !empty($image) && file_exists('../' . $image);
                    
                    // 解析gallery图片
                    $gallery = [];
                    if (!empty($spu['gallery_images'])) {
                        $gallery = json_decode($spu['gallery_images'], true) ?? [];
                    }
                    
                    // 构建所有图片数组（主图 + 副图）
                    $all_images = [];
                    if ($has_image) {
                        $all_images[] = $image;
                    }
                    foreach ($gallery as $g) {
                        if (!in_array($g, $all_images) && file_exists('../' . $g)) {
                            $all_images[] = $g;
                        }
                    }
                    ?>
                    <?php if ($has_image): ?>
                        <img src="../<?php echo htmlspecialchars($image); ?>" 
                             alt="<?php echo htmlspecialchars($spu['model_name']); ?>"
                             class="max-w-full max-h-full object-contain transition-opacity duration-300"
                             id="mainImage">
                    <?php else: ?>
                        <div class="text-center text-gray-300" id="mainImagePlaceholder">
                            <div class="text-8xl mb-4">📱</div>
                            <span class="text-gray-400"><?php echo htmlspecialchars($spu['brand']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- 缩略图轮播 -->
                <div class="flex gap-2 justify-center overflow-x-auto pb-2" id="thumbnailContainer">
                    <?php if (count($all_images) > 0): ?>
                        <?php foreach ($all_images as $idx => $thumb): ?>
                        <div class="w-16 h-16 bg-gray-50 rounded-lg border-2 flex-shrink-0 flex items-center justify-center cursor-pointer transition-all hover:border-red-400 <?php echo $idx === 0 ? 'border-red-500' : 'border-gray-200'; ?>"
                             data-image="../<?php echo htmlspecialchars($thumb); ?>"
                             onclick="switchImage(this)">
                            <img src="../<?php echo htmlspecialchars($thumb); ?>" 
                                 alt="图片 <?php echo $idx + 1; ?>"
                                 class="max-w-full max-h-full object-contain rounded"
                                 onerror="this.parentNode.innerHTML='<span class=\'text-gray-400 text-xs\'>图片</span>'">
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="w-16 h-16 bg-gray-100 rounded-lg border-2 border-red-500 flex items-center justify-center text-xs text-gray-400 cursor-pointer">
                            主图
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 右侧：产品信息 -->
            <div class="space-y-6">
                <!-- 标题 -->
                <div>
                    <div class="text-sm text-gray-400 mb-2"><?php echo htmlspecialchars($spu['brand']); ?></div>
                    <h1 class="text-3xl font-bold text-gray-900 leading-tight mb-4">
                        <?php echo htmlspecialchars($spu['model_name']); ?>
                    </h1>
                    
                    <!-- 参数图标栏 -->
                    <div class="flex items-center gap-4 text-xs">
                        <div class="flex items-center gap-2">
                            <div class="param-icon">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 7H7v6h6V7z"/>
                                    <path fill-rule="evenodd" d="M7 2a1 1 0 012 0v1h2V2a1 1 0 112 0v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v2h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1V9H2a1 1 0 010-2h1V5a2 2 0 012-2h2V2zM5 5h10v10H5V5z"/>
                                </svg>
                            </div>
                            <span class="text-gray-600">旗舰芯片</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="param-icon">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm11 1H6v8h8V6z"/>
                                </svg>
                            </div>
                            <span class="text-gray-600">超清屏幕</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="param-icon">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2h-1.586a1 1 0 01-.707-.293l-1.121-1.121A2 2 0 0011.172 3H8.828a2 2 0 00-1.414.586L6.293 4.707A1 1 0 015.586 5H4zm6 9a3 3 0 100-6 3 3 0 000 6z"/>
                                </svg>
                            </div>
                            <span class="text-gray-600">高清影像</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="param-icon">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z"/>
                                </svg>
                            </div>
                            <span class="text-gray-600">强劲续航</span>
                        </div>
                    </div>
                </div>

                <!-- Hero价格盒子 -->
                <div class="hero-price-box p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <div class="text-sm text-gray-500 mb-2">批发价</div>
                            <div class="flex items-baseline gap-4">
                                <span class="text-5xl font-bold text-red-600" id="currentPrice">
                                    ¥<?php echo number_format($spu['min_price'], 0); ?>
                                </span>
                                <?php if (count($skus) > 1): ?>
                                <span class="text-xl text-red-500 font-medium">起</span>
                                <?php endif; ?>
                                <span class="text-xl text-gray-400 line-through" id="officialPrice"></span>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <span class="px-4 py-1.5 bg-red-500 text-white rounded-full text-sm font-bold" id="savingsTag"></span>
                                <span class="px-3 py-1 bg-orange-100 text-orange-600 rounded-full text-xs font-medium">批发源头</span>
                            </div>
                        </div>
                        
                        <!-- 紧迫感提示 -->
                        <div class="text-right">
                            <div class="urgency-badge px-3 py-1.5 bg-red-100 text-red-600 rounded-lg text-xs font-medium mb-2">
                                🔥 <?php echo $online_users; ?>人在看
                            </div>
                            <div id="stockUrgency" class="px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded-lg text-xs font-medium">
                                ⚠️ 库存紧张
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SKU选择区 -->
                <div class="bg-white rounded-xl p-6 shadow-sm space-y-6">
                    <?php if (count($colors) > 1 || (count($colors) == 1 && $colors[0] != '标准')): ?>
                    <!-- 颜色选择 -->
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-gray-700 font-semibold">颜色</span>
                            <span class="text-red-600 font-medium" id="selectedColorLabel">请选择</span>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <?php foreach ($colors as $color): 
                                $hex = $color_hex[$color] ?? '#888888';
                            ?>
                            <button type="button"
                                    class="sku-option"
                                    data-color="<?php echo htmlspecialchars($color); ?>"
                                    onclick="selectColor(this)">
                                <span class="color-dot" style="background-color: <?php echo $hex; ?>; <?php echo in_array($color, ['白色', '星光色', '洛可可白', '羽砂白', '银色']) ? 'border-color: #9ca3af;' : ''; ?>"></span>
                                <span><?php echo htmlspecialchars($color); ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (count($storages) > 1 || (count($storages) == 1 && $storages[0] != '标准')): ?>
                    <!-- 版本选择 -->
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-gray-700 font-semibold">版本</span>
                            <span class="text-red-600 font-medium" id="selectedStorageLabel">请选择</span>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <?php foreach ($storages as $storage): ?>
                            <button type="button"
                                    class="sku-option"
                                    data-storage="<?php echo htmlspecialchars($storage); ?>"
                                    onclick="selectStorage(this)">
                                <?php echo htmlspecialchars($storage); ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 操作按钮 -->
                <div class="grid grid-cols-2 gap-4">
                    <button onclick="contact()" 
                            class="py-4 rounded-xl text-lg font-bold text-white transition-all hover:opacity-90 hover:shadow-lg"
                            style="background: var(--brand-red);">
                        立即咨询
                    </button>
                    <button onclick="addToCart()" 
                            class="py-4 rounded-xl text-lg font-bold border-2 transition-all hover:bg-red-50 hover:shadow-lg"
                            style="border-color: var(--brand-red); color: var(--brand-red);">
                        加入询价单
                    </button>
                </div>

                <!-- 服务保障 -->
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-5">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-green-600 text-lg">✓</span>
                            <span class="text-gray-700 font-medium">正品保障</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-600 text-lg">✓</span>
                            <span class="text-gray-700 font-medium">全国联保</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-600 text-lg">✓</span>
                            <span class="text-gray-700 font-medium">急速发货</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-600 text-lg">✓</span>
                            <span class="text-gray-700 font-medium">7天退换</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 猜你喜欢 -->
        <?php if (count($recommended_products) > 0): ?>
        <div class="bg-white rounded-2xl p-8 shadow-sm mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                <span style="color: var(--brand-red);">💡</span>
                猜你喜欢
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach ($recommended_products as $rec): ?>
                <div class="recommend-card bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm"
                     onclick="location.href='/core/detail_v4.php?spu=<?php echo $rec['id']; ?>'">
                    <div class="aspect-square bg-gray-50 flex items-center justify-center p-6">
                        <?php if (!empty($rec['image_url']) && file_exists('../' . $rec['image_url'])): ?>
                            <img src="../<?php echo htmlspecialchars($rec['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($rec['model_name']); ?>"
                                 class="max-w-full max-h-full object-contain">
                        <?php else: ?>
                            <div class="text-6xl text-gray-300">📱</div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <div class="text-xs text-gray-400 mb-1"><?php echo htmlspecialchars($rec['brand']); ?></div>
                        <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10">
                            <?php echo htmlspecialchars($rec['model_name']); ?>
                        </h3>
                        <div class="text-xl font-bold text-red-600 mb-3">
                            ¥<?php echo number_format($rec['min_price'], 0); ?>
                            <?php if ($rec['sku_count'] > 1): ?><span class="text-sm">起</span><?php endif; ?>
                        </div>
                        <button class="w-full py-2 border border-red-500 text-red-500 rounded-lg text-sm font-medium hover:bg-red-50 transition">
                            去看看
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 全部配置 -->
        <div class="bg-white rounded-2xl p-8 shadow-sm">
            <h2 class="text-2xl font-bold mb-6">📋 全部配置报价</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold">配置</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">颜色</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">版本</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold">批发价</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold">官网价</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold">优惠</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($skus as $sku): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($sku['full_name']); ?></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($sku['color']) && $sku['color'] !== '标准'): ?>
                                <span class="inline-flex items-center gap-2 text-sm">
                                    <span class="w-3 h-3 rounded-full border" 
                                          style="background: <?php echo $color_hex[$sku['color']] ?? '#888'; ?>"></span>
                                    <?php echo htmlspecialchars($sku['color']); ?>
                                </span>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($sku['storage'] ?? '-'); ?></td>
                            <td class="px-4 py-3 text-right font-bold text-red-600">
                                ¥<?php echo number_format($sku['price'], 0); ?>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-400 text-sm">
                                <?php echo $sku['official_price'] > 0 ? '¥' . number_format($sku['official_price'], 0) : '-'; ?>
                            </td>
                            <td class="px-4 py-3 text-right text-green-600 text-sm font-medium">
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
    </main>

    <footer class="bg-gray-900 text-white mt-20 py-10">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-400 text-sm">
            © 2026 汇森科技 版权所有
        </div>
    </footer>

    <script>
    const skuMatrix = <?php echo json_encode($sku_matrix, JSON_UNESCAPED_UNICODE); ?>;
    const allColors = <?php echo json_encode($colors, JSON_UNESCAPED_UNICODE); ?>;
    const allStorages = <?php echo json_encode($storages, JSON_UNESCAPED_UNICODE); ?>;
    
    let selectedColor = null;
    let selectedStorage = null;
    
    function selectColor(btn) {
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
                    document.getElementById('selectedStorageLabel').textContent = '请选择';
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
        const savingsEl = document.getElementById('savingsTag');
        const urgencyEl = document.getElementById('stockUrgency');
        
        if (selectedColor && selectedStorage) {
            const key = selectedColor + '|' + selectedStorage;
            const sku = skuMatrix[key];
            
            if (sku) {
                priceEl.textContent = '¥' + sku.price.toLocaleString();
                
                if (sku.official_price > 0) {
                    officialEl.textContent = '¥' + sku.official_price.toLocaleString();
                    const save = sku.official_price - sku.price;
                    savingsEl.textContent = save > 0 ? '已省 ¥' + save.toLocaleString() : '';
                    savingsEl.style.display = save > 0 ? 'inline-block' : 'none';
                } else {
                    officialEl.textContent = '';
                    savingsEl.style.display = 'none';
                }
                
                const stockText = {
                    'in_stock': '✓ 现货充足',
                    'low': '⚠️ 库存紧张',
                    'out': '✗ 暂时缺货'
                };
                const stockClass = {
                    'in_stock': 'bg-green-100 text-green-600',
                    'low': 'bg-yellow-100 text-yellow-700',
                    'out': 'bg-red-100 text-red-600'
                };
                urgencyEl.textContent = stockText[sku.stock] || stockText['in_stock'];
                urgencyEl.className = 'px-3 py-1.5 rounded-lg text-xs font-medium ' + (stockClass[sku.stock] || stockClass['in_stock']);
            }
        }
    }
    
    function contact() {
        let msg = '咨询产品: <?php echo addslashes($spu['model_name']); ?>';
        if (selectedColor) msg += '\n颜色: ' + selectedColor;
        if (selectedStorage) msg += '\n版本: ' + selectedStorage;
        msg += '\n\n客服微信: huisen_tech\n电话: 400-XXX-XXXX';
        alert(msg);
    }
    
    async function addToCart() {
        // 自动选择：如果只有一个选项，自动选择
        if (!selectedColor && allColors.length > 0) {
            if (allColors.length === 1) {
                // 只有一个颜色，自动选择
                selectedColor = allColors[0];
                const colorBtn = document.querySelector(`[data-color="${selectedColor}"]`);
                if (colorBtn) {
                    colorBtn.classList.add('active');
                    document.getElementById('selectedColorLabel').textContent = selectedColor;
                }
            } else if (allColors.length > 1 && allColors[0] !== '标准') {
                alert('请先选择颜色');
                return;
            }
        }
        
        if (!selectedStorage && allStorages.length > 0) {
            if (allStorages.length === 1) {
                // 只有一个版本，自动选择
                selectedStorage = allStorages[0];
                const storageBtn = document.querySelector(`[data-storage="${selectedStorage}"]`);
                if (storageBtn) {
                    storageBtn.classList.add('active');
                    document.getElementById('selectedStorageLabel').textContent = selectedStorage;
                }
            } else if (allStorages.length > 1 && allStorages[0] !== '标准') {
                alert('请先选择版本');
                return;
            }
        }

        // 获取当前选中的 SKU - 使用实际值，如果没有则使用默认值
        const color = selectedColor || (allColors.length > 0 ? allColors[0] : '标准');
        const storage = selectedStorage || (allStorages.length > 0 ? allStorages[0] : '标准');
        
        // 构建 key，尝试多种可能的组合
        let key = color + '|' + storage;
        let sku = skuMatrix[key];
        
        // 如果找不到，尝试使用 '标准' 作为默认值
        if (!sku) {
            key = (color === '标准' ? '标准' : color) + '|' + (storage === '标准' ? '标准' : storage);
            sku = skuMatrix[key];
        }
        
        // 如果还是找不到，尝试所有可能的组合
        if (!sku) {
            for (const c of allColors) {
                for (const s of allStorages) {
                    const testKey = c + '|' + s;
                    if (skuMatrix[testKey]) {
                        key = testKey;
                        sku = skuMatrix[testKey];
                        break;
                    }
                }
                if (sku) break;
            }
        }

        if (!sku || !sku.id) {
            console.error('SKU查找失败:', { color, storage, key, skuMatrix });
            alert('无法获取商品信息，请刷新页面重试\n\n如果问题持续，请联系客服');
            return;
        }

        try {
            const response = await fetch('/api/cart.php?action=add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    sku_id: sku.id,
                    qty: 1
                })
            });

            const result = await response.json();

            if (result.success) {
                alert('✓ 已加入询价单！\n\n' + 
                      '<?php echo addslashes($spu['model_name']); ?>' + 
                      (color ? ' - ' + color : '') + 
                      (storage ? ' - ' + storage : ''));
            } else {
                alert('添加失败：' + (result.error || '未知错误'));
            }
        } catch (error) {
            console.error('添加询价单失败:', error);
            alert('网络错误，请稍后重试');
        }
    }
    
    /**
     * 切换主图（点击缩略图时调用）
     */
    function switchImage(thumbDiv) {
        const newSrc = thumbDiv.dataset.image;
        const mainImg = document.getElementById('mainImage');
        const placeholder = document.getElementById('mainImagePlaceholder');
        
        // 更新主图
        if (mainImg) {
            mainImg.style.opacity = '0.5';
            setTimeout(() => {
                mainImg.src = newSrc;
                mainImg.style.opacity = '1';
            }, 150);
        } else if (placeholder) {
            // 如果之前是占位符，替换为真实图片
            placeholder.innerHTML = `<img src="${newSrc}" alt="产品图片" class="max-w-full max-h-full object-contain" id="mainImage">`;
        }
        
        // 更新缩略图边框
        document.querySelectorAll('#thumbnailContainer > div').forEach(div => {
            div.classList.remove('border-red-500');
            div.classList.add('border-gray-200');
        });
        thumbDiv.classList.remove('border-gray-200');
        thumbDiv.classList.add('border-red-500');
    }
    </script>
</body>
</html>
