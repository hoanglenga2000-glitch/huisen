<?php
/**
 * ============================================
 * 甘肃汇森信息科技有限公司 - 内部数据大屏
 * ============================================
 * 
 * 权限：需要登录才能访问
 * 功能：展示敏感财务数据、业务报表、图表分析
 * AI：集成内部财务分析 AI 智能体
 */

// 开启 Session
session_start();

// 检查登录状态，未登录则跳转到登录页
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /login.php');
    exit;
}

// 获取用户信息
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '用户';
$real_name = isset($_SESSION['real_name']) ? $_SESSION['real_name'] : $username;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="甘肃汇森信息科技有限公司 - 内部数据大屏">
    <meta name="theme-color" content="#0a0a0f">
    <title>数据大屏 | 甘肃汇森信息科技有限公司</title>
    
    <!-- Favicon -->
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
    
    <!-- 全局错误处理 -->
    <script>
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
            overflow-x: hidden;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
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
        
        .hero-bg {
            background: 
                radial-gradient(ellipse at 20% 80%, rgba(123, 45, 255, 0.3) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(0, 240, 255, 0.2) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(255, 45, 123, 0.1) 0%, transparent 70%),
                linear-gradient(180deg, #050508 0%, #0a0a0f 50%, #0f0f18 100%);
        }
        
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
        
        .btn-glow {
            position: relative;
            background: linear-gradient(135deg, #00f0ff 0%, #7b2dff 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            color: #fff;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            text-decoration: none;
            display: inline-block;
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
        
        /* 品牌 Tab 样式 */
        .brand-tab {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
        }
        
        .brand-tab:hover {
            background: rgba(0, 240, 255, 0.1);
            border-color: rgba(0, 240, 255, 0.3);
            color: #00f0ff;
            transform: translateY(-2px);
        }
        
        .brand-tab.active {
            background: linear-gradient(135deg, rgba(0, 240, 255, 0.2) 0%, rgba(123, 45, 255, 0.2) 100%);
            border-color: rgba(0, 240, 255, 0.5);
            color: #00f0ff;
            box-shadow: 0 4px 12px rgba(0, 240, 255, 0.2);
        }
        
        /* 隐藏滚动条但保持滚动功能 */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        /* 表格行样式优化 */
        .data-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .data-table tbody tr:hover {
            background: rgba(0, 240, 255, 0.08) !important;
            transform: scale(1.01);
        }
        
        /* 操作按钮样式 */
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .action-btn-edit {
            background: rgba(0, 240, 255, 0.1);
            color: #00f0ff;
            border: 1px solid rgba(0, 240, 255, 0.3);
        }
        
        .action-btn-edit:hover {
            background: rgba(0, 240, 255, 0.2);
            border-color: rgba(0, 240, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 240, 255, 0.3);
        }
        
        .action-btn-delete {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border: 1px solid rgba(255, 68, 68, 0.3);
        }
        
        .action-btn-delete:hover {
            background: rgba(255, 68, 68, 0.2);
            border-color: rgba(255, 68, 68, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 68, 68, 0.3);
        }
        
        .heat-high { background: rgba(255, 68, 68, 0.6) !important; color: #fff; }
        .heat-medium { background: rgba(255, 170, 0, 0.6) !important; color: #fff; }
        .heat-low { background: rgba(0, 255, 136, 0.3) !important; }
        .heat-zero { background: rgba(255, 255, 255, 0.05) !important; color: rgba(255, 255, 255, 0.5); }
        
        .chart-container {
            min-height: 350px;
            width: 100%;
        }
        
        .counter {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
        }
        
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 240, 255, 0.5), rgba(123, 45, 255, 0.5), transparent);
        }
        
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
        
        @keyframes breathe {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
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
                0 0 60px rgba(0, 240, 255, 0.3);
        }
        
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
        
        .launch-ai-btn:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 
                0 20px 40px rgba(123, 45, 255, 0.4),
                0 0 60px rgba(0, 240, 255, 0.3);
        }
        
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
        }
        
        /* 修改密码弹窗样式 */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(20, 20, 40, 0.95) 0%, rgba(30, 20, 60, 0.95) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 240, 255, 0.3);
            border-radius: 24px;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.5),
                0 0 40px rgba(0, 240, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            transform: scale(0.9) translateY(20px);
            transition: transform 0.3s ease;
        }
        
        .modal-overlay.show .modal-content {
            transform: scale(1) translateY(0);
        }
        
        .modal-content::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #00f0ff, #7b2dff, #ff2d7b);
            border-radius: 24px;
            z-index: -1;
            opacity: 0.3;
            animation: borderGlow 3s ease-in-out infinite;
        }
        
        @keyframes borderGlow {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.6; }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #00f0ff, #7b2dff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .modal-close {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .modal-close:hover {
            background: rgba(255, 68, 68, 0.2);
            border-color: rgba(255, 68, 68, 0.4);
            color: #ff4444;
            transform: rotate(90deg);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .form-input-wrapper {
            position: relative;
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 3rem 0.875rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #00f0ff;
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #00f0ff;
        }
        
        .form-error {
            margin-top: 0.5rem;
            color: #ff4444;
            font-size: 0.875rem;
            display: none;
        }
        
        .form-error.show {
            display: block;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-submit {
            flex: 1;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, #00f0ff 0%, #7b2dff 100%);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 240, 255, 0.4);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-submit.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .btn-cancel {
            flex: 1;
            padding: 0.875rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .toast {
            position: fixed;
            top: 100px;
            right: 20px;
            background: linear-gradient(135deg, rgba(20, 20, 40, 0.95) 0%, rgba(30, 20, 60, 0.95) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 255, 136, 0.3);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            color: #fff;
            box-shadow: 0 10px 40px rgba(0, 255, 136, 0.2);
            z-index: 10001;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 300px;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(0, 255, 136, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .toast.error {
            border-color: rgba(255, 68, 68, 0.3);
            box-shadow: 0 10px 40px rgba(255, 68, 68, 0.2);
        }
        
        .toast.error .toast-icon {
            background: rgba(255, 68, 68, 0.2);
        }
        
        @media (max-width: 768px) {
            .chart-container {
                min-height: 250px;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.5rem 0.4rem;
                font-size: 0.75rem;
            }
            
            .modal-content {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .modal-title {
                font-size: 1.25rem;
            }
            
            .toast {
                right: 10px;
                left: 10px;
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- ============================================
         导航栏
         ============================================ -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-huisen-dark/90 backdrop-blur-lg shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-huisen-accent to-huisen-accent2 flex items-center justify-center">
                        <span class="font-display font-bold text-lg">汇</span>
                    </div>
                    <span class="font-body font-bold text-lg hidden sm:block">甘肃汇森</span>
                </div>
                
                <!-- 用户信息 -->
                <div class="flex items-center space-x-4">
                    <span class="text-white/80 hidden md:block">欢迎，<span class="text-huisen-accent"><?php echo htmlspecialchars($real_name); ?></span></span>
                    <button onclick="openChangePasswordModal()" class="btn-glow text-sm px-4 py-2 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        修改密码
                    </button>
                    <button onclick="handleLogout()" class="btn-glow text-sm px-4 py-2">
                        退出登录
                    </button>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- ============================================
         Dashboard Section - 业务数据仪表盘
         ============================================ -->
    <section id="dashboard" class="relative pt-24 pb-20 bg-huisen-darker min-h-screen">
        <!-- 网格背景 -->
        <div class="grid-bg absolute inset-0"></div>
        
        <!-- 粒子效果 -->
        <div class="particles" id="particles"></div>
        
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <!-- Section 标题 -->
            <div class="text-center mb-16">
                <p class="text-huisen-accent font-display tracking-wider text-sm mb-4 opacity-0 dashboard-animate">BUSINESS ANALYTICS</p>
                <h2 class="text-3xl md:text-5xl font-body font-bold mb-4 opacity-0 dashboard-animate">
                    业务数据<span class="glow-text-purple">仪表盘</span>
                </h2>
                <p class="text-white/60 max-w-2xl mx-auto opacity-0 dashboard-animate">
                    实时监控各渠道业务数据，数据驱动决策
                </p>
            </div>
            
            <!-- 核心指标卡片 -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 mb-12">
                <!-- 总新增 -->
                <div class="glass-card stat-card rounded-2xl p-6 opacity-0 dashboard-animate">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-white/60 text-sm">总新增</span>
                        <div class="w-10 h-10 rounded-full bg-huisen-accent/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-huisen-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="counter text-3xl md:text-4xl text-huisen-accent mb-2" data-target="346">0</div>
                    <div class="flex items-center text-huisen-success text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        <span>+12.5%</span>
                    </div>
                </div>
                
                <!-- 总宽带 -->
                <div class="glass-card stat-card rounded-2xl p-6 opacity-0 dashboard-animate">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-white/60 text-sm">总宽带</span>
                        <div class="w-10 h-10 rounded-full bg-huisen-accent2/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-huisen-accent2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="counter text-3xl md:text-4xl text-huisen-accent2 mb-2" data-target="144">0</div>
                    <div class="flex items-center text-huisen-success text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        <span>+8.3%</span>
                    </div>
                </div>
                
                <!-- 存量金币 -->
                <div class="glass-card stat-card rounded-2xl p-6 opacity-0 dashboard-animate">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-white/60 text-sm">存量金币</span>
                        <div class="w-10 h-10 rounded-full bg-huisen-gold/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-huisen-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="counter text-3xl md:text-4xl text-huisen-gold mb-2" data-target="363">0</div>
                    <div class="flex items-center text-huisen-warning text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                        <span>持平</span>
                    </div>
                </div>
                
                <!-- 低提 -->
                <div class="glass-card stat-card rounded-2xl p-6 opacity-0 dashboard-animate">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-white/60 text-sm">低提</span>
                        <div class="w-10 h-10 rounded-full bg-huisen-danger/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-huisen-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="counter text-3xl md:text-4xl text-huisen-danger mb-2" data-target="406">0</div>
                    <div class="flex items-center text-huisen-danger text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                        <span>需关注</span>
                    </div>
                </div>
            </div>
            
            <!-- 图表区域 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12">
                <!-- 柱状图 - 各渠道新增与宽带对比 -->
                <div class="glass-card rounded-2xl p-6 opacity-0 dashboard-animate">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <span class="w-3 h-3 rounded-full bg-huisen-accent mr-3"></span>
                        各渠道业务对比
                    </h3>
                    <div id="barChart" class="chart-container"></div>
                </div>
                
                <!-- 饼图 - 存量金币分布 -->
                <div class="glass-card rounded-2xl p-6 opacity-0 dashboard-animate">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <span class="w-3 h-3 rounded-full bg-huisen-accent2 mr-3"></span>
                        存量金币分布
                    </h3>
                    <div id="pieChart" class="chart-container"></div>
                </div>
            </div>
            
            <!-- 雷达图和折线图 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12">
                <!-- 雷达图 -->
                <div class="glass-card rounded-2xl p-6 opacity-0 dashboard-animate">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <span class="w-3 h-3 rounded-full bg-huisen-success mr-3"></span>
                        综合指标分析
                    </h3>
                    <div id="radarChart" class="chart-container"></div>
                </div>
                
                <!-- 折线图 -->
                <div class="glass-card rounded-2xl p-6 opacity-0 dashboard-animate">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <span class="w-3 h-3 rounded-full bg-huisen-gold mr-3"></span>
                        业务趋势
                    </h3>
                    <div id="lineChart" class="chart-container"></div>
                </div>
            </div>
            
            <!-- 数据表格 - 热力图样式 -->
            <div class="glass-card rounded-2xl p-6 opacity-0 dashboard-animate overflow-x-auto">
                <h3 class="text-lg font-bold mb-6 flex items-center">
                    <span class="w-3 h-3 rounded-full bg-huisen-accent3 mr-3"></span>
                    渠道详细数据
                </h3>
                <table class="data-table" id="dataTable">
                    <thead>
                        <tr>
                            <th>渠道</th>
                            <th>新增</th>
                            <th>宽带</th>
                            <th>新增金币</th>
                            <th>存量金币</th>
                            <th>低提</th>
                            <th>千兆</th>
                            <th>亲情网</th>
                            <th>移动爱家</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- 数据将通过 JS 动态加载 -->
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    
    <!-- 分隔线 -->
    <div class="section-divider"></div>
    
    <!-- ============================================
         手机报价管理 Section
         ============================================ -->
    <section id="quotes-section" class="relative py-20 bg-huisen-dark">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <p class="text-huisen-accent font-display tracking-wider text-sm mb-4 opacity-0 dashboard-animate">MOBILE QUOTES</p>
                <h2 class="text-3xl md:text-5xl font-body font-bold mb-4 opacity-0 dashboard-animate">
                    每日<span class="glow-text-purple">手机报价管理</span>
                </h2>
                <p class="text-white/60 max-w-2xl mx-auto opacity-0 dashboard-animate">
                    管理手机批发报价，快速调价，实时更新
                </p>
            </div>
            
            <!-- 品牌分类 Tab 栏 -->
            <div class="glass-card rounded-2xl p-4 mb-6 opacity-0 dashboard-animate">
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide" id="brandTabsContainer">
                    <!-- 品牌标签将通过 JS 动态加载 -->
                    <button 
                        class="brand-tab active flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300"
                        data-brand="全部"
                        onclick="switchBrand('全部')">
                        全部
                    </button>
                </div>
            </div>
            
            <!-- 操作栏 -->
            <div class="glass-card rounded-2xl p-6 mb-6 opacity-0 dashboard-animate">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4 flex-1">
                        <input 
                            type="text" 
                            id="quoteSearch" 
                            placeholder="搜索型号、规格或品牌..." 
                            class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-white/40 focus:outline-none focus:border-huisen-accent flex-1 max-w-md"
                        >
                    </div>
                    <button onclick="openAddQuoteModal()" class="btn-glow text-sm px-6 py-2 flex items-center gap-2 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        新增报价
                    </button>
                </div>
            </div>
            
            <!-- 报价表格 -->
            <div class="glass-card rounded-2xl p-6 opacity-0 dashboard-animate overflow-x-auto">
                <div class="min-h-[400px]">
                    <table class="data-table w-full" id="quotesTable">
                        <thead>
                            <tr>
                                <th>品牌</th>
                                <th>型号</th>
                                <th>规格</th>
                                <th>批发价</th>
                                <th>官网价</th>
                                <th>备注</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="quotesTableBody">
                            <tr>
                                <td colspan="7" class="text-center text-white/60 py-12">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        <span>正在加载数据...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 分隔线 -->
    <div class="section-divider"></div>
    
    <!-- ============================================
         AI 助手 Section - 内部财务 AI
         ============================================ -->
    <section id="ai-section" class="relative py-20 bg-huisen-dark">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <p class="text-huisen-accent2 font-display tracking-wider text-sm mb-4 opacity-0 ai-animate">FINANCE ASSISTANT</p>
                <h2 class="text-3xl md:text-5xl font-body font-bold mb-4 opacity-0 ai-animate">
                    <span class="glow-text">汇森财务大管家</span>
                </h2>
                <p class="text-white/60 max-w-2xl mx-auto opacity-0 ai-animate">
                    专业的财务数据分析智能体，提供财务入库、智能流水分析等全方位财务服务
                </p>
            </div>
            
            <!-- AI 企业大脑卡片 -->
            <div class="ai-brain-card opacity-0 ai-animate" id="aiBrainCard">
                <div class="ai-brain-card-inner text-center">
                    <!-- 状态指示器 -->
                    <div class="status-indicator mx-auto mb-8 inline-flex">
                        <span class="status-dot"></span>
                        <span class="text-huisen-success text-sm font-medium">财务大管家在线</span>
                    </div>
                    
                    <!-- AI 大脑图标 -->
                    <div class="ai-brain-icon" id="aiBrainIcon">
                        <div class="ai-brain-icon-inner">
                            <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- 主标题 -->
                    <h3 class="text-3xl md:text-4xl font-bold mb-3">
                        <span class="bg-gradient-to-r from-huisen-accent via-huisen-accent2 to-huisen-accent3 bg-clip-text text-transparent">
                            汇森财务大管家
                        </span>
                    </h3>
                    <p class="text-huisen-accent/80 font-display text-sm tracking-wider mb-6">
                        FINANCE ASSISTANT
                    </p>
                    
                    <!-- 描述 -->
                    <p class="text-white/60 text-lg max-w-lg mx-auto mb-8 leading-relaxed">
                        基于 <span class="text-huisen-accent">Coze</span> 智能驱动<br>
                        提供财务入库助手、智能流水分析等专业财务服务，帮助您高效管理财务数据
                    </p>
                    
                    <!-- 功能标签 -->
                    <div class="feature-tags mb-10">
                        <span class="feature-tag">📊 财务入库助手</span>
                        <span class="feature-tag">💰 智能流水分析</span>
                        <span class="feature-tag">📈 数据分析</span>
                        <span class="feature-tag">🎯 精准建议</span>
                    </div>
                    
                    <!-- 启动对话按钮 -->
                    <button class="launch-ai-btn" id="launchAiBtn" onclick="openCozeChat()">
                        <span>启动财务大管家</span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
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
    
    <!-- ============================================
         修改密码弹窗
         ============================================ -->
    <div class="modal-overlay" id="changePasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">修改密码</h3>
                <button class="modal-close" onclick="closeChangePasswordModal()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="changePasswordForm">
                <div class="form-group">
                    <label class="form-label">旧密码</label>
                    <div class="form-input-wrapper">
                        <input type="password" id="oldPassword" name="old_password" class="form-input" placeholder="请输入旧密码" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('oldPassword', this)">
                            <svg class="w-5 h-5" id="oldPasswordIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="form-error" id="oldPasswordError"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">新密码</label>
                    <div class="form-input-wrapper">
                        <input type="password" id="newPassword" name="new_password" class="form-input" placeholder="请输入新密码（至少6位）" required minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('newPassword', this)">
                            <svg class="w-5 h-5" id="newPasswordIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="form-error" id="newPasswordError"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">确认新密码</label>
                    <div class="form-input-wrapper">
                        <input type="password" id="confirmPassword" name="confirm_password" class="form-input" placeholder="请再次输入新密码" required minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', this)">
                            <svg class="w-5 h-5" id="confirmPasswordIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="form-error" id="confirmPasswordError"></div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeChangePasswordModal()">取消</button>
                    <button type="submit" class="btn-submit" id="submitBtn">确认修改</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ============================================
         报价管理弹窗
         ============================================ -->
    <div class="modal-overlay" id="quoteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="quoteModalTitle">新增报价</h3>
                <button class="modal-close" onclick="closeQuoteModal()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="quoteForm">
                <div class="form-group">
                    <label class="form-label">品牌 <span class="text-huisen-danger">*</span></label>
                    <input type="text" id="quoteBrand" name="brand" class="form-input" placeholder="如：苹果、华为、小米">
                </div>
                
                <div class="form-group">
                    <label class="form-label">型号 <span class="text-huisen-danger">*</span></label>
                    <input type="text" id="quoteModel" name="model" class="form-input" placeholder="如：iPhone 15 Pro Max">
                </div>
                
                <div class="form-group">
                    <label class="form-label">规格 <span class="text-huisen-danger">*</span></label>
                    <input type="text" id="quoteSpec" name="spec" class="form-input" placeholder="如：256G / 512G">
                </div>
                
                <div class="form-group">
                    <label class="form-label">颜色</label>
                    <input type="text" id="quoteColor" name="color" class="form-input" placeholder="如：原色钛金属">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label text-lg font-bold text-huisen-accent">批发价 <span class="text-huisen-danger">*</span></label>
                    <input type="number" id="quotePrice" name="price" class="form-input text-xl font-bold" placeholder="0.00" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">官网价</label>
                    <input type="number" id="quoteRetailPrice" name="retail_price" class="form-input" placeholder="0.00" step="0.01" min="0">
                    <p class="text-white/40 text-xs mt-1">如果填写，将自动添加到备注中</p>
                </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">状态</label>
                    <select id="quoteCondition" name="condition" class="form-input">
                        <option value="全新未拆">全新未拆</option>
                        <option value="充新">充新</option>
                        <option value="靓机">靓机</option>
                        <option value="其他">其他</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">备注</label>
                    <input type="text" id="quoteNote" name="note" class="form-input" placeholder="如：带票、港版、国行">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeQuoteModal()">取消</button>
                    <button type="submit" class="btn-submit" id="quoteSubmitBtn">确认添加</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Toast 提示 -->
    <div class="toast" id="toast">
        <div class="toast-icon">
            <svg class="w-4 h-4 text-huisen-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <span id="toastMessage">操作成功</span>
    </div>
    
    <!-- ============================================
         JavaScript
         ============================================ -->
    <script>
        // ============================================
        // 全局变量
        // ============================================
        let businessData = [];
        
        // ============================================
        // 内部财务 AI Bot ID 配置
        // ============================================
        // 财务大管家 Bot ID
        const INTERNAL_BOT_ID = '7595051655952597026';  // 汇森财务大管家 Bot ID
        
        // Coze Token（PAT Token，已开启所有权限）
        const COZE_TOKEN = 'pat_Q6b1JPhue2wTV35bwoKrbmy7GL4I8qPB9oZ1EaleEHUN0Ptt76rs9LTKXvIcKAxC';
        
        // ============================================
        // 页面加载完成后执行
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            initParticles();
            initScrollAnimations();
            loadBusinessData();
            initCozeSDK();
        });
        
        // ============================================
        // Coze SDK 初始化 - 内部财务 AI
        // ============================================
        let cozeClient = null;
        let cozeButtonElement = null;
        
        function initCozeSDK() {
            if (typeof CozeWebSDK === 'undefined') {
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
                // 创建内部财务 AI 智能体实例（悬浮窗模式）
                cozeClient = new CozeWebSDK.WebChatClient({
                    config: {
                        bot_id: INTERNAL_BOT_ID,  // 使用内部财务 AI Bot ID
                    },
                    componentProps: {
                        title: '汇森财务大管家',  // 财务大管家标题
                        layout: window.innerWidth < 768 ? 'mobile' : 'pc',
                    },
                    auth: {
                        type: 'token',
                        token: COZE_TOKEN,
                        onRefreshToken: function () {
                            return COZE_TOKEN;
                        }
                    }
                });
                
                setTimeout(() => {
                    findCozeButton();
                    adjustCozeButtonPosition();
                }, 2000);
                
            } catch (error) {
                console.error('Coze SDK 初始化失败:', error);
            }
        }
        
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
            return null;
        }
        
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
            `;
            document.head.appendChild(style);
        }
        
        function openCozeChat() {
            if (cozeButtonElement && document.body.contains(cozeButtonElement)) {
                try {
                    cozeButtonElement.click();
                    return;
                } catch (e) {}
            }
            
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
            
            const foundButton = findCozeButton();
            if (foundButton) {
                try {
                    foundButton.click();
                } catch (e) {}
            }
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
                
                const colors = ['#00f0ff', '#7b2dff', '#ff2d7b', '#ffd700'];
                particle.style.background = colors[Math.floor(Math.random() * colors.length)];
                particle.style.boxShadow = `0 0 10px ${particle.style.background}`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // ============================================
        // 滚动动画
        // ============================================
        function initScrollAnimations() {
            if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
            
            gsap.registerPlugin(ScrollTrigger);
            
            gsap.utils.toArray('.dashboard-animate').forEach((el, i) => {
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
        }
        
        // ============================================
        // 加载业务数据
        // ============================================
        async function loadBusinessData() {
            try {
                const response = await fetch('../api/api.php?action=chart_data');
                const result = await response.json();
                
                if (result.success && result.data) {
                    businessData = result.data;
                    initCharts(result.data);
                    renderTable(result.data.raw);
                    animateCounters(result.data);
                } else {
                    useDefaultData();
                }
            } catch (error) {
                console.error('API 请求失败，使用默认数据:', error);
                useDefaultData();
            }
        }
        
        function useDefaultData() {
            const defaultData = {
                channels: ['七里河恒巨', '城关汇达旗舰店', '西固冯立超', '西固金恒生', '城关恒巨', '汇森同创', '西峰区统办楼', '安定区物美超市', '成县于军旗'],
                newAdds: [85, 81, 90, 5, 85, 0, 0, 0, 0],
                broadband: [34, 27, 46, 0, 37, 0, 0, 0, 0],
                newCoins: [44, 46, 39, 0, 22, 0, 0, 0, 0],
                stockCoins: [0, 0, 73, 3, 0, 70, 57, 115, 45],
                lowCommission: [405, 0, 0, 0, 0, 1, 0, 0, 0],
                gigabit: [38, 0, 0, 0, 0, 0, 0, 0, 0],
                familyNet: [9, 5, 5, 1, 5, 0, 0, 0, 0],
                mobileHome: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                raw: [
                    {channel_name: '七里河恒巨', new_adds: 85, broadband: 34, new_coins: 44, stock_coins: 0, low_commission: 405, gigabit: 38, family_net: 9, mobile_home: 0},
                    {channel_name: '城关汇达旗舰店', new_adds: 81, broadband: 27, new_coins: 46, stock_coins: 0, low_commission: 0, gigabit: 0, family_net: 5, mobile_home: 0},
                    {channel_name: '西固冯立超', new_adds: 90, broadband: 46, new_coins: 39, stock_coins: 73, low_commission: 0, gigabit: 0, family_net: 5, mobile_home: 0},
                    {channel_name: '西固金恒生', new_adds: 5, broadband: 0, new_coins: 0, stock_coins: 3, low_commission: 0, gigabit: 0, family_net: 1, mobile_home: 0},
                    {channel_name: '城关恒巨', new_adds: 85, broadband: 37, new_coins: 22, stock_coins: 0, low_commission: 0, gigabit: 0, family_net: 5, mobile_home: 0},
                    {channel_name: '汇森同创', new_adds: 0, broadband: 0, new_coins: 0, stock_coins: 70, low_commission: 1, gigabit: 0, family_net: 0, mobile_home: 0},
                    {channel_name: '西峰区统办楼', new_adds: 0, broadband: 0, new_coins: 0, stock_coins: 57, low_commission: 0, gigabit: 0, family_net: 0, mobile_home: 0},
                    {channel_name: '安定区物美超市', new_adds: 0, broadband: 0, new_coins: 0, stock_coins: 115, low_commission: 0, gigabit: 0, family_net: 0, mobile_home: 0},
                    {channel_name: '成县于军旗', new_adds: 0, broadband: 0, new_coins: 0, stock_coins: 45, low_commission: 0, gigabit: 0, family_net: 0, mobile_home: 0}
                ]
            };
            
            businessData = defaultData;
            initCharts(defaultData);
            renderTable(defaultData.raw);
            animateCounters(defaultData);
        }
        
        function animateCounters(data) {
            const counters = document.querySelectorAll('.counter');
            
            const totals = {
                newAdds: data.newAdds.reduce((a, b) => a + b, 0),
                broadband: data.broadband.reduce((a, b) => a + b, 0),
                stockCoins: data.stockCoins.reduce((a, b) => a + b, 0),
                lowCommission: data.lowCommission.reduce((a, b) => a + b, 0)
            };
            
            counters[0].dataset.target = totals.newAdds;
            counters[1].dataset.target = totals.broadband;
            counters[2].dataset.target = totals.stockCoins;
            counters[3].dataset.target = totals.lowCommission;
            
            counters.forEach(counter => {
                const target = parseInt(counter.dataset.target);
                const duration = 2000;
                const increment = target / (duration / 16);
                let current = 0;
                
                const updateCounter = () => {
                    current += increment;
                    if (current < target) {
                        counter.textContent = Math.floor(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                if (typeof ScrollTrigger !== 'undefined') {
                    ScrollTrigger.create({
                        trigger: counter,
                        start: 'top 85%',
                        onEnter: updateCounter,
                        once: true
                    });
                } else {
                    updateCounter();
                }
            });
        }
        
        function initCharts(data) {
            initBarChart(data);
            initPieChart(data);
            initRadarChart(data);
            initLineChart(data);
        }
        
        function initBarChart(data) {
            const chart = echarts.init(document.getElementById('barChart'));
            
            const option = {
                backgroundColor: 'transparent',
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    backgroundColor: 'rgba(10, 10, 15, 0.9)',
                    borderColor: 'rgba(0, 240, 255, 0.3)',
                    textStyle: { color: '#fff' }
                },
                legend: {
                    data: ['新增', '宽带', '新增金币'],
                    textStyle: { color: 'rgba(255, 255, 255, 0.6)' },
                    top: 0
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '15%',
                    containLabel: true
                },
                xAxis: {
                    type: 'category',
                    data: data.channels,
                    axisLabel: {
                        color: 'rgba(255, 255, 255, 0.6)',
                        rotate: 30,
                        fontSize: 10
                    },
                    axisLine: { lineStyle: { color: 'rgba(255, 255, 255, 0.1)' } }
                },
                yAxis: {
                    type: 'value',
                    axisLabel: { color: 'rgba(255, 255, 255, 0.6)' },
                    axisLine: { lineStyle: { color: 'rgba(255, 255, 255, 0.1)' } },
                    splitLine: { lineStyle: { color: 'rgba(255, 255, 255, 0.05)' } }
                },
                series: [
                    {
                        name: '新增',
                        type: 'bar',
                        data: data.newAdds,
                        itemStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: '#00f0ff' },
                                { offset: 1, color: '#0080ff' }
                            ]),
                            borderRadius: [4, 4, 0, 0]
                        }
                    },
                    {
                        name: '宽带',
                        type: 'bar',
                        data: data.broadband,
                        itemStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: '#7b2dff' },
                                { offset: 1, color: '#4a1d99' }
                            ]),
                            borderRadius: [4, 4, 0, 0]
                        }
                    },
                    {
                        name: '新增金币',
                        type: 'bar',
                        data: data.newCoins,
                        itemStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: '#ffd700' },
                                { offset: 1, color: '#ff8c00' }
                            ]),
                            borderRadius: [4, 4, 0, 0]
                        }
                    }
                ]
            };
            
            chart.setOption(option);
            window.addEventListener('resize', () => chart.resize());
        }
        
        function initPieChart(data) {
            const chart = echarts.init(document.getElementById('pieChart'));
            
            const pieData = data.channels.map((name, i) => ({
                name: name,
                value: data.stockCoins[i]
            })).filter(item => item.value > 0);
            
            const option = {
                backgroundColor: 'transparent',
                tooltip: {
                    trigger: 'item',
                    backgroundColor: 'rgba(10, 10, 15, 0.9)',
                    borderColor: 'rgba(123, 45, 255, 0.3)',
                    textStyle: { color: '#fff' },
                    formatter: '{b}: {c} ({d}%)'
                },
                legend: {
                    orient: 'vertical',
                    right: '5%',
                    top: 'center',
                    textStyle: { color: 'rgba(255, 255, 255, 0.6)', fontSize: 11 }
                },
                series: [
                    {
                        type: 'pie',
                        radius: ['40%', '70%'],
                        center: ['40%', '50%'],
                        avoidLabelOverlap: false,
                        itemStyle: {
                            borderRadius: 10,
                            borderColor: '#0a0a0f',
                            borderWidth: 2
                        },
                        label: {
                            show: false,
                            position: 'center'
                        },
                        emphasis: {
                            label: {
                                show: true,
                                fontSize: 20,
                                fontWeight: 'bold',
                                color: '#fff'
                            }
                        },
                        labelLine: { show: false },
                        data: pieData,
                        color: ['#00f0ff', '#7b2dff', '#ff2d7b', '#ffd700', '#00ff88', '#ff8c00']
                    }
                ]
            };
            
            chart.setOption(option);
            window.addEventListener('resize', () => chart.resize());
        }
        
        function initRadarChart(data) {
            const chart = echarts.init(document.getElementById('radarChart'));
            
            const activeChannels = data.channels.filter((_, i) => 
                data.newAdds[i] > 0 || data.broadband[i] > 0
            ).slice(0, 5);
            
            const radarData = activeChannels.map((name, idx) => {
                const i = data.channels.indexOf(name);
                return {
                    name: name,
                    value: [
                        data.newAdds[i],
                        data.broadband[i],
                        data.newCoins[i],
                        data.familyNet[i],
                        data.gigabit[i]
                    ]
                };
            });
            
            const option = {
                backgroundColor: 'transparent',
                tooltip: {
                    backgroundColor: 'rgba(10, 10, 15, 0.9)',
                    borderColor: 'rgba(0, 255, 136, 0.3)',
                    textStyle: { color: '#fff' }
                },
                legend: {
                    data: activeChannels,
                    bottom: 0,
                    textStyle: { color: 'rgba(255, 255, 255, 0.6)', fontSize: 10 }
                },
                radar: {
                    indicator: [
                        { name: '新增', max: 100 },
                        { name: '宽带', max: 50 },
                        { name: '新增金币', max: 50 },
                        { name: '亲情网', max: 15 },
                        { name: '千兆', max: 50 }
                    ],
                    shape: 'polygon',
                    splitNumber: 4,
                    axisName: { color: 'rgba(255, 255, 255, 0.6)' },
                    splitLine: { lineStyle: { color: 'rgba(255, 255, 255, 0.1)' } },
                    splitArea: { 
                        show: true,
                        areaStyle: { color: ['rgba(0, 240, 255, 0.02)', 'rgba(0, 240, 255, 0.04)'] }
                    },
                    axisLine: { lineStyle: { color: 'rgba(255, 255, 255, 0.1)' } }
                },
                series: [{
                    type: 'radar',
                    data: radarData,
                    symbol: 'circle',
                    symbolSize: 6,
                    lineStyle: { width: 2 },
                    areaStyle: { opacity: 0.2 }
                }],
                color: ['#00f0ff', '#7b2dff', '#ff2d7b', '#ffd700', '#00ff88']
            };
            
            chart.setOption(option);
            window.addEventListener('resize', () => chart.resize());
        }
        
        function initLineChart(data) {
            const chart = echarts.init(document.getElementById('lineChart'));
            
            const option = {
                backgroundColor: 'transparent',
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: 'rgba(10, 10, 15, 0.9)',
                    borderColor: 'rgba(255, 215, 0, 0.3)',
                    textStyle: { color: '#fff' }
                },
                legend: {
                    data: ['新增', '宽带'],
                    textStyle: { color: 'rgba(255, 255, 255, 0.6)' },
                    top: 0
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '15%',
                    containLabel: true
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: data.channels,
                    axisLabel: {
                        color: 'rgba(255, 255, 255, 0.6)',
                        rotate: 30,
                        fontSize: 10
                    },
                    axisLine: { lineStyle: { color: 'rgba(255, 255, 255, 0.1)' } }
                },
                yAxis: {
                    type: 'value',
                    axisLabel: { color: 'rgba(255, 255, 255, 0.6)' },
                    axisLine: { lineStyle: { color: 'rgba(255, 255, 255, 0.1)' } },
                    splitLine: { lineStyle: { color: 'rgba(255, 255, 255, 0.05)' } }
                },
                series: [
                    {
                        name: '新增',
                        type: 'line',
                        smooth: true,
                        data: data.newAdds,
                        lineStyle: { color: '#00f0ff', width: 3 },
                        itemStyle: { color: '#00f0ff' },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: 'rgba(0, 240, 255, 0.3)' },
                                { offset: 1, color: 'rgba(0, 240, 255, 0)' }
                            ])
                        }
                    },
                    {
                        name: '宽带',
                        type: 'line',
                        smooth: true,
                        data: data.broadband,
                        lineStyle: { color: '#7b2dff', width: 3 },
                        itemStyle: { color: '#7b2dff' },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: 'rgba(123, 45, 255, 0.3)' },
                                { offset: 1, color: 'rgba(123, 45, 255, 0)' }
                            ])
                        }
                    }
                ]
            };
            
            chart.setOption(option);
            window.addEventListener('resize', () => chart.resize());
        }
        
        function renderTable(rawData) {
            const tbody = document.getElementById('tableBody');
            
            const maxValues = {
                new_adds: Math.max(...rawData.map(r => r.new_adds)),
                broadband: Math.max(...rawData.map(r => r.broadband)),
                new_coins: Math.max(...rawData.map(r => r.new_coins)),
                stock_coins: Math.max(...rawData.map(r => r.stock_coins)),
                low_commission: Math.max(...rawData.map(r => r.low_commission)),
                gigabit: Math.max(...rawData.map(r => r.gigabit)),
                family_net: Math.max(...rawData.map(r => r.family_net)),
                mobile_home: Math.max(...rawData.map(r => r.mobile_home))
            };
            
            function getHeatClass(value, max) {
                if (value === 0) return 'heat-zero';
                const ratio = value / max;
                if (ratio > 0.7) return 'heat-high';
                if (ratio > 0.3) return 'heat-medium';
                return 'heat-low';
            }
            
            let html = '';
            
            rawData.forEach(row => {
                html += `
                    <tr>
                        <td class="font-medium text-left pl-4">${row.channel_name}</td>
                        <td class="${getHeatClass(row.new_adds, maxValues.new_adds)} rounded">${row.new_adds}</td>
                        <td class="${getHeatClass(row.broadband, maxValues.broadband)} rounded">${row.broadband}</td>
                        <td class="${getHeatClass(row.new_coins, maxValues.new_coins)} rounded">${row.new_coins}</td>
                        <td class="${getHeatClass(row.stock_coins, maxValues.stock_coins)} rounded">${row.stock_coins}</td>
                        <td class="${getHeatClass(row.low_commission, maxValues.low_commission)} rounded">${row.low_commission}</td>
                        <td class="${getHeatClass(row.gigabit, maxValues.gigabit)} rounded">${row.gigabit}</td>
                        <td class="${getHeatClass(row.family_net, maxValues.family_net)} rounded">${row.family_net}</td>
                        <td class="${getHeatClass(row.mobile_home, maxValues.mobile_home)} rounded">${row.mobile_home}</td>
                    </tr>
                `;
            });
            
            const totals = rawData.reduce((acc, row) => {
                Object.keys(row).forEach(key => {
                    if (key !== 'channel_name' && key !== 'id' && key !== 'created_at' && key !== 'updated_at') {
                        acc[key] = (acc[key] || 0) + (parseInt(row[key]) || 0);
                    }
                });
                return acc;
            }, {});
            
            html += `
                <tr class="bg-huisen-accent/10 font-bold">
                    <td class="text-left pl-4 text-huisen-accent">总计</td>
                    <td class="text-huisen-accent">${totals.new_adds || 0}</td>
                    <td class="text-huisen-accent">${totals.broadband || 0}</td>
                    <td class="text-huisen-accent">${totals.new_coins || 0}</td>
                    <td class="text-huisen-accent">${totals.stock_coins || 0}</td>
                    <td class="text-huisen-accent">${totals.low_commission || 0}</td>
                    <td class="text-huisen-accent">${totals.gigabit || 0}</td>
                    <td class="text-huisen-accent">${totals.family_net || 0}</td>
                    <td class="text-huisen-accent">${totals.mobile_home || 0}</td>
                </tr>
            `;
            
            tbody.innerHTML = html;
        }
        
        // ============================================
        // 修改密码功能
        // ============================================
        
        // 打开修改密码弹窗
        function openChangePasswordModal() {
            const modal = document.getElementById('changePasswordModal');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            // 重置表单
            document.getElementById('changePasswordForm').reset();
            clearErrors();
        }
        
        // 关闭修改密码弹窗
        function closeChangePasswordModal() {
            const modal = document.getElementById('changePasswordModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
            // 重置表单
            document.getElementById('changePasswordForm').reset();
            clearErrors();
        }
        
        // 切换密码显示/隐藏
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('svg');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                `;
            } else {
                input.type = 'password';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }
        
        // 清除所有错误提示
        function clearErrors() {
            document.querySelectorAll('.form-error').forEach(el => {
                el.classList.remove('show');
                el.textContent = '';
            });
        }
        
        // 显示错误提示
        function showError(inputId, message) {
            const errorEl = document.getElementById(inputId + 'Error');
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.add('show');
            }
        }
        
        // 显示 Toast 提示
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = toast.querySelector('.toast-icon');
            
            toastMessage.textContent = message;
            
            if (isError) {
                toast.classList.add('error');
                toastIcon.innerHTML = `
                    <svg class="w-4 h-4 text-huisen-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                `;
            } else {
                toast.classList.remove('error');
                toastIcon.innerHTML = `
                    <svg class="w-4 h-4 text-huisen-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                `;
            }
            
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
        
        // 表单提交处理
        document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            clearErrors();
            
            const oldPassword = document.getElementById('oldPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const submitBtn = document.getElementById('submitBtn');
            
            // 前端验证
            let hasError = false;
            
            if (!oldPassword) {
                showError('oldPassword', '请输入旧密码');
                hasError = true;
            }
            
            if (!newPassword) {
                showError('newPassword', '请输入新密码');
                hasError = true;
            } else if (newPassword.length < 6) {
                showError('newPassword', '新密码长度至少为6位');
                hasError = true;
            }
            
            if (!confirmPassword) {
                showError('confirmPassword', '请确认新密码');
                hasError = true;
            } else if (newPassword !== confirmPassword) {
                showError('confirmPassword', '两次输入的密码不一致');
                hasError = true;
            }
            
            if (hasError) {
                return;
            }
            
            // 禁用提交按钮，显示加载状态
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtn.textContent = '修改中...';
            
            try {
                const response = await fetch('../api/auth.php?action=change_password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        old_password: oldPassword,
                        new_password: newPassword
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('密码修改成功！', false);
                    setTimeout(() => {
                        closeChangePasswordModal();
                    }, 1500);
                } else {
                    showToast(result.error || '密码修改失败', true);
                    if (result.error && result.error.includes('旧密码')) {
                        showError('oldPassword', result.error);
                    }
                }
            } catch (error) {
                console.error('密码修改请求失败:', error);
                showToast('网络错误，请稍后重试', true);
            } finally {
                // 恢复提交按钮
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                submitBtn.textContent = '确认修改';
            }
        });
        
        // 点击弹窗外部关闭
        document.getElementById('changePasswordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeChangePasswordModal();
            }
        });
        
        // 点击报价弹窗外部关闭
        document.getElementById('quoteModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeQuoteModal();
            }
        });
        
        // ESC 键关闭弹窗
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('changePasswordModal');
                if (modal.classList.contains('show')) {
                    closeChangePasswordModal();
                }
            }
        });
        
        // ============================================
        // 手机报价管理功能
        // ============================================
        let allQuotesData = [];
        let allBrands = [];
        let currentBrand = '全部';
        
        // 页面加载时初始化报价管理
        document.addEventListener('DOMContentLoaded', function() {
            loadQuotesData();
            
            // 搜索框实时过滤
            const quoteSearch = document.getElementById('quoteSearch');
            if (quoteSearch) {
                quoteSearch.addEventListener('input', function() {
                    filterQuotesTable(this.value.trim());
                });
            }
            
            // ============================================
            // 事件委托：处理表格中的编辑和删除按钮点击
            // ============================================
            // 绑定在表格容器上，监听内部所有按钮的点击
            const quotesTable = document.getElementById('quotesTable');
            if (quotesTable) {
                quotesTable.addEventListener('click', function(e) {
                    // 使用 closest 查找最近的按钮（兼容点击图标的情况）
                    const editBtn = e.target.closest('.edit-quote-btn');
                    const deleteBtn = e.target.closest('.delete-quote-btn');
                    
                    if (editBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('✅ [调试] 点击了编辑按钮，ID:', editBtn.dataset.id);
                        openEditQuoteModalFromRow(editBtn);
                    } else if (deleteBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('✅ [调试] 点击了删除按钮，ID:', deleteBtn.dataset.id);
                        const id = parseInt(deleteBtn.dataset.id);
                        if (id) {
                            deleteQuote(id);
                        }
                    }
                });
            }
            
            console.log('✅ [调试] 事件委托已绑定，表格容器:', quotesTable ? '找到' : '未找到');
        });
        
        // 加载报价数据
        async function loadQuotesData() {
            try {
                const response = await fetch('../api/quotes.php');
                const result = await response.json();
                
                if (result.success) {
                    allQuotesData = result.data.quotes || [];
                    allBrands = result.data.brands || [];
                    
                    // 渲染品牌 Tab
                    renderBrandTabs();
                    
                    // 渲染表格
                    renderQuotesTable(allQuotesData);
                } else {
                    console.error('加载报价失败:', result.error);
                    showToast('加载报价失败: ' + (result.error || '未知错误'), true);
                }
            } catch (error) {
                console.error('加载报价失败:', error);
                showToast('网络错误，请稍后重试', true);
            }
        }
        
        // 渲染品牌 Tab
        function renderBrandTabs() {
            const container = document.getElementById('brandTabsContainer');
            if (!container) return;
            
            // 保留"全部"按钮
            let html = '<button class="brand-tab active flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300" data-brand="全部" onclick="switchBrand(\'全部\')">全部</button>';
            
            // 添加品牌按钮
            allBrands.forEach(function(brand) {
                if (brand && brand !== '未分类') {
                    html += `<button class="brand-tab flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300" data-brand="${brand}" onclick="switchBrand('${brand}')">${brand}</button>`;
                }
            });
            
            container.innerHTML = html;
        }
        
        // 切换品牌
        function switchBrand(brand) {
            currentBrand = brand;
            
            // 更新 Tab 状态
            document.querySelectorAll('.brand-tab').forEach(function(tab) {
                if (tab.dataset.brand === brand) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
            
            // 过滤数据
            filterQuotesTable(document.getElementById('quoteSearch')?.value.trim() || '');
        }
        
        // 渲染报价表格
        function renderQuotesTable(quotes) {
            const tbody = document.getElementById('quotesTableBody');
            if (!tbody) return;
            
            if (quotes.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-white/60 py-12">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-12 h-12 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <span>暂无报价数据</span>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            quotes.forEach(function(quote) {
                const price = parseFloat(quote.price || 0).toFixed(2);
                const retailPrice = quote.retail_price ? parseFloat(quote.retail_price).toFixed(2) : null;
                
                // 从 note 中提取官网价（如果没有 retail_price）
                let displayRetailPrice = retailPrice;
                if (!displayRetailPrice && quote.note) {
                    const match = quote.note.match(/官网价[:：]\s*¥?\s*([\d.]+)/);
                    if (match) {
                        displayRetailPrice = parseFloat(match[1]).toFixed(2);
                    }
                }
                
                html += `
                    <tr class="quote-row">
                        <td class="font-medium text-white/90">${quote.brand || '-'}</td>
                        <td class="font-medium text-white">${quote.model || '-'}</td>
                        <td class="text-white/80">${quote.spec || '-'}</td>
                        <td class="font-bold text-lg text-huisen-accent">¥${price}</td>
                        <td class="text-white/70">${displayRetailPrice ? '¥' + displayRetailPrice : '-'}</td>
                        <td class="text-white/60 text-sm max-w-xs truncate" title="${quote.note || ''}">${quote.note || '-'}</td>
                        <td>
                            <div class="flex items-center justify-center gap-2">
                                <button data-action="edit" 
                                        data-id="${quote.id}"
                                        data-brand="${quote.brand || ''}"
                                        data-model="${quote.model || ''}"
                                        data-spec="${quote.spec || ''}"
                                        data-price="${quote.price || ''}"
                                        data-retail-price="${displayRetailPrice || ''}"
                                        data-note="${(quote.note || '').replace(/"/g, '&quot;')}"
                                        class="action-btn action-btn-edit flex items-center gap-1 edit-quote-btn">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    编辑
                                </button>
                                <button data-action="delete" 
                                        data-id="${quote.id}"
                                        class="action-btn action-btn-delete flex items-center gap-1 delete-quote-btn">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    删除
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }
        
        // 过滤报价表格
        function filterQuotesTable(search) {
            let filtered = allQuotesData;
            
            // 品牌过滤
            if (currentBrand && currentBrand !== '全部') {
                filtered = filtered.filter(function(quote) {
                    return quote.brand === currentBrand;
                });
            }
            
            // 搜索过滤
            if (search) {
                const searchLower = search.toLowerCase();
                filtered = filtered.filter(function(quote) {
                    return (quote.model && quote.model.toLowerCase().includes(searchLower)) || 
                           (quote.spec && quote.spec.toLowerCase().includes(searchLower)) ||
                           (quote.brand && quote.brand.toLowerCase().includes(searchLower)) ||
                           (quote.note && quote.note.toLowerCase().includes(searchLower));
                });
            }
            
            renderQuotesTable(filtered);
        }
        
        // 快速编辑价格
        function editPrice(id, field, currentValue) {
            const newValue = prompt(`请输入新的${field === 'price' ? '批发价' : '零售价'}:`, currentValue === 'null' || currentValue === '-' ? '' : currentValue);
            
            if (newValue === null) return;
            
            const numValue = parseFloat(newValue);
            if (isNaN(numValue) || numValue <= 0) {
                showToast('请输入有效的价格', true);
                return;
            }
            
            updateQuoteField(id, field, numValue);
        }
        
        // 更新报价字段
        async function updateQuoteField(id, field, value) {
            try {
                const response = await fetch(`../api/quotes.php?id=${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        [field]: value
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('价格更新成功！', false);
                    loadQuotesData();
                } else {
                    showToast(result.error || '更新失败', true);
                }
            } catch (error) {
                console.error('更新报价失败:', error);
                showToast('网络错误，请稍后重试', true);
            }
        }
        
        // 打开新增报价弹窗
        function openAddQuoteModal() {
            // 安全获取元素的辅助函数
            const safeGetElement = (id) => document.getElementById(id);
            const safeShowFormGroup = (el) => {
                if (el && el.closest('.form-group')) {
                    el.closest('.form-group').style.display = 'block';
                }
            };
            
            const titleEl = safeGetElement('quoteModalTitle');
            const formEl = safeGetElement('quoteForm');
            const submitBtn = safeGetElement('quoteSubmitBtn');
            const modalEl = safeGetElement('quoteModal');
            
            if (titleEl) titleEl.textContent = '新增报价';
            if (formEl) {
                formEl.reset();
                formEl.dataset.quoteId = '';
            }
            if (submitBtn) submitBtn.textContent = '确认添加';
            
            // 显示所有字段（新增时需要）
            safeShowFormGroup(safeGetElement('quoteBrand'));
            safeShowFormGroup(safeGetElement('quoteModel'));
            safeShowFormGroup(safeGetElement('quoteSpec'));
            safeShowFormGroup(safeGetElement('quoteColor'));
            safeShowFormGroup(safeGetElement('quoteCondition'));
            
            // 确保新增模式下必填
            if (safeGetElement('quoteBrand')) safeGetElement('quoteBrand').required = true;
            if (safeGetElement('quoteModel')) safeGetElement('quoteModel').required = true;
            if (safeGetElement('quoteSpec')) safeGetElement('quoteSpec').required = true;
            
            if (modalEl) {
                modalEl.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }
        
        // 从表格行直接打开编辑弹窗（优化版：不需要重新请求数据）
        function openEditQuoteModalFromRow(button) {
            // 安全获取元素的辅助函数
            const safeGetElement = (id) => document.getElementById(id);
            const safeSetValue = (id, value) => {
                const el = safeGetElement(id);
                if (el) el.value = value;
            };
            const safeSetText = (id, text) => {
                const el = safeGetElement(id);
                if (el) el.textContent = text;
            };
            const safeHideFormGroup = (el) => {
                if (el && el.closest('.form-group')) {
                    el.closest('.form-group').style.display = 'none';
                }
            };
            
            const id = parseInt(button.dataset.id);
            const brand = button.dataset.brand || '';
            const model = button.dataset.model || '';
            const spec = button.dataset.spec || '';
            const price = button.dataset.price || '';
            const retailPrice = button.dataset.retailPrice || '';
            let note = button.dataset.note || '';
            
            console.log('✅ [调试] 打开编辑弹窗，数据:', {
                id, brand, model, spec, price, retailPrice, note
            });
            
            // 如果 note 中有官网价，提取出来
            let extractedRetailPrice = retailPrice;
            if (!extractedRetailPrice && note) {
                const match = note.match(/官网价[:：]\s*¥?\s*([\d.]+)/);
                if (match) {
                    extractedRetailPrice = match[1];
                }
            }
            
            // 从 note 中移除官网价部分（用于显示在备注框中）
            if (extractedRetailPrice) {
                note = note.replace(/官网价[:：]\s*¥?\s*[\d.]+/g, '').trim();
            }
            
            // 填充表单（安全版本）
            // 注意：即使隐藏字段，也要设置值，以便提交时能获取到
            safeSetText('quoteModalTitle', `修改报价 - ${brand} ${model}`);
            safeSetValue('quoteBrand', brand);  // 设置值，即使隐藏
            safeSetValue('quoteModel', model);  // 设置值，即使隐藏
            safeSetValue('quoteSpec', spec);    // 设置值，即使隐藏
            safeSetValue('quotePrice', price);
            safeSetValue('quoteRetailPrice', extractedRetailPrice);
            safeSetValue('quoteNote', note);
            
            const formEl = safeGetElement('quoteForm');
            const submitBtn = safeGetElement('quoteSubmitBtn');
            if (formEl) {
                formEl.dataset.quoteId = id;
                // 保存完整数据到 form dataset，以便提交时使用
                formEl.dataset.brand = brand;
                formEl.dataset.model = model;
                formEl.dataset.spec = spec;
            }
            if (submitBtn) {
                submitBtn.dataset.quoteId = id;
                submitBtn.textContent = '保存修改';
            }
            
            // 编辑模式下，隐藏不需要修改的字段（只显示价格和备注）
            safeHideFormGroup(safeGetElement('quoteBrand'));
            safeHideFormGroup(safeGetElement('quoteModel'));
            safeHideFormGroup(safeGetElement('quoteSpec'));
            safeHideFormGroup(safeGetElement('quoteColor'));
            safeHideFormGroup(safeGetElement('quoteCondition'));
            
            // 移除必填属性，防止隐藏字段验证失败
            if (safeGetElement('quoteBrand')) safeGetElement('quoteBrand').required = false;
            if (safeGetElement('quoteModel')) safeGetElement('quoteModel').required = false;
            if (safeGetElement('quoteSpec')) safeGetElement('quoteSpec').required = false;
            
            // 显示弹窗
            const modal = safeGetElement('quoteModal');
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
                console.log('✅ [调试] 弹窗已显示');
            } else {
                console.error('❌ [错误] 找不到弹窗元素 #quoteModal');
            }
        }
        
        // 兼容旧版本的函数（保留，以防其他地方调用）
        async function openEditQuoteModal(id) {
            console.log('⚠️ [警告] 使用了旧版本的 openEditQuoteModal，建议使用事件委托');
            const quote = allQuotesData.find(q => q.id === id);
            if (!quote) {
                showToast('报价不存在', true);
                return;
            }
            
            // 提取官网价（从 retail_price 或 note 中）
            let retailPrice = quote.retail_price;
            if (!retailPrice && quote.note) {
                const match = quote.note.match(/官网价[:：]\s*¥?\s*([\d.]+)/);
                if (match) {
                    retailPrice = match[1];
                }
            }
            
            // 提取备注（去除官网价部分）
            let note = quote.note || '';
            if (retailPrice) {
                note = note.replace(/官网价[:：]\s*¥?\s*[\d.]+/g, '').trim();
            }
            
            document.getElementById('quoteModalTitle').textContent = `修改报价 - ${quote.brand} ${quote.model}`;
            document.getElementById('quoteBrand').value = quote.brand || '';
            document.getElementById('quoteModel').value = quote.model || '';
            document.getElementById('quoteSpec').value = quote.spec || '';
            document.getElementById('quoteColor').value = quote.color || '';
            document.getElementById('quotePrice').value = quote.price || '';
            document.getElementById('quoteRetailPrice').value = retailPrice || '';
            document.getElementById('quoteCondition').value = quote.condition || '全新未拆';
            document.getElementById('quoteNote').value = note;
            document.getElementById('quoteForm').dataset.quoteId = id;
            document.getElementById('quoteSubmitBtn').textContent = '保存修改';
            
            // 编辑模式下，隐藏不需要修改的字段（只显示价格和备注）
            document.getElementById('quoteBrand').closest('.form-group').style.display = 'none';
            document.getElementById('quoteModel').closest('.form-group').style.display = 'none';
            document.getElementById('quoteSpec').closest('.form-group').style.display = 'none';
            document.getElementById('quoteColor').closest('.form-group').style.display = 'none';
            document.getElementById('quoteCondition').closest('.form-group').style.display = 'none';
            
            document.getElementById('quoteModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        // 关闭报价弹窗
        function closeQuoteModal() {
            document.getElementById('quoteModal').classList.remove('show');
            document.body.style.overflow = '';
            document.getElementById('quoteForm').reset();
        }
        
        // 删除报价
        async function deleteQuote(id) {
            const quote = allQuotesData.find(q => q.id === id);
            const quoteName = quote ? `${quote.brand} ${quote.model}` : '这条报价';
            
            if (!confirm(`确定要删除 ${quoteName} 吗？此操作不可恢复！`)) {
                return;
            }
            
            try {
                const response = await fetch(`../api/quotes.php?id=${id}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('删除成功！', false);
                    loadQuotesData();
                } else {
                    showToast(result.error || '删除失败', true);
                }
            } catch (error) {
                console.error('删除报价失败:', error);
                showToast('网络错误，请稍后重试', true);
            }
        }
        
        // 提交报价表单
        document.getElementById('quoteForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const quoteId = form.dataset.quoteId;
            const submitBtn = form.querySelector('button[type="submit"]');
            
            // 如果是编辑模式，从已有数据中获取 brand、model、spec
            let brand, model, spec, color, condition;
            if (quoteId) {
                // 编辑模式：优先从 form.dataset 获取（在 openEditQuoteModalFromRow 中设置）
                brand = form.dataset.brand || '';
                model = form.dataset.model || '';
                spec = form.dataset.spec || '';
                
                // 如果 dataset 中没有，尝试从 allQuotesData 中查找
                if (!brand || !model || !spec) {
                    const existingQuote = allQuotesData.find(q => q.id == quoteId);
                    if (existingQuote) {
                        brand = brand || existingQuote.brand || '';
                        model = model || existingQuote.model || '';
                        spec = spec || existingQuote.spec || '';
                        color = existingQuote.color || null;
                        condition = existingQuote.condition || '全新未拆';
                    }
                }
                
                // 如果还是找不到，从隐藏的输入框获取（即使隐藏，值也应该被设置了）
                if (!brand || !model || !spec) {
                    const brandEl = document.getElementById('quoteBrand');
                    const modelEl = document.getElementById('quoteModel');
                    const specEl = document.getElementById('quoteSpec');
                    brand = brand || (brandEl ? brandEl.value.trim() : '');
                    model = model || (modelEl ? modelEl.value.trim() : '');
                    spec = spec || (specEl ? specEl.value.trim() : '');
                }
                
                // color 和 condition 从输入框获取（如果存在）
                const colorEl = document.getElementById('quoteColor');
                const conditionEl = document.getElementById('quoteCondition');
                color = colorEl && colorEl.value.trim() ? colorEl.value.trim() : null;
                condition = conditionEl && conditionEl.value.trim() ? conditionEl.value.trim() : '全新未拆';
            } else {
                // 新增模式：从输入框获取
                brand = document.getElementById('quoteBrand').value.trim();
                model = document.getElementById('quoteModel').value.trim();
                spec = document.getElementById('quoteSpec').value.trim();
                color = document.getElementById('quoteColor').value.trim() || null;
                condition = document.getElementById('quoteCondition').value.trim() || '全新未拆';
            }
            
            const price = parseFloat(document.getElementById('quotePrice').value);
            const retailPriceEl = document.getElementById('quoteRetailPrice');
            const retail_price = retailPriceEl && retailPriceEl.value.trim() ? parseFloat(retailPriceEl.value) : null;
            const note = document.getElementById('quoteNote').value.trim() || null;
            
            const data = {
                brand: brand,
                model: model,
                spec: spec,
                color: color,
                price: price,
                retail_price: retail_price,
                condition: condition,
                note: note
            };
            
            // 验证必填字段（编辑模式下，brand、model、spec 可能被隐藏，但应该从已有数据中获取）
            if (!data.brand || !data.model || !data.spec || !data.price || isNaN(data.price) || data.price <= 0) {
                console.error('❌ [验证失败] 数据:', data);
                showToast('请填写完整的必填信息', true);
                return;
            }
            
            console.log('✅ [提交数据]', data);
            
            submitBtn.disabled = true;
            submitBtn.textContent = quoteId ? '更新中...' : '添加中...';
            
            try {
                const url = quoteId ? `../api/quotes.php?id=${quoteId}` : '../api/quotes.php';
                const method = quoteId ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(quoteId ? '更新成功！' : '添加成功！', false);
                    closeQuoteModal();
                    loadQuotesData();
                } else {
                    showToast(result.error || '操作失败', true);
                }
            } catch (error) {
                console.error('提交报价失败:', error);
                showToast('网络错误，请稍后重试', true);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = quoteId ? '确认更新' : '确认添加';
            }
        });
        
        // ============================================
        // 退出登录功能
        // ============================================
        async function handleLogout() {
            try {
                // 调用登出 API
                const response = await fetch('../api/auth.php?action=logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const result = await response.json();
                
                // 无论 API 调用是否成功，都跳转到外部网站首页
                // 平滑过渡动画
                gsap.to('body', {
                    opacity: 0,
                    duration: 0.8,
                    ease: 'power2.inOut',
                    onComplete: () => {
                        // 跳转到外部网站首页
                        window.location.href = '/index.php';
                    }
                });
            } catch (error) {
                console.error('登出请求失败:', error);
                // 即使请求失败，也跳转到首页
                gsap.to('body', {
                    opacity: 0,
                    duration: 0.8,
                    ease: 'power2.inOut',
                    onComplete: () => {
                        window.location.href = '/index.php';
                    }
                });
            }
        }
    </script>
</body>
</html>
