<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="甘肃汇森信息科技有限公司 - 员工登录">
    <meta name="theme-color" content="#0a0a0f">
    <title>员工登录 | 甘肃汇森信息科技有限公司</title>
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>汇</text></svg>">
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- GSAP Animation Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700;900&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- TailwindCSS Custom Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'huisen': {
                            'dark': '#0a0a0f',
                            'darker': '#050508',
                            'accent': '#00f0ff',
                            'accent2': '#7b2dff',
                            'accent3': '#ff2d7b',
                            'gold': '#ffd700',
                            'success': '#00ff88',
                            'warning': '#ffaa00',
                            'danger': '#ff4444'
                        }
                    },
                    fontFamily: {
                        'display': ['Orbitron', 'sans-serif'],
                        'body': ['Noto Sans SC', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans SC', sans-serif;
            background: #0a0a0f;
            color: #ffffff;
            overflow: hidden;
        }
        
        /* Hero 背景动画 */
        .hero-bg {
            background: 
                radial-gradient(ellipse at 20% 80%, rgba(123, 45, 255, 0.3) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(0, 240, 255, 0.2) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(255, 45, 123, 0.1) 0%, transparent 70%),
                linear-gradient(180deg, #050508 0%, #0a0a0f 50%, #0f0f18 100%);
        }
        
        /* 网格背景 */
        .grid-bg {
            background-image: 
                linear-gradient(rgba(0, 240, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 240, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s linear infinite;
        }
        
        @keyframes gridMove {
            0% { background-position: 0 0; }
            100% { background-position: 50px 50px; }
        }
        
        /* 粒子效果 */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #00f0ff;
            border-radius: 50%;
            box-shadow: 0 0 10px #00f0ff, 0 0 20px #00f0ff;
            animation: float 15s infinite;
            opacity: 0.6;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.6; }
            90% { opacity: 0.6; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }
        
        /* 玻璃拟态卡片 */
        .glass-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .glass-card:hover {
            border-color: rgba(0, 240, 255, 0.4);
            box-shadow: 
                0 8px 32px rgba(0, 240, 255, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        /* 输入框样式 */
        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            outline: none;
            border-color: rgba(0, 240, 255, 0.5);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.2);
        }
        
        /* 按钮样式 */
        .btn-glow {
            position: relative;
            background: linear-gradient(135deg, #00f0ff 0%, #7b2dff 100%);
            border: none;
            padding: 1rem 2rem;
            color: #fff;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .btn-glow::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-glow:hover::before {
            left: 100%;
        }
        
        .btn-glow:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(0, 240, 255, 0.4);
        }
        
        .btn-glow:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* 错误提示 */
        .error-message {
            color: #ff4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
        
        .error-message.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* 加载动画 */
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
        
        /* 页面淡入淡出 */
        .fade-out {
            opacity: 0;
            transition: opacity 0.5s ease;
        }
    </style>
</head>
<body>
    <!-- 背景层 -->
    <div class="hero-bg fixed inset-0">
        <!-- 网格背景 -->
        <div class="grid-bg absolute inset-0"></div>
        
        <!-- 粒子效果 -->
        <div class="particles" id="particles"></div>
    </div>
    
    <!-- 登录卡片容器 -->
    <div class="relative z-10 min-h-screen flex items-center justify-center px-6">
        <div class="glass-card rounded-3xl p-8 md:p-12 w-full max-w-md opacity-0" id="loginCard">
            <!-- Logo 和标题 -->
            <div class="text-center mb-8">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-huisen-accent to-huisen-accent2 flex items-center justify-center shadow-lg">
                        <span class="font-display font-bold text-2xl">汇</span>
                    </div>
                </div>
                <h1 class="text-2xl md:text-3xl font-body font-bold mb-2">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-huisen-accent to-huisen-accent2">
                        员工登录
                    </span>
                </h1>
                <p class="text-white/60 text-sm">甘肃汇森信息科技有限公司</p>
            </div>
            
            <!-- 登录表单 -->
            <form id="loginForm" class="space-y-6">
                <!-- 用户名 -->
                <div>
                    <label class="block text-sm font-medium mb-2 text-white/80">用户名</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="input-field w-full px-4 py-3 rounded-lg"
                        placeholder="请输入用户名"
                        required
                        autocomplete="username"
                    >
                </div>
                
                <!-- 密码 -->
                <div>
                    <label class="block text-sm font-medium mb-2 text-white/80">密码</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="input-field w-full px-4 py-3 rounded-lg"
                        placeholder="请输入密码"
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <!-- 错误提示 -->
                <div class="error-message" id="errorMessage"></div>
                
                <!-- 登录按钮 -->
                <button 
                    type="submit" 
                    class="btn-glow w-full py-3 text-lg"
                    id="loginBtn"
                >
                    <span id="btnText">登录</span>
                    <span class="loader" id="loader"></span>
                </button>
            </form>
            
            <!-- 注册链接 -->
            <div class="mt-6 text-center">
                <p class="text-white/60 text-sm">
                    还没有账户？
                    <a href="register.php" class="text-huisen-accent hover:underline font-medium">立即注册</a>
                </p>
            </div>

            <!-- 返回首页链接 -->
            <div class="mt-4 text-center">
                <a href="../index.php" class="text-white/60 hover:text-huisen-accent text-sm transition-colors">
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
            initParticles();
            initLoginCardAnimation();
            initLoginForm();
            
            // 如果已登录，直接跳转
            checkLoginStatus();
        });
        
        // ============================================
        // 粒子效果
        // ============================================
        function initParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 20;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                
                const colors = ['#00f0ff', '#7b2dff', '#ff2d7b'];
                const color = colors[Math.floor(Math.random() * colors.length)];
                particle.style.background = color;
                particle.style.boxShadow = `0 0 10px ${color}`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // ============================================
        // 登录卡片动画
        // ============================================
        function initLoginCardAnimation() {
            const card = document.getElementById('loginCard');
            
            if (typeof gsap !== 'undefined') {
                gsap.to(card, {
                    opacity: 1,
                    y: 0,
                    duration: 1,
                    ease: 'power3.out',
                    delay: 0.3
                });
            } else {
                card.style.opacity = '1';
            }
        }
        
        // ============================================
        // 检查登录状态
        // ============================================
        async function checkLoginStatus() {
            try {
                const response = await fetch('../api/auth.php?action=check');
                const result = await response.json();
                
                if (result.success && result.data.logged_in) {
                    // 已登录，跳转到 dashboard
                    window.location.href = '/dashboard.php';
                }
            } catch (error) {
                // 忽略错误，继续显示登录页面
            }
        }
        
        // ============================================
        // 登录表单处理
        // ============================================
        function initLoginForm() {
            const form = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const loader = document.getElementById('loader');
            const errorMessage = document.getElementById('errorMessage');
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // 隐藏错误提示
                errorMessage.classList.remove('show');
                errorMessage.textContent = '';
                
                // 禁用按钮，显示加载状态
                loginBtn.disabled = true;
                btnText.textContent = '登录中...';
                loader.classList.add('show');
                
                // 获取表单数据
                const formData = {
                    username: document.getElementById('username').value.trim(),
                    password: document.getElementById('password').value
                };
                
                try {
                    // 发送登录请求
                    const response = await fetch('../api/auth.php?action=login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // 登录成功，淡出页面并跳转
                        document.body.classList.add('fade-out');
                        
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 500);
                    } else {
                        // 登录失败，显示错误信息
                        showError(result.error || '登录失败，请检查用户名和密码');
                        
                        // 恢复按钮状态
                        loginBtn.disabled = false;
                        btnText.textContent = '登录';
                        loader.classList.remove('show');
                    }
                } catch (error) {
                    showError('网络错误，请稍后重试');
                    
                    // 恢复按钮状态
                    loginBtn.disabled = false;
                    btnText.textContent = '登录';
                    loader.classList.remove('show');
                }
            });
        }
        
        // ============================================
        // 显示错误信息
        // ============================================
        function showError(message) {
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.textContent = message;
            errorMessage.classList.add('show');
            
            // 3秒后自动隐藏
            setTimeout(() => {
                errorMessage.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>