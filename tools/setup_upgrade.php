<?php
/**
 * ============================================
 * 甘肃汇森信息科技有限公司 - 数据库升级工具
 * ============================================
 *
 * 功能说明：
 * 1. 一键升级数据库表结构
 * 2. 添加图片路径、维修价格、标签等字段
 * 3. 自动添加示例数据
 * 4. 使用锁定文件防止重复升级
 *
 * 使用方法：
 * 直接访问此页面，点击"开始升级"按钮即可
 */

// 引入配置文件
require_once __DIR__ . '/config/config.php';

// 锁定文件路径
$lockFile = __DIR__ . '/upgrade.lock';

// 处理升级请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upgrade') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        // 检查是否已经升级过
        if (file_exists($lockFile)) {
            echo json_encode([
                'success' => false,
                'message' => '数据库已经升级过了！如需重新升级，请先删除 upgrade.lock 文件'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 获取数据库连接
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // 开始事务
        $conn->beginTransaction();

        $results = [];

        // ============================================
        // 步骤1: 添加 image_path 字段
        // ============================================
        try {
            // 先检查字段是否存在
            $checkSql = "SELECT COUNT(*) as cnt FROM information_schema.columns 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'mobile_quotes' 
                        AND column_name = 'image_path'";
            $checkResult = $conn->query($checkSql)->fetch(PDO::FETCH_ASSOC);
            
            if ($checkResult['cnt'] == 0) {
                $sql = "ALTER TABLE mobile_quotes ADD COLUMN image_path VARCHAR(255) DEFAULT NULL COMMENT '产品图片路径' AFTER note";
                $conn->exec($sql);
                $results[] = ['step' => '添加 image_path 字段', 'status' => 'success', 'message' => '成功'];
            } else {
                $results[] = ['step' => '添加 image_path 字段', 'status' => 'skip', 'message' => '字段已存在，跳过'];
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Duplicate column name') !== false || strpos($errorMsg, 'already exists') !== false) {
                $results[] = ['step' => '添加 image_path 字段', 'status' => 'skip', 'message' => '字段已存在，跳过'];
            } else {
                $results[] = ['step' => '添加 image_path 字段', 'status' => 'error', 'message' => '错误: ' . $errorMsg];
                // 不抛出异常，继续执行其他步骤
            }
        }

        // ============================================
        // 步骤2: 添加 repair_price 字段
        // ============================================
        try {
            $checkSql = "SELECT COUNT(*) as cnt FROM information_schema.columns 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'mobile_quotes' 
                        AND column_name = 'repair_price'";
            $checkResult = $conn->query($checkSql)->fetch(PDO::FETCH_ASSOC);
            
            if ($checkResult['cnt'] == 0) {
                $sql = "ALTER TABLE mobile_quotes ADD COLUMN repair_price TEXT DEFAULT NULL COMMENT '维修参考价(JSON格式)' AFTER image_path";
                $conn->exec($sql);
                $results[] = ['step' => '添加 repair_price 字段', 'status' => 'success', 'message' => '成功'];
            } else {
                $results[] = ['step' => '添加 repair_price 字段', 'status' => 'skip', 'message' => '字段已存在，跳过'];
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Duplicate column name') !== false || strpos($errorMsg, 'already exists') !== false) {
                $results[] = ['step' => '添加 repair_price 字段', 'status' => 'skip', 'message' => '字段已存在，跳过'];
            } else {
                $results[] = ['step' => '添加 repair_price 字段', 'status' => 'error', 'message' => '错误: ' . $errorMsg];
            }
        }

        // ============================================
        // 步骤3: 添加 tags 字段
        // ============================================
        try {
            $checkSql = "SELECT COUNT(*) as cnt FROM information_schema.columns 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'mobile_quotes' 
                        AND column_name = 'tags'";
            $checkResult = $conn->query($checkSql)->fetch(PDO::FETCH_ASSOC);
            
            if ($checkResult['cnt'] == 0) {
                $sql = "ALTER TABLE mobile_quotes ADD COLUMN tags VARCHAR(100) DEFAULT NULL COMMENT '产品标签，多个用逗号分隔' AFTER repair_price";
                $conn->exec($sql);
                $results[] = ['step' => '添加 tags 字段', 'status' => 'success', 'message' => '成功'];
            } else {
                $results[] = ['step' => '添加 tags 字段', 'status' => 'skip', 'message' => '字段已存在，跳过'];
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Duplicate column name') !== false || strpos($errorMsg, 'already exists') !== false) {
                $results[] = ['step' => '添加 tags 字段', 'status' => 'skip', 'message' => '字段已存在，跳过'];
            } else {
                $results[] = ['step' => '添加 tags 字段', 'status' => 'error', 'message' => '错误: ' . $errorMsg];
            }
        }

        // ============================================
        // 步骤4: 添加 stock_status 字段
        // ============================================
        try {
            $checkSql = "SELECT COUNT(*) as cnt FROM information_schema.columns 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'mobile_quotes' 
                        AND column_name = 'stock_status'";
            $checkResult = $conn->query($checkSql)->fetch(PDO::FETCH_ASSOC);
            
            if ($checkResult['cnt'] == 0) {
                $sql = "ALTER TABLE mobile_quotes ADD COLUMN stock_status ENUM('充足', '紧张', '缺货') DEFAULT '充足' COMMENT '库存状态' AFTER tags";
                $conn->exec($sql);
                $results[] = ['step' => '添加 stock_status 字段', 'status' => 'success', 'message' => '成功'];
            } else {
                $results[] = ['step' => '添加 stock_status 字段', 'status' => 'skip', 'message' => '字段已存在，跳过'];
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Duplicate column name') !== false || strpos($errorMsg, 'already exists') !== false) {
                $results[] = ['step' => '添加 stock_status 字段', 'status' => 'skip', 'message' => '字段已存在，跳过'];
            } else {
                $results[] = ['step' => '添加 stock_status 字段', 'status' => 'error', 'message' => '错误: ' . $errorMsg];
            }
        }

        // ============================================
        // 步骤5: 添加 sales_count 字段
        // ============================================
        try {
            $checkSql = "SELECT COUNT(*) as cnt FROM information_schema.columns 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'mobile_quotes' 
                        AND column_name = 'sales_count'";
            $checkResult = $conn->query($checkSql)->fetch(PDO::FETCH_ASSOC);
            
            if ($checkResult['cnt'] == 0) {
                $sql = "ALTER TABLE mobile_quotes ADD COLUMN sales_count INT(11) DEFAULT 0 COMMENT '销量统计' AFTER stock_status";
                $conn->exec($sql);
                $results[] = ['step' => '添加 sales_count 字段', 'status' => 'success', 'message' => '成功'];
            } else {
                $results[] = ['step' => '添加 sales_count 字段', 'status' => 'skip', 'message' => '字段已存在，跳过'];
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Duplicate column name') !== false || strpos($errorMsg, 'already exists') !== false) {
                $results[] = ['step' => '添加 sales_count 字段', 'status' => 'skip', 'message' => '字段已存在，跳过'];
            } else {
                $results[] = ['step' => '添加 sales_count 字段', 'status' => 'error', 'message' => '错误: ' . $errorMsg];
            }
        }
        
        // ============================================
        // 步骤5.5: 为 mobile_phones 表添加 image_path 字段
        // ============================================
        try {
            $checkSql = "SELECT COUNT(*) as cnt FROM information_schema.columns 
                        WHERE table_schema = DATABASE() 
                        AND table_name = 'mobile_phones' 
                        AND column_name = 'image_path'";
            $checkResult = $conn->query($checkSql)->fetch(PDO::FETCH_ASSOC);
            
            if ($checkResult['cnt'] == 0) {
                $sql = "ALTER TABLE mobile_phones ADD COLUMN image_path VARCHAR(255) DEFAULT NULL COMMENT '产品图片路径' AFTER note";
                $conn->exec($sql);
                $results[] = ['step' => '为 mobile_phones 表添加 image_path 字段', 'status' => 'success', 'message' => '成功'];
            } else {
                $results[] = ['step' => '为 mobile_phones 表添加 image_path 字段', 'status' => 'skip', 'message' => '字段已存在，跳过'];
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Duplicate column name') !== false || strpos($errorMsg, 'already exists') !== false) {
                $results[] = ['step' => '为 mobile_phones 表添加 image_path 字段', 'status' => 'skip', 'message' => '字段已存在，跳过'];
            } else {
                $results[] = ['step' => '为 mobile_phones 表添加 image_path 字段', 'status' => 'error', 'message' => '错误: ' . $errorMsg];
            }
        }

        // ============================================
        // 步骤6: 为苹果手机添加示例数据
        // ============================================
        try {
            $repairPriceApple = json_encode([
                "screen" => ["name" => "更换屏幕", "price" => 2800, "time" => "1-2小时"],
                "battery" => ["name" => "更换电池", "price" => 580, "time" => "30分钟"],
                "camera" => ["name" => "更换后置摄像头", "price" => 1200, "time" => "1小时"],
                "charge_port" => ["name" => "更换充电接口", "price" => 380, "time" => "1小时"],
                "back_glass" => ["name" => "更换后盖玻璃", "price" => 680, "time" => "2小时"]
            ], JSON_UNESCAPED_UNICODE);

            $sql = "UPDATE mobile_quotes SET
                    repair_price = :repair_price,
                    tags = '热销,旗舰',
                    stock_status = '充足',
                    sales_count = FLOOR(100 + RAND() * 400)
                    WHERE (brand = '苹果' OR brand LIKE '%苹果%') AND (repair_price IS NULL OR repair_price = '')";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['repair_price' => $repairPriceApple]);
            $affectedRows = $stmt->rowCount();
            $results[] = ['step' => '更新苹果手机数据', 'status' => 'success', 'message' => "已更新 {$affectedRows} 条记录"];
        } catch (Exception $e) {
            $results[] = ['step' => '更新苹果手机数据', 'status' => 'warning', 'message' => '跳过: ' . $e->getMessage()];
        }

        // ============================================
        // 步骤7: 为华为手机添加示例数据
        // ============================================
        try {
            $repairPriceHuawei = json_encode([
                "screen" => ["name" => "更换屏幕", "price" => 1800, "time" => "1-2小时"],
                "battery" => ["name" => "更换电池", "price" => 380, "time" => "30分钟"],
                "camera" => ["name" => "更换后置摄像头", "price" => 880, "time" => "1小时"],
                "charge_port" => ["name" => "更换充电接口", "price" => 280, "time" => "1小时"],
                "back_glass" => ["name" => "更换后盖玻璃", "price" => 480, "time" => "2小时"]
            ], JSON_UNESCAPED_UNICODE);

            $sql = "UPDATE mobile_quotes SET
                    repair_price = :repair_price,
                    tags = '热销,国产旗舰',
                    stock_status = '充足',
                    sales_count = FLOOR(80 + RAND() * 350)
                    WHERE (brand = '华为' OR brand LIKE '%华为%') AND (repair_price IS NULL OR repair_price = '')";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['repair_price' => $repairPriceHuawei]);
            $affectedRows = $stmt->rowCount();
            $results[] = ['step' => '更新华为手机数据', 'status' => 'success', 'message' => "已更新 {$affectedRows} 条记录"];
        } catch (Exception $e) {
            $results[] = ['step' => '更新华为手机数据', 'status' => 'warning', 'message' => '跳过: ' . $e->getMessage()];
        }

        // ============================================
        // 步骤8: 为小米手机添加示例数据
        // ============================================
        try {
            $repairPriceXiaomi = json_encode([
                "screen" => ["name" => "更换屏幕", "price" => 1200, "time" => "1-2小时"],
                "battery" => ["name" => "更换电池", "price" => 280, "time" => "30分钟"],
                "camera" => ["name" => "更换后置摄像头", "price" => 680, "time" => "1小时"],
                "charge_port" => ["name" => "更换充电接口", "price" => 180, "time" => "1小时"],
                "back_glass" => ["name" => "更换后盖玻璃", "price" => 380, "time" => "2小时"]
            ], JSON_UNESCAPED_UNICODE);

            $sql = "UPDATE mobile_quotes SET
                    repair_price = :repair_price,
                    tags = '性价比,热销',
                    stock_status = '充足',
                    sales_count = FLOOR(60 + RAND() * 300)
                    WHERE (brand = '小米' OR brand LIKE '%小米%') AND (repair_price IS NULL OR repair_price = '')";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['repair_price' => $repairPriceXiaomi]);
            $affectedRows = $stmt->rowCount();
            $results[] = ['step' => '更新小米手机数据', 'status' => 'success', 'message' => "已更新 {$affectedRows} 条记录"];
        } catch (Exception $e) {
            $results[] = ['step' => '更新小米手机数据', 'status' => 'warning', 'message' => '跳过: ' . $e->getMessage()];
        }

        // ============================================
        // 步骤9.5: 创建索引优化查询性能
        // ============================================
        try {
            // 检查并创建 tags 索引
            $checkIndex = "SELECT COUNT(*) as cnt FROM information_schema.statistics 
                          WHERE table_schema = DATABASE() 
                          AND table_name = 'mobile_quotes' 
                          AND index_name = 'idx_tags'";
            $indexResult = $conn->query($checkIndex)->fetch(PDO::FETCH_ASSOC);
            if ($indexResult['cnt'] == 0) {
                $conn->exec("ALTER TABLE mobile_quotes ADD INDEX idx_tags (tags)");
                $results[] = ['step' => '创建 tags 索引', 'status' => 'success', 'message' => '成功'];
            } else {
                $results[] = ['step' => '创建 tags 索引', 'status' => 'skip', 'message' => '索引已存在，跳过'];
            }
        } catch (Exception $e) {
            $results[] = ['step' => '创建 tags 索引', 'status' => 'warning', 'message' => '跳过: ' . $e->getMessage()];
        }
        
        try {
            // 检查并创建 stock_status 索引
            $checkIndex = "SELECT COUNT(*) as cnt FROM information_schema.statistics 
                          WHERE table_schema = DATABASE() 
                          AND table_name = 'mobile_quotes' 
                          AND index_name = 'idx_stock_status'";
            $indexResult = $conn->query($checkIndex)->fetch(PDO::FETCH_ASSOC);
            if ($indexResult['cnt'] == 0) {
                $conn->exec("ALTER TABLE mobile_quotes ADD INDEX idx_stock_status (stock_status)");
                $results[] = ['step' => '创建 stock_status 索引', 'status' => 'success', 'message' => '成功'];
            } else {
                $results[] = ['step' => '创建 stock_status 索引', 'status' => 'skip', 'message' => '索引已存在，跳过'];
            }
        } catch (Exception $e) {
            $results[] = ['step' => '创建 stock_status 索引', 'status' => 'warning', 'message' => '跳过: ' . $e->getMessage()];
        }
        
        try {
            // 检查并创建 sales_count 索引
            $checkIndex = "SELECT COUNT(*) as cnt FROM information_schema.statistics 
                          WHERE table_schema = DATABASE() 
                          AND table_name = 'mobile_quotes' 
                          AND index_name = 'idx_sales_count'";
            $indexResult = $conn->query($checkIndex)->fetch(PDO::FETCH_ASSOC);
            if ($indexResult['cnt'] == 0) {
                $conn->exec("ALTER TABLE mobile_quotes ADD INDEX idx_sales_count (sales_count)");
                $results[] = ['step' => '创建 sales_count 索引', 'status' => 'success', 'message' => '成功'];
            } else {
                $results[] = ['step' => '创建 sales_count 索引', 'status' => 'skip', 'message' => '索引已存在，跳过'];
            }
        } catch (Exception $e) {
            $results[] = ['step' => '创建 sales_count 索引', 'status' => 'warning', 'message' => '跳过: ' . $e->getMessage()];
        }

        // ============================================
        // 步骤9: 为其他品牌添加示例数据
        // ============================================
        try {
            $repairPriceOther = json_encode([
                "screen" => ["name" => "更换屏幕", "price" => 1500, "time" => "1-2小时"],
                "battery" => ["name" => "更换电池", "price" => 300, "time" => "30分钟"],
                "camera" => ["name" => "更换后置摄像头", "price" => 600, "time" => "1小时"],
                "charge_port" => ["name" => "更换充电接口", "price" => 200, "time" => "1小时"],
                "back_glass" => ["name" => "更换后盖玻璃", "price" => 400, "time" => "2小时"]
            ], JSON_UNESCAPED_UNICODE);

            $sql = "UPDATE mobile_quotes SET
                    repair_price = :repair_price,
                    tags = '专业维修,品质保证',
                    stock_status = COALESCE(stock_status, '充足'),
                    sales_count = COALESCE(sales_count, FLOOR(50 + RAND() * 250))
                    WHERE brand NOT IN ('苹果', '华为', '小米') 
                    AND brand NOT LIKE '%苹果%' 
                    AND brand NOT LIKE '%华为%' 
                    AND brand NOT LIKE '%小米%'
                    AND (repair_price IS NULL OR repair_price = '')";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['repair_price' => $repairPriceOther]);
            $affectedRows = $stmt->rowCount();
            $results[] = ['step' => '更新其他品牌数据', 'status' => 'success', 'message' => "已更新 {$affectedRows} 条记录"];
        } catch (Exception $e) {
            $results[] = ['step' => '更新其他品牌数据', 'status' => 'warning', 'message' => '跳过: ' . $e->getMessage()];
        }

        // 检查是否有严重错误
        $hasError = false;
        foreach ($results as $result) {
            if (isset($result['status']) && $result['status'] === 'error') {
                $hasError = true;
                break;
            }
        }
        
        if ($hasError) {
            // 有错误，回滚事务
            $conn->rollBack();
            echo json_encode([
                'success' => false,
                'message' => '数据库升级过程中出现错误，已回滚',
                'results' => $results
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // 提交事务
            $conn->commit();

            // 创建锁定文件
            file_put_contents($lockFile, date('Y-m-d H:i:s') . " - 数据库升级完成\n");

            echo json_encode([
                'success' => true,
                'message' => '数据库升级成功！',
                'results' => $results
            ], JSON_UNESCAPED_UNICODE);
        }

    } catch (Exception $e) {
        // 回滚事务
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }

        echo json_encode([
            'success' => false,
            'message' => '升级失败：' . $e->getMessage(),
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// 检查升级状态
$isUpgraded = file_exists($lockFile);
$upgradeTime = $isUpgraded ? file_get_contents($lockFile) : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库升级工具 - 甘肃汇森</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans SC', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .step-item {
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }
        .step-item.show {
            opacity: 1;
            transform: translateY(0);
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .loading {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4">
        <!-- 头部 -->
        <div class="max-w-4xl mx-auto">
            <div class="gradient-bg rounded-2xl shadow-2xl p-8 text-white mb-8">
                <h1 class="text-4xl font-bold mb-2">数据库升级工具</h1>
                <p class="text-blue-100">甘肃汇森信息科技有限公司 - Database Upgrade Tool</p>
            </div>

            <!-- 升级状态卡片 -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <?php if ($isUpgraded): ?>
                            <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        <?php else: ?>
                            <svg class="w-12 h-12 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="ml-4 flex-1">
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">
                            <?php echo $isUpgraded ? '数据库已升级' : '待升级'; ?>
                        </h2>
                        <?php if ($isUpgraded): ?>
                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($upgradeTime); ?></p>
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                                <p class="text-yellow-700">
                                    <strong>注意：</strong> 如需重新升级，请先删除根目录下的 <code class="bg-yellow-100 px-2 py-1 rounded">upgrade.lock</code> 文件
                                </p>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-600 mb-4">点击下方按钮开始升级数据库</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 升级内容说明 -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    本次升级内容
                </h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                        <h4 class="font-semibold text-gray-800">新增字段</h4>
                        <ul class="text-gray-600 mt-2 space-y-1">
                            <li>• image_path - 手机图片路径</li>
                            <li>• repair_price - 维修价格（JSON）</li>
                            <li>• tags - 标签</li>
                            <li>• stock_status - 库存状态</li>
                            <li>• sales_count - 销售数量</li>
                        </ul>
                    </div>
                    <div class="border-l-4 border-green-500 pl-4 py-2">
                        <h4 class="font-semibold text-gray-800">示例数据</h4>
                        <ul class="text-gray-600 mt-2 space-y-1">
                            <li>• 苹果手机维修价格</li>
                            <li>• 华为手机维修价格</li>
                            <li>• 小米手机维修价格</li>
                            <li>• 其他品牌维修价格</li>
                            <li>• 标签和销量数据</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 升级按钮 -->
            <?php if (!$isUpgraded): ?>
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <button
                    id="upgradeBtn"
                    class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white font-bold py-4 px-6 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all transform hover:scale-105 shadow-lg flex items-center justify-center"
                >
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    开始升级数据库
                </button>
            </div>
            <?php endif; ?>

            <!-- 升级进度 -->
            <div id="progressContainer" class="bg-white rounded-xl shadow-lg p-8 mb-6 hidden">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600 loading" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    升级进度
                </h3>
                <div id="progressSteps" class="space-y-3"></div>
            </div>

            <!-- 快速访问链接 -->
            <div id="quickLinks" class="bg-white rounded-xl shadow-lg p-8 <?php echo $isUpgraded ? '' : 'hidden'; ?>">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                    快速访问
                </h3>
                <div class="grid md:grid-cols-3 gap-4">
                    <a href="run_image_match.php" class="block p-4 border-2 border-purple-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-all group">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-800 group-hover:text-purple-600">图片匹配工具</h4>
                                <p class="text-sm text-gray-500 mt-1">批量匹配手机图片</p>
                            </div>
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </a>
                    <a href="quotes.php" class="block p-4 border-2 border-blue-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all group">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-800 group-hover:text-blue-600">报价列表</h4>
                                <p class="text-sm text-gray-500 mt-1">查看所有手机报价</p>
                            </div>
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </a>
                    <a href="detail.php?id=1" class="block p-4 border-2 border-green-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all group">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-800 group-hover:text-green-600">详情页面</h4>
                                <p class="text-sm text-gray-500 mt-1">查看手机详情</p>
                            </div>
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const upgradeBtn = document.getElementById('upgradeBtn');
        const progressContainer = document.getElementById('progressContainer');
        const progressSteps = document.getElementById('progressSteps');
        const quickLinks = document.getElementById('quickLinks');

        // 升级按钮点击事件
        if (upgradeBtn) {
            upgradeBtn.addEventListener('click', async () => {
                upgradeBtn.disabled = true;
                upgradeBtn.innerHTML = '<svg class="w-6 h-6 mr-2 loading" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>正在升级...';

                progressContainer.classList.remove('hidden');
                progressSteps.innerHTML = '';

                try {
                    const formData = new FormData();
                    formData.append('action', 'upgrade');

                    const response = await fetch('setup_upgrade.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        // 显示每个步骤的结果
                        result.results.forEach((step, index) => {
                            setTimeout(() => {
                                const stepDiv = document.createElement('div');
                                stepDiv.className = 'step-item flex items-center p-4 rounded-lg ' +
                                    (step.status === 'success' ? 'bg-green-50 border border-green-200' :
                                     step.status === 'skip' ? 'bg-yellow-50 border border-yellow-200' :
                                     'bg-red-50 border border-red-200');

                                const icon = step.status === 'success' ?
                                    '<svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
                                    step.status === 'skip' ?
                                    '<svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>' :
                                    '<svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';

                                stepDiv.innerHTML = `
                                    <div class="flex-shrink-0">${icon}</div>
                                    <div class="ml-3 flex-1">
                                        <p class="font-semibold text-gray-800">${step.step}</p>
                                        <p class="text-sm text-gray-600">${step.message}</p>
                                    </div>
                                `;

                                progressSteps.appendChild(stepDiv);
                                setTimeout(() => stepDiv.classList.add('show'), 10);
                            }, index * 200);
                        });

                        // 显示成功消息
                        setTimeout(() => {
                            upgradeBtn.innerHTML = '<svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>升级完成！';
                            upgradeBtn.className = 'w-full bg-green-600 text-white font-bold py-4 px-6 rounded-lg cursor-not-allowed flex items-center justify-center';
                            quickLinks.classList.remove('hidden');

                            // 3秒后刷新页面
                            setTimeout(() => {
                                location.reload();
                            }, 3000);
                        }, result.results.length * 200 + 500);

                    } else {
                        progressSteps.innerHTML = `
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div class="ml-3">
                                        <h3 class="font-semibold text-red-800">升级失败</h3>
                                        <p class="text-red-700 mt-1">${result.message}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                        upgradeBtn.disabled = false;
                        upgradeBtn.innerHTML = '<svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>重新升级';
                    }

                } catch (error) {
                    progressSteps.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="ml-3">
                                    <h3 class="font-semibold text-red-800">网络错误</h3>
                                    <p class="text-red-700 mt-1">${error.message}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    upgradeBtn.disabled = false;
                    upgradeBtn.innerHTML = '<svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>重新升级';
                }
            });
        }
    </script>
</body>
</html>
