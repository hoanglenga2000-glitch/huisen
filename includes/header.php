<?php
/**
 * ==========================================
 * 汇森科技 - 超级头部组件 v3.1
 * B2B 专业批发风格 - PC/Mobile 分离设计
 * ==========================================
 *
 * Stage 11 升级：
 * 1. PC Header (hidden md:block) - 三层式设计
 * 2. Mobile Header (block md:hidden) - 吸顶搜索栏
 */

// 获取当前页面用于导航高亮
$current_page = basename($_SERVER['PHP_SELF']);

// 检查登录状态
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $_SESSION['username'] ?? '';
$user_level = $_SESSION['user_level'] ?? 'normal'; // normal, gold, partner
$user_balance = $_SESSION['balance'] ?? 0;

// 热搜词配置
$hot_keywords = [
    ['keyword' => 'iPhone 16 Pro Max', 'hot' => true],
    ['keyword' => 'Mate 70', 'hot' => true],
    ['keyword' => '小米15 Ultra', 'hot' => false],
    ['keyword' => '荣耀Magic7', 'hot' => false],
    ['keyword' => 'vivo X200', 'hot' => false],
];

// 购物车/询价单数量
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}

// 基础路径
$base = isset($base_path) ? $base_path : '';
?>
<!-- Tailwind CSS 本地构建文件 -->
<link rel="stylesheet" href="<?php echo $base; ?>dist/css/output.css">

<!-- ==========================================
     Mobile Header (手机端 - 吸顶红色搜索栏)
     ========================================== -->
<header class="block md:hidden fixed top-0 left-0 w-full z-[200] bg-primary-500 shadow-md"
        style="padding-top: env(safe-area-inset-top);">
    <div class="h-12 flex items-center px-3 gap-3">
        <!-- 左侧：扫一扫图标 -->
        <a href="<?php echo $base; ?>index.php" class="flex-shrink-0">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h2M4 12h2m10 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
        </a>

        <!-- 中间：搜索框 -->
        <form action="<?php echo $base; ?>core/quotes_v6.php" method="GET" class="flex-1">
            <div class="bg-white rounded-full h-8 flex items-center px-3 gap-2">
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                       name="search"
                       placeholder="iPhone 17 Pro Max..."
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                       class="flex-1 text-sm text-gray-700 placeholder:text-gray-400 bg-transparent border-none outline-none">
            </div>
        </form>

        <!-- 右侧：登录/消息图标 -->
        <?php if ($is_logged_in): ?>
        <a href="<?php echo $base; ?>core/user_center.php" class="flex-shrink-0 relative">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <!-- 消息红点 -->
            <span class="absolute -top-1 -right-1 w-2 h-2 bg-yellow-400 rounded-full"></span>
        </a>
        <?php else: ?>
        <a href="<?php echo $base; ?>login.php" class="flex-shrink-0">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </a>
        <?php endif; ?>
    </div>
</header>
<!-- Mobile Header 占位符 (防止内容被遮挡) -->
<div class="block md:hidden h-12" style="padding-top: env(safe-area-inset-top);"></div>


<!-- ==========================================
     Desktop Header (电脑端 - 三层式设计)
     ========================================== -->
