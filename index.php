<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="甘肃汇森信息科技有限公司 - 专注通信业务与信息技术服务，为企业数字化转型赋能">
    <meta name="theme-color" content="#0a0a0f">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>甘肃汇森信息科技有限公司 | 官方网站</title>
    
    <!-- Favicon - 防止 404 错误 -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>汇</text></svg>">
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- GSAP Animation Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    
    <!-- ECharts -->
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
    
    <!-- Coze Web SDK - 延迟加载 -->
    <script src="https://lf-cdn.coze.cn/obj/unpkg/flow-platform/chat-app-sdk/1.2.0-beta.19/libs/cn/index.js" defer></script>
    
    <!-- 全局错误处理 - 减少控制台噪音 -->
    <script>
        // 静默处理 Coze SDK 内部错误和第三方库警告
        const originalConsoleError = console.error;
        const originalConsoleWarn = console.warn;
        const suppressPatterns = [
            'ErrorBoundary',
            'logger instance',
            'Agora',
            'WEB_SECURITY_RESTRICT',
            'not found waiting'
        ];
        console.error = function(...args) {
            const msg = args.join(' ');
            if (!suppressPatterns.some(p => msg.includes(p))) {
                originalConsoleError.apply(console, args);
            }
        };
        console.warn = function(...args) {
            const msg = args.join(' ');
            if (!suppressPatterns.some(p => msg.includes(p))) {
                originalConsoleWarn.apply(console, args);
            }
        };
    </script>
    
    <!-- Google Fonts - Noto Sans SC + Space Grotesk Alternative -->
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
        /* ============================================
           自定义样式
           ============================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Noto Sans SC', sans-serif;
            background: #0a0a0f;
            color: #ffffff;
            overflow-x: hidden;
        }
        
        /* 滚动条样式 */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #0a0a0f;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #00f0ff, #7b2dff);
            border-radius: 4px;
        }
        
        /* 光标跟随效果 */
        .cursor-glow {
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0, 240, 255, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            transform: translate(-50%, -50%);
            transition: opacity 0.3s;
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
        
        /* 标题发光效果 */
        .glow-text {
            text-shadow: 
                0 0 10px rgba(0, 240, 255, 0.8),
                0 0 20px rgba(0, 240, 255, 0.6),
                0 0 40px rgba(0, 240, 255, 0.4),
                0 0 80px rgba(0, 240, 255, 0.2);
        }
        
        .glow-text-purple {
            text-shadow: 
                0 0 10px rgba(123, 45, 255, 0.8),
                0 0 20px rgba(123, 45, 255, 0.6),
                0 0 40px rgba(123, 45, 255, 0.4);
        }
        
        /* 卡片玻璃态效果 */
        .glass-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .glass-card:hover {
            border-color: rgba(0, 240, 255, 0.3);
            box-shadow: 
                0 8px 32px rgba(0, 240, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        /* 指标卡片 */
        .stat-card {
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 240, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .stat-card:hover::before {
            left: 100%;
        }
        
        /* 导航栏 */
        .nav-link {
            position: relative;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #00f0ff, #7b2dff);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .nav-link:hover {
            color: #00f0ff;
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
        
        /* 数据表格样式 */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .data-table th {
            background: linear-gradient(180deg, rgba(0, 240, 255, 0.2) 0%, rgba(0, 240, 255, 0.1) 100%);
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            color: #00f0ff;
            border-bottom: 2px solid rgba(0, 240, 255, 0.3);
        }
        
        .data-table td {
            padding: 0.875rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        
        .data-table tr:hover td {
            background: rgba(0, 240, 255, 0.05);
        }
        
        /* 热力图颜色 */
        .heat-high { background: rgba(255, 68, 68, 0.6) !important; color: #fff; }
        .heat-medium { background: rgba(255, 170, 0, 0.6) !important; color: #fff; }
        .heat-low { background: rgba(0, 255, 136, 0.3) !important; }
        .heat-zero { background: rgba(255, 255, 255, 0.05) !important; color: rgba(255, 255, 255, 0.5); }
        
        /* ============================================
           AI 企业大脑卡片样式
           ============================================ */
        .ai-brain-card {
            position: relative;
            background: linear-gradient(135deg, 
                rgba(20, 20, 40, 0.9) 0%, 
                rgba(30, 20, 60, 0.8) 50%,
                rgba(15, 25, 50, 0.9) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(123, 45, 255, 0.3);
            border-radius: 24px;
            overflow: hidden;
        }
        
        .ai-brain-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(
                from 0deg,
                transparent 0deg 60deg,
                rgba(0, 240, 255, 0.1) 60deg 120deg,
                transparent 120deg 180deg,
                rgba(123, 45, 255, 0.1) 180deg 240deg,
                transparent 240deg 300deg,
                rgba(255, 45, 123, 0.08) 300deg 360deg
            );
            animation: rotateGlow 10s linear infinite;
            z-index: 0;
        }
        
        @keyframes rotateGlow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .ai-brain-card-inner {
            position: relative;
            z-index: 1;
            padding: 3rem;
        }
        
        /* AI 大脑图标动画 */
        .ai-brain-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            position: relative;
        }
        
        .ai-brain-icon::before {
            content: '';
            position: absolute;
            inset: -10px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(123, 45, 255, 0.4) 0%, transparent 70%);
            animation: breathe 3s ease-in-out infinite;
        }
        
        .ai-brain-icon::after {
            content: '';
            position: absolute;
            inset: -20px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: rgba(0, 240, 255, 0.5);
            border-right-color: rgba(123, 45, 255, 0.3);
            animation: orbitRing 4s linear infinite;
        }
        
        @keyframes breathe {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        @keyframes orbitRing {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .ai-brain-icon-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #7b2dff 0%, #00f0ff 50%, #ff2d7b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 
                0 0 30px rgba(123, 45, 255, 0.5),
                0 0 60px rgba(0, 240, 255, 0.3),
                inset 0 0 30px rgba(255, 255, 255, 0.1);
            animation: iconPulse 2s ease-in-out infinite;
        }
        
        @keyframes iconPulse {
            0%, 100% { box-shadow: 0 0 30px rgba(123, 45, 255, 0.5), 0 0 60px rgba(0, 240, 255, 0.3); }
            50% { box-shadow: 0 0 50px rgba(123, 45, 255, 0.7), 0 0 80px rgba(0, 240, 255, 0.5); }
        }
        
        /* 启动对话按钮 - 霓虹呼吸灯效果 */
        .launch-ai-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 1.25rem 3rem;
            font-size: 1.125rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, rgba(123, 45, 255, 0.8) 0%, rgba(0, 240, 255, 0.6) 100%);
            border: none;
            border-radius: 60px;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .launch-ai-btn::before {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 60px;
            background: linear-gradient(135deg, #00f0ff, #7b2dff, #ff2d7b, #00f0ff);
            background-size: 300% 300%;
            animation: neonFlow 3s linear infinite;
            z-index: -1;
            filter: blur(8px);
            opacity: 0.7;
        }
        
        .launch-ai-btn::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 60px;
            background: linear-gradient(135deg, rgba(123, 45, 255, 0.9) 0%, rgba(0, 240, 255, 0.7) 100%);
            z-index: -1;
        }
        
        @keyframes neonFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .launch-ai-btn:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 
                0 20px 40px rgba(123, 45, 255, 0.4),
                0 0 60px rgba(0, 240, 255, 0.3);
        }
        
        .launch-ai-btn:active {
            transform: translateY(-2px) scale(0.98);
        }
        
        .launch-ai-btn .btn-icon {
            width: 24px;
            height: 24px;
            animation: iconBounce 2s ease-in-out infinite;
        }
        
        @keyframes iconBounce {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(4px); }
        }
        
        /* 状态指示器 */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid rgba(0, 255, 136, 0.3);
            border-radius: 30px;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #00ff88;
            animation: statusPulse 1.5s ease-in-out infinite;
        }
        
        @keyframes statusPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        
        /* 功能标签 */
        .feature-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }
        
        .feature-tag {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
        }
        
        .feature-tag:hover {
            background: rgba(0, 240, 255, 0.1);
            border-color: rgba(0, 240, 255, 0.3);
            color: #00f0ff;
        }
        
        /* Coze 悬浮图标位置调整 */
        .coze-web-sdk-container,
        [class*="coze-web-chat"] {
            z-index: 9999 !important;
        }
        
        /* 隐藏我们自定义的悬浮按钮，使用 Coze 自带的 */
        .ai-container {
            display: none;
        }
        
        /* 加载动画 */
        .loader {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(0, 240, 255, 0.1);
            border-top: 3px solid #00f0ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* 数字滚动动画 */
        .counter {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
        }
        
        /* Section 分隔线 */
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 240, 255, 0.5), rgba(123, 45, 255, 0.5), transparent);
        }
        
        /* 图表容器 */
        .chart-container {
            min-height: 350px;
            width: 100%;
        }
        
        /* ============================================
           移动端响应式样式
           ============================================ */
        
        /* 平板设备 (768px - 1024px) */
        @media (max-width: 1024px) {
            .chart-container {
                min-height: 300px;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.6rem 0.5rem;
                font-size: 0.85rem;
            }
            
            .ai-brain-card-inner {
                padding: 2rem;
            }
            
            .ai-brain-icon {
                width: 100px;
                height: 100px;
            }
        }
        
        /* 手机设备 (< 768px) */
        @media (max-width: 768px) {
            .ai-panel {
                width: 100vw;
                height: 80vh;
                right: -20px;
                bottom: 70px;
                border-radius: 20px 20px 0 0;
            }
            
            /* 隐藏光标跟随效果 - 移动端无需 */
            .cursor-glow {
                display: none !important;
            }
            
            /* 减少粒子数量提高性能 */
            .particles .particle:nth-child(n+15) {
                display: none;
            }
            
            /* Dashboard 卡片 */
            .stat-card {
                padding: 1rem !important;
            }
            
            .stat-card .counter {
                font-size: 1.75rem !important;
            }
            
            /* 图表容器 */
            .chart-container {
                min-height: 250px;
            }
            
            /* 数据表格 - 水平滚动 */
            .data-table {
                font-size: 0.75rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.5rem 0.4rem;
                min-width: 55px;
            }
            
            .data-table th:first-child,
            .data-table td:first-child {
                position: sticky;
                left: 0;
                background: rgba(10, 10, 15, 0.98);
                z-index: 10;
                min-width: 70px;
            }
            
            /* AI 大脑卡片 */
            .ai-brain-card-inner {
                padding: 1.5rem;
            }
            
            .ai-brain-icon {
                width: 80px;
                height: 80px;
                margin-bottom: 1.5rem;
            }
            
            .ai-brain-icon-inner svg {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }
            
            /* 功能标签 */
            .feature-tags {
                gap: 8px;
            }
            
            .feature-tag {
                padding: 6px 12px;
                font-size: 0.75rem;
            }
            
            /* 启动按钮 */
            .launch-ai-btn {
                padding: 1rem 2rem;
                font-size: 1rem;
                gap: 8px;
            }
            
            /* Glass 卡片 */
            .glass-card {
                padding: 1rem !important;
                border-radius: 16px;
            }
            
            /* Section 间距 */
            section {
                padding-top: 3rem !important;
                padding-bottom: 3rem !important;
            }
            
            /* Coze 悬浮按钮位置 */
            [class*="coze-entry"],
            [class*="coze-web-chat-entry"] {
                bottom: 70px !important;
                right: 15px !important;
                transform: scale(0.9);
            }
            
            /* 聊天窗口优化 */
            [class*="coze-web-chat-panel"],
            [class*="coze-chat-window"] {
                width: 100vw !important;
                max-width: 100vw !important;
                height: 75vh !important;
                bottom: 0 !important;
                right: 0 !important;
                left: 0 !important;
                border-radius: 16px 16px 0 0 !important;
            }
        }
        
        /* 小屏手机 (< 480px) */
        @media (max-width: 480px) {
            /* 隐藏装饰元素提高性能 */
            .particles {
                display: none;
            }
            
            #hero .absolute.border {
                display: none;
            }
            
            /* 导航栏简化 */
            nav .btn-glow {
                padding: 0.5rem 1rem;
                font-size: 0.75rem;
            }
            
            /* 统计卡片 */
            .stat-card .counter {
                font-size: 1.5rem !important;
            }
            
            /* AI 卡片简化 */
            .ai-brain-icon::before,
            .ai-brain-icon::after {
                display: none;
            }
            
            .ai-brain-card::before {
                display: none;
            }
            
            .launch-ai-btn {
                padding: 0.875rem 1.5rem;
                font-size: 0.9rem;
            }
            
            .launch-ai-btn::before {
                display: none;
            }
            
            /* 关于我们装饰隐藏 */
            #about .aspect-square .absolute.border,
            #about .aspect-square .animate-spin {
                display: none;
            }
        }
        
        /* 横屏手机优化 */
        @media (max-height: 500px) and (orientation: landscape) {
            #hero {
                min-height: auto;
                padding: 4rem 1rem;
            }
            
            .ai-brain-card-inner {
                padding: 1rem;
            }
            
            .ai-brain-icon {
                width: 60px;
                height: 60px;
                margin-bottom: 1rem;
            }
        }
        
        /* 触摸设备优化 */
        @media (hover: none) and (pointer: coarse) {
            /* 移除悬停效果 */
            .nav-link::after {
                display: none;
            }
            
            .glass-card:hover {
                border-color: rgba(255, 255, 255, 0.1);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            }
            
            .stat-card::before {
                display: none;
            }
            
            .feature-tag:hover {
                background: rgba(255, 255, 255, 0.05);
                border-color: rgba(255, 255, 255, 0.1);
                color: rgba(255, 255, 255, 0.7);
            }
            
            /* 增大点击区域 */
            .nav-link,
            .feature-tag,
            button {
                min-height: 44px;
            }
            
            /* 禁用3D效果提高性能 */
            .ai-brain-card {
                transform: none !important;
            }
        }
        
        /* 移动端导航菜单 */
        .mobile-menu-btn {
            display: none;
            flex-direction: column;
            justify-content: space-around;
            width: 28px;
            height: 24px;
            padding: 0;
            background: transparent;
            border: none;
            cursor: pointer;
            z-index: 60;
        }
        
        .mobile-menu-btn span {
            display: block;
            width: 100%;
            height: 2px;
            background: white;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-btn.active span:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }
        
        .mobile-menu-btn.active span:nth-child(2) {
            opacity: 0;
        }
        
        .mobile-menu-btn.active span:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
        }
        
        .mobile-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(5, 5, 8, 0.98);
            backdrop-filter: blur(20px);
            z-index: 55;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .mobile-nav.active {
            opacity: 1;
            visibility: visible;
        }
        
        .mobile-nav a {
            font-size: 1.5rem;
            color: white;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .mobile-nav.active a {
            opacity: 1;
            transform: translateY(0);
        }
        
        .mobile-nav.active a:nth-child(1) { transition-delay: 0.1s; }
        .mobile-nav.active a:nth-child(2) { transition-delay: 0.15s; }
        .mobile-nav.active a:nth-child(3) { transition-delay: 0.2s; }
        .mobile-nav.active a:nth-child(4) { transition-delay: 0.25s; }
        
        .mobile-nav a:hover, .mobile-nav a:active {
            background: rgba(0, 240, 255, 0.1);
            color: #00f0ff;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: flex;
            }
            
            nav .btn-glow {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- 光标跟随效果 -->
    <div class="cursor-glow" id="cursorGlow"></div>
    
    <!-- ============================================
         导航栏
         ============================================ -->
    <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-huisen-accent to-huisen-accent2 flex items-center justify-center">
                        <span class="font-display font-bold text-lg">汇</span>
                    </div>
                    <span class="font-body font-bold text-lg hidden sm:block">甘肃汇森</span>
                </div>
                
                <!-- 导航链接 -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#hero" class="nav-link text-white/80 hover:text-white">首页</a>
                    <a href="core/index_v4.php" class="nav-link text-white/80 hover:text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        今日报价
                    </a>
                    <a href="javascript:void(0)" onclick="openCozeChat()" class="nav-link text-white/80 hover:text-white flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-huisen-success opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-huisen-success"></span>
                        </span>
                        AI 助手
                    </a>
                    <a href="#about" class="nav-link text-white/80 hover:text-white">关于我们</a>
                </div>
                
                <!-- CTA 按钮组 (桌面端) -->
                <div class="hidden md:flex items-center gap-4">
                    <a href="login.php" class="btn-glow text-sm px-6 py-2">
                        登录
                    </a>
                </div>
                
                <!-- 移动端菜单按钮 -->
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="菜单">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- 移动端导航菜单 -->
    <div class="mobile-nav" id="mobileNav">
        <a href="#hero" onclick="closeMobileMenu()">首页</a>
        <a href="core/index_v4.php" onclick="closeMobileMenu()">今日报价</a>
        <a href="javascript:void(0)" onclick="closeMobileMenu(); setTimeout(openCozeChat, 300);">AI 助手</a>
        <a href="#about" onclick="closeMobileMenu()">关于我们</a>
        <a href="login.php" onclick="closeMobileMenu()">登录</a>
    </div>
    
    <!-- ============================================
         Hero Section - 首页展示
         ============================================ -->
    <section id="hero" class="relative min-h-screen hero-bg overflow-hidden">
        <!-- 网格背景 -->
        <div class="grid-bg absolute inset-0"></div>
        
        <!-- 粒子效果 -->
        <div class="particles" id="particles"></div>
        
        <!-- Hero 内容 -->
        <div class="relative z-10 min-h-screen flex flex-col items-center justify-center px-6 text-center">
            <!-- 公司标语 -->
            <div class="overflow-hidden mb-4">
                <p class="text-huisen-accent font-display tracking-[0.3em] text-sm md:text-base opacity-0" id="tagline">
                    GANSU HUISEN TECHNOLOGY
                </p>
            </div>
            
            <!-- 主标题 -->
            <div class="overflow-hidden mb-6">
                <h1 class="text-4xl md:text-6xl lg:text-8xl font-body font-black opacity-0" id="mainTitle">
                    <span class="glow-text">甘肃汇森</span>
                </h1>
            </div>
            
            <div class="overflow-hidden mb-8">
                <h2 class="text-2xl md:text-4xl lg:text-5xl font-body font-light text-white/80 opacity-0" id="subTitle">
                    信息科技有限公司
                </h2>
            </div>
            
            <!-- 描述文字 -->
            <div class="max-w-2xl mb-12">
                <p class="text-white/60 text-lg md:text-xl leading-relaxed opacity-0" id="description">
                    专注于通信业务、数字化转型，为企业提供<br>
                    <span class="text-huisen-accent">全方位</span>的信息技术解决方案
                </p>
            </div>
            
            <!-- CTA 按钮组 -->
            <div class="flex flex-col sm:flex-row gap-4 opacity-0" id="ctaButtons">
                <a href="core/index_v4.php" class="btn-glow text-center flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    今日手机报价
                </a>
                <a href="#about" class="px-8 py-4 rounded-full border border-white/20 hover:border-huisen-accent hover:bg-huisen-accent/10 transition-all duration-300 text-center">
                    了解更多
                </a>
                <a href="login.php" class="px-8 py-4 rounded-full border border-white/20 hover:border-huisen-accent hover:bg-huisen-accent/10 transition-all duration-300 text-center">
                    登录
                </a>
            </div>
            
            <!-- 滚动提示 -->
            <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 opacity-0" id="scrollHint">
                <div class="flex flex-col items-center text-white/40">
                    <span class="text-xs mb-2 tracking-wider">SCROLL</span>
                    <div class="w-6 h-10 border-2 border-white/20 rounded-full flex justify-center">
                        <div class="w-1 h-3 bg-huisen-accent rounded-full mt-2 animate-bounce"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 装饰性元素 -->
        <div class="absolute top-1/4 left-10 w-32 h-32 border border-huisen-accent/20 rounded-full animate-pulse"></div>
        <div class="absolute bottom-1/4 right-10 w-48 h-48 border border-huisen-accent2/20 rounded-full animate-pulse" style="animation-delay: 0.5s;"></div>
    </section>
    
    <!-- 分隔线 -->
    <div class="section-divider"></div>
    
    <!-- ============================================
         AI 助手 Section
         ============================================ -->
    <section id="ai-section" class="relative py-20 bg-huisen-dark">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <p class="text-huisen-accent2 font-display tracking-wider text-sm mb-4 opacity-0 ai-animate">AI ASSISTANT</p>
                <h2 class="text-3xl md:text-5xl font-body font-bold mb-4 opacity-0 ai-animate">
                    智能<span class="glow-text">AI 助手</span>
                </h2>
                <p class="text-white/60 max-w-2xl mx-auto opacity-0 ai-animate">
                    集成 Coze 智能体，为您提供智能问答和咨询服务
                </p>
            </div>
            
            <!-- AI 功能卡片 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="glass-card rounded-2xl p-8 text-center opacity-0 ai-animate">
                    <div class="w-16 h-16 mx-auto mb-6 rounded-full bg-gradient-to-br from-huisen-accent to-huisen-accent2 flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">智能问答</h3>
                    <p class="text-white/60">随时提问，AI 即时解答您的业务问题</p>
                </div>
                
                <div class="glass-card rounded-2xl p-8 text-center opacity-0 ai-animate">
                    <div class="w-16 h-16 mx-auto mb-6 rounded-full bg-gradient-to-br from-huisen-accent2 to-huisen-accent3 flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">业务咨询</h3>
                    <p class="text-white/60">AI 驱动的业务咨询，解答您的疑问</p>
                </div>
                
                <div class="glass-card rounded-2xl p-8 text-center opacity-0 ai-animate">
                    <div class="w-16 h-16 mx-auto mb-6 rounded-full bg-gradient-to-br from-huisen-accent3 to-huisen-gold flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">快速响应</h3>
                    <p class="text-white/60">7x24小时在线服务，快速响应您的需求</p>
                </div>
            </div>
            
            <!-- AI 企业大脑卡片 -->
            <div class="ai-brain-card opacity-0 ai-animate" id="aiBrainCard">
                <div class="ai-brain-card-inner text-center">
                    <!-- 状态指示器 -->
                    <div class="status-indicator mx-auto mb-8 inline-flex">
                        <span class="status-dot"></span>
                        <span class="text-huisen-success text-sm font-medium">AI 智能体在线</span>
                    </div>
                    
                    <!-- AI 大脑图标 -->
                    <div class="ai-brain-icon" id="aiBrainIcon">
                        <div class="ai-brain-icon-inner">
                            <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- 主标题 -->
                    <h3 class="text-3xl md:text-4xl font-bold mb-3">
                        <span class="bg-gradient-to-r from-huisen-accent via-huisen-accent2 to-huisen-accent3 bg-clip-text text-transparent">
                            汇森企业大脑
                        </span>
                    </h3>
                    <p class="text-huisen-accent/80 font-display text-sm tracking-wider mb-6">
                        HUISEN ENTERPRISE BRAIN
                    </p>
                    
                    <!-- 描述 -->
                    <p class="text-white/60 text-lg max-w-lg mx-auto mb-8 leading-relaxed">
                        基于 <span class="text-huisen-accent">Coze</span> 智能驱动<br>
                        为您提供智能问答、业务咨询与客户服务支持
                    </p>
                    
                    <!-- 功能标签 -->
                    <div class="feature-tags mb-10">
                        <span class="feature-tag">💬 智能对话</span>
                        <span class="feature-tag">📞 业务咨询</span>
                        <span class="feature-tag">🎯 精准解答</span>
                        <span class="feature-tag">⚡ 实时响应</span>
                    </div>
                    
                    <!-- 启动对话按钮 -->
                    <button class="launch-ai-btn" id="launchAiBtn" onclick="openCozeChat()">
                        <span>启动 AI 对话</span>
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M13 7l5 5m0 0l-5 5m5-5H6">
                            </path>
                        </svg>
                    </button>
                    
                    <!-- 提示文字 -->
                    <p class="text-white/40 text-sm mt-6">
                        点击按钮或右下角图标开始对话
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 分隔线 -->
    <div class="section-divider"></div>
    
    <!-- ============================================
         关于我们 Section
         ============================================ -->
    <section id="about" class="relative py-20 bg-huisen-darker">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- 左侧内容 -->
                <div class="opacity-0 about-animate">
                    <p class="text-huisen-accent font-display tracking-wider text-sm mb-4">ABOUT US</p>
                    <h2 class="text-3xl md:text-5xl font-body font-bold mb-6">
                        关于<span class="glow-text">汇森</span>
                    </h2>
                    <p class="text-white/60 text-lg leading-relaxed mb-6">
                        甘肃汇森信息科技有限公司是一家专注于通信业务及信息技术服务的创新型企业。
                        我们致力于为客户提供高效、可靠的数字化解决方案。
                    </p>
                    <p class="text-white/60 text-lg leading-relaxed mb-8">
                        凭借专业的团队和丰富的行业经验，我们已服务众多渠道合作伙伴，
                        共同推动区域通信业务的发展。
                    </p>
                    
                    <!-- 统计数字 -->
                    <div class="grid grid-cols-3 gap-6">
                        <div>
                            <div class="text-3xl font-display font-bold text-huisen-accent mb-2">9+</div>
                            <div class="text-white/60 text-sm">合作渠道</div>
                        </div>
                        <div>
                            <div class="text-3xl font-display font-bold text-huisen-accent2 mb-2">346</div>
                            <div class="text-white/60 text-sm">月均新增</div>
                        </div>
                        <div>
                            <div class="text-3xl font-display font-bold text-huisen-gold mb-2">100%</div>
                            <div class="text-white/60 text-sm">客户满意</div>
                        </div>
                    </div>
                </div>
                
                <!-- 右侧装饰 -->
                <div class="relative opacity-0 about-animate">
                    <div class="aspect-square max-w-md mx-auto relative">
                        <!-- 装饰圆环 -->
                        <div class="absolute inset-0 border-2 border-huisen-accent/20 rounded-full animate-spin" style="animation-duration: 20s;"></div>
                        <div class="absolute inset-4 border-2 border-huisen-accent2/20 rounded-full animate-spin" style="animation-duration: 15s; animation-direction: reverse;"></div>
                        <div class="absolute inset-8 border-2 border-huisen-accent3/20 rounded-full animate-spin" style="animation-duration: 10s;"></div>
                        
                        <!-- 中心 Logo -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-32 h-32 rounded-2xl bg-gradient-to-br from-huisen-accent via-huisen-accent2 to-huisen-accent3 flex items-center justify-center shadow-2xl">
                                <span class="font-body font-black text-5xl text-white">汇森</span>
                            </div>
                        </div>
                        
                        <!-- 浮动标签 -->
                        <div class="absolute top-10 right-0 glass-card rounded-lg px-4 py-2 text-sm">
                            <span class="text-huisen-accent">●</span> 通信服务
                        </div>
                        <div class="absolute bottom-10 left-0 glass-card rounded-lg px-4 py-2 text-sm">
                            <span class="text-huisen-accent2">●</span> 数字化转型
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- ============================================
         Footer
         ============================================ -->
    <footer class="bg-huisen-dark border-t border-white/5 py-12">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <!-- Logo 和简介 -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-huisen-accent to-huisen-accent2 flex items-center justify-center">
                            <span class="font-display font-bold text-lg">汇</span>
                        </div>
                        <span class="font-body font-bold text-lg">甘肃汇森信息科技有限公司</span>
                    </div>
                    <p class="text-white/40 text-sm leading-relaxed">
                        专注通信业务与信息技术服务<br>
                        为企业数字化转型赋能
                    </p>
                </div>
                
                <!-- 快速链接 -->
                <div>
                    <h4 class="font-bold mb-4">快速链接</h4>
                    <ul class="space-y-2 text-white/40 text-sm">
                        <li><a href="#hero" class="hover:text-huisen-accent transition-colors">首页</a></li>
                        <li><a href="core/index_v4.php" class="hover:text-huisen-accent transition-colors">今日报价</a></li>
                        <li><a href="#ai-section" class="hover:text-huisen-accent transition-colors">AI 助手</a></li>
                        <li><a href="#about" class="hover:text-huisen-accent transition-colors">关于我们</a></li>
                        <li><a href="login.php" class="hover:text-huisen-accent transition-colors">登录</a></li>
                    </ul>
                </div>
                
                <!-- 联系方式 -->
                <div>
                    <h4 class="font-bold mb-4">联系我们</h4>
                    <ul class="space-y-2 text-white/40 text-sm">
                        <li>📍 甘肃省兰州市</li>
                        <li>📞 400-XXX-XXXX</li>
                        <li>✉️ contact@huisen.com</li>
                    </ul>
                </div>
            </div>
            
            <!-- 版权信息 -->
            <div class="border-t border-white/5 pt-8 text-center">
                <p class="text-white/30 text-sm">
                    © 2024 甘肃汇森信息科技有限公司 版权所有 | 
                    <a href="#" class="hover:text-huisen-accent transition-colors">陇ICP备XXXXXXXX号</a>
                </p>
            </div>
        </div>
    </footer>
    
    <!-- Coze SDK 会自动生成悬浮窗按钮 -->
    
    <!-- ============================================
         JavaScript
         ============================================ -->
    <script>
        // ============================================
        // 全局变量
        // ============================================
        let businessData = [];
        
        // ============================================
        // 页面加载完成后执行
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化所有功能
            initCursorGlow();
            initParticles();
            initHeroAnimations();
            initScrollAnimations();
            initNavbarScroll();
            initCozeSDK();
        });
        
        // ============================================
        // Coze SDK 初始化 - 全局实例
        // ============================================
        let cozeClient = null;
        let cozeButtonElement = null;
        
        function initCozeSDK() {
            // 检查 SDK 是否已加载
            if (typeof CozeWebSDK === 'undefined') {
                // 等待 SDK 加载
                let retryCount = 0;
                const checkSDK = setInterval(() => {
                    retryCount++;
                    if (typeof CozeWebSDK !== 'undefined') {
                        clearInterval(checkSDK);
                        createCozeClient();
                    } else if (retryCount >= 15) {
                        clearInterval(checkSDK);
                    }
                }, 500);
            } else {
                createCozeClient();
            }
        }
        
        function createCozeClient() {
            try {
                // 创建 Coze 智能体实例（悬浮窗模式）
                cozeClient = new CozeWebSDK.WebChatClient({
                    config: {
                        bot_id: '7595849107479543808',
                    },
                    componentProps: {
                        title: '汇森小助手',
                        layout: window.innerWidth < 768 ? 'mobile' : 'pc',
                    },
                    auth: {
                        type: 'token',
                        token: 'pat_zAulkIkG2WPLUKeqQWrfahODPLT0ktrTZxm8VWKv1aJVn6jxTC1I7cNIV6KCRomG',
                        onRefreshToken: function () {
                            return 'pat_zAulkIkG2WPLUKeqQWrfahODPLT0ktrTZxm8VWKv1aJVn6jxTC1I7cNIV6KCRomG'
                        }
                    }
                });
                
                // 等待 DOM 生成后查找按钮
                setTimeout(() => {
                    findCozeButton();
                    adjustCozeButtonPosition();
                }, 2000);
                
            } catch (error) {
                // 静默处理错误
            }
        }
        
        // 查找并保存 Coze 按钮引用
        function findCozeButton() {
            const selectors = [
                '[class*="coze"] button',
                'button[class*="coze"]',
                '.coze-web-sdk-container button',
                '[class*="coze-entry"]',
                '[class*="coze-web-chat-entry"]'
            ];
            
            for (const selector of selectors) {
                const btn = document.querySelector(selector);
                if (btn) {
                    cozeButtonElement = btn;
                    return btn;
                }
            }
            
            // 查找所有可能的元素
            const allElements = document.querySelectorAll('*');
            for (const el of allElements) {
                const className = el.className?.toString() || '';
                if (className.includes('coze') && 
                    (el.tagName === 'BUTTON' || el.getAttribute('role') === 'button')) {
                    cozeButtonElement = el;
                    return el;
                }
            }
            return null;
        }
        
        // 调整 Coze 悬浮按钮位置
        function adjustCozeButtonPosition() {
            const style = document.createElement('style');
            style.id = 'coze-position-fix';
            if (document.getElementById('coze-position-fix')) return;
            
            style.textContent = `
                .coze-web-sdk-container { z-index: 9999 !important; }
                [class*="coze-web-chat-entry"], [class*="coze-entry-btn"] {
                    bottom: 80px !important;
                    right: 25px !important;
                    z-index: 9999 !important;
                }
                [class*="coze-web-chat-panel"], [class*="coze-chat-window"] {
                    z-index: 9998 !important;
                }
                @media (max-width: 768px) {
                    [class*="coze-web-chat-entry"], [class*="coze-entry-btn"] {
                        bottom: 70px !important;
                        right: 15px !important;
                        transform: scale(0.9);
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        // ============================================
        // 打开 Coze 聊天窗口
        // ============================================
        function openCozeChat() {
            // 添加按钮点击动效
            animateLaunchButton();
            
            // 方法1: 如果已保存按钮引用，直接点击
            if (cozeButtonElement && document.body.contains(cozeButtonElement)) {
                try {
                    cozeButtonElement.click();
                    return;
                } catch (e) {}
            }
            
            // 方法2: 尝试调用 SDK 的方法
            if (cozeClient) {
                const methods = ['open', 'show', 'toggle', 'showChat'];
                for (const method of methods) {
                    if (typeof cozeClient[method] === 'function') {
                        try {
                            cozeClient[method]();
                            return;
                        } catch (e) {}
                    }
                }
            }
            
            // 方法3: 重新查找并点击按钮
            const foundButton = findCozeButton();
            if (foundButton) {
                try {
                    foundButton.click();
                    return;
                } catch (e) {}
            }
            
            // 方法4: 搜索并点击 Coze 元素
            const allElements = document.querySelectorAll('[class*="coze"]');
            for (const el of allElements) {
                if (el.tagName === 'BUTTON' || el.getAttribute('role') === 'button') {
                    try {
                        el.click();
                        return;
                    } catch (e) {}
                }
            }
            
            // 方法5: 重试机制
            if (typeof CozeWebSDK !== 'undefined') {
                let retryCount = 0;
                const retryInterval = setInterval(() => {
                    retryCount++;
                    const btn = findCozeButton();
                    if (btn) {
                        btn.click();
                        clearInterval(retryInterval);
                    } else if (retryCount >= 10) {
                        clearInterval(retryInterval);
                        // 滚动到 AI 区域
                        document.getElementById('ai-section')?.scrollIntoView({ behavior: 'smooth' });
                    }
                }, 300);
            }
        }
        
        // ============================================
        // 启动按钮动效
        // ============================================
        function animateLaunchButton() {
            const btn = document.getElementById('launchAiBtn');
            if (btn && typeof gsap !== 'undefined') {
                gsap.to(btn, {
                    scale: 0.95,
                    duration: 0.1,
                    yoyo: true,
                    repeat: 1,
                    ease: 'power2.inOut'
                });
            }
        }
        
        // ============================================
        // 光标跟随效果
        // ============================================
        function initCursorGlow() {
            const cursorGlow = document.getElementById('cursorGlow');
            
            document.addEventListener('mousemove', (e) => {
                cursorGlow.style.left = e.clientX + 'px';
                cursorGlow.style.top = e.clientY + 'px';
            });
            
            document.addEventListener('mouseleave', () => {
                cursorGlow.style.opacity = '0';
            });
            
            document.addEventListener('mouseenter', () => {
                cursorGlow.style.opacity = '1';
            });
        }
        
        // ============================================
        // 粒子效果
        // ============================================
        function initParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                
                // 随机颜色
                const colors = ['#00f0ff', '#7b2dff', '#ff2d7b', '#ffd700'];
                particle.style.background = colors[Math.floor(Math.random() * colors.length)];
                particle.style.boxShadow = `0 0 10px ${particle.style.background}`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // ============================================
        // Hero 区域动画
        // ============================================
        function initHeroAnimations() {
            // 使用 GSAP 创建入场动画
            const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
            
            tl.to('#tagline', {
                opacity: 1,
                y: 0,
                duration: 1,
                delay: 0.5
            })
            .to('#mainTitle', {
                opacity: 1,
                y: 0,
                duration: 1.2
            }, '-=0.5')
            .to('#subTitle', {
                opacity: 1,
                y: 0,
                duration: 1
            }, '-=0.7')
            .to('#description', {
                opacity: 1,
                y: 0,
                duration: 1
            }, '-=0.5')
            .to('#ctaButtons', {
                opacity: 1,
                y: 0,
                duration: 0.8
            }, '-=0.3')
            .to('#scrollHint', {
                opacity: 1,
                duration: 0.8
            }, '-=0.2');
        }
        
        // ============================================
        // 滚动动画
        // ============================================
        function initScrollAnimations() {
            // 注册 ScrollTrigger 插件
            gsap.registerPlugin(ScrollTrigger);
            
            // AI 区域动画
            gsap.utils.toArray('.ai-animate').forEach((el, i) => {
                gsap.to(el, {
                    scrollTrigger: {
                        trigger: el,
                        start: 'top 85%',
                        toggleActions: 'play none none none'
                    },
                    opacity: 1,
                    y: 0,
                    duration: 0.8,
                    delay: i * 0.1
                });
            });
            
            // 关于我们动画
            gsap.utils.toArray('.about-animate').forEach((el, i) => {
                gsap.to(el, {
                    scrollTrigger: {
                        trigger: el,
                        start: 'top 85%',
                        toggleActions: 'play none none none'
                    },
                    opacity: 1,
                    x: 0,
                    duration: 1,
                    delay: i * 0.2
                });
            });
        }
        
        // ============================================
        // 导航栏滚动效果
        // ============================================
        function initNavbarScroll() {
            const navbar = document.getElementById('navbar');
            
            window.addEventListener('scroll', () => {
                if (window.scrollY > 100) {
                    navbar.classList.add('bg-huisen-dark/90', 'backdrop-blur-lg', 'shadow-lg');
                } else {
                    navbar.classList.remove('bg-huisen-dark/90', 'backdrop-blur-lg', 'shadow-lg');
                }
            });
            
            // 移动端菜单功能
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileNav = document.getElementById('mobileNav');
            
            if (mobileMenuBtn && mobileNav) {
                mobileMenuBtn.addEventListener('click', toggleMobileMenu);
            }
        }
        
        // 移动端菜单切换
        function toggleMobileMenu() {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileNav = document.getElementById('mobileNav');
            
            mobileMenuBtn.classList.toggle('active');
            mobileNav.classList.toggle('active');
            
            // 禁止/允许背景滚动
            document.body.style.overflow = mobileNav.classList.contains('active') ? 'hidden' : '';
        }
        
        // 关闭移动端菜单
        function closeMobileMenu() {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileNav = document.getElementById('mobileNav');
            
            mobileMenuBtn.classList.remove('active');
            mobileNav.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        
        // ============================================
        // AI 大脑卡片特效
        // ============================================
        function initAiBrainEffects() {
            const card = document.getElementById('aiBrainCard');
            const icon = document.getElementById('aiBrainIcon');
            const btn = document.getElementById('launchAiBtn');
            
            if (!card || typeof gsap === 'undefined') return;
            
            // 卡片悬停效果 - 3D 倾斜
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                
                gsap.to(card, {
                    rotateX: rotateX,
                    rotateY: rotateY,
                    duration: 0.5,
                    ease: 'power2.out',
                    transformPerspective: 1000
                });
            });
            
            card.addEventListener('mouseleave', () => {
                gsap.to(card, {
                    rotateX: 0,
                    rotateY: 0,
                    duration: 0.5,
                    ease: 'power2.out'
                });
            });
            
            // 按钮磁性吸附效果
            if (btn) {
                btn.addEventListener('mousemove', (e) => {
                    const rect = btn.getBoundingClientRect();
                    const x = e.clientX - rect.left - rect.width / 2;
                    const y = e.clientY - rect.top - rect.height / 2;
                    
                    gsap.to(btn, {
                        x: x * 0.2,
                        y: y * 0.2,
                        duration: 0.3,
                        ease: 'power2.out'
                    });
                });
                
                btn.addEventListener('mouseleave', () => {
                    gsap.to(btn, {
                        x: 0,
                        y: 0,
                        duration: 0.5,
                        ease: 'elastic.out(1, 0.5)'
                    });
                });
            }
        }
        
        // 在滚动动画初始化后调用
        ScrollTrigger.create({
            trigger: '#aiBrainCard',
            start: 'top 80%',
            onEnter: () => {
                setTimeout(initAiBrainEffects, 500);
            },
            once: true
        });
        
        // ============================================
        // 平滑滚动
        // ============================================
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
