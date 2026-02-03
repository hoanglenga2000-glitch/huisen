<?php
/**
 * ==========================================
 * 汇森科技 - 骨架屏组件 v1.0
 * ==========================================
 *
 * 使用方法：
 * <?php include 'includes/skeleton.php'; ?>
 * <div id="product-list" class="skeleton-container" data-skeleton="product-grid" data-count="8">
 *     <!-- 实际内容 -->
 * </div>
 *
 * 或直接调用函数：
 * <?php echo skeleton_product_card(); ?>
 */

/**
 * 产品卡片骨架屏
 */
function skeleton_product_card() {
    return <<<HTML
<div class="skeleton-card">
    <div class="skeleton skeleton-image"></div>
    <div class="skeleton-content">
        <div class="skeleton skeleton-text short"></div>
        <div class="skeleton skeleton-text long"></div>
        <div class="skeleton skeleton-text medium"></div>
        <div class="skeleton skeleton-price"></div>
        <div class="skeleton skeleton-button"></div>
    </div>
</div>
HTML;
}

/**
 * 产品网格骨架屏
 */
function skeleton_product_grid($count = 8) {
    $html = '<div class="skeleton-grid">';
    for ($i = 0; $i < $count; $i++) {
        $html .= skeleton_product_card();
    }
    $html .= '</div>';
    return $html;
}

/**
 * 列表项骨架屏
 */
function skeleton_list_item() {
    return <<<HTML
<div class="skeleton-list-item">
    <div class="skeleton skeleton-avatar"></div>
    <div class="skeleton-list-content">
        <div class="skeleton skeleton-text medium"></div>
        <div class="skeleton skeleton-text long"></div>
    </div>
</div>
HTML;
}

/**
 * 表格行骨架屏
 */
function skeleton_table_row($cols = 5) {
    $html = '<tr class="skeleton-row">';
    for ($i = 0; $i < $cols; $i++) {
        $width = match($i) {
            0 => 'short',
            $cols - 1 => 'short',
            default => 'medium'
        };
        $html .= "<td><div class=\"skeleton skeleton-text {$width}\"></div></td>";
    }
    $html .= '</tr>';
    return $html;
}

/**
 * 详情页骨架屏
 */
function skeleton_detail_page() {
    return <<<HTML
<div class="skeleton-detail">
    <div class="skeleton-detail-gallery">
        <div class="skeleton skeleton-image-large"></div>
        <div class="skeleton-thumbnails">
            <div class="skeleton skeleton-thumb"></div>
            <div class="skeleton skeleton-thumb"></div>
            <div class="skeleton skeleton-thumb"></div>
            <div class="skeleton skeleton-thumb"></div>
        </div>
    </div>
    <div class="skeleton-detail-info">
        <div class="skeleton skeleton-text short"></div>
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-text long"></div>
        <div class="skeleton skeleton-price-large"></div>
        <div class="skeleton-specs">
            <div class="skeleton skeleton-spec"></div>
            <div class="skeleton skeleton-spec"></div>
            <div class="skeleton skeleton-spec"></div>
        </div>
        <div class="skeleton skeleton-button-large"></div>
    </div>
</div>
HTML;
}
?>

<style>
/* 骨架屏基础样式 */
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 4px;
}

@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* 骨架屏网格 */
.skeleton-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

@media (max-width: 1024px) {
    .skeleton-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .skeleton-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .skeleton-grid {
        grid-template-columns: 1fr;
    }
}

/* 骨架屏卡片 */
.skeleton-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    padding: 16px;
}

.skeleton-image {
    aspect-ratio: 1;
    margin-bottom: 12px;
    border-radius: 8px;
}

.skeleton-content {
    padding: 0;
}

.skeleton-text {
    height: 14px;
    margin-bottom: 8px;
}

.skeleton-text.short { width: 40%; }
.skeleton-text.medium { width: 70%; }
.skeleton-text.long { width: 100%; }

.skeleton-price {
    height: 24px;
    width: 50%;
    margin: 12px 0;
}

.skeleton-button {
    height: 40px;
    width: 100%;
    border-radius: 20px;
}

/* 列表项骨架屏 */
.skeleton-list-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: white;
    border-radius: 8px;
    margin-bottom: 8px;
}

.skeleton-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    flex-shrink: 0;
}

.skeleton-list-content {
    flex: 1;
}

/* 表格骨架屏 */
.skeleton-row td {
    padding: 16px 12px;
}

/* 详情页骨架屏 */
.skeleton-detail {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    padding: 24px;
    background: white;
    border-radius: 12px;
}

@media (max-width: 768px) {
    .skeleton-detail {
        grid-template-columns: 1fr;
    }
}

