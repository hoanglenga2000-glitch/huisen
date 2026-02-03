<?php
/**
 * ==========================================
 * 汇森科技 - 进货单/购物车 v2.0
 * B2B 批量采购清单风格
 * ==========================================
 * Stage 8 升级：
 * 1. 全选checkbox + 商品列表
 * 2. 数量步进器
 * 3. 底部悬浮结算条
 * 4. 空状态优化
 */

require_once '../config/config.php';
session_start();

// 设置 Base Path
$base_path = '../';

// 初始化询价单
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 处理操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'remove' && isset($_POST['sku_id'])) {
        $sku_id = intval($_POST['sku_id']);
        $_SESSION['cart'] = array_filter($_SESSION['cart'], fn($item) => $item['sku_id'] !== $sku_id);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
    }

    if ($action === 'update_qty' && isset($_POST['sku_id'], $_POST['qty'])) {
        $sku_id = intval($_POST['sku_id']);
        $qty = max(1, intval($_POST['qty']));
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['sku_id'] === $sku_id) {
                $item['qty'] = $qty;
                break;
            }
        }
    }

    header('Location: cart.php');
    exit;
}

// 获取询价单商品详情
$cart_items = [];
$total_amount = 0;
$total_qty = 0;

if (!empty($_SESSION['cart'])) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sku_ids = array_column($_SESSION['cart'], 'sku_id');
    $placeholders = implode(',', array_fill(0, count($sku_ids), '?'));

    // 检查使用哪个表
    $use_v4 = $conn->query("SHOW TABLES LIKE 'products_sku_v4'")->rowCount() > 0;
    $sku_table = $use_v4 ? 'products_sku_v4' : 'products_sku_v3';
    $spu_table = $use_v4 ? 'products_spu_v4' : 'products_spu_v3';

    $stmt = $conn->prepare("
        SELECT s.*, p.model_name, p.brand, p.image_url, p.category
        FROM $sku_table s
        JOIN $spu_table p ON s.spu_id = p.id
        WHERE s.id IN ($placeholders)
    ");
    $stmt->execute($sku_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 合并数量信息
    $qty_map = [];
    foreach ($_SESSION['cart'] as $item) {
        $qty_map[$item['sku_id']] = $item['qty'] ?? 1;
    }

    foreach ($products as $p) {
        $qty = $qty_map[$p['id']] ?? 1;
        $p['qty'] = $qty;
        $p['subtotal'] = $p['price'] * $qty;
        $total_amount += $p['subtotal'];
        $total_qty += $qty;
        $cart_items[] = $p;
    }
}

$item_count = count($cart_items);
$cart_empty = $item_count === 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的进货单 - 汇森科技</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../dist/css/output.css">
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f5; }

        /* Checkbox 样式 */
        .cart-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .cart-checkbox:checked {
            background: #e1251b;
            border-color: #e1251b;
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
        }
        .cart-checkbox:hover {
            border-color: #e1251b;
        }

        /* 数量步进器 */
        .qty-stepper {
            display: flex;
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .qty-btn {
            width: 36px;
            height: 36px;
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 18px;
            color: #6b7280;
            border: none;
        }
        .qty-btn:hover {
            background: #f3f4f6;
            color: #e1251b;
        }
        .qty-btn:active {
            background: #e5e7eb;
        }
        .qty-input {
            width: 60px;
            height: 36px;
            border: none;
            border-left: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
        }
        .qty-input:focus {
            outline: none;
        }

        /* 商品行 */
        .cart-item {
            transition: all 0.2s;
        }
        .cart-item:hover {
            background: #fafafa;
        }

        /* 底部结算条 */
        .bottom-bar {
            position: sticky;
            bottom: 0;
            z-index: 50;
            background: white;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* 空状态 */
        .empty-state {
            background: linear-gradient(135deg, #fafafa 0%, #f0f0f0 100%);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <?php include '../includes/header.php'; ?>

    <main class="flex-1 max-w-screen-xl mx-auto px-4 py-8 w-full">
        <!-- 页面标题 -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">我的进货单</h1>
                <p class="text-gray-500 text-sm mt-1">共 <?php echo $item_count; ?> 种商品，<?php echo $total_qty; ?> 台</p>
            </div>
            <?php if (!$cart_empty): ?>
            <form method="POST" onsubmit="return confirm('确定要清空进货单吗？');">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="text-gray-400 hover:text-red-500 text-sm transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    清空进货单
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (!$cart_empty): ?>
        <!-- ==========================================
             PC Table View (仅电脑端显示)
             ========================================== -->
        <div class="hidden md:block bg-white rounded-xl shadow-sm overflow-hidden mb-24">
            <!-- Header Row 表头 -->
            <div class="bg-gray-50 px-6 py-4 border-b grid grid-cols-12 gap-4 items-center text-sm font-semibold text-gray-600">
                <div class="col-span-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="cart-checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                        <span>全选</span>
                    </label>
                </div>
                <div class="col-span-5">商品信息</div>
                <div class="col-span-2 text-center">单价</div>
                <div class="col-span-2 text-center">数量</div>
                <div class="col-span-1 text-right">小计</div>
                <div class="col-span-1 text-center">操作</div>
            </div>

            <!-- Cart Items 商品列表 -->
            <div class="divide-y">
                <?php foreach ($cart_items as $index => $item): ?>
                <div class="cart-item px-6 py-5 grid grid-cols-12 gap-4 items-center" data-sku-id="<?php echo $item['id']; ?>">
                    <!-- Checkbox -->
                    <div class="col-span-1">
                        <input type="checkbox" class="cart-checkbox item-checkbox" checked data-price="<?php echo $item['subtotal']; ?>" data-qty="<?php echo $item['qty']; ?>" onchange="updateTotal()">
                    </div>

                    <!-- 商品信息 -->
                    <div class="col-span-5 flex items-center gap-4">
                        <!-- 缩略图 -->
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <?php if (!empty($item['image_url']) && file_exists('../' . $item['image_url'])): ?>
                                <img src="../<?php echo htmlspecialchars($item['image_url']); ?>"
                                     alt="<?php echo htmlspecialchars($item['model_name']); ?>"
                                     class="max-w-full max-h-full object-contain">
                            <?php else: ?>
                                <span class="text-3xl text-gray-300">📱</span>
                            <?php endif; ?>
                        </div>

                        <!-- 标题 + SKU标签 -->
                        <div class="min-w-0">
                            <div class="text-xs text-gray-400 mb-1"><?php echo htmlspecialchars($item['brand']); ?></div>
                            <h3 class="font-medium text-gray-900 truncate"><?php echo htmlspecialchars($item['full_name']); ?></h3>
                            <div class="flex gap-2 mt-2">
                                <?php if (!empty($item['color']) && $item['color'] !== '标准'): ?>
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">
                                    <?php echo htmlspecialchars($item['color']); ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($item['storage']) && $item['storage'] !== '标准'): ?>
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-600 text-xs rounded">
                                    <?php echo htmlspecialchars($item['storage']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- 单价 -->
                    <div class="col-span-2 text-center">
                        <span class="text-primary font-bold text-lg">¥<?php echo number_format($item['price'], 0); ?></span>
                    </div>

                    <!-- 数量步进器 -->
                    <div class="col-span-2 flex justify-center">
                        <div class="qty-stepper">
                            <button type="button" class="qty-btn" onclick="updateQty(<?php echo $item['id']; ?>, -1)">−</button>
                            <input type="number" value="<?php echo $item['qty']; ?>" min="1"
                                   class="qty-input"
                                   id="qty-<?php echo $item['id']; ?>"
                                   data-price="<?php echo $item['price']; ?>"
                                   onchange="setQty(<?php echo $item['id']; ?>, this.value)">
                            <button type="button" class="qty-btn" onclick="updateQty(<?php echo $item['id']; ?>, 1)">+</button>
                        </div>
                    </div>

                    <!-- 小计 -->
                    <div class="col-span-1 text-right">
                        <span class="text-primary font-bold text-lg" id="subtotal-<?php echo $item['id']; ?>">
                            ¥<?php echo number_format($item['subtotal'], 0); ?>
                        </span>
                    </div>

                    <!-- 删除 -->
                    <div class="col-span-1 text-center">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="sku_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="text-gray-400 hover:text-red-500 transition text-sm">
                                删除
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ==========================================
             Mobile Card View (仅手机端显示)
             pb-28 防止被底部结算条遮挡 (14 * 4px = 56px + 安全间距)
             ========================================== -->
        <div class="md:hidden space-y-3 pb-28">
            <?php foreach ($cart_items as $index => $item): ?>
            <div class="bg-white p-3 rounded-lg shadow-sm" data-sku-id="<?php echo $item['id']; ?>">
                <div class="flex gap-3">
                    <!-- 左侧: Checkbox -->
                    <div class="flex items-center">
                        <input type="checkbox" class="cart-checkbox item-checkbox" checked
                               data-price="<?php echo $item['subtotal']; ?>"
                               data-qty="<?php echo $item['qty']; ?>"
                               onchange="updateTotal()">
                    </div>

                    <!-- 中间: 商品图片 -->
                    <div class="w-20 h-20 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <?php if (!empty($item['image_url']) && file_exists('../' . $item['image_url'])): ?>
                            <img src="../<?php echo htmlspecialchars($item['image_url']); ?>"
                                 alt="<?php echo htmlspecialchars($item['model_name']); ?>"
                                 class="max-w-full max-h-full object-contain">
                        <?php else: ?>
                            <span class="text-3xl text-gray-300">📱</span>
                        <?php endif; ?>
                    </div>

                    <!-- 右侧: 商品信息 -->
                    <div class="flex-1 min-w-0 flex flex-col justify-between">
                        <!-- 上部: 标题 + 删除按钮 -->
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <h3 class="text-sm font-medium text-gray-900 line-clamp-2 leading-tight">
                                    <?php echo htmlspecialchars($item['full_name']); ?>
                                </h3>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <?php if (!empty($item['color']) && $item['color'] !== '标准'): ?>
                                    <span class="px-1.5 py-0.5 bg-gray-100 text-gray-500 text-[10px] rounded">
                                        <?php echo htmlspecialchars($item['color']); ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['storage']) && $item['storage'] !== '标准'): ?>
                                    <span class="px-1.5 py-0.5 bg-blue-50 text-blue-500 text-[10px] rounded">
                                        <?php echo htmlspecialchars($item['storage']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- 删除按钮 -->
                            <form method="POST" class="flex-shrink-0">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="sku_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="text-gray-300 hover:text-red-500 transition p-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>

                        <!-- 下部: 价格 + 数量步进器 -->
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-base font-bold text-primary">¥<?php echo number_format($item['price'], 0); ?></span>

                            <!-- 迷你数量步进器 -->
                            <div class="flex items-center border border-gray-200 rounded">
                                <button type="button"
                                        class="w-7 h-7 flex items-center justify-center text-gray-500 hover:text-primary"
                                        onclick="updateQty(<?php echo $item['id']; ?>, -1)">−</button>
                                <input type="number" value="<?php echo $item['qty']; ?>" min="1"
                                       class="w-10 h-7 text-center text-sm font-medium border-x border-gray-200 bg-transparent"
                                       id="qty-mobile-<?php echo $item['id']; ?>"
                                       data-price="<?php echo $item['price']; ?>"
                                       onchange="setQty(<?php echo $item['id']; ?>, this.value)">
                                <button type="button"
                                        class="w-7 h-7 flex items-center justify-center text-gray-500 hover:text-primary"
                                        onclick="updateQty(<?php echo $item['id']; ?>, 1)">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ==========================================
             PC 底部结算条 (静态布局，仅电脑显示)
             ========================================== -->
        <div class="hidden md:block bg-white rounded-xl shadow-sm p-6 mt-6">
            <div class="flex items-center justify-between">
                <!-- Left: 选中信息 -->
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="cart-checkbox" id="selectAllBottom" onchange="toggleSelectAll(this)" checked>
                        <span class="text-gray-600">全选</span>
                    </label>
                    <span class="text-gray-500">
                        已选 <span class="text-primary font-bold" id="selectedCount"><?php echo $item_count; ?></span> 种商品，
                        共 <span class="text-primary font-bold" id="selectedQty"><?php echo $total_qty; ?></span> 台
                    </span>
                </div>

                <!-- Right: 金额 + 按钮 -->
                <div class="flex items-center gap-8">
                    <div class="text-right">
                        <span class="text-gray-500">合计(不含运费):</span>
                        <span class="text-3xl font-bold text-primary ml-2" id="totalAmount">¥<?php echo number_format($total_amount, 0); ?></span>
                    </div>
                    <button onclick="submitOrder()"
                            class="px-10 py-4 bg-primary text-white text-lg font-bold rounded-lg hover:bg-red-600 transition shadow-lg shadow-red-200">
                        提交订单
                    </button>
                </div>
            </div>
        </div>

        <!-- ==========================================
             Mobile 底部结算条 (固定悬浮，仅手机显示)
             bottom-[56px] 明确避开底部导航栏 (14 * 4px = 56px)
             ========================================== -->
        <div class="md:hidden fixed bottom-[56px] left-0 w-full bg-white border-t border-gray-200 z-40 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]"
             style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="h-14 flex items-center justify-between px-4">
                <!-- Left: 全选 + 合计 -->
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <label class="flex items-center gap-1.5 cursor-pointer flex-shrink-0">
                        <input type="checkbox" class="cart-checkbox w-5 h-5" id="selectAllMobile" onchange="toggleSelectAll(this)" checked>
                        <span class="text-sm text-gray-600">全选</span>
                    </label>
                    <div class="text-sm min-w-0">
                        <span class="text-gray-500">合计:</span>
                        <span class="text-lg font-bold text-primary ml-1" id="totalAmountMobile">¥<?php echo number_format($total_amount, 0); ?></span>
                    </div>
                </div>

                <!-- Right: 提交按钮 -->
                <button onclick="submitOrder()"
                        class="px-6 h-10 bg-primary text-white font-bold rounded-full active:bg-red-600 transition flex-shrink-0 ml-2">
                    提交订单
                </button>
            </div>
        </div>

        <?php else: ?>
        <!-- Empty State 空状态 -->
        <div class="empty-state rounded-2xl p-20 text-center min-h-[50vh] flex flex-col items-center justify-center">
            <div class="w-32 h-32 bg-gray-200 rounded-full flex items-center justify-center mb-6">
                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-700 mb-3">进货单还是空的</h2>
            <p class="text-gray-500 mb-8">快去挑选心仪的商品吧！海量手机，源头直供</p>
            <a href="quotes_v6.php"
               class="inline-flex items-center gap-2 px-8 py-4 bg-primary text-white font-bold rounded-lg hover:bg-red-600 transition shadow-lg shadow-red-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                去选购商品
            </a>
        </div>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
    // 全选/取消全选
    function toggleSelectAll(checkbox) {
        const isChecked = checkbox.checked;
        document.querySelectorAll('.item-checkbox').forEach(cb => {
            cb.checked = isChecked;
        });
        // 同步两个全选按钮
        document.getElementById('selectAll')?.checked !== undefined && (document.getElementById('selectAll').checked = isChecked);
        document.getElementById('selectAllBottom')?.checked !== undefined && (document.getElementById('selectAllBottom').checked = isChecked);
        updateTotal();
    }

    // 更新合计
    function updateTotal() {
        let totalPrice = 0;
        let totalQty = 0;
        let selectedCount = 0;

        document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
            // 重新计算小计（因为数量可能已更新）
            const skuId = cb.closest('[data-sku-id]')?.dataset.skuId;
            if (skuId) {
                const qtyInput = document.getElementById('qty-' + skuId) || document.getElementById('qty-mobile-' + skuId);
                const price = parseFloat(cb.dataset.price) / (parseInt(cb.dataset.qty) || 1); // 单价
                const qty = parseInt(qtyInput?.value || cb.dataset.qty) || 1;
                totalPrice += price * qty;
                totalQty += qty;
            } else {
                totalPrice += parseFloat(cb.dataset.price) || 0;
                totalQty += parseInt(cb.dataset.qty) || 0;
            }
            selectedCount++;
        });

        // 更新PC端显示
        const totalAmountEl = document.getElementById('totalAmount');
        if (totalAmountEl) totalAmountEl.textContent = '¥' + Math.round(totalPrice).toLocaleString();
        
        const selectedCountEl = document.getElementById('selectedCount');
        if (selectedCountEl) selectedCountEl.textContent = selectedCount;
        
        const selectedQtyEl = document.getElementById('selectedQty');
        if (selectedQtyEl) selectedQtyEl.textContent = totalQty;

        // 更新移动端显示
        const totalAmountMobileEl = document.getElementById('totalAmountMobile');
        if (totalAmountMobileEl) totalAmountMobileEl.textContent = '¥' + Math.round(totalPrice).toLocaleString();

        // 检查是否全选
        const allCheckboxes = document.querySelectorAll('.item-checkbox');
        const checkedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
        const allChecked = allCheckboxes.length > 0 && allCheckboxes.length === checkedCheckboxes.length;

        if (document.getElementById('selectAll')) document.getElementById('selectAll').checked = allChecked;
        if (document.getElementById('selectAllBottom')) document.getElementById('selectAllBottom').checked = allChecked;
        if (document.getElementById('selectAllMobile')) document.getElementById('selectAllMobile').checked = allChecked;
    }

    // 数量调整
    function updateQty(skuId, delta) {
        const input = document.getElementById('qty-' + skuId);
        const newQty = Math.max(1, parseInt(input.value) + delta);
        input.value = newQty;
        setQty(skuId, newQty);
    }

    function setQty(skuId, qty) {
        qty = Math.max(1, parseInt(qty) || 1);

        // 更新小计显示（不刷新页面）
        const itemRow = document.querySelector(`[data-sku-id="${skuId}"]`);
        if (itemRow) {
            const priceInput = document.getElementById('qty-' + skuId) || document.getElementById('qty-mobile-' + skuId);
            const price = parseFloat(priceInput?.dataset.price || 0);
            const subtotal = price * qty;
            
            const subtotalEl = document.getElementById('subtotal-' + skuId);
            if (subtotalEl) {
                subtotalEl.textContent = '¥' + Math.round(subtotal).toLocaleString();
            }
            
            // 更新checkbox的data属性
            const checkbox = itemRow.querySelector('.item-checkbox');
            if (checkbox) {
                checkbox.dataset.qty = qty;
                checkbox.dataset.price = subtotal;
            }
            
            // 更新合计
            updateTotal();
        }

        // 提交到服务器
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_qty">
            <input type="hidden" name="sku_id" value="${skuId}">
            <input type="hidden" name="qty" value="${qty}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    // 提交订单
    function submitOrder() {
        const selectedItems = document.querySelectorAll('.item-checkbox:checked');
        if (selectedItems.length === 0) {
            alert('请至少选择一件商品');
            return;
        }

        const totalAmount = document.getElementById('totalAmount').textContent;
        const totalQty = document.getElementById('selectedQty').textContent;

        const msg = `询价单提交成功！\n\n已选 ${selectedItems.length} 种商品，共 ${totalQty} 台\n预估金额: ${totalAmount}\n\n我们的客服将在1小时内与您联系确认订单。\n\n客服微信: huisen_tech\n客服电话: 400-XXX-XXXX`;
        alert(msg);
    }

    // 初始化
    document.addEventListener('DOMContentLoaded', function() {
        updateTotal();
    });
    </script>
</body>
</html>
