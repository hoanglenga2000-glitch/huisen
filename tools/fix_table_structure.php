<?php
/**
 * ============================================
 * 修复表结构工具
 * ============================================
 * 功能：先更新表结构，再导入数据
 * 解决 image_url 字段不存在的问题
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修复表结构</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #E11D25; margin-top: 0; }
        .log { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto; font-family: "Courier New", monospace; font-size: 13px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #007bff; }
        .warning { color: #ffc107; }
        .btn { display: inline-block; padding: 12px 30px; background: #E11D25; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px 0 0; }
        .btn:hover { background: #C91A22; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔧 修复表结构工具</h2>
        
        <?php
        $action = $_GET['action'] ?? '';
        
        if ($action === 'fix') {
            echo '<div class="log">';
            flush();
            
            try {
                $db = Database::getInstance();
                $pdo = $db->getConnection();
                
                echo '<div class="info">✓ 已连接到数据库</div>';
                flush();
                
                // 1. 添加所有必需字段（从导出的 SQL 文件中提取的字段列表）
                echo '<div class="info">📋 步骤 1: 更新 mobile_phones 表结构...</div>';
                flush();
                $required_fields = [
                    ['name' => 'detail_id', 'type' => 'INT(11) UNSIGNED', 'default' => 'NULL', 'comment' => '关联 phone_details.id', 'after' => 'id'],
                    ['name' => 'image_url', 'type' => 'VARCHAR(500)', 'default' => 'NULL', 'comment' => '产品图片URL', 'after' => 'note'],
                    ['name' => 'product_link', 'type' => 'VARCHAR(500)', 'default' => 'NULL', 'comment' => '产品链接', 'after' => 'image_url'],
                    ['name' => 'official_price', 'type' => 'DECIMAL(10, 2)', 'default' => 'NULL', 'comment' => '官方价格', 'after' => 'price'],
                    ['name' => 'retail_price', 'type' => 'DECIMAL(10, 2)', 'default' => 'NULL', 'comment' => '官网零售价', 'after' => 'price'],
                    ['name' => 'wholesale_price', 'type' => 'DECIMAL(10, 2)', 'default' => 'NULL', 'comment' => '批发价', 'after' => 'price'],
                    ['name' => 'specs_json', 'type' => 'TEXT', 'default' => 'NULL', 'comment' => '规格JSON数据', 'after' => 'spec'],
                    ['name' => 'intro_images', 'type' => 'TEXT', 'default' => 'NULL', 'comment' => '介绍图片JSON数组', 'after' => 'image_url'],
                    ['name' => 'description', 'type' => 'TEXT', 'default' => 'NULL', 'comment' => '产品描述', 'after' => 'note'],
                    ['name' => 'detail_description', 'type' => 'TEXT', 'default' => 'NULL', 'comment' => '详细描述', 'after' => 'description'],
                    ['name' => 'detail_highlights', 'type' => 'TEXT', 'default' => 'NULL', 'comment' => '产品亮点JSON数组', 'after' => 'detail_description'],
                    ['name' => 'detail_specs_full', 'type' => 'TEXT', 'default' => 'NULL', 'comment' => '完整规格JSON', 'after' => 'detail_highlights'],
                    ['name' => 'detail_images', 'type' => 'TEXT', 'default' => 'NULL', 'comment' => '详情图片JSON数组', 'after' => 'detail_specs_full'],
                    ['name' => 'source_url', 'type' => 'VARCHAR(500)', 'default' => 'NULL', 'comment' => '来源URL', 'after' => 'detail_images'],
                    ['name' => 'data_source', 'type' => 'VARCHAR(50)', 'default' => 'NULL', 'comment' => '数据来源', 'after' => 'source_url'],
                    ['name' => 'last_updated', 'type' => 'TIMESTAMP', 'default' => 'NULL', 'comment' => '最后更新时间', 'after' => 'updated_at'],
                    ['name' => 'cover_image', 'type' => 'VARCHAR(500)', 'default' => 'NULL', 'comment' => '封面图片URL', 'after' => 'image_url'],
                    ['name' => 'normalized_model', 'type' => 'VARCHAR(150)', 'default' => 'NULL', 'comment' => '标准化型号名称', 'after' => 'model'],
                ];
                
                foreach ($required_fields as $field) {
                    try {
                        // 检查字段是否存在
                        $check = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.columns 
                            WHERE table_schema = DATABASE() 
                            AND table_name = 'mobile_phones' 
                            AND column_name = '{$field['name']}'")->fetch();
                        
                        if ($check['cnt'] > 0) {
                            echo '<div class="warning">⚠ 字段 `' . $field['name'] . '` 已存在，跳过</div>';
                            flush();
                            continue;
                        }
                        
                        // 构建 ALTER TABLE 语句
                        $after_clause = !empty($field['after']) ? " AFTER `{$field['after']}`" : '';
                        $default_clause = $field['default'] === 'NULL' ? ' DEFAULT NULL' : " DEFAULT {$field['default']}";
                        $sql = "ALTER TABLE `mobile_phones` ADD COLUMN `{$field['name']}` {$field['type']}{$default_clause} COMMENT '{$field['comment']}'{$after_clause}";
                        
                        $pdo->exec($sql);
                        echo '<div class="success">✓ 已添加字段 `' . $field['name'] . '`</div>';
                        flush();
                    } catch (PDOException $e) {
                        // 忽略已存在的错误
                        if (strpos($e->getMessage(), 'Duplicate') !== false || 
                            strpos($e->getMessage(), 'already exists') !== false) {
                            echo '<div class="warning">⚠ 字段 `' . $field['name'] . '`: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        } else {
                            echo '<div class="error">❌ 添加字段 `' . $field['name'] . '` 失败: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                        flush();
                    }
                }
                
                // 添加索引
                $indexes = [
                    ['name' => 'idx_detail_id', 'columns' => 'detail_id'],
                    ['name' => 'idx_retail_price', 'columns' => 'retail_price'],
                ];
                
                foreach ($indexes as $index) {
                    try {
                        $check = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.statistics 
                            WHERE table_schema = DATABASE() 
                            AND table_name = 'mobile_phones' 
                            AND index_name = '{$index['name']}'")->fetch();
                        
                        if ($check['cnt'] > 0) {
                            echo '<div class="warning">⚠ 索引 `' . $index['name'] . '` 已存在，跳过</div>';
                            flush();
                            continue;
                        }
                        
                        $sql = "ALTER TABLE `mobile_phones` ADD INDEX `{$index['name']}` (`{$index['columns']}`)";
                        $pdo->exec($sql);
                        echo '<div class="success">✓ 已添加索引 `' . $index['name'] . '`</div>';
                        flush();
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'Duplicate') !== false) {
                            echo '<div class="warning">⚠ 索引 `' . $index['name'] . '` 已存在</div>';
                        } else {
                            echo '<div class="error">❌ 添加索引 `' . $index['name'] . '` 失败: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                        flush();
                    }
                }
                
                // 2. 验证表结构
                echo '<div class="info">📋 步骤 2: 验证表结构...</div>';
                flush();
                
                $columns = $pdo->query("SHOW COLUMNS FROM mobile_phones")->fetchAll(PDO::FETCH_COLUMN);
                $required_fields = [
                    'detail_id', 'image_url', 'product_link', 'official_price', 'retail_price', 
                    'wholesale_price', 'specs_json', 'intro_images', 'description', 
                    'detail_description', 'detail_highlights', 'detail_specs_full', 'detail_images',
                    'source_url', 'data_source', 'last_updated', 'cover_image', 'normalized_model'
                ];
                $missing_fields = [];
                
                foreach ($required_fields as $field) {
                    if (!in_array($field, $columns)) {
                        $missing_fields[] = $field;
                    }
                }
                
                if (empty($missing_fields)) {
                    echo '<div class="success">✅ 所有必需字段已存在！</div>';
                    echo '<div class="info">当前表共有 ' . count($columns) . ' 个字段</div>';
                } else {
                    echo '<div class="error">❌ 缺少字段: ' . implode(', ', $missing_fields) . '</div>';
                    echo '<div class="warning">请重新运行修复工具或手动执行 sql/complete_schema_update.sql</div>';
                }
                flush();
                
                echo '</div>';
                echo '<div style="background: #e8f5e9; border: 1px solid #4caf50; padding: 15px; border-radius: 4px; margin-top: 20px;">';
                echo '<h3 style="margin-top: 0; color: #2e7d32;">✅ 表结构修复完成！</h3>';
                echo '<p>现在可以安全导入数据文件了。</p>';
                echo '<p style="margin-bottom: 0;"><a href="import_database.php" class="btn">前往导入数据</a></p>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="error">❌ 错误: ' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '</div>';
            }
        } else {
            echo '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 20px 0;">';
            echo '<h3>⚠️ 问题说明</h3>';
            echo '<p>导入数据时出现错误：<code>Unknown column \'image_url\' in \'field list\'</code></p>';
            echo '<p>这是因为服务器上的表结构缺少某些字段。此工具将自动添加缺失的字段。</p>';
            echo '</div>';
            
            echo '<div style="background: #e3f2fd; border: 1px solid #90caf9; padding: 15px; border-radius: 4px; margin: 20px 0;">';
            echo '<h3>📋 将执行的操作</h3>';
            echo '<p>将添加以下所有缺失的字段（共18个字段）：</p>';
            echo '<ul style="columns: 2; column-gap: 20px;">';
            echo '<li><code>detail_id</code></li>';
            echo '<li><code>image_url</code></li>';
            echo '<li><code>product_link</code></li>';
            echo '<li><code>official_price</code></li>';
            echo '<li><code>retail_price</code></li>';
            echo '<li><code>wholesale_price</code></li>';
            echo '<li><code>specs_json</code></li>';
            echo '<li><code>intro_images</code></li>';
            echo '<li><code>description</code></li>';
            echo '<li><code>detail_description</code></li>';
            echo '<li><code>detail_highlights</code></li>';
            echo '<li><code>detail_specs_full</code></li>';
            echo '<li><code>detail_images</code></li>';
            echo '<li><code>source_url</code></li>';
            echo '<li><code>data_source</code></li>';
            echo '<li><code>last_updated</code></li>';
            echo '<li><code>cover_image</code></li>';
            echo '<li><code>normalized_model</code></li>';
            echo '</ul>';
            echo '<p>以及相关索引</p>';
            echo '</div>';
            
            echo '<p><a href="?action=fix" class="btn" onclick="return confirm(\'确定要修复表结构吗？\')">🔧 开始修复表结构</a></p>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="import_database.php">返回导入工具</a> | 
            <a href="quotes.php">返回报价页面</a>
        </div>
    </div>
</body>
</html>
