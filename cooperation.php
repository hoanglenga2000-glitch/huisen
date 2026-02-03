<?php
/**
 * 批发合作页面
 * 包含公司实力文案和联系表格
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>批发合作 - 汇森科技</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand-red: #e53935; }
        body { font-family: 'Inter', -apple-system, sans-serif; }

        .feature-card {
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #e53935 0%, #ff6b6b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- 顶部导航 -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="/core/index_v4.php" class="flex items-center gap-2">
                    <span class="text-2xl font-bold" style="color: var(--brand-red);">汇森科技</span>
                    <span class="text-sm text-gray-500 hidden md:inline">专业手机批发平台</span>
                </a>

                <nav class="flex items-center gap-6 text-sm">
                    <a href="/core/index_v4.php" class="text-gray-600 hover:text-gray-900">首页</a>
                    <a href="/core/quotes_final.php" class="text-gray-600 hover:text-gray-900">手机报价</a>
                    <a href="/cooperation.php" class="font-medium" style="color: var(--brand-red);">批发合作</a>
                    <a href="/login.php" class="text-gray-600 hover:text-gray-900">登录</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-red-600 to-red-500 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-5xl font-bold mb-6">批发合作</h1>
            <p class="text-xl opacity-90 mb-8 max-w-2xl mx-auto">
                携手汇森科技，共创双赢未来<br>
                专业的手机批发服务，助力您的事业腾飞
            </p>
            <a href="#contact" class="inline-block px-8 py-4 bg-white text-red-600 rounded-full font-bold text-lg hover:bg-gray-100 transition shadow-xl">
                立即咨询合作
            </a>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-4 py-16">
        <!-- 公司实力 -->
        <section class="mb-20">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">为什么选择汇森科技</h2>
                <p class="text-gray-600">专业的团队 · 优质的货源 · 完善的服务</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
                <!-- 统计数据 -->
                <div class="bg-white rounded-2xl p-8 text-center shadow-sm">
                    <div class="stat-number mb-2">10+</div>
                    <p class="text-gray-600 font-medium">年行业经验</p>
                </div>

                <div class="bg-white rounded-2xl p-8 text-center shadow-sm">
                    <div class="stat-number mb-2">5000+</div>
                    <p class="text-gray-600 font-medium">合作客户</p>
                </div>

                <div class="bg-white rounded-2xl p-8 text-center shadow-sm">
                    <div class="stat-number mb-2">50+</div>
                    <p class="text-gray-600 font-medium">品牌授权</p>
                </div>

                <div class="bg-white rounded-2xl p-8 text-center shadow-sm">
                    <div class="stat-number mb-2">100%</div>
                    <p class="text-gray-600 font-medium">正品保障</p>
                </div>
            </div>

            <!-- 核心优势 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">源头直供</h3>
                    <p class="text-gray-600">
                        与各大品牌厂商直接合作，减少中间环节，确保价格优势和货源稳定。所有产品均为原装正品，支持全国联保。
                    </p>
                </div>

                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">价格优势</h3>
                    <p class="text-gray-600">
                        批发价格远低于市场零售价，量大从优。我们提供灵活的价格策略，让您获得最大的利润空间。
                    </p>
                </div>

                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">快速发货</h3>
                    <p class="text-gray-600">
                        现货充足，当天下单当天发货。我们在全国多地设有仓库，确保快速配送到您手中。
                    </p>
                </div>

                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-purple-100 flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">专业团队</h3>
                    <p class="text-gray-600">
                        拥有10年以上行业经验的专业团队，为您提供一对一的服务支持，解答您的所有疑问。
                    </p>
                </div>

                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-orange-100 flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">质量保障</h3>
                    <p class="text-gray-600">
                        所有产品均经过严格质检，支持7天无理由退换货。我们对产品质量负责到底。
                    </p>
                </div>

                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-pink-100 flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">售后无忧</h3>
                    <p class="text-gray-600">
                        完善的售后服务体系，7×24小时客服在线，随时为您解决问题，让您无后顾之忧。
                    </p>
                </div>
            </div>
        </section>

        <!-- 合作流程 -->
        <section class="mb-20">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">合作流程</h2>
                <p class="text-gray-600">简单四步，开启合作之旅</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl font-bold text-red-600">1</span>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">提交申请</h3>
                    <p class="text-sm text-gray-600">填写合作申请表，我们会尽快与您联系</p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl font-bold text-blue-600">2</span>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">洽谈细节</h3>
                    <p class="text-sm text-gray-600">专业客服与您沟通合作细节和价格政策</p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl font-bold text-green-600">3</span>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">签订合同</h3>
                    <p class="text-sm text-gray-600">双方达成一致，签订正式合作协议</p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 rounded-full bg-purple-100 flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl font-bold text-purple-600">4</span>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">开始合作</h3>
                    <p class="text-sm text-gray-600">享受优质服务，开启合作共赢之旅</p>
                </div>
            </div>
        </section>

        <!-- 联系表单 -->
        <section id="contact" class="bg-white rounded-3xl shadow-xl p-8 md:p-12">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- 左侧 - 联系信息 -->
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">立即联系我们</h2>
                    <p class="text-gray-600 mb-8">
                        填写下方表单，我们的专业团队将在24小时内与您取得联系，为您提供最优质的批发合作方案。
                    </p>

                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 mb-1">客服热线</h3>
                                <p class="text-gray-600">400-XXX-XXXX</p>
                                <p class="text-sm text-gray-500">周一至周日 9:00-21:00</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.5 6c-1.4 0-2.6.6-3.5 1.5C4 8.4 3.5 9.6 3.5 11c0 1.7.8 3.2 2.1 4.1-.1.5-.3 1.3-.6 2.4.9-.5 1.6-.9 2-.1.7.2 1.3.3 2 .3 1.4 0 2.6-.6 3.5-1.5 1-1 1.5-2.2 1.5-3.5s-.5-2.6-1.5-3.5C11.1 6.6 9.9 6 8.5 6z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 mb-1">微信客服</h3>
                                <p class="text-gray-600">huisen_tech</p>
                                <p class="text-sm text-gray-500">扫码添加客服微信</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 mb-1">商务邮箱</h3>
                                <p class="text-gray-600">business@huisen.com</p>
                                <p class="text-sm text-gray-500">大宗采购请发邮件咨询</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 右侧 - 表单 -->
                <div>
                    <form class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">您的姓名 *</label>
                            <input type="text" required
                                   placeholder="请输入您的姓名"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">联系电话 *</label>
                            <input type="tel" required
                                   placeholder="请输入您的手机号"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">公司名称</label>
                            <input type="text"
                                   placeholder="请输入您的公司名称（选填）"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">合作类型 *</label>
                            <select required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                <option value="">请选择合作类型</option>
                                <option value="wholesale">手机批发</option>
                                <option value="agent">品牌代理</option>
                                <option value="retail">零售合作</option>
                                <option value="other">其他合作</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">留言</label>
                            <textarea rows="4"
                                      placeholder="请简要描述您的合作需求..."
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"></textarea>
                        </div>

                        <button type="submit"
                                class="w-full py-4 rounded-lg text-white font-bold text-lg transition hover:opacity-90 shadow-lg"
                                style="background: var(--brand-red);">
                            提交申请
                        </button>

                        <p class="text-xs text-gray-500 text-center">
                            提交即代表您同意我们的隐私政策，我们将对您的信息严格保密
                        </p>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- 页脚 -->
    <footer class="bg-gray-900 text-white mt-20">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h3 class="text-lg font-bold mb-4" style="color: var(--brand-red);">汇森科技</h3>
                    <p class="text-gray-400 text-sm">专业手机批发商</p>
                </div>
                <div>
                    <h4 class="font-medium mb-4">产品分类</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="/core/quotes_final.php?category=手机" class="hover:text-white">手机</a></li>
                        <li><a href="/core/quotes_final.php?category=智能穿戴" class="hover:text-white">智能穿戴</a></li>
                        <li><a href="/core/quotes_final.php?category=平板" class="hover:text-white">平板电脑</a></li>
                        <li><a href="/core/quotes_final.php?category=配件" class="hover:text-white">配件</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium mb-4">服务保障</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li>✓ 原装正品</li>
                        <li>✓ 全国联保</li>
                        <li>✓ 7天无理由</li>
                        <li>✓ 批发价格</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium mb-4">联系我们</h4>
                    <p class="text-sm text-gray-400">客服热线: 400-XXX-XXXX</p>
                    <p class="text-sm text-gray-400 mt-2">微信: huisen_tech</p>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-sm text-gray-500">
                © 2026 甘肃汇森信息科技有限公司 版权所有
            </div>
        </div>
    </footer>
</body>
</html>
