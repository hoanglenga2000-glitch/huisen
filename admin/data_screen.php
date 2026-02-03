<?php
/**
 * ==========================================
 * 汇森科技 - 批发数据可视化大屏 v1.0
 * ==========================================
 *
 * 功能：
 * 1. 实时交易动态滚动
 * 2. 今日交易额/出货量仪表盘
 * 3. 品牌关注度饼状图
 * 4. 全国发货热点地图
 */

session_start();
require_once '../config/config.php';

// 权限验证 - 仅员工/管理员可访问
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ../core/user_center.php');
    exit;
}

// 模拟实时数据（实际应从数据库获取）
$today_stats = [
    'total_amount' => 2856800,
    'total_orders' => 156,
    'total_units' => 423,
    'avg_order' => 18313,
];

// 模拟最新询价/订单
$recent_activities = [
    ['time' => '14:35', 'user' => '王总', 'action' => '询价', 'product' => 'iPhone 16 Pro Max', 'qty' => 10],
    ['time' => '14:32', 'user' => '李经理', 'action' => '下单', 'product' => '华为 Mate 70 Pro', 'qty' => 5],
    ['time' => '14:28', 'user' => '张总', 'action' => '询价', 'product' => '小米 15 Ultra', 'qty' => 20],
    ['time' => '14:25', 'user' => '陈老板', 'action' => '下单', 'product' => 'OPPO Find X8', 'qty' => 8],
    ['time' => '14:20', 'user' => '刘总', 'action' => '询价', 'product' => 'vivo X200 Pro', 'qty' => 15],
    ['time' => '14:15', 'user' => '赵经理', 'action' => '下单', 'product' => '荣耀 Magic7 Pro', 'qty' => 6],
    ['time' => '14:10', 'user' => '周总', 'action' => '询价', 'product' => '三星 S25 Ultra', 'qty' => 12],
    ['time' => '14:05', 'user' => '吴老板', 'action' => '下单', 'product' => '一加 13', 'qty' => 10],
];

// 品牌关注度数据
$brand_focus = [
    ['name' => 'Apple', 'value' => 35],
    ['name' => '华为', 'value' => 28],
    ['name' => '小米', 'value' => 15],
    ['name' => 'OPPO', 'value' => 8],
    ['name' => 'vivo', 'value' => 7],
    ['name' => '其他', 'value' => 7],
];

// 地区发货数据
$region_data = [
    ['name' => '广东', 'value' => 156],
    ['name' => '浙江', 'value' => 89],
    ['name' => '江苏', 'value' => 76],
    ['name' => '上海', 'value' => 65],
    ['name' => '北京', 'value' => 54],
    ['name' => '福建', 'value' => 48],
    ['name' => '山东', 'value' => 42],
    ['name' => '四川', 'value' => 38],
];

