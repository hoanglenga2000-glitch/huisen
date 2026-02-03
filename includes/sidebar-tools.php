<?php
/**
 * ==========================================
 * 汇森科技 - 侧边悬浮工具栏
 * 包含客服、购物车预览、APP下载、回到顶部
 * ==========================================
 */

// 获取购物车数量（如果有session）
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}
?>
<!-- 侧边悬浮工具栏 -->
<div class="sidebar-tools" id="sidebarTools">
    <!-- 在线客服 -->
    <div class="sidebar-tool-item" data-tool="service" onclick="openServiceChat()">
        <span class="icon">💬</span>
        <span class="label">客服</span>
        <!-- 客服弹窗 -->
        <div class="popup service-popup">
            <div class="popup-header" style="font-weight: 600; margin-bottom: 12px; color: #1f2937;">联系客服</div>
            <div class="popup-body">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <span style="font-size: 24px;">📞</span>
                    <div>
                        <div style="font-size: 12px; color: #9ca3af;">客服热线</div>
                        <div style="font-weight: 600; color: #1f2937;">400-XXX-XXXX</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <span style="font-size: 24px;">💬</span>
                    <div>
                        <div style="font-size: 12px; color: #9ca3af;">微信咨询</div>
                        <div style="font-weight: 600; color: #1f2937;">huisen_tech</div>
                    </div>
                </div>
                <div style="font-size: 12px; color: #9ca3af; text-align: center; padding-top: 8px; border-top: 1px solid #f0f0f0;">
                    工作时间: 9:00-21:00
                </div>
            </div>
        </div>
    </div>

    <!-- 购物车/询价单 -->
    <div class="sidebar-tool-item" data-tool="cart" onclick="location.href='<?php echo isset($base_path) ? $base_path : ''; ?>cart.php'">
        <span class="icon">🛒</span>
        <span class="label">询价</span>
        <?php if ($cart_count > 0): ?>
        <span class="badge cart-badge"><?php echo $cart_count; ?></span>
        <?php endif; ?>
        <!-- 购物车预览弹窗 -->
        <div class="popup cart-popup" id="cartPreviewPopup">
            <div class="popup-header" style="font-weight: 600; margin-bottom: 12px; color: #1f2937; display: flex; justify-content: space-between; align-items: center;">
                <span>询价单</span>
                <span style="font-size: 12px; color: #9ca3af;" id="cartPreviewCount"><?php echo $cart_count; ?>件商品</span>
            </div>
            <div class="popup-body" id="cartPreviewItems" style="max-height: 200px; overflow-y: auto;">
                <!-- 动态加载购物车商品 -->
                <div style="text-align: center; padding: 20px; color: #9ca3af;">
                    <div style="font-size: 32px; margin-bottom: 8px;">🛒</div>
                    <div>询价单暂无商品</div>
                </div>
            </div>
            <div style="padding-top: 12px; border-top: 1px solid #f0f0f0; margin-top: 12px;">
                <a href="<?php echo isset($base_path) ? $base_path : ''; ?>cart.php"
                   style="display: block; text-align: center; padding: 10px; background: var(--brand-red); color: white; border-radius: 8px; font-size: 14px; font-weight: 500;">
                    查看询价单
                </a>
            </div>
        </div>
    </div>

    <!-- APP下载 -->
    <div class="sidebar-tool-item" data-tool="app">
        <span class="icon">📱</span>
        <span class="label">APP</span>
        <!-- 二维码弹窗 -->
        <div class="popup app-popup">
            <div class="popup-header" style="font-weight: 600; margin-bottom: 12px; color: #1f2937; text-align: center;">扫码下载APP</div>
            <div class="popup-body" style="text-align: center;">
                <div style="width: 140px; height: 140px; background: #f5f5f5; border-radius: 8px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 48px;">📲</span>
                </div>
                <div style="font-size: 12px; color: #6b7280;">使用微信或浏览器扫码下载</div>
                <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">iOS & Android 通用</div>
            </div>
        </div>
    </div>

    <!-- 回到顶部 -->
    <div class="sidebar-tool-item" data-tool="top" onclick="scrollToTop()" id="backToTopBtn">
        <span class="icon">⬆</span>
        <span class="label">顶部</span>
    </div>
</div>

<style>
/* 侧边工具栏样式增强 */
.sidebar-tools {
    position: fixed;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    gap: 2px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-radius: 12px;
    overflow: hidden;
}

.sidebar-tool-item {
    width: 54px;
    height: 54px;
    background: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    border-bottom: 1px solid #f0f0f0;
}

.sidebar-tool-item:last-child {
    border-bottom: none;
}

.sidebar-tool-item:hover {
    background: var(--brand-red);
}

.sidebar-tool-item:hover .icon,
.sidebar-tool-item:hover .label {
    color: white !important;
}

.sidebar-tool-item .icon {
    font-size: 20px;
    line-height: 1;
}

.sidebar-tool-item .label {
    font-size: 10px;
    color: #9ca3af;
    margin-top: 2px;
}

.sidebar-tool-item .badge {
    position: absolute;
    top: 4px;
    right: 4px;
    min-width: 18px;
    height: 18px;
    background: var(--brand-red);
    color: white;
    font-size: 11px;
    font-weight: 600;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
}

.sidebar-tool-item:hover .badge {
    background: white;
    color: var(--brand-red);
}

/* 弹出面板 */
.sidebar-tool-item .popup {
    position: absolute;
    right: 64px;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    padding: 16px;
    min-width: 260px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s;
    pointer-events: none;
}

.sidebar-tool-item .popup::after {
    content: '';
    position: absolute;
    right: -8px;
    top: 50%;
    transform: translateY(-50%);
    border: 8px solid transparent;
    border-left-color: white;
}

.sidebar-tool-item:hover .popup {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

/* 回到顶部按钮淡入淡出 */
#backToTopBtn {
    transition: opacity 0.3s;
}

/* 移动端适配 */
@media (max-width: 768px) {
    .sidebar-tools {
        right: 10px;
        bottom: 80px;
        top: auto;
        transform: none;
    }

    .sidebar-tool-item {
        width: 46px;
        height: 46px;
    }

    .sidebar-tool-item .icon {
        font-size: 18px;
    }

    .sidebar-tool-item .label {
        display: none;
    }

    .sidebar-tool-item .popup {
        display: none !important;
    }
}
</style>

<script>
// 客服弹窗
function openServiceChat() {
    // 可以集成第三方客服系统
    // 这里使用简单的弹窗
}

// 回到顶部
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// 监听滚动，控制回到顶部按钮显示
document.addEventListener('DOMContentLoaded', function() {
    var backToTopBtn = document.getElementById('backToTopBtn');
    if (backToTopBtn) {
        // 初始隐藏
        backToTopBtn.style.opacity = '0.4';

        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTopBtn.style.opacity = '1';
            } else {
                backToTopBtn.style.opacity = '0.4';
            }
        });
    }
});
</script>
