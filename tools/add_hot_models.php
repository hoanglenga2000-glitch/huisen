<?php
/**
 * ==========================================
 * 汇森科技 - 添加热门旗舰机型
 * ==========================================
 *
 * 添加2025-2026年最热门的旗舰手机型号
 */

require_once dirname(__DIR__) . '/config/config.php';

echo "========================================\n";
echo "汇森科技 - 添加热门旗舰机型\n";
echo "========================================\n\n";

$db = Database::getInstance();
$conn = $db->getConnection();

// 检查使用哪个表
$use_v4 = $conn->query("SHOW TABLES LIKE 'products_spu_v4'")->rowCount() > 0;
$spu_table = $use_v4 ? 'products_spu_v4' : 'products_spu_v3';
$sku_table = $use_v4 ? 'products_sku_v4' : 'products_sku_v3';

echo "使用表: $spu_table / $sku_table\n\n";

// 热门机型数据
$hot_models = [
    // Apple iPhone 16 系列
    [
        'brand' => '苹果',
        'model_name' => 'iPhone 16 Pro Max',
        'category' => 'phone',
        'description' => 'A18 Pro芯片，钛金属设计，4K 120fps杜比视界',
        'skus' => [
            ['storage' => '256GB', 'colors' => ['原色钛金属', '沙漠钛金属', '白色钛金属', '黑色钛金属'], 'price' => 9999, 'official' => 10999],
            ['storage' => '512GB', 'colors' => ['原色钛金属', '沙漠钛金属', '白色钛金属', '黑色钛金属'], 'price' => 11599, 'official' => 12799],
            ['storage' => '1TB', 'colors' => ['原色钛金属', '沙漠钛金属', '白色钛金属', '黑色钛金属'], 'price' => 13199, 'official' => 14599],
        ]
    ],
    [
        'brand' => '苹果',
        'model_name' => 'iPhone 16 Pro',
        'category' => 'phone',
        'description' => 'A18 Pro芯片，钛金属设计，专业相机系统',
        'skus' => [
            ['storage' => '128GB', 'colors' => ['原色钛金属', '沙漠钛金属', '白色钛金属', '黑色钛金属'], 'price' => 7999, 'official' => 8999],
            ['storage' => '256GB', 'colors' => ['原色钛金属', '沙漠钛金属', '白色钛金属', '黑色钛金属'], 'price' => 8799, 'official' => 9999],
            ['storage' => '512GB', 'colors' => ['原色钛金属', '沙漠钛金属', '白色钛金属', '黑色钛金属'], 'price' => 10399, 'official' => 11799],
            ['storage' => '1TB', 'colors' => ['原色钛金属', '沙漠钛金属', '白色钛金属', '黑色钛金属'], 'price' => 11999, 'official' => 13599],
        ]
    ],
    [
        'brand' => '苹果',
        'model_name' => 'iPhone 16',
        'category' => 'phone',
        'description' => 'A18芯片，全新相机控制按钮，动态岛',
        'skus' => [
            ['storage' => '128GB', 'colors' => ['黑色', '白色', '粉色', '蓝色', '群青色'], 'price' => 5999, 'official' => 6999],
            ['storage' => '256GB', 'colors' => ['黑色', '白色', '粉色', '蓝色', '群青色'], 'price' => 6799, 'official' => 7899],
            ['storage' => '512GB', 'colors' => ['黑色', '白色', '粉色', '蓝色', '群青色'], 'price' => 8399, 'official' => 9699],
        ]
    ],

    // 华为 Mate 70 系列
    [
        'brand' => '华为',
        'model_name' => 'Mate 70 Pro+',
        'category' => 'phone',
        'description' => '麒麟9100芯片，玄武架构，卫星通信，纯血鸿蒙',
        'skus' => [
            ['storage' => '512GB', 'colors' => ['宣白', '曜石黑', '雅川青', '金丝锦'], 'price' => 8999, 'official' => 9999],
            ['storage' => '1TB', 'colors' => ['宣白', '曜石黑', '雅川青', '金丝锦'], 'price' => 9999, 'official' => 10999],
        ]
    ],
    [
        'brand' => '华为',
        'model_name' => 'Mate 70 Pro',
        'category' => 'phone',
        'description' => '麒麟9000S芯片，超可靠玄武架构，卫星消息',
        'skus' => [
            ['storage' => '256GB', 'colors' => ['曜石黑', '雪域白', '雅川青', '风信紫'], 'price' => 6299, 'official' => 6999],
            ['storage' => '512GB', 'colors' => ['曜石黑', '雪域白', '雅川青', '风信紫'], 'price' => 6999, 'official' => 7699],
            ['storage' => '1TB', 'colors' => ['曜石黑', '雪域白', '雅川青', '风信紫'], 'price' => 7699, 'official' => 8499],
        ]
    ],
    [
        'brand' => '华为',
        'model_name' => 'Mate X6',
        'category' => 'phone',
        'description' => '折叠旗舰，玄武钢化昆仑玻璃，卫星通信',
        'skus' => [
            ['storage' => '512GB', 'colors' => ['曜石黑', '星辰银', '霞光紫'], 'price' => 12999, 'official' => 13999],
            ['storage' => '1TB', 'colors' => ['曜石黑', '星辰银', '霞光紫'], 'price' => 14499, 'official' => 15499],
        ]
    ],

    // 小米 15 系列
    [
        'brand' => '小米',
        'model_name' => '小米 15 Ultra',
        'category' => 'phone',
        'description' => '徕卡光学Summilux镜头，骁龙8至尊版，陶瓷机身',
        'skus' => [
            ['storage' => '16+512GB', 'colors' => ['陶瓷黑', '陶瓷白', '橄榄绿'], 'price' => 5999, 'official' => 6499],
            ['storage' => '16+1TB', 'colors' => ['陶瓷黑', '陶瓷白', '橄榄绿'], 'price' => 6599, 'official' => 7099],
        ]
    ],
    [
        'brand' => '小米',
        'model_name' => '小米 15 Pro',
        'category' => 'phone',
        'description' => '骁龙8至尊版，徕卡影像，小米澎湃OS',
        'skus' => [
            ['storage' => '12+256GB', 'colors' => ['黑色', '白色', '紫色', '绿色'], 'price' => 4499, 'official' => 4999],
            ['storage' => '16+512GB', 'colors' => ['黑色', '白色', '紫色', '绿色'], 'price' => 4999, 'official' => 5499],
            ['storage' => '16+1TB', 'colors' => ['黑色', '白色', '紫色', '绿色'], 'price' => 5499, 'official' => 5999],
        ]
    ],

    // vivo X200 系列
    [
        'brand' => 'vivo',
        'model_name' => 'vivo X200 Pro',
        'category' => 'phone',
        'description' => '蔡司APO超级长焦，天玑9400，蓝海电池',
        'skus' => [
            ['storage' => '12+256GB', 'colors' => ['钛灰', '白月光', '落日橙'], 'price' => 4799, 'official' => 5299],
            ['storage' => '16+512GB', 'colors' => ['钛灰', '白月光', '落日橙'], 'price' => 5299, 'official' => 5799],
            ['storage' => '16+1TB', 'colors' => ['钛灰', '白月光', '落日橙'], 'price' => 5799, 'official' => 6299],
        ]
    ],

    // OPPO Find X8 系列
    [
        'brand' => 'OPPO',
        'model_name' => 'OPPO Find X8 Ultra',
        'category' => 'phone',
        'description' => '哈苏影像系统，天玑9400，潜望长焦',
        'skus' => [
            ['storage' => '12+256GB', 'colors' => ['玄夜黑', '大漠银月', '浮光紫'], 'price' => 5499, 'official' => 5999],
            ['storage' => '16+512GB', 'colors' => ['玄夜黑', '大漠银月', '浮光紫'], 'price' => 5999, 'official' => 6499],
            ['storage' => '16+1TB', 'colors' => ['玄夜黑', '大漠银月', '浮光紫'], 'price' => 6499, 'official' => 6999],
        ]
    ],

    // 荣耀 Magic7 系列
    [
        'brand' => '荣耀',
        'model_name' => '荣耀 Magic7 Pro',
        'category' => 'phone',
        'description' => '骁龙8至尊版，青海湖电池，鹰眼相机',
        'skus' => [
            ['storage' => '12+256GB', 'colors' => ['海湖青', '曙光金', '月影白', '绒黑'], 'price' => 4499, 'official' => 4999],
            ['storage' => '16+512GB', 'colors' => ['海湖青', '曙光金', '月影白', '绒黑'], 'price' => 4999, 'official' => 5499],
            ['storage' => '16+1TB', 'colors' => ['海湖青', '曙光金', '月影白', '绒黑'], 'price' => 5499, 'official' => 5999],
        ]
    ],

    // 三星 Galaxy S25 系列
    [
        'brand' => '三星',
        'model_name' => 'Galaxy S25 Ultra',
        'category' => 'phone',
        'description' => '骁龙8至尊版Galaxy定制版，S Pen，AI智能',
        'skus' => [
            ['storage' => '12+256GB', 'colors' => ['钛金黑', '钛金灰', '钛金蓝', '钛金银'], 'price' => 9199, 'official' => 9999],
            ['storage' => '12+512GB', 'colors' => ['钛金黑', '钛金灰', '钛金蓝', '钛金银'], 'price' => 10199, 'official' => 10999],
            ['storage' => '12+1TB', 'colors' => ['钛金黑', '钛金灰', '钛金蓝', '钛金银'], 'price' => 11699, 'official' => 12499],
        ]
    ],
];

