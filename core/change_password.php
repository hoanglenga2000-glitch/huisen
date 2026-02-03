<?php
/**
 * ==========================================
 * 修改密码页面
 * ==========================================
 */

session_start();
require_once '../config/config.php';

// 检查登录状态
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$user = [
    'username' => $_SESSION['username'],
    'real_name' => $_SESSION['real_name'] ?? $_SESSION['username']
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密码 - 汇森科技</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --brand-red: #e1251b; }

        .input-field {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .input-field:focus {
            outline: none;
            border-color: var(--brand-red);
            box-shadow: 0 0 0 4px rgba(225, 37, 27, 0.1);
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- 顶部导航 -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="index_v4.php" class="flex items-center gap-2">
                    <span class="text-2xl font-bold" style="color: var(--brand-red);">汇森科技</span>
                </a>
                <nav class="flex items-center gap-6 text-sm">
                    <a href="index_v4.php" class="text-gray-600 hover:text-gray-900 transition">首页</a>
                    <a href="quotes_v6.php" class="text-gray-600 hover:text-gray-900 transition">手机报价</a>
                    <a href="cart.php" class="text-gray-600 hover:text-gray-900 transition">询价单</a>
                    <a href="profile.php" class="font-medium" style="color: var(--brand-red);">个人中心</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="max-w-xl mx-auto px-4 py-8">
        <!-- 面包屑 -->
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="profile.php" class="hover:text-gray-900">个人中心</a>
            <span>/</span>
            <span class="text-gray-900">修改密码</span>
        </div>

        <!-- 修改密码卡片 -->
        <div class="bg-white rounded-2xl shadow-sm p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-2xl" style="background: #fef2f2;">
                    🔐
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">修改密码</h1>
                    <p class="text-gray-500 text-sm">定期修改密码，保护账户安全</p>
                </div>
            </div>

            <!-- 成功消息 -->
            <div class="hidden bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6" id="successMessage">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>密码修改成功！</span>
                </div>
            </div>

            <!-- 错误消息 -->
            <div class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6" id="errorMessage"></div>

            <form id="passwordForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">当前密码</label>
                    <input type="password" id="oldPassword" name="old_password" class="input-field"
                           placeholder="请输入当前密码" required autocomplete="current-password">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">新密码</label>
                    <input type="password" id="newPassword" name="new_password" class="input-field"
                           placeholder="请输入新密码（至少6位）" required autocomplete="new-password">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrength"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1" id="passwordHint">密码强度提示</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">确认新密码</label>
                    <input type="password" id="confirmPassword" name="confirm_password" class="input-field"
                           placeholder="请再次输入新密码" required autocomplete="new-password">
                    <p class="text-red-500 text-xs mt-1 hidden" id="confirmError">两次密码输入不一致</p>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full py-3.5 text-white rounded-lg font-medium hover:opacity-90 transition flex items-center justify-center gap-2"
                            style="background: var(--brand-red);" id="submitBtn">
                        <span id="btnText">确认修改</span>
                        <svg class="w-5 h-5 animate-spin hidden" id="loader" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t">
                <h4 class="font-medium text-gray-900 mb-3">密码安全提示</h4>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li class="flex items-center gap-2">
                        <span class="text-green-500">✓</span>
                        密码长度至少6位
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="text-green-500">✓</span>
                        建议包含大小写字母和数字
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="text-green-500">✓</span>
                        避免使用生日、手机号等易猜密码
                    </li>
                </ul>
            </div>
        </div>
    </main>

    <script>
    // 密码强度检测
    document.getElementById('newPassword').addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('passwordStrength');
        const hint = document.getElementById('passwordHint');
        let strength = 0;

        if (password.length >= 6) strength++;
        if (password.length >= 10) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        strengthBar.className = 'password-strength-bar';

        if (password.length === 0) {
            hint.textContent = '密码强度提示';
        } else if (strength <= 2) {
            strengthBar.classList.add('strength-weak');
            hint.textContent = '弱 - 建议使用更复杂的密码';
        } else if (strength <= 3) {
            strengthBar.classList.add('strength-medium');
            hint.textContent = '中 - 可以更强';
        } else {
            strengthBar.classList.add('strength-strong');
            hint.textContent = '强 - 密码安全';
        }
    });

    // 确认密码验证
    document.getElementById('confirmPassword').addEventListener('input', function() {
        const newPwd = document.getElementById('newPassword').value;
        const confirmError = document.getElementById('confirmError');
        if (this.value && this.value !== newPwd) {
            confirmError.classList.remove('hidden');
        } else {
            confirmError.classList.add('hidden');
        }
    });

    // 表单提交
    document.getElementById('passwordForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const oldPassword = document.getElementById('oldPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // 验证
        if (newPassword.length < 6) {
            showError('新密码长度至少6位');
            return;
        }

        if (newPassword !== confirmPassword) {
            showError('两次密码输入不一致');
            return;
        }

        // 显示加载状态
        const btn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const loader = document.getElementById('loader');
        btn.disabled = true;
        btnText.textContent = '提交中...';
        loader.classList.remove('hidden');

        try {
            const response = await fetch('../api/auth.php?action=change_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    old_password: oldPassword,
                    new_password: newPassword
                })
            });

            const result = await response.json();

            if (result.success) {
                document.getElementById('successMessage').classList.remove('hidden');
                document.getElementById('errorMessage').classList.add('hidden');
                this.reset();
                document.getElementById('passwordStrength').className = 'password-strength-bar';
                document.getElementById('passwordHint').textContent = '密码强度提示';
            } else {
                showError(result.error || '密码修改失败');
            }
        } catch (error) {
            showError('网络错误，请稍后重试');
        } finally {
            btn.disabled = false;
            btnText.textContent = '确认修改';
            loader.classList.add('hidden');
        }
    });

    function showError(message) {
        const errorDiv = document.getElementById('errorMessage');
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
        document.getElementById('successMessage').classList.add('hidden');
    }
    </script>
</body>
</html>
