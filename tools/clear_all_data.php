<?php
/**
 * ============================================
 * 清空所有数据工具
 * ============================================
 * 警告：此操作将删除所有数据，请谨慎使用！
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>清空所有数据</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #E11D25; }
        .warning { color: #856404; padding: 15px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 4px; margin: 20px 0; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }
        .btn { display: inline-block; padding: 12px 30px; background: #E11D25; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px 0 0; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #C91A22; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #E11D25; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🗑️ 清空所有数据</h2>
        
        <?php
        $action = $_GET['action'] ?? '';
        $confirm = $_GET['confirm'] ?? '';
        
        try {
            $db = Database::getInstance();
            
            // 获取各表的数据量
            $tables = [
                'mobile_phones' => '手机报价表',
                'mobile_quotes' => '手机报价备用表',
                'phone_details' => '产品详情表',
                'business_stats' => '业务统计表',
            ];
            
            $stats = [];
            foreach ($tables as $table => $name) {
                try {
                    $result = $db->fetchOne("SELECT COUNT(*) as cnt FROM `{$table}`");
                    $stats[$table] = ['name' => $name, 'count' => $result['cnt']];
                } catch (Exception $e) {
                    $stats[$table] = ['name' => $name, 'count' => 0];
                }
            }
            
            if ($action === 'clear' && $confirm === 'yes') {
                echo '<div class="warning">';
                echo '<h3>⚠️ 正在清空数据...</h3>';
                echo '</div>';
                
                $cleared = [];
                $errors = [];
                
                foreach ($tables as $table => $name) {
                    try {
                        $db->query("DELETE FROM `{$table}`");
                        $cleared[] = $name . " ({$table})";
                    } catch (Exception $e) {
                        $errors[] = $name . ": " . $e->getMessage();
                    }
                }
                
                echo '<div class="success">';
                echo '<h3>✅ 清空完成！</h3>';
                echo '<p>已清空以下表的数据：</p>';
                echo '<ul>';
                foreach ($cleared as $item) {
                    echo '<li>' . htmlspecialchars($item) . '</li>';
                }
                echo '</ul>';
                
                if (!empty($errors)) {
                    echo '<div class="error">';
                    echo '<p>以下表清空失败：</p>';
                    echo '<ul>';
                    foreach ($errors as $error) {
                        echo '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                
                echo '<p style="margin-top: 20px;">';
                echo '<a href="import_database.php" class="btn">重新导入数据库</a> ';
                echo '<a href="quotes.php" class="btn btn-secondary">前往报价页面</a>';
                echo '</p>';
                echo '</div>';
                
            } else {
                // 显示当前数据统计
                echo '<div class="warning">';
                echo '<h3>⚠️ 警告：此操作将删除所有数据！</h3>';
                echo '<p>请确认您要清空以下表的所有数据：</p>';
                echo '</div>';
                
                echo '<table>';
                echo '<tr><th>表名</th><th>说明</th><th>当前记录数</th></tr>';
                foreach ($stats as $table => $info) {
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($table) . '</code></td>';
                    echo '<td>' . htmlspecialchars($info['name']) . '</td>';
                    echo '<td><strong>' . $info['count'] . '</strong> 条</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                echo '<div class="warning">';
                echo '<p><strong>⚠️ 重要提示：</strong></p>';
                echo '<ul>';
                echo '<li>此操作不可恢复！</li>';
                echo '<li>清空后，您需要重新导入数据</li>';
                echo '<li>建议先备份数据库</li>';
                echo '</ul>';
                echo '</div>';
                
                echo '<div style="margin-top: 20px;">';
                echo '<a href="?action=clear&confirm=yes" class="btn btn-danger" onclick="return confirm(\'⚠️ 警告！\\n\\n确定要清空所有数据吗？\\n\\n此操作不可恢复！\')">确认清空所有数据</a> ';
                echo '<a href="quotes.php" class="btn btn-secondary">取消，返回报价页面</a>';
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<h3>❌ 错误</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
