<?php
/**
 * ============================================
 * 甘肃汇森 - 数据库一键导入工具
 * ============================================
 * 
 * 功能：自动导入所有 SQL 文件到数据库
 * 使用方法：在浏览器中访问此文件即可
 * 
 * 安全提示：
 * 1. 导入完成后请删除或重命名此文件
 * 2. 建议在生产环境中添加密码保护
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 引入配置文件
require_once __DIR__ . '/config/config.php';

// 安全验证（可选：设置导入密码）
$IMPORT_PASSWORD = ''; // 留空则无需密码，或设置密码如 'your_password_here'

// 检查密码（如果设置了密码）
if (!empty($IMPORT_PASSWORD)) {
    $provided_password = $_GET['password'] ?? '';
    if ($provided_password !== $IMPORT_PASSWORD) {
        die('<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库导入 - 需要密码</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 20px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #E11D25; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #C91A22; }
    </style>
</head>
<body>
    <h2>数据库导入工具</h2>
    <p>请输入导入密码：</p>
    <form method="GET">
        <div class="form-group">
            <label>密码：</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">确认导入</button>
    </form>
</body>
</html>');
    }
}

// 获取 SQL 文件列表
$sql_dir = __DIR__ . '/sql/';
$sql_files = [];

if (is_dir($sql_dir)) {
    $files = scandir($sql_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $sql_files[] = $file;
        }
    }
            // 按文件名排序，确保导入顺序
            // 优先导入：init.sql, phone_details.sql, complete_schema_update.sql, update_schema.sql, insert_phone_data.sql
            $priority = ['init.sql', 'phone_details.sql', 'complete_schema_update.sql', 'update_schema.sql', 'insert_phone_data.sql'];
    usort($sql_files, function($a, $b) use ($priority) {
        $a_priority = array_search($a, $priority);
        $b_priority = array_search($b, $priority);
        if ($a_priority !== false && $b_priority !== false) {
            return $a_priority - $b_priority;
        }
        if ($a_priority !== false) return -1;
        if ($b_priority !== false) return 1;
        return strcmp($a, $b);
    });
}

// 如果没有 SQL 文件
if (empty($sql_files)) {
    die('<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>数据库导入 - 错误</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .error { background: #fee; border: 1px solid #fcc; padding: 15px; border-radius: 4px; color: #c33; }
    </style>
</head>
<body>
    <h2>数据库导入工具</h2>
    <div class="error">
        <strong>错误：</strong>未找到 SQL 文件！<br>
        请确保 sql/ 目录中存在以下文件：<br>
        <ul>
            <li>init.sql</li>
            <li>phone_details.sql</li>
            <li>update_schema.sql</li>
        </ul>
    </div>
</body>
</html>');
}

// 处理导入请求
$action = $_GET['action'] ?? '';

if ($action === 'import') {
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库导入中...</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #E11D25; margin-top: 0; }
        .log { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto; font-family: "Courier New", monospace; font-size: 13px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #007bff; }
        .warning { color: #ffc107; }
        .progress { margin: 20px 0; }
        .progress-bar { background: #e0e0e0; height: 30px; border-radius: 15px; overflow: hidden; }
        .progress-fill { background: linear-gradient(90deg, #E11D25, #C91A22); height: 100%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📦 数据库导入工具</h2>
        <div class="progress">
            <div class="progress-bar">
                <div class="progress-fill" id="progress" style="width: 0%">0%</div>
            </div>
        </div>
        <div class="log" id="log">';
    
    flush();
    
    try {
        // 连接数据库（不指定数据库名，先创建数据库）
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ]
        );
        
        echo '<div class="info">✓ 已连接到 MySQL 服务器</div>';
        flush();
        
        // 创建数据库（如果不存在）
        $db_name = DB_NAME;
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo '<div class="success">✓ 数据库 "' . htmlspecialchars($db_name) . '" 已准备就绪</div>';
        flush();
        
        // 选择数据库
        $pdo->exec("USE `{$db_name}`");
        echo '<div class="info">✓ 已切换到数据库 "' . htmlspecialchars($db_name) . '"</div><br>';
        flush();
        
        $total_files = count($sql_files);
        $success_count = 0;
        $error_count = 0;
        
        // 逐个导入 SQL 文件
        foreach ($sql_files as $index => $file) {
            $file_path = $sql_dir . $file;
            $progress = round(($index + 1) / $total_files * 100);
            
            echo '<script>document.getElementById("progress").style.width="' . $progress . '%"; document.getElementById("progress").textContent="' . $progress . '%";</script>';
            flush();
            
            echo '<div class="info">📄 正在导入: ' . htmlspecialchars($file) . '...</div>';
            flush();
            
            if (!file_exists($file_path)) {
                echo '<div class="error">✗ 文件不存在: ' . htmlspecialchars($file) . '</div>';
                $error_count++;
                continue;
            }
            
            // 读取 SQL 文件内容
            $sql_content = file_get_contents($file_path);
            if ($sql_content === false) {
                echo '<div class="error">✗ 无法读取文件: ' . htmlspecialchars($file) . '</div>';
                $error_count++;
                continue;
            }
            
            // 移除 BOM
            $sql_content = preg_replace('/^\xEF\xBB\xBF/', '', $sql_content);
            
            // 分割 SQL 语句（按分号和换行）
            $statements = array_filter(
                array_map('trim', preg_split('/;\s*\n/', $sql_content)),
                function($stmt) {
                    return !empty($stmt) && 
                           !preg_match('/^\s*--/', $stmt) && 
                           !preg_match('/^\s*\/\*/', $stmt);
                }
            );
            
            $file_success = true;
            $stmt_count = 0;
            
            foreach ($statements as $statement) {
                // 跳过注释和空语句
                $statement = trim($statement);
                if (empty($statement) || 
                    preg_match('/^\s*--/', $statement) || 
                    preg_match('/^\s*\/\*/', $statement) ||
                    strtoupper(substr($statement, 0, 2)) === '--') {
                    continue;
                }
                
                try {
                    // 执行 SQL 语句
                    $pdo->exec($statement);
                    $stmt_count++;
                } catch (PDOException $e) {
                    // 忽略某些错误（如表已存在、列已存在等）
                    $error_code = $e->getCode();
                    $error_msg = $e->getMessage();
                    
                    // 1050: 表已存在, 1060: 重复列名, 1061: 重复键名
                    if (!in_array($error_code, ['42S01', '42S21', '42S22']) && 
                        strpos($error_msg, 'already exists') === false &&
                        strpos($error_msg, 'Duplicate') === false) {
                        echo '<div class="warning">⚠ SQL 执行警告: ' . htmlspecialchars(substr($statement, 0, 100)) . '...<br>错误: ' . htmlspecialchars($error_msg) . '</div>';
                        flush();
                    }
                }
            }
            
            if ($file_success) {
                echo '<div class="success">✓ ' . htmlspecialchars($file) . ' 导入成功 (' . $stmt_count . ' 条语句)</div>';
                $success_count++;
            } else {
                $error_count++;
            }
            
            echo '<br>';
            flush();
        }
        
        // 显示总结
        echo '</div><br>';
        echo '<div style="background: #e8f5e9; border: 1px solid #4caf50; padding: 15px; border-radius: 4px; margin-top: 20px;">';
        echo '<h3 style="margin-top: 0; color: #2e7d32;">✅ 导入完成！</h3>';
        echo '<p><strong>成功导入:</strong> ' . $success_count . ' 个文件</p>';
        if ($error_count > 0) {
            echo '<p><strong>失败:</strong> ' . $error_count . ' 个文件</p>';
        }
        echo '<p style="margin-bottom: 0;"><strong>⚠️ 安全提示:</strong> 导入完成后，请删除或重命名此文件 (import_database.php) 以确保安全！</p>';
        echo '</div>';
        
        echo '<div style="margin-top: 20px;">';
        echo '<a href="quotes.php" style="display: inline-block; padding: 10px 20px; background: #E11D25; color: white; text-decoration: none; border-radius: 4px;">前往报价页面</a> ';
        echo '<a href="dashboard.php" style="display: inline-block; padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 4px; margin-left: 10px;">前往管理后台</a>';
        echo '</div>';
        
    } catch (PDOException $e) {
        echo '<div class="error">✗ 数据库连接失败: ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<div class="error">请检查 config/config.php 中的数据库配置</div>';
    } catch (Exception $e) {
        echo '<div class="error">✗ 发生错误: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    echo '</div></div></body></html>';
    exit;
}

