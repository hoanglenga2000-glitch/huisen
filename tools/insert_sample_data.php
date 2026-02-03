<?php
/**
 * ============================================
 * 快速插入示例数据
 * ============================================
 * 如果数据库导入后没有数据，运行此脚本快速插入示例数据
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>插入示例数据</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #E11D25; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }
        .info { color: #007bff; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0; }
        .btn { display: inline-block; padding: 12px 30px; background: #E11D25; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px 0 0; }
        .btn:hover { background: #C91A22; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📥 插入示例数据</h2>
        
        <?php
        $action = $_GET['action'] ?? '';
        
        if ($action === 'insert') {
            try {
                $db = Database::getInstance();
                
                // 检查表是否存在
                try {
                    $db->fetchOne("SELECT 1 FROM mobile_phones LIMIT 1");
                } catch (Exception $e) {
                    echo '<div class="error">❌ mobile_phones 表不存在！请先运行 <a href="import_database.php">import_database.php</a> 创建表结构。</div>';
                    exit;
                }
                
                // 先检查是否已有数据
                $count = $db->fetchOne("SELECT COUNT(*) as cnt FROM mobile_phones WHERE brand != '未分类'");
                if ($count['cnt'] > 0) {
                    echo '<div class="info">ℹ️ 数据库中已有 ' . $count['cnt'] . ' 条数据</div>';
                    echo '<p>是否要清空现有数据并重新插入？</p>';
                    echo '<a href="?action=insert&force=1" class="btn" onclick="return confirm(\'确定要清空现有数据吗？\')">清空并重新插入</a>';
                    echo '<a href="quotes.php" class="btn" style="background: #6c757d;">返回报价页面</a>';
                    exit;
                }
                
                // 插入数据
                $sql = "INSERT INTO `mobile_phones` 
                        (`brand`, `model`, `spec`, `price`, `note`, `performance_score`, `camera_score`, `battery_score`, `tags`) 
                        VALUES 
                        ('苹果', 'iPhone 15 Pro Max', '256G 原色钛金属', 8500.00, '国行 带票', 95, 90, 75, '高性能,拍照好,旗舰机'),
                        ('苹果', 'iPhone 15 Pro Max', '512G 原色钛金属', 9800.00, '国行 带票', 95, 90, 75, '高性能,拍照好,旗舰机'),
                        ('苹果', 'iPhone 15 Pro', '256G 蓝色钛金属', 7500.00, '国行 带票', 92, 88, 70, '高性能,拍照好,旗舰机'),
                        ('华为', 'Mate 60 Pro', '512G 雅川青', 6800.00, '国行 带票', 85, 95, 80, '高性能,拍照好,长续航,旗舰机'),
                        ('华为', 'Mate 60 Pro', '256G 羽砂紫', 6200.00, '国行 带票', 85, 95, 80, '高性能,拍照好,长续航,旗舰机'),
                        ('华为', 'nova 12', '256G 白色', 3200.00, '国行 带票', 70, 85, 75, '拍照好,性价比'),
                        ('小米', '14 Pro', '512G 黑色', 4200.00, '国行 带票', 88, 82, 78, '高性能,拍照好,长续航'),
                        ('小米', '14 Pro', '256G 白色', 3800.00, '国行 带票', 88, 82, 78, '高性能,拍照好,长续航'),
                        ('小米', 'Redmi K70', '256G 黑色', 2800.00, '国行 带票', 82, 75, 85, '高性能,长续航,性价比'),
                        ('OPPO', 'Find X7', '512G 黑色', 4500.00, '国行 带票', 80, 90, 75, '拍照好,长续航'),
                        ('vivo', 'X100 Pro', '512G 蓝色', 4800.00, '国行 带票', 85, 92, 78, '高性能,拍照好,长续航'),
                        ('一加', '12', '256G 白色', 4000.00, '国行 带票', 90, 80, 75, '高性能,游戏手机'),
                        ('realme', 'GT5 Pro', '256G 橙色', 3500.00, '国行 带票', 88, 78, 80, '高性能,长续航,游戏手机'),
                        ('荣耀', 'Magic6', '256G 紫色', 4200.00, '国行 带票', 82, 88, 85, '拍照好,长续航'),
                        ('红米', 'Note 13 Pro', '256G 蓝色', 1800.00, '国行 带票', 65, 70, 90, '长续航,性价比')";
                
                if (isset($_GET['force']) && $_GET['force'] == '1') {
                    // 清空现有数据
                    $db->query("DELETE FROM mobile_phones WHERE brand != '未分类'");
                    echo '<div class="info">✓ 已清空现有数据</div>';
                }
                
                $db->query($sql);
                $inserted = $db->fetchOne("SELECT COUNT(*) as cnt FROM mobile_phones WHERE brand != '未分类'");
                
                echo '<div class="success">';
                echo '<h3>✅ 数据插入成功！</h3>';
                echo '<p>共插入 <strong>' . $inserted['cnt'] . '</strong> 条手机数据</p>';
                echo '</div>';
                
                // 显示插入的数据统计
                $brands = $db->fetchAll("SELECT brand, COUNT(*) as cnt FROM mobile_phones WHERE brand != '未分类' GROUP BY brand");
                echo '<div class="info">';
                echo '<h4>数据统计：</h4>';
                echo '<ul>';
                foreach ($brands as $b) {
                    echo '<li>' . htmlspecialchars($b['brand']) . ': ' . $b['cnt'] . ' 款</li>';
                }
                echo '</ul>';
                echo '</div>';
                
                echo '<div style="margin-top: 20px;">';
                echo '<a href="quotes.php" class="btn">前往报价页面查看</a>';
                echo '<a href="check_database.php" class="btn" style="background: #007bff;">查看数据库详情</a>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<h3>❌ 插入失败</h3>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
        } else {
            ?>
            <div class="info">
                <p>此工具将向 <code>mobile_phones</code> 表插入 15 条示例手机数据。</p>
                <p><strong>注意：</strong>如果表中已有数据，将提示是否清空后重新插入。</p>
            </div>
            
            <div style="margin-top: 20px;">
                <a href="?action=insert" class="btn">开始插入数据</a>
                <a href="check_database.php" class="btn" style="background: #007bff;">先检查数据库状态</a>
                <a href="quotes.php" class="btn" style="background: #6c757d;">返回报价页面</a>
            </div>
            <?php
        }
        ?>
    </div>
</body>
</html>
