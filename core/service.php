<?php
/**
 * ==========================================
 * V5 售后服务/帮助中心 - 九机网专业风格
 * ==========================================
 */

require_once '../config/config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>售后服务 - 汇森科技</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --brand-red: #e1251b; }

        .faq-item {
            transition: all 0.3s ease;
        }
        .faq-item.active .faq-answer {
            max-height: 500px;
            opacity: 1;
            padding-top: 16px;
        }
        .faq-item.active .faq-icon {
            transform: rotate(180deg);
        }
        .faq-answer {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .service-card {
            transition: all 0.3s ease;
        }
        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .contact-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- 顶部导航 -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="index_v4.php" class="flex items-center gap-3">
                    <span class="text-2xl font-bold" style="color: var(--brand-red);">汇森科技</span>
                    <span class="text-sm text-gray-500 hidden md:inline">专业手机批发平台</span>
                </a>

                <nav class="flex items-center gap-6 text-sm">
                    <a href="index_v4.php" class="text-gray-600 hover:text-gray-900 transition">手机首页</a>
                    <a href="quotes_v6.php" class="text-gray-600 hover:text-gray-900 transition">全部产品</a>
                    <a href="cart.php" class="text-gray-600 hover:text-gray-900 transition">询价单</a>
                    <a href="service.php" class="font-medium transition" style="color: var(--brand-red);">售后服务</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero区域 -->
    <section class="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">售后服务中心</h1>
            <p class="text-xl text-gray-300 mb-8">专业、快速、贴心的服务保障</p>
            <div class="flex flex-wrap justify-center gap-4">
                <div class="px-6 py-3 bg-white/10 backdrop-blur rounded-full">
                    <span class="text-green-400 mr-2">✓</span> 7天无理由退换
                </div>
                <div class="px-6 py-3 bg-white/10 backdrop-blur rounded-full">
                    <span class="text-green-400 mr-2">✓</span> 全国联保
                </div>
                <div class="px-6 py-3 bg-white/10 backdrop-blur rounded-full">
                    <span class="text-green-400 mr-2">✓</span> 专业技术支持
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-4 py-12">
        <!-- 服务保障 -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-center mb-10">服务保障</h2>
            <div class="grid md:grid-cols-4 gap-6">
                <div class="service-card bg-white rounded-2xl p-8 text-center shadow-sm">
                    <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2">正品保障</h3>
                    <p class="text-gray-500 text-sm">100%原装正品，假一赔十，可验证真伪</p>
                </div>

                <div class="service-card bg-white rounded-2xl p-8 text-center shadow-sm">
                    <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2">极速发货</h3>
                    <p class="text-gray-500 text-sm">现货商品当天发货，顺丰/京东快递直达</p>
                </div>

                <div class="service-card bg-white rounded-2xl p-8 text-center shadow-sm">
                    <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2">7天退换</h3>
                    <p class="text-gray-500 text-sm">7天内无理由退换货，让您购物无忧</p>
                </div>

                <div class="service-card bg-white rounded-2xl p-8 text-center shadow-sm">
                    <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2">专业支持</h3>
                    <p class="text-gray-500 text-sm">专业客服团队，一对一技术支持</p>
                </div>
            </div>
        </section>

        <!-- 售后政策 -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-center mb-10">售后政策</h2>
            <div class="bg-white rounded-2xl overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left font-semibold">问题类型</th>
                            <th class="px-6 py-4 text-left font-semibold">处理时限</th>
                            <th class="px-6 py-4 text-left font-semibold">处理方式</th>
                            <th class="px-6 py-4 text-left font-semibold">备注</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr>
                            <td class="px-6 py-4">收货7天内无理由退换</td>
                            <td class="px-6 py-4"><span class="text-green-600 font-medium">7天</span></td>
                            <td class="px-6 py-4">全额退款/换货</td>
                            <td class="px-6 py-4 text-gray-500 text-sm">商品需保持原包装完好</td>
                        </tr>
                        <tr class="bg-gray-50/50">
                            <td class="px-6 py-4">质量问题退换</td>
                            <td class="px-6 py-4"><span class="text-blue-600 font-medium">15天</span></td>
                            <td class="px-6 py-4">免费换新/退款</td>
                            <td class="px-6 py-4 text-gray-500 text-sm">需提供质量问题证据</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4">官方保修期内故障</td>
                            <td class="px-6 py-4"><span class="text-purple-600 font-medium">1年</span></td>
                            <td class="px-6 py-4">官方售后维修</td>
                            <td class="px-6 py-4 text-gray-500 text-sm">协助联系官方售后</td>
                        </tr>
                        <tr class="bg-gray-50/50">
                            <td class="px-6 py-4">物流损坏</td>
                            <td class="px-6 py-4"><span class="text-red-600 font-medium">签收时</span></td>
                            <td class="px-6 py-4">拒收/免费重发</td>
                            <td class="px-6 py-4 text-gray-500 text-sm">请当面验货</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 常见问题 FAQ -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-center mb-10">常见问题</h2>
            <div class="max-w-3xl mx-auto space-y-4">
                <div class="faq-item bg-white rounded-xl shadow-sm overflow-hidden">
                    <button class="w-full px-6 py-5 flex items-center justify-between text-left" onclick="toggleFaq(this)">
                        <span class="font-medium">如何验证手机是否为正品？</span>
                        <svg class="faq-icon w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="faq-answer px-6 pb-5 text-gray-600">
                        <p>我们提供多种验证方式：</p>
                        <ul class="mt-2 space-y-1 list-disc list-inside">
                            <li>苹果设备可通过官网序列号查询</li>
                            <li>华为/小米等可通过官方App验证</li>
                            <li>所有商品均提供正规发票</li>
                            <li>支持当面验货后付款</li>
                        </ul>
                    </div>
                </div>

                <div class="faq-item bg-white rounded-xl shadow-sm overflow-hidden">
                    <button class="w-full px-6 py-5 flex items-center justify-between text-left" onclick="toggleFaq(this)">
                        <span class="font-medium">批发价格是否还能优惠？</span>
                        <svg class="faq-icon w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="faq-answer px-6 pb-5 text-gray-600">
                        <p>我们提供阶梯价格优惠：</p>
                        <ul class="mt-2 space-y-1 list-disc list-inside">
                            <li>单次采购10台以上，享受额外折扣</li>
                            <li>长期合作客户，可签订年度框架协议</li>
                            <li>大客户专属价格通道</li>
                            <li>具体优惠请联系客服咨询</li>
                        </ul>
                    </div>
                </div>

                <div class="faq-item bg-white rounded-xl shadow-sm overflow-hidden">
                    <button class="w-full px-6 py-5 flex items-center justify-between text-left" onclick="toggleFaq(this)">
                        <span class="font-medium">支持哪些付款方式？</span>
                        <svg class="faq-icon w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="faq-answer px-6 pb-5 text-gray-600">
                        <ul class="space-y-1 list-disc list-inside">
                            <li>对公转账（可开具增值税专用发票）</li>
                            <li>支付宝/微信企业付款</li>
                            <li>货到付款（限兰州市区）</li>
                            <li>长期客户可申请账期付款</li>
                        </ul>
                    </div>
                </div>

                <div class="faq-item bg-white rounded-xl shadow-sm overflow-hidden">
                    <button class="w-full px-6 py-5 flex items-center justify-between text-left" onclick="toggleFaq(this)">
                        <span class="font-medium">发货及物流时效如何？</span>
                        <svg class="faq-icon w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="faq-answer px-6 pb-5 text-gray-600">
                        <ul class="space-y-1 list-disc list-inside">
                            <li>现货商品：当天16:00前付款，当天发货</li>
                            <li>省内：顺丰次日达</li>
                            <li>省外：顺丰2-3天送达</li>
                            <li>支持京东物流、德邦等多种方式</li>
                            <li>兰州市区支持同城配送</li>
                        </ul>
                    </div>
                </div>

                <div class="faq-item bg-white rounded-xl shadow-sm overflow-hidden">
                    <button class="w-full px-6 py-5 flex items-center justify-between text-left" onclick="toggleFaq(this)">
                        <span class="font-medium">如何申请退换货？</span>
                        <svg class="faq-icon w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="faq-answer px-6 pb-5 text-gray-600">
                        <p>退换货流程：</p>
                        <ol class="mt-2 space-y-1 list-decimal list-inside">
                            <li>联系客服说明退换原因</li>
                            <li>获取退货地址和退换单号</li>
                            <li>将商品完好包装后寄回</li>
                            <li>我们收到商品后1-2个工作日处理</li>
                            <li>退款原路返回/换货重新发出</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- 联系我们 -->
        <section>
            <h2 class="text-2xl font-bold text-center mb-10">联系我们</h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="contact-card rounded-2xl p-8 text-white text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2">客服热线</h3>
                    <p class="text-2xl font-bold mb-1">400-XXX-XXXX</p>
                    <p class="text-white/70 text-sm">工作时间：9:00 - 21:00</p>
                </div>

                <div class="bg-white rounded-2xl p-8 text-center shadow-sm">
                    <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2Z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2">微信客服</h3>
                    <p class="text-xl font-bold text-gray-900 mb-1">huisen_tech</p>
                    <p class="text-gray-500 text-sm">扫码添加，一对一服务</p>
                </div>

                <div class="bg-white rounded-2xl p-8 text-center shadow-sm">
                    <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2">公司地址</h3>
                    <p class="text-gray-900 mb-1">甘肃省兰州市城关区</p>
                    <p class="text-gray-500 text-sm">欢迎实地考察洽谈</p>
                </div>
            </div>
        </section>
    </main>

    <!-- 页脚 -->
    <footer class="bg-gray-900 text-white mt-20">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="border-t border-gray-800 pt-8 text-center text-sm text-gray-500">
                © 2026 汇森科技 版权所有 | 专业手机批发平台
            </div>
        </div>
    </footer>

    <script>
    function toggleFaq(btn) {
        const item = btn.closest('.faq-item');
        const wasActive = item.classList.contains('active');

        // 关闭所有
        document.querySelectorAll('.faq-item').forEach(el => el.classList.remove('active'));

        // 如果之前没打开，则打开当前
        if (!wasActive) {
            item.classList.add('active');
        }
    }
    </script>
</body>
</html>
