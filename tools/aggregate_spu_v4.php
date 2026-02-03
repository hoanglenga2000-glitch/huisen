<?php
/**
 * ==========================================
 * SPU数据聚合工具 V4 - 终极智能合并
 * ==========================================
 *
 * 核心逻辑：提取"品牌+核心系列型号"，完全忽略后面的配置描述
 *
 * 例如：
 * - "17Pro Max 2TG星宇橙色" → "17Pro Max"
 * - "17Pro Max 1TG深空黑" → "17Pro Max"
 * - "Mate70 Pro+ 雅丹黑 512G" → "Mate70 Pro+"
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');

/**
 * 提取核心型号名 - 终极版
 * 只保留手机系列名称，彻底去除所有颜色、容量、版本
 */
function extractCoreModel($modelName, $brand) {
    $original = $modelName;
    $name = trim($modelName);

    // 1. 首先移除容量描述 (必须先做，因为容量通常在型号后面)
    // 匹配: 2TG, 1TB, 512G, 256GB, 128M 等
    $name = preg_replace('/\s*\d+\s*(TG|TB|G|GB|M|MB)\b/i', '', $name);

    // 2. 移除所有括号及内容
    $name = preg_replace('/[\(（\[【].*?[\)）\]】]/u', '', $name);

    // 3. 超级完整的颜色词库 - 从长到短排序（避免部分匹配问题）
    $colorWords = [
        // 复合颜色名（长的先匹配）
        '原色钛金属', '黑色钛金属', '白色钛金属', '蓝色钛金属', '自然色钛金属',
        '深空黑色', '深空灰色', '午夜黑色', '星光白色', '远峰蓝色',
        '星宇橙色', '海蓝色', '宝石蓝色', '丁香紫色', '烟紫色', '樱花粉色',
        '大漠银月', '昆仑霞光', '昆仑玻璃', '流光幻境',

        // 品牌特色颜色
        '深空黑', '深空灰', '曜石黑', '午夜色', '午夜黑', '星光色', '星光白',
        '远峰蓝', '沙漠金', '玫瑰金', '钛金属',
        '雅丹黑', '羽砂黑', '羽砂白', '羽砂紫', '洛可可白', '雅川青',
        '冰晶蓝', '冰雪蓝', '冰川蓝', '冰川白', '凝霜白', '雪域白',
        '曜金黑', '南糯紫', '星曜黑', '月影白', '松石青', '流光紫',
        '陶瓷黑', '陶瓷白', '极光蓝', '极地白', '云海白', '星穹灰',
        '沙漠色', '极地灰', '东方青', '飞泉绿', '幻影黑', '幻影白',
        '幻影紫', '松林绿', '天际蓝', '雾松绿', '墨玉青', '冰峰白',
        '幻夜黑', '星宇橙', '翡冷翠', '薄荷绿', '原野绿', '星河银',
        '岩石灰', '中国红', '琥珀棕', '钛金黑', '钛金灰', '钛金白',
        '光影银', '影青黑', '白月光', '黑曜石', '钛空银',

        // 基础颜色
        '黑色', '白色', '蓝色', '绿色', '紫色', '粉色', '金色', '银色',
        '灰色', '红色', '橙色', '青色', '棕色', '黄色',
        '晴雪', '墨羽', '原色', '星宇', '燃',

        // 单字颜色（最后匹配）
        '黑', '白', '蓝', '绿', '紫', '粉', '金', '银', '灰', '红', '橙', '青', '棕', '黄',

        // 英文颜色
        'Titanium', 'Natural', 'Desert', 'Starlight', 'Midnight', 'Space',
        'Graphite', 'Sierra', 'Alpine', 'Pacific', 'Atlantic',
        'Black', 'White', 'Blue', 'Green', 'Purple', 'Pink',
        'Gold', 'Silver', 'Gray', 'Grey', 'Red', 'Orange',
    ];

    // 4. 版本/渠道描述词
    $versionWords = [
        '活力版', '素皮版', '玻璃版', '陶瓷版', '典藏版', '至臻版',
        '保时捷', '艺术版', '限定版', '标准版', '高配版', '顶配版',
        '国行', '港版', '美版', '日版', '欧版', '韩版', '台版',
        '原封', '官换', '未激活', '已激活', '激活', '全新', '二手', '官翻',
        '移动版', '联通版', '电信版', '全网通', '双卡', '单卡',
        '卫星版', '非卫星', '卫星通信',
    ];

    // 5. 合并所有需要移除的词
    $allRemoveWords = array_merge($colorWords, $versionWords);

    // 按长度降序排序，确保长词先匹配
    usort($allRemoveWords, function($a, $b) {
        return mb_strlen($b) - mb_strlen($a);
    });

    foreach ($allRemoveWords as $word) {
        $name = str_ireplace($word, ' ', $name);
    }

    // 6. 移除尾部修饰词
    $name = preg_replace('/\s*(深|浅|亮|暗|色|版|款)\s*$/u', '', $name);

    // 7. 清理特殊符号
    $name = preg_replace('/[\+\-_]+$/', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    $name = trim($name);

    // 8. 如果结果太短或为空，使用智能截取
    if (mb_strlen($name) < 2) {
        // 尝试从原始名称提取型号（取到第一个数字+字母组合后的空格或颜色词之前）
        if (preg_match('/^(.+?)\s*\d+\s*(TG|TB|G|GB)/iu', $original, $m)) {
            $name = trim($m[1]);
        } else {
            $name = mb_substr(trim($original), 0, 15);
        }
    }

    // 9. 最终清理
    $name = preg_replace('/\s+/', ' ', $name);
    $name = trim($name);

    return $name ?: mb_substr($original, 0, 20);
}

$testMode = isset($_GET['test']);
$executeMode = isset($_GET['execute']);

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $results = [];

    // 确定源表 - 优先使用原始的v3表或其备份
    $source_spu = 'products_spu_v3';
    $source_sku = 'products_sku_v3';

    // 检查备份表
    $v3_backup_exists = $conn->query("SHOW TABLES LIKE 'products_spu_v3_backup'")->rowCount() > 0;
    if ($v3_backup_exists) {
        $source_spu = 'products_spu_v3_backup';
    }

    // 检查源表是否存在
    $source_exists = $conn->query("SHOW TABLES LIKE '$source_spu'")->rowCount() > 0;
    if (!$source_exists) {
        throw new Exception("源表 $source_spu 不存在！请先运行数据迁移。");
    }

    // 读取原始SPU数据
    $stmt = $conn->query("SELECT * FROM $source_spu WHERE min_price > 0 ORDER BY brand, model_name");
    $existingSPUs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results[] = ['step' => '读取源数据', 'status' => 'success',
        'message' => "从 $source_spu 读取到 " . count($existingSPUs) . " 条SPU记录"];

    // 按核心型号分组
    $groupedByCore = [];
    $debugMapping = []; // 用于调试

    foreach ($existingSPUs as $spu) {
        $coreModel = extractCoreModel($spu['model_name'], $spu['brand']);
        $key = $spu['brand'] . '||' . $coreModel;

        // 记录映射（调试用）
        $debugMapping[] = [
            'original' => $spu['model_name'],
            'core' => $coreModel,
            'brand' => $spu['brand'],
            'key' => $key
        ];

        if (!isset($groupedByCore[$key])) {
            $groupedByCore[$key] = [
                'brand' => $spu['brand'],
                'core_model' => $coreModel,
                'category' => $spu['category'] ?? 'phone',
                'variants' => [],
                'spu_ids' => [],
                'min_price' => PHP_INT_MAX,
                'max_price' => 0,
                'total_sku_count' => 0,
                'images' => [],
            ];
        }

        $groupedByCore[$key]['variants'][] = [
            'id' => $spu['id'],
            'name' => $spu['model_name'],
            'price' => $spu['min_price'],
        ];
        $groupedByCore[$key]['spu_ids'][] = $spu['id'];

        if ($spu['min_price'] > 0 && $spu['min_price'] < $groupedByCore[$key]['min_price']) {
            $groupedByCore[$key]['min_price'] = $spu['min_price'];
        }
        if ($spu['max_price'] > $groupedByCore[$key]['max_price']) {
            $groupedByCore[$key]['max_price'] = $spu['max_price'];
        }

        $groupedByCore[$key]['total_sku_count'] += ($spu['sku_count'] ?? 1);

        if (!empty($spu['image_url']) && !in_array($spu['image_url'], $groupedByCore[$key]['images'])) {
            $groupedByCore[$key]['images'][] = $spu['image_url'];
        }
    }

    // 统计
    $originalCount = count($existingSPUs);
    $newCount = count($groupedByCore);
    $reducedCount = $originalCount - $newCount;

    // 找出被合并的组（超过1个变体的）
    $mergedGroups = array_filter($groupedByCore, fn($g) => count($g['variants']) > 1);
    $mergedGroupCount = count($mergedGroups);

    $results[] = ['step' => '分析合并', 'status' => 'success',
        'message' => "发现 $mergedGroupCount 组可合并型号，将从 $originalCount 减少到 $newCount 个SPU（减少 $reducedCount 个）"];

    // ============ 测试模式 ============
    if ($testMode) {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>SPU合并预览 V4</title>';
        echo '<script src="https://cdn.tailwindcss.com"></script></head>';
        echo '<body class="bg-gray-100 p-8"><div class="max-w-6xl mx-auto">';
        echo '<h1 class="text-3xl font-bold text-red-600 mb-4">🔄 SPU智能合并预览 V4</h1>';

        echo '<div class="bg-white rounded-lg p-6 mb-6 shadow-sm">';
        echo '<div class="grid grid-cols-3 gap-4 text-center">';
        echo '<div class="p-4 bg-blue-50 rounded-lg"><div class="text-3xl font-bold text-blue-600">' . $originalCount . '</div><div class="text-gray-500">原始SPU数</div></div>';
        echo '<div class="p-4 bg-green-50 rounded-lg"><div class="text-3xl font-bold text-green-600">' . $newCount . '</div><div class="text-gray-500">合并后SPU数</div></div>';
        echo '<div class="p-4 bg-red-50 rounded-lg"><div class="text-3xl font-bold text-red-600">-' . $reducedCount . '</div><div class="text-gray-500">减少重复</div></div>';
        echo '</div></div>';

        echo '<h2 class="text-xl font-bold mb-4">📦 将被合并的型号组 (' . $mergedGroupCount . ' 组)</h2>';
        echo '<div class="space-y-4">';

        $shown = 0;
        foreach ($mergedGroups as $key => $group) {
            if ($shown >= 50) {
                echo '<p class="text-gray-500 p-4 bg-white rounded-lg">...还有 ' . ($mergedGroupCount - 50) . ' 组未显示</p>';
                break;
            }

            echo '<div class="bg-white rounded-lg p-4 shadow-sm">';
            echo '<div class="flex items-center gap-3 mb-3">';
            echo '<span class="px-3 py-1 bg-gray-800 text-white text-sm rounded-lg font-medium">' . htmlspecialchars($group['brand']) . '</span>';
            echo '<span class="font-bold text-green-600 text-lg">' . htmlspecialchars($group['core_model']) . '</span>';
            echo '<span class="px-2 py-1 bg-blue-100 text-blue-600 text-xs rounded-full">' . count($group['variants']) . '个变体合并</span>';
            echo '</div>';
            echo '<div class="grid grid-cols-2 gap-2 text-sm">';
            foreach ($group['variants'] as $v) {
                echo '<div class="text-gray-600 truncate p-2 bg-gray-50 rounded">• ' . htmlspecialchars($v['name']) . ' <span class="text-red-500 font-medium">¥' . number_format($v['price']) . '</span></div>';
            }
            echo '</div></div>';
            $shown++;
        }

        echo '</div>';
        echo '<div class="mt-8 flex gap-4">';
        echo '<a href="?execute=1" class="inline-block px-8 py-4 bg-red-600 text-white rounded-lg font-bold text-lg hover:bg-red-700 shadow-lg transition">✓ 确认执行合并</a>';
        echo '<a href="?" class="inline-block px-8 py-4 bg-gray-500 text-white rounded-lg font-bold hover:bg-gray-600 transition">返回</a>';
        echo '</div></div></body></html>';
        exit;
    }

    // ============ 执行模式 ============
    if ($executeMode) {
        // 备份原始v3表（如果还没备份）
        if (!$v3_backup_exists) {
            $conn->exec("CREATE TABLE products_spu_v3_backup AS SELECT * FROM products_spu_v3");
            $results[] = ['step' => '备份原表', 'status' => 'success', 'message' => '已备份 products_spu_v3'];
        }

        // 删除旧的v4表
        $conn->exec("DROP TABLE IF EXISTS products_spu_v4");
        $conn->exec("DROP TABLE IF EXISTS products_sku_v4");

        // 创建新的聚合SPU表
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
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_brand (brand),
                INDEX idx_category (category),
                INDEX idx_price (min_price)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $results[] = ['step' => '创建新表', 'status' => 'success', 'message' => '已创建 products_spu_v4'];

        // 插入聚合SPU
        $insertStmt = $conn->prepare("
            INSERT INTO products_spu_v4 (brand, model_name, category, min_price, max_price, sku_count, image_url, gallery_images, original_spu_ids)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $spuMapping = []; // old_id => new_id

        foreach ($groupedByCore as $group) {
            if ($group['min_price'] == PHP_INT_MAX) $group['min_price'] = 0;

            $insertStmt->execute([
                $group['brand'],
                $group['core_model'],
                $group['category'],
                $group['min_price'],
                $group['max_price'],
                $group['total_sku_count'],
                $group['images'][0] ?? '',
                json_encode(array_slice($group['images'], 0, 10)),
                implode(',', $group['spu_ids'])
            ]);

            $newId = $conn->lastInsertId();
            foreach ($group['spu_ids'] as $oldId) {
                $spuMapping[$oldId] = $newId;
            }
        }

        $results[] = ['step' => '插入聚合SPU', 'status' => 'success', 'message' => '已插入 ' . count($groupedByCore) . ' 条聚合SPU'];

        // 创建新的SKU表
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
                image_url VARCHAR(500),
                original_sku_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_spu_id (spu_id),
                INDEX idx_price (price)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 迁移SKU数据
        $skuStmt = $conn->query("SELECT * FROM $source_sku");
        $skus = $skuStmt->fetchAll(PDO::FETCH_ASSOC);

        $skuInsert = $conn->prepare("
            INSERT INTO products_sku_v4 (spu_id, full_name, color, storage, price, official_price, stock_status, image_url, original_sku_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $skuCount = 0;
        foreach ($skus as $sku) {
            $newSpuId = $spuMapping[$sku['spu_id']] ?? null;
            if ($newSpuId) {
                $skuInsert->execute([
                    $newSpuId,
                    $sku['full_name'] ?? '',
                    $sku['color'] ?? '',
                    $sku['storage'] ?? '',
                    $sku['price'] ?? 0,
                    $sku['official_price'] ?? 0,
                    $sku['stock_status'] ?? 'in_stock',
                    $sku['image_url'] ?? '',
                    $sku['id']
                ]);
                $skuCount++;
            }
        }

        $results[] = ['step' => '迁移SKU', 'status' => 'success', 'message' => "已迁移 $skuCount 条SKU记录"];
        $results[] = ['step' => '✅ 完成', 'status' => 'success',
            'message' => "成功！原 $originalCount 个SPU → 合并为 $newCount 个（减少 $reducedCount 个重复）"];
    }

} catch (Exception $e) {
    $results[] = ['step' => '❌ 错误', 'status' => 'error', 'message' => $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>SPU智能合并工具 V4</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto p-8">
        <h1 class="text-3xl font-bold text-red-600 mb-2">🔄 SPU智能合并工具 V4</h1>
        <p class="text-gray-600 mb-8">终极版：自动识别同型号产品，合并为一个商品页面</p>

        <?php if (!$testMode && !$executeMode): ?>
        <div class="bg-white rounded-xl p-6 shadow-sm mb-6">
            <h2 class="font-bold text-lg mb-4">📋 合并规则说明</h2>
            <div class="space-y-2 text-gray-600">
                <p>✓ 同品牌 + 同型号系列 → 合并为一个商品</p>
                <p>✓ 不同颜色、容量、版本 → 变为详情页的可选项</p>
                <p class="text-sm text-gray-400 mt-3">例如：</p>
                <p class="text-sm">• 苹果17Pro Max 2TG星宇橙 + 苹果17Pro Max 1TG深空黑 → <strong class="text-green-600">17Pro Max</strong></p>
                <p class="text-sm">• 华为Mate70 Pro+ 雅丹黑 + 华为Mate70 Pro+ 羽砂白 → <strong class="text-green-600">Mate70 Pro+</strong></p>
            </div>
        </div>

        <div class="flex gap-4">
            <a href="?test=1" class="inline-block px-8 py-4 bg-blue-600 text-white rounded-lg font-bold text-lg hover:bg-blue-700 shadow-lg transition">
                👁 预览合并效果
            </a>
            <a href="?execute=1" class="inline-block px-8 py-4 bg-red-600 text-white rounded-lg font-bold text-lg hover:bg-red-700 shadow-lg transition">
                ⚡ 直接执行合并
            </a>
        </div>
        <?php endif; ?>

        <?php if ($executeMode): ?>
        <div class="space-y-3 mb-8">
            <?php foreach ($results as $r):
                $bgColor = match($r['status']) {
                    'success' => 'bg-green-100 border-green-500 text-green-800',
                    'error' => 'bg-red-100 border-red-500 text-red-800',
                    default => 'bg-blue-100 border-blue-500 text-blue-800'
                };
            ?>
            <div class="<?php echo $bgColor; ?> border-l-4 p-4 rounded-r-lg">
                <strong><?php echo htmlspecialchars($r['step']); ?>:</strong>
                <?php echo htmlspecialchars($r['message']); ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex gap-4 flex-wrap">
            <a href="/core/quotes_v6.php" class="inline-block px-8 py-4 bg-green-600 text-white rounded-lg font-bold text-lg hover:bg-green-700 shadow-lg transition">
                🎉 查看列表页效果
            </a>
            <a href="/core/index_v4.php" class="inline-block px-8 py-4 bg-purple-600 text-white rounded-lg font-bold text-lg hover:bg-purple-700 shadow-lg transition">
                🏠 查看首页效果
            </a>
            <a href="?" class="inline-block px-8 py-4 bg-gray-500 text-white rounded-lg font-bold hover:bg-gray-600 transition">
                🔄 重新执行
            </a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
