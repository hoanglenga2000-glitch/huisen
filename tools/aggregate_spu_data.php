<?php
/**
 * ==========================================
 * SPU数据聚合工具 - 合并重复产品
 * ==========================================
 *
 * 功能：将同型号手机（不同颜色/容量）合并为一个SPU
 *
 * 合并逻辑：
 * - 从型号名中提取基础型号（去除颜色、容量描述）
 * - 按基础型号GROUP BY，生成新的聚合SPU
 * - 保留原始SKU数据，关联到新SPU
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');

// 基础型号提取函数
function extractBaseModel($modelName) {
    $original = $modelName;

    // 移除容量描述 (如: 1TG, 2TG, 256G, 512G, 1TB, 2TB 等)
    $modelName = preg_replace('/\s*\d+\s*(TG|TB|G|GB)\s*/i', ' ', $modelName);

    // 移除常见颜色词汇
    $colors = [
        // 中文颜色
        '黑色', '白色', '蓝色', '绿色', '紫色', '粉色', '金色', '银色', '灰色', '红色', '橙色', '青色', '棕色',
        '曜石黑', '雅丹黑', '羽砂黑', '深空黑', '钛金黑', '午夜色', '深空灰',
        '洛可可白', '羽砂白', '星光色', '凝霜白', '雪域白', '冰川白',
        '远峰蓝', '冰川蓝', '海蓝色', '深蓝色', '宝石蓝', '冰雪蓝', '冰晶蓝',
        '原野绿', '薄荷绿', '翡冷翠',
        '南糯紫', '丁香紫', '烟紫色', '羽砂紫',
        '樱花粉',
        '沙漠金', '玫瑰金',
        '星河银', '原色钛金属',
        '岩石灰', '钛金灰',
        '中国红',
        '琥珀棕',
        '雅川青',
        '星宇橙色', '星宇橙',
        // 英文颜色
        'Black', 'White', 'Blue', 'Green', 'Purple', 'Pink', 'Gold', 'Silver', 'Gray', 'Grey', 'Red', 'Orange',
        'Titanium', 'Natural', 'Desert', 'Starlight', 'Midnight', 'Space',
    ];

    foreach ($colors as $color) {
        $modelName = str_ireplace($color, '', $modelName);
    }

    // 移除"深"、"浅"等颜色修饰词（当它们出现在末尾时）
    $modelName = preg_replace('/\s*(深|浅|亮|暗)\s*$/u', '', $modelName);

    // 移除多余空格
    $modelName = preg_replace('/\s+/', ' ', $modelName);
    $modelName = trim($modelName);

    // 如果提取后为空，返回原始名称
    if (empty($modelName)) {
        return trim($original);
    }

    return $modelName;
}

