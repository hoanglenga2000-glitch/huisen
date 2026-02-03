<?php
/**
 * ============================================
 * 报价数据解析工具 - 将报价文件转换为SQL
 * ============================================
 * 
 * 功能说明：
 * 1. 解析资料.txt文件中的报价信息
 * 2. 提取品牌、型号、规格、颜色、批发价、官网价
 * 3. 生成SQL INSERT语句
 * 4. 智能分割价格信息
 * 
 * 访问方式：通过浏览器访问此文件即可
 */

// 如果是命令行运行，不需要HTML输出
$isCli = php_sapi_name() === 'cli';

// 输入文件
$inputFile = __DIR__ . '/资料.txt';
// 输出SQL文件
$outputFile = __DIR__ . '/sql/import_price_data.sql';

/**
 * 智能分割价格
 * 例如：59105999 -> 批发价5910, 官网价5999 (8位：4+4)
 *       1079010999 -> 批发价10790, 官网价10999 (10位：5+5)
 *       1028012999 -> 批发价10280, 官网价12999 (10位：5+5)
 *       110149 -> 批发价110, 官网价149 (6位：3+3)
 *       4100 -> 批发价41, 官网价00 (4位：2+2，但通常只有批发价)
 */
function splitPrice($priceStr) {
    $priceStr = trim($priceStr);
    if (empty($priceStr) || !is_numeric($priceStr)) {
        return [0, 0];
    }
    
    $len = strlen($priceStr);
    
    // 根据长度智能分割
    if ($len == 4) {
        // 4位：通常只有批发价，官网价设为0或相同
        $wholesale = $priceStr;
        $retail = $priceStr; // 或者设为0，根据实际情况调整
    } elseif ($len == 5) {
        // 5位：可能是批发价，或前3位批发价后2位官网价
        if (substr($priceStr, -2) == '00') {
            // 如果后两位是00，可能是只有批发价
            $wholesale = $priceStr;
            $retail = $priceStr;
        } else {
            $wholesale = substr($priceStr, 0, 3);
            $retail = substr($priceStr, 3);
        }
    } elseif ($len == 6) {
        // 6位：前3位批发价，后3位官网价
        $wholesale = substr($priceStr, 0, 3);
        $retail = substr($priceStr, 3);
    } elseif ($len == 7) {
        // 7位：前4位批发价，后3位官网价
        $wholesale = substr($priceStr, 0, 4);
        $retail = substr($priceStr, 4);
    } elseif ($len == 8) {
        // 8位：前4位批发价，后4位官网价
        $wholesale = substr($priceStr, 0, 4);
        $retail = substr($priceStr, 4);
    } elseif ($len == 9) {
        // 9位：前5位批发价，后4位官网价
        $wholesale = substr($priceStr, 0, 5);
        $retail = substr($priceStr, 5);
    } elseif ($len == 10) {
        // 10位：前5位批发价，后5位官网价
        $wholesale = substr($priceStr, 0, 5);
        $retail = substr($priceStr, 5);
    } elseif ($len == 11) {
        // 11位：前6位批发价，后5位官网价
        $wholesale = substr($priceStr, 0, 6);
        $retail = substr($priceStr, 6);
    } else {
        // 其他长度：尝试平分或前一半批发价，后一半官网价
        $mid = floor($len / 2);
        $wholesale = substr($priceStr, 0, $mid);
        $retail = substr($priceStr, $mid);
    }
    
    return [
        floatval($wholesale),
        floatval($retail)
    ];
}

/**
 * 解析一行报价数据 - 支持所有产品类型
 */
