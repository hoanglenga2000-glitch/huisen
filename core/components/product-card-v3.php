<?php
/**
 * V3 产品卡片组件
 * 
 * 特性：
 * 1. 只显示清洗后的干净型号名称
 * 2. 价格显示为 "¥X,XXX 起"
 * 3. 分类特定占位图（手机/平板/手表/配件）
 */

if (!isset($p)) return;

$spu_id = $p['id'];
$brand = $p['brand'] ?? '';
$model = $p['model_name'] ?? '';
$min_price = floatval($p['min_price'] ?? 0);
$max_price = floatval($p['max_price'] ?? 0);
$sku_count = intval($p['sku_count'] ?? 1);
$category = $p['category'] ?? 'phone';
$image = $p['image_url'] ?? '';
$has_image = !empty($image) && file_exists('../' . $image);

// 分类特定的占位图SVG
$placeholder_icons = [
    'phone' => '<svg class="category-placeholder" viewBox="0 0 24 24" fill="currentColor">
        <path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14zm-4.2-5.78v1.75l3.2-2.99L12.8 9v1.7c-3.11.43-4.35 2.56-4.8 4.7 1.11-1.5 2.58-2.18 4.8-2.18z"/>
    </svg>',
    'tablet' => '<svg class="category-placeholder" viewBox="0 0 24 24" fill="currentColor">
        <path d="M21 4H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h18c1.1 0 1.99-.9 1.99-2L23 6c0-1.1-.9-2-2-2zm-2 14H5V6h14v12z"/>
    </svg>',
    'watch' => '<svg class="category-placeholder" viewBox="0 0 24 24" fill="currentColor">
        <path d="M20 12c0-2.54-1.19-4.81-3.04-6.27L16 0H8l-.95 5.73C5.19 7.19 4 9.45 4 12s1.19 4.81 3.05 6.27L8 24h8l.96-5.73C18.81 16.81 20 14.54 20 12zM6 12c0-3.31 2.69-6 6-6s6 2.69 6 6-2.69 6-6 6-6-2.69-6-6z"/>
    </svg>',
    'accessory' => '<svg class="category-placeholder" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 1c-4.97 0-9 4.03-9 9v7c0 1.66 1.34 3 3 3h3v-8H5v-2c0-3.87 3.13-7 7-7s7 3.13 7 7v2h-4v8h3c1.66 0 3-1.34 3-3v-7c0-4.97-4.03-9-9-9z"/>
    </svg>',
];

$placeholder = $placeholder_icons[$category] ?? $placeholder_icons['phone'];

// 分类标签
$category_labels = [
    'phone' => '',
    'tablet' => '平板',
    'watch' => '手表',
    'accessory' => '配件',
];
?>
<article class="product-card bg-white rounded-xl overflow-hidden" 
         onclick="location.href='/core/detail_v4.php?spu=<?php echo $spu_id; ?>'">
    
    <!-- 图片区域 -->
    <div class="relative aspect-square bg-gray-50 flex items-center justify-center p-6">
        <?php if ($has_image): ?>
            <img src="../<?php echo htmlspecialchars($image); ?>" 
                 alt="<?php echo htmlspecialchars($model); ?>"
                 class="max-w-full max-h-full object-contain"
                 loading="lazy">
        <?php else: ?>
            <!-- 分类特定占位图 -->
            <div class="text-gray-300 flex flex-col items-center justify-center">
                <?php echo $placeholder; ?>
                <span class="text-xs text-gray-400 mt-2"><?php echo htmlspecialchars($brand); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- 分类标签 -->
        <?php if (!empty($category_labels[$category])): ?>
        <div class="absolute top-3 left-3">
            <span class="px-2 py-1 bg-gray-800/80 text-white text-xs rounded-md">
                <?php echo $category_labels[$category]; ?>
            </span>
        </div>
        <?php endif; ?>
        
        <!-- SKU数量标签 -->
        <?php if ($sku_count > 1): ?>
        <div class="absolute top-3 right-3">
            <span class="px-2 py-1 bg-blue-500 text-white text-xs rounded-md">
                <?php echo $sku_count; ?>款可选
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- 信息区域 -->
    <div class="p-4">
        <!-- 品牌 -->
        <div class="text-xs text-gray-400 mb-1"><?php echo htmlspecialchars($brand); ?></div>
        
        <!-- 清洗后的型号名称 -->
        <h3 class="font-bold text-sm mb-3 line-clamp-2 h-10 text-gray-800">
            <?php echo htmlspecialchars($model); ?>
        </h3>

        <!-- 价格区域 -->
        <div class="flex items-baseline gap-1">
            <span class="price-tag">¥<?php echo number_format($min_price, 0); ?></span>
            <?php if ($sku_count > 1 || $min_price != $max_price): ?>
            <span class="price-suffix text-red-500">起</span>
            <?php endif; ?>
        </div>
        
        <?php if ($min_price != $max_price): ?>
        <div class="text-xs text-gray-400 mt-1">
            ¥<?php echo number_format($min_price, 0); ?> - ¥<?php echo number_format($max_price, 0); ?>
        </div>
        <?php endif; ?>

        <!-- 查看详情按钮 -->
        <button class="w-full mt-4 py-2.5 rounded-lg text-sm font-medium text-white transition hover:opacity-90"
                style="background: var(--brand-red);"
                onclick="event.stopPropagation();">
            查看详情
        </button>
    </div>
</article>
