<?php
/**
 * ==========================================
 * 汇森科技 - 专业结算收银台 v1.0
 * JD/Tmall Style Checkout Page
 * ==========================================
 * 功能：
 * 1. 收货地址选择
 * 2. 商品清单展示
 * 3. 支付方式选择 (UI Only)
 * 4. 订单提交
 */

session_start();
require_once '../config/config.php';

$base_path = '../';

// 检查登录状态
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? '游客';
$user_balance = $_SESSION['balance'] ?? 28650.00;

// 模拟收货地址数据
$addresses = [
    [
        'id' => 1,
        'name' => '张三',
        'phone' => '138****8888',
        'province' => '甘肃省',
        'city' => '兰州市',
        'district' => '城关区',
        'detail' => '东岗西路999号汇森科技大厦18楼',
        'is_default' => true,
    ],
    [
        'id' => 2,
        'name' => '李四',
        'phone' => '139****6666',
        'province' => '甘肃省',
        'city' => '兰州市',
        'district' => '七里河区',
        'detail' => '西站十字建兰商务中心5楼',
        'is_default' => false,
    ],
];

// 获取订单商品 - 优先从Session购物车，否则Mock数据
$order_items = [];
$db = Database::getInstance();
$conn = $db->getConnection();

// 检查使用哪个表
$use_v4 = $conn->query("SHOW TABLES LIKE 'products_sku_v4'")->rowCount() > 0;
$sku_table = $use_v4 ? 'products_sku_v4' : 'products_sku_v3';
$spu_table = $use_v4 ? 'products_spu_v4' : 'products_spu_v3';