function parsePriceLine($line, $currentBrand = '') {
    $line = trim($line);
    
    // 跳过空行和标题行
    if (empty($line) || 
        strpos($line, '批发价官网价') !== false ||
        strpos($line, '请输入关键字') !== false ||
        strpos($line, '分类') !== false ||
        strpos($line, '品牌') !== false ||
        strpos($line, '我司') !== false) {
        return null;
    }
    
    // 检查是否是品牌标题行（例如：苹果 2026-01-30 16:33:00批发价官网价）
    if (preg_match('/^([^\s]+)\s+\d{4}-\d{2}-\d{2}/', $line, $matches)) {
        return ['type' => 'brand', 'brand' => $matches[1]];
    }
    
    // 提取价格（最后3-11位数字）
    if (!preg_match('/(\d{3,11})$/', $line, $priceMatch)) {
        return null;
    }
    $priceStr = $priceMatch[1];
    $lineWithoutPrice = mb_substr($line, 0, mb_strlen($line) - mb_strlen($priceStr));
    
    // 移除所有括号内容（代码）
    $lineClean = preg_replace('/\([^)]+\)/', '', $lineWithoutPrice);
    $lineClean = trim($lineClean);
    
    // 提取品牌（常见品牌列表，按长度从长到短排序）
    $brands = ['摩托罗拉', '科大讯飞', '移动和家亲', '视海卫家', '哈曼卡顿', '苹果', '华为', '小米', 
               '荣耀', '三星', 'VIVO', 'OPPO', '一加', 'Realme', 'realme', '小度', '百合', '诺基亚', 
               'SKG', '安顿', '大疆', '小天才', '步步高', '优学派', '作业帮', 'Redmi', '红米'];
    
    $brand = '';
    $brandPos = -1;
    foreach ($brands as $b) {
        $pos = mb_strpos($lineClean, $b);
        if ($pos !== false && ($brandPos === -1 || $pos < $brandPos)) {
            $brand = $b;
            $brandPos = $pos;
        }
    }
    
    // 如果没有找到品牌，使用当前品牌或尝试从开头提取
    if (empty($brand)) {
        if (!empty($currentBrand)) {
            $brand = $currentBrand;
        } else {
            // 尝试从开头提取品牌（非数字开头的词，最多10个字符）
            if (preg_match('/^\s*([^\s\d]{2,10})/u', $lineClean, $m)) {
                $potentialBrand = trim($m[1]);
                // 如果提取的词包含常见品牌关键词，使用它
                if (mb_strlen($potentialBrand) <= 6) {
                    $brand = $potentialBrand;
                }
            }
        }
    }
    
    // 移除品牌，得到型号+规格+颜色部分
    $rest = trim(str_replace($brand, '', $lineClean, $count));
    if ($count == 0 && !empty($brand)) {
        // 如果替换失败，尝试直接移除
        $rest = trim(str_replace([$brand . ' ', $brand], '', $lineClean));
    }
    
    // 提取规格（可能是：256G、512G、1TG、16+256G、128G、42毫米等）
    $spec = '';
    $specPatterns = [
        '/(\d+\+\d+[GT]?B?)/',           // 16+256G, 12+256G
        '/(\d+[GT]B?)/',                  // 256G, 1TG, 512G
        '/(\d+\.\d+寸)/',                 // 11.5寸
        '/(\d+寸)/',                      // 11寸, 13寸
        '/(\d+\.\d+英寸)/',               // 65.5英寸
        '/(\d+英寸)/',                    // 65英寸
        '/(\d+毫米)/',                    // 42毫米
        '/(\d+\.\d+匹)/',                 // 1.5匹
        '/(\d+匹)/',                      // 1匹, 2匹, 3匹
        '/(\d+kg)/',                      // 10kg
        '/(\d+L)/',                       // 146L
        '/(\d+W)/',                       // 20W, 100W
        '/(\d+mAh)/',                     // 5000mAh
        '/(\d+GB)/',                      // 128GB
        '/(\d+TB)/',                      // 1TB
    ];
    
    foreach ($specPatterns as $pattern) {
        if (preg_match($pattern, $rest, $m)) {
            $spec = $m[1];
            // 不立即移除，因为可能影响后续解析
            break;
        }
    }
    
    // 提取型号（剩余部分，去除常见后缀）
    $model = trim($rest);
    
    // 清理型号中的常见后缀和描述
    $model = preg_replace('/\s*(原封|不换机|店保|纯原装|CCC认证|3C认证|官网预激活|拆封未激活).*$/i', '', $model);
    $model = preg_replace('/\s*-\s*$/', '', $model);
    $model = trim($model);
    
    // 提取颜色（从型号中分离，如果包含常见颜色词）
    $color = '';
    $colorKeywords = [
        '黑色', '白色', '红色', '蓝色', '绿色', '紫色', '粉色', '金色', '银色', '灰色', '橙色', '黄色', 
        '青色', '棕色', '黑', '白', '红', '蓝', '绿', '紫', '粉', '金', '银', '灰', '橙', '黄', '青', '棕',
        '午夜色', '星光色', '深空灰', '深空黑', '曜石黑', '雪域白', '云杉绿', '风信紫', '冰晶蓝', '凝霜白',
        '羽砂黑', '天青色', '月影白', '珊瑚粉', '绒黑色', '幻夜黑', '天海青', '典雅黑', '玄武黑', '星岩黑',
        '暮光紫', '青柠绿', '云暮白', '星河银', '凤银金', '微粉', '星空银', '简黑', '柔粉', '浅绿', '祥云金',
        '钛黑', '天青', '白金', '钻黑', '樱花粉', '蔚空蓝', '晴山蓝', '绯红', '天境蓝', '茶金白', '陨石灰',
        '星云粉', '山潮蓝', '霞光紫', '少年白', '少年黑', '疏烟粉', '镜空银'
    ];
    
    // 按长度从长到短排序，优先匹配长词
    usort($colorKeywords, function($a, $b) {
        return mb_strlen($b) - mb_strlen($a);
    });
    
    foreach ($colorKeywords as $colorWord) {
        if (mb_strpos($model, $colorWord) !== false) {
            $color = $colorWord;
            $model = str_replace($colorWord, '', $model);
            $model = trim($model);
            break;
        }
    }
    
    // 如果还是没有颜色，尝试从规格后面提取
    if (empty($color) && !empty($spec)) {
        $specPos = mb_strpos($rest, $spec);
        if ($specPos !== false) {
            $afterSpec = mb_substr($rest, $specPos + mb_strlen($spec));
            foreach ($colorKeywords as $colorWord) {
                if (mb_strpos($afterSpec, $colorWord) !== false) {
                    $color = $colorWord;
                    break;
                }
            }
        }
    }
    
    // 如果型号为空，尝试从原始行提取
    if (empty($model)) {
        // 尝试提取型号（数字开头的部分，或包含字母数字的组合）
        if (preg_match('/' . preg_quote($brand, '/') . '\s*(\d+[^\s\-]*?)/u', $lineClean, $m)) {
            $model = trim($m[1]);
        } elseif (preg_match('/(\d+[^\s\-\(]*?)/u', $lineClean, $m)) {
            $model = trim($m[1]);
        } elseif (preg_match('/([A-Za-z]+\d+[^\s\-]*?)/u', $lineClean, $m)) {
            $model = trim($m[1]);
        }
    }
    
    // 如果型号仍然为空，使用剩余部分（去除颜色和规格）
    if (empty($model)) {
        $model = trim($rest);
        if (!empty($spec)) {
            $model = str_replace($spec, '', $model);
        }
        if (!empty($color)) {
            $model = str_replace($color, '', $model);
        }
        // 移除常见描述词
        $model = preg_replace('/\s*(款|代|版|型|式|个|只|件|套|条|米|寸|英寸).*$/u', '', $model);
        $model = trim($model);
    }
    
    // 清理型号：移除多余的空格和特殊字符
    $model = preg_replace('/\s+/', ' ', $model);
    $model = trim($model);
    
    // 如果型号太长（超过50字符），截取前50字符
    if (mb_strlen($model) > 50) {
        $model = mb_substr($model, 0, 50);
    }
    
    // 清理
    $brand = trim($brand);
    $model = trim($model);
    $spec = trim($spec);
    $color = trim($color);
    
    // 如果品牌为空，返回null
    if (empty($brand)) {
        return null;
    }
    
    // 如果型号为空，尝试使用整个产品名称作为型号
    if (empty($model)) {
        $model = trim($lineClean);
        // 移除品牌
        $model = str_replace($brand, '', $model);
        $model = trim($model);
        // 如果还是太长，只取前30个字符
        if (mb_strlen($model) > 30) {
            $model = mb_substr($model, 0, 30);
        }
    }
    
    // 如果型号仍然为空，返回null
    if (empty($model)) {
        return null;
    }
    
    // 分割价格
    list($wholesalePrice, $retailPrice) = splitPrice($priceStr);
    
    return [
        'type' => 'product',
        'brand' => $brand,
        'model' => $model,
        'spec' => $spec ?: '',
        'color' => $color ?: '',
        'wholesale_price' => $wholesalePrice,
        'retail_price' => $retailPrice
    ];
}

