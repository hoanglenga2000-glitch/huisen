<?php
/**
 * ============================================
 * 导出本地数据库数据工具
 * ============================================
 * 功能：将本地数据库中的 mobile_phones 表数据导出为 SQL 文件
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>导出本地数据</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #E11D25; margin-top: 0; }
        .section { margin: 20px 0; padding: 20px; background: #f9f9f9; border-left: 4px solid #E11D25; border-radius: 4px; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }
        .info { color: #007bff; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0; }
        .btn { display: inline-block; padding: 12px 30px; background: #E11D25; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px 0 0; }
        .btn:hover { background: #C91A22; }
        .btn-secondary { background: #6c757d; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #E11D25; color: white; }
        .count { font-size: 24px; font-weight: bold; color: #E11D25; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📤 导出本地数据库数据</h2>
        
        <?php
        $action = $_GET['action'] ?? '';
        
        try {
            $db = Database::getInstance();
            
            // 检查数据量
            $count = $db->fetchOne("SELECT COUNT(*) as cnt FROM mobile_phones WHERE brand != '未分类'");
            $total_count = $count['cnt'];
            
            echo '<div class="section">';
            echo '<h3>📊 本地数据统计</h3>';
            echo '<p>mobile_phones 表共有 <span class="count">' . $total_count . '</span> 条数据</p>';
            
            if ($total_count == 0) {
                echo '<div class="error">本地数据库中没有数据！</div>';
                echo '</div>';
                exit;
            }
            
            // 按品牌统计
            $brands = $db->fetchAll("SELECT brand, COUNT(*) as cnt FROM mobile_phones WHERE brand != '未分类' GROUP BY brand ORDER BY cnt DESC");
            echo '<table>';
            echo '<tr><th>品牌</th><th>数量</th></tr>';
            foreach ($brands as $b) {
                echo '<tr><td>' . htmlspecialchars($b['brand']) . '</td><td>' . $b['cnt'] . '</td></tr>';
            }
            echo '</table>';
            echo '</div>';
            
            if ($action === 'export') {
                echo '<div class="section">';
                echo '<h3>🔄 正在导出数据...</h3>';
                
                // 获取所有数据
                $data = $db->fetchAll("SELECT * FROM mobile_phones WHERE brand != '未分类' ORDER BY brand, model, price");
                
                // 获取表结构
                $columns = $db->fetchAll("SHOW COLUMNS FROM mobile_phones");
                $column_names = array_column($columns, 'Field');
                
                // 生成 SQL 文件内容
                $sql_content = "-- ============================================\n";
                $sql_content .= "-- 甘肃汇森 - 手机数据完整导出\n";
                $sql_content .= "-- 导出时间: " . date('Y-m-d H:i:s') . "\n";
                $sql_content .= "-- 数据条数: " . count($data) . " 条\n";
                $sql_content .= "-- ============================================\n\n";
                
                $sql_content .= "-- 清空现有数据（可选）\n";
                $sql_content .= "-- TRUNCATE TABLE `mobile_phones`;\n\n";
                
                $sql_content .= "-- ============================================\n";
                $sql_content .= "-- 插入手机数据\n";
                $sql_content .= "-- ============================================\n";
                $sql_content .= "INSERT INTO `mobile_phones` \n";
                
                // 构建字段列表
                $fields = [];
                foreach ($column_names as $col) {
                    if ($col !== 'id') { // 排除自增ID
                        $fields[] = "`{$col}`";
                    }
                }
                $sql_content .= "(" . implode(", ", $fields) . ") \n";
                $sql_content .= "VALUES \n";
                
                // 构建值列表
                $values = [];
                foreach ($data as $row) {
                    $vals = [];
                    foreach ($column_names as $col) {
                        if ($col === 'id') continue;
                        
                        $value = $row[$col];
                        if ($value === null) {
                            $vals[] = 'NULL';
                        } elseif (is_numeric($value)) {
                            $vals[] = $value;
                        } else {
                            // 转义单引号
                            $value = str_replace("'", "''", $value);
                            $vals[] = "'" . $value . "'";
                        }
                    }
                    $values[] = "(" . implode(", ", $vals) . ")";
                }
                
                $sql_content .= implode(",\n", $values) . ";\n\n";
                
                $sql_content .= "-- ============================================\n";
                $sql_content .= "-- 查询验证\n";
                $sql_content .= "-- ============================================\n";
                $sql_content .= "-- SELECT COUNT(*) as total FROM mobile_phones WHERE brand != '未分类';\n";
                $sql_content .= "-- SELECT brand, COUNT(*) as cnt FROM mobile_phones WHERE brand != '未分类' GROUP BY brand ORDER BY cnt DESC;\n";
                
                // 保存到文件
                $export_file = __DIR__ . '/sql/export_mobile_phones_' . date('Ymd_His') . '.sql';
                file_put_contents($export_file, $sql_content);
                
                echo '<div class="success">';
                echo '<h3>✅ 导出成功！</h3>';
                echo '<p>已导出 <strong>' . count($data) . '</strong> 条数据</p>';
                echo '<p>文件保存位置：<code>sql/' . htmlspecialchars(basename($export_file)) . '</code></p>';
                echo '</div>';
                
                // 显示文件预览（前1000字符）
                echo '<div class="section">';
                echo '<h3>📄 SQL 文件预览（前1000字符）</h3>';
                echo '<pre>' . htmlspecialchars(substr($sql_content, 0, 1000)) . '...</pre>';
                echo '</div>';
                
                // 提供下载链接
                echo '<div class="section">';
                echo '<h3>📥 下载 SQL 文件</h3>';
                echo '<p>';
                $download_url = 'sql/' . basename($export_file);
                echo '<a href="' . htmlspecialchars($download_url) . '" class="btn" download>下载 SQL 文件</a> ';
                echo '<a href="import_database.php" class="btn btn-secondary">前往导入工具</a>';
                echo '</p>';
                echo '<p class="info">💡 提示：下载后，可以将此文件上传到宝塔面板的 sql/ 目录，然后通过 phpMyAdmin 或 import_database.php 导入</p>';
                echo '</div>';
                
            } else {
                echo '<div class="section">';
                echo '<h3>🚀 导出选项</h3>';
                echo '<p>点击下方按钮开始导出所有手机数据为 SQL 文件</p>';
                echo '<a href="?action=export" class="btn">开始导出数据</a>';
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
            <h3>📋 使用说明</h3>
            <ol>
                <li>点击"开始导出数据"按钮</li>
                <li>等待导出完成，下载生成的 SQL 文件</li>
                <li>将 SQL 文件上传到宝塔面板的 <code>sql/</code> 目录</li>
                <li>访问 <a href="import_database.php">import_database.php</a> 导入数据</li>
                <li>或者直接在 phpMyAdmin 中执行 SQL 文件</li>
            </ol>
        </div>
    </div>
</body>
</html>
