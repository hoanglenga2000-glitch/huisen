<?php
/**
 * ==========================================
 * 汇森科技 - 会员中心 v2.0
 * B2B Member Dashboard 商务工作台风格
 * ==========================================
 * Stage 6 升级：
 * 1. 左侧固定侧边菜单 + 右侧主工作区
 * 2. 顶部资产卡片 (头像+等级+数据看板)
 * 3. 订单状态流程条
 * 4. 最近订单表格
 */

session_start();
require_once '../config/config.php';

// 设置 Base Path
$base_path = '../';

// 权限分流逻辑
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php?redirect=user_center');
    exit;
}

// 如果是员工角色，跳转到后台管理
if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff') {
    header('Location: ../admin/index.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// 获取用户信息
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? '';

// 用户数据
$user = [
    'id' => $user_id,
    'username' => $username,
    'real_name' => $_SESSION['real_name'] ?? $username,
    'phone' => $_SESSION['phone'] ?? '138****8888',
    'level' => $_SESSION['user_level'] ?? 'gold',
    'balance' => 28650.00,
    'total_purchase' => 125800,
    'saved_amount' => 8650,
    'points' => 1258,
    'coupons' => 3,
    'created_at' => '2024-06-15',
];

// 等级配置
$level_config = [
    'normal' => [
        'name' => '普通会员',
        'icon' => '👤',
        'color' => 'bg-gray-500',
        'text_color' => 'text-gray-600',
    ],
    'gold' => [
        'name' => '金牌会员',
        'icon' => '👑',
        'color' => 'bg-amber-500',
        'text_color' => 'text-amber-600',
    ],
    'diamond' => [
        'name' => '钻石会员',
        'icon' => '💎',
        'color' => 'bg-blue-500',
        'text_color' => 'text-blue-600',
    ],
    'partner' => [
        'name' => '合作伙伴',
        'icon' => '🏆',
        'color' => 'bg-primary',
        'text_color' => 'text-primary',
    ],
];

$current_level = $level_config[$user['level']] ?? $level_config['normal'];

// 订单状态统计
$order_stats = [
    'pending_pay' => 2,
    'pending_ship' => 1,
    'pending_receive' => 3,
    'after_sales' => 1,
];

// 最近订单
$recent_orders = [
    ['id' => 'HS202602010001', 'product' => 'iPhone 16 Pro Max 256GB', 'image' => '', 'amount' => 13719, 'status' => 'completed', 'date' => '2026-02-01'],
    ['id' => 'HS202601280002', 'product' => '华为 Mate 70 Pro 512GB', 'image' => '', 'amount' => 7999, 'status' => 'shipping', 'date' => '2026-01-28'],
    ['id' => 'HS202601250003', 'product' => '小米15 Ultra 1TB', 'image' => '', 'amount' => 6999, 'status' => 'pending', 'date' => '2026-01-25'],
    ['id' => 'HS202601200004', 'product' => 'OPPO Find X8 Pro', 'image' => '', 'amount' => 5999, 'status' => 'pending_pay', 'date' => '2026-01-20'],
];

// 购物车/询价单数量
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}

