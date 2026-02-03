<?php
/**
 * ============================================
 * 手机数据同步工具 - 将 mobile_phones 数据同步到 mobile_quotes
 * ============================================
 * 
 * 功能说明：
 * 1. 将 mobile_phones 表中的所有数据同步到 mobile_quotes 表
 * 2. 自动处理字段映射和转换
 * 3. 避免重复数据
 * 4. 保留 mobile_quotes 中已存在的数据
 * 
 * 使用方法：
 * 访问此页面，点击"开始同步"按钮即可
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');

$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 检查数据统计
    $phones_count = $db->fetchOne("SELECT COUNT(*) as cnt FROM mobile_phones WHERE brand != '未分类' AND brand IS NOT NULL")['cnt'];
    $quotes_count = $db->fetchOne("SELECT COUNT(*) as cnt FROM mobile_quotes")['cnt'];
    
    if ($action === 'sync') {
        echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据同步中...</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #E11D25; margin-top: 0; }
        .log { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto; font-family: "Courier New", monospace; font-size: 13px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #007bff; }
        .progress { margin: 20px 0; }
        .progress-bar { background: #e0e0e0; height: 30px; border-radius: 15px; overflow: hidden; }
        .progress-fill { background: linear-gradient(90deg, #E11D25, #C91A22); height: 100%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔄 数据同步工具</h2>
        <div class="progress">
            <div class="progress-bar">
                <div class="progress-fill" id="progress" style="width: 0%">0%</div>
            </div>
        </div>
        <div class="log" id="log">';
        
        flush();
        
        // 开始同步
        $phones = $db->fetchAll("SELECT * FROM mobile_phones WHERE brand != '未分类' AND brand IS NOT NULL");
        $total = count($phones);
        $synced = 0;
        $skipped = 0;
        $errors = 0;
        
        echo '<div class="info">📊 找到 ' . $total . ' 条 mobile_phones 数据，开始同步...</div><br>';
        flush();
        
        foreach ($phones as $index => $phone) {
            $progress = round(($index + 1) / $total * 100);
            echo '<script>document.getElementById("progress").style.width="' . $progress . '%"; document.getElementById("progress").textContent="' . $progress . '%";</script>';
            flush();
            
            try {
                // 检查是否已存在（根据品牌、型号、规格）
                $exists = $db->fetchOne(
                    "SELECT id FROM mobile_quotes WHERE brand = ? AND model = ? AND spec = ?",
                    [$phone['brand'], $phone['model'], $phone['spec'] ?? '']
                );
                
                if ($exists) {
                    // 更新现有记录
                    $update_sql = "UPDATE mobile_quotes SET 
                        `price` = ?,
                        `note` = ?,
                        `image_path` = ?,
                        `tags` = ?,
                        `stock_status` = COALESCE(`stock_status`, '充足'),
                        `updated_at` = NOW()
                        WHERE `id` = ?";
                    
                    $db->query($update_sql, [
                        $phone['price'],
                        $phone['note'] ?? '',
                        $phone['image_path'] ?? null,
                        $phone['tags'] ?? null,
                        $exists['id']
                    ]);
                    $skipped++;
                } else {
                    // 插入新记录
                    $insert_sql = "INSERT INTO mobile_quotes 
                        (`brand`, `model`, `spec`, `color`, `price`, `retail_price`, `condition`, `note`, `image_path`, `tags`, `stock_status`, `sales_count`, `created_at`, `updated_at`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    
                    $db->query($insert_sql, [
                        $phone['brand'],
                        $phone['model'],
                        $phone['spec'] ?? '标准版',
                        $phone['color'] ?? null,
                        $phone['price'],
                        $phone['retail_price'] ?? null,
                        '全新未拆',
                        $phone['note'] ?? '',
                        $phone['image_path'] ?? null,
                        $phone['tags'] ?? null,
                        '充足',
                        0
                    ]);
                    $synced++;
                }
                
                if (($index + 1) % 50 == 0) {
                    echo '<div class="info">已处理 ' . ($index + 1) . ' / ' . $total . ' 条...</div>';
                    flush();
                }
                
            } catch (Exception $e) {
                $errors++;
                echo '<div class="error">✗ 同步失败: ' . htmlspecialchars($phone['brand']) . ' ' . htmlspecialchars($phone['model']) . ' - ' . htmlspecialchars($e->getMessage()) . '</div>';
                flush();
            }
        }
        
        // 显示总结
        echo '</div><br>';
        echo '<div style="background: #e8f5e9; border: 1px solid #4caf50; padding: 15px; border-radius: 4px; margin-top: 20px;">';
        echo '<h3 style="margin-top: 0; color: #2e7d32;">✅ 同步完成！</h3>';
        echo '<p><strong>新增记录:</strong> ' . $synced . ' 条</p>';
        echo '<p><strong>更新记录:</strong> ' . $skipped . ' 条</p>';
        if ($errors > 0) {
            echo '<p><strong>错误:</strong> ' . $errors . ' 条</p>';
        }
        
        // 查询最终统计
        $final_count = $db->fetchOne("SELECT COUNT(*) as cnt FROM mobile_quotes")['cnt'];
        echo '<p><strong>mobile_quotes 表现在共有:</strong> <span style="font-size: 24px; font-weight: bold; color: #E11D25;">' . $final_count . '</span> 条记录</p>';
        echo '</div>';
        
        echo '<div style="margin-top: 20px;">';
        echo '<a href="quotes.php" style="display: inline-block; padding: 10px 20px; background: #E11D25; color: white; text-decoration: none; border-radius: 4px;">前往报价页面查看</a> ';
        echo '<a href="sync_phones_to_quotes.php" style="display: inline-block; padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 4px; margin-left: 10px;">返回</a>';
        echo '</div>';
        
        echo '</div></div></body></html>';
        exit;
    }
    
} catch (Exception $e) {
    die('错误: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据同步工具 - 甘肃汇森</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Microsoft YaHei", Arial, sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #E11D25 0%, #C91A22 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #E11D25;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .info-box h3 {
            color: #E11D25;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        .stat-card .count {
            font-size: 36px;
            font-weight: bold;
            color: #E11D25;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .warning-box strong {
            color: #856404;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: #E11D25;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: center;
        }
        .btn:hover {
            background: #C91A22;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(225, 29, 37, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 手机数据同步工具</h1>
            <p>将 mobile_phones 数据同步到 mobile_quotes</p>
        </div>
        
        <div class="content">
            <div class="info-box">
                <h3>📋 数据统计</h3>
                <div class="stats">
                    <div class="stat-card">
                        <h4>mobile_phones 表</h4>
                        <div class="count"><?php echo number_format($phones_count); ?></div>
                        <p>条记录</p>
                    </div>
                    <div class="stat-card">
                        <h4>mobile_quotes 表</h4>
                        <div class="count"><?php echo number_format($quotes_count); ?></div>
                        <p>条记录</p>
                    </div>
                </div>
            </div>
            
            <div class="info-box">
                <h3>ℹ️ 同步说明</h3>
                <ul style="line-height: 2; color: #666;">
                    <li>将 <strong>mobile_phones</strong> 表中的所有数据同步到 <strong>mobile_quotes</strong> 表</li>
                    <li>如果记录已存在（根据品牌、型号、规格判断），将更新价格等信息</li>
                    <li>如果记录不存在，将创建新记录</li>
                    <li>同步过程会自动处理字段映射和转换</li>
                    <li>预计同步 <strong><?php echo number_format($phones_count); ?></strong> 条记录</li>
                </ul>
            </div>
            
            <div class="warning-box">
                <strong>⚠️ 重要提示：</strong><br>
                1. 同步过程可能需要几分钟时间，请耐心等待<br>
                2. 同步不会删除 mobile_quotes 表中已存在的数据<br>
                3. 建议在同步前备份数据库<br>
                4. 同步完成后，报价页面将显示所有手机数据
            </div>
            
            <form method="GET" action="">
                <input type="hidden" name="action" value="sync">
                <button type="submit" class="btn" onclick="return confirm('确定要开始同步数据吗？\\n\\n这将把 mobile_phones 表中的 <?php echo number_format($phones_count); ?> 条数据同步到 mobile_quotes 表。')">
                    🚀 开始同步数据
                </button>
            </form>
            
            <a href="quotes.php" class="btn btn-secondary">返回报价页面</a>
        </div>
    </div>
</body>
</html>
