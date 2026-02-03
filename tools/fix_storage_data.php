<?php
/**
 * ==========================================
 * 汇森科技 - 存储规格数据修复脚本
 * ==========================================
 *
 * 问题：1GB 应该是 1TB（当产品价格 > 2000元时）
 */

require_once dirname(__DIR__) . '/config/config.php';

echo "========================================\n";
echo "汇森科技 - 存储规格数据修复\n";
echo "========================================\n\n";

$db = Database::getInstance();
$conn = $db->getConnection();

// 检查使用哪个表
$use_v4 = $conn->query("SHOW TABLES LIKE 'products_sku_v4'")->rowCount() > 0;
$sku_table = $use_v4 ? 'products_sku_v4' : 'products_sku_v3';

echo "使用表: $sku_table\n\n";

// 1. 修复 storage 字段中的 1GB -> 1TB (价格>2000)
$sql1 = "UPDATE $sku_table SET storage = REPLACE(storage, '1GB', '1TB')
         WHERE storage LIKE '%1GB%' AND price > 2000";
$affected1 = $conn->exec($sql1);
echo "修复 storage 字段 1GB→1TB: {$affected1} 条记录\n";

// 2. 修复其他常见错误
$fixes = [
    // 存储容量错误修复
    ['512gb', '512GB'],
    ['256gb', '256GB'],
    ['128gb', '128GB'],
    ['1tb', '1TB'],
    ['2tb', '2TB'],
    // GB -> TB 修复（高端机型）
];

foreach ($fixes as $fix) {
    $sql = "UPDATE $sku_table SET storage = REPLACE(storage, '{$fix[0]}', '{$fix[1]}')
            WHERE storage LIKE '%{$fix[0]}%'";
    $affected = $conn->exec($sql);
    if ($affected > 0) {
        echo "修复 {$fix[0]} → {$fix[1]}: {$affected} 条记录\n";
    }
}

// 3. 查看修复后的数据
echo "\n=== 修复后的存储规格分布 ===\n";
$stmt = $conn->query("SELECT storage, COUNT(*) as count FROM $sku_table
                      WHERE storage IS NOT NULL AND storage != ''
                      GROUP BY storage ORDER BY count DESC LIMIT 20");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  {$row['storage']}: {$row['count']} 条\n";
}

// 4. 检查是否还有问题数据
$check = $conn->query("SELECT COUNT(*) FROM $sku_table WHERE storage LIKE '%1GB%' AND price > 2000")->fetchColumn();
echo "\n剩余问题数据(1GB且价格>2000): {$check} 条\n";

echo "\n修复完成!\n";
