<?php
/**
 * ==========================================
 * V5 产品列表页 - 九机网专业风格
 * ==========================================
 *
 * 核心特性：
 * 1. SPU聚合展示（同型号不同颜色只显示一次）
 * 2. 显示 "¥xxx 起" 格式
 * 3. 专业的筛选和搜索功能
 */

require_once '../config/config.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// 获取筛选参数
$brand = $_GET['brand'] ?? '';
$category = $_GET['category'] ?? '';
$search = trim($_GET['search'] ?? $_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'default';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 24;

// 构建查询 - 使用SPU表，天然聚合
$where = ['1=1'];
$params = [];

if (!empty($brand)) {
    $where[] = 'brand = ?';
    $params[] = $brand;
}

if (!empty($category)) {
    $where[] = 'category = ?';
    $params[] = $category;
}

if (!empty($search)) {
    $where[] = '(model_name LIKE ? OR brand LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$where[] = 'min_price > 0';

$where_sql = implode(' AND ', $where);

// 排序
$order_sql = match($sort) {
    'price_asc' => 'min_price ASC',
    'price_desc' => 'min_price DESC',
    'new' => 'id DESC',
    default => 'brand, min_price DESC'
};

// 统计总数
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM products_spu_v3 WHERE $where_sql");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// 获取产品列表
$offset = ($page - 1) * $per_page;
$stmt = $conn->prepare("
    SELECT * FROM products_spu_v3
    WHERE $where_sql
    ORDER BY $order_sql
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取品牌列表
$brands_stmt = $conn->query("
    SELECT brand, COUNT(*) as count
    FROM products_spu_v3
    WHERE min_price > 0
    GROUP BY brand
    ORDER BY count DESC
");
$brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);

// 分类配置
$categories = [
    ['key' => '', 'name' => '全部', 'icon' => '📦'],
    ['key' => 'phone', 'name' => '手机', 'icon' => '📱'],
    ['key' => 'tablet', 'name' => '平板', 'icon' => '📱'],
    ['key' => 'watch', 'name' => '手表', 'icon' => '⌚'],
    ['key' => 'accessory', 'name' => '配件', 'icon' => '🎧'],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $search ? htmlspecialchars($search) . ' - ' : ''; ?><?php echo $brand ?: '全部产品'; ?> - 汇森科技</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --brand-red: #e1251b; }

        .product-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        .product-image {
            transition: transform 0.3s ease;
        }

        .brand-tab {
            transition: all 0.2s;
        }
        .brand-tab:hover {
            background: #fef2f2;
            color: var(--brand-red);
        }
        .brand-tab.active {
            background: var(--brand-red);
            color: white;
        }

        .filter-btn {
            transition: all 0.2s;
        }
        .filter-btn:hover {
            border-color: var(--brand-red);
            color: var(--brand-red);
        }
        .filter-btn.active {
            background: var(--brand-red);
            color: white;
            border-color: var(--brand-red);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- 顶部导航 -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="index_v4.php" class="flex items-center gap-3">
                    <span class="text-2xl font-bold" style="color: var(--brand-red);">汇森科技</span>
                    <span class="text-sm text-gray-500 hidden md:inline">专业手机批发平台</span>
                </a>

                <!-- 搜索框 -->
                <form action="quotes_v5.php" method="GET" class="flex-1 max-w-xl mx-8">
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="搜索手机型号、品牌..."
                               class="w-full px-4 py-2.5 bg-gray-100 border-0 rounded-full focus:outline-none focus:ring-2 focus:ring-red-500 focus:bg-white transition">
                        <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 px-6 py-2 bg-red-500 text-white rounded-full text-sm font-medium hover:bg-red-600 transition">
                            搜索
                        </button>
                    </div>
                </form>

                <nav class="flex items-center gap-6 text-sm">
                    <a href="index_v4.php" class="text-gray-600 hover:text-gray-900 transition">手机首页</a>
                    <a href="quotes_v5.php" class="font-medium transition" style="color: var(--brand-red);">全部产品</a>
                    <a href="cart.php" class="text-gray-600 hover:text-gray-900 transition flex items-center gap-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        询价单
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6">
        <!-- 面包屑导航 -->
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="index_v4.php" class="hover:text-gray-900">首页</a>
            <span>></span>
            <a href="quotes_v5.php" class="hover:text-gray-900">全部产品</a>
            <?php if ($brand): ?>
            <span>></span>
            <span class="text-gray-900"><?php echo htmlspecialchars($brand); ?></span>
            <?php endif; ?>
            <?php if ($search): ?>
            <span>></span>
            <span class="text-gray-900">搜索: <?php echo htmlspecialchars($search); ?></span>
            <?php endif; ?>
        </nav>

        <div class="grid lg:grid-cols-5 gap-6">
            <!-- 左侧筛选栏 -->
            <aside class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm p-5 sticky top-24">
                    <!-- 分类筛选 -->
                    <div class="mb-6">
                        <h3 class="font-bold text-gray-900 mb-3">产品分类</h3>
                        <div class="space-y-2">
                            <?php foreach ($categories as $cat): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => $cat['key'], 'page' => 1])); ?>"
                               class="filter-btn flex items-center gap-2 px-3 py-2 rounded-lg border <?php echo $category === $cat['key'] ? 'active' : 'border-gray-200'; ?>">
                                <span><?php echo $cat['icon']; ?></span>
                                <span><?php echo $cat['name']; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 品牌筛选 -->
                    <div>
                        <h3 class="font-bold text-gray-900 mb-3">品牌</h3>
                        <div class="space-y-2 max-h-80 overflow-y-auto">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['brand' => '', 'page' => 1])); ?>"
                               class="filter-btn flex items-center justify-between px-3 py-2 rounded-lg border <?php echo empty($brand) ? 'active' : 'border-gray-200'; ?>">
                                <span>全部品牌</span>
                            </a>
                            <?php foreach ($brands as $b): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['brand' => $b['brand'], 'page' => 1])); ?>"
                               class="filter-btn flex items-center justify-between px-3 py-2 rounded-lg border <?php echo $brand === $b['brand'] ? 'active' : 'border-gray-200'; ?>">
                                <span><?php echo htmlspecialchars($b['brand']); ?></span>
                                <span class="text-xs text-gray-400"><?php echo $b['count']; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- 右侧产品列表 -->
            <div class="lg:col-span-4">
                <!-- 排序栏 -->
                <div class="bg-white rounded-xl shadow-sm p-4 mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <span class="text-gray-500 text-sm">排序:</span>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'default'])); ?>"
                           class="px-3 py-1 rounded <?php echo $sort === 'default' ? 'bg-red-500 text-white' : 'text-gray-600 hover:text-red-500'; ?>">
                            综合
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>"
                           class="px-3 py-1 rounded <?php echo $sort === 'price_asc' ? 'bg-red-500 text-white' : 'text-gray-600 hover:text-red-500'; ?>">
                            价格↑
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>"
                           class="px-3 py-1 rounded <?php echo $sort === 'price_desc' ? 'bg-red-500 text-white' : 'text-gray-600 hover:text-red-500'; ?>">
                            价格↓
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'new'])); ?>"
                           class="px-3 py-1 rounded <?php echo $sort === 'new' ? 'bg-red-500 text-white' : 'text-gray-600 hover:text-red-500'; ?>">
                            最新
                        </a>
                    </div>
                    <div class="text-sm text-gray-500">
                        共 <span class="font-medium text-gray-900"><?php echo $total; ?></span> 款产品
                    </div>
                </div>

                <?php if (empty($products)): ?>
                <!-- 无结果 -->
                <div class="bg-white rounded-xl shadow-sm p-16 text-center">
                    <div class="text-6xl mb-4">🔍</div>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">未找到相关产品</h3>
                    <p class="text-gray-500 mb-6">换个关键词试试吧</p>
                    <a href="quotes_v5.php" class="inline-block px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                        查看全部产品
                    </a>
                </div>
                <?php else: ?>
                <!-- 产品网格 -->
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($products as $p): ?>
                    <article class="product-card bg-white rounded-xl overflow-hidden shadow-sm"
                             onclick="location.href='detail_v4.php?spu=<?php echo $p['id']; ?>'">
                        <!-- 产品图片 -->
                        <div class="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-6 overflow-hidden">
                            <?php if (!empty($p['image_url']) && file_exists('../' . $p['image_url'])): ?>
                                <img src="../<?php echo htmlspecialchars($p['image_url']); ?>"
                                     alt="<?php echo htmlspecialchars($p['model_name']); ?>"
                                     class="product-image max-w-full max-h-full object-contain"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="text-6xl text-gray-300">📱</div>
                            <?php endif; ?>

                            <!-- SKU数量角标 -->
                            <?php if ($p['sku_count'] > 1): ?>
                            <div class="absolute top-3 right-3">
                                <span class="px-2 py-1 bg-blue-500 text-white text-xs rounded-md shadow font-medium">
                                    <?php echo $p['sku_count']; ?>款可选
                                </span>
                            </div>
                            <?php endif; ?>

                            <!-- 分类标签 -->
                            <div class="absolute top-3 left-3">
                                <span class="px-2 py-1 bg-gray-900/60 text-white text-xs rounded-md backdrop-blur">
                                    <?php echo htmlspecialchars($p['brand']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- 产品信息 -->
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 h-12 leading-6">
                                <?php echo htmlspecialchars($p['model_name']); ?>
                            </h3>

                            <!-- 价格 -->
                            <div class="flex items-baseline gap-1 mb-3">
                                <span class="text-2xl font-bold text-red-600">¥<?php echo number_format($p['min_price'], 0); ?></span>
                                <?php if ($p['sku_count'] > 1): ?>
                                <span class="text-sm text-red-500 font-medium">起</span>
                                <?php endif; ?>
                            </div>

                            <!-- 标签 -->
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 bg-orange-100 text-orange-600 text-xs rounded-full font-medium">批发价</span>
                                <span class="px-2 py-0.5 bg-green-100 text-green-600 text-xs rounded-full font-medium">现货</span>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- 分页 -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-8 flex justify-center">
                    <nav class="flex items-center gap-2">
                        <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                           class="px-4 py-2 bg-white rounded-lg shadow-sm hover:bg-gray-50 transition">
                            上一页
                        </a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);

                        if ($start > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>"
                           class="px-4 py-2 bg-white rounded-lg shadow-sm hover:bg-gray-50 transition">1</a>
                        <?php if ($start > 2): ?>
                        <span class="px-2 text-gray-400">...</span>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                           class="px-4 py-2 rounded-lg shadow-sm transition <?php echo $i === $page ? 'bg-red-500 text-white' : 'bg-white hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($end < $total_pages): ?>
                        <?php if ($end < $total_pages - 1): ?>
                        <span class="px-2 text-gray-400">...</span>
                        <?php endif; ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"
                           class="px-4 py-2 bg-white rounded-lg shadow-sm hover:bg-gray-50 transition"><?php echo $total_pages; ?></a>
                        <?php endif; ?>

                        <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                           class="px-4 py-2 bg-white rounded-lg shadow-sm hover:bg-gray-50 transition">
                            下一页
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- 页脚 -->
    <footer class="bg-gray-900 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h3 class="text-lg font-bold mb-4" style="color: var(--brand-red);">汇森科技</h3>
                    <p class="text-gray-400 text-sm mb-4">专业手机批发商</p>
                    <div class="flex gap-2 flex-wrap">
                        <span class="px-3 py-1 bg-green-600/20 text-green-400 text-xs rounded-full">✓ 正品保障</span>
                        <span class="px-3 py-1 bg-blue-600/20 text-blue-400 text-xs rounded-full">✓ 急速发货</span>
                    </div>
                </div>
                <div>
                    <h4 class="font-medium mb-4">快速链接</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="index_v4.php" class="hover:text-white transition">手机首页</a></li>
                        <li><a href="quotes_v5.php" class="hover:text-white transition">全部产品</a></li>
                        <li><a href="cart.php" class="hover:text-white transition">我的询价单</a></li>
                        <li><a href="cooperation.php" class="hover:text-white transition">批发合作</a></li>
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
                    <p class="text-sm text-gray-400 mt-2">工作时间: 9:00-21:00</p>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-sm text-gray-500">
                © 2026 汇森科技 版权所有 | 专业手机批发平台
            </div>
        </div>
    </footer>
</body>
</html>
