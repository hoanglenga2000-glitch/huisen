<?php
/**
 * 生成产品目录SQL文件
 */

require_once '../config/config.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// 创建sql目录
$sql_dir = __DIR__ . '/../sql';
if (!is_dir($sql_dir)) {
    mkdir($sql_dir, 0755, true);
}

$output_file = $sql_dir . '/产品目录.sql';

// 获取所有SPU产品
$spu_stmt = $conn->query("SELECT * FROM products_spu_v3 ORDER BY brand, model_name");
$spus = $spu_stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取所有SKU产品
$sku_stmt = $conn->query("SELECT * FROM products_sku_v3 ORDER BY spu_id, price");
$skus = $sku_stmt->fetchAll(PDO::FETCH_ASSOC);

// 构建SQL内容
$sql_content = "-- ============================================\n";
$sql_content .= "-- 汇森科技 - 产品目录\n";
$sql_content .= "-- 生成时间: " . date('Y-m-d H:i:s') . "\n";
$sql_content .= "-- ============================================\n\n";

// 统计信息
$sql_content .= "-- 统计信息:\n";
$sql_content .= "-- SPU产品总数: " . count($spus) . " 款\n";
$sql_content .= "-- SKU变体总数: " . count($skus) . " 个\n\n";

// 品牌统计
$brand_stats = [];
foreach ($spus as $spu) {
    $brand = $spu['brand'];
    if (!isset($brand_stats[$brand])) {
        $brand_stats[$brand] = 0;
    }
    $brand_stats[$brand]++;
}

$sql_content .= "-- 品牌分布:\n";
foreach ($brand_stats as $brand => $count) {
    $sql_content .= "-- {$brand}: {$count} 款\n";
}
$sql_content .= "\n";

// ========== SPU表结构和数据 ==========
$sql_content .= "-- ============================================\n";
$sql_content .= "-- SPU产品主表 (products_spu_v3)\n";
$sql_content .= "-- ============================================\n\n";

$sql_content .= "DROP TABLE IF EXISTS `products_spu_v3`;\n";
$sql_content .= "CREATE TABLE `products_spu_v3` (\n";
$sql_content .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
$sql_content .= "  `brand` varchar(50) NOT NULL COMMENT '品牌',\n";
$sql_content .= "  `model_name` varchar(200) NOT NULL COMMENT '型号名称',\n";
$sql_content .= "  `category` varchar(50) DEFAULT 'phone' COMMENT '分类',\n";
$sql_content .= "  `image_url` varchar(500) DEFAULT NULL COMMENT '主图URL',\n";
$sql_content .= "  `min_price` decimal(10,2) DEFAULT 0 COMMENT '最低价',\n";
$sql_content .= "  `max_price` decimal(10,2) DEFAULT 0 COMMENT '最高价',\n";
$sql_content .= "  `sku_count` int(11) DEFAULT 0 COMMENT 'SKU数量',\n";
$sql_content .= "  `gallery_images` text COMMENT '图片集JSON',\n";
$sql_content .= "  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,\n";
$sql_content .= "  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
$sql_content .= "  PRIMARY KEY (`id`),\n";
$sql_content .= "  KEY `idx_brand` (`brand`),\n";
$sql_content .= "  KEY `idx_category` (`category`)\n";
$sql_content .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SPU产品主表';\n\n";

// SPU数据
$sql_content .= "-- SPU产品数据\n";
foreach ($spus as $spu) {
    $id = $spu['id'];
    $brand = addslashes($spu['brand']);
    $model_name = addslashes($spu['model_name']);
    $category = addslashes($spu['category'] ?? 'phone');
    $image_url = addslashes($spu['image_url'] ?? '');
    $min_price = $spu['min_price'] ?? 0;
    $max_price = $spu['max_price'] ?? 0;
    $sku_count = $spu['sku_count'] ?? 0;
    $gallery = addslashes($spu['gallery_images'] ?? '');

    $sql_content .= "INSERT INTO `products_spu_v3` (`id`, `brand`, `model_name`, `category`, `image_url`, `min_price`, `max_price`, `sku_count`, `gallery_images`) VALUES ";
    $sql_content .= "({$id}, '{$brand}', '{$model_name}', '{$category}', '{$image_url}', {$min_price}, {$max_price}, {$sku_count}, '{$gallery}');\n";
}

$sql_content .= "\n";

// ========== SKU表结构和数据 ==========
$sql_content .= "-- ============================================\n";
$sql_content .= "-- SKU变体表 (products_sku_v3)\n";
$sql_content .= "-- ============================================\n\n";

$sql_content .= "DROP TABLE IF EXISTS `products_sku_v3`;\n";
$sql_content .= "CREATE TABLE `products_sku_v3` (\n";
$sql_content .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
$sql_content .= "  `spu_id` int(11) NOT NULL COMMENT '关联SPU ID',\n";
$sql_content .= "  `full_name` varchar(300) NOT NULL COMMENT '完整名称',\n";
$sql_content .= "  `color` varchar(50) DEFAULT NULL COMMENT '颜色',\n";
$sql_content .= "  `storage` varchar(50) DEFAULT NULL COMMENT '存储容量',\n";
$sql_content .= "  `price` decimal(10,2) NOT NULL COMMENT '批发价',\n";
$sql_content .= "  `official_price` decimal(10,2) DEFAULT 0 COMMENT '官网价',\n";
$sql_content .= "  `stock_status` varchar(20) DEFAULT 'in_stock' COMMENT '库存状态',\n";
$sql_content .= "  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,\n";
$sql_content .= "  PRIMARY KEY (`id`),\n";
$sql_content .= "  KEY `idx_spu_id` (`spu_id`)\n";
$sql_content .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SKU变体表';\n\n";

// SKU数据
$sql_content .= "-- SKU变体数据\n";
foreach ($skus as $sku) {
    $id = $sku['id'];
    $spu_id = $sku['spu_id'];
    $full_name = addslashes($sku['full_name'] ?? '');
    $color = addslashes($sku['color'] ?? '');
    $storage = addslashes($sku['storage'] ?? '');
    $price = $sku['price'] ?? 0;
    $official_price = $sku['official_price'] ?? 0;
    $stock_status = addslashes($sku['stock_status'] ?? 'in_stock');

    $sql_content .= "INSERT INTO `products_sku_v3` (`id`, `spu_id`, `full_name`, `color`, `storage`, `price`, `official_price`, `stock_status`) VALUES ";
    $sql_content .= "({$id}, {$spu_id}, '{$full_name}', '{$color}', '{$storage}', {$price}, {$official_price}, '{$stock_status}');\n";
}

$sql_content .= "\n-- ============================================\n";
$sql_content .= "-- 产品目录生成完成\n";
$sql_content .= "-- ============================================\n";

// 写入文件
file_put_contents($output_file, $sql_content);

echo "====================================\n";
echo "产品目录SQL文件生成成功!\n";
echo "====================================\n";
echo "文件路径: {$output_file}\n";
echo "SPU产品数: " . count($spus) . " 款\n";
echo "SKU变体数: " . count($skus) . " 个\n";
echo "\n品牌分布:\n";
foreach ($brand_stats as $brand => $count) {
    echo "  {$brand}: {$count} 款\n";
}
echo "\n";
