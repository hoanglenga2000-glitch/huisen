<?php
/**
 * SPU产品卡片组件
 * 用于首页展示，显示基础型号信息
 */
if (!isset($p)) return;

$spu_id = $p['id'];
$brand = $p['brand'] ?? '';
$model = $p['base_model'] ?? '';
$min_price = $p['min_price'] ?? 0;
$max_price = $p['max_price'] ?? 0;
$sku_count = $p['sku_count'] ?? $p['variant_count'] ?? 1;
$image = $p['image_url'] ?? '';
$category = $p['category'] ?? 'phone';
$is_hot = $p['is_hot'] ?? false;
$is_new = $p['is_new'] ?? false;
$is_discount = $p['is_discount'] ?? false;

// 占位图根据品牌
$placeholder = '../images/placeholder.jpg';
?>
<div class="product-card bg-white rounded-lg overflow-hidden shadow-sm" 
     onclick="location.href='/core/detail_v4.php?spu=<?php echo $spu_id; ?>'">
    
    <!-- 图片区域 -->
    <div class="relative aspect-square bg-gray-50 flex items-center justify-center p-4">
        <?php if (!empty($image) && file_exists('../' . $image)): ?>
            <img src="../<?php echo htmlspecialchars($image); ?>" 
                 alt="<?php echo htmlspecialchars($model); ?>"
                 class="max-w-full max-h-full object-contain">
        <?php else: ?>
            <div class="text-center">
                <svg class="w-16 h-16 mx-auto text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/>
                </svg>
                <span class="text-xs text-gray-400 mt-2 block"><?php echo htmlspecialchars($brand); ?></span>
            </div>
        <?php endif; ?>

        <!-- 标签 -->
        <div class="absolute top-2 left-2 flex flex-col gap-1">
            <?php if ($is_new): ?>
            <span class="badge badge-new">新品</span>
            <?php endif; ?>
            <?php if ($is_hot): ?>
            <span class="badge badge-hot">热销</span>
            <?php endif; ?>
            <?php if ($is_discount): ?>
            <span class="badge badge-discount">降价</span>
            <?php endif; ?>
        </div>
        
        <!-- 类别标识 -->
        <?php if ($category !== 'phone'): ?>
        <div class="absolute top-2 right-2">
            <span class="text-xs px-2 py-1 bg-gray-200 text-gray-600 rounded">
                <?php 
                echo match($category) {
                    'tablet' => '平板',
                    'watch' => '手表',
                    'accessory' => '配件',
                    default => $category
                };
                ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- 信息区域 -->
    <div class="p-3">
        <!-- 品牌 -->
        <div class="text-xs text-gray-500 mb-1"><?php echo htmlspecialchars($brand); ?></div>
        
        <!-- 型号 -->
        <h3 class="font-bold text-sm mb-2 line-clamp-2 h-10">
            <?php echo htmlspecialchars($model); ?>
        </h3>
        
        <!-- 配置数量 -->
        <?php if ($sku_count > 1): ?>
        <div class="mb-2">
            <span class="badge badge-variants">📦 <?php echo $sku_count; ?>个配置可选</span>
        </div>
        <?php endif; ?>

        <!-- 价格区域 -->
        <div class="mb-3">
            <div class="price-main">
                ¥<?php echo number_format($min_price, 0); ?><?php echo $sku_count > 1 ? '起' : ''; ?>
            </div>
            <?php if ($sku_count > 1 && $min_price != $max_price): ?>
            <div class="text-xs text-gray-500">
                ¥<?php echo number_format($min_price, 0); ?> - ¥<?php echo number_format($max_price, 0); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- 按钮 -->
        <button class="w-full py-2 rounded-lg text-sm font-medium text-white transition hover:opacity-90"
                style="background-color: var(--jiji-red);"
                onclick="event.stopPropagation(); location.href='/core/detail_v4.php?spu=<?php echo $spu_id; ?>'">
            查看详情
        </button>
    </div>
</div>
