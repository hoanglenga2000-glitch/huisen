<?php
/**
 * ==========================================
 * 汇森科技 - 热门机型视差滚动组件 v1.0
 * ==========================================
 *
 * 使用方法：
 * <?php include 'includes/hot_products_slider.php'; ?>
 */

require_once __DIR__ . '/image_helper.php';

// 初始化图片助手
ImageHelper::init(isset($base_path) ? $base_path : '../');

// 获取热门产品数据
$hot_products = ImageHelper::getHotProductImages(8);
?>

<!-- 热门机型视差滚动区域 -->
<section class="hot-products-section">
    <div class="section-header">
        <h2 class="section-title">
            <span class="icon">🔥</span>
            热门机型
        </h2>
        <p class="section-subtitle">批发爆款，限时优惠</p>
    </div>

    <div class="hot-products-slider" id="hotProductsSlider">
        <div class="slider-track">
            <?php foreach ($hot_products as $index => $product): ?>
            <div class="slider-item" data-index="<?php echo $index; ?>">
                <div class="product-showcase">
                    <div class="product-image-wrapper">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="product-image"
                             loading="lazy">
                        <div class="parallax-bg"></div>
                    </div>
                    <div class="product-info-overlay">
                        <span class="brand-badge"><?php echo htmlspecialchars($product['brand']); ?></span>
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="price-tag">
                            <span class="price-label">批发指导价</span>
                            <span class="price-value">¥<span class="price-num">询价</span></span>
                        </div>
                        <a href="quotes_v6.php?search=<?php echo urlencode($product['name']); ?>" class="view-btn">
                            查看详情 →
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 导航按钮 -->
        <button class="slider-nav prev" id="sliderPrev">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <button class="slider-nav next" id="sliderNext">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>

        <!-- 指示器 -->
        <div class="slider-dots" id="sliderDots">
            <?php for ($i = 0; $i < count($hot_products); $i++): ?>
            <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
            <?php endfor; ?>
        </div>
    </div>
</section>

<style>
/* 热门机型区域 */
.hot-products-section {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    padding: 60px 0;
    margin: 40px 0;
    overflow: hidden;
    position: relative;
}

.hot-products-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
    background-size: 50px 50px;
    opacity: 0.5;
}

.section-header {
    text-align: center;
    margin-bottom: 40px;
    position: relative;
    z-index: 1;
}

.section-title {
    font-size: 32px;
    font-weight: 700;
    color: white;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.section-title .icon {
    font-size: 36px;
    animation: pulse 2s infinite;
}

.section-subtitle {
    color: rgba(255,255,255,0.7);
    font-size: 16px;
}

/* 滑动容器 */
.hot-products-slider {
    position: relative;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 60px;
}

.slider-track {
    display: flex;
    gap: 24px;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding: 20px 0;
}

.slider-track::-webkit-scrollbar {
    display: none;
}

/* 单个产品展示 */
.slider-item {
    flex: 0 0 320px;
    transition: transform 0.3s ease;
}

.slider-item:hover {
    transform: scale(1.02);
}

.product-showcase {
    background: rgba(255,255,255,0.05);
    border-radius: 20px;
    overflow: hidden;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    transition: all 0.3s ease;
}

.product-showcase:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.product-image-wrapper {
    position: relative;
    height: 280px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.parallax-bg {
    position: absolute;
    top: -20%;
    left: -20%;
    right: -20%;
    bottom: -20%;
    background: radial-gradient(circle at 30% 30%, rgba(211,47,47,0.2) 0%, transparent 50%);
    transition: transform 0.5s ease;
}

.product-showcase:hover .parallax-bg {
    transform: translate(10px, 10px);
}

.product-image {
    max-width: 80%;
    max-height: 80%;
    object-fit: contain;
    position: relative;
    z-index: 1;
    transition: transform 0.5s ease;
    filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3));
}

.product-showcase:hover .product-image {
    transform: scale(1.1) rotate(-2deg);
}

.product-info-overlay {
    padding: 24px;
    text-align: center;
}

.brand-badge {
    display: inline-block;
    padding: 4px 12px;
    background: rgba(211,47,47,0.8);
    color: white;
    font-size: 12px;
    font-weight: 600;
    border-radius: 20px;
    margin-bottom: 12px;
}

.product-info-overlay .product-name {
    font-size: 18px;
    font-weight: 700;
    color: white;
    margin-bottom: 12px;
}

.price-tag {
    margin-bottom: 16px;
}

.price-label {
    display: block;
    font-size: 12px;
    color: rgba(255,255,255,0.6);
    margin-bottom: 4px;
}

.price-value {
    font-size: 24px;
    font-weight: 700;
    color: #FF6B6B;
    font-family: 'JetBrains Mono', monospace;
}

.view-btn {
    display: inline-block;
    padding: 10px 24px;
    background: linear-gradient(135deg, #D32F2F 0%, #F44336 100%);
    color: white;
    font-weight: 600;
    border-radius: 25px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.view-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(211,47,47,0.4);
}

/* 导航按钮 */
.slider-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 50%;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.slider-nav:hover {
    background: rgba(211,47,47,0.8);
    border-color: #D32F2F;
}

.slider-nav.prev {
    left: 10px;
}

.slider-nav.next {
    right: 10px;
}

/* 指示器 */
.slider-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
}

.slider-dots .dot {
    width: 8px;
    height: 8px;
    background: rgba(255,255,255,0.3);
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.slider-dots .dot.active {
    width: 24px;
    border-radius: 4px;
    background: #D32F2F;
}

/* 响应式 */
@media (max-width: 768px) {
    .hot-products-section {
        padding: 40px 0;
    }

    .section-title {
        font-size: 24px;
    }

    .slider-item {
        flex: 0 0 280px;
    }

    .product-image-wrapper {
        height: 220px;
    }

    .slider-nav {
        display: none;
    }

    .hot-products-slider {
        padding: 0 16px;
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('hotProductsSlider');
    if (!slider) return;

    const track = slider.querySelector('.slider-track');
    const items = slider.querySelectorAll('.slider-item');
    const prevBtn = document.getElementById('sliderPrev');
    const nextBtn = document.getElementById('sliderNext');
    const dots = slider.querySelectorAll('.dot');

    let currentIndex = 0;
    const itemWidth = 344; // 320 + 24 gap

    function scrollToIndex(index) {
        if (index < 0) index = items.length - 1;
        if (index >= items.length) index = 0;

        currentIndex = index;
        track.scrollTo({
            left: index * itemWidth,
            behavior: 'smooth'
        });

        // 更新指示器
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => scrollToIndex(currentIndex - 1));
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => scrollToIndex(currentIndex + 1));
    }

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => scrollToIndex(index));
    });

    // 自动轮播
    let autoSlide = setInterval(() => scrollToIndex(currentIndex + 1), 5000);

    slider.addEventListener('mouseenter', () => clearInterval(autoSlide));
    slider.addEventListener('mouseleave', () => {
        autoSlide = setInterval(() => scrollToIndex(currentIndex + 1), 5000);
    });

    // 视差效果
    items.forEach(item => {
        item.addEventListener('mousemove', (e) => {
            const rect = item.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;

            const bg = item.querySelector('.parallax-bg');
            if (bg) {
                bg.style.transform = `translate(${x * 30}px, ${y * 30}px)`;
            }
        });

        item.addEventListener('mouseleave', () => {
            const bg = item.querySelector('.parallax-bg');
            if (bg) {
                bg.style.transform = 'translate(0, 0)';
            }
        });
    });
});
</script>