// 当前菜单
$current_menu = $_GET['tab'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会员中心 - 汇森科技</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f5; }

        /* 侧边菜单 */
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: #4b5563;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .sidebar-menu a:hover {
            background: #fef2f2;
            color: #e1251b;
        }
        .sidebar-menu a.active {
            background: #fef2f2;
            color: #e1251b;
            border-left-color: #e1251b;
            font-weight: 600;
        }
        .sidebar-menu a .icon {
            width: 20px;
            text-align: center;
        }

        /* 订单状态流程 */
        .status-item {
            flex: 1;
            text-align: center;
            padding: 16px;
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
        }
        .status-item:hover {
            background: #fef2f2;
        }
        .status-item:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 40px;
            background: #e5e7eb;
        }
        .status-item .badge {
            position: absolute;
            top: 8px;
            right: calc(50% - 24px);
            min-width: 18px;
            height: 18px;
            background: #e1251b;
            color: white;
            font-size: 11px;
            font-weight: 600;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
        }

        /* 订单状态标签 */
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .order-status.completed { background: #dcfce7; color: #16a34a; }
        .order-status.shipping { background: #dbeafe; color: #2563eb; }
        .order-status.pending { background: #fef3c7; color: #d97706; }
        .order-status.pending_pay { background: #fee2e2; color: #dc2626; }

        /* 数据卡片 */
        .data-card {
            text-align: center;
            padding: 0 24px;
            border-right: 1px solid #e5e7eb;
        }
        .data-card:last-child {
            border-right: none;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="max-w-screen-xl mx-auto px-4 py-4 md:py-8 pb-24 md:pb-8">
        <!-- ==========================================
             响应式布局容器
             Mobile: 单列堆叠
             PC: 4列网格 (1:3 比例)
             ========================================== -->
        <div class="block md:grid md:grid-cols-4 md:gap-6">

            <!-- ==========================================
                 左侧：Sidebar 侧边菜单 (仅PC显示)
                 ========================================== -->
            <div class="hidden md:block md:col-span-1">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-20">
                    <!-- 用户头像区 -->
                    <div class="p-6 bg-gradient-to-r from-primary to-red-500 text-white">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center text-2xl">
                                <?php echo $current_level['icon']; ?>
                            </div>
                            <div>
                                <div class="font-bold text-lg"><?php echo htmlspecialchars($user['real_name']); ?></div>
                                <div class="text-sm opacity-90"><?php echo $current_level['name']; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- 菜单列表 -->
                    <nav class="sidebar-menu py-2">
                        <a href="user_center.php" class="<?php echo ($current_menu === 'overview' || !isset($_GET['tab'])) ? 'active' : ''; ?>">
                            <span class="icon">🏠</span>
                            <span>会员中心</span>
                        </a>
                        <a href="?tab=overview" class="<?php echo $current_menu === 'overview' ? 'active' : ''; ?>">
                            <span class="icon">📊</span>
                            <span>账户总览</span>
                        </a>
                        <a href="orders.php" class="<?php echo $current_menu === 'orders' ? 'active' : ''; ?>">
                            <span class="icon">📦</span>
                            <span>我的订单</span>
                            <?php if ($order_stats['pending_ship'] > 0): ?>
                            <span class="ml-auto text-xs bg-red-500 text-white px-2 py-0.5 rounded-full"><?php echo $order_stats['pending_ship']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="cart.php" class="<?php echo $current_menu === 'cart' ? 'active' : ''; ?>">
                            <span class="icon">🛒</span>
                            <span>我的询价单</span>
                            <?php if ($cart_count > 0): ?>
                            <span class="ml-auto text-xs bg-red-500 text-white px-2 py-0.5 rounded-full"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="quotes_v6.php" class="<?php echo $current_menu === 'quotes' ? 'active' : ''; ?>">
                            <span class="icon">📋</span>
                            <span>我的询价</span>
                        </a>
                        <a href="#" class="<?php echo $current_menu === 'finance' ? 'active' : ''; ?>">
                            <span class="icon">💰</span>
                            <span>资金管理</span>
                        </a>
                        <a href="addresses.php" class="<?php echo $current_menu === 'address' ? 'active' : ''; ?>">
                            <span class="icon">📍</span>
                            <span>收货地址</span>
                        </a>
                        <a href="after_sales.php" class="<?php echo $current_menu === 'aftersales' ? 'active' : ''; ?>">
                            <span class="icon">🔧</span>
                            <span>售后服务</span>
                        </a>
                        <a href="change_password.php" class="<?php echo $current_menu === 'security' ? 'active' : ''; ?>">
                            <span class="icon">🔒</span>
                            <span>账号安全</span>
                        </a>
                    </nav>

                    <!-- 退出登录 -->
                    <div class="border-t p-4">
                        <a href="../api/auth.php?action=logout" class="flex items-center gap-2 text-gray-500 hover:text-red-600 text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            退出登录
                        </a>
                    </div>
                </div>
            </div>

            <!-- ==========================================
                 右侧：主工作区
                 Mobile: 全宽
                 PC: 3列宽度
                 ========================================== -->
            <div class="w-full md:col-span-3">
                <!-- User Info Card: 顶部资产卡 -->
                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-4 md:mb-6">
                    <!-- Mobile: 紧凑头部 -->
                    <div class="md:hidden">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-primary to-red-400 rounded-full flex items-center justify-center text-white text-2xl shadow-lg">
                                <?php echo $current_level['icon']; ?>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h2 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($user['real_name']); ?></h2>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $current_level['color']; ?> text-white">
                                        <?php echo $current_level['name']; ?>
                                    </span>
                                </div>
                                <p class="text-gray-400 text-xs">累计采购 ¥<?php echo number_format($user['total_purchase']); ?></p>
                            </div>
                        </div>
                        <!-- 数据看板 (3列) -->
                        <div class="grid grid-cols-3 gap-2 bg-gray-50 rounded-lg p-3">
                            <div class="text-center">
                                <div class="text-lg font-bold text-primary">¥<?php echo number_format($user['balance'], 0); ?></div>
                                <div class="text-[10px] text-gray-400">余额</div>
                            </div>
                            <div class="text-center border-x border-gray-200">
                                <div class="text-lg font-bold text-gray-900"><?php echo number_format($user['points']); ?></div>
                                <div class="text-[10px] text-gray-400">积分</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-orange-500"><?php echo $user['coupons']; ?></div>
                                <div class="text-[10px] text-gray-400">优惠券</div>
                            </div>
                        </div>
                    </div>

                    <!-- PC: 原有布局 -->
                    <div class="hidden md:flex items-center justify-between">
                        <!-- Left: 欢迎语 -->
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-primary to-red-400 rounded-full flex items-center justify-center text-white text-3xl shadow-lg">
                                <?php echo $current_level['icon']; ?>
                            </div>
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <h2 class="text-xl font-bold text-gray-900">欢迎回来，<?php echo htmlspecialchars($user['real_name']); ?></h2>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $current_level['color']; ?> text-white">
                                        <?php echo $current_level['name']; ?>
                                    </span>
                                </div>
                                <p class="text-gray-500 text-sm">累计采购 ¥<?php echo number_format($user['total_purchase']); ?>，已为您节省 ¥<?php echo number_format($user['saved_amount']); ?></p>
                            </div>
                        </div>

                        <!-- Right: 数据看板 -->
                        <div class="flex items-center">
                            <div class="data-card">
                                <div class="text-3xl font-bold text-primary font-mono">¥<?php echo number_format($user['balance'], 2); ?></div>
                                <div class="text-sm text-gray-500 mt-1">账户余额</div>
                            </div>
                            <div class="data-card">
                                <div class="text-3xl font-bold text-gray-900 font-mono"><?php echo number_format($user['points']); ?></div>
                                <div class="text-sm text-gray-500 mt-1">可用积分</div>
                            </div>
                            <div class="data-card">
                                <div class="text-3xl font-bold text-orange-500 font-mono"><?php echo $user['coupons']; ?></div>
                                <div class="text-sm text-gray-500 mt-1">优惠券</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Status Bar: 订单状态流程 -->
                <div class="bg-white rounded-lg shadow-sm mb-4 md:mb-6">
                    <div class="flex">
                        <a href="orders.php?status=pending_pay" class="status-item">
                            <?php if ($order_stats['pending_pay'] > 0): ?>
                            <span class="badge"><?php echo $order_stats['pending_pay']; ?></span>
                            <?php endif; ?>
                            <div class="w-10 h-10 md:w-12 md:h-12 mx-auto mb-1 md:mb-2 bg-red-50 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 md:w-6 md:h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <div class="text-xs md:text-sm font-medium text-gray-700">待付款</div>
                        </a>
                        <a href="orders.php?status=pending_ship" class="status-item">
                            <?php if ($order_stats['pending_ship'] > 0): ?>
                            <span class="badge"><?php echo $order_stats['pending_ship']; ?></span>
                            <?php endif; ?>
                            <div class="w-10 h-10 md:w-12 md:h-12 mx-auto mb-1 md:mb-2 bg-orange-50 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 md:w-6 md:h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                            </div>
                            <div class="text-xs md:text-sm font-medium text-gray-700">待发货</div>
                        </a>
                        <a href="orders.php?status=shipping" class="status-item">
                            <?php if ($order_stats['pending_receive'] > 0): ?>
                            <span class="badge"><?php echo $order_stats['pending_receive']; ?></span>
                            <?php endif; ?>
                            <div class="w-10 h-10 md:w-12 md:h-12 mx-auto mb-1 md:mb-2 bg-blue-50 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 md:w-6 md:h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                </svg>
                            </div>
                            <div class="text-xs md:text-sm font-medium text-gray-700">待收货</div>
                        </a>
                        <a href="after_sales.php" class="status-item">
                            <?php if ($order_stats['after_sales'] > 0): ?>
                            <span class="badge"><?php echo $order_stats['after_sales']; ?></span>
                            <?php endif; ?>
                            <div class="w-10 h-10 md:w-12 md:h-12 mx-auto mb-1 md:mb-2 bg-purple-50 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 md:w-6 md:h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="text-xs md:text-sm font-medium text-gray-700">售后中</div>
                        </a>
                    </div>
                </div>

                <!-- ==========================================
                     Mobile Function Grid (仅手机显示)
                     替代PC端的Sidebar菜单
                     ========================================== -->
                <div class="md:hidden grid grid-cols-4 gap-3 bg-white p-4 rounded-lg shadow-sm mb-4">
                    <a href="cart.php" class="flex flex-col items-center gap-1.5">
                        <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center text-lg">🛒</div>
                        <span class="text-[11px] text-gray-600">我的询价</span>
                    </a>
                    <a href="addresses.php" class="flex flex-col items-center gap-1.5">
                        <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-lg">📍</div>
                        <span class="text-[11px] text-gray-600">收货地址</span>
                    </a>
                    <a href="#" class="flex flex-col items-center gap-1.5">
                        <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center text-lg">💰</div>
                        <span class="text-[11px] text-gray-600">资金管理</span>
                    </a>
                    <a href="change_password.php" class="flex flex-col items-center gap-1.5">
                        <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center text-lg">🔒</div>
                        <span class="text-[11px] text-gray-600">账号安全</span>
                    </a>
                </div>

                <!-- Recent Orders: 最近订单 -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="flex items-center justify-between p-6 border-b">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <span>📦</span> 最近订单
                        </h3>
                        <a href="orders.php" class="text-sm text-primary hover:underline">查看全部 ></a>
                    </div>

                    <?php if (empty($recent_orders)): ?>
                    <div class="text-center py-16">
                        <div class="text-6xl mb-4">📭</div>
                        <p class="text-gray-500 mb-4">暂无订单记录</p>
                        <a href="quotes_v6.php" class="inline-block px-6 py-2 bg-primary text-white rounded-lg hover:bg-red-600 transition">去选购</a>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">订单号</th>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">商品信息</th>
                                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600">总金额</th>
                                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">状态</th>
                                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach ($recent_orders as $order): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-mono text-sm text-gray-900"><?php echo $order['id']; ?></div>
                                        <div class="text-xs text-gray-400 mt-1"><?php echo $order['date']; ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-xl">
                                                📱
                                            </div>
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($order['product']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-bold text-primary font-mono text-lg">¥<?php echo number_format($order['amount']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="order-status <?php echo $order['status']; ?>">
                                            <?php
                                            echo match($order['status']) {
                                                'completed' => '已完成',
                                                'shipping' => '配送中',
                                                'pending' => '待发货',
                                                'pending_pay' => '待付款',
                                                default => $order['status']
                                            };
                                            ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="#" class="text-primary text-sm hover:underline">详情</a>
                                            <?php if ($order['status'] === 'pending_pay'): ?>
                                            <a href="#" class="px-3 py-1 bg-primary text-white text-sm rounded hover:bg-red-600 transition">付款</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 快捷功能入口 -->
                <div class="grid grid-cols-4 gap-4 mt-6">
                    <a href="quotes_v6.php" class="bg-white rounded-lg p-5 shadow-sm hover:shadow-md transition text-center group">
                        <div class="w-12 h-12 mx-auto mb-3 bg-red-50 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition">
                            📋
                        </div>
                        <div class="font-medium text-gray-700">我的询价单</div>
                    </a>
                    <a href="service.php" class="bg-white rounded-lg p-5 shadow-sm hover:shadow-md transition text-center group">
                        <div class="w-12 h-12 mx-auto mb-3 bg-blue-50 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition">
                            💬
                        </div>
                        <div class="font-medium text-gray-700">联系客服</div>
                    </a>
                    <a href="#" class="bg-white rounded-lg p-5 shadow-sm hover:shadow-md transition text-center group">
                        <div class="w-12 h-12 mx-auto mb-3 bg-orange-50 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition">
                            🎁
                        </div>
                        <div class="font-medium text-gray-700">积分商城</div>
                    </a>
                    <a href="cooperation.php" class="bg-white rounded-lg p-5 shadow-sm hover:shadow-md transition text-center group">
                        <div class="w-12 h-12 mx-auto mb-3 bg-green-50 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition">
                            🤝
                        </div>
                        <div class="font-medium text-gray-700">批发合作</div>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/sidebar-tools.php'; ?>
    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>
