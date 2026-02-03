<?php
/**
 * 网站访问测试页面
 * 用于检查所有路径和配置是否正确
 */

require_once 'config/config.php';

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$projectPath = dirname($_SERVER['SCRIPT_NAME']);

// 检查服务状态
$apacheRunning = true; // 假设运行中
$mysqlRunning = false;
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $conn->query("SELECT 1");
    $mysqlRunning = true;
} catch (Exception $e) {
    $mysqlError = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站访问测试 - 汇森科技</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --brand-red: #e1251b; }
    </style>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6" style="color: var(--brand-red);">网站访问测试</h1>
        
        <!-- 服务状态 -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">服务状态</h2>
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full <?php echo $apacheRunning ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                    <span>Apache 服务：<?php echo $apacheRunning ? '✅ 运行中' : '❌ 未运行'; ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full <?php echo $mysqlRunning ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                    <span>MySQL 服务：<?php echo $mysqlRunning ? '✅ 运行中' : '❌ 未运行'; ?></span>
                    <?php if (!$mysqlRunning): ?>
                    <span class="text-red-600 text-sm"><?php echo $mysqlError ?? ''; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 访问链接 -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">快速访问链接</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="index.php" class="p-4 border rounded-lg hover:bg-gray-50 transition">
                    <div class="font-semibold">官网首页</div>
                    <div class="text-sm text-gray-500">index.php</div>
                </a>
                <a href="core/index_v4.php" class="p-4 border rounded-lg hover:bg-gray-50 transition">
                    <div class="font-semibold">手机首页</div>
                    <div class="text-sm text-gray-500">core/index_v4.php</div>
                </a>
                <a href="core/quotes_v6.php" class="p-4 border rounded-lg hover:bg-gray-50 transition">
                    <div class="font-semibold">产品列表</div>
                    <div class="text-sm text-gray-500">core/quotes_v6.php</div>
                </a>
                <a href="login.php" class="p-4 border rounded-lg hover:bg-gray-50 transition">
                    <div class="font-semibold">登录页面</div>
                    <div class="text-sm text-gray-500">login.php</div>
                </a>
                <a href="core/login.php" class="p-4 border rounded-lg hover:bg-gray-50 transition">
                    <div class="font-semibold">员工登录</div>
                    <div class="text-sm text-gray-500">core/login.php</div>
                </a>
                <a href="core/cart.php" class="p-4 border rounded-lg hover:bg-gray-50 transition">
                    <div class="font-semibold">询价单</div>
                    <div class="text-sm text-gray-500">core/cart.php</div>
                </a>
            </div>
        </div>

        <!-- 当前访问信息 -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">当前访问信息</h2>
            <div class="space-y-2 text-sm">
                <div><strong>当前URL：</strong><?php echo $baseUrl . $_SERVER['REQUEST_URI']; ?></div>
                <div><strong>项目路径：</strong><?php echo $projectPath; ?></div>
                <div><strong>服务器：</strong><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                <div><strong>PHP版本：</strong><?php echo PHP_VERSION; ?></div>
            </div>
        </div>

        <!-- 配置说明 -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4 text-blue-900">访问说明</h2>
            <div class="space-y-2 text-sm text-blue-800">
                <p><strong>如果使用 huisen666 无法访问：</strong></p>
                <ol class="list-decimal list-inside space-y-1 ml-4">
                    <li>使用 <code class="bg-blue-100 px-1 rounded">http://localhost/huisen/</code> 访问</li>
                    <li>或配置 hosts 文件（详见 docs/网站访问配置指南.md）</li>
                </ol>
                <p class="mt-4"><strong>推荐访问方式：</strong></p>
                <ul class="list-disc list-inside space-y-1 ml-4">
                    <li><code>http://localhost/huisen/index.php</code> - 官网首页</li>
                    <li><code>http://localhost/huisen/core/index_v4.php</code> - 手机首页</li>
                    <li><code>http://localhost/huisen/core/quotes_v6.php</code> - 产品列表</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