// 处理Web请求
if (!$isCli && isset($_GET['action']) && $_GET['action'] === 'parse') {
    header('Content-Type: application/json; charset=utf-8');
    
    // 读取文件
    if (!file_exists($inputFile)) {
        echo json_encode(['success' => false, 'error' => "找不到文件 {$inputFile}"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $lines = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $currentBrand = '';
    $products = [];
    $stats = [
        'total_lines' => 0,
        'parsed' => 0,
        'skipped' => 0,
        'brands' => []
    ];
    
    foreach ($lines as $lineNum => $line) {
        $stats['total_lines']++;
        
        // 检查是否是品牌标题
        $parsed = parsePriceLine($line, $currentBrand);
        
        if ($parsed && $parsed['type'] === 'brand') {
            $currentBrand = $parsed['brand'];
            $stats['brands'][$currentBrand] = 0;
            continue;
        }
        
        if ($parsed && $parsed['type'] === 'product') {
            $products[] = $parsed;
            $stats['parsed']++;
            if (isset($stats['brands'][$parsed['brand']])) {
                $stats['brands'][$parsed['brand']]++;
            }
        } else {
            $stats['skipped']++;
        }
    }
    
    // 生成SQL文件

$sqlContent = "-- ============================================\n";
$sqlContent .= "-- 报价数据导入SQL\n";
$sqlContent .= "-- 生成时间：" . date('Y-m-d H:i:s') . "\n";
$sqlContent .= "-- 数据来源：资料.txt\n";
$sqlContent .= "-- 总记录数：" . count($products) . "\n";
$sqlContent .= "-- ============================================\n\n";

$sqlContent .= "USE `甘肃汇森`;\n\n";

$sqlContent .= "-- 清空现有数据（可选）\n";
$sqlContent .= "-- TRUNCATE TABLE `mobile_quotes`;\n\n";

$sqlContent .= "-- ============================================\n";
$sqlContent .= "-- 插入报价数据\n";
$sqlContent .= "-- ============================================\n\n";

$sqlContent .= "INSERT INTO `mobile_quotes` \n";
$sqlContent .= "(`brand`, `model`, `spec`, `color`, `price`, `retail_price`, `condition`, `note`, `created_at`, `updated_at`) \n";
$sqlContent .= "VALUES\n";

$values = [];
foreach ($products as $index => $product) {
    $brand = addslashes($product['brand']);
    $model = addslashes($product['model']);
    $spec = addslashes($product['spec']);
    $color = addslashes($product['color']);
    $wholesalePrice = $product['wholesale_price'];
    $retailPrice = $product['retail_price'];
    
    $values[] = sprintf(
        "('%s', '%s', '%s', '%s', %.2f, %.2f, '全新未拆', '兰州八方报价', NOW(), NOW())",
        $brand,
        $model,
        $spec,
        $color,
        $wholesalePrice,
        $retailPrice
    );
}

$sqlContent .= implode(",\n", $values) . ";\n\n";

$sqlContent .= "-- ============================================\n";
$sqlContent .= "-- 验证数据\n";
$sqlContent .= "-- ============================================\n\n";
$sqlContent .= "SELECT COUNT(*) as total FROM `mobile_quotes`;\n";
$sqlContent .= "SELECT brand, COUNT(*) as count FROM `mobile_quotes` GROUP BY brand ORDER BY count DESC;\n";

    // 写入文件
    $outputDir = dirname($outputFile);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    file_put_contents($outputFile, $sqlContent);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'products_count' => count($products),
        'output_file' => basename($outputFile),
        'file_size' => filesize($outputFile),
        'sample' => array_slice($products, 0, 5) // 返回前5条作为示例
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 命令行模式
if ($isCli) {
    // 读取文件
    if (!file_exists($inputFile)) {
        die("错误：找不到文件 {$inputFile}\n");
    }
    
    $lines = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $currentBrand = '';
    $products = [];
    $stats = [
        'total_lines' => 0,
        'parsed' => 0,
        'skipped' => 0,
        'brands' => []
    ];
    
    echo "开始解析报价文件...\n";
    
    foreach ($lines as $lineNum => $line) {
        $stats['total_lines']++;
        
        // 检查是否是品牌标题
        $parsed = parsePriceLine($line, $currentBrand);
        
        if ($parsed && $parsed['type'] === 'brand') {
            $currentBrand = $parsed['brand'];
            $stats['brands'][$currentBrand] = 0;
            echo "发现品牌：{$currentBrand}\n";
            continue;
        }
        
        if ($parsed && $parsed['type'] === 'product') {
            $products[] = $parsed;
            $stats['parsed']++;
            if (isset($stats['brands'][$parsed['brand']])) {
                $stats['brands'][$parsed['brand']]++;
            }
        } else {
            $stats['skipped']++;
        }
    }
    
    echo "\n解析完成！\n";
    echo "总行数：{$stats['total_lines']}\n";
    echo "解析成功：{$stats['parsed']}\n";
    echo "跳过：{$stats['skipped']}\n";
    echo "\n各品牌数量：\n";
    foreach ($stats['brands'] as $brand => $count) {
        echo "  {$brand}: {$count}\n";
    }
    
    // 生成SQL文件
    echo "\n生成SQL文件...\n";
    
    $sqlContent = "-- ============================================\n";
    $sqlContent .= "-- 报价数据导入SQL\n";
    $sqlContent .= "-- 生成时间：" . date('Y-m-d H:i:s') . "\n";
    $sqlContent .= "-- 数据来源：资料.txt\n";
    $sqlContent .= "-- 总记录数：" . count($products) . "\n";
    $sqlContent .= "-- ============================================\n\n";
    
    $sqlContent .= "USE `甘肃汇森`;\n\n";
    
    $sqlContent .= "-- 清空现有数据（可选）\n";
    $sqlContent .= "-- TRUNCATE TABLE `mobile_quotes`;\n\n";
    
    $sqlContent .= "-- ============================================\n";
    $sqlContent .= "-- 插入报价数据\n";
    $sqlContent .= "-- ============================================\n\n";
    
    $sqlContent .= "INSERT INTO `mobile_quotes` \n";
    $sqlContent .= "(`brand`, `model`, `spec`, `color`, `price`, `retail_price`, `condition`, `note`, `created_at`, `updated_at`) \n";
    $sqlContent .= "VALUES\n";
    
    $values = [];
    foreach ($products as $index => $product) {
        $brand = addslashes($product['brand']);
        $model = addslashes($product['model']);
        $spec = addslashes($product['spec']);
        $color = addslashes($product['color']);
        $wholesalePrice = $product['wholesale_price'];
        $retailPrice = $product['retail_price'];
        
        $values[] = sprintf(
            "('%s', '%s', '%s', '%s', %.2f, %.2f, '全新未拆', '兰州八方报价', NOW(), NOW())",
            $brand,
            $model,
            $spec,
            $color,
            $wholesalePrice,
            $retailPrice
        );
    }
    
    $sqlContent .= implode(",\n", $values) . ";\n\n";
    
    $sqlContent .= "-- ============================================\n";
    $sqlContent .= "-- 验证数据\n";
    $sqlContent .= "-- ============================================\n\n";
    $sqlContent .= "SELECT COUNT(*) as total FROM `mobile_quotes`;\n";
    $sqlContent .= "SELECT brand, COUNT(*) as count FROM `mobile_quotes` GROUP BY brand ORDER BY count DESC;\n";
    
    // 写入文件
    $outputDir = dirname($outputFile);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    file_put_contents($outputFile, $sqlContent);
    
    echo "\nSQL文件已生成：{$outputFile}\n";
    echo "文件大小：" . number_format(filesize($outputFile)) . " 字节\n";
    echo "\n完成！\n";
    exit;
}

// Web界面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>报价数据解析工具</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Microsoft YaHei', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-right: 10px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .result {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            display: none;
        }
        .result.show {
            display: block;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .stat-value {
            color: #333;
            font-size: 24px;
            font-weight: bold;
        }
        .brand-list {
            margin-top: 20px;
        }
        .brand-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: white;
            margin-bottom: 5px;
            border-radius: 5px;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .loading.show {
            display: block;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .download-link {
            display: inline-block;
            margin-top: 20px;
            padding: 15px 30px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 18px;
        }
        .download-link:hover {
            background: #218838;
        }
        .sample-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .sample-table th,
        .sample-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .sample-table th {
            background: #667eea;
            color: white;
        }
        .sample-table tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 报价数据解析工具</h1>
        <p class="subtitle">将报价文件转换为SQL格式，方便导入数据库</p>
        
        <button class="btn" onclick="parseData()">🚀 开始解析</button>
        <button class="btn" onclick="downloadSQL()" id="downloadBtn" style="display:none;">📥 下载SQL文件</button>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>正在解析数据，请稍候...</p>
        </div>
        
        <div class="result" id="result">
            <h2>解析结果</h2>
            <div class="stats" id="stats"></div>
            <div id="brandList"></div>
            <div id="sampleData"></div>
            <a href="#" class="download-link" id="downloadLink" style="display:none;">下载SQL文件</a>
        </div>
    </div>
    
    <script>
        let outputFile = '';
        
        async function parseData() {
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            const btn = event.target;
            
            loading.classList.add('show');
            result.classList.remove('show');
            btn.disabled = true;
            
            try {
                const response = await fetch('?action=parse');
                const data = await response.json();
                
                if (data.success) {
                    outputFile = data.output_file;
                    displayResults(data);
                    document.getElementById('downloadBtn').style.display = 'inline-block';
                    document.getElementById('downloadLink').href = 'sql/' + outputFile;
                    document.getElementById('downloadLink').style.display = 'inline-block';
                } else {
                    alert('解析失败：' + data.error);
                }
            } catch (error) {
                alert('发生错误：' + error.message);
            } finally {
                loading.classList.remove('show');
                btn.disabled = false;
            }
        }
        
        function displayResults(data) {
            const stats = document.getElementById('stats');
            const brandList = document.getElementById('brandList');
            const sampleData = document.getElementById('sampleData');
            
            // 显示统计信息
            stats.innerHTML = `
                <div class="stat-card">
                    <div class="stat-label">总行数</div>
                    <div class="stat-value">${data.stats.total_lines}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">解析成功</div>
                    <div class="stat-value" style="color: #28a745;">${data.stats.parsed}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">跳过行数</div>
                    <div class="stat-value" style="color: #ffc107;">${data.stats.skipped}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">文件大小</div>
                    <div class="stat-value">${(data.file_size / 1024).toFixed(2)} KB</div>
                </div>
            `;
            
            // 显示品牌列表
            let brandHtml = '<h3>各品牌数量：</h3><div class="brand-list">';
            for (const [brand, count] of Object.entries(data.stats.brands)) {
                brandHtml += `<div class="brand-item"><span>${brand}</span><span style="font-weight:bold;">${count} 条</span></div>`;
            }
            brandHtml += '</div>';
            brandList.innerHTML = brandHtml;
            
            // 显示示例数据
            if (data.sample && data.sample.length > 0) {
                let tableHtml = '<h3>示例数据（前5条）：</h3><table class="sample-table"><thead><tr><th>品牌</th><th>型号</th><th>规格</th><th>颜色</th><th>批发价</th><th>官网价</th></tr></thead><tbody>';
                data.sample.forEach(item => {
                    tableHtml += `<tr>
                        <td>${item.brand}</td>
                        <td>${item.model}</td>
                        <td>${item.spec}</td>
                        <td>${item.color}</td>
                        <td>¥${item.wholesale_price.toFixed(2)}</td>
                        <td>¥${item.retail_price.toFixed(2)}</td>
                    </tr>`;
                });
                tableHtml += '</tbody></table>';
                sampleData.innerHTML = tableHtml;
            }
            
            result.classList.add('show');
        }
        
        function downloadSQL() {
            if (outputFile) {
                window.location.href = 'sql/' + outputFile;
            }
        }
    </script>
</body>
</html>