.skeleton-image-large {
    aspect-ratio: 1;
    border-radius: 12px;
}

.skeleton-thumbnails {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.skeleton-thumb {
    width: 60px;
    height: 60px;
    border-radius: 8px;
}

.skeleton-title {
    height: 28px;
    width: 80%;
    margin: 16px 0;
}

.skeleton-price-large {
    height: 36px;
    width: 40%;
    margin: 16px 0;
}

.skeleton-specs {
    display: flex;
    gap: 8px;
    margin: 16px 0;
}

.skeleton-spec {
    width: 80px;
    height: 36px;
    border-radius: 8px;
}

.skeleton-button-large {
    height: 48px;
    width: 100%;
    border-radius: 24px;
    margin-top: 16px;
}

/* 隐藏骨架屏（加载完成后） */
.skeleton-hidden {
    display: none !important;
}

/* 淡入动画 */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>

<script>
/**
 * 骨架屏管理器
 */
class SkeletonLoader {
    constructor() {
        this.init();
    }

    init() {
        // 监听所有带有 data-skeleton 属性的容器
        document.querySelectorAll('[data-skeleton]').forEach(container => {
            this.showSkeleton(container);
        });
    }

    showSkeleton(container) {
        const type = container.dataset.skeleton;
        const count = parseInt(container.dataset.count) || 8;

        // 创建骨架屏容器
        const skeletonWrapper = document.createElement('div');
        skeletonWrapper.className = 'skeleton-wrapper';
        skeletonWrapper.id = 'skeleton-' + Math.random().toString(36).substr(2, 9);

        // 根据类型生成骨架屏
        switch (type) {
            case 'product-grid':
                skeletonWrapper.innerHTML = this.generateProductGrid(count);
                break;
            case 'list':
                skeletonWrapper.innerHTML = this.generateList(count);
                break;
            case 'detail':
                skeletonWrapper.innerHTML = this.generateDetail();
                break;
            default:
                skeletonWrapper.innerHTML = this.generateProductGrid(count);
        }

        // 插入骨架屏
        container.insertBefore(skeletonWrapper, container.firstChild);

        // 隐藏实际内容
        const actualContent = container.querySelector('.actual-content');
        if (actualContent) {
            actualContent.style.display = 'none';
        }
    }

    hideSkeleton(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const skeleton = container.querySelector('.skeleton-wrapper');
        const actualContent = container.querySelector('.actual-content');

        if (skeleton) {
            skeleton.classList.add('skeleton-hidden');
        }

        if (actualContent) {
            actualContent.style.display = '';
            actualContent.classList.add('fade-in');
        }
    }

    generateProductGrid(count) {
        let html = '<div class="skeleton-grid">';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton-card">
                    <div class="skeleton skeleton-image"></div>
                    <div class="skeleton-content">
                        <div class="skeleton skeleton-text short"></div>
                        <div class="skeleton skeleton-text long"></div>
                        <div class="skeleton skeleton-text medium"></div>
                        <div class="skeleton skeleton-price"></div>
                        <div class="skeleton skeleton-button"></div>
                    </div>
                </div>
            `;
        }
        html += '</div>';
        return html;
    }

    generateList(count) {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton-list-item">
                    <div class="skeleton skeleton-avatar"></div>
                    <div class="skeleton-list-content">
                        <div class="skeleton skeleton-text medium"></div>
                        <div class="skeleton skeleton-text long"></div>
                    </div>
                </div>
            `;
        }
        return html;
    }

    generateDetail() {
        return `
            <div class="skeleton-detail">
                <div class="skeleton-detail-gallery">
                    <div class="skeleton skeleton-image-large"></div>
                    <div class="skeleton-thumbnails">
                        <div class="skeleton skeleton-thumb"></div>
                        <div class="skeleton skeleton-thumb"></div>
                        <div class="skeleton skeleton-thumb"></div>
                        <div class="skeleton skeleton-thumb"></div>
                    </div>
                </div>
                <div class="skeleton-detail-info">
                    <div class="skeleton skeleton-text short"></div>
                    <div class="skeleton skeleton-title"></div>
                    <div class="skeleton skeleton-text long"></div>
                    <div class="skeleton skeleton-price-large"></div>
                    <div class="skeleton-specs">
                        <div class="skeleton skeleton-spec"></div>
                        <div class="skeleton skeleton-spec"></div>
                        <div class="skeleton skeleton-spec"></div>
                    </div>
                    <div class="skeleton skeleton-button-large"></div>
                </div>
            </div>
        `;
    }
}

// 全局骨架屏实例
window.skeletonLoader = new SkeletonLoader();

// 便捷函数
function hideSkeleton(containerId) {
    window.skeletonLoader.hideSkeleton(containerId);
}
</script>
