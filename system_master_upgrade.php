<?php
/**
 * ==========================================
 * 系统主升级脚本 - System Master Upgrade
 * ==========================================
 *
 * 功能：
 * 1. 一键清洗数据库（修正分类）
 * 2. 执行V6图片匹配逻辑
 * 3. 更新数据库图片链接
 * 4. 生成升级报告
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/tools/image_engine_v6.php';

class SystemMasterUpgrade {
    private $db;
    private $conn;
    private $log = [];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * 执行完整升级
     */
    public function execute() {
        $this->logMessage("===========================================");
        $this->logMessage("系统主升级脚本 - 开始执行");
        $this->logMessage("===========================================\n");

        $startTime = microtime(true);

        // 步骤1: 清洗数据库分类
        $this->logMessage("📋 步骤1: 清洗数据库分类...");
        $categoryResult = $this->cleanCategories();
        $this->logMessage("✅ 完成! 修正了 {$categoryResult['updated']} 条记录\n");

        // 步骤2: 清理无效价格
        $this->logMessage("💰 步骤2: 清理无效价格...");
        $priceResult = $this->cleanPrices();
        $this->logMessage("✅ 完成! 修正了 {$priceResult['updated']} 条记录\n");

        // 步骤3: 执行图片匹配
        $this->logMessage("🖼️  步骤3: 执行图片匹配引擎 V6...");
        $imageEngine = new ImageEngineV6();
        $imageResult = $imageEngine->matchAllImages();
        $this->logMessage("✅ 完成! 匹配了 {$imageResult['matched']} 张图片\n");

        // 步骤4: 更新统计数据
        $this->logMessage("📊 步骤4: 更新统计数据...");
        $statsResult = $this->updateStats();
        $this->logMessage("✅ 完成!\n");

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->logMessage("===========================================");
        $this->logMessage("升级完成！");
        $this->logMessage("===========================================");
        $this->logMessage("总耗时: {$duration} 秒");
        $this->logMessage("分类修正: {$categoryResult['updated']} 条");
        $this->logMessage("价格修正: {$priceResult['updated']} 条");
        $this->logMessage("图片匹配: {$imageResult['matched']} 张");
        $this->logMessage("===========================================");

        return [
            'success' => true,
            'duration' => $duration,
            'category' => $categoryResult,
            'price' => $priceResult,
            'image' => $imageResult,
            'stats' => $statsResult,
            'log' => $this->log
        ];
    }

    /**
     * 清洗分类数据
     */
    private function cleanCategories() {
        $updated = 0;

        // 规则1: 型号包含 "Watch", "Band" 的归类为 watch
        $stmt = $this->conn->prepare("
            UPDATE products_spu_v3
            SET category = 'watch'
            WHERE category != 'watch'
            AND (model_name LIKE '%Watch%' OR model_name LIKE '%Band%' OR model_name LIKE '%手表%' OR model_name LIKE '%手环%')
        ");
        $stmt->execute();
        $updated += $stmt->rowCount();

        // 规则2: 型号包含 "Pad", "Tab" 的归类为 tablet
        $stmt = $this->conn->prepare("
            UPDATE products_spu_v3
            SET category = 'tablet'
            WHERE category != 'tablet'
            AND (model_name LIKE '%Pad%' OR model_name LIKE '%Tab%' OR model_name LIKE '%平板%')
        ");
        $stmt->execute();
        $updated += $stmt->rowCount();

        // 规则3: 型号包含 "AirPods", "耳机", "充电" 的归类为 accessory
        $stmt = $this->conn->prepare("
            UPDATE products_spu_v3
            SET category = 'accessory'
            WHERE category != 'accessory'
            AND (model_name LIKE '%AirPods%' OR model_name LIKE '%耳机%' OR model_name LIKE '%充电%' OR model_name LIKE '%保护%')
        ");
        $stmt->execute();
        $updated += $stmt->rowCount();

        // 规则4: 其他未分类的默认为 phone
        $stmt = $this->conn->prepare("
            UPDATE products_spu_v3
            SET category = 'phone'
            WHERE category IS NULL OR category = '' OR category = 'unknown'
        ");
        $stmt->execute();
        $updated += $stmt->rowCount();

        return ['updated' => $updated];
    }

    /**
     * 清理无效价格
     */
    private function cleanPrices() {
        $updated = 0;

        // 修正 min_price = 0 或 NULL 的记录
        $stmt = $this->conn->prepare("
            UPDATE products_spu_v3 s
            SET s.min_price = (
                SELECT MIN(price) FROM products_sku_v3 WHERE spu_id = s.id AND price > 0
            ),
            s.max_price = (
                SELECT MAX(price) FROM products_sku_v3 WHERE spu_id = s.id AND price > 0
            )
            WHERE s.min_price = 0 OR s.min_price IS NULL
        ");
        $stmt->execute();
        $updated += $stmt->rowCount();

        return ['updated' => $updated];
    }

    /**
     * 更新统计数据
     */
    private function updateStats() {
        // 获取各分类数量
        $stats = [];

        $stmt = $this->conn->query("
            SELECT category, COUNT(*) as cnt
            FROM products_spu_v3
            WHERE min_price > 0
            GROUP BY category
        ");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['category']] = $row['cnt'];
        }

        return $stats;
    }

    /**
     * 记录日志
     */
    private function logMessage($message) {
        $this->log[] = $message;
        echo $message . "\n";
    }

    /**
     * 生成HTML报告
     */
    public function generateReport($result) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>系统升级报告 - 汇森科技</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-50 p-8">
            <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-xl p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">系统升级报告</h1>
                <p class="text-gray-500 mb-8">执行时间: <?php echo date('Y-m-d H:i:s'); ?></p>

                <!-- 总览 -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-blue-50 rounded-xl p-6 text-center">
                        <div class="text-3xl font-bold text-blue-600 mb-2"><?php echo $result['category']['updated']; ?></div>
                        <div class="text-sm text-gray-600">分类修正</div>
                    </div>

                    <div class="bg-green-50 rounded-xl p-6 text-center">
                        <div class="text-3xl font-bold text-green-600 mb-2"><?php echo $result['price']['updated']; ?></div>
                        <div class="text-sm text-gray-600">价格修正</div>
                    </div>

                    <div class="bg-purple-50 rounded-xl p-6 text-center">
                        <div class="text-3xl font-bold text-purple-600 mb-2"><?php echo $result['image']['matched']; ?></div>
                        <div class="text-sm text-gray-600">图片匹配</div>
                    </div>

                    <div class="bg-orange-50 rounded-xl p-6 text-center">
                        <div class="text-3xl font-bold text-orange-600 mb-2"><?php echo $result['duration']; ?>s</div>
                        <div class="text-sm text-gray-600">总耗时</div>
                    </div>
                </div>

                <!-- 分类统计 -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">分类统计</h2>
                    <div class="bg-gray-50 rounded-xl p-6">
                        <ul class="space-y-2">
                            <?php foreach ($result['stats'] as $category => $count): ?>
                            <li class="flex justify-between items-center">
                                <span class="text-gray-700">
                                    <?php
                                    $categoryNames = [
                                        'phone' => '📱 手机',
                                        'watch' => '⌚ 智能穿戴',
                                        'tablet' => '📱 平板',
                                        'accessory' => '🎧 配件'
                                    ];
                                    echo $categoryNames[$category] ?? $category;
                                    ?>
                                </span>
                                <span class="font-bold text-gray-900"><?php echo $count; ?> 款</span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- 执行日志 -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">执行日志</h2>
                    <div class="bg-gray-900 text-green-400 rounded-xl p-6 font-mono text-sm overflow-auto max-h-96">
                        <?php foreach ($result['log'] as $line): ?>
                        <div><?php echo htmlspecialchars($line); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 操作按钮 -->
                <div class="flex gap-4">
                    <a href="/core/index_v4.php" class="flex-1 py-3 bg-red-600 text-white text-center rounded-lg font-medium hover:bg-red-700 transition">
                        返回首页
                    </a>
                    <a href="/core/quotes_final.php" class="flex-1 py-3 bg-blue-600 text-white text-center rounded-lg font-medium hover:bg-blue-700 transition">
                        查看产品列表
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}

// 如果直接运行此脚本
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF']) === 'system_master_upgrade.php') {
    $upgrade = new SystemMasterUpgrade();
    $result = $upgrade->execute();

    // 如果是网页访问，显示HTML报告
    if (php_sapi_name() !== 'cli') {
        echo $upgrade->generateReport($result);
    }
}
