<?php
/**
 * 修复报价页面数据库字段问题
 * 检查并添加缺失的字段
 */
require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $results = [];
    
    // 检查 mobile_phones 表是否存在 image_url 字段
    $checkSql = "SELECT COUNT(*) as cnt FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = 'mobile_phones'
                AND column_name = 'image_url'";
    $checkResult = $conn->query($checkSql)->fetch(PDO::FETCH_ASSOC);
    
    if ($checkResult['cnt'] == 0) {
        // 添加 image_url 字段
        try {
            $alterSql = "ALTER TABLE mobile_phones 
                        ADD COLUMN image_url VARCHAR(500) DEFAULT NULL COMMENT '产品图片URL' AFTER note";
            $conn->exec($alterSql);
            $results[] = ['step' => '添加 image_url 字段', 'status' => 'success', 'message' => '成功添加 image_url 字段'];
        } catch (PDOException $e) {
            $results[] = ['step' => '添加 image_url 字段', 'status' => 'error', 'message' => '错误: ' . $e->getMessage()];
        }
    } else {
        $results[] = ['step' => '检查 image_url 字段', 'status' => 'skip', 'message' => 'image_url 字段已存在'];
    }
    
    // 检查 mobile_phones 表是否存在 image_path 字段
    $checkSql2 = "SELECT COUNT(*) as cnt FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                 AND table_name = 'mobile_phones'
                 AND column_name = 'image_path'";
    $checkResult2 = $conn->query($checkSql2)->fetch(PDO::FETCH_ASSOC);
    
    if ($checkResult2['cnt'] == 0) {
        // 添加 image_path 字段（可选，用于兼容）
        try {
            $alterSql2 = "ALTER TABLE mobile_phones 
                         ADD COLUMN image_path VARCHAR(500) DEFAULT NULL COMMENT '产品图片路径' AFTER image_url";
            $conn->exec($alterSql2);
            $results[] = ['step' => '添加 image_path 字段', 'status' => 'success', 'message' => '成功添加 image_path 字段'];
        } catch (PDOException $e) {
            $results[] = ['step' => '添加 image_path 字段', 'status' => 'error', 'message' => '错误: ' . $e->getMessage()];
        }
    } else {
        $results[] = ['step' => '检查 image_path 字段', 'status' => 'skip', 'message' => 'image_path 字段已存在'];
    }
    
    // 检查 tags 字段
    $checkSql3 = "SELECT COUNT(*) as cnt FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                 AND table_name = 'mobile_phones'
                 AND column_name = 'tags'";
    $checkResult3 = $conn->query($checkSql3)->fetch(PDO::FETCH_ASSOC);
    
    if ($checkResult3['cnt'] == 0) {
        try {
            $alterSql3 = "ALTER TABLE mobile_phones 
                         ADD COLUMN tags VARCHAR(255) DEFAULT NULL COMMENT '标签' AFTER note";
            $conn->exec($alterSql3);
            $results[] = ['step' => '添加 tags 字段', 'status' => 'success', 'message' => '成功添加 tags 字段'];
        } catch (PDOException $e) {
            $results[] = ['step' => '添加 tags 字段', 'status' => 'error', 'message' => '错误: ' . $e->getMessage()];
        }
    } else {
        $results[] = ['step' => '检查 tags 字段', 'status' => 'skip', 'message' => 'tags 字段已存在'];
    }
    
} catch (Exception $e) {
    $results[] = ['step' => '数据库连接', 'status' => 'error', 'message' => '错误: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修复报价页面数据库字段</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #E11D25; margin-top: 0; }
        .result-item { padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #ddd; }
        .success { background: #e8f5e9; border-color: #4caf50; }
        .error { background: #ffebee; border-color: #f44336; }
        .skip { background: #fff3e0; border-color: #ff9800; }
        .btn { display: inline-block; padding: 10px 20px; background: #E11D25; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 0 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔧 修复报价页面数据库字段</h2>
        
        <?php foreach ($results as $result): ?>
        <div class="result-item <?php echo $result['status']; ?>">
            <strong><?php echo htmlspecialchars($result['step']); ?>:</strong>
            <?php echo htmlspecialchars($result['message']); ?>
        </div>
        <?php endforeach; ?>
        
        <div style="margin-top: 30px;">
            <a href="quotes.php" class="btn">测试报价页面</a>
            <a href="index.php" class="btn">返回首页</a>
        </div>
    </div>
</body>
</html>
