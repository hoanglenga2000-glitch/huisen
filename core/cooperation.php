<?php
/**
 * ==========================================
 * V5 批发合作页面 - 九机网专业风格
 * ==========================================
 */

require_once '../config/config.php';

$submitted = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company = trim($_POST['company'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $business = trim($_POST['business'] ?? '');
    $volume = trim($_POST['volume'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($contact) || empty($phone)) {
        $error = '请填写联系人和联系电话';
    } else {
        // 实际项目中这里应该保存到数据库或发送邮件
        $submitted = true;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>批发合作 - 汇森科技</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --brand-red: #e1251b; }

        .hero-bg {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        }

        .step-card {
            position: relative;
        }
        .step-card::after {
            content: '';
            position: absolute;
            top: 40px;
            right: -50%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--brand-red), transparent);
        }
        .step-card:last-child::after {
            display: none;
        }

        .advantage-card {
            transition: all 0.3s ease;
        }
        .advantage-card:hover {
            transform: translateY(-8px);
        }

        .form-input {
            transition: all 0.2s;
        }
        .form-input:focus {
            border-color: var(--brand-red);
            box-shadow: 0 0 0 3px rgba(225, 37, 27, 0.1);
        }

        .number-badge {
            background: linear-gradient(135deg, var(--brand-red) 0%, #ff6b6b 100%);
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
                    <a href="cooperation.php" class="font-medium transition" style="color: var(--brand-red);">批发合作</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero区域 -->
    <section class="hero-bg text-white py-24">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold mb-6 leading-tight">
                        成为汇森科技<br>
                        <span style="color: #ff6b6b;">批发合作伙伴</span>
                    </h1>
                    <p class="text-xl text-gray-300 mb-8">
                        源头直供 · 正品保障 · 价格优势 · 专业服务
                    </p>
                    <div class="flex flex-wrap gap-6">
                        <div class="text-center">
                            <div class="text-4xl font-bold text-white">1000+</div>
                            <div class="text-gray-400 text-sm">合作商家</div>
                        </div>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-white">50+</div>
                            <div class="text-gray-400 text-sm">品牌授权</div>
                        </div>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-white">99.8%</div>
                            <div class="text-gray-400 text-sm">客户满意度</div>
                        </div>
                    </div>
                </div>
                <div class="hidden lg:block">
                    <div class="relative">
                        <div class="absolute -inset-4 bg-gradient-to-r from-red-500/20 to-purple-500/20 rounded-3xl blur-2xl"></div>
                        <div class="relative bg-white/10 backdrop-blur-lg rounded-2xl p-8 border border-white/20">
                            <div class="text-6xl mb-4">🤝</div>
                            <h3 class="text-xl font-bold mb-2">诚邀合作</h3>
                            <p class="text-gray-300">无论您是手机零售店、电商卖家还是企业采购，我们都能为您提供最优质的货源和服务。</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-4 py-16">
        <!-- 合作优势 -->
        <section class="mb-20">
            <h2 class="text-3xl font-bold text-center mb-4">合作优势</h2>
            <p class="text-gray-500 text-center mb-12">选择汇森，就是选择放心与专业</p>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="advantage-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl">
                    <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">价格优势</h3>
                    <p class="text-gray-500">厂家直供，一手货源，价格比市场低10%-15%，让您的利润空间更大</p>
                </div>

                <div class="advantage-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl">
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">正品保障</h3>
                    <p class="text-gray-500">所有商品均为原装正品，支持官方验证，假一赔十承诺</p>
                </div>

                <div class="advantage-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">现货充足</h3>
                    <p class="text-gray-500">常年备货各品牌热销机型，当天下单当天发货，不让您错过任何商机</p>
                </div>

                <div class="advantage-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl">
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-2">专属服务</h3>
                    <p class="text-gray-500">一对一客户经理，7x12小时在线响应，专业售后团队保驾护航</p>
                </div>
            </div>
        </section>

        <!-- 合作流程 -->
        <section class="mb-20">
            <h2 class="text-3xl font-bold text-center mb-4">合作流程</h2>
            <p class="text-gray-500 text-center mb-12">简单四步，开启合作之旅</p>

            <div class="grid md:grid-cols-4 gap-8">
                <div class="step-card text-center">
                    <div class="number-badge w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold shadow-lg">
                        01
                    </div>
                    <h3 class="text-lg font-bold mb-2">提交申请</h3>
                    <p class="text-gray-500 text-sm">填写合作申请表单，提交您的基本信息和合作意向</p>
                </div>

                <div class="step-card text-center">
                    <div class="number-badge w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold shadow-lg">
                        02
                    </div>
                    <h3 class="text-lg font-bold mb-2">资质审核</h3>
                    <p class="text-gray-500 text-sm">我们将在1-2个工作日内完成审核并与您联系</p>
                </div>

                <div class="step-card text-center">
                    <div class="number-badge w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold shadow-lg">
                        03
                    </div>
                    <h3 class="text-lg font-bold mb-2">签订协议</h3>
                    <p class="text-gray-500 text-sm">双方确认合作条款，签订正式合作协议</p>
                </div>

                <div class="step-card text-center">
                    <div class="number-badge w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold shadow-lg">
                        04
                    </div>
                    <h3 class="text-lg font-bold mb-2">开始合作</h3>
                    <p class="text-gray-500 text-sm">开通专属账户，享受批发价格和专属服务</p>
                </div>
            </div>
        </section>

        <!-- 入驻申请表单 -->
        <section class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-8 py-6 text-white">
                    <h2 class="text-2xl font-bold">申请成为合作伙伴</h2>
                    <p class="text-red-100 mt-1">填写以下信息，我们将尽快与您联系</p>
                </div>

                <?php if ($submitted): ?>
                <div class="p-8 text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">申请提交成功！</h3>
                    <p class="text-gray-500 mb-6">我们的商务经理将在1-2个工作日内与您联系，请保持电话畅通。</p>
                    <a href="index_v4.php" class="inline-block px-6 py-3 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition">
                        返回首页
                    </a>
                </div>
                <?php else: ?>
                <form method="POST" class="p-8 space-y-6">
                    <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">公司名称</label>
                            <input type="text" name="company"
                                   class="form-input w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none"
                                   placeholder="请输入公司名称（选填）">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">联系人 <span class="text-red-500">*</span></label>
                            <input type="text" name="contact" required
                                   class="form-input w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none"
                                   placeholder="请输入您的姓名">
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">联系电话 <span class="text-red-500">*</span></label>
                            <input type="tel" name="phone" required
                                   class="form-input w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none"
                                   placeholder="请输入手机号码">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">所在地区</label>
                            <input type="text" name="region"
                                   class="form-input w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none"
                                   placeholder="如：甘肃省兰州市">
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">业务类型</label>
                            <select name="business"
                                    class="form-input w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none bg-white">
                                <option value="">请选择业务类型</option>
                                <option value="retail">手机零售店</option>
                                <option value="ecommerce">电商卖家</option>
                                <option value="enterprise">企业采购</option>
                                <option value="wholesale">二级批发商</option>
                                <option value="other">其他</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">月采购量</label>
                            <select name="volume"
                                    class="form-input w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none bg-white">
                                <option value="">请选择月采购量</option>
                                <option value="1-10">1-10台</option>
                                <option value="10-50">10-50台</option>
                                <option value="50-100">50-100台</option>
                                <option value="100-500">100-500台</option>
                                <option value="500+">500台以上</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">补充说明</label>
                        <textarea name="message" rows="4"
                                  class="form-input w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none resize-none"
                                  placeholder="请描述您的合作需求或其他想说的话..."></textarea>
                    </div>

                    <button type="submit"
                            class="w-full py-4 rounded-xl text-lg font-bold text-white transition-all hover:opacity-90 hover:shadow-lg"
                            style="background: var(--brand-red);">
                        提交申请
                    </button>

                    <p class="text-center text-gray-400 text-sm">
                        提交即表示您同意我们的服务条款和隐私政策
                    </p>
                </form>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- 页脚 -->
    <footer class="bg-gray-900 text-white mt-20">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="text-center">
                <h3 class="text-2xl font-bold mb-4" style="color: var(--brand-red);">汇森科技</h3>
                <p class="text-gray-400 mb-6">甘肃省专业手机批发平台</p>
                <div class="flex justify-center gap-8 text-sm text-gray-400">
                    <span>客服热线: 400-XXX-XXXX</span>
                    <span>微信: huisen_tech</span>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-500">
                © 2026 汇森科技 版权所有
            </div>
        </div>
    </footer>
</body>
</html>
