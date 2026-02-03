<?php
/**
 * ==========================================
 * 汇森科技 - 公共页脚组件 v2.1
 * B2B Professional Footer + Mobile Bottom Nav
 * ==========================================
 * Stage 7 升级：
 * 1. 服务承诺条 (4列图标)
 * 2. 核心链接区 (品牌/快速链接/服务保障/联系我们)
 * 3. 版权条
 *
 * Stage 9 升级：
 * 4. Mobile Bottom Navigation (仅手机显示)
 */

$footer_base = isset($base_path) ? $base_path : '';

// 获取当前页面用于高亮导航
$current_page = basename($_SERVER['PHP_SELF']);
$current_uri = $_SERVER['REQUEST_URI'] ?? '';

// 判断当前页面
$is_home = ($current_page === 'index.php' || $current_page === 'index_v4.php' || $current_uri === '/');
$is_category = ($current_page === 'quotes_v6.php' || strpos($current_uri, 'quotes') !== false);
$is_cart = ($current_page === 'cart.php');
$is_user = ($current_page === 'user_center.php' || $current_page === 'profile.php' || $current_page === 'orders.php');

// 获取购物车数量（安全检查 session 是否已启动）
$cart_count = 0;
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}
?>

<!-- Desktop Footer (电脑端显示) -->
<footer class="site-footer mt-16 hidden md:block">
    <!-- 服务承诺条 -->
    <div class="bg-gray-800">
        <div class="max-w-screen-xl mx-auto px-4 py-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <!-- 正品保障 -->
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-green-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-7 h-7 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-white">正品保障</div>
                        <div class="text-sm text-gray-400">100%原装正品</div>
                    </div>
                </div>
                <!-- 闪电发货 -->
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-blue-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-white">闪电发货</div>
                        <div class="text-sm text-gray-400">当日订单当日发</div>
                    </div>
                </div>
                <!-- 7天退换 -->
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-orange-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-7 h-7 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-white">7天退换</div>
                        <div class="text-sm text-gray-400">无理由退换货</div>
                    </div>
                </div>
                <!-- 全国联保 -->
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-purple-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-7 h-7 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-white">全国联保</div>
                        <div class="text-sm text-gray-400">官方售后保障</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 核心链接区 -->
    <div class="bg-gray-900">
        <div class="max-w-screen-xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Col 1: Brand 品牌介绍 -->
                <div>
                    <h3 class="text-xl font-bold text-white mb-4">
                        <span class="text-red-500">汇森</span>科技
                    </h3>
                    <p class="text-gray-400 text-sm mb-6 leading-relaxed">
                        专业手机批发商，10年行业经验，为您提供最优质的货源和最实惠的批发价格。正品保障，源头直供。
                    </p>
                    <!-- 社交媒体 -->
                    <div class="flex items-center gap-4">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-gray-400 hover:bg-green-600 hover:text-white transition" title="微信">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 01.213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 00.167-.054l1.903-1.114a.864.864 0 01.717-.098 10.16 10.16 0 002.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 01-1.162 1.178A1.17 1.17 0 014.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 01-1.162 1.178 1.17 1.17 0 01-1.162-1.178c0-.651.52-1.18 1.162-1.18zm5.34 2.867c-1.797-.052-3.746.512-5.28 1.786-1.72 1.428-2.687 3.72-1.78 6.22.942 2.453 3.666 4.229 6.884 4.229.826 0 1.622-.12 2.361-.336a.722.722 0 01.598.082l1.584.926a.272.272 0 00.14.045c.134 0 .24-.108.24-.243 0-.06-.024-.12-.04-.178l-.327-1.233a.49.49 0 01.177-.553C23.008 18.253 24 16.553 24 14.652c0-3.249-2.941-5.794-7.062-5.794zm-2.036 2.87c.535 0 .969.44.969.982a.976.976 0 01-.969.983.976.976 0 01-.969-.983c0-.542.434-.982.97-.982zm4.072 0c.535 0 .969.44.969.982a.976.976 0 01-.969.983.976.976 0 01-.969-.983c0-.542.434-.982.97-.982z"/>
                            </svg>
                        </a>
                        <a href="tel:400-XXX-XXXX" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-gray-400 hover:bg-red-600 hover:text-white transition" title="电话">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-gray-400 hover:bg-blue-600 hover:text-white transition" title="QQ">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.003 2c-2.265 0-6.29 1.364-6.29 7.325v1.195S3.55 14.96 3.55 17.474c0 .665.17 1.025.281 1.025.114 0 .902-.484 1.748-2.072 0 0-.18 2.197 1.904 3.967 0 0-1.77.495-1.77 1.182 0 .686 4.078.43 6.29.43 2.213 0 6.29.257 6.29-.43 0-.687-1.77-1.182-1.77-1.182 2.085-1.77 1.904-3.967 1.904-3.967.846 1.588 1.634 2.072 1.746 2.072.111 0 .283-.36.283-1.025 0-2.514-2.166-6.954-2.166-6.954V9.325C18.29 3.364 14.268 2 12.003 2z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Col 2: Quick Links 快速链接 -->
                <div>
                    <h4 class="font-bold text-white mb-5">快速链接</h4>
                    <ul class="space-y-3 text-sm">
                        <li>
                            <a href="<?php echo $footer_base; ?>index.php" class="text-gray-400 hover:text-white transition flex items-center gap-2">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                官网首页
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $footer_base; ?>core/index_v4.php" class="text-gray-400 hover:text-white transition flex items-center gap-2">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                今日报价
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $footer_base; ?>core/quotes_v6.php" class="text-gray-400 hover:text-white transition flex items-center gap-2">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                全部产品
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $footer_base; ?>core/cart.php" class="text-gray-400 hover:text-white transition flex items-center gap-2">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                我的询价单
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $footer_base; ?>core/user_center.php" class="text-gray-400 hover:text-white transition flex items-center gap-2">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                个人中心
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Col 3: Help 服务保障 -->
                <div>
                    <h4 class="font-bold text-white mb-5">服务保障</h4>
                    <ul class="space-y-3 text-sm">
                        <li>
                            <a href="<?php echo $footer_base; ?>core/after_sales.php" class="text-gray-400 hover:text-white transition flex items-center gap-2">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                售后政策
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-white transition flex items-center gap-2">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                常见问题
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-white transition flex items-center gap-2">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                支付方式
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-white transition flex items-center gap-2">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                物流配送
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $footer_base; ?>core/cooperation.php" class="text-gray-400 hover:text-white transition flex items-center gap-2">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                批发合作
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Col 4: Contact 联系我们 (高亮区域) -->
                <div>
                    <h4 class="font-bold text-white mb-5">联系我们</h4>
                    <div class="bg-gray-800 rounded-xl p-5">
                        <!-- 客服热线 -->
                        <div class="mb-4">
                            <div class="text-gray-400 text-xs mb-1">客服热线</div>
                            <div class="text-2xl font-bold text-red-500">400-XXX-XXXX</div>
                        </div>
                        <!-- 工作时间 -->
                        <div class="space-y-2 text-sm text-gray-400">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>工作时间: 9:00 - 21:00</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span>微信: huisen_tech</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>甘肃省兰州市</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 版权条 -->
    <div class="bg-black">
        <div class="max-w-screen-xl mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-gray-500">
                <p>© <?php echo date('Y'); ?> 甘肃汇森信息科技有限公司 版权所有 | 专业手机批发平台</p>
                <div class="flex items-center gap-4">
                    <a href="#" class="hover:text-gray-400 transition">关于我们</a>
                    <span class="text-gray-700">|</span>
                    <a href="#" class="hover:text-gray-400 transition">隐私政策</a>
                    <span class="text-gray-700">|</span>
                    <a href="#" class="hover:text-gray-400 transition">服务条款</a>
                    <span class="text-gray-700">|</span>
                    <a href="#" class="hover:text-gray-400 transition">ICP备案号</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Mobile Footer (简化版，手机端显示) -->
