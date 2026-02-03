<?php
/**
 * ==========================================
 * V5 专业电商首页 - 九机网风格
 * ==========================================
 */

// 设置路径变量，用于修正 header 中的 CSS 路径
$base_path = '../';

require_once '../config/config.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// 检查V3/V4表
$use_v4 = $conn->query("SHOW TABLES LIKE 'products_spu_v4'")->rowCount() > 0;
$spu_table = $use_v4 ? 'products_spu_v4' : 'products_spu_v3';

$table_exists = $conn->query("SHOW TABLES LIKE '$spu_table'")->rowCount() > 0;

if (!$table_exists) {
    header('Location: ../migrate_v3.php');
    exit;
}

// 获取所有产品（带封面图）
$stmt = $conn->query("
    SELECT p.*, pi.image_path as cover_image
    FROM $spu_table p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.sort_order = 0
    WHERE p.min_price > 0
    ORDER BY p.brand, p.min_price DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 为每个产品设置最佳图片
foreach ($products as &$p) {
    // 优先使用product_images表的封面图
    if (!empty($p['cover_image']) && file_exists('../' . $p['cover_image'])) {
        $p['display_image'] = $p['cover_image'];
    } elseif (!empty($p['image_url']) && file_exists('../' . $p['image_url'])) {
        $p['display_image'] = $p['image_url'];
    } else {
        $p['display_image'] = '';
    }
}
unset($p);

// 热门手机（取价格最高的8款）
$hot_phones = array_slice(array_filter($products, fn($p) => $p['category'] === 'phone'), 0, 8);

// 品牌配色
$brand_colors = [
    '苹果' => ['bg' => '#000000', 'text' => '#fff'],
    '华为' => ['bg' => '#c8102e', 'text' => '#fff'],
    '小米' => ['bg' => '#ff6700', 'text' => '#fff'],
    '荣耀' => ['bg' => '#e60012', 'text' => '#fff'],
    'OPPO' => ['bg' => '#00a862', 'text' => '#fff'],
    'vivo' => ['bg' => '#0084ff', 'text' => '#fff'],
    '三星' => ['bg' => '#0a78f4', 'text' => '#fff'],
];

// 构建品牌楼层
$brand_floors = [];
$main_brands = ['苹果', '华为', '小米', 'OPPO', 'vivo', '荣耀', '三星'];

foreach ($main_brands as $b) {
    $floor_products = array_filter($products, fn($p) => $p['brand'] === $b && $p['category'] === 'phone');
    if (count($floor_products) > 0) {
        $brand_floors[] = [
            'name' => $b,
            'products' => array_slice(array_values($floor_products), 0, 8),
            'total' => count($floor_products),
            'color' => $brand_colors[$b] ?? ['bg' => '#666', 'text' => '#fff']
        ];
    }
}

// 配件专区
$accessory_products = array_filter($products, fn($p) => in_array($p['category'], ['watch', 'tablet', 'accessory']));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>汇森科技 - 专业手机批发报价平台</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        :root { --brand-red: #e1251b; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }

        /* 左侧分类导航 */
        .category-nav {
            background: linear-gradient(180deg, #2d2d2d 0%, #1a1a1a 100%);
        }
        .category-item {
            padding: 12px 16px;
            color: rgba(255,255,255,0.85);
            cursor: pointer;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .category-item:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: var(--brand-red);
            color: white;
        }
        .category-item .icon {
            width: 20px;
            text-align: center;
        }

        /* Banner轮播 */
        .banner-container {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
        }
        .banner-slide {
            display: none;
            animation: fadeIn 0.5s;
        }
        .banner-slide.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .banner-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
        }
        .banner-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.2s;
        }
        .banner-dot.active {
            background: white;
            width: 24px;
            border-radius: 4px;
        }

        /* 产品卡片 */
        .product-card {
            transition: all 0.3s ease;
            cursor: pointer;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }

        /* 品牌楼层 */
        .floor-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: white;
            border-radius: 8px 8px 0 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .floor-content {
            background: white;
            border-radius: 0 0 8px 8px;
            padding: 16px;
        }

        /* 品牌Logo栏 */
        .brand-logo-bar {
            display: flex;
            justify-content: center;
            gap: 40px;
            padding: 24px;
            background: white;
            border-radius: 8px;
        }
        .brand-logo-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .brand-logo-item:hover {
            transform: scale(1.1);
        }
        .brand-logo-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: white;
        }

        /* 热门横滚 */
        .hot-scroll {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            padding-bottom: 8px;
            scrollbar-width: thin;
        }
        .hot-scroll::-webkit-scrollbar {
            height: 4px;
        }
        .hot-scroll::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 2px;
        }
        .hot-item {
            flex-shrink: 0;
            width: 140px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .hot-item:hover {
            transform: translateY(-4px);
        }

        /* 右侧服务区 */
        .service-card {
            background: linear-gradient(135deg, #fff5f5 0%, #fff 100%);
            border-radius: 8px;
            padding: 16px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>

    <main class="max-w-[1280px] mx-auto px-4 mt-4 mb-8">
        <?php
        // 用户信息 (从 session 获取)
        $user = [
            'username' => $_SESSION['username'] ?? '游客',
            'level' => $_SESSION['user_level'] ?? 'normal',
            'balance' => $_SESSION['balance'] ?? 0,
            'is_logged_in' => isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true
        ];

        // 会员等级配置
        $level_config = [
            'normal' => ['name' => '普通会员', 'icon' => '👤', 'color' => 'text-gray-500'],
            'gold' => ['name' => '金牌会员', 'icon' => '👑', 'color' => 'text-amber-500'],
            'partner' => ['name' => '钻石合作伙伴', 'icon' => '💎', 'color' => 'text-blue-500'],
        ];
        $current_level = $level_config[$user['level']] ?? $level_config['normal'];

        // 平台公告
        $announcements = [
            ['title' => 'iPhone 16 系列现货充足，批发价直降', 'time' => '今天', 'hot' => true],
            ['title' => '华为 Mate 70 Pro 新品上架', 'time' => '昨天', 'hot' => true],
            ['title' => '春节期间发货时效说明', 'time' => '01-28', 'hot' => false],
        ];

        // 分类配置
        $categories = [
            ['name' => '手机', 'icon' => '📱', 'link' => 'quotes_v6.php?category=phone', 'badge' => '热销'],
            ['name' => '平板电脑', 'icon' => '📱', 'link' => 'quotes_v6.php?category=tablet', 'badge' => ''],
            ['name' => '智能手表', 'icon' => '⌚', 'link' => 'quotes_v6.php?category=watch', 'badge' => ''],
            ['name' => '耳机配件', 'icon' => '🎧', 'link' => 'quotes_v6.php?category=accessory', 'badge' => ''],
            ['name' => '充电设备', 'icon' => '🔋', 'link' => 'quotes_v6.php', 'badge' => ''],
            ['name' => '电脑办公', 'icon' => '💻', 'link' => 'quotes_v6.php', 'badge' => ''],
            ['name' => '批发合作', 'icon' => '🤝', 'link' => 'cooperation.php', 'badge' => '推荐'],
            ['name' => '售后服务', 'icon' => '🔧', 'link' => 'service.php', 'badge' => ''],
        ];
        ?>

        <!-- ==========================================
             首屏三栏布局 (LCR Layout)
             比例: 2:7:3 (左:中:右)
             ========================================== -->
        <div class="grid grid-cols-12 gap-4">

            <!-- ========== 左侧：垂直分类菜单 (手机隐藏) ========== -->
            <div class="hidden md:block md:col-span-2">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden h-full">
                    <?php foreach ($categories as $cat): ?>
                    <a href="<?php echo $cat['link']; ?>"
                       class="flex items-center gap-3 px-4 py-3 text-gray-700
                              border-l-3 border-transparent
                              hover:bg-gray-50 hover:text-primary-500 hover:border-primary-500
                              transition-all group">
                        <span class="text-lg group-hover:scale-110 transition-transform">
                            <?php echo $cat['icon']; ?>
                        </span>
                        <span class="font-medium text-sm"><?php echo $cat['name']; ?></span>
                        <?php if (!empty($cat['badge'])): ?>
                        <span class="ml-auto text-xs px-1.5 py-0.5 rounded
                                     <?php echo $cat['badge'] === '热销' ? 'bg-primary-100 text-primary-500' : 'bg-amber-100 text-amber-600'; ?>">
                            <?php echo $cat['badge']; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ========== 中间：主轮播图 (手机全宽) ========== -->
            <div class="col-span-12 md:col-span-7">
                <?php
                // 智能扫描优质图片文件夹
                $banner_images = [];
                $quality_dir = dirname(__DIR__) . '/images/优质图片';
                if (is_dir($quality_dir)) {
                    $files = scandir($quality_dir);
                    foreach ($files as $file) {
                        if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) {
                            $banner_images[] = 'images/优质图片/' . $file;
                        }
                    }
                }
                // 如果没有优质图片，使用banners文件夹
                if (empty($banner_images)) {
                    $banners_dir = dirname(__DIR__) . '/images/banners';
                    if (is_dir($banners_dir)) {
                        $files = scandir($banners_dir);
                        foreach ($files as $file) {
                            if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) {
                                $banner_images[] = 'images/banners/' . $file;
                            }
                        }
                    }
                }
                // 限制最多5张轮播图
                $banner_images = array_slice($banner_images, 0, 5);
                ?>
                <div class="relative rounded-lg overflow-hidden shadow-sm h-[320px]">
                    <?php if (!empty($banner_images)): ?>
                        <?php foreach ($banner_images as $idx => $img): ?>
                        <div class="banner-slide <?php echo $idx === 0 ? 'active' : ''; ?> absolute inset-0">
                            <div class="absolute inset-0 bg-cover bg-center"
                                 style="background-image: url('../<?php echo htmlspecialchars($img); ?>');">
                                <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/20 to-transparent"></div>
                            </div>
                            <div class="relative z-10 h-full flex items-center px-12">
                                <div class="text-white">
                                    <h2 class="text-4xl font-bold mb-3 drop-shadow-lg">汇森科技</h2>
                                    <p class="text-xl mb-2 opacity-90">专业手机批发 · 源头直供</p>
                                    <p class="mb-6 opacity-75">比官网更优惠 · 100%正品保障</p>
                                    <a href="quotes_v6.php"
                                       class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 text-white rounded-sm font-semibold
                                              hover:bg-primary-600 transition-colors shadow-lg">
                                        立即选购
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- 默认渐变背景轮播 -->
                        <div class="banner-slide active absolute inset-0 bg-gradient-to-br from-primary-500 to-primary-700">
                            <div class="h-full flex items-center justify-center text-white text-center">
                                <div>
                                    <h2 class="text-4xl font-bold mb-3">汇森科技</h2>
                                    <p class="text-xl mb-2 opacity-90">专业手机批发 · 源头直供</p>
                                    <p class="mb-6 opacity-75">比官网更优惠 · 正品保障</p>
                                    <a href="quotes_v6.php"
                                       class="inline-flex items-center gap-2 px-6 py-3 bg-white text-primary-500 rounded-sm font-bold
                                              hover:bg-gray-100 transition-colors">
                                        立即选购
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="banner-slide absolute inset-0 bg-gradient-to-br from-secondary-700 to-secondary-900">
                            <div class="h-full flex items-center justify-center text-white text-center">
                                <div>
                                    <h2 class="text-4xl font-bold mb-3">iPhone 16 系列</h2>
                                    <p class="text-xl mb-2 opacity-90">新品热销 · 批发价更低</p>
                                    <p class="mb-6 opacity-75">¥6,999 起</p>
                                    <a href="quotes_v6.php?brand=苹果"
                                       class="inline-flex items-center gap-2 px-6 py-3 bg-white text-secondary-800 rounded-sm font-bold
                                              hover:bg-gray-100 transition-colors">
                                        查看详情
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="banner-slide absolute inset-0 bg-gradient-to-br from-blue-500 to-blue-700">
                            <div class="h-full flex items-center justify-center text-white text-center">
                                <div>
                                    <h2 class="text-4xl font-bold mb-3">华为 Mate 系列</h2>
                                    <p class="text-xl mb-2 opacity-90">鸿蒙旗舰 · 现货充足</p>
                                    <p class="mb-6 opacity-75">批发价直降</p>
                                    <a href="quotes_v6.php?brand=华为"
                                       class="inline-flex items-center gap-2 px-6 py-3 bg-white text-blue-600 rounded-sm font-bold
                                              hover:bg-gray-100 transition-colors">
                                        查看详情
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 轮播指示点 -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-20">
                        <?php
                        $dot_count = !empty($banner_images) ? count($banner_images) : 3;
                        for ($i = 0; $i < $dot_count; $i++):
                        ?>
                        <button onclick="showSlide(<?php echo $i; ?>)"
                                class="banner-dot w-2 h-2 rounded-full transition-all
                                       <?php echo $i === 0 ? 'bg-white w-6' : 'bg-white/50 hover:bg-white/80'; ?>">
                        </button>
                        <?php endfor; ?>
                    </div>

                    <!-- 左右箭头 -->
                    <button onclick="prevSlide()"
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 bg-black/20 hover:bg-black/40
                                   text-white rounded-full flex items-center justify-center transition-colors z-20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button onclick="nextSlide()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 bg-black/20 hover:bg-black/40
                                   text-white rounded-full flex items-center justify-center transition-colors z-20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- ========== 右侧：会员工作台 (手机隐藏) ========== -->
            <div class="hidden md:block md:col-span-3">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden h-full flex flex-col">

                    <!-- Top: 用户信息区 -->
                    <div class="p-4 bg-gradient-to-r from-primary-500 to-primary-600 text-white">
                        <?php if ($user['is_logged_in']): ?>
                        <!-- 已登录 -->
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center text-2xl">
                                <?php echo $current_level['icon']; ?>
                            </div>
                            <div>
                                <div class="font-bold text-lg"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="text-sm opacity-90 flex items-center gap-1">
                                    <span><?php echo $current_level['name']; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between bg-white/10 rounded px-3 py-2">
                            <span class="text-sm opacity-90">账户余额</span>
                            <span class="font-bold text-lg">¥<?php echo number_format($user['balance'], 2); ?></span>
                        </div>
                        <?php else: ?>
                        <!-- 未登录 -->
                        <div class="text-center py-2">
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-3xl mx-auto mb-3">
                                👤
                            </div>
                            <p class="text-sm opacity-90 mb-3">登录后享受专属批发价</p>
                            <div class="flex gap-2 justify-center">
                                <a href="../login.php"
                                   class="px-4 py-1.5 bg-white text-primary-500 rounded text-sm font-medium
                                          hover:bg-gray-100 transition-colors">
                                    登录
                                </a>
                                <a href="../register.php"
                                   class="px-4 py-1.5 bg-white/20 text-white rounded text-sm font-medium
                                          hover:bg-white/30 transition-colors">
                                    注册
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Middle: 快捷功能 (2x2 网格) -->
                    <div class="p-4 border-b border-gray-100">
                        <div class="grid grid-cols-2 gap-3">
                            <a href="cart.php"
                               class="flex flex-col items-center gap-1.5 p-3 rounded-lg
                                      hover:bg-gray-50 transition-colors group">
                                <div class="w-10 h-10 bg-primary-50 text-primary-500 rounded-lg
                                            flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                                <span class="text-xs text-gray-600 font-medium">询价单</span>
                            </a>
                            <a href="service.php"
                               class="flex flex-col items-center gap-1.5 p-3 rounded-lg
                                      hover:bg-gray-50 transition-colors group">
                                <div class="w-10 h-10 bg-blue-50 text-blue-500 rounded-lg
                                            flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                                    </svg>
                                </div>
                                <span class="text-xs text-gray-600 font-medium">专属客服</span>
                            </a>
                            <a href="cooperation.php"
                               class="flex flex-col items-center gap-1.5 p-3 rounded-lg
                                      hover:bg-gray-50 transition-colors group">
                                <div class="w-10 h-10 bg-amber-50 text-amber-500 rounded-lg
                                            flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                                <span class="text-xs text-gray-600 font-medium">发布需求</span>
                            </a>
                            <a href="service.php"
                               class="flex flex-col items-center gap-1.5 p-3 rounded-lg
                                      hover:bg-gray-50 transition-colors group">
                                <div class="w-10 h-10 bg-green-50 text-green-500 rounded-lg
                                            flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <span class="text-xs text-gray-600 font-medium">售后申请</span>
                            </a>
                        </div>
                    </div>

                    <!-- Bottom: 平台公告 -->
                    <div class="p-4 flex-1">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-bold text-gray-800 text-sm flex items-center gap-1">
                                <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                </svg>
                                平台公告
                            </h4>
                            <a href="#" class="text-xs text-gray-400 hover:text-primary-500">更多</a>
                        </div>
                        <div class="space-y-2">
                            <?php foreach ($announcements as $notice): ?>
                            <a href="#" class="flex items-start gap-2 text-sm text-gray-600 hover:text-primary-500 transition-colors">
                                <?php if ($notice['hot']): ?>
                                <span class="text-xs px-1 bg-primary-100 text-primary-500 rounded shrink-0">热</span>
                                <?php else: ?>
                                <span class="text-xs px-1 bg-gray-100 text-gray-400 rounded shrink-0">新</span>
                                <?php endif; ?>
                                <span class="line-clamp-1 flex-1"><?php echo htmlspecialchars($notice['title']); ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- 品牌Logo栏 -->
        <div class="brand-logo-bar mb-8">
            <?php
            $brand_logos = [
                '华为' => ['color' => '#c8102e', 'icon' => 'H'],
                '苹果' => ['color' => '#000000', 'icon' => ''],
                '小米' => ['color' => '#ff6700', 'icon' => 'MI'],
                'OPPO' => ['color' => '#00a862', 'icon' => 'O'],
                'vivo' => ['color' => '#0084ff', 'icon' => 'V'],
                '三星' => ['color' => '#0a78f4', 'icon' => 'S'],
                '荣耀' => ['color' => '#e60012', 'icon' => '荣'],
            ];
            foreach ($brand_logos as $name => $config):
            ?>
            <a href="quotes_v6.php?brand=<?php echo urlencode($name); ?>" class="brand-logo-item">
                <div class="brand-logo-circle" style="background: <?php echo $config['color']; ?>;">
                    <?php echo $config['icon']; ?>
                </div>
                <span class="text-sm text-gray-600"><?php echo $name; ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- 手机精品汇 -->
        <div class="bg-white rounded-lg p-5 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold flex items-center gap-2">
                    <span style="color: var(--brand-red);">📱</span>
                    手机精品汇
                    <span class="text-sm font-normal text-gray-400">大牌手机 批发直供</span>
                </h2>
                <a href="quotes_v6.php?category=phone" class="text-sm text-gray-500 hover:text-red-600">查看更多 →</a>
            </div>
            <div class="hot-scroll">
                <?php foreach ($hot_phones as $p): ?>
                <div class="hot-item" onclick="location.href='detail_v5.php?spu=<?php echo $p['id']; ?>'">
                    <div class="w-28 h-28 mx-auto mb-2 bg-gray-50 rounded-lg flex items-center justify-center">
                        <?php if (!empty($p['display_image'])): ?>
                            <img src="../<?php echo htmlspecialchars($p['display_image']); ?>"
                                 alt="<?php echo htmlspecialchars($p['model_name']); ?>"
                                 class="max-w-full max-h-full object-contain">
                        <?php else: ?>
                            <span class="text-4xl text-gray-300">📱</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-gray-600 truncate mb-1"><?php echo htmlspecialchars($p['model_name']); ?></div>
                    <div class="text-red-600 font-bold text-sm">¥<?php echo number_format($p['min_price'], 0); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 品牌楼层 -->
        <?php foreach ($brand_floors as $floor): ?>
        <div class="mb-8">
            <div class="floor-header">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-6 rounded" style="background: <?php echo $floor['color']['bg']; ?>"></div>
                    <h2 class="text-lg font-bold"><?php echo htmlspecialchars($floor['name']); ?> 专区</h2>
                    <span class="text-sm text-gray-400">(<?php echo $floor['total']; ?>款)</span>
                </div>
                <a href="quotes_v6.php?brand=<?php echo urlencode($floor['name']); ?>"
                   class="text-sm hover:underline" style="color: <?php echo $floor['color']['bg']; ?>">
                    查看全部 →
                </a>
            </div>
            <div class="floor-content">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach ($floor['products'] as $p): ?>
                    <div class="product-card p-3" onclick="location.href='detail_v5.php?spu=<?php echo $p['id']; ?>'">
                        <div class="aspect-square bg-gray-50 rounded-lg flex items-center justify-center mb-3 p-4">
                            <?php if (!empty($p['display_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($p['display_image']); ?>"
                                     alt="<?php echo htmlspecialchars($p['model_name']); ?>"
                                     class="max-w-full max-h-full object-contain">
                            <?php else: ?>
                                <span class="text-5xl text-gray-300">📱</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-400 mb-1"><?php echo htmlspecialchars($p['brand']); ?></div>
                        <h3 class="text-sm font-medium text-gray-800 mb-2 line-clamp-2 h-10">
                            <?php echo htmlspecialchars($p['model_name']); ?>
                        </h3>
                        <div class="flex items-baseline gap-1">
                            <span class="text-lg font-bold text-red-600">¥<?php echo number_format($p['min_price'], 0); ?></span>
                            <?php if ($p['sku_count'] > 1): ?>
                            <span class="text-xs text-red-500">起</span>
                            <?php endif; ?>
                            <span class="ml-auto text-xs px-2 py-0.5 bg-orange-100 text-orange-600 rounded">补贴价</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- 配件精选 -->
        <?php if (count($accessory_products) > 0): ?>
        <div class="mb-8">
            <div class="floor-header">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-6 rounded bg-purple-500"></div>
                    <h2 class="text-lg font-bold">配件精选</h2>
                    <span class="text-sm text-gray-400">(<?php echo count($accessory_products); ?>款)</span>
                </div>
                <a href="quotes_v6.php?category=accessory" class="text-sm text-purple-500 hover:underline">
                    查看全部 →
                </a>
            </div>
            <div class="floor-content">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach (array_slice(array_values($accessory_products), 0, 8) as $p): ?>
                    <div class="product-card p-3" onclick="location.href='detail_v5.php?spu=<?php echo $p['id']; ?>'">
                        <div class="aspect-square bg-gray-50 rounded-lg flex items-center justify-center mb-3 p-4">
                            <?php if (!empty($p['display_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($p['display_image']); ?>"
                                     alt="<?php echo htmlspecialchars($p['model_name']); ?>"
                                     class="max-w-full max-h-full object-contain">
                            <?php else: ?>
                                <span class="text-5xl text-gray-300">🎧</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-400 mb-1"><?php echo htmlspecialchars($p['brand']); ?></div>
                        <h3 class="text-sm font-medium text-gray-800 mb-2 line-clamp-2 h-10">
                            <?php echo htmlspecialchars($p['model_name']); ?>
                        </h3>
                        <div class="flex items-baseline gap-1">
                            <span class="text-lg font-bold text-red-600">¥<?php echo number_format($p['min_price'], 0); ?></span>
                            <span class="ml-auto text-xs px-2 py-0.5 bg-purple-100 text-purple-600 rounded">精选</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- 热门机型视差滚动 -->
    <?php $base_path = '../'; include '../includes/hot_products_slider.php'; ?>

    <!-- 侧边悬浮工具栏 -->
    <?php include '../includes/sidebar-tools.php'; ?>

    <!-- 页脚 -->
    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script>
    // Banner轮播 - 增强版
    let currentSlide = 0;
    const slides = document.querySelectorAll('.banner-slide');
    const dots = document.querySelectorAll('.banner-dot');
    let autoPlayInterval;

    function showSlide(index) {
        if (index < 0) index = slides.length - 1;
        if (index >= slides.length) index = 0;

        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
        currentSlide = index;
    }

    function nextSlide() {
        showSlide(currentSlide + 1);
        resetAutoPlay();
    }

    function prevSlide() {
        showSlide(currentSlide - 1);
        resetAutoPlay();
    }

    function resetAutoPlay() {
        clearInterval(autoPlayInterval);
        autoPlayInterval = setInterval(() => {
            showSlide(currentSlide + 1);
        }, 5000);
    }

    // 启动自动播放
    autoPlayInterval = setInterval(() => {
        showSlide(currentSlide + 1);
    }, 5000);
    </script>
</body>
</html>
