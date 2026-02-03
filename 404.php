<?php
/**
 * ==========================================
 * 404错误页面 - 专业且友好
 * ==========================================
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>页面未找到 - 汇森科技</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --brand-red: #e53935; }
        body { font-family: 'Inter', -apple-system, sans-serif; }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-2xl mx-auto px-4 text-center">
        <!-- 404动画图标 -->
        <div class="float-animation mb-8">
            <svg class="w-64 h-64 mx-auto text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <!-- 404文字 -->
        <h1 class="text-8xl font-bold mb-4" style="color: var(--brand-red);">404</h1>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">哎呀！页面走丢了</h2>
        <p class="text-lg text-gray-600 mb-8">
            您访问的页面可能已被移除，或者URL输入有误<br>
            别担心，让我们帮您找到正确的方向
        </p>

        <!-- 快捷导航 -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-12">
            <a href="/core/index_v4.php" class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition group">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900">返回首页</h3>
                <p class="text-sm text-gray-500 mt-2">回到网站首页</p>
            </a>

            <a href="/core/quotes_final.php" class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition group">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900">手机报价</h3>
                <p class="text-sm text-gray-500 mt-2">查看所有产品</p>
            </a>

            <a href="/cooperation.php" class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition group">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900">批发合作</h3>
                <p class="text-sm text-gray-500 mt-2">了解合作方式</p>
            </a>
        </div>

        <!-- 搜索框 -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h3 class="text-lg font-bold text-gray-900 mb-4">或者试试搜索您想要的产品</h3>
            <form action="/core/quotes_final.php" method="get" class="max-w-md mx-auto">
                <div class="relative">
                    <input type="text"
                           name="search"
                           placeholder="搜索手机型号、品牌..."
                           class="w-full px-6 py-4 bg-gray-50 border-2 border-gray-200 rounded-full focus:outline-none focus:border-red-500 transition">
                    <button type="submit"
                            class="absolute right-2 top-1/2 -translate-y-1/2 px-6 py-2 rounded-full text-white font-medium transition hover:opacity-90"
                            style="background: var(--brand-red);">
                        搜索
                    </button>
                </div>
            </form>
        </div>

        <!-- 联系方式 -->
        <div class="text-gray-500 text-sm">
            <p>如果您认为这是网站错误，请联系我们</p>
            <p class="mt-2">客服热线: <a href="tel:400-XXX-XXXX" class="text-red-600 hover:underline">400-XXX-XXXX</a></p>
        </div>

        <!-- 返回按钮 -->
        <div class="mt-8">
            <button onclick="history.back()" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                返回上一页
            </button>
        </div>
    </div>
</body>
</html>
