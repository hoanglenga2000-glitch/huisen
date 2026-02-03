<?php
/**
 * ==========================================
 * V3 专业列表页
 * ==========================================
 * 
 * 特性：
 * 1. 只展示清洗后的干净型号名称
 * 2. 价格显示为 "¥X,XXX 起"
 * 3. 智能分类占位图（手机/平板/手表/配件）
 * 4. 多维度筛选
 */

require_once '../config/config.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// 检查V3表是否存在
$table_exists = $conn->query("SHOW TABLES LIKE 'products_spu_v3'")->rowCount() > 0;

if (!$table_exists) {
    header('Location: ../migrate_v3.php');
    exit;
}

// 获取筛选参数
$selected_brand = $_GET['brand'] ?? '';
$selected_category = $_GET['category'] ?? '';
$price_range = $_GET['price'] ?? '';
$search = trim($_GET['search'] ?? '');

// 构建查询
$sql = "SELECT s.*, 
        (SELECT COUNT(*) FROM products_sku_v3 WHERE spu_id = s.id) as sku_count
        FROM products_spu_v3 s 
        WHERE s.min_price > 0";
$params = [];

// 品牌筛选
if (!empty($selected_brand)) {
    $sql .= " AND s.brand = ?";
    $params[] = $selected_brand;
}

// 分类筛选
if (!empty($selected_category)) {
    $sql .= " AND s.category = ?";
    $params[] = $selected_category;
}

// 价格筛选
if (!empty($price_range)) {
    switch ($price_range) {
        case '0-2000':
            $sql .= " AND s.min_price <= 2000";
            break;
        case '2000-4000':
            $sql .= " AND s.min_price >= 2000 AND s.min_price <= 4000";
            break;
        case '4000-6000':
            $sql .= " AND s.min_price >= 4000 AND s.min_price <= 6000";
            break;
        case '6000+':
            $sql .= " AND s.min_price >= 6000";
            break;
    }
}

