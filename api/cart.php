<?php
/**
 * ============================================
 * 询价单 API - 处理商品添加、删除、更新
 * ============================================
 */

session_start();
require_once __DIR__ . '/../config/config.php';

// 初始化询价单
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 处理 OPTIONS 预检请求
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

try {
    switch ($action) {
        case 'add':
            if ($method !== 'POST') {
                errorResponse('请使用POST方法', 405);
            }
            handleAddToCart();
            break;
            
        case 'remove':
            if ($method !== 'POST') {
                errorResponse('请使用POST方法', 405);
            }
            handleRemoveFromCart();
            break;
            
        case 'update':
            if ($method !== 'POST') {
                errorResponse('请使用POST方法', 405);
            }
            handleUpdateCart();
            break;
            
        case 'clear':
            if ($method !== 'POST') {
                errorResponse('请使用POST方法', 405);
            }
            handleClearCart();
            break;
            
        case 'list':
            handleGetCart();
            break;
            
        default:
            errorResponse('未知的操作类型', 400);
    }
} catch (Exception $e) {
    errorResponse('操作失败：' . $e->getMessage(), 500);
}

/**
 * 添加商品到询价单
 */
function handleAddToCart() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        $input = $_POST;
    }
    
    // 验证必填字段
    if (empty($input['sku_id'])) {
        errorResponse('SKU ID不能为空', 400);
    }
    
    $sku_id = intval($input['sku_id']);
    $qty = isset($input['qty']) ? max(1, intval($input['qty'])) : 1;
    
    // 检查是否已存在
    $exists = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['sku_id'] === $sku_id) {
            $item['qty'] = ($item['qty'] ?? 1) + $qty;
            $exists = true;
            break;
        }
    }
    
    // 如果不存在，添加新商品
    if (!$exists) {
        $_SESSION['cart'][] = [
            'sku_id' => $sku_id,
            'qty' => $qty
        ];
    }
    
    // 重新索引数组
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    successResponse([
        'cart_count' => count($_SESSION['cart']),
        'message' => '已加入询价单'
    ], '添加成功');
}

/**
 * 从询价单删除商品
 */
function handleRemoveFromCart() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        $input = $_POST;
    }
    
    if (empty($input['sku_id'])) {
        errorResponse('SKU ID不能为空', 400);
    }
    
    $sku_id = intval($input['sku_id']);
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($sku_id) {
        return $item['sku_id'] !== $sku_id;
    });
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    successResponse([
        'cart_count' => count($_SESSION['cart'])
    ], '删除成功');
}

/**
 * 更新询价单商品数量
 */
function handleUpdateCart() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        $input = $_POST;
    }
    
    if (empty($input['sku_id']) || !isset($input['qty'])) {
        errorResponse('SKU ID和数量不能为空', 400);
    }
    
    $sku_id = intval($input['sku_id']);
    $qty = max(1, intval($input['qty']));
    
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['sku_id'] === $sku_id) {
            $item['qty'] = $qty;
            break;
        }
    }
    
    successResponse([
        'cart_count' => count($_SESSION['cart'])
    ], '更新成功');
}

/**
 * 清空询价单
 */
function handleClearCart() {
    $_SESSION['cart'] = [];
    successResponse([], '清空成功');
}

/**
 * 获取询价单列表
 */
function handleGetCart() {
    successResponse([
        'cart' => $_SESSION['cart'],
        'cart_count' => count($_SESSION['cart'])
    ], '获取成功');
}