<footer class="site-footer mt-16 mb-20 block md:hidden">
    <div class="bg-gray-800 px-4 py-6">
        <div class="text-center mb-4">
            <h3 class="text-lg font-bold text-white mb-2"><span class="text-red-500">汇森</span>科技</h3>
            <p class="text-gray-400 text-sm">专业手机批发平台</p>
        </div>
        <div class="flex justify-center gap-6 mb-4">
            <div class="text-center">
                <div class="text-green-400 text-lg mb-1">✓</div>
                <div class="text-gray-400 text-xs">正品保障</div>
            </div>
            <div class="text-center">
                <div class="text-blue-400 text-lg mb-1">⚡</div>
                <div class="text-gray-400 text-xs">闪电发货</div>
            </div>
            <div class="text-center">
                <div class="text-orange-400 text-lg mb-1">↩</div>
                <div class="text-gray-400 text-xs">7天退换</div>
            </div>
            <div class="text-center">
                <div class="text-purple-400 text-lg mb-1">🛡</div>
                <div class="text-gray-400 text-xs">全国联保</div>
            </div>
        </div>
        <div class="text-center text-gray-500 text-xs">
            © <?php echo date('Y'); ?> 甘肃汇森信息科技有限公司
        </div>
    </div>
</footer>

<!-- ========================================== -->
<!-- Mobile Bottom Navigation (仅手机显示) -->
<!-- ========================================== -->
<nav class="mobile-bottom-nav block md:hidden fixed bottom-0 left-0 w-full z-50 bg-white border-t border-gray-100"
     style="box-shadow: 0 -2px 10px rgba(0,0,0,0.05); padding-bottom: env(safe-area-inset-bottom);">
    <div class="flex justify-around items-center h-14">
        <!-- 首页 -->
        <a href="<?php echo $footer_base; ?>index.php"
           class="flex flex-col items-center justify-center flex-1 py-2 <?php echo $is_home ? 'text-red-500' : 'text-gray-500'; ?>">
            <svg class="w-6 h-6" fill="<?php echo $is_home ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="<?php echo $is_home ? '0' : '1.5'; ?>" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-[10px] mt-0.5 font-medium">首页</span>
        </a>

        <!-- 分类 -->
        <a href="<?php echo $footer_base; ?>core/quotes_v6.php"
           class="flex flex-col items-center justify-center flex-1 py-2 <?php echo $is_category ? 'text-red-500' : 'text-gray-500'; ?>">
            <svg class="w-6 h-6" fill="<?php echo $is_category ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="<?php echo $is_category ? '0' : '1.5'; ?>" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span class="text-[10px] mt-0.5 font-medium">分类</span>
        </a>

        <!-- 进货单 (带Badge) -->
        <a href="<?php echo $footer_base; ?>core/cart.php"
           class="flex flex-col items-center justify-center flex-1 py-2 relative <?php echo $is_cart ? 'text-red-500' : 'text-gray-500'; ?>">
            <?php if ($cart_count > 0): ?>
            <span class="absolute top-1 right-1/4 min-w-[16px] h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1">
                <?php echo $cart_count > 99 ? '99+' : $cart_count; ?>
            </span>
            <?php endif; ?>
            <svg class="w-6 h-6" fill="<?php echo $is_cart ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="<?php echo $is_cart ? '0' : '1.5'; ?>" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="text-[10px] mt-0.5 font-medium">进货单</span>
        </a>

        <!-- 我的 -->
        <a href="<?php echo $footer_base; ?>core/user_center.php"
           class="flex flex-col items-center justify-center flex-1 py-2 <?php echo $is_user ? 'text-red-500' : 'text-gray-500'; ?>">
            <svg class="w-6 h-6" fill="<?php echo $is_user ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="<?php echo $is_user ? '0' : '1.5'; ?>" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="text-[10px] mt-0.5 font-medium">我的</span>
        </a>
    </div>
</nav>