if (!empty($_SESSION['cart'])) {
    // 从购物车获取
    $sku_ids = array_column($_SESSION['cart'], 'sku_id');
    if (!empty($sku_ids)) {
        $placeholders = implode(',', array_fill(0, count($sku_ids), '?'));
        $stmt = $conn->prepare("
            SELECT s.*, p.model_name, p.brand, p.image_url
            FROM $sku_table s
            JOIN $spu_table p ON s.spu_id = p.id
            WHERE s.id IN ($placeholders)
        ");
        $stmt->execute($sku_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $qty_map = [];
        foreach ($_SESSION['cart'] as $item) {
            $qty_map[$item['sku_id']] = $item['qty'] ?? 1;
        }

        foreach ($products as $p) {
            $qty = $qty_map[$p['id']] ?? 1;
            $order_items[] = [
                'id' => $p['id'],
                'name' => $p['full_name'],
                'brand' => $p['brand'],
                'image' => $p['image_url'] ?? '',
                'color' => $p['color'] ?? '',
                'storage' => $p['storage'] ?? '',
                'price' => floatval($p['price']),
                'qty' => $qty,
                'subtotal' => floatval($p['price']) * $qty,
            ];
        }
    }
}

// 如果购物车为空，Mock示例数据
if (empty($order_items)) {
    $order_items = [
        [
            'id' => 1001,
            'name' => 'iPhone 16 Pro Max 256GB 沙漠金',
            'brand' => '苹果',
            'image' => '',
            'color' => '沙漠金',
            'storage' => '256GB',
            'price' => 9299,
            'qty' => 1,
            'subtotal' => 9299,
        ],
        [
            'id' => 1002,
            'name' => '华为 Mate 70 Pro 512GB 雅丹黑',
            'brand' => '华为',
            'image' => '',
            'color' => '雅丹黑',
            'storage' => '512GB',
            'price' => 7999,
            'qty' => 2,
            'subtotal' => 15998,
        ],
    ];
}

// 计算总金额
$subtotal = array_sum(array_column($order_items, 'subtotal'));
$shipping_fee = 0; // 免运费
$discount = 0; // 暂无优惠
$total_amount = $subtotal + $shipping_fee - $discount;
$total_qty = array_sum(array_column($order_items, 'qty'));

// 默认选中地址
$selected_address = $addresses[0] ?? null;

// 支付方式配置
$payment_methods = [
    [
        'id' => 'wechat',
        'name' => '微信支付',
        'icon' => '💚',
        'color' => 'border-green-500 bg-green-50',
        'icon_bg' => 'bg-green-500',
    ],
    [
        'id' => 'alipay',
        'name' => '支付宝',
        'icon' => '💙',
        'color' => 'border-blue-500 bg-blue-50',
        'icon_bg' => 'bg-blue-500',
    ],
    [
        'id' => 'bank',
        'name' => '对公转账',
        'icon' => '🏦',
        'color' => 'border-gray-400 bg-gray-50',
        'icon_bg' => 'bg-gray-500',
    ],
    [
        'id' => 'balance',
        'name' => '余额支付',
        'icon' => '💰',
        'color' => 'border-amber-500 bg-amber-50',
        'icon_bg' => 'bg-amber-500',
        'extra' => '可用 ¥' . number_format($user_balance, 2),
    ],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>确认订单 - 汇森科技</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../dist/css/output.css">
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f5; }

        /* 地址卡片选中状态 */
        .address-card {
            transition: all 0.2s;
            cursor: pointer;
        }
        .address-card:hover {
            border-color: #fca5a5;
        }
        .address-card.selected {
            border-color: #e1251b;
            background: #fef2f2;
        }
        .address-card.selected::after {
            content: '✓';
            position: absolute;
            right: -1px;
            bottom: -1px;
            width: 24px;
            height: 24px;
            background: #e1251b;
            color: white;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px 0 0 0;
        }

        /* 支付方式卡片 */
        .payment-card {
            transition: all 0.2s;
            cursor: pointer;
            border: 2px solid #e5e7eb;
        }
        .payment-card:hover {
            border-color: #d1d5db;
        }
        .payment-card.selected {
            border-width: 2px;
        }
        .payment-card input:checked + .payment-content {
            /* 选中样式由JS控制 */
        }

        /* 结算条阴影 */
        .shadow-top {
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="min-h-screen pb-20 md:pb-0">

    <!-- ==========================================
         Mobile Header (仅手机显示)
         ========================================== -->
    <header class="md:hidden fixed top-0 left-0 w-full z-50 bg-white border-b border-gray-100"
            style="padding-top: env(safe-area-inset-top);">
        <div class="h-12 flex items-center px-4">
            <button onclick="history.back()" class="w-8 h-8 flex items-center justify-center -ml-2">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <h1 class="flex-1 text-center font-bold text-gray-900">确认订单</h1>
            <div class="w-8"></div>
        </div>
    </header>
    <div class="md:hidden h-12" style="padding-top: env(safe-area-inset-top);"></div>

    <!-- ==========================================
         PC Header (仅电脑显示)
         ========================================== -->
    <header class="hidden md:block bg-white border-b border-gray-200">
        <div class="max-w-screen-xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="index_v4.php" class="text-2xl font-bold text-primary">汇森科技</a>
                <span class="text-gray-300">|</span>
                <span class="text-lg text-gray-600">结算页</span>
            </div>
            <div class="text-sm text-gray-500">
                <?php if ($is_logged_in): ?>
                欢迎，<?php echo htmlspecialchars($username); ?>
                <?php else: ?>
                <a href="../login.php" class="text-primary hover:underline">请登录</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <form method="POST" action="order_success.php" id="checkoutForm">
        <input type="hidden" name="address_id" id="selectedAddressId" value="<?php echo $selected_address['id'] ?? ''; ?>">
        <input type="hidden" name="payment_method" id="selectedPayment" value="wechat">
        <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">

        <main class="max-w-screen-xl mx-auto px-4 py-4 md:py-8">
            <div class="md:grid md:grid-cols-12 md:gap-6">

                <!-- ==========================================
                     左侧主内容区 (PC: 8列, Mobile: 全宽)
                     ========================================== -->
                <div class="md:col-span-8 space-y-4 md:space-y-6">

                    <!-- ==========================================
                         Section A: 收货地址
                         ========================================== -->
                    <section class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="p-4 md:p-6 border-b border-gray-100">
                            <h2 class="font-bold text-gray-900 flex items-center gap-2">
                                <span class="text-primary">📍</span>
                                收货地址
                            </h2>
                        </div>

                        <?php if (empty($addresses)): ?>
                        <!-- 空状态 -->
                        <div class="p-6">
                            <a href="addresses.php?action=add"
                               class="block border-2 border-dashed border-gray-300 rounded-lg p-8 text-center
                                      hover:border-primary hover:bg-red-50 transition-colors">
                                <div class="text-4xl mb-2">➕</div>
                                <div class="text-gray-500">新建收货地址</div>
                            </a>
                        </div>
                        <?php else: ?>

                        <!-- Mobile: 单条地址显示 -->
                        <div class="md:hidden p-4">
                            <?php if ($selected_address): ?>
                            <a href="addresses.php?select=1" class="flex items-start gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-bold text-gray-900"><?php echo htmlspecialchars($selected_address['name']); ?></span>
                                        <span class="text-gray-500"><?php echo $selected_address['phone']; ?></span>
                                        <?php if ($selected_address['is_default']): ?>
                                        <span class="px-1.5 py-0.5 bg-primary text-white text-[10px] rounded">默认</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-gray-600 line-clamp-2">
                                        <?php echo htmlspecialchars($selected_address['province'] . $selected_address['city'] . $selected_address['district'] . $selected_address['detail']); ?>
                                    </p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                            <?php endif; ?>
                        </div>

                        <!-- PC: 地址卡片列表 -->
                        <div class="hidden md:block p-6">
                            <div class="grid grid-cols-2 gap-4">
                                <?php foreach ($addresses as $addr): ?>
                                <div class="address-card relative border-2 rounded-lg p-4 <?php echo $addr['is_default'] ? 'selected' : 'border-gray-200'; ?>"
                                     data-id="<?php echo $addr['id']; ?>"
                                     onclick="selectAddress(this, <?php echo $addr['id']; ?>)">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="font-bold text-gray-900"><?php echo htmlspecialchars($addr['name']); ?></span>
                                        <span class="text-gray-500 text-sm"><?php echo $addr['phone']; ?></span>
                                        <?php if ($addr['is_default']): ?>
                                        <span class="px-2 py-0.5 bg-primary text-white text-xs rounded">默认</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($addr['province'] . $addr['city'] . $addr['district']); ?><br>
                                        <?php echo htmlspecialchars($addr['detail']); ?>
                                    </p>
                                </div>
                                <?php endforeach; ?>

                                <!-- 新增地址按钮 -->
                                <a href="addresses.php?action=add"
                                   class="border-2 border-dashed border-gray-300 rounded-lg p-4 flex items-center justify-center
                                          hover:border-primary hover:bg-red-50 transition-colors min-h-[100px]">
                                    <div class="text-center">
                                        <div class="text-2xl mb-1">➕</div>
                                        <div class="text-sm text-gray-500">新建地址</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </section>

                    <!-- ==========================================
                         Section B: 商品清单
                         ========================================== -->
                    <section class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="p-4 md:p-6 border-b border-gray-100">
                            <h2 class="font-bold text-gray-900 flex items-center gap-2">
                                <span class="text-primary">📦</span>
                                商品清单
                                <span class="text-sm font-normal text-gray-400">(<?php echo $total_qty; ?>件)</span>
                            </h2>
                        </div>

                        <!-- PC: 表格视图 -->
                        <div class="hidden md:block">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">商品信息</th>
                                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-600">规格</th>
                                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-600">单价</th>
                                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-600">数量</th>
                                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600">小计</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                    <?php if (!empty($item['image']) && file_exists('../' . $item['image'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($item['image']); ?>"
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                         class="max-w-full max-h-full object-contain">
                                                    <?php else: ?>
                                                    <span class="text-2xl">📱</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="text-xs text-gray-400 mb-1"><?php echo htmlspecialchars($item['brand']); ?></div>
                                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex justify-center gap-1">
                                                <?php if (!empty($item['color']) && $item['color'] !== '标准'): ?>
                                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded"><?php echo htmlspecialchars($item['color']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['storage']) && $item['storage'] !== '标准'): ?>
                                                <span class="px-2 py-0.5 bg-blue-50 text-blue-600 text-xs rounded"><?php echo htmlspecialchars($item['storage']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center text-gray-700">
                                            ¥<?php echo number_format($item['price'], 0); ?>
                                        </td>
                                        <td class="px-6 py-4 text-center text-gray-700">
                                            x<?php echo $item['qty']; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="font-bold text-primary">¥<?php echo number_format($item['subtotal'], 0); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile: 卡片视图 -->
                        <div class="md:hidden divide-y">
                            <?php foreach ($order_items as $item): ?>
                            <div class="p-4 flex gap-3">
                                <div class="w-20 h-20 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <?php if (!empty($item['image']) && file_exists('../' . $item['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($item['image']); ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="max-w-full max-h-full object-contain">
                                    <?php else: ?>
                                    <span class="text-3xl">📱</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-medium text-gray-900 line-clamp-2 mb-1">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </h3>
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        <?php if (!empty($item['color']) && $item['color'] !== '标准'): ?>
                                        <span class="px-1.5 py-0.5 bg-gray-100 text-gray-500 text-[10px] rounded"><?php echo htmlspecialchars($item['color']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($item['storage']) && $item['storage'] !== '标准'): ?>
                                        <span class="px-1.5 py-0.5 bg-blue-50 text-blue-500 text-[10px] rounded"><?php echo htmlspecialchars($item['storage']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-primary">¥<?php echo number_format($item['price'], 0); ?></span>
                                        <span class="text-gray-400 text-sm">x<?php echo $item['qty']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- ==========================================
                         Section C: 支付方式
                         ========================================== -->
                    <section class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="p-4 md:p-6 border-b border-gray-100">
                            <h2 class="font-bold text-gray-900 flex items-center gap-2">
                                <span class="text-primary">💳</span>
                                支付方式
                            </h2>
                        </div>

                        <div class="p-4 md:p-6">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
                                <?php foreach ($payment_methods as $index => $method): ?>
                                <label class="payment-card rounded-lg p-3 md:p-4 <?php echo $index === 0 ? $method['color'] . ' selected' : ''; ?>"
                                       data-color="<?php echo $method['color']; ?>"
                                       onclick="selectPayment('<?php echo $method['id']; ?>', this)">
                                    <input type="radio" name="payment" value="<?php echo $method['id']; ?>"
                                           class="hidden" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                    <div class="payment-content flex flex-col items-center text-center">
                                        <div class="w-10 h-10 md:w-12 md:h-12 <?php echo $method['icon_bg']; ?> rounded-full
                                                    flex items-center justify-center text-white text-xl md:text-2xl mb-2">
                                            <?php echo $method['icon']; ?>
                                        </div>
                                        <div class="font-medium text-gray-900 text-sm"><?php echo $method['name']; ?></div>
                                        <?php if (!empty($method['extra'])): ?>
                                        <div class="text-[10px] text-gray-400 mt-0.5"><?php echo $method['extra']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>

                            <!-- 对公转账提示 -->
                            <div id="bankNotice" class="hidden mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <span class="text-amber-500">⚠️</span>
                                    <div class="text-sm">
                                        <p class="font-medium text-amber-800 mb-1">对公转账说明</p>
                                        <p class="text-amber-700">请在提交订单后24小时内完成转账，转账时请备注订单号。我们将在收到款项后发货。</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ==========================================
                         Section D: 买家留言 (可选)
                         ========================================== -->
                    <section class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="p-4 md:p-6">
                            <div class="flex items-center gap-4">
                                <span class="text-gray-600 text-sm flex-shrink-0">买家留言:</span>
                                <input type="text" name="buyer_note" placeholder="选填，请先和卖家协商一致"
                                       class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm
                                              focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                        </div>
                    </section>

                </div>

                <!-- ==========================================
                     右侧结算条 (仅PC显示)
                     ========================================== -->
                <div class="hidden md:block md:col-span-4">
                    <div class="bg-white rounded-lg shadow-sm p-6 sticky top-24">
                        <h3 class="font-bold text-lg text-gray-900 mb-6 pb-4 border-b">订单结算</h3>

                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between">
                                <span class="text-gray-500">商品总额</span>
                                <span class="text-gray-900">¥<?php echo number_format($subtotal, 0); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">运费</span>
                                <span class="text-green-600"><?php echo $shipping_fee > 0 ? '¥' . $shipping_fee : '免运费'; ?></span>
                            </div>
                            <?php if ($discount > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-500">优惠减免</span>
                                <span class="text-primary">-¥<?php echo number_format($discount, 0); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="border-t pt-4 mb-6">
                            <div class="flex items-baseline justify-between">
                                <span class="text-gray-600">应付总额:</span>
                                <div>
                                    <span class="text-3xl font-bold text-primary">¥<?php echo number_format($total_amount, 0); ?></span>
                                </div>
                            </div>
                            <div class="text-right text-sm text-gray-400 mt-1">
                                共<?php echo $total_qty; ?>件商品
                            </div>
                        </div>

                        <!-- 提交按钮 -->
                        <button type="submit"
                                class="w-full py-4 bg-primary text-white text-lg font-bold rounded-lg
                                       hover:bg-red-600 transition-colors shadow-lg shadow-red-200">
                            提交订单
                        </button>

                        <!-- 服务保障 -->
                        <div class="mt-6 pt-4 border-t">
                            <div class="flex items-center justify-center gap-4 text-xs text-gray-400">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    正品保障
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    闪电发货
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    7天退换
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- ==========================================
             Mobile 底部结算条 (仅手机显示)
             ========================================== -->
        <div class="md:hidden fixed bottom-0 left-0 w-full bg-white shadow-top z-50"
             style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="h-14 flex items-center px-4">
                <!-- 左侧: 金额 -->
                <div class="flex-1">
                    <div class="flex items-baseline gap-1">
                        <span class="text-gray-500 text-sm">实付:</span>
                        <span class="text-xl font-bold text-primary">¥<?php echo number_format($total_amount, 0); ?></span>
                    </div>
                    <div class="text-[10px] text-gray-400">含运费 ¥0，共<?php echo $total_qty; ?>件</div>
                </div>

                <!-- 右侧: 提交按钮 -->
                <button type="submit"
                        class="px-8 h-10 bg-primary text-white font-bold rounded-full
                               active:bg-red-600 transition-colors">
                    提交订单
                </button>
            </div>
        </div>
    </form>

    <script>
    // 选择地址
    function selectAddress(element, addressId) {
        // 移除其他选中状态
        document.querySelectorAll('.address-card').forEach(card => {
            card.classList.remove('selected');
            card.classList.add('border-gray-200');
        });

        // 添加选中状态
        element.classList.add('selected');
        element.classList.remove('border-gray-200');

        // 更新隐藏字段
        document.getElementById('selectedAddressId').value = addressId;
    }

    // 选择支付方式
    function selectPayment(paymentId, element) {
        const colorClass = element.dataset.color;

        // 移除其他选中状态
        document.querySelectorAll('.payment-card').forEach(card => {
            card.classList.remove('selected');
            card.classList.remove(card.dataset.color);
            card.classList.add('border-gray-200');
        });

        // 添加选中状态
        element.classList.add('selected');
        element.classList.add(colorClass);
        element.classList.remove('border-gray-200');
        element.querySelector('input').checked = true;

        // 更新隐藏字段
        document.getElementById('selectedPayment').value = paymentId;

        // 显示/隐藏对公转账提示
        const bankNotice = document.getElementById('bankNotice');
        if (paymentId === 'bank') {
            bankNotice.classList.remove('hidden');
        } else {
            bankNotice.classList.add('hidden');
        }
    }

    // 表单提交验证
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const addressId = document.getElementById('selectedAddressId').value;

        if (!addressId) {
            e.preventDefault();
            alert('请选择收货地址');
            return;
        }

        // 确认提交
        const totalAmount = '¥<?php echo number_format($total_amount, 0); ?>';
        if (!confirm(`确认提交订单？\n\n应付金额: ${totalAmount}\n\n提交后请及时完成支付。`)) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>
