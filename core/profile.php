<?php
/**
 * ==========================================
 * 用户中心 - 淘宝风格个人主页
 * ==========================================
 */

session_start();
require_once '../config/config.php';

// 检查登录状态
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// 获取用户信息
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 获取用户地址数量
$addrStmt = $conn->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
$addrStmt->execute([$userId]);
$addressCount = $addrStmt->fetchColumn();

// 获取询价单数量（从session）
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人中心 - 汇森科技</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand-red: #e1251b; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Noto Sans SC', sans-serif; }

        .profile-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .menu-item {
            transition: all 0.2s ease;
        }
        .menu-item:hover {
            background: #fef2f2;
            color: var(--brand-red);
        }

        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- 顶部导航 -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="index_v4.php" class="flex items-center gap-2">
                    <span class="text-2xl font-bold" style="color: var(--brand-red);">汇森科技</span>
                </a>
                <nav class="flex items-center gap-6 text-sm">
                    <a href="index_v4.php" class="text-gray-600 hover:text-gray-900 transition">首页</a>
                    <a href="quotes_v6.php" class="text-gray-600 hover:text-gray-900 transition">手机报价</a>
                    <a href="cart.php" class="text-gray-600 hover:text-gray-900 transition">询价单</a>
                    <a href="profile.php" class="font-medium" style="color: var(--brand-red);">个人中心</a>
                    <a href="javascript:void(0)" onclick="logout()" class="text-gray-600 hover:text-red-600 transition">退出</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid lg:grid-cols-4 gap-6">
            <!-- 左侧个人信息卡片 -->
            <div class="lg:col-span-1">
                <!-- 头像和基本信息 -->
                <div class="profile-card rounded-2xl p-6 text-white mb-6">
                    <div class="flex flex-col items-center">
                        <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center text-3xl font-bold mb-4">
                            <?php echo mb_substr($user['real_name'] ?? $user['username'], 0, 1); ?>
                        </div>
                        <h2 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($user['real_name'] ?? $user['username']); ?></h2>
                        <p class="text-white/80 text-sm">普通会员</p>
                        <div class="mt-4 px-4 py-2 bg-white/20 rounded-full text-sm">
                            ID: <?php echo $user['id']; ?>
                        </div>
                    </div>
                </div>

                <!-- 功能菜单 -->
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-4 border-b font-bold text-gray-800">账户管理</div>
                    <nav>
                        <a href="profile.php" class="menu-item flex items-center gap-3 px-4 py-3 border-b">
                            <span class="text-lg">👤</span>
                            <span>个人资料</span>
                        </a>
                        <a href="addresses.php" class="menu-item flex items-center gap-3 px-4 py-3 border-b">
                            <span class="text-lg">📍</span>
                            <span>收货地址</span>
                            <span class="ml-auto text-xs bg-gray-100 px-2 py-1 rounded"><?php echo $addressCount; ?></span>
                        </a>
                        <a href="change_password.php" class="menu-item flex items-center gap-3 px-4 py-3 border-b">
                            <span class="text-lg">🔐</span>
                            <span>修改密码</span>
                        </a>
                        <a href="cart.php" class="menu-item flex items-center gap-3 px-4 py-3 border-b">
                            <span class="text-lg">📋</span>
                            <span>我的询价单</span>
                            <?php if ($cartCount > 0): ?>
                            <span class="ml-auto text-xs bg-red-500 text-white px-2 py-1 rounded"><?php echo $cartCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="orders.php" class="menu-item flex items-center gap-3 px-4 py-3 border-b">
                            <span class="text-lg">📦</span>
                            <span>我的订单</span>
                        </a>
                        <a href="user_center.php" class="menu-item flex items-center gap-3 px-4 py-3">
                            <span class="text-lg">🏠</span>
                            <span>会员中心</span>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- 右侧主内容区 -->
            <div class="lg:col-span-3 space-y-6">
                <!-- 欢迎卡片 -->
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        欢迎回来，<?php echo htmlspecialchars($user['real_name'] ?? $user['username']); ?>！
                    </h1>
                    <p class="text-gray-500">管理您的账户信息和订单</p>
                </div>

                <!-- 统计卡片 -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="cart.php" class="stat-card bg-white rounded-xl p-5 text-center cursor-pointer">
                        <div class="text-3xl font-bold text-red-500 mb-2"><?php echo $cartCount; ?></div>
                        <div class="text-gray-500 text-sm">询价单商品</div>
                    </a>
                    <a href="addresses.php" class="stat-card bg-white rounded-xl p-5 text-center cursor-pointer">
                        <div class="text-3xl font-bold text-blue-500 mb-2"><?php echo $addressCount; ?></div>
                        <div class="text-gray-500 text-sm">收货地址</div>
                    </a>
                    <a href="orders.php" class="stat-card bg-white rounded-xl p-5 text-center cursor-pointer">
                        <div class="text-3xl font-bold text-green-500 mb-2">0</div>
                        <div class="text-gray-500 text-sm">历史订单</div>
                    </a>
                    <a href="user_center.php" class="stat-card bg-white rounded-xl p-5 text-center cursor-pointer">
                        <div class="text-3xl font-bold text-purple-500 mb-2">VIP</div>
                        <div class="text-gray-500 text-sm">会员等级</div>
                    </a>
                </div>

                <!-- 个人资料编辑 -->
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <span>👤</span> 个人资料
                    </h3>

                    <form id="profileForm" class="space-y-5">
                        <div class="grid md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">用户名</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>"
                                       class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg text-gray-500 cursor-not-allowed"
                                       disabled>
                                <p class="text-xs text-gray-400 mt-1">用户名不可修改</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">真实姓名</label>
                                <input type="text" id="realName" name="real_name"
                                       value="<?php echo htmlspecialchars($user['real_name'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500 transition"
                                       placeholder="请输入真实姓名">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">手机号</label>
                                <input type="tel" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500 transition"
                                       placeholder="请输入手机号">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">邮箱</label>
                                <input type="email" id="email" name="email"
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500 transition"
                                       placeholder="请输入邮箱地址">
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-4">
                            <button type="submit" class="px-8 py-3 text-white rounded-lg font-medium hover:opacity-90 transition"
                                    style="background: var(--brand-red);">
                                保存修改
                            </button>
                            <span id="saveMessage" class="text-green-600 text-sm hidden">✓ 保存成功</span>
                        </div>
                    </form>
                </div>

                <!-- 快捷操作 -->
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">快捷操作</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="quotes_v6.php" class="flex flex-col items-center gap-2 p-4 rounded-xl hover:bg-gray-50 transition">
                            <span class="text-3xl">📱</span>
                            <span class="text-sm text-gray-600">浏览商品</span>
                        </a>
                        <a href="cart.php" class="flex flex-col items-center gap-2 p-4 rounded-xl hover:bg-gray-50 transition">
                            <span class="text-3xl">📋</span>
                            <span class="text-sm text-gray-600">查看询价单</span>
                        </a>
                        <a href="addresses.php" class="flex flex-col items-center gap-2 p-4 rounded-xl hover:bg-gray-50 transition">
                            <span class="text-3xl">📍</span>
                            <span class="text-sm text-gray-600">管理地址</span>
                        </a>
                        <a href="service.php" class="flex flex-col items-center gap-2 p-4 rounded-xl hover:bg-gray-50 transition">
                            <span class="text-3xl">💬</span>
                            <span class="text-sm text-gray-600">联系客服</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 页脚 -->
    <footer class="bg-gray-900 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="text-center text-sm text-gray-500">
                © 2026 汇森科技 版权所有 | 专业手机批发平台
            </div>
        </div>
    </footer>

    <script>
    // 保存个人资料
    document.getElementById('profileForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = {
            real_name: document.getElementById('realName').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            email: document.getElementById('email').value.trim()
        };

        try {
            const response = await fetch('../api/auth.php?action=update_profile', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                const msg = document.getElementById('saveMessage');
                msg.classList.remove('hidden');
                setTimeout(() => msg.classList.add('hidden'), 3000);
            } else {
                alert(result.error || '保存失败');
            }
        } catch (error) {
            alert('网络错误，请稍后重试');
        }
    });

    // 退出登录
    async function logout() {
        if (!confirm('确定要退出登录吗？')) return;

        try {
            await fetch('../api/auth.php?action=logout');
            window.location.href = 'login.php';
        } catch (error) {
            window.location.href = 'login.php';
        }
    }
    </script>
</body>
</html>
