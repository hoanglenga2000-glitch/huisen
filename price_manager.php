<?php
/**
 * 价格数据管理面板
 * 用于分析和管理价格同步情况
 */

require_once 'config/config.php';

$action = $_GET['action'] ?? 'dashboard';

$db = Database::getInstance();
$conn = $db->getConnection();

// 加载资料.txt
$file_path = __DIR__ . '/资料.txt';
$source_data = [];

if (file_exists($file_path)) {
    $content = file_get_contents($file_path);
    $lines = explode("\n", $content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line) || 
            strpos($line, '批发价官网价') !== false || 
            strpos($line, '报价单') !== false ||
            strpos($line, '请输入') !== false ||
            strpos($line, '分类') !== false ||
            strpos($line, '品牌') !== false ||
            strpos($line, '我司') !== false) {
            continue;
        }
        
        if (preg_match('/^(.+?)(\d{4,})$/u', $line, $matches)) {
            $model_raw = trim($matches[1]);
            $prices_str = $matches[2];
            
            // 清理型号
            $model_clean = preg_replace('/\([A-Z0-9\/\-]+\)/u', '', $model_raw);
            $model_clean = preg_replace('/原封|省外|不管控|拆封未激活/u', '', $model_clean);
            $model_clean = trim($model_clean);
            
            if (strlen($model_clean) > 3) {
                $source_data[] = [
                    'model' => $model_clean,
                    'price_str' => $prices_str,
                    'original' => $model_raw
                ];
            }
        }
    }
}

// 处理删除无价格数据
if ($action == 'delete_no_price') {
    $stmt = $conn->prepare("DELETE FROM mobile_phones WHERE price IS NULL OR price = 0 OR price = ''");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    
    header('Location: price_manager.php?action=dashboard&msg=deleted_' . $deleted);
    exit;
}

// 处理删除未匹配数据
if ($action == 'delete_unmatched') {
    // 获取所有数据库型号
    $db_models = [];
    $stmt = $conn->query("SELECT id, model FROM mobile_phones");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $db_models[$row['id']] = $row['model'];
    }
    
    // 检查哪些无法匹配
    $to_delete = [];
    foreach ($db_models as $id => $db_model) {
        $matched = false;
        
        foreach ($source_data as $src) {
            // 简单的相似度检查
            if (stripos($db_model, $src['model']) !== false || stripos($src['model'], $db_model) !== false) {
                $matched = true;
                break;
            }
        }
        
        if (!$matched) {
            $to_delete[] = $id;
        }
    }
    
    if (!empty($to_delete)) {
        $placeholders = implode(',', array_fill(0, count($to_delete), '?'));
        $stmt = $conn->prepare("DELETE FROM mobile_phones WHERE id IN ($placeholders)");
        $stmt->execute($to_delete);
        $deleted = $stmt->rowCount();
    } else {
        $deleted = 0;
    }
    
    header('Location: price_manager.php?action=dashboard&msg=deleted_unmatched_' . $deleted);
    exit;
}

// 获取数据库统计
$total_phones = $conn->query("SELECT COUNT(*) FROM mobile_phones")->fetchColumn();
$with_price = $conn->query("SELECT COUNT(*) FROM mobile_phones WHERE price > 0")->fetchColumn();
$no_price = $total_phones - $with_price;

// 获取未匹配的数据
$unmatched_db = [];
$stmt = $conn->query("SELECT id, model, brand, price, official_price FROM mobile_phones ORDER BY brand, model LIMIT 100");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $unmatched_db[] = $row;
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>价格数据管理面板</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">价格数据管理面板</h1>
        
        <?php if (isset($_GET['msg'])): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 rounded-lg text-green-800">
            <?php 
            $msg = $_GET['msg'];
            if (strpos($msg, 'deleted_') === 0) {
                $count = str_replace('deleted_', '', $msg);
                echo "✓ 已删除 {$count} 条数据";
            } elseif (strpos($msg, 'deleted_unmatched_') === 0) {
                $count = str_replace('deleted_unmatched_', '', $msg);
                echo "✓ 已删除 {$count} 条未匹配数据";
            }
            ?>
        </div>
        <?php endif; ?>
        
        <!-- 统计卡片 -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-semibold mb-2">资料.txt 总数据</h3>
                <p class="text-4xl font-bold text-blue-600"><?= count($source_data) ?></p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-semibold mb-2">数据库 有价格</h3>
                <p class="text-4xl font-bold text-green-600"><?= $with_price ?></p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-gray-500 text-sm font-semibold mb-2">数据库 无价格</h3>
                <p class="text-4xl font-bold text-red-600"><?= $no_price ?></p>
            </div>
        </div>
        
        <!-- 匹配率分析 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-bold mb-4">匹配率分析</h2>
            <div class="mb-4">
                <div class="flex justify-between mb-2">
                    <span>匹配成功率</span>
                    <span class="font-bold"><?= $total_phones > 0 ? round(($with_price / $total_phones) * 100, 2) : 0 ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="bg-green-500 h-4 rounded-full" style="width: <?= $total_phones > 0 ? ($with_price / $total_phones) * 100 : 0 ?>%"></div>
                </div>
            </div>
            
            <p class="text-sm text-gray-600 mt-4">
                <strong>未匹配原因分析：</strong><br>
                1. 型号名称格式不一致（数据库 vs 资料.txt）<br>
                2. 数据库中存在测试数据或废弃型号<br>
                3. 关键词提取逻辑可能遗漏某些特殊格式
            </p>
        </div>
        
        <!-- 操作按钮 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-bold mb-4">数据清理操作</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-semibold mb-2">删除无价格数据</h3>
                    <p class="text-sm text-gray-600 mb-3">删除数据库中价格为空或0的所有记录</p>
                    <button onclick="deleteNoPrice()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                        删除 <?= $no_price ?> 条无价格数据
                    </button>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-2">重新运行价格同步</h3>
                    <p class="text-sm text-gray-600 mb-3">再次运行价格同步脚本</p>
                    <a href="sync_prices.php" target="_blank" class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        运行同步脚本
                    </a>
                </div>
            </div>
        </div>
        
        <!-- 数据库前100条数据 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">数据库数据预览 (前100条)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">品牌</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">型号</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">批发价</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">官网价</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">状态</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($unmatched_db as $item): ?>
                        <tr class="<?= $item['price'] > 0 ? '' : 'bg-red-50' ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm"><?= $item['id'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm"><?= htmlspecialchars($item['brand']) ?></td>
                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($item['model']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">
                                <?= $item['price'] > 0 ? '¥' . number_format($item['price']) : '-' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?= $item['official_price'] > 0 ? '¥' . number_format($item['official_price']) : '-' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($item['price'] > 0): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">✓ 有价格</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">✗ 无价格</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- 资料.txt 数据样本 -->
        <div class="bg-white rounded-lg shadow p-6 mt-8">
            <h2 class="text-xl font-bold mb-4">资料.txt 数据样本 (前50条)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">型号</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">价格串</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">原始数据</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach (array_slice($source_data, 0, 50) as $item): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($item['model']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono"><?= $item['price_str'] ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($item['original']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
    function deleteNoPrice() {
        if (confirm('确定要删除所有无价格的数据吗？此操作不可恢复！')) {
            window.location.href = 'price_manager.php?action=delete_no_price';
        }
    }
    
    function deleteUnmatched() {
        if (confirm('确定要删除所有无法匹配的数据吗？此操作不可恢复！')) {
            window.location.href = 'price_manager.php?action=delete_unmatched';
        }
    }
    </script>
</body>
</html>
