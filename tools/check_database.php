<?php
/**
 * ============================================
 * 数据库诊断工具
 * ============================================
 * 检查数据库表和数据是否存在
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库诊断工具</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #E11D25; margin-top: 0; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #E11D25; border-radius: 4px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #007bff; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #E11D25; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .count { font-size: 24px; font-weight: bold; color: #E11D25; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔍 数据库诊断工具</h2>
        
        <?php
        try {
            $db = Database::getInstance();
            echo '<div class="section success">✓ 数据库连接成功</div>';
            
            // 检查数据库名
            $db_name = $db->fetchOne("SELECT DATABASE() as db");
            echo '<div class="section info"><strong>当前数据库：</strong>' . htmlspecialchars($db_name['db']) . '</div>';
            
            // 检查表是否存在
            echo '<div class="section"><h3>📊 数据表检查</h3>';
            
            $tables = ['mobile_phones', 'mobile_quotes', 'business_stats', 'users', 'phone_details'];
            $table_status = [];
            
            foreach ($tables as $table) {
                try {
                    $result = $db->fetchOne("SELECT COUNT(*) as cnt FROM `{$table}`");
                    $count = $result['cnt'];
                    $table_status[$table] = $count;
                    
                    if ($count > 0) {
                        echo "<div class='success'>✓ 表 <strong>{$table}</strong> 存在，共有 <span class='count'>{$count}</span> 条记录</div>";
                    } else {
                        echo "<div class='warning'>⚠ 表 <strong>{$table}</strong> 存在，但 <span class='count'>没有数据</span></div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='error'>✗ 表 <strong>{$table}</strong> 不存在或无法访问</div>";
                    $table_status[$table] = -1;
                }
            }
            echo '</div>';
            
            // 检查 mobile_phones 表的数据
            if (isset($table_status['mobile_phones']) && $table_status['mobile_phones'] > 0) {
                echo '<div class="section"><h3>📱 mobile_phones 表数据详情</h3>';
                
                // 按品牌统计
                $brands = $db->fetchAll("SELECT brand, COUNT(*) as cnt FROM mobile_phones WHERE brand != '未分类' GROUP BY brand ORDER BY cnt DESC");
                echo '<table>';
                echo '<tr><th>品牌</th><th>数量</th></tr>';
                foreach ($brands as $b) {
                    echo '<tr><td>' . htmlspecialchars($b['brand']) . '</td><td>' . $b['cnt'] . '</td></tr>';
                }
                echo '</table>';
                
                // 显示前10条数据
                $samples = $db->fetchAll("SELECT id, brand, model, spec, price FROM mobile_phones WHERE brand != '未分类' LIMIT 10");
                echo '<h4>前10条数据示例：</h4>';
                echo '<table>';
                echo '<tr><th>ID</th><th>品牌</th><th>型号</th><th>规格</th><th>价格</th></tr>';
                foreach ($samples as $s) {
                    echo '<tr>';
                    echo '<td>' . $s['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($s['brand']) . '</td>';
                    echo '<td>' . htmlspecialchars($s['model']) . '</td>';
                    echo '<td>' . htmlspecialchars($s['spec']) . '</td>';
                    echo '<td>¥' . number_format($s['price'], 2) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            } else {
                echo '<div class="section error"><h3>❌ 问题诊断</h3>';
                echo '<p><strong>mobile_phones 表没有数据！</strong></p>';
                echo '<p>可能的原因：</p>';
                echo '<ol>';
                echo '<li>SQL 文件导入时数据插入失败</li>';
                echo '<li>表被清空或数据被删除</li>';
                echo '<li>INSERT 语句执行失败</li>';
                echo '</ol>';
                echo '<p><strong>解决方案：</strong></p>';
                echo '<ol>';
                echo '<li>重新运行 <a href="import_database.php">import_database.php</a> 导入数据</li>';
                echo '<li>或者手动在 phpMyAdmin 中执行以下 SQL：</li>';
                echo '</ol>';
                echo '<pre>';
                echo "INSERT INTO `mobile_phones` \n";
                echo "(`brand`, `model`, `spec`, `price`, `note`, `performance_score`, `camera_score`, `battery_score`, `tags`) \n";
                echo "VALUES \n";
                echo "('苹果', 'iPhone 15 Pro Max', '256G 原色钛金属', 8500.00, '国行 带票', 95, 90, 75, '高性能,拍照好,旗舰机'),\n";
                echo "('苹果', 'iPhone 15 Pro Max', '512G 原色钛金属', 9800.00, '国行 带票', 95, 90, 75, '高性能,拍照好,旗舰机'),\n";
                echo "('华为', 'Mate 60 Pro', '512G 雅川青', 6800.00, '国行 带票', 85, 95, 80, '高性能,拍照好,长续航,旗舰机'),\n";
                echo "('小米', '14 Pro', '512G 黑色', 4200.00, '国行 带票', 88, 82, 78, '高性能,拍照好,长续航');\n";
                echo '</pre>';
                echo '</div>';
            }
            
            // 检查表结构
            echo '<div class="section"><h3>🔧 mobile_phones 表结构</h3>';
            try {
                $columns = $db->fetchAll("SHOW COLUMNS FROM mobile_phones");
                echo '<table>';
                echo '<tr><th>字段名</th><th>类型</th><th>允许NULL</th><th>默认值</th></tr>';
                foreach ($columns as $col) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } catch (Exception $e) {
                echo '<div class="error">无法获取表结构：' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            echo '</div>';
            
            // 测试查询
            echo '<div class="section"><h3>🧪 测试查询（quotes.php 使用的查询）</h3>';
            try {
                $test_query = "SELECT COUNT(*) as cnt FROM mobile_phones WHERE brand != '未分类'";
                $result = $db->fetchOne($test_query);
                echo '<div class="info">查询语句：<code>' . htmlspecialchars($test_query) . '</code></div>';
                echo '<div class="success">查询结果：找到 <span class="count">' . $result['cnt'] . '</span> 条记录</div>';
                
                if ($result['cnt'] == 0) {
                    echo '<div class="error">⚠️ 这就是为什么 quotes.php 页面没有显示数据的原因！</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">查询失败：' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="section error">';
            echo '<h3>❌ 数据库连接失败</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p>请检查 config/config.php 中的数据库配置</p>';
            echo '</div>';
        }
        ?>
        
        <div class="section">
            <h3>🔗 快速链接</h3>
            <p>
                <a href="quotes.php" style="display: inline-block; padding: 10px 20px; background: #E11D25; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;">前往报价页面</a>
                <a href="import_database.php" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;">重新导入数据库</a>
                <a href="dashboard.php" style="display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">管理后台</a>
            </p>
        </div>
    </div>
</body>
</html>
