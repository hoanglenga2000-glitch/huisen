<?php
/**
 * 专业登录页面 - 左右分栏布局
 * 支持扫码/账号登录切换
 */
session_start();

// 如果已登录，跳转到个人中心
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: /core/profile.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 汇森科技</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand-red: #e53935; }
        body { font-family: 'Inter', -apple-system, sans-serif; }

        .login-card {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
        }

        .tab-active {
            color: var(--brand-red);
            border-bottom: 2px solid var(--brand-red);
        }

        .input-field {
            transition: all 0.3s;
        }

        .input-field:focus {
            border-color: var(--brand-red);
            box-shadow: 0 0 0 3px rgba(229, 57, 53, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- 顶部导航 -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="/core/index_v4.php" class="flex items-center gap-2">
                    <span class="text-2xl font-bold" style="color: var(--brand-red);">汇森科技</span>
                    <span class="text-sm text-gray-500 hidden md:inline">专业手机批发平台</span>
                </a>

                <nav class="flex items-center gap-6 text-sm">
                    <a href="/core/index_v4.php" class="text-gray-600 hover:text-gray-900">首页</a>
                    <a href="/core/quotes_v6.php" class="text-gray-600 hover:text-gray-900">手机报价</a>
                    <a href="/cart.php" class="text-gray-600 hover:text-gray-900">询价单</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- 主体内容 -->
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <!-- 左侧 - 品牌介绍 -->
            <div class="hidden lg:block">
                <div class="text-center">
                    <h1 class="text-5xl font-bold mb-6" style="color: var(--brand-red);">汇森科技</h1>
                    <p class="text-2xl text-gray-700 mb-8">专业手机批发平台</p>
                    <div class="space-y-4 text-left max-w-md mx-auto">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 mb-1">正品保障</h3>
                                <p class="text-gray-600 text-sm">所有产品均为原装正品，全国联保</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 mb-1">批发价格</h3>
                                <p class="text-gray-600 text-sm">源头直供，价格更优惠</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 mb-1">急速发货</h3>
                                <p class="text-gray-600 text-sm">现货充足，当天下单当天发货</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 右侧 - 登录表单 -->
            <div class="login-card rounded-2xl shadow-2xl p-8 md:p-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-2 text-center">欢迎登录</h2>
                <p class="text-gray-500 text-center mb-8">登录后享受更多批发优惠</p>

                <!-- 错误消息 -->
                <div class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6" id="errorMessage"></div>

                <!-- 登录方式切换 -->
                <div class="flex justify-center gap-8 mb-8 border-b">
                    <button class="tab-active py-3 px-4 font-medium transition" id="accountTab" onclick="switchTab('account')">
                        账号登录
                    </button>
                    <button class="py-3 px-4 font-medium text-gray-500 hover:text-gray-900 transition" id="qrcodeTab" onclick="switchTab('qrcode')">
                        扫码登录
                    </button>
                </div>

                <!-- 账号登录表单 -->
                <form id="loginForm" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">用户名/手机号</label>
                        <input type="text" id="username" name="username"
                               placeholder="请输入用户名或手机号"
                               class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none"
                               required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">密码</label>
                        <input type="password" id="password" name="password"
                               placeholder="请输入密码"
                               class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none"
                               required>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="remember" class="w-4 h-4 rounded border-gray-300" style="accent-color: var(--brand-red);">
                            <span class="text-gray-600">记住我</span>
                        </label>
                        <a href="#" class="text-red-600 hover:text-red-700">忘记密码？</a>
                    </div>

                    <button type="submit" id="loginBtn"
                            class="w-full py-3 rounded-lg text-white font-medium transition hover:opacity-90 shadow-lg flex items-center justify-center gap-2"
                            style="background: var(--brand-red);">
                        <span id="btnText">立即登录</span>
                        <svg class="w-5 h-5 animate-spin hidden" id="loader" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>

                    <div class="text-center text-sm text-gray-600">
                        还没有账号？ <a href="/core/register.php" class="text-red-600 hover:text-red-700 font-medium">立即注册</a>
                    </div>
                    <div class="text-center text-xs text-gray-500 mt-2">
                        <a href="/core/login.php" class="text-gray-500 hover:text-gray-700">员工登录</a>
                    </div>
                </form>

                <!-- 扫码登录 -->
                <div id="qrcodeForm" class="hidden text-center">
                    <div class="w-64 h-64 mx-auto mb-6 bg-gray-100 rounded-xl flex items-center justify-center">
                        <div class="text-center">
                            <svg class="w-48 h-48 mx-auto text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm4 4H7V7h2v2zm-4 2h8v8H3v-8zm2 2v4h4v-4H5zm4 4H7v-2h2v2zm4-12h8v8h-8V3zm2 2v4h4V5h-4zm4 4h-2V7h2v2zm-4 2h8v8h-8v-8zm2 2v4h4v-4h-4zm4 4h-2v-2h2v2z"/>
                            </svg>
                            <p class="text-gray-500 mt-4">请使用微信扫码登录</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">打开微信，扫一扫登录</p>
                </div>

                <!-- 第三方登录 -->
                <div class="mt-8">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white text-gray-500">或使用以下方式登录</span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-center gap-4">
                        <button class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center text-white hover:bg-green-600 transition shadow-md" title="微信登录">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8.5 6c-1.4 0-2.6.6-3.5 1.5C4 8.4 3.5 9.6 3.5 11c0 1.7.8 3.2 2.1 4.1-.1.5-.3 1.3-.6 2.4.9-.5 1.6-.9 2-.1.7.2 1.3.3 2 .3 1.4 0 2.6-.6 3.5-1.5 1-1 1.5-2.2 1.5-3.5s-.5-2.6-1.5-3.5C11.1 6.6 9.9 6 8.5 6zM16 2c-1.8 0-3.4.8-4.7 2.1C10 5.4 9.2 7.1 9.2 9c0 2.2 1.1 4.2 2.8 5.4-.2.7-.5 1.7-.9 3.2 1.2-.7 2.1-1.2 2.7-1.5.9.3 1.8.4 2.7.4 1.8 0 3.4-.8 4.7-2.1C22.5 13.1 23.3 11.4 23.3 9.5s-.8-3.6-2.1-4.9C19.9 2.8 18.2 2 16 2z"/>
                            </svg>
                        </button>
                        <button class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center text-white hover:bg-blue-600 transition shadow-md" title="QQ登录">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.4 0-8-3.6-8-8s3.6-8 8-8 8 3.6 8 8-3.6 8-8 8z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="bg-gray-900 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-400">
            © 2026 甘肃汇森信息科技有限公司 版权所有
        </div>
    </footer>

    <script>
        function switchTab(tab) {
            const accountTab = document.getElementById('accountTab');
            const qrcodeTab = document.getElementById('qrcodeTab');
            const loginForm = document.getElementById('loginForm');
            const qrcodeForm = document.getElementById('qrcodeForm');

            if (tab === 'account') {
                accountTab.classList.add('tab-active');
                qrcodeTab.classList.remove('tab-active');
                loginForm.classList.remove('hidden');
                qrcodeForm.classList.add('hidden');
            } else {
                qrcodeTab.classList.add('tab-active');
                accountTab.classList.remove('tab-active');
                qrcodeForm.classList.remove('hidden');
                loginForm.classList.add('hidden');
            }
        }

        // 登录表单提交
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('errorMessage');
            const btn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const loader = document.getElementById('loader');

            if (!username || !password) {
                errorDiv.textContent = '请输入用户名和密码';
                errorDiv.classList.remove('hidden');
                return;
            }

            // 显示加载状态
            btn.disabled = true;
            btnText.textContent = '登录中...';
            loader.classList.remove('hidden');
            errorDiv.classList.add('hidden');

            try {
                const response = await fetch('/api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: username,
                        password: password
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // 登录成功，跳转到个人中心
                    window.location.href = '/core/profile.php';
                } else {
                    errorDiv.textContent = result.error || '登录失败，请检查用户名和密码';
                    errorDiv.classList.remove('hidden');
                }
            } catch (error) {
                errorDiv.textContent = '网络错误，请稍后重试';
                errorDiv.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btnText.textContent = '立即登录';
                loader.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