// 显示导入确认页面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库导入工具 - 甘肃汇森</title>
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
        .header p {
            opacity: 0.9;
            font-size: 14px;
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
        .file-list {
            list-style: none;
            margin: 15px 0;
        }
        .file-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
        }
        .file-list li:before {
            content: "📄";
            margin-right: 10px;
            font-size: 18px;
        }
        .db-info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .db-info strong {
            color: #1976d2;
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
            <h1>📦 数据库一键导入工具</h1>
            <p>甘肃汇森信息科技有限公司</p>
        </div>
        
        <div class="content">
            <div class="info-box">
                <h3>📋 将要导入的 SQL 文件</h3>
                <ul class="file-list">
                    <?php foreach ($sql_files as $file): ?>
                        <li><?= htmlspecialchars($file) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="db-info">
                <strong>数据库配置信息：</strong><br>
                主机: <?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?><br>
                数据库名: <?= htmlspecialchars(DB_NAME) ?><br>
                用户名: <?= htmlspecialchars(DB_USER) ?>
            </div>
            
            <div class="warning-box">
                <strong>⚠️ 重要提示：</strong><br>
                1. 导入过程将执行所有 SQL 文件中的语句<br>
                2. 如果表已存在，将跳过创建（不会覆盖现有数据）<br>
                3. 导入完成后请删除或重命名此文件以确保安全<br>
                4. 建议在导入前备份现有数据库
            </div>
            
            <form method="GET" action="">
                <input type="hidden" name="action" value="import">
                <?php if (!empty($IMPORT_PASSWORD)): ?>
                    <input type="hidden" name="password" value="<?= htmlspecialchars($_GET['password'] ?? '') ?>">
                <?php endif; ?>
                <button type="submit" class="btn" onclick="return confirm('确定要开始导入数据库吗？\\n\\n这将执行所有 SQL 文件。')">
                    🚀 开始导入数据库
                </button>
            </form>
            
            <a href="quotes.php" class="btn btn-secondary">返回报价页面</a>
        </div>
    </div>
</body>
</html>