$base_path = '../';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据大屏 - 汇森科技</title>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0a0a1a 0%, #1a1a2e 50%, #0f0f23 100%);
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* 科技感背景 */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(211, 47, 47, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(33, 150, 243, 0.1) 0%, transparent 50%),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><line x1="0" y1="50" x2="100" y2="50" stroke="rgba(255,255,255,0.03)" stroke-width="0.5"/><line x1="50" y1="0" x2="50" y2="100" stroke="rgba(255,255,255,0.03)" stroke-width="0.5"/></svg>');
            background-size: 100% 100%, 100% 100%, 50px 50px;
            pointer-events: none;
            z-index: 0;
        }

        /* 顶部标题栏 */
        .header {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 10;
        }

        .header-title {
            font-family: 'Orbitron', monospace;
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(90deg, #D32F2F, #FF6B6B, #D32F2F);
            background-size: 200% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientFlow 3s infinite;
        }

        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .header-time {
            font-family: 'Orbitron', monospace;
            font-size: 24px;
            color: #00E5FF;
            text-shadow: 0 0 10px rgba(0, 229, 255, 0.5);
        }

        .header-back {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }

        .header-back:hover {
            color: #D32F2F;
        }

        /* 主内容区 */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 24px;
            padding: 24px 32px;
            min-height: calc(100vh - 80px);
            position: relative;
            z-index: 1;
        }

        /* 面板通用样式 */
        .panel {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #D32F2F, transparent);
        }

        .panel-title {
            font-size: 16px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-title .icon {
            font-size: 20px;
        }

        /* 左侧 - 实时动态 */
        .activity-list {
            height: 400px;
            overflow: hidden;
            position: relative;
        }

        .activity-scroll {
            animation: scrollUp 20s linear infinite;
        }

        @keyframes scrollUp {
            0% { transform: translateY(0); }
            100% { transform: translateY(-50%); }
        }

        .activity-item {
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 8px;
            margin-bottom: 8px;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }

        .activity-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-left-color: #D32F2F;
        }

        .activity-item .time {
            font-family: 'Orbitron', monospace;
            font-size: 12px;
            color: #00E5FF;
            margin-bottom: 4px;
        }

        .activity-item .content {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
        }

        .activity-item .highlight {
            color: #FF6B6B;
            font-weight: 600;
        }

        .activity-item .action-order {
            color: #4CAF50;
        }

        .activity-item .action-inquiry {
            color: #FF9800;
        }

        /* 中间 - 核心数据 */
        .center-panel {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(211, 47, 47, 0.1) 0%, rgba(211, 47, 47, 0.02) 100%);
            border: 1px solid rgba(211, 47, 47, 0.3);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #D32F2F, transparent);
        }

        .stat-card .label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-family: 'Orbitron', monospace;
            font-size: 32px;
            font-weight: 700;
            color: #FF6B6B;
            text-shadow: 0 0 20px rgba(255, 107, 107, 0.5);
        }

        .stat-card .unit {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.5);
            margin-left: 4px;
        }

        /* 图表容器 */
        .chart-container {
            flex: 1;
            min-height: 300px;
        }

        /* 右侧 - 品牌饼图 */
        .brand-chart {
            height: 300px;
        }

        /* 地区排行 */
        .region-list {
            margin-top: 16px;
        }

        .region-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .region-rank {
            width: 24px;
            height: 24px;
            background: rgba(211, 47, 47, 0.2);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            margin-right: 12px;
        }

        .region-rank.top-3 {
            background: linear-gradient(135deg, #D32F2F, #FF6B6B);
        }

        .region-name {
            flex: 1;
            font-size: 14px;
        }

        .region-bar {
            width: 100px;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            margin-right: 12px;
            overflow: hidden;
        }

        .region-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #D32F2F, #FF6B6B);
            border-radius: 3px;
            transition: width 1s ease;
        }

        .region-value {
            font-family: 'Orbitron', monospace;
            font-size: 14px;
            color: #00E5FF;
            min-width: 40px;
            text-align: right;
        }

        /* 响应式 */
        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .header {
                flex-wrap: wrap;
                gap: 12px;
            }

            .header-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="header-back">
            ← 返回后台
        </a>
        <h1 class="header-title">汇森科技 · 实时数据监控中心</h1>
        <div class="header-time" id="currentTime">--:--:--</div>
    </header>

    <main class="main-content">
        <!-- 左侧 - 实时动态 -->
        <div class="panel">
            <h2 class="panel-title"><span class="icon">📊</span> 实时交易动态</h2>
            <div class="activity-list">
                <div class="activity-scroll" id="activityScroll">
                    <?php foreach (array_merge($recent_activities, $recent_activities) as $activity): ?>
                    <div class="activity-item">
                        <div class="time">[<?php echo $activity['time']; ?>]</div>
                        <div class="content">
                            用户 <span class="highlight"><?php echo htmlspecialchars($activity['user']); ?></span>
                            <span class="<?php echo $activity['action'] === '下单' ? 'action-order' : 'action-inquiry'; ?>">
                                <?php echo $activity['action']; ?>
                            </span>了
                            <span class="highlight"><?php echo htmlspecialchars($activity['product']); ?></span>
                            <?php echo $activity['qty']; ?>台
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 中间 - 核心数据 -->
        <div class="center-panel">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">今日交易额</div>
                    <div class="value" id="totalAmount">0<span class="unit">元</span></div>
                </div>
                <div class="stat-card">
                    <div class="label">订单数量</div>
                    <div class="value" id="totalOrders">0<span class="unit">单</span></div>
                </div>
                <div class="stat-card">
                    <div class="label">出货数量</div>
                    <div class="value" id="totalUnits">0<span class="unit">台</span></div>
                </div>
                <div class="stat-card">
                    <div class="label">平均客单价</div>
                    <div class="value" id="avgOrder">0<span class="unit">元</span></div>
                </div>
            </div>

            <div class="panel" style="flex: 1;">
                <h2 class="panel-title"><span class="icon">📈</span> 24小时交易趋势</h2>
                <div class="chart-container" id="trendChart"></div>
            </div>
        </div>

        <!-- 右侧 - 品牌分布 & 地区排行 -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <div class="panel">
                <h2 class="panel-title"><span class="icon">🏷️</span> 品牌关注度</h2>
                <div class="brand-chart" id="brandChart"></div>
            </div>

            <div class="panel">
                <h2 class="panel-title"><span class="icon">🗺️</span> 发货地区排行</h2>
                <div class="region-list">
                    <?php
                    $max_value = max(array_column($region_data, 'value'));
                    foreach ($region_data as $index => $region):
                        $percentage = ($region['value'] / $max_value) * 100;
                    ?>
                    <div class="region-item">
                        <div class="region-rank <?php echo $index < 3 ? 'top-3' : ''; ?>"><?php echo $index + 1; ?></div>
                        <div class="region-name"><?php echo htmlspecialchars($region['name']); ?></div>
                        <div class="region-bar">
                            <div class="region-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="region-value"><?php echo $region['value']; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // 实时时间
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('zh-CN', { hour12: false });
            document.getElementById('currentTime').textContent = timeStr;
        }
        setInterval(updateTime, 1000);
        updateTime();

        // 数字动画
        function animateNumber(elementId, targetValue) {
            const element = document.getElementById(elementId);
            const duration = 2000;
            const startTime = performance.now();
            const startValue = 0;

            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeProgress = 1 - Math.pow(1 - progress, 3);
                const currentValue = Math.floor(startValue + (targetValue - startValue) * easeProgress);

                element.innerHTML = currentValue.toLocaleString() + element.querySelector('.unit').outerHTML;

                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }

            requestAnimationFrame(update);
        }

        // 初始化数字动画
        setTimeout(() => {
            animateNumber('totalAmount', <?php echo $today_stats['total_amount']; ?>);
            animateNumber('totalOrders', <?php echo $today_stats['total_orders']; ?>);
            animateNumber('totalUnits', <?php echo $today_stats['total_units']; ?>);
            animateNumber('avgOrder', <?php echo $today_stats['avg_order']; ?>);
        }, 500);

        // 品牌饼图
        const brandChart = echarts.init(document.getElementById('brandChart'));
        brandChart.setOption({
            tooltip: {
                trigger: 'item',
                backgroundColor: 'rgba(0,0,0,0.8)',
                borderColor: '#D32F2F',
                textStyle: { color: '#fff' }
            },
            series: [{
                type: 'pie',
                radius: ['40%', '70%'],
                center: ['50%', '50%'],
                avoidLabelOverlap: false,
                itemStyle: {
                    borderRadius: 8,
                    borderColor: '#0a0a1a',
                    borderWidth: 2
                },
                label: {
                    show: true,
                    color: '#fff',
                    fontSize: 12
                },
                labelLine: {
                    lineStyle: { color: 'rgba(255,255,255,0.3)' }
                },
                data: <?php echo json_encode($brand_focus); ?>,
                color: ['#D32F2F', '#FF6B6B', '#FF9800', '#4CAF50', '#2196F3', '#9C27B0']
            }]
        });

        // 趋势图
        const trendChart = echarts.init(document.getElementById('trendChart'));
        const hours = Array.from({length: 24}, (_, i) => `${i}:00`);
        const trendData = [12, 8, 5, 3, 2, 4, 15, 35, 68, 89, 76, 95, 112, 98, 85, 92, 78, 65, 72, 58, 45, 38, 28, 18];

        trendChart.setOption({
            tooltip: {
                trigger: 'axis',
                backgroundColor: 'rgba(0,0,0,0.8)',
                borderColor: '#D32F2F'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: hours,
                axisLine: { lineStyle: { color: 'rgba(255,255,255,0.1)' } },
                axisLabel: { color: 'rgba(255,255,255,0.5)', fontSize: 10 }
            },
            yAxis: {
                type: 'value',
                axisLine: { show: false },
                splitLine: { lineStyle: { color: 'rgba(255,255,255,0.05)' } },
                axisLabel: { color: 'rgba(255,255,255,0.5)' }
            },
            series: [{
                data: trendData,
                type: 'line',
                smooth: true,
                symbol: 'none',
                lineStyle: {
                    color: '#D32F2F',
                    width: 3,
                    shadowColor: 'rgba(211, 47, 47, 0.5)',
                    shadowBlur: 10
                },
                areaStyle: {
                    color: {
                        type: 'linear',
                        x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                            { offset: 0, color: 'rgba(211, 47, 47, 0.4)' },
                            { offset: 1, color: 'rgba(211, 47, 47, 0)' }
                        ]
                    }
                }
            }]
        });

        // 响应式
        window.addEventListener('resize', () => {
            brandChart.resize();
            trendChart.resize();
        });
    </script>
</body>
</html>
