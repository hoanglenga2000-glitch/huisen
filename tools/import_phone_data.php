<?php
/**
 * ============================================
 * 手机数据导入工具（多数据源）
 * ============================================
 * 功能：
 * 1. 从 mobile_quotes 表导入数据到 mobile_phones
 * 2. 从 phone_details 表导入数据到 mobile_phones
 * 3. 插入示例数据
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>手机数据导入工具</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #E11D25; margin-top: 0; }
        .section { margin: 20px 0; padding: 20px; background: #f9f9f9; border-left: 4px solid #E11D25; border-radius: 4px; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }
        .info { color: #007bff; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0; }
        .btn { display: inline-block; padding: 12px 30px; background: #E11D25; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px 0 0; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #C91A22; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #E11D25; color: white; }
        .count { font-size: 20px; font-weight: bold; color: #E11D25; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📥 手机数据导入工具</h2>
        
        <?php
        $action = $_GET['action'] ?? '';
        
        try {
            $db = Database::getInstance();
            
            // 检查各表的数据量
            $stats = [];
            $tables = ['mobile_phones', 'mobile_quotes', 'phone_details'];
            foreach ($tables as $table) {
                try {
                    $result = $db->fetchOne("SELECT COUNT(*) as cnt FROM `{$table}` WHERE brand != '未分类' OR brand IS NOT NULL");
                    $stats[$table] = $result['cnt'];
                } catch (Exception $e) {
                    $stats[$table] = 0;
                }
            }
            
            // 显示当前数据统计
            echo '<div class="section">';
            echo '<h3>📊 当前数据统计</h3>';
            echo '<table>';
            echo '<tr><th>数据表</th><th>记录数</th></tr>';
            echo '<tr><td>mobile_phones（报价页面使用）</td><td><span class="count">' . $stats['mobile_phones'] . '</span> 条</td></tr>';
            echo '<tr><td>mobile_quotes（备用数据）</td><td><span class="count">' . $stats['mobile_quotes'] . '</span> 条</td></tr>';
            echo '<tr><td>phone_details（详情数据）</td><td><span class="count">' . $stats['phone_details'] . '</span> 条</td></tr>';
            echo '</table>';
            echo '</div>';
            
            if ($action === 'import_from_quotes') {
                // 从 mobile_quotes 导入
                echo '<div class="section">';
                echo '<h3>🔄 从 mobile_quotes 导入数据</h3>';
                
                $quotes = $db->fetchAll("SELECT brand, model, spec, price, note FROM mobile_quotes WHERE brand != '未分类'");
                
                if (empty($quotes)) {
                    echo '<div class="error">mobile_quotes 表没有数据可导入</div>';
                } else {
                    $imported = 0;
                    $skipped = 0;
                    
                    foreach ($quotes as $quote) {
                        // 检查是否已存在
                        $exists = $db->fetchOne(
                            "SELECT id FROM mobile_phones WHERE brand = ? AND model = ? AND spec = ?",
                            [$quote['brand'], $quote['model'], $quote['spec']]
                        );
                        
                        if (!$exists) {
                            $db->query(
                                "INSERT INTO mobile_phones (brand, model, spec, price, note) VALUES (?, ?, ?, ?, ?)",
                                [$quote['brand'], $quote['model'], $quote['spec'], $quote['price'], $quote['note']]
                            );
                            $imported++;
                        } else {
                            $skipped++;
                        }
                    }
                    
                    echo '<div class="success">';
                    echo '<p>✓ 导入完成！</p>';
                    echo '<p>成功导入：<strong>' . $imported . '</strong> 条</p>';
                    echo '<p>跳过重复：<strong>' . $skipped . '</strong> 条</p>';
                    echo '</div>';
                }
                echo '</div>';
            }
            
            if ($action === 'import_from_details') {
                // 从 phone_details 导入
                echo '<div class="section">';
                echo '<h3>🔄 从 phone_details 导入数据</h3>';
                
                $details = $db->fetchAll("SELECT DISTINCT brand, model FROM phone_details WHERE brand != '未分类' AND brand IS NOT NULL LIMIT 100");
                
                if (empty($details)) {
                    echo '<div class="error">phone_details 表没有数据可导入</div>';
                } else {
                    $imported = 0;
                    $skipped = 0;
                    
                    foreach ($details as $detail) {
                        // 检查是否已存在
                        $exists = $db->fetchOne(
                            "SELECT id FROM mobile_phones WHERE brand = ? AND model = ?",
                            [$detail['brand'], $detail['model']]
                        );
                        
                        if (!$exists) {
                            // 生成默认价格（根据品牌估算）
                            $base_price = 3000;
                            if (strpos($detail['brand'], '苹果') !== false || strpos($detail['brand'], 'Apple') !== false) {
                                $base_price = 6000;
                            } elseif (strpos($detail['brand'], '华为') !== false || strpos($detail['brand'], 'Huawei') !== false) {
                                $base_price = 4000;
                            } elseif (strpos($detail['brand'], '小米') !== false || strpos($detail['brand'], 'Xiaomi') !== false) {
                                $base_price = 2500;
                            }
                            
                            $db->query(
                                "INSERT INTO mobile_phones (brand, model, spec, price, note) VALUES (?, ?, ?, ?, ?)",
                                [$detail['brand'], $detail['model'], '标准版', $base_price, '待更新价格']
                            );
                            $imported++;
                        } else {
                            $skipped++;
                        }
                    }
                    
                    echo '<div class="success">';
                    echo '<p>✓ 导入完成！</p>';
                    echo '<p>成功导入：<strong>' . $imported . '</strong> 条</p>';
                    echo '<p>跳过重复：<strong>' . $skipped . '</strong> 条</p>';
                    echo '<p class="info">⚠️ 注意：从 phone_details 导入的数据使用了估算价格，请后续手动更新实际价格</p>';
                    echo '</div>';
                }
                echo '</div>';
            }
            
            if ($action === 'insert_samples') {
                // 插入示例数据
                echo '<div class="section">';
                echo '<h3>📝 插入示例数据</h3>';
                
                $samples = [
                    ['苹果', 'iPhone 15 Pro Max', '256G 原色钛金属', 8500.00, '国行 带票', 95, 90, 75, '高性能,拍照好,旗舰机'],
                    ['苹果', 'iPhone 15 Pro Max', '512G 原色钛金属', 9800.00, '国行 带票', 95, 90, 75, '高性能,拍照好,旗舰机'],
                    ['苹果', 'iPhone 15 Pro', '256G 蓝色钛金属', 7500.00, '国行 带票', 92, 88, 70, '高性能,拍照好,旗舰机'],
                    ['苹果', 'iPhone 15', '256G 粉色', 5500.00, '国行 带票', 85, 80, 70, '拍照好,性价比'],
                    ['华为', 'Mate 60 Pro', '512G 雅川青', 6800.00, '国行 带票', 85, 95, 80, '高性能,拍照好,长续航,旗舰机'],
                    ['华为', 'Mate 60 Pro', '256G 羽砂紫', 6200.00, '国行 带票', 85, 95, 80, '高性能,拍照好,长续航,旗舰机'],
                    ['华为', 'nova 12', '256G 白色', 3200.00, '国行 带票', 70, 85, 75, '拍照好,性价比'],
                    ['华为', 'P60 Pro', '512G 羽砂紫', 4800.00, '国行 带票', 80, 92, 75, '拍照好,旗舰机'],
                    ['小米', '14 Pro', '512G 黑色', 4200.00, '国行 带票', 88, 82, 78, '高性能,拍照好,长续航'],
                    ['小米', '14 Pro', '256G 白色', 3800.00, '国行 带票', 88, 82, 78, '高性能,拍照好,长续航'],
                    ['小米', 'Redmi K70', '256G 黑色', 2800.00, '国行 带票', 82, 75, 85, '高性能,长续航,性价比'],
                    ['小米', '13 Ultra', '512G 黑色', 4500.00, '国行 带票', 85, 95, 80, '高性能,拍照好,旗舰机'],
                    ['OPPO', 'Find X7', '512G 黑色', 4500.00, '国行 带票', 80, 90, 75, '拍照好,长续航'],
                    ['OPPO', 'Reno 11', '256G 绿色', 2800.00, '国行 带票', 75, 85, 75, '拍照好,性价比'],
                    ['vivo', 'X100 Pro', '512G 蓝色', 4800.00, '国行 带票', 85, 92, 78, '高性能,拍照好,长续航'],
                    ['vivo', 'S18 Pro', '256G 紫色', 3200.00, '国行 带票', 78, 88, 75, '拍照好,性价比'],
                    ['一加', '12', '256G 白色', 4000.00, '国行 带票', 90, 80, 75, '高性能,游戏手机'],
                    ['一加', 'Ace 3', '256G 蓝色', 2800.00, '国行 带票', 85, 75, 80, '高性能,长续航,性价比'],
                    ['荣耀', 'Magic6', '256G 紫色', 4200.00, '国行 带票', 82, 88, 85, '拍照好,长续航'],
                    ['荣耀', '100 Pro', '256G 白色', 3200.00, '国行 带票', 78, 85, 80, '拍照好,性价比'],
                    ['realme', 'GT5 Pro', '256G 橙色', 3500.00, '国行 带票', 88, 78, 80, '高性能,长续航,游戏手机'],
                    ['红米', 'Note 13 Pro', '256G 蓝色', 1800.00, '国行 带票', 65, 70, 90, '长续航,性价比'],
                    ['三星', 'Galaxy S24 Ultra', '512G 钛色', 6800.00, '国行 带票', 90, 95, 85, '高性能,拍照好,旗舰机'],
                    ['三星', 'Galaxy S24', '256G 黑色', 4200.00, '国行 带票', 85, 88, 80, '高性能,拍照好'],
                ];
                
                $imported = 0;
                $skipped = 0;
                
                foreach ($samples as $sample) {
                    // 检查是否已存在
                    $exists = $db->fetchOne(
                        "SELECT id FROM mobile_phones WHERE brand = ? AND model = ? AND spec = ?",
                        [$sample[0], $sample[1], $sample[2]]
                    );
                    
                    if (!$exists) {
                        $db->query(
                            "INSERT INTO mobile_phones (brand, model, spec, price, note, performance_score, camera_score, battery_score, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            $sample
                        );
                        $imported++;
                    } else {
                        $skipped++;
                    }
                }
                
                echo '<div class="success">';
                echo '<p>✓ 示例数据插入完成！</p>';
                echo '<p>成功插入：<strong>' . $imported . '</strong> 条</p>';
                echo '<p>跳过重复：<strong>' . $skipped . '</strong> 条</p>';
                echo '</div>';
                echo '</div>';
            }
            
            // 刷新统计数据
            $stats_after = [];
            foreach ($tables as $table) {
                try {
                    $result = $db->fetchOne("SELECT COUNT(*) as cnt FROM `{$table}` WHERE brand != '未分类' OR brand IS NOT NULL");
                    $stats_after[$table] = $result['cnt'];
                } catch (Exception $e) {
                    $stats_after[$table] = 0;
                }
            }
            
            // 显示导入选项
            echo '<div class="section">';
            echo '<h3>🚀 数据导入选项</h3>';
            
            if ($stats['mobile_quotes'] > 0) {
                echo '<div class="info">';
                echo '<p><strong>选项 1：从 mobile_quotes 表导入</strong></p>';
                echo '<p>mobile_quotes 表有 ' . $stats['mobile_quotes'] . ' 条数据可以导入</p>';
                echo '<a href="?action=import_from_quotes" class="btn">从 mobile_quotes 导入</a>';
                echo '</div>';
            }
            
            if ($stats['phone_details'] > 0) {
                echo '<div class="info">';
                echo '<p><strong>选项 2：从 phone_details 表导入</strong></p>';
                echo '<p>phone_details 表有 ' . $stats['phone_details'] . ' 条数据可以导入（会生成基础记录）</p>';
                echo '<a href="?action=import_from_details" class="btn">从 phone_details 导入</a>';
                echo '</div>';
            }
            
            echo '<div class="info">';
            echo '<p><strong>选项 3：插入示例数据</strong></p>';
            echo '<p>插入 24 条精选手机示例数据（包含多个品牌和型号）</p>';
            echo '<a href="?action=insert_samples" class="btn">插入示例数据</a>';
            echo '</div>';
            echo '</div>';
            
            // 显示导入后的统计
            if ($action && ($stats_after['mobile_phones'] != $stats['mobile_phones'])) {
                echo '<div class="section success">';
                echo '<h3>✅ 导入后数据统计</h3>';
                echo '<p>mobile_phones 表现在有 <span class="count">' . $stats_after['mobile_phones'] . '</span> 条数据</p>';
                echo '<p><a href="quotes.php" class="btn">前往报价页面查看</a></p>';
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<h3>❌ 错误</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
        
        <div class="section">
            <h3>🔗 快速链接</h3>
            <p>
                <a href="quotes.php" class="btn">前往报价页面</a>
                <a href="check_database.php" class="btn btn-secondary">数据库诊断</a>
                <a href="dashboard.php" class="btn btn-secondary">管理后台</a>
            </p>
        </div>
    </div>
</body>
</html>