// 测试模式
$testMode = isset($_GET['test']);
$executeMode = isset($_GET['execute']);

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $results = [];
    $aggregatedData = [];

    // 步骤1: 读取现有SPU数据
    $results[] = ['step' => '读取现有SPU数据', 'status' => 'info', 'message' => '正在分析...'];

    $stmt = $conn->query("SELECT * FROM products_spu_v3 ORDER BY brand, model_name");
    $existingSPUs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results[] = ['step' => '读取现有SPU数据', 'status' => 'success', 'message' => '共找到 ' . count($existingSPUs) . ' 条SPU记录'];

    // 步骤2: 按基础型号分组
    $groupedByBase = [];
    $baseModelMapping = [];

    foreach ($existingSPUs as $spu) {
        $baseModel = extractBaseModel($spu['model_name']);
        $key = $spu['brand'] . '|' . $baseModel;

        if (!isset($groupedByBase[$key])) {
            $groupedByBase[$key] = [
                'brand' => $spu['brand'],
                'base_model' => $baseModel,
                'category' => $spu['category'] ?? 'phone',
                'original_spus' => [],
                'min_price' => PHP_INT_MAX,
                'max_price' => 0,
                'sku_count' => 0,
                'image_url' => null,
                'gallery_images' => [],
            ];
        }

        $groupedByBase[$key]['original_spus'][] = $spu;
        $groupedByBase[$key]['min_price'] = min($groupedByBase[$key]['min_price'], floatval($spu['min_price']));
        $groupedByBase[$key]['max_price'] = max($groupedByBase[$key]['max_price'], floatval($spu['max_price']));
        $groupedByBase[$key]['sku_count'] += intval($spu['sku_count']);

        // 保留第一个有图片的SPU的图片
        if (empty($groupedByBase[$key]['image_url']) && !empty($spu['image_url'])) {
            $groupedByBase[$key]['image_url'] = $spu['image_url'];
        }

        // 收集所有图片
        if (!empty($spu['image_url'])) {
            $groupedByBase[$key]['gallery_images'][] = $spu['image_url'];
        }
        if (!empty($spu['gallery_images'])) {
            $gallery = json_decode($spu['gallery_images'], true);
            if (is_array($gallery)) {
                $groupedByBase[$key]['gallery_images'] = array_merge(
                    $groupedByBase[$key]['gallery_images'],
                    $gallery
                );
            }
        }

        $baseModelMapping[$spu['id']] = $key;
    }

    // 统计需要合并的数量
    $needMerge = 0;
    $mergeDetails = [];
    foreach ($groupedByBase as $key => $group) {
        if (count($group['original_spus']) > 1) {
            $needMerge++;
            $mergeDetails[] = [
                'base' => $group['base_model'],
                'brand' => $group['brand'],
                'count' => count($group['original_spus']),
                'models' => array_map(function($s) { return $s['model_name']; }, $group['original_spus'])
            ];
        }
    }

    $results[] = [
        'step' => '分析聚合需求',
        'status' => $needMerge > 0 ? 'warning' : 'success',
        'message' => "发现 {$needMerge} 组重复型号需要合并，聚合后将有 " . count($groupedByBase) . " 个独立SPU"
    ];

    // 显示合并详情
    if ($testMode && count($mergeDetails) > 0) {
        echo "<h3>需要合并的型号详情：</h3>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background:#f0f0f0;'><th>基础型号</th><th>品牌</th><th>重复数</th><th>原始型号列表</th></tr>";
        foreach ($mergeDetails as $detail) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($detail['base']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($detail['brand']) . "</td>";
            echo "<td style='text-align:center;'>{$detail['count']}</td>";
            echo "<td><ul style='margin:0;padding-left:20px;'>";
            foreach ($detail['models'] as $m) {
                echo "<li>" . htmlspecialchars($m) . "</li>";
            }
            echo "</ul></td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 步骤3: 执行聚合（仅在execute模式下）
    if ($executeMode) {
        $conn->beginTransaction();

        try {
            // 创建新的聚合SPU表
            $conn->exec("DROP TABLE IF EXISTS products_spu_v4");
            $conn->exec("
                CREATE TABLE products_spu_v4 (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    brand VARCHAR(100) NOT NULL,
                    model_name VARCHAR(255) NOT NULL COMMENT '基础型号名',
                    category VARCHAR(50) DEFAULT 'phone',
                    min_price DECIMAL(10,2) DEFAULT 0,
                    max_price DECIMAL(10,2) DEFAULT 0,
                    sku_count INT DEFAULT 0,
                    image_url VARCHAR(500),
                    gallery_images TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_brand (brand),
                    INDEX idx_category (category),
                    INDEX idx_min_price (min_price)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $results[] = ['step' => '创建新SPU表', 'status' => 'success', 'message' => '已创建 products_spu_v4 表'];

            // 创建SPU映射表（旧ID到新ID）
            $oldToNewSPU = [];

            // 插入聚合后的SPU数据
            $insertSPU = $conn->prepare("
                INSERT INTO products_spu_v4
                (brand, model_name, category, min_price, max_price, sku_count, image_url, gallery_images)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($groupedByBase as $key => $group) {
                // 去重gallery
                $gallery = array_unique($group['gallery_images']);
                $gallery = array_values(array_filter($gallery));

                $insertSPU->execute([
                    $group['brand'],
                    $group['base_model'],
                    $group['category'],
                    $group['min_price'] == PHP_INT_MAX ? 0 : $group['min_price'],
                    $group['max_price'],
                    $group['sku_count'],
                    $group['image_url'],
                    json_encode($gallery, JSON_UNESCAPED_UNICODE)
                ]);

                $newSpuId = $conn->lastInsertId();

                // 记录旧ID到新ID的映射
                foreach ($group['original_spus'] as $oldSpu) {
                    $oldToNewSPU[$oldSpu['id']] = $newSpuId;
                }
            }

            $results[] = ['step' => '插入聚合SPU', 'status' => 'success', 'message' => '已插入 ' . count($groupedByBase) . ' 条聚合SPU'];

            // 更新SKU表的spu_id引用
            $skuUpdateCount = 0;
            foreach ($oldToNewSPU as $oldId => $newId) {
                $updateStmt = $conn->prepare("UPDATE products_sku_v3 SET spu_id = ? WHERE spu_id = ?");
                $updateStmt->execute([$newId, $oldId]);
                $skuUpdateCount += $updateStmt->rowCount();
            }

            $results[] = ['step' => '更新SKU关联', 'status' => 'success', 'message' => "已更新 {$skuUpdateCount} 条SKU的SPU关联"];

            // 备份旧表并切换
            $conn->exec("RENAME TABLE products_spu_v3 TO products_spu_v3_backup");
            $conn->exec("RENAME TABLE products_spu_v4 TO products_spu_v3");

            $results[] = ['step' => '切换表名', 'status' => 'success', 'message' => '已将旧表备份为 products_spu_v3_backup，新表已启用'];

            $conn->commit();

            $results[] = ['step' => '聚合完成', 'status' => 'success', 'message' => '数据聚合成功！原有 ' . count($existingSPUs) . ' 条记录已合并为 ' . count($groupedByBase) . ' 条'];

        } catch (Exception $e) {
            $conn->rollBack();
            $results[] = ['step' => '执行失败', 'status' => 'error', 'message' => '错误: ' . $e->getMessage()];
        }
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
    <title>SPU数据聚合工具</title>
    <style>
        body {
            font-family: "Microsoft YaHei", Arial, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 { color: #E11D25; margin-top: 0; }
        .result-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #ddd;
        }
        .success { background: #e8f5e9; border-color: #4caf50; }
        .error { background: #ffebee; border-color: #f44336; }
        .warning { background: #fff3e0; border-color: #ff9800; }
        .info { background: #e3f2fd; border-color: #2196f3; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #E11D25;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px 0 0;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #c41820; }
        .btn-secondary { background: #666; }
        .btn-secondary:hover { background: #555; }
        .btn-success { background: #4caf50; }
        .btn-success:hover { background: #45a049; }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="container">
        <h2>SPU数据聚合工具</h2>
        <p>此工具将同型号手机（不同颜色/容量）合并为一个SPU，解决列表页重复显示问题。</p>

        <?php foreach ($results as $result): ?>
        <div class="result-item <?php echo $result['status']; ?>">
            <strong><?php echo htmlspecialchars($result['step']); ?>:</strong>
            <?php echo htmlspecialchars($result['message']); ?>
        </div>
        <?php endforeach; ?>

        <?php if (!$testMode && !$executeMode): ?>
        <div class="warning-box">
            <strong>操作说明：</strong>
            <ol>
                <li>点击「分析模式」查看哪些型号会被合并</li>
                <li>确认无误后，点击「执行聚合」进行实际合并</li>
                <li>原数据会备份到 products_spu_v3_backup 表</li>
            </ol>
        </div>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <?php if (!$executeMode): ?>
            <a href="?test=1" class="btn btn-secondary">分析模式（预览）</a>
            <a href="?execute=1" class="btn" onclick="return confirm('确定要执行聚合吗？此操作会修改数据库！');">执行聚合</a>
            <?php endif; ?>
            <a href="../core/quotes_v6.php" class="btn btn-success">查看列表页效果</a>
            <a href="check_database.php" class="btn btn-secondary">检查数据库</a>
        </div>
    </div>
</body>
</html>
