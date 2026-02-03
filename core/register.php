<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="甘肃汇森信息科技有限公司 - 用户注册">
    <meta name="theme-color" content="#e1251b">
    <title>用户注册 | 汇森科技</title>

    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>汇</text></svg>">

    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- GSAP Animation Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700;900&display=swap" rel="stylesheet">

    <style>
        :root { --brand-red: #e1251b; }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans SC', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        .input-field {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--brand-red);
            background: white;
            box-shadow: 0 0 0 4px rgba(225, 37, 27, 0.1);
        }

        .input-field.error {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--brand-red) 0%, #ff4d4f 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(225, 37, 27, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .error-text {
            color: #ef4444;
            font-size: 13px;
            margin-top: 6px;
            display: none;
        }

        .error-text.show {
            display: block;
        }

        .success-message {
            background: #ecfdf5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: none;
        }

        .success-message.show {
            display: block;
        }

        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }

        .strength-weak { width: 33%; background: #ef4444; }
        .strength-medium { width: 66%; background: #f59e0b; }
        .strength-strong { width: 100%; background: #10b981; }

        .loader {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }

        .loader.show {
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- 顶部导航 -->
    <header class="bg-white/90 backdrop-blur-lg shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="index_v4.php" class="flex items-center gap-2">
                    <span class="text-2xl font-bold" style="color: var(--brand-red);">汇森科技</span>
                </a>
                <nav class="flex items-center gap-6 text-sm">
                    <a href="index_v4.php" class="text-gray-600 hover:text-gray-900 transition">首页</a>
                    <a href="quotes_v6.php" class="text-gray-600 hover:text-gray-900 transition">手机报价</a>
                    <a href="cart.php" class="text-gray-600 hover:text-gray-900 transition">询价单</a>
                    <a href="login.php" class="text-gray-600 hover:text-gray-900 transition">登录</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- 注册表单 -->
    <div class="min-h-[calc(100vh-64px)] flex items-center justify-center px-4 py-12">
        <div class="glass-card p-8 md:p-10 w-full max-w-md" id="registerCard">
            <!-- Logo 和标题 -->
            <div class="text-center mb-8">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg" style="background: var(--brand-red);">
                        <span class="text-white font-bold text-2xl">汇</span>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">创建账户</h1>
                <p class="text-gray-500 text-sm">加入汇森科技，享受专属批发价</p>
            </div>

            <!-- 成功消息 -->
            <div class="success-message" id="successMessage">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>注册成功！正在跳转到登录页面...</span>
                </div>
            </div>

            <!-- 注册表单 -->
            <form id="registerForm" class="space-y-5">
                <!-- 用户名 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">用户名</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="input-field"
                        placeholder="请输入用户名（4-20位字母数字）"
                        required
                        autocomplete="username"
                    >
                    <p class="error-text" id="usernameError">用户名格式不正确</p>
                </div>

                <!-- 手机号 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">手机号</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        class="input-field"
                        placeholder="请输入11位手机号"
                        required
                        autocomplete="tel"
                    >
                    <p class="error-text" id="phoneError">请输入正确的手机号</p>
                </div>

                <!-- 真实姓名 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">真实姓名</label>
                    <input
                        type="text"
                        id="realName"
                        name="real_name"
                        class="input-field"
                        placeholder="请输入您的真实姓名"
                        required
                    >
                    <p class="error-text" id="realNameError">请输入真实姓名</p>
                </div>

                <!-- 密码 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">密码</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="input-field"
                        placeholder="请输入密码（至少6位）"
                        required
                        autocomplete="new-password"
                    >
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrength"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1" id="passwordHint">密码强度提示</p>
                    <p class="error-text" id="passwordError">密码长度至少6位</p>
                </div>

                <!-- 确认密码 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">确认密码</label>
                    <input
                        type="password"
                        id="confirmPassword"
                        name="confirm_password"
                        class="input-field"
                        placeholder="请再次输入密码"
                        required
                        autocomplete="new-password"
                    >
                    <p class="error-text" id="confirmPasswordError">两次密码输入不一致</p>
                </div>

                <!-- 用户协议 -->
                <div class="flex items-start gap-2">
                    <input type="checkbox" id="agreement" class="mt-1 w-4 h-4 text-red-600 rounded" required>
                    <label for="agreement" class="text-sm text-gray-600">
                        我已阅读并同意 <a href="#" class="text-red-600 hover:underline">《用户服务协议》</a> 和 <a href="#" class="text-red-600 hover:underline">《隐私政策》</a>
                    </label>
                </div>

                <!-- 错误提示 -->
                <div class="text-red-500 text-sm text-center hidden" id="errorMessage"></div>

                <!-- 注册按钮 -->
                <button type="submit" class="btn-primary flex items-center justify-center gap-2" id="registerBtn">
                    <span id="btnText">立即注册</span>
                    <span class="loader" id="loader"></span>
                </button>
            </form>

            <!-- 已有账户 -->
            <div class="mt-6 text-center">
                <p class="text-gray-500 text-sm">
                    已有账户？
                    <a href="login.php" class="font-medium hover:underline" style="color: var(--brand-red);">立即登录</a>
                </p>
            </div>

            <!-- 返回首页 -->
            <div class="mt-4 text-center">
                <a href="../index.php" class="text-gray-400 hover:text-gray-600 text-sm transition">
                    ← 返回首页
                </a>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // 初始化
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            initPasswordStrength();
            initFormValidation();
            initCardAnimation();
        });

        // ============================================
        // 卡片动画
        // ============================================
        function initCardAnimation() {
            const card = document.getElementById('registerCard');
            if (typeof gsap !== 'undefined') {
                gsap.from(card, {
                    opacity: 0,
                    y: 30,
                    duration: 0.8,
                    ease: 'power3.out'
                });
            }
        }

        // ============================================
        // 密码强度检测
        // ============================================
        function initPasswordStrength() {
            const passwordInput = document.getElementById('password');
            const strengthBar = document.getElementById('passwordStrength');
            const hint = document.getElementById('passwordHint');

            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let message = '';

                if (password.length >= 6) strength++;
                if (password.length >= 10) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;

                strengthBar.className = 'password-strength-bar';

                if (password.length === 0) {
                    message = '密码强度提示';
                } else if (strength <= 2) {
                    strengthBar.classList.add('strength-weak');
                    message = '弱 - 建议使用更复杂的密码';
                } else if (strength <= 3) {
                    strengthBar.classList.add('strength-medium');
                    message = '中 - 可以更强';
                } else {
                    strengthBar.classList.add('strength-strong');
                    message = '强 - 密码安全';
                }

                hint.textContent = message;
            });
        }

        // ============================================
        // 表单验证
        // ============================================
        function initFormValidation() {
            const form = document.getElementById('registerForm');
            const registerBtn = document.getElementById('registerBtn');
            const btnText = document.getElementById('btnText');
            const loader = document.getElementById('loader');

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                // 重置错误状态
                resetErrors();

                // 获取表单数据
                const formData = {
                    username: document.getElementById('username').value.trim(),
                    phone: document.getElementById('phone').value.trim(),
                    real_name: document.getElementById('realName').value.trim(),
                    password: document.getElementById('password').value,
                    confirm_password: document.getElementById('confirmPassword').value
                };

                // 前端验证
                let hasError = false;

                // 用户名验证
                if (!/^[a-zA-Z0-9_]{4,20}$/.test(formData.username)) {
                    showFieldError('username', 'usernameError');
                    hasError = true;
                }

                // 手机号验证
                if (!/^1[3-9]\d{9}$/.test(formData.phone)) {
                    showFieldError('phone', 'phoneError');
                    hasError = true;
                }

                // 真实姓名验证
                if (formData.real_name.length < 2) {
                    showFieldError('realName', 'realNameError');
                    hasError = true;
                }

                // 密码验证
                if (formData.password.length < 6) {
                    showFieldError('password', 'passwordError');
                    hasError = true;
                }

                // 确认密码验证
                if (formData.password !== formData.confirm_password) {
                    showFieldError('confirmPassword', 'confirmPasswordError');
                    hasError = true;
                }

                if (hasError) return;

                // 禁用按钮，显示加载状态
                registerBtn.disabled = true;
                btnText.textContent = '注册中...';
                loader.classList.add('show');

                try {
                    // 发送注册请求
                    const response = await fetch('../api/auth.php?action=register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    });

                    const result = await response.json();

                    if (result.success) {
                        // 注册成功
                        document.getElementById('successMessage').classList.add('show');
                        form.style.display = 'none';

                        // 3秒后跳转到登录页
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                    } else {
                        showError(result.error || '注册失败，请稍后重试');
                        registerBtn.disabled = false;
                        btnText.textContent = '立即注册';
                        loader.classList.remove('show');
                    }
                } catch (error) {
                    showError('网络错误，请稍后重试');
                    registerBtn.disabled = false;
                    btnText.textContent = '立即注册';
                    loader.classList.remove('show');
                }
            });
        }

        function showFieldError(fieldId, errorId) {
            document.getElementById(fieldId).classList.add('error');
            document.getElementById(errorId).classList.add('show');
        }

        function resetErrors() {
            document.querySelectorAll('.input-field').forEach(el => el.classList.remove('error'));
            document.querySelectorAll('.error-text').forEach(el => el.classList.remove('show'));
            document.getElementById('errorMessage').classList.add('hidden');
        }

        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.classList.remove('hidden');
        }
    </script>
</body>
</html>