// 搜索
if (!empty($search)) {
    $sql .= " AND (s.model_name LIKE ? OR s.brand LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql .= " ORDER BY s.brand, s.min_price DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取品牌列表
$brands = $conn->query("
    SELECT brand, COUNT(*) as cnt 
    FROM products_spu_v3 
    GROUP BY brand 
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

// 品牌颜色配置
$brand_colors = [
    '苹果' => ['bg' => '#000000', 'text' => '#ffffff'],
    '华为' => ['bg' => '#c8102e', 'text' => '#ffffff'],
    '小米' => ['bg' => '#ff6700', 'text' => '#ffffff'],
    '荣耀' => ['bg' => '#e60012', 'text' => '#ffffff'],
    'OPPO' => ['bg' => '#00a862', 'text' => '#ffffff'],
    'vivo' => ['bg' => '#0084ff', 'text' => '#ffffff'],
    '三星' => ['bg' => '#0a78f4', 'text' => '#ffffff'],
];

// 按品牌分组（用于品牌楼层展示）
$brand_floors = [];
$main_brands = ['苹果', '华为', '小米', '荣耀', 'OPPO', 'vivo', '三星'];

if (empty($selected_brand) && empty($search) && empty($selected_category)) {
    foreach ($main_brands as $b) {
        $floor_products = array_filter($products, fn($p) => $p['brand'] === $b && $p['category'] === 'phone');
        if (count($floor_products) > 0) {
            $brand_floors[] = [
                'name' => $b,
                'products' => array_slice(array_values($floor_products), 0, 10),
                'total' => count($floor_products),
                'color' => $brand_colors[$b] ?? ['bg' => '#666', 'text' => '#fff']
            ];
        }
    }
    
    // 配件/手表专区
    $accessory_products = array_filter($products, fn($p) => in_array($p['category'], ['watch', 'tablet', 'accessory']));
    if (count($accessory_products) > 0) {
        $brand_floors[] = [
            'name' => '智能穿戴与配件',
            'products' => array_slice(array_values($accessory_products), 0, 10),
            'total' => count($accessory_products),
            'color' => ['bg' => '#8b5cf6', 'text' => '#fff']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>汇森科技 - 专业手机批发报价</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand-red: #e53935; }
        body { font-family: 'Inter', -apple-system, sans-serif; }
        
        /* 产品卡片 */
        .product-card {
            transition: all 0.25s ease;
            cursor: pointer;
            border: 1px solid #f3f4f6;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            border-color: #e5e7eb;
        }
        
        /* 价格样式 */
        .price-tag {
            color: #e53935;
            font-weight: 700;
            font-size: 1.25rem;
        }
        .price-suffix {
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        /* 筛选按钮 */
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.2s;
            background: white;
        }
        .filter-btn:hover { border-color: #e53935; color: #e53935; }
        .filter-btn.active { 
            background: #e53935; 
            color: white; 
            border-color: #e53935; 
        }
        
        /* 分类图标占位 */
        .category-placeholder {
            width: 80px;
            height: 80px;
            opacity: 0.3;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- 顶部导航 -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <a href="/core/index_v4.php" class="flex items-center gap-2 hover:opacity-80 transition">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="text-sm text-gray-600 hover:text-gray-900">首页</span>
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="/core/quotes_v3.php" class="flex items-center gap-2">
                        <span class="text-2xl font-bold" style="color: var(--brand-red);">汇森科技</span>
                    </a>
                </div>
                
                <!-- 搜索框 -->
                <form action="" method="get" class="flex-1 max-w-lg mx-8">
                    <div class="relative">
                        <input type="text" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="搜索手机型号..."
                               class="w-full px-4 py-2.5 bg-gray-100 border-0 rounded-full focus:outline-none focus:ring-2 focus:ring-red-500 focus:bg-white"
                               id="searchInput">
                        <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 px-5 py-1.5 bg-red-500 text-white rounded-full text-sm font-medium hover:bg-red-600">
                            搜索
                        </button>
                    </div>
                </form>
                
                <!-- 导航 -->
                <nav class="flex items-center gap-6 text-sm">
                    <a href="/core/index_v4.php" class="text-gray-600 hover:text-gray-900 transition flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        主页
                    </a>
                    <a href="/core/quotes_v3.php" class="font-medium" style="color: var(--brand-red);">手机报价</a>
                    <a href="?category=watch" class="text-gray-600 hover:text-gray-900">智能手表</a>
                    <a href="?category=accessory" class="text-gray-600 hover:text-gray-900">配件</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6">
        <!-- 筛选栏 -->
        <div class="bg-white rounded-2xl p-5 mb-6 shadow-sm">
            <!-- 品牌筛选 -->
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <span class="text-gray-500 text-sm font-medium w-16">品牌</span>
                <a href="?" class="filter-btn <?php echo empty($selected_brand) ? 'active' : ''; ?>">全部</a>
                <?php foreach (array_slice($brands, 0, 10) as $b): ?>
                <a href="?brand=<?php echo urlencode($b['brand']); ?>" 
                   class="filter-btn <?php echo $selected_brand === $b['brand'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($b['brand']); ?>
                    <span class="text-xs opacity-70">(<?php echo $b['cnt']; ?>)</span>
                </a>
                <?php endforeach; ?>
            </div>
            
            <!-- 价格筛选 -->
            <div class="flex flex-wrap items-center gap-2 mb-4 pt-4 border-t">
                <span class="text-gray-500 text-sm font-medium w-16">价格</span>
                <a href="?" class="filter-btn <?php echo empty($price_range) ? 'active' : ''; ?>">全部</a>
                <a href="?price=0-2000<?php echo $selected_brand ? "&brand={$selected_brand}" : ''; ?>" 
                   class="filter-btn <?php echo $price_range === '0-2000' ? 'active' : ''; ?>">2000以下</a>
                <a href="?price=2000-4000<?php echo $selected_brand ? "&brand={$selected_brand}" : ''; ?>" 
                   class="filter-btn <?php echo $price_range === '2000-4000' ? 'active' : ''; ?>">2000-4000</a>
                <a href="?price=4000-6000<?php echo $selected_brand ? "&brand={$selected_brand}" : ''; ?>" 
                   class="filter-btn <?php echo $price_range === '4000-6000' ? 'active' : ''; ?>">4000-6000</a>
                <a href="?price=6000+<?php echo $selected_brand ? "&brand={$selected_brand}" : ''; ?>" 
                   class="filter-btn <?php echo $price_range === '6000+' ? 'active' : ''; ?>">6000以上</a>
            </div>
            
            <!-- 分类筛选 -->
            <div class="flex flex-wrap items-center gap-2 pt-4 border-t">
                <span class="text-gray-500 text-sm font-medium w-16">分类</span>
                <a href="?" class="filter-btn <?php echo empty($selected_category) ? 'active' : ''; ?>">全部</a>
                <a href="?category=phone" class="filter-btn <?php echo $selected_category === 'phone' ? 'active' : ''; ?>">📱 手机</a>
                <a href="?category=tablet" class="filter-btn <?php echo $selected_category === 'tablet' ? 'active' : ''; ?>">📱 平板</a>
                <a href="?category=watch" class="filter-btn <?php echo $selected_category === 'watch' ? 'active' : ''; ?>">⌚ 手表</a>
                <a href="?category=accessory" class="filter-btn <?php echo $selected_category === 'accessory' ? 'active' : ''; ?>">🎧 配件</a>
            </div>
        </div>

        <?php if (!empty($selected_brand) || !empty($search) || !empty($selected_category) || !empty($price_range)): ?>
        <!-- 筛选结果页 -->
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl font-bold">
                    <?php 
                    if (!empty($search)) echo "搜索: " . htmlspecialchars($search);
                    elseif (!empty($selected_brand)) echo $selected_brand . " 产品";
                    elseif (!empty($selected_category)) echo match($selected_category) {
                        'phone' => '📱 手机',
                        'tablet' => '📱 平板电脑',
                        'watch' => '⌚ 智能手表',
                        'accessory' => '🎧 配件',
                        default => $selected_category
                    };
                    else echo "筛选结果";
                    ?>
                    <span class="text-gray-400 font-normal text-base ml-2">(<?php echo count($products); ?>款)</span>
                </h1>
                <div class="flex items-center gap-3">
                    <a href="/core/index_v4.php" class="text-sm text-gray-500 hover:text-red-500 transition flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        返回主页
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="/core/quotes_v3.php" class="text-sm text-gray-500 hover:text-red-500 transition">← 返回全部</a>
                </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
                <?php foreach ($products as $p): ?>
                <article class="product-card bg-white rounded-xl overflow-hidden shadow-sm" 
                         onclick="location.href='/core/detail_v4.php?spu=<?php echo $p['id']; ?>'">
                    <div class="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-6">
                        <?php 
                        $has_image = !empty($p['image_url']) && file_exists('../' . $p['image_url']);
                        ?>
                        <?php if ($has_image): ?>
                            <img src="../<?php echo htmlspecialchars($p['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($p['model_name']); ?>"
                                 class="max-w-full max-h-full object-contain"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="text-gray-300 text-6xl">📱</div>
                        <?php endif; ?>
                        
                        <?php if ($p['sku_count'] > 1): ?>
                        <div class="absolute top-3 right-3">
                            <span class="px-2 py-1 bg-blue-500 text-white text-xs rounded-md">
                                <?php echo $p['sku_count']; ?>款可选
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-4">
                        <div class="text-xs text-gray-400 mb-1"><?php echo htmlspecialchars($p['brand']); ?></div>
                        <h3 class="font-bold text-sm mb-3 line-clamp-2 h-10 text-gray-800">
                            <?php echo htmlspecialchars($p['model_name']); ?>
                        </h3>

                        <div class="flex items-baseline gap-2 mb-2">
                            <span class="text-xl font-bold text-red-600">¥<?php echo number_format($p['min_price'], 0); ?></span>
                            <?php if ($p['sku_count'] > 1 || $p['min_price'] != $p['max_price']): ?>
                            <span class="text-sm text-red-500 font-medium">起</span>
                            <?php endif; ?>
                            <span class="ml-auto px-2 py-0.5 bg-orange-100 text-orange-600 text-xs rounded-full font-medium">补贴价</span>
                        </div>
                        
                        <?php if ($p['min_price'] != $p['max_price']): ?>
                        <div class="text-xs text-gray-400 mb-3">
                            ¥<?php echo number_format($p['min_price'], 0); ?> - ¥<?php echo number_format($p['max_price'], 0); ?>
                        </div>
                        <?php endif; ?>

                        <button class="w-full py-2.5 rounded-lg text-sm font-medium text-white transition hover:opacity-90"
                                style="background: var(--brand-red);"
                                onclick="event.stopPropagation();">
                            查看详情
                        </button>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php else: ?>
        <!-- 品牌楼层展示 -->
        <?php foreach ($brand_floors as $floor): ?>
        <section class="mb-10">
            <!-- 楼层头部 -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-6 rounded-full" style="background: <?php echo $floor['color']['bg']; ?>"></div>
                    <h2 class="text-xl font-bold"><?php echo htmlspecialchars($floor['name']); ?></h2>
                    <span class="text-gray-400 text-sm">(<?php echo $floor['total']; ?>款)</span>
                </div>
                <a href="?brand=<?php echo urlencode($floor['name']); ?>" 
                   class="text-sm hover:underline" style="color: <?php echo $floor['color']['bg']; ?>">
                    查看全部 →
                </a>
            </div>
            
            <!-- 产品网格 -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
                <?php foreach ($floor['products'] as $p): ?>
                <article class="product-card bg-white rounded-xl overflow-hidden shadow-sm" 
                         onclick="location.href='/core/detail_v4.php?spu=<?php echo $p['id']; ?>'">
                    <!-- 图片区域 -->
                    <div class="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-6">
                        <?php 
                        $has_image = !empty($p['image_url']) && file_exists('../' . $p['image_url']);
                        ?>
                        <?php if ($has_image): ?>
                            <img src="../<?php echo htmlspecialchars($p['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($p['model_name']); ?>"
                                 class="max-w-full max-h-full object-contain"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="text-gray-300 text-6xl">📱</div>
                        <?php endif; ?>
                        
                        <?php if ($p['sku_count'] > 1): ?>
                        <div class="absolute top-3 right-3">
                            <span class="px-2 py-1 bg-blue-500 text-white text-xs rounded-md">
                                <?php echo $p['sku_count']; ?>款可选
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- 信息区域 -->
                    <div class="p-4">
                        <div class="text-xs text-gray-400 mb-1"><?php echo htmlspecialchars($p['brand']); ?></div>
                        <h3 class="font-bold text-sm mb-3 line-clamp-2 h-10 text-gray-800">
                            <?php echo htmlspecialchars($p['model_name']); ?>
                        </h3>

                        <div class="flex items-baseline gap-2 mb-2">
                            <span class="text-xl font-bold text-red-600">¥<?php echo number_format($p['min_price'], 0); ?></span>
                            <?php if ($p['sku_count'] > 1 || $p['min_price'] != $p['max_price']): ?>
                            <span class="text-sm text-red-500 font-medium">起</span>
                            <?php endif; ?>
                            <span class="ml-auto px-2 py-0.5 bg-orange-100 text-orange-600 text-xs rounded-full font-medium">补贴价</span>
                        </div>
                        
                        <?php if ($p['min_price'] != $p['max_price']): ?>
                        <div class="text-xs text-gray-400 mb-3">
                            ¥<?php echo number_format($p['min_price'], 0); ?> - ¥<?php echo number_format($p['max_price'], 0); ?>
                        </div>
                        <?php endif; ?>

                        <button class="w-full py-2.5 rounded-lg text-sm font-medium text-white transition hover:opacity-90"
                                style="background: var(--brand-red);"
                                onclick="event.stopPropagation();">
                            查看详情
                        </button>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- 页脚 -->
    <footer class="bg-gray-900 text-white mt-20">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h3 class="text-lg font-bold mb-4" style="color: var(--brand-red);">汇森科技</h3>
                    <p class="text-gray-400 text-sm">专业手机批发商</p>
                    <div class="flex gap-2 mt-4">
                        <span class="px-3 py-1 bg-green-600/20 text-green-400 text-xs rounded-full">✓ 正品保障</span>
                        <span class="px-3 py-1 bg-blue-600/20 text-blue-400 text-xs rounded-full">✓ 急速发货</span>
                    </div>
                </div>
                <div>
                    <h4 class="font-medium mb-4">产品分类</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="?category=phone" class="hover:text-white">手机</a></li>
                        <li><a href="?category=tablet" class="hover:text-white">平板电脑</a></li>
                        <li><a href="?category=watch" class="hover:text-white">智能手表</a></li>
                        <li><a href="?category=accessory" class="hover:text-white">配件</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium mb-4">服务保障</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li>✓ 原装正品</li>
                        <li>✓ 全国联保</li>
                        <li>✓ 7天无理由</li>
                        <li>✓ 批发价格</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium mb-4">联系我们</h4>
                    <p class="text-sm text-gray-400">客服热线: 400-XXX-XXXX</p>
                    <p class="text-sm text-gray-400 mt-2">微信: huisen_tech</p>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-sm text-gray-500">
                © 2026 汇森科技 版权所有
            </div>
        </div>
    </footer>
</body>
</html>
