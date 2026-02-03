<?php
/** 
 * 产品卡片组件
 * 用于在列表页统一展示产品
 */
if (!isset($product)) return;
?>

<div class="product-card bg-white rounded-lg overflow-hidden" onclick="location.href='/core/detail_v4.php?spu=<?php echo $product['id']; ?>'">
    
    <!-- 图片区域 -->
    <div class="image-container relative">
        <?php 
        $image_path = $product['image_path'] ?? '';
        if (!empty($image_path) && file_exists('../' . $image_path)): 
        ?>
            <img src="../<?php echo htmlspecialchars($image_path); ?>" 
                 alt="<?php echo htmlspecialchars($product['model']); ?>"
                 class="product-image"
                 loading="lazy">
        <?php else: ?>
            <img src="../images/placeholder.jpg" 
                 alt="暂无图片"
                 class="product-image"
                 loading="lazy">
        <?php endif; ?>

        <!-- 标签 -->
        <?php if (!empty($product['tags'])): ?>
        <div class="absolute top-2 left-2 flex flex-wrap gap-1">
            <?php
            $tags = explode(',', $product['tags']);
            foreach ($tags as $tag):
                $tag = trim($tag);
                $tag_class = 'tag-hot';
                if (stripos($tag, '自营') !== false) $tag_class = 'tag-self-run';
                if (stripos($tag, '降价') !== false) $tag_class = 'tag-discount';
                if (stripos($tag, '新品') !== false) $tag_class = 'tag-new';
            ?>
            <span class="<?php echo $tag_class; ?> text-white text-xs px-2 py-1 rounded-md shadow-sm">
                <?php echo htmlspecialchars($tag); ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- 信息区域 -->
    <div class="p-3">
        <!-- 品牌 -->
        <div class="text-xs text-gray-500 mb-1">
            <?php echo htmlspecialchars($product['brand']); ?>
        </div>

        <!-- 型号 -->
        <h3 class="font-bold text-sm mb-2 line-clamp-2 h-10">
            <?php echo htmlspecialchars($product['base_model'] ?? $product['model']); ?>
        </h3>

        <!-- 变体数量标识 -->
        <?php if (isset($product['variant_count']) && $product['variant_count'] > 1): ?>
        <div class="mb-2">
            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-md">
                📦 <?php echo $product['variant_count']; ?>个配置
            </span>
        </div>
        <?php endif; ?>

        <!-- 价格区域 -->
        <div class="mb-3">
            <?php if (isset($product['variant_count']) && $product['variant_count'] > 1): ?>
                <!-- 多配置：显示价格区间 -->
                <div class="price-current">
                    ￥<?php echo number_format($product['min_price'], 0); ?>起
                </div>
                <?php if (isset($product['max_price']) && $product['min_price'] != $product['max_price']): ?>
                <div class="text-xs text-gray-500 mt-1">
                    ￥<?php echo number_format($product['min_price'], 0); ?> - ￥<?php echo number_format($product['max_price'], 0); ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- 单配置：正常显示价格 -->
                <div class="price-current">
                    ￥<?php echo number_format($product['price'], 0); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($product['official_price']) && $product['official_price'] > 0): ?>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-xs text-gray-400 line-through">￥<?php echo number_format($product['official_price'], 0); ?></span>
                <span class="price-discount">💰 省<?php echo number_format($product['official_price'] - $product['price'], 0); ?>元</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- 库存状态 -->
        <?php if (!empty($product['stock_status'])): ?>
        <div class="text-xs mb-2 flex items-center gap-1">
            <?php
            $status = $product['stock_status'];
            if ($status === '充足' || $status === 'in_stock') {
                echo '<span class="text-green-600">✓ 现货</span>';
            } elseif ($status === '缺货' || $status === 'out_of_stock') {
                echo '<span class="text-red-600">✗ 缺货</span>';
            } else {
                echo '<span class="text-yellow-600">⚠ 少量</span>';
            }
            ?>
        </div>
        <?php endif; ?>

        <!-- 按钮 -->
        <button class="w-full py-2 rounded-lg text-sm font-medium text-white transition hover:opacity-90"
                style="background-color: var(--jiji-red);"
                onclick="event.stopPropagation(); alert('请联系客服咨询');">
            立即咨询
        </button>
    </div>
</div>