<div class="hidden md:block">

    <!-- 第一层：顶部工具栏 (Top Bar) - 32px -->
    <div class="bg-secondary-800 h-8 text-xs">
        <div class="max-w-[1280px] mx-auto px-4 h-full flex items-center justify-between">
            <!-- 左侧欢迎语 -->
            <div class="text-gray-400">
                你好，欢迎来到汇森科技！专业手机批发平台
            </div>

            <!-- 右侧工具链接 -->
            <div class="flex items-center gap-1 text-gray-300">
                <?php if ($is_logged_in): ?>
                    <!-- 已登录状态 -->
                    <a href="<?php echo $base; ?>core/user_center.php" class="hover:text-white transition-colors px-2 py-1 flex items-center gap-1">
                        <?php
                        $level_icon = match($user_level) {
                            'gold' => '👑',
                            'partner' => '💎',
                            default => '👤'
                        };
                        $level_text = match($user_level) {
                            'gold' => '金牌会员',
                            'partner' => '合作伙伴',
                            default => htmlspecialchars($username)
                        };
                        ?>
                        <span><?php echo $level_icon; ?></span>
                        <span><?php echo $level_text; ?></span>
                    </a>
                    <span class="text-gray-600">|</span>
                    <a href="<?php echo $base; ?>logout.php" class="hover:text-white transition-colors px-2 py-1">退出</a>
                <?php else: ?>
                    <!-- 未登录状态 -->
                    <a href="<?php echo $base; ?>login.php" class="hover:text-white transition-colors px-2 py-1">登录</a>
                    <span class="text-gray-600">|</span>
                    <a href="<?php echo $base; ?>register.php" class="hover:text-white transition-colors px-2 py-1">免费注册</a>
                <?php endif; ?>

                <span class="text-gray-600">|</span>
                <a href="<?php echo $base; ?>core/orders.php" class="hover:text-white transition-colors px-2 py-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    我的订单
                </a>
                <span class="text-gray-600">|</span>
                <a href="#" class="hover:text-white transition-colors px-2 py-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    手机端
                </a>
                <span class="text-gray-600">|</span>
                <a href="<?php echo $base; ?>core/help.php" class="hover:text-white transition-colors px-2 py-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    帮助中心
                </a>
            </div>
        </div>
    </div>

    <!-- 第二层：核心功能区 (Brand & Search) - 96px -->
    <div class="bg-white h-24 shadow-sm">
        <div class="max-w-[1280px] mx-auto px-4 h-full flex items-center justify-between gap-8">

            <!-- 左侧 Logo -->
            <a href="<?php echo $base; ?>index.php" class="flex-shrink-0 flex items-baseline gap-2 group">
                <span class="text-3xl font-bold text-primary-500 group-hover:text-primary-600 transition-colors">
                    汇森科技
                </span>
                <span class="text-sm text-gray-400 hidden sm:inline">
                    专业手机批发
                </span>
            </a>

            <!-- 中间 超级搜索框 -->
            <div class="flex-1 max-w-[500px] relative" id="searchContainer">
                <form action="<?php echo $base; ?>core/quotes_v6.php" method="GET" class="w-full">
                    <div class="flex border-2 border-primary-500 rounded-full overflow-hidden bg-white
                                focus-within:ring-2 focus-within:ring-primary-200 transition-all">
                        <input type="text"
                               name="search"
                               id="globalSearch"
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                               placeholder="输入型号搜索，如 iPhone 16 Pro Max..."
                               autocomplete="off"
                               class="flex-1 px-5 py-3 text-sm border-none outline-none bg-transparent
                                      placeholder:text-gray-400">
                        <button type="submit"
                                class="px-8 py-3 bg-primary-500 text-white font-semibold
                                       hover:bg-primary-600 active:bg-primary-700 transition-colors">
                            搜索
                        </button>
                    </div>
                </form>

                <!-- 热搜词下拉 -->
                <div id="hotSearchDropdown"
                     class="absolute top-full left-0 right-0 mt-2 bg-white rounded-lg shadow-xl
                            border border-gray-100 p-4 z-[100] hidden">
                    <div class="text-xs text-gray-400 mb-3 font-medium">热门搜索</div>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($hot_keywords as $kw): ?>
                        <a href="<?php echo $base; ?>core/quotes_v6.php?search=<?php echo urlencode($kw['keyword']); ?>"
                           class="px-3 py-1.5 text-sm rounded-full transition-all
                                  <?php echo $kw['hot']
                                      ? 'bg-red-50 text-primary-500 hover:bg-red-100'
                                      : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                            <?php if ($kw['hot']): ?>🔥<?php endif; ?>
                            <?php echo htmlspecialchars($kw['keyword']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 右侧 资产区域 -->
            <div class="flex-shrink-0 flex items-center gap-6">
                <!-- 询价单 -->
                <a href="<?php echo $base; ?>core/cart.php"
                   class="flex items-center gap-2 px-4 py-2 rounded-md
                          hover:bg-gray-50 transition-colors group relative">
                    <div class="relative">
                        <svg class="w-6 h-6 text-gray-500 group-hover:text-primary-500 transition-colors"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <?php if ($cart_count > 0): ?>
                        <span class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1
                                     bg-primary-500 text-white text-xs font-bold rounded-full
                                     flex items-center justify-center">
                            <?php echo $cart_count > 99 ? '99+' : $cart_count; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="hidden lg:block">
                        <div class="text-xs text-gray-400">我的询价单</div>
                        <div class="text-sm font-medium text-gray-700">
                            <?php echo $cart_count; ?> 件商品
                        </div>
                    </div>
                </a>

                <?php if ($is_logged_in): ?>
                <!-- 账户余额 (登录后显示) -->
                <div class="flex items-center gap-2 px-4 py-2 rounded-md bg-gray-50">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <div class="text-xs text-gray-400">账户余额</div>
                        <div class="text-sm font-bold text-primary-500">
                            ¥<?php echo number_format($user_balance, 2); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 第三层：主导航栏 (Main Nav) - 48px -->
    <nav class="bg-white border-b-2 border-primary-500 sticky top-0 z-[200] shadow-sm">
        <div class="max-w-[1280px] mx-auto px-4 flex items-stretch">

            <!-- 全部商品分类 (红色块) -->
            <div class="relative group">
                <button class="w-52 h-12 bg-primary-500 text-white font-bold
                               flex items-center justify-center gap-2
                               hover:bg-primary-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    全部商品分类
                    <svg class="w-4 h-4 ml-auto mr-2 transition-transform group-hover:rotate-180"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- 分类下拉菜单 -->
                <div class="absolute top-full left-0 w-52 bg-white shadow-xl border border-gray-100
                            opacity-0 invisible group-hover:opacity-100 group-hover:visible
                            transition-all duration-200 z-[100]">
                    <a href="<?php echo $base; ?>core/quotes_v6.php?brand=apple"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 hover:text-primary-500 transition-colors border-b border-gray-50">
                        <span class="text-xl">🍎</span>
                        <span class="font-medium">苹果 iPhone</span>
                    </a>
                    <a href="<?php echo $base; ?>core/quotes_v6.php?brand=huawei"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 hover:text-primary-500 transition-colors border-b border-gray-50">
                        <span class="text-xl">📱</span>
                        <span class="font-medium">华为 HUAWEI</span>
                    </a>
                    <a href="<?php echo $base; ?>core/quotes_v6.php?brand=xiaomi"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 hover:text-primary-500 transition-colors border-b border-gray-50">
                        <span class="text-xl">🔶</span>
                        <span class="font-medium">小米 Xiaomi</span>
                    </a>
                    <a href="<?php echo $base; ?>core/quotes_v6.php?brand=oppo"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 hover:text-primary-500 transition-colors border-b border-gray-50">
                        <span class="text-xl">🟢</span>
                        <span class="font-medium">OPPO</span>
                    </a>
                    <a href="<?php echo $base; ?>core/quotes_v6.php?brand=vivo"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 hover:text-primary-500 transition-colors border-b border-gray-50">
                        <span class="text-xl">🔵</span>
                        <span class="font-medium">vivo</span>
                    </a>
                    <a href="<?php echo $base; ?>core/quotes_v6.php?brand=honor"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 hover:text-primary-500 transition-colors border-b border-gray-50">
                        <span class="text-xl">⭐</span>
                        <span class="font-medium">荣耀 Honor</span>
                    </a>
                    <a href="<?php echo $base; ?>core/quotes_v6.php?brand=samsung"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 hover:text-primary-500 transition-colors">
                        <span class="text-xl">💠</span>
                        <span class="font-medium">三星 Samsung</span>
                    </a>
                </div>
            </div>

            <!-- 横向导航菜单 -->
            <div class="flex items-center ml-2">
                <a href="<?php echo $base; ?>index.php"
                   class="px-5 h-12 flex items-center font-bold text-gray-800
                          hover:text-primary-500 transition-colors
                          <?php echo $current_page === 'index.php' || strpos($current_page, 'index') !== false ? 'text-primary-500' : ''; ?>">
                    首页
                </a>
                <a href="<?php echo $base; ?>core/flash_sale.php"
                   class="px-5 h-12 flex items-center gap-1 font-bold text-gray-800
                          hover:text-primary-500 transition-colors
                          <?php echo strpos($current_page, 'flash_sale') !== false ? 'text-primary-500' : ''; ?>">
                    <span class="text-primary-500">🔥</span>
                    限时抢购
                </a>
                <a href="<?php echo $base; ?>core/quotes_v6.php?brand=apple"
                   class="px-5 h-12 flex items-center font-bold text-gray-800
                          hover:text-primary-500 transition-colors">
                    苹果专区
                </a>
                <a href="<?php echo $base; ?>core/quotes_v6.php?type=android"
                   class="px-5 h-12 flex items-center font-bold text-gray-800
                          hover:text-primary-500 transition-colors">
                    安卓专区
                </a>
                <a href="<?php echo $base; ?>core/accessories.php"
                   class="px-5 h-12 flex items-center font-bold text-gray-800
                          hover:text-primary-500 transition-colors
                          <?php echo strpos($current_page, 'accessories') !== false ? 'text-primary-500' : ''; ?>">
                    数码配件
                </a>
                <a href="<?php echo $base; ?>core/service.php"
                   class="px-5 h-12 flex items-center font-bold text-gray-800
                          hover:text-primary-500 transition-colors
                          <?php echo strpos($current_page, 'service') !== false || strpos($current_page, 'repair') !== false ? 'text-primary-500' : ''; ?>">
                    售后中心
                </a>
            </div>

            <!-- 右侧快捷入口 -->
            <div class="ml-auto flex items-center gap-2">
                <a href="<?php echo $base; ?>core/trade_in.php"
                   class="px-4 h-9 flex items-center gap-1.5 text-sm font-medium
                          bg-amber-50 text-amber-600 rounded
                          hover:bg-amber-500 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    以旧换新
                </a>
                <a href="tel:400-888-8888"
                   class="px-4 h-9 flex items-center gap-1.5 text-sm font-medium
                          text-primary-500 border border-primary-500 rounded
                          hover:bg-primary-500 hover:text-white transition-colors">
                    📞 400-888-8888
                </a>
            </div>
        </div>
    </nav>

</div>
<!-- End Desktop Header -->

<!-- 搜索框交互脚本 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('globalSearch');
    const dropdown = document.getElementById('hotSearchDropdown');
    const container = document.getElementById('searchContainer');

    if (searchInput && dropdown) {
        // 聚焦时显示热搜
        searchInput.addEventListener('focus', function() {
            dropdown.classList.remove('hidden');
        });

        // 点击外部隐藏
        document.addEventListener('click', function(e) {
            if (!container.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // 输入时隐藏热搜
        searchInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                dropdown.classList.add('hidden');
            } else {
                dropdown.classList.remove('hidden');
            }
        });
    }
});
</script>
