<?php
/**
 * ==========================================
 * V7 专业报价页 - 京东/淘宝横向卡片风格
 * ==========================================
 *
 * 升级内容：
 * 1. 复用公共 header.php
 * 2. 横向富媒体商品卡片
 * 3. 悬浮上浮动效
 * 4. 专业 B2B 筛选器
 */

require_once '../config/config.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// 获取筛选参数
$brand = $_GET['brand'] ?? '';
$category = $_GET['category'] ?? '';
$search = trim($_GET['search'] ?? $_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'default';
$price_min = intval($_GET['price_min'] ?? 0);
$price_max = intval($_GET['price_max'] ?? 0);
$storage = $_GET['storage'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// 构建查询 - 使用SPU表，天然聚合
$where = ['1=1'];
$params = [];

if (!empty($brand)) {
    $where[] = 'p.brand = ?';
    $params[] = $brand;
}

if (!empty($category)) {
    $where[] = 'p.category = ?';
    $params[] = $category;
}

if (!empty($search)) {
    $where[] = '(p.model_name LIKE ? OR p.brand LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$where[] = 'p.min_price > 0';

// 价格区间筛选
if ($price_min > 0) {
    $where[] = 'p.min_price >= ?';
    $params[] = $price_min;
}
if ($price_max > 0) {
    $where[] = 'p.min_price <= ?';
    $params[] = $price_max;
}

$where_sql = implode(' AND ', $where);

// 排序
$order_sql = match($sort) {
    'price_asc' => 'p.min_price ASC',
    'price_desc' => 'p.min_price DESC',
    'new' => 'p.id DESC',
    default => 'p.brand, p.min_price DESC'
};

// 检查使用哪个表（优先使用v4聚合表）
$use_v4 = $conn->query("SHOW TABLES LIKE 'products_spu_v4'")->rowCount() > 0;
$spu_table = $use_v4 ? 'products_spu_v4' : 'products_spu_v3';

// 统计总数
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM $spu_table p WHERE $where_sql");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// 获取产品列表（带封面图）
$offset = ($page - 1) * $per_page;
$stmt = $conn->prepare("
    SELECT p.*, pi.image_path as cover_image
    FROM $spu_table p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.sort_order = 0
    WHERE $where_sql
    ORDER BY $order_sql
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 为每个产品设置最佳显示图片
foreach ($products as &$p) {
    if (!empty($p['cover_image']) && file_exists('../' . $p['cover_image'])) {
        $p['display_image'] = $p['cover_image'];
    } elseif (!empty($p['image_url']) && file_exists('../' . $p['image_url'])) {
        $p['display_image'] = $p['image_url'];
    } else {
        $p['display_image'] = '';
    }

    // 计算利润差价（假设官网价比批发价高15-25%）
    $p['official_price'] = $p['official_price'] ?? round($p['min_price'] * 1.18);
    $p['profit'] = $p['official_price'] - $p['min_price'];
}
unset($p);

// 获取品牌列表及数量
$brands_stmt = $conn->query("
    SELECT brand, COUNT(*) as count
    FROM $spu_table
    WHERE min_price > 0
    GROUP BY brand
    ORDER BY count DESC
");
$brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);

// 主要品牌
$main_brands = ['苹果', '华为', '小米', '荣耀', 'OPPO', 'vivo', '三星'];

// 分类配置
$categories_list = [
    ['key' => '', 'name' => '全部', 'icon' => '📦'],
    ['key' => 'phone', 'name' => '手机', 'icon' => '📱'],
    ['key' => 'tablet', 'name' => '平板', 'icon' => '📱'],
    ['key' => 'watch', 'name' => '手表', 'icon' => '⌚'],
    ['key' => 'accessory', 'name' => '配件', 'icon' => '🎧'],
];

// 价格区间预设
$price_ranges = [
    ['min' => 0, 'max' => 2000, 'label' => '2000以下'],
    ['min' => 2000, 'max' => 4000, 'label' => '2000-4000'],
    ['min' => 4000, 'max' => 6000, 'label' => '4000-6000'],
    ['min' => 6000, 'max' => 10000, 'label' => '6000-10000'],
    ['min' => 10000, 'max' => 0, 'label' => '10000以上'],
];

// 当前展示的标题
$page_title = '全部产品';
if (!empty($brand)) {
    $page_title = $brand . ' 产品';
} elseif (!empty($search)) {
    $page_title = '搜索: ' . $search;
} elseif (!empty($category)) {
    $page_title = match($category) {
        'phone' => '手机',
        'tablet' => '平板电脑',
        'watch' => '智能手表',
        'accessory' => '配件',
        default => $category
    };
}

// 设置 header 路径
$base_path = '../';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - 汇森科技批发报价</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-background min-h-screen">
    <?php include '../includes/header.php'; ?>

    <main class="max-w-[1280px] mx-auto px-4 py-6">

        <!-- ==========================================
             面包屑导航
             ========================================== -->
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="index_v4.php" class="hover:text-primary-500">首页</a>
            <span>/</span>
            <a href="quotes_v6.php" class="hover:text-primary-500">商品报价</a>
            <?php if (!empty($brand)): ?>
            <span>/</span>
            <span class="text-gray-800"><?php echo htmlspecialchars($brand); ?></span>
            <?php endif; ?>
            <?php if (!empty($search)): ?>
            <span>/</span>
            <span class="text-gray-800">搜索: <?php echo htmlspecialchars($search); ?></span>
            <?php endif; ?>
        </div>

        <!-- ==========================================
             高级筛选器 (Filter Bar)
             Stage 12: 移动端横向滑动优化
             ========================================== -->
        <style>
            .no-scrollbar::-webkit-scrollbar { display: none; }
            .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        </style>
        <div class="bg-white rounded-lg shadow-sm p-3 md:p-5 mb-4 md:mb-6">
            <!-- 品牌筛选 -->
            <div class="flex items-center gap-2 md:gap-4 mb-3 md:mb-4 pb-3 md:pb-4 border-b border-gray-100">
                <span class="text-gray-500 text-xs md:text-sm w-10 md:w-16 flex-shrink-0">品牌</span>
                <div class="flex items-center gap-1.5 md:gap-2 overflow-x-auto no-scrollbar whitespace-nowrap md:flex-wrap md:whitespace-normal">
                    <a href="?<?php echo http_build_query(array_filter(['category' => $category, 'sort' => $sort, 'search' => $search])); ?>"
                       class="px-3 md:px-4 py-1 md:py-1.5 rounded-full text-xs md:text-sm font-medium transition-all flex-shrink-0
                              <?php echo empty($brand)
                                  ? 'bg-primary-500 text-white'
                                  : 'bg-gray-100 text-gray-600 hover:bg-primary-50 hover:text-primary-500'; ?>">
                        全部
                    </a>
                    <?php foreach ($main_brands as $b): ?>
                    <a href="?<?php echo http_build_query(array_filter(['brand' => $b, 'category' => $category, 'sort' => $sort, 'search' => $search])); ?>"
                       class="px-3 md:px-4 py-1 md:py-1.5 rounded-full text-xs md:text-sm font-medium transition-all flex-shrink-0
                              <?php echo $brand === $b
                                  ? 'bg-primary-500 text-white'
                                  : 'bg-gray-100 text-gray-600 hover:bg-primary-50 hover:text-primary-500'; ?>">
                        <?php echo $b; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 分类筛选 -->
            <div class="flex items-center gap-2 md:gap-4 mb-3 md:mb-4 pb-3 md:pb-4 border-b border-gray-100">
                <span class="text-gray-500 text-xs md:text-sm w-10 md:w-16 flex-shrink-0">分类</span>
                <div class="flex items-center gap-1.5 md:gap-2 overflow-x-auto no-scrollbar whitespace-nowrap md:flex-wrap md:whitespace-normal">
                    <?php foreach ($categories_list as $cat): ?>
                    <a href="?<?php echo http_build_query(array_filter(['category' => $cat['key'], 'brand' => $brand, 'sort' => $sort, 'search' => $search])); ?>"
                       class="px-3 md:px-4 py-1 md:py-1.5 rounded-full text-xs md:text-sm font-medium transition-all flex items-center gap-1 flex-shrink-0
                              <?php echo $category === $cat['key']
                                  ? 'bg-primary-500 text-white'
                                  : 'bg-gray-100 text-gray-600 hover:bg-primary-50 hover:text-primary-500'; ?>">
                        <span><?php echo $cat['icon']; ?></span>
                        <span><?php echo $cat['name']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 价格区间 -->
            <div class="flex items-center gap-2 md:gap-4 mb-3 md:mb-4 pb-3 md:pb-4 border-b border-gray-100">
                <span class="text-gray-500 text-xs md:text-sm w-10 md:w-16 flex-shrink-0">价格</span>
                <div class="flex items-center gap-1.5 md:gap-2 overflow-x-auto no-scrollbar whitespace-nowrap md:flex-wrap md:whitespace-normal">
                    <a href="?<?php echo http_build_query(array_filter(['brand' => $brand, 'category' => $category, 'sort' => $sort, 'search' => $search])); ?>"
                       class="px-3 md:px-4 py-1 md:py-1.5 rounded-full text-xs md:text-sm font-medium transition-all flex-shrink-0
                              <?php echo ($price_min == 0 && $price_max == 0)
                                  ? 'bg-primary-500 text-white'
                                  : 'bg-gray-100 text-gray-600 hover:bg-primary-50 hover:text-primary-500'; ?>">
                        不限
                    </a>
                    <?php foreach ($price_ranges as $pr): ?>
                    <?php
                    $is_active = ($price_min == $pr['min'] && ($price_max == $pr['max'] || ($pr['max'] == 0 && $price_max == 0)));
                    ?>
                    <a href="?<?php echo http_build_query(array_filter(['brand' => $brand, 'category' => $category, 'sort' => $sort, 'search' => $search, 'price_min' => $pr['min'], 'price_max' => $pr['max']])); ?>"
                       class="px-3 md:px-4 py-1 md:py-1.5 rounded-full text-xs md:text-sm font-medium transition-all flex-shrink-0
                              <?php echo $is_active
                                  ? 'bg-primary-500 text-white'
                                  : 'bg-gray-100 text-gray-600 hover:bg-primary-50 hover:text-primary-500'; ?>">
                        <?php echo $pr['label']; ?>
                    </a>
                    <?php endforeach; ?>

                    <!-- 自定义价格 (仅PC显示) -->
                    <div class="hidden md:flex items-center gap-2 ml-4 flex-shrink-0">
                        <input type="number" id="priceMin" placeholder="最低价"
                               value="<?php echo $price_min ?: ''; ?>"
                               class="w-24 px-3 py-1.5 border border-gray-200 rounded text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
                        <span class="text-gray-400">-</span>
                        <input type="number" id="priceMax" placeholder="最高价"
                               value="<?php echo $price_max ?: ''; ?>"
                               class="w-24 px-3 py-1.5 border border-gray-200 rounded text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
                        <button onclick="applyPriceFilter()"
                                class="px-4 py-1.5 bg-gray-100 text-gray-700 rounded text-sm font-medium
                                       hover:bg-gray-200 transition-colors">
                            确定
                        </button>
                    </div>
                </div>
            </div>

            <!-- 排序 + 统计 -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-0.5 md:gap-1 overflow-x-auto no-scrollbar whitespace-nowrap">
                    <span class="text-gray-500 text-xs md:text-sm w-10 md:w-16 flex-shrink-0">排序</span>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'default'])); ?>"
                       class="px-2 md:px-4 py-1 md:py-1.5 rounded text-xs md:text-sm transition-colors flex-shrink-0
                              <?php echo $sort === 'default' ? 'text-primary-500 font-medium' : 'text-gray-600 hover:text-primary-500'; ?>">
                        综合
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'new'])); ?>"
                       class="px-2 md:px-4 py-1 md:py-1.5 rounded text-xs md:text-sm transition-colors flex-shrink-0
                              <?php echo $sort === 'new' ? 'text-primary-500 font-medium' : 'text-gray-600 hover:text-primary-500'; ?>">
                        新品
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>"
                       class="px-2 md:px-4 py-1 md:py-1.5 rounded text-xs md:text-sm transition-colors flex items-center gap-0.5 flex-shrink-0
                              <?php echo $sort === 'price_asc' ? 'text-primary-500 font-medium' : 'text-gray-600 hover:text-primary-500'; ?>">
                        价格↑
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>"
                       class="px-2 md:px-4 py-1 md:py-1.5 rounded text-xs md:text-sm transition-colors flex items-center gap-0.5 flex-shrink-0
                              <?php echo $sort === 'price_desc' ? 'text-primary-500 font-medium' : 'text-gray-600 hover:text-primary-500'; ?>">
                        价格↓
                    </a>
                </div>
                <div class="text-xs md:text-sm text-gray-500 flex-shrink-0 ml-2">
                    <span class="hidden md:inline">共找到 </span><span class="text-primary-500 font-bold"><?php echo $total; ?></span><span class="hidden md:inline"> 款产品</span><span class="md:hidden">款</span>
                </div>
            </div>
        </div>

        <?php if (empty($products)): ?>
        <!-- ==========================================
             无结果 - 精美空状态
             ========================================== -->
        <div class="bg-white rounded-lg shadow-sm p-16 text-center">
            <div class="w-32 h-32 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                <span class="text-5xl">🔍</span>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">未找到相关产品</h3>
            <p class="text-gray-500 mb-6">换个关键词试试吧，或者浏览热门品牌</p>
            <div class="flex justify-center gap-3 flex-wrap mb-6">
                <?php foreach ($main_brands as $b): ?>
                <a href="?brand=<?php echo urlencode($b); ?>"
                   class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg
                          hover:bg-primary-50 hover:text-primary-500 transition-colors">
                    <?php echo $b; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <a href="quotes_v6.php"
               class="inline-block px-6 py-2 bg-primary-500 text-white rounded
                      hover:bg-primary-600 transition-colors font-medium">
                查看全部产品
            </a>
        </div>

        <?php else: ?>
        <!-- ==========================================
             商品列表 - 移动端双列布局优化
             Mobile: 2列 (gap-2) | Tablet: 4列 | Desktop: 5列
             ========================================== -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-2 md:gap-4">
            <?php foreach ($products as $product): ?>
            <article class="bg-white rounded-lg overflow-hidden group cursor-pointer
                            border border-gray-100
                            md:shadow-sm md:hover:-translate-y-1 md:hover:shadow-lg md:hover:border-primary-200
                            transition-all duration-300"
                     onclick="location.href='detail_v5.php?spu=<?php echo $product['id']; ?>'">

                <!-- ===== 图片区 (移动端优化高度) ===== -->
                <div class="relative h-32 md:h-44 lg:h-48 bg-gray-50 overflow-hidden">
                    <?php if (!empty($product['display_image'])): ?>
                    <img src="../<?php echo htmlspecialchars($product['display_image']); ?>"
                         alt="<?php echo htmlspecialchars($product['model_name']); ?>"
                         class="w-full h-full object-contain p-1.5 md:p-3
                                group-hover:scale-105 transition-transform duration-300"
                         loading="lazy">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center">
                        <span class="text-2xl md:text-4xl text-gray-200">📱</span>
                    </div>
                    <?php endif; ?>

                    <!-- 左上角标签 (移动端缩小) -->
                    <div class="absolute top-0.5 left-0.5 md:top-2 md:left-2 flex gap-0.5 md:gap-1">
                        <span class="px-1 md:px-1.5 py-0.5 bg-primary-500 text-white text-[9px] md:text-[10px] rounded font-medium">
                            热销
                        </span>
                        <?php if ($product['sku_count'] > 1): ?>
                        <span class="hidden md:inline-block px-1.5 py-0.5 bg-blue-500 text-white text-[10px] rounded">
                            <?php echo $product['sku_count']; ?>款
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- 右上角品牌 (仅PC显示) -->
                    <span class="hidden md:block absolute top-2 right-2 px-1.5 py-0.5 bg-black/50 text-white text-[10px] rounded">
                        <?php echo htmlspecialchars($product['brand']); ?>
                    </span>
                </div>

                <!-- ===== 内容区 (移动端紧凑布局) ===== -->
                <div class="p-1.5 md:p-3">
                    <!-- 商品名称 (移动端单行，PC端两行) -->
                    <h3 class="text-[11px] md:text-sm font-medium md:font-bold text-gray-800 
                               line-clamp-1 md:line-clamp-2 
                               h-4 md:h-10 leading-tight md:leading-5 mb-0.5 md:mb-1
                               group-hover:text-primary-500 transition-colors">
                        <?php echo htmlspecialchars($product['model_name']); ?>
                    </h3>

                    <!-- 规格标签 (移动端简化) -->
                    <div class="flex flex-wrap gap-0.5 md:gap-1 mb-1 md:mb-2">
                        <?php
                        $specs = [];
                        if (preg_match_all('/(\d+)(GB|TB)/i', $product['model_name'], $matches)) {
                            $specs = array_unique($matches[0]);
                        }
                        foreach (array_slice($specs, 0, 1) as $spec):
                        ?>
                        <span class="hidden md:inline-block px-1 py-0.5 bg-gray-100 text-gray-500 text-[10px] rounded">
                            <?php echo htmlspecialchars($spec); ?>
                        </span>
                        <?php endforeach; ?>
                        <span class="px-1 py-0.5 bg-red-50 text-primary-500 text-[9px] md:text-[10px] rounded">
                            现货
                        </span>
                    </div>

                    <!-- 价格行 (移动端紧凑) -->
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-0.5">
                                <span class="text-[10px] md:text-xs text-primary-500">¥</span>
                                <span class="text-sm md:text-lg font-bold text-primary-500 truncate">
                                    <?php echo number_format($product['min_price'], 0); ?>
                                </span>
                                <?php if ($product['sku_count'] > 1): ?>
                                <span class="text-[9px] md:text-[10px] text-primary-400 ml-0.5 flex-shrink-0">起</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($product['profit'] > 0): ?>
                            <span class="hidden md:inline text-[10px] text-gray-400 line-through">
                                ¥<?php echo number_format($product['official_price'], 0); ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- 购物车按钮 (移动端缩小) -->
                        <button onclick="event.stopPropagation(); addToCart(<?php echo $product['id']; ?>)"
                                class="w-5 h-5 md:w-7 md:h-7 rounded-full bg-primary-500 text-white
                                       flex items-center justify-center shadow flex-shrink-0
                                       hover:bg-primary-600 active:scale-95 transition-all ml-1">
                            <svg class="w-2.5 h-2.5 md:w-3.5 md:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- ==========================================
             分页器
             ========================================== -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-10 flex justify-center">
            <nav class="flex items-center gap-2">
                <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                   class="px-5 py-2.5 bg-white rounded-lg shadow-sm hover:bg-gray-50
                          transition font-medium text-gray-700">
                    上一页
                </a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);

                if ($start > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>"
                   class="w-10 h-10 flex items-center justify-center bg-white rounded-lg shadow-sm
                          hover:bg-gray-50 transition">1</a>
                <?php if ($start > 2): ?>
                <span class="px-2 text-gray-400">...</span>
                <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                   class="w-10 h-10 flex items-center justify-center rounded-lg shadow-sm transition font-medium
                          <?php echo $i === $page
                              ? 'bg-primary-500 text-white'
                              : 'bg-white hover:bg-gray-50 text-gray-700'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>

                <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?>
                <span class="px-2 text-gray-400">...</span>
                <?php endif; ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"
                   class="w-10 h-10 flex items-center justify-center bg-white rounded-lg shadow-sm
                          hover:bg-gray-50 transition"><?php echo $total_pages; ?></a>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                   class="px-5 py-2.5 bg-white rounded-lg shadow-sm hover:bg-gray-50
                          transition font-medium text-gray-700">
                    下一页
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </main>

    <!-- 侧边悬浮工具栏 -->
    <?php include '../includes/sidebar-tools.php'; ?>

    <!-- 页脚 -->
    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script>
    // 价格筛选
    function applyPriceFilter() {
        const min = document.getElementById('priceMin').value;
        const max = document.getElementById('priceMax').value;
        const url = new URL(window.location);

        if (min) url.searchParams.set('price_min', min);
        else url.searchParams.delete('price_min');

        if (max) url.searchParams.set('price_max', max);
        else url.searchParams.delete('price_max');

        window.location.href = url.toString();
    }

    // 回车提交价格筛选
    document.getElementById('priceMin')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') applyPriceFilter();
    });
    document.getElementById('priceMax')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') applyPriceFilter();
    });

    // 加入进货单
    function addToCart(productId) {
        fetch('../api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_id: productId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // 显示成功提示
                const toast = document.createElement('div');
                toast.className = 'fixed top-24 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-xl z-50 animate-fade-in';
                toast.innerHTML = '✓ 已加入进货单';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2000);
            } else {
                alert(data.message || '添加失败');
            }
        })
        .catch(() => {
            // 如果API不存在，跳转到详情页
            location.href = 'detail_v5.php?spu=' + productId;
        });
    }
    </script>

    <style>
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }
    </style>
</body>
</html>