$added_spu = 0;
$added_sku = 0;

foreach ($hot_models as $model) {
    // 检查是否已存在
    $check = $conn->prepare("SELECT id FROM $spu_table WHERE model_name = ?");
    $check->execute([$model['model_name']]);
    if ($check->fetch()) {
        echo "跳过（已存在）: {$model['model_name']}\n";
        continue;
    }

    // 计算最低价和SKU数量
    $min_price = PHP_INT_MAX;
    $sku_count = 0;
    foreach ($model['skus'] as $sku) {
        $sku_count += count($sku['colors']);
        if ($sku['price'] < $min_price) {
            $min_price = $sku['price'];
        }
    }

    // 插入SPU
    $stmt = $conn->prepare("INSERT INTO $spu_table (brand, model_name, category, min_price, sku_count, created_at)
                            VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $model['brand'],
        $model['model_name'],
        $model['category'],
        $min_price,
        $sku_count
    ]);
    $spu_id = $conn->lastInsertId();
    $added_spu++;
    echo "添加SPU: {$model['model_name']} (ID: $spu_id)\n";

    // 插入SKU
    foreach ($model['skus'] as $sku) {
        foreach ($sku['colors'] as $color) {
            $full_name = "{$model['model_name']} {$color} {$sku['storage']}";
            $stmt = $conn->prepare("INSERT INTO $sku_table (spu_id, full_name, color, storage, price, official_price, stock_status, created_at)
                                    VALUES (?, ?, ?, ?, ?, ?, 'in_stock', NOW())");
            $stmt->execute([
                $spu_id,
                $full_name,
                $color,
                $sku['storage'],
                $sku['price'],
                $sku['official']
            ]);
            $added_sku++;
        }
    }
}

echo "\n========================================\n";
echo "添加完成！\n";
echo "新增SPU: {$added_spu} 款\n";
echo "新增SKU: {$added_sku} 条\n";
echo "========================================\n";
