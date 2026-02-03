<?php
/**
 * ==========================================
 * SPU数据聚合工具 V2 - 智能型号合并
 * ==========================================
 *
 * 核心逻辑：只保留"品牌+基础型号"，后面的颜色、容量全部归为SKU变体
 *
 * 例如：
 * - "苹果17Pro Max 2TG星宇橙色" → "苹果17Pro Max"
 * - "苹果17Pro Max 1TG深" → "苹果17Pro Max"
 * - "苹果16Pro Max 原色" → "苹果16Pro Max"
 * - "华为Mate70 Pro+ 雅丹黑" → "华为Mate70 Pro+"
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');

// 基础型号提取函数 - 更激进的清洗
function extractBaseModel($modelName, $brand) {
    $original = $modelName;

    // 1. 移除容量/存储描述 (各种格式)
    $modelName = preg_replace('/\s*\d+\s*(TG|TB|G|GB|M|MB)\s*/i', ' ', $modelName);

    // 2. 完整的颜色词汇表（包括所有变体）
    $colors = [
        // 基础颜色
        '黑色', '白色', '蓝色', '绿色', '紫色', '粉色', '金色', '银色', '灰色', '红色', '橙色', '青色', '棕色', '黄色',
        '黑', '白', '蓝', '绿', '紫', '粉', '金', '银', '灰', '红', '橙', '青', '棕', '黄',

        // 苹果系列
        '曜石黑', '深空黑', '深空灰', '午夜色', '午夜黑', '星光色', '星光白', '远峰蓝', '沙漠金', '玫瑰金',
        '原色钛金属', '原色', '黑色钛金属', '白色钛金属', '蓝色钛金属', '自然色钛金属',
        '钛金属', '钛金黑', '钛金灰', '钛金白',

        // 华为系列
        '雅丹黑', '羽砂黑', '羽砂白', '羽砂紫', '洛可可白', '雅川青', '冰晶蓝', '冰雪蓝',
        '凝霜白', '雪域白', '冰川白', '冰川蓝', '曜金黑', '南糯紫',
        '昆仑霞光', '昆仑玻璃',

        // 小米/红米系列
        '星曜黑', '月影白', '松石青', '流光紫', '陶瓷黑', '陶瓷白',
        '晴雪', '墨羽', '白月光', '黑曜石', '光影银', '影青黑',

        // OPPO/vivo系列
        '极光蓝', '极地白', '云海白', '星穹灰', '燃', '沙漠色', '极地灰',
        '东方青', '大漠银月', '飞泉绿',

        // 三星系列
        '幻影黑', '幻影白', '幻影紫', '松林绿', '天际蓝', '雾松绿',

        // 荣耀系列
        '墨玉青', '冰峰白', '幻夜黑', '流光幻境', '钛空银',

        // 通用颜色词
        '星宇橙色', '星宇橙', '星宇', '深空', '海蓝色', '宝石蓝', '丁香紫', '烟紫色', '樱花粉',
        '琥珀棕', '翡冷翠', '薄荷绿', '原野绿', '星河银', '岩石灰', '中国红',
        '沙漠', '活力版', '素皮版', '玻璃版', '陶瓷版', '典藏版',

        // 英文颜色
        'Black', 'White', 'Blue', 'Green', 'Purple', 'Pink', 'Gold', 'Silver', 'Gray', 'Grey',
        'Red', 'Orange', 'Titanium', 'Natural', 'Desert', 'Starlight', 'Midnight', 'Space',
        'Graphite', 'Sierra', 'Alpine',
    ];

    foreach ($colors as $color) {
        $modelName = str_ireplace($color, '', $modelName);
    }

    // 3. 移除尾部的颜色/版本修饰词
    $modelName = preg_replace('/\s*(深|浅|亮|暗|色|版)\s*$/u', '', $modelName);

    // 4. 移除包装/封装描述
    $modelName = preg_replace('/\s*[\(（].*?[\)）]\s*/u', '', $modelName);
    $modelName = preg_replace('/\s*(原封|官换|未激活|激活|国行|港版|美版|日版|欧版)\s*/u', '', $modelName);

    // 5. 移除多余空格和符号
    $modelName = preg_replace('/\s+/', ' ', $modelName);
    $modelName = preg_replace('/[\s\-_]+$/', '', $modelName);
    $modelName = trim($modelName);

    // 如果提取后为空或太短，返回原始名称的前半部分
    if (empty($modelName) || mb_strlen($modelName) < 3) {
        // 尝试只取前15个字符作为基础型号
        return mb_substr(trim($original), 0, 15);
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

    // 步骤1: 读取原始SKU数据（从v3表）
    $results[] = ['step' => '读取原始数据', 'status' => 'info', 'message' => '正在分析...'];

    // 检查源表
    $source_spu = 'products_spu_v3';
    $source_sku = 'products_sku_v3';

    // 如果v3_backup存在，说明之前已经执行过，需要从backup恢复
    $backup_exists = $conn->query("SHOW TABLES LIKE 'products_spu_v3_backup'")->rowCount() > 0;
    if ($backup_exists) {
        $source_spu = 'products_spu_v3_backup';
    }

    $stmt = $conn->query("SELECT * FROM $source_spu ORDER BY brand, model_name");
    $existingSPUs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results[] = ['step' => '读取原始数据', 'status' => 'success', 'message' => "共找到 " . count($existingSPUs) . " 条原始SPU记录"];

    // 步骤2: 按基础型号分组
    $groupedByBase = [];
    $mappingDetails = [];

    foreach ($existingSPUs as $spu) {
        $baseModel = extractBaseModel($spu['model_name'], $spu['brand']);
        $key = $spu['brand'] . '|' . $baseModel;

        // 记录映射详情（用于调试）
        $mappingDetails[] = [
            'original' => $spu['model_name'],
            'base' => $baseModel,
            'brand' => $spu['brand']
        ];

        if (!isset($groupedByBase[$key])) {
            $groupedByBase[$key] = [
                'brand' => $spu['brand'],
                'base_model' => $baseModel,
                'category' => $spu['category'] ?? 'phone',
                'original_spus' => [],
                'original_spu_ids' => [],
                'min_price' => PHP_INT_MAX,
                'max_price' => 0,
                'sku_count' => 0,
                'images' => [],
            ];
        }

        $groupedByBase[$key]['original_spus'][] = $spu['model_name'];
        $groupedByBase[$key]['original_spu_ids'][] = $spu['id'];

        if ($spu['min_price'] > 0 && $spu['min_price'] < $groupedByBase[$key]['min_price']) {
            $groupedByBase[$key]['min_price'] = $spu['min_price'];
        }
        if ($spu['max_price'] > $groupedByBase[$key]['max_price']) {
            $groupedByBase[$key]['max_price'] = $spu['max_price'];
        }
        $groupedByBase[$key]['sku_count'] += ($spu['sku_count'] ?? 1);

        if (!empty($spu['image_url']) && !in_array($spu['image_url'], $groupedByBase[$key]['images'])) {
            $groupedByBase[$key]['images'][] = $spu['image_url'];
        }
    }

    // 统计合并情况
    $mergedCount = 0;
    $mergedGroups = [];
    foreach ($groupedByBase as $key => $group) {
        if (count($group['original_spus']) > 1) {
            $mergedCount++;
            $mergedGroups[$key] = $group['original_spus'];
        }
    }

    $originalCount = count($existingSPUs);
    $newCount = count($groupedByBase);
    $reducedCount = $originalCount - $newCount;

    $results[] = ['step' => '分析聚合需求', 'status' => 'success',
        'message' => "发现 $mergedCount 组重复型号需要合并，聚合后将有 $newCount 个独立SPU（减少 $reducedCount 个）"];

    // 测试模式：显示将要合并的详情
    if ($testMode) {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>SPU聚合预览</title>';
        echo '<script src="https://cdn.tailwindcss.com"></script></head>';
        echo '<body class="bg-gray-100 p-8"><div class="max-w-6xl mx-auto">';
        echo '<h1 class="text-2xl font-bold text-red-600 mb-4">SPU数据聚合预览</h1>';
        echo '<p class="mb-4 text-gray-600">原始: ' . $originalCount . ' → 聚合后: ' . $newCount . ' (减少 ' . $reducedCount . ')</p>';

        echo '<h2 class="text-xl font-bold mb-3">将被合并的型号组 (' . $mergedCount . ' 组)</h2>';
        echo '<div class="space-y-4">';

        $shown = 0;
        foreach ($mergedGroups as $key => $originals) {
            if ($shown >= 30) {
                echo '<p class="text-gray-500">...还有 ' . (count($mergedGroups) - 30) . ' 组未显示</p>';
                break;
            }

            list($brand, $baseModel) = explode('|', $key);
            echo '<div class="bg-white rounded-lg p-4 shadow-sm">';
            echo '<div class="font-bold text-green-600 mb-2">→ ' . htmlspecialchars($baseModel) . ' (' . $brand . ')</div>';
            echo '<div class="text-sm text-gray-500 space-y-1">';
            foreach ($originals as $orig) {
                echo '<div>• ' . htmlspecialchars($orig) . '</div>';
            }
            echo '</div></div>';
            $shown++;
        }

        echo '</div>';
        echo '<div class="mt-8">';
        echo '<a href="?execute=1" class="inline-block px-6 py-3 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700">确认执行聚合</a>';
        echo ' <a href="?" class="inline-block px-6 py-3 bg-gray-500 text-white rounded-lg font-bold hover:bg-gray-600 ml-2">返回</a>';
        echo '</div></div></body></html>';
        exit;
    }

    // 执行模式
    if ($executeMode) {
        // 步骤3: 创建新的SPU表
        $conn->exec("DROP TABLE IF EXISTS products_spu_v4");
        $conn->exec("
            CREATE TABLE products_spu_v4 (
                id INT AUTO_INCREMENT PRIMARY KEY,
                brand VARCHAR(100),
                model_name VARCHAR(255),
                category VARCHAR(50) DEFAULT 'phone',
                min_price DECIMAL(10,2) DEFAULT 0,
                max_price DECIMAL(10,2) DEFAULT 0,
                sku_count INT DEFAULT 0,
                image_url VARCHAR(500),
                gallery_images TEXT,
                original_spu_ids TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $results[] = ['step' => '创建新SPU表', 'status' => 'success', 'message' => '已创建 products_spu_v4 表'];

        // 步骤4: 插入聚合后的SPU
        $insertStmt = $conn->prepare("
            INSERT INTO products_spu_v4 (brand, model_name, category, min_price, max_price, sku_count, image_url, gallery_images, original_spu_ids)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $spuIdMapping = []; // old_spu_id => new_spu_id
        $insertedCount = 0;

        foreach ($groupedByBase as $key => $group) {
            if ($group['min_price'] == PHP_INT_MAX) {
                $group['min_price'] = 0;
            }

            $mainImage = $group['images'][0] ?? '';
            $galleryImages = json_encode(array_slice($group['images'], 0, 10));
            $originalIds = implode(',', $group['original_spu_ids']);

            $insertStmt->execute([
                $group['brand'],
                $group['base_model'],
                $group['category'],
                $group['min_price'],
                $group['max_price'],
                $group['sku_count'],
                $mainImage,
                $galleryImages,
                $originalIds
            ]);

            $newSpuId = $conn->lastInsertId();

            // 记录ID映射
            foreach ($group['original_spu_ids'] as $oldId) {
                $spuIdMapping[$oldId] = $newSpuId;
            }

            $insertedCount++;
        }

        $results[] = ['step' => '插入聚合SPU', 'status' => 'success', 'message' => "已插入 $insertedCount 条聚合SPU"];

        // 步骤5: 创建新的SKU表并更新关联
        $conn->exec("DROP TABLE IF EXISTS products_sku_v4");
        $conn->exec("
            CREATE TABLE products_sku_v4 (
                id INT AUTO_INCREMENT PRIMARY KEY,
                spu_id INT,
                full_name VARCHAR(255),
                color VARCHAR(100),
                storage VARCHAR(50),
                price DECIMAL(10,2),
                official_price DECIMAL(10,2) DEFAULT 0,
                stock_status VARCHAR(20) DEFAULT 'in_stock',
                original_sku_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_spu_id (spu_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 从原始SKU表复制数据并更新spu_id
        $skuStmt = $conn->query("SELECT * FROM $source_sku");
        $skus = $skuStmt->fetchAll(PDO::FETCH_ASSOC);

        $skuInsertStmt = $conn->prepare("
            INSERT INTO products_sku_v4 (spu_id, full_name, color, storage, price, official_price, stock_status, original_sku_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $skuUpdatedCount = 0;
        foreach ($skus as $sku) {
            $oldSpuId = $sku['spu_id'];
            $newSpuId = $spuIdMapping[$oldSpuId] ?? null;

            if ($newSpuId) {
                $skuInsertStmt->execute([
                    $newSpuId,
                    $sku['full_name'] ?? '',
                    $sku['color'] ?? '',
                    $sku['storage'] ?? '',
                    $sku['price'] ?? 0,
                    $sku['official_price'] ?? 0,
                    $sku['stock_status'] ?? 'in_stock',
                    $sku['id']
                ]);
                $skuUpdatedCount++;
            }
        }

        $results[] = ['step' => '更新SKU关联', 'status' => 'success', 'message' => "已迁移 $skuUpdatedCount 条SKU记录"];

        $results[] = ['step' => '聚合完成', 'status' => 'success',
            'message' => "原始 $originalCount 个SPU → 聚合后 $newCount 个SPU，减少了 $reducedCount 个重复"];
    }

} catch (Exception $e) {
    $results[] = ['step' => '错误', 'status' => 'error', 'message' => $e->getMessage()];
}

// 显示结果页面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPU数据聚合工具 V2</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto p-8">
        <h1 class="text-3xl font-bold text-red-600 mb-2">SPU数据聚合工具 V2</h1>
        <p class="text-gray-600 mb-8">智能合并同型号产品（只保留品牌+基础型号，颜色和容量变为SKU选项）</p>

        <?php if (!$testMode && !$executeMode): ?>
        <div class="bg-white rounded-xl p-6 shadow-sm mb-6">
            <h2 class="font-bold text-lg mb-4">操作说明</h2>
            <div class="space-y-3 text-gray-600">
                <p><strong>步骤1:</strong> 点击"预览聚合效果"查看哪些型号将被合并</p>
                <p><strong>步骤2:</strong> 确认无误后点击"确认执行聚合"</p>
                <p><strong>步骤3:</strong> 刷新列表页查看效果</p>
            </div>
        </div>

        <div class="flex gap-4">
            <a href="?test=1" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700">
                预览聚合效果
            </a>
            <a href="?execute=1" class="inline-block px-6 py-3 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700">
                直接执行聚合
            </a>
        </div>
        <?php endif; ?>

        <?php if ($executeMode): ?>
        <div class="space-y-3 mb-8">
            <?php foreach ($results as $r):
                $bgColor = match($r['status']) {
                    'success' => 'bg-green-100 border-green-500',
                    'error' => 'bg-red-100 border-red-500',
                    default => 'bg-blue-100 border-blue-500'
                };
            ?>
            <div class="<?php echo $bgColor; ?> border-l-4 p-4 rounded">
                <strong><?php echo htmlspecialchars($r['step']); ?>:</strong>
                <?php echo htmlspecialchars($r['message']); ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-4">
            <a href="/core/quotes_v6.php" class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700">
                查看列表页效果
            </a>
            <a href="?" class="inline-block px-6 py-3 bg-gray-500 text-white rounded-lg font-bold hover:bg-gray-600">
                返回
            </a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
