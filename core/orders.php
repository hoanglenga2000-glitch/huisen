<?php
/**
 * ==========================================
 * 我的订单/询价记录 v2.0
 * PC/Mobile 双端适配版本
 * ==========================================
 */

session_start();
require_once '../config/config.php';

// 设置 Base Path
$base_path = '../';

// 检查登录状态
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// 确保订单表存在
$conn->exec("
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_no VARCHAR(32) NOT NULL UNIQUE,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        status ENUM('pending', 'confirmed', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
        address_id INT,
        remark TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_order_no (order_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$conn->exec("
    CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        sku_id INT NOT NULL,
        product_name VARCHAR(200),
        sku_name VARCHAR(200),
        price DECIMAL(10,2),
        quantity INT DEFAULT 1,
        INDEX idx_order_id (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 获取用户订单
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 状态映射
$statusMap = [
    'pending' => ['label' => '待确认', 'color' => 'yellow'],
    'confirmed' => ['label' => '已确认', 'color' => 'blue'],
    'shipped' => ['label' => '已发货', 'color' => 'purple'],
    'completed' => ['label' => '已完成', 'color' => 'green'],
    'cancelled' => ['label' => '已取消', 'color' => 'gray'],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的订单 - 汇森科技</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../dist/css/output.css">
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f5; }

        /* 订单状态标签 */
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .order-status.yellow { background: #fef3c7; color: #d97706; }
        .order-status.blue { background: #dbeafe; color: #2563eb; }
        .order-status.purple { background: #ede9fe; color: #7c3aed; }
        .order-status.green { background: #dcfce7; color: #16a34a; }
        .order-status.gray { background: #f3f4f6; color: #6b7280; }

        /* 筛选标签 */
        .filter-tab {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .filter-tab.active {
            background: #fef2f2;
            color: #e1251b;
        }
        .filter-tab:not(.active) {
            color: #6b7280;
        }
        .filter-tab:not(.active):hover {
            background: #f3f4f6;
        }
    </style>
</head>
<body class="min-h-screen pb-20 md:pb-0">
    <!-- ==========================================
         Mobile Header (仅手机显示)
         ========================================== -->
    <header class="md:hidden fixed top-0 left-0 w-full z-50 bg-white border-b border-gray-100"
            style="padding-top: env(safe-area-inset-top);">
        <div class="h-12 flex items-center px-4">
            <button onclick="history.back()" class="w-8 h-8 flex items-center justify-center -ml-2">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <h1 class="flex-1 text-center font-bold text-gray-900">我的订单</h1>
            <div class="w-8"></div>
        </div>
    </header>
    <div class="md:hidden h-12" style="padding-top: env(safe-area-inset-top);"></div>

    <!-- ==========================================
         PC Header (仅电脑显示)
         ========================================== -->
    <div class="hidden md:block">
        <?php include '../includes/header.php'; ?>
    </div>

    <main class="max-w-5xl mx-auto px-4 py-4 md:py-8">
        <!-- 面包屑 (仅PC显示) -->
        <div class="hidden md:flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="user_center.php" class="hover:text-gray-900">个人中心</a>
            <span>/</span>
            <span class="text-gray-900">我的订单</span>
        </div>

        <!-- 页面标题 (仅PC显示) -->
        <div class="hidden md:flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">我的订单</h1>
            <a href="quotes_v6.php" class="px-6 py-2 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                继续选购
            </a>
        </div>

        <!-- 订单筛选 - 横向滚动适配移动端 -->
        <div class="bg-white rounded-xl shadow-sm p-3 md:p-4 mb-4 md:mb-6">
            <div class="flex items-center gap-2 md:gap-4 overflow-x-auto pb-1 -mx-1 px-1">
                <a href="?status=" class="filter-tab <?php echo empty($_GET['status']) ? 'active' : ''; ?>">
                    全部
                </a>
                <a href="?status=pending" class="filter-tab <?php echo ($_GET['status'] ?? '') === 'pending' ? 'active' : ''; ?>">
                    待确认
                </a>
                <a href="?status=confirmed" class="filter-tab <?php echo ($_GET['status'] ?? '') === 'confirmed' ? 'active' : ''; ?>">
                    已确认
                </a>
                <a href="?status=shipped" class="filter-tab <?php echo ($_GET['status'] ?? '') === 'shipped' ? 'active' : ''; ?>">
                    已发货
                </a>
                <a href="?status=completed" class="filter-tab <?php echo ($_GET['status'] ?? '') === 'completed' ? 'active' : ''; ?>">
                    已完成
                </a>
            </div>
        </div>

        <!-- 订单列表 -->
        <?php if (empty($orders)): ?>
        <div class="bg-white rounded-2xl shadow-sm p-8 md:p-16 text-center">
            <div class="text-5xl md:text-6xl mb-4">📦</div>
            <h3 class="text-lg md:text-xl font-bold text-gray-700 mb-2">暂无订单记录</h3>
            <p class="text-gray-500 mb-6 text-sm md:text-base">您还没有提交过询价订单</p>
            <a href="quotes_v6.php" class="inline-block px-6 md:px-8 py-2.5 md:py-3 bg-primary text-white rounded-lg font-medium hover:bg-red-600 transition">
                去选购
            </a>
        </div>
        <?php else: ?>
        <div class="space-y-3 md:space-y-4">
            <?php foreach ($orders as $order):
                $status = $statusMap[$order['status']] ?? ['label' => '未知', 'color' => 'gray'];
            ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <!-- 订单头部 -->
                <div class="px-4 md:px-6 py-3 md:py-4 bg-gray-50 border-b">
                    <!-- PC: 横向布局 -->
                    <div class="hidden md:flex items-center justify-between">
                        <div class="flex items-center gap-6 text-sm">
                            <span class="text-gray-500">订单号：<span class="text-gray-900 font-mono"><?php echo $order['order_no']; ?></span></span>
                            <span class="text-gray-500">下单时间：<?php echo $order['created_at']; ?></span>
                        </div>
                        <span class="order-status <?php echo $status['color']; ?>">
                            <?php echo $status['label']; ?>
                        </span>
                    </div>
                    <!-- Mobile: 紧凑布局 -->
                    <div class="md:hidden">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-500">订单号</span>
                            <span class="order-status <?php echo $status['color']; ?>">
                                <?php echo $status['label']; ?>
                            </span>
                        </div>
                        <div class="font-mono text-sm text-gray-900"><?php echo $order['order_no']; ?></div>
                        <div class="text-xs text-gray-400 mt-1"><?php echo $order['created_at']; ?></div>
                    </div>
                </div>

                <!-- 订单内容 -->
                <div class="p-4 md:p-6">
                    <!-- PC: 横向布局 -->
                    <div class="hidden md:flex items-center justify-between">
                        <div class="text-gray-600">
                            订单金额：<span class="text-xl font-bold text-primary">¥<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">
                                查看详情
                            </a>
                            <?php if ($order['status'] === 'pending'): ?>
                            <button class="px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-red-600 transition">
                                催单
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Mobile: 垂直布局 -->
                    <div class="md:hidden">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm text-gray-500">订单金额</span>
                            <span class="text-lg font-bold text-primary">¥<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <div class="flex gap-2">
                            <a href="order_detail.php?id=<?php echo $order['id']; ?>"
                               class="flex-1 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 text-center hover:bg-gray-50 transition">
                                查看详情
                            </a>
                            <?php if ($order['status'] === 'pending'): ?>
                            <button class="flex-1 py-2 bg-primary text-white rounded-lg text-sm hover:bg-red-600 transition">
                                催单
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <!-- 页脚 -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>
