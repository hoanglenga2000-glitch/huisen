<?php
/**
 * ==========================================
 * 汇森科技 - 后台批量调价系统
 * ==========================================
 *
 * 功能：
 * 1. 品牌筛选器
 * 2. 加价比例/固定金额批量操作
 * 3. 预览确认弹窗
 * 4. 批量更新 wholesale_price
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

$db = Database::getInstance();
$conn = $db->getConnection();

// 处理批量调价请求
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'batch_update') {
        $brand = $_POST['brand'] ?? '';
        $adjust_type = $_POST['adjust_type'] ?? 'percentage'; // percentage or fixed
        $adjust_value = floatval($_POST['adjust_value'] ?? 0);

        if (empty($brand) || $adjust_value == 0) {
            $message = '请选择品牌并输入调价值';
            $message_type = 'error';
        } else {
            try {
                // 检查使用哪个表
                $use_v4 = $conn->query("SHOW TABLES LIKE 'products_sku_v4'")->rowCount() > 0;
                $sku_table = $use_v4 ? 'products_sku_v4' : 'products_sku_v3';
                $spu_table = $use_v4 ? 'products_spu_v4' : 'products_spu_v3';

                // 构建更新SQL
                if ($adjust_type === 'percentage') {
                    // 百分比调整: NewPrice = Price * (1 + Percentage/100)
                    $sql = "UPDATE $sku_table SET price = price * (1 + ? / 100) WHERE spu_id IN (SELECT id FROM $spu_table WHERE brand = ?)";
                } else {
                    // 固定金额调整: NewPrice = Price + FixedAmount
                    $sql = "UPDATE $sku_table SET price = price + ? WHERE spu_id IN (SELECT id FROM $spu_table WHERE brand = ?)";
                }

                $stmt = $conn->prepare($sql);
                $stmt->execute([$adjust_value, $brand]);
                $affected = $stmt->rowCount();

                // 同步更新SPU表的min_price和max_price
                $sync_sql = "UPDATE $spu_table s SET
                    min_price = (SELECT MIN(price) FROM $sku_table WHERE spu_id = s.id),
                    max_price = (SELECT MAX(price) FROM $sku_table WHERE spu_id = s.id)
                    WHERE brand = ?";
                $conn->prepare($sync_sql)->execute([$brand]);

                $message = "成功更新 {$affected} 款 {$brand} 产品的价格";
                $message_type = 'success';

                // 记录操作日志
                $log_sql = "INSERT INTO price_change_logs (operator, brand, adjust_type, adjust_value, affected_count, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                try {
                    $conn->prepare($log_sql)->execute([$_SESSION['username'], $brand, $adjust_type, $adjust_value, $affected]);
                } catch (Exception $e) {
                    // 日志表可能不存在，忽略错误
                }

            } catch (Exception $e) {
                $message = '更新失败：' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// 获取品牌列表
$use_v4 = $conn->query("SHOW TABLES LIKE 'products_spu_v4'")->rowCount() > 0;
$spu_table = $use_v4 ? 'products_spu_v4' : 'products_spu_v3';

$brands_stmt = $conn->query("SELECT brand, COUNT(*) as count FROM $spu_table WHERE min_price > 0 GROUP BY brand ORDER BY count DESC");
$brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取预览数据
$preview_brand = $_GET['preview'] ?? '';
$preview_products = [];
if ($preview_brand) {
    $sku_table = $use_v4 ? 'products_sku_v4' : 'products_sku_v3';
    $preview_stmt = $conn->prepare("
        SELECT s.model_name, s.brand, k.color, k.storage, k.price, k.official_price
        FROM $sku_table k
        JOIN $spu_table s ON k.spu_id = s.id
        WHERE s.brand = ?
        ORDER BY k.price DESC
        LIMIT 20
    ");
    $preview_stmt->execute([$preview_brand]);
    $preview_products = $preview_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$base_path = '../';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>批量调价管理 - 汇森科技后台</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        :root { --brand-red: #D32F2F; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #F5F5F5; }

        .brand-card {
            background: white;
            border: 2px solid #EEEEEE;
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .brand-card:hover {
            border-color: var(--brand-red);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .brand-card.selected {
            border-color: var(--brand-red);
            background: #FFEBEE;
        }

        .adjust-input {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            border: 2px solid #E0E0E0;
            border-radius: 12px;
            padding: 16px;
            width: 200px;
        }
        .adjust-input:focus {
            outline: none;
            border-color: var(--brand-red);
        }
    </style>
</head>
<body>
    <!-- 后台导航 -->
    <header class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="index.php" class="font-bold text-lg">汇森科技 · 后台管理</a>
                <span class="text-gray-500">|</span>
                <span class="text-gray-400">批量调价</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-400">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="../logout.php" class="text-sm text-gray-400 hover:text-white">退出</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <!-- 页面标题 -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">📊 批量报价管理</h1>
            <p class="text-gray-500">批量调整产品价格，支持按比例或固定金额调整</p>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- 左侧：品牌选择 -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl p-6 sticky top-20">
                    <h2 class="font-bold text-lg mb-4">选择品牌</h2>
                    <div class="space-y-3">
                        <?php foreach ($brands as $b): ?>
                        <div class="brand-card <?php echo $preview_brand === $b['brand'] ? 'selected' : ''; ?>"
                             onclick="selectBrand('<?php echo htmlspecialchars($b['brand']); ?>')">
                            <div class="flex items-center justify-between">
                                <span class="font-medium"><?php echo htmlspecialchars($b['brand']); ?></span>
                                <span class="text-sm text-gray-500"><?php echo $b['count']; ?> 款</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 右侧：调价操作 -->
            <div class="lg:col-span-2">
                <form method="POST" id="priceForm" onsubmit="return confirmUpdate()">
                    <input type="hidden" name="action" value="batch_update">
                    <input type="hidden" name="brand" id="selectedBrand" value="<?php echo htmlspecialchars($preview_brand); ?>">

                    <!-- 调价方式 -->
                    <div class="bg-white rounded-xl p-6 mb-6">
                        <h2 class="font-bold text-lg mb-6">调价设置</h2>

                        <div class="grid md:grid-cols-2 gap-6 mb-6">
                            <!-- 调价类型 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">调价方式</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="adjust_type" value="percentage" checked
                                               class="w-4 h-4" style="accent-color: var(--brand-red);">
                                        <span>按比例 (%)</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="adjust_type" value="fixed"
                                               class="w-4 h-4" style="accent-color: var(--brand-red);">
                                        <span>固定金额 (元)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 调价值 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">调价值</label>
                                <div class="flex items-center gap-4">
                                    <input type="number" name="adjust_value" step="0.01"
                                           class="adjust-input" placeholder="0" id="adjustValue">
                                    <span class="text-gray-500" id="adjustUnit">%</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-2">正数为加价，负数为降价</p>
                            </div>
                        </div>

                        <!-- 公式说明 -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <div class="text-sm font-medium text-gray-700 mb-2">📐 计算公式</div>
                            <code class="text-sm text-gray-600" id="formulaDisplay">
                                新价格 = 原价 × (1 + 加价比例%)
                            </code>
                        </div>

                        <!-- 操作按钮 -->
                        <div class="flex gap-4">
                            <button type="button" onclick="previewChanges()"
                                    class="flex-1 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition">
                                👁 预览变化
                            </button>
                            <button type="submit"
                                    class="flex-1 py-3 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition"
                                    id="submitBtn" disabled>
                                ⚡ 确认更新
                            </button>
                        </div>
                    </div>
                </form>

                <!-- 产品预览列表 -->
                <?php if (!empty($preview_products)): ?>
                <div class="bg-white rounded-xl p-6">
                    <h2 class="font-bold text-lg mb-4">
                        <?php echo htmlspecialchars($preview_brand); ?> 产品预览
                        <span class="text-sm font-normal text-gray-500">（显示前20款）</span>
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">型号</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">配置</th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">当前价格</th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">官网价</th>
                                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">调整后</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach ($preview_products as $p): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($p['model_name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo htmlspecialchars(($p['color'] ?? '') . ' ' . ($p['storage'] ?? '')); ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono current-price" data-price="<?php echo $p['price']; ?>">
                                        ¥<?php echo number_format($p['price'], 0); ?>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-400 font-mono">
                                        ¥<?php echo number_format($p['official_price'] ?? 0, 0); ?>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-red-600 font-mono new-price">
                                        -
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php elseif (empty($preview_brand)): ?>
                <div class="bg-white rounded-xl p-12 text-center">
                    <div class="text-5xl mb-4">👈</div>
                    <p class="text-gray-500">请先选择一个品牌</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
    function selectBrand(brand) {
        document.getElementById('selectedBrand').value = brand;
        window.location.href = '?preview=' + encodeURIComponent(brand);
    }

    // 切换调价类型
    document.querySelectorAll('input[name="adjust_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const unit = document.getElementById('adjustUnit');
            const formula = document.getElementById('formulaDisplay');
            if (this.value === 'percentage') {
                unit.textContent = '%';
                formula.textContent = '新价格 = 原价 × (1 + 加价比例%)';
            } else {
                unit.textContent = '元';
                formula.textContent = '新价格 = 原价 + 固定金额';
            }
            updatePreview();
        });
    });

    // 输入调价值时更新预览
    document.getElementById('adjustValue').addEventListener('input', updatePreview);

    function updatePreview() {
        const type = document.querySelector('input[name="adjust_type"]:checked').value;
        const value = parseFloat(document.getElementById('adjustValue').value) || 0;
        const btn = document.getElementById('submitBtn');

        btn.disabled = value === 0 || !document.getElementById('selectedBrand').value;

        document.querySelectorAll('.current-price').forEach((cell, index) => {
            const currentPrice = parseFloat(cell.dataset.price);
            let newPrice;

            if (type === 'percentage') {
                newPrice = currentPrice * (1 + value / 100);
            } else {
                newPrice = currentPrice + value;
            }

            const newPriceCell = document.querySelectorAll('.new-price')[index];
            newPriceCell.textContent = '¥' + Math.round(newPrice).toLocaleString();

            // 颜色标记
            if (newPrice > currentPrice) {
                newPriceCell.classList.remove('text-green-600');
                newPriceCell.classList.add('text-red-600');
            } else if (newPrice < currentPrice) {
                newPriceCell.classList.remove('text-red-600');
                newPriceCell.classList.add('text-green-600');
            }
        });
    }

    function previewChanges() {
        updatePreview();
    }

    function confirmUpdate() {
        const brand = document.getElementById('selectedBrand').value;
        const value = document.getElementById('adjustValue').value;
        const type = document.querySelector('input[name="adjust_type"]:checked').value;
        const typeText = type === 'percentage' ? '%' : '元';

        if (!brand) {
            alert('请先选择一个品牌');
            return false;
        }

        if (!value || parseFloat(value) === 0) {
            alert('请输入调价值');
            return false;
        }

        const productCount = document.querySelectorAll('.current-price').length;
        const message = `确认要更新 ${brand} 品牌的价格吗？\n\n` +
                       `调价方式：${type === 'percentage' ? '按比例' : '固定金额'}\n` +
                       `调价值：${value}${typeText}\n` +
                       `影响产品：约 ${productCount}+ 款\n\n` +
                       `此操作不可撤销，请确认！`;

        return confirm(message);
    }
    </script>
</body>
</html>
