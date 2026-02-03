<?php
/**
 * ============================================
 * 甘肃汇森信息科技有限公司 - 图片匹配工具
 * ============================================
 *
 * 功能说明：
 * 1. 扫描指定目录的手机图片
 * 2. 智能匹配数据库中的手机型号
 * 3. 更新数据库中的图片路径
 * 4. 优先选择 _1.jpg 作为主图
 *
 * 使用方法：
 * 直接访问此页面，点击"开始匹配"按钮即可
 */

// 引入配置文件
require_once __DIR__ . '/config/config.php';

// 图片目录路径
$imageBaseDir = 'C:\\xampp\\htdocs\\huisen\\images\\图片素材\\';

/**
 * 标准化文本 - 用于智能匹配
 * 去除空格、横线、下划线，转为小写
 */
function normalizeText($text) {
    $text = strtolower($text);
    $text = str_replace([' ', '-', '_', '（', '）', '(', ')'], '', $text);
    return $text;
}

/**
 * 计算图片匹配分数 - 返回0-100的分数，分数越高匹配度越好
 */
function calculateMatchScore($filename, $model, $brand = '') {
    $score = 0;
    $normalizedFilename = normalizeText($filename);
    $normalizedModel = normalizeText($model);
    $normalizedBrand = normalizeText($brand);
    
    // 1. 完全匹配型号（最高分：50分）
    if (strpos($normalizedFilename, $normalizedModel) !== false) {
        $score += 50;
        // 如果完全匹配，直接返回高分
        return $score + 30;
    }
    
    // 2. 处理特殊情况：iPhone 15 Pro Max 可能写成 iPhone15ProMax（40分）
    $modelParts = explode(' ', $model);
    if (count($modelParts) > 1) {
        $compactModel = normalizeText(implode('', $modelParts));
        if (strpos($normalizedFilename, $compactModel) !== false) {
            $score += 40;
        }
    }
    
    // 3. 提取型号的关键数字和字母组合
    $modelKeywords = extractModelKeywords($model);
    $matchedKeywords = 0;
    $totalKeywords = count($modelKeywords);
    
    foreach ($modelKeywords as $keyword) {
        $normalizedKeyword = normalizeText($keyword);
        if (strlen($keyword) >= 3 && strpos($normalizedFilename, $normalizedKeyword) !== false) {
            $matchedKeywords++;
            // 长关键词匹配得分更高
            if (strlen($keyword) >= 4) {
                $score += 8;
            } else {
                $score += 5;
            }
        } elseif (strlen($keyword) >= 2 && strpos($normalizedFilename, $normalizedKeyword) !== false) {
            $matchedKeywords++;
            $score += 3;
        }
    }
    
    // 4. 如果文件名包含品牌，额外加分（10分）
    if (!empty($normalizedBrand) && strpos($normalizedFilename, $normalizedBrand) !== false) {
        $score += 10;
    }
    
    // 5. 优先选择 _1 结尾的图片（5分）
    if (preg_match('/_1\.(jpg|jpeg|png|webp)$/i', $filename)) {
        $score += 5;
    }
    
    // 6. 型号关键词匹配率
    if ($totalKeywords > 0) {
        $matchRate = $matchedKeywords / $totalKeywords;
        $score += $matchRate * 15; // 最多15分
    }
    
    return min($score, 100); // 最高100分
}

/**
 * 检查型号是否匹配 - 改进版，支持更多匹配方式
 * @deprecated 使用 calculateMatchScore 代替
 */
function isModelMatch($filename, $model, $brand = '') {
    $score = calculateMatchScore($filename, $model, $brand);
    return $score >= 20; // 分数>=20认为匹配
}

/**
 * 提取型号关键词
 */
function extractModelKeywords($model) {
    $keywords = [];
    
    // 提取数字+字母组合（如 15Pro, 14Pro, 60Pro）
    preg_match_all('/\d+[a-z]+/i', $model, $matches);
    $keywords = array_merge($keywords, $matches[0]);
    
    // 提取字母+数字组合（如 Pro15, Max15）
    preg_match_all('/[a-z]+\d+/i', $model, $matches);
    $keywords = array_merge($keywords, $matches[0]);
    
    // 提取纯数字（如 15, 14, 60）
    preg_match_all('/\d+/', $model, $matches);
    $keywords = array_merge($keywords, $matches[0]);
    
    // 提取主要字母词（如 Pro, Max, Plus, Ultra）
    preg_match_all('/\b(pro|max|plus|ultra|mini|se)\b/i', $model, $matches);
    $keywords = array_merge($keywords, $matches[0]);
    
    return array_unique($keywords);
}

/**
 * 扫描图片目录
 */
function scanImageDirectory($baseDir) {
    $images = [];

    if (!is_dir($baseDir)) {
        return $images;
    }

    // 扫描品牌文件夹
    $brands = scandir($baseDir);
    foreach ($brands as $brand) {
        if ($brand === '.' || $brand === '..') continue;

        $brandDir = $baseDir . $brand;
        if (!is_dir($brandDir)) continue;

        // 扫描品牌文件夹中的图片
        $files = scandir($brandDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $images[] = [
                    'brand' => $brand,
                    'filename' => $file,
                    'fullpath' => $brandDir . '\\' . $file,
                    'relativepath' => 'images/图片素材/' . $brand . '/' . $file
                ];
            }
        }
    }

    return $images;
}

// 处理匹配请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'match') {
    header('Content-Type: application/json; charset=utf-8');
    
    // 设置执行时间限制（788条记录可能需要较长时间）
    set_time_limit(300); // 5分钟
    ini_set('max_execution_time', 300);

    try {
        // 获取数据库连接
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // 扫描图片目录
        $images = scanImageDirectory($imageBaseDir);

        if (empty($images)) {
            echo json_encode([
                'success' => false,
                'message' => '未找到任何图片文件，请检查目录：' . $imageBaseDir
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 获取所有手机型号 - 从 mobile_phones 表查询（包含700+条数据）
        // 可以选择只匹配没有图片的记录，或者匹配所有记录
        $onlyMissingImages = isset($_POST['only_missing']) && $_POST['only_missing'] === 'true';
        
        if ($onlyMissingImages) {
            $sql = "SELECT id, brand, model, spec, 
                    COALESCE(image_path, image_url) as image_path 
                    FROM mobile_phones 
                    WHERE brand != '未分类' AND brand IS NOT NULL
                    AND (image_path IS NULL OR image_path = '' OR image_url IS NULL OR image_url = '')
                    ORDER BY brand, model";
        } else {
            $sql = "SELECT id, brand, model, spec, 
                    COALESCE(image_path, image_url) as image_path 
                    FROM mobile_phones 
                    WHERE brand != '未分类' AND brand IS NOT NULL
                    ORDER BY brand, model";
        }
        $stmt = $conn->query($sql);
        $phones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($phones)) {
            echo json_encode([
                'success' => false,
                'message' => '数据库中没有手机数据'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $matchResults = [];
        $matchedCount = 0;
        $unmatchedCount = 0;
        $updatedCount = 0;
        
        // 跟踪已使用的图片，确保每张图片只使用一次
        $usedImages = [];
        
        // 品牌名称映射
        $brandMap = [
            '苹果' => ['apple', '苹果'],
            '华为' => ['huawei', '华为'],
            '小米' => ['xiaomi', '小米', 'redmi', '红米'],
            '荣耀' => ['honor', '荣耀'],
            'OPPO' => ['oppo'],
            'vivo' => ['vivo'],
            '三星' => ['samsung', '三星']
        ];

        // 开始匹配 - 使用评分系统
        foreach ($phones as $phone) {
            $matched = false;
            $matchedImage = null;
            $allMatches = []; // 存储所有匹配的图片及其分数

            // 查找所有匹配的图片并计算匹配分数
            foreach ($images as $imageIndex => $image) {
                // 跳过已使用的图片
                if (isset($usedImages[$image['relativepath']])) {
                    continue;
                }
                
                // 首先检查品牌是否匹配（如果图片在品牌文件夹中）
                $brandMatch = true;
                if (!empty($phone['brand']) && !empty($image['brand'])) {
                    $phoneBrand = normalizeText($phone['brand']);
                    $imageBrand = normalizeText($image['brand']);
                    
                    // 检查品牌是否匹配
                    $brandMatch = false;
                    if (isset($brandMap[$phone['brand']])) {
                        foreach ($brandMap[$phone['brand']] as $brandAlias) {
                            if (strpos($imageBrand, normalizeText($brandAlias)) !== false) {
                                $brandMatch = true;
                                break;
                            }
                        }
                    } else {
                        // 直接比较
                        $brandMatch = (strpos($imageBrand, $phoneBrand) !== false || 
                                     strpos($phoneBrand, $imageBrand) !== false);
                    }
                }
                
                // 如果品牌匹配，计算匹配分数
                if ($brandMatch) {
                    $matchScore = calculateMatchScore($image['filename'], $phone['model'], $phone['brand']);
                    
                    // 只考虑分数>=20的匹配（可调整阈值）
                    if ($matchScore >= 20) {
                        $allMatches[] = [
                            'image' => $image,
                            'score' => $matchScore,
                            'index' => $imageIndex
                        ];
                    }
                }
            }

            // 根据分数排序，选择最佳匹配
            if (!empty($allMatches)) {
                // 按分数降序排序
                usort($allMatches, function($a, $b) {
                    return $b['score'] - $a['score'];
                });
                
                // 选择分数最高的图片（且未被使用）
                foreach ($allMatches as $match) {
                    $imagePath = $match['image']['relativepath'];
                    if (!isset($usedImages[$imagePath])) {
                        $matchedImage = $match['image'];
                        $matchScore = $match['score'];
                        // 标记图片为已使用
                        $usedImages[$imagePath] = true;
                        break;
                    }
                }

                if ($matchedImage) {
                    $matched = true;
                }
            }

            if ($matched && $matchedImage) {
                // 更新数据库 - 更新 mobile_phones 表的 image_url 字段
                // 如果 image_path 字段存在则更新它，否则更新 image_url
                $updateSql = "UPDATE mobile_phones 
                             SET `image_url` = :image_path,
                                 `image_path` = :image_path2
                             WHERE `id` = :id";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([
                    'image_path' => $matchedImage['relativepath'],
                    'image_path2' => $matchedImage['relativepath'],
                    'id' => $phone['id']
                ]);

                $matchedCount++;
                $updatedCount += $updateStmt->rowCount();

                $matchResults[] = [
                    'status' => 'success',
                    'phone' => $phone['brand'] . ' ' . $phone['model'],
                    'image' => $matchedImage['filename'],
                    'path' => $matchedImage['relativepath'],
                    'score' => isset($matchScore) ? $matchScore : 0,
                    'total_matches' => count($allMatches)
                ];
            } else {
                $unmatchedCount++;
                $matchResults[] = [
                    'status' => 'failed',
                    'phone' => $phone['brand'] . ' ' . $phone['model'],
                    'message' => '未找到匹配的图片'
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'message' => '匹配完成！',
            'statistics' => [
                'total' => count($phones),
                'matched' => $matchedCount,
                'unmatched' => $unmatchedCount,
                'updated' => $updatedCount,
                'total_images' => count($images),
                'used_images' => count($usedImages),
                'unused_images' => count($images) - count($usedImages)
            ],
            'results' => $matchResults
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '匹配失败：' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>图片匹配工具 - 甘肃汇森</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans SC', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .result-item {
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease;
        }
        .result-item.show {
            opacity: 1;
            transform: translateX(0);
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .loading {
            animation: spin 1s linear infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4">
        <!-- 头部 -->
        <div class="max-w-6xl mx-auto">
            <div class="gradient-bg rounded-2xl shadow-2xl p-8 text-white mb-8">
                <h1 class="text-4xl font-bold mb-2">图片智能匹配工具</h1>
                <p class="text-pink-100">甘肃汇森信息科技有限公司 - Image Matching Tool</p>
            </div>

            <!-- 功能说明 -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    功能说明
                </h3>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <h4 class="font-semibold text-gray-800">扫描图片</h4>
                        </div>
                        <p class="text-sm text-gray-600">自动扫描图片素材目录，识别所有品牌文件夹中的图片文件</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            <h4 class="font-semibold text-gray-800">智能匹配</h4>
                        </div>
                        <p class="text-sm text-gray-600">使用智能算法匹配文件名与手机型号，优先选择 _1.jpg 作为主图</p>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                            </svg>
                            <h4 class="font-semibold text-gray-800">更新数据库</h4>
                        </div>
                        <p class="text-sm text-gray-600">自动更新数据库中的图片路径，保存为相对路径便于访问</p>
                    </div>
                </div>
            </div>

            <!-- 目录信息 -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    扫描目录
                </h3>
                <div class="bg-gray-50 rounded-lg p-4 font-mono text-sm">
                    <code class="text-gray-700"><?php echo htmlspecialchars($imageBaseDir); ?></code>
                </div>
                <p class="text-sm text-gray-500 mt-2">支持的图片格式：JPG、JPEG、PNG、WEBP</p>
            </div>

            <!-- 匹配选项 -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    匹配选项
                </h3>
                <div class="space-y-4">
                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 cursor-pointer transition-all">
                        <input type="radio" name="matchMode" value="all" checked class="w-5 h-5 text-blue-600 mr-3">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">匹配所有手机</div>
                            <div class="text-sm text-gray-600 mt-1">为所有手机（包括已有图片的）重新匹配图片</div>
                        </div>
                    </label>
                    <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 cursor-pointer transition-all">
                        <input type="radio" name="matchMode" value="missing" class="w-5 h-5 text-blue-600 mr-3">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">仅匹配缺少图片的手机</div>
                            <div class="text-sm text-gray-600 mt-1">只为还没有图片的手机匹配图片（更快）</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 开始匹配按钮 -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
                <button
                    id="matchBtn"
                    class="w-full bg-gradient-to-r from-pink-600 to-purple-600 text-white font-bold py-4 px-6 rounded-lg hover:from-pink-700 hover:to-purple-700 transition-all transform hover:scale-105 shadow-lg flex items-center justify-center"
                >
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path>
                    </svg>
                    开始智能匹配
                </button>
            </div>

            <!-- 匹配进度 -->
            <div id="progressContainer" class="bg-white rounded-xl shadow-lg p-8 mb-6 hidden">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600 loading" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    正在匹配...
                </h3>
                <div class="flex items-center justify-center py-8">
                    <div class="text-center">
                        <div class="inline-block w-16 h-16 border-4 border-pink-200 border-t-pink-600 rounded-full loading"></div>
                        <p class="text-gray-600 mt-4">正在扫描图片并匹配手机型号...</p>
                    </div>
                </div>
            </div>

            <!-- 统计结果 -->
            <div id="statisticsContainer" class="bg-white rounded-xl shadow-lg p-8 mb-6 hidden">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    匹配统计
                </h3>
                <div id="statisticsContent" class="grid md:grid-cols-7 gap-4"></div>
            </div>

            <!-- 匹配结果 -->
            <div id="resultsContainer" class="hidden">
                <!-- 成功匹配 -->
                <div id="successContainer" class="bg-white rounded-xl shadow-lg p-8 mb-6 hidden">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        匹配成功 <span id="successCount" class="ml-2 text-green-600"></span>
                    </h3>
                    <div id="successList" class="space-y-2 max-h-96 overflow-y-auto"></div>
                </div>

                <!-- 匹配失败 -->
                <div id="failedContainer" class="bg-white rounded-xl shadow-lg p-8 mb-6 hidden">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        未匹配 <span id="failedCount" class="ml-2 text-red-600"></span>
                    </h3>
                    <div id="failedList" class="space-y-2 max-h-96 overflow-y-auto"></div>
                </div>
            </div>

            <!-- 快速访问 -->
            <div id="quickLinks" class="bg-white rounded-xl shadow-lg p-8 hidden">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                    下一步操作
                </h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <a href="quotes.php" class="block p-6 border-2 border-blue-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all group">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-lg text-gray-800 group-hover:text-blue-600 mb-2">查看报价列表</h4>
                                <p class="text-sm text-gray-600">浏览所有手机报价信息，查看匹配的图片效果</p>
                            </div>
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </a>
                    <a href="detail.php?id=1" class="block p-6 border-2 border-green-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all group">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-lg text-gray-800 group-hover:text-green-600 mb-2">查看详情页面</h4>
                                <p class="text-sm text-gray-600">查看单个手机的详细信息和图片展示效果</p>
                            </div>
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const matchBtn = document.getElementById('matchBtn');
        const progressContainer = document.getElementById('progressContainer');
        const statisticsContainer = document.getElementById('statisticsContainer');
        const statisticsContent = document.getElementById('statisticsContent');
        const resultsContainer = document.getElementById('resultsContainer');
        const successContainer = document.getElementById('successContainer');
        const successList = document.getElementById('successList');
        const successCount = document.getElementById('successCount');
        const failedContainer = document.getElementById('failedContainer');
        const failedList = document.getElementById('failedList');
        const failedCount = document.getElementById('failedCount');
        const quickLinks = document.getElementById('quickLinks');

        matchBtn.addEventListener('click', async () => {
            // 禁用按钮
            matchBtn.disabled = true;
            matchBtn.innerHTML = '<svg class="w-6 h-6 mr-2 loading" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>正在匹配...';

            // 显示进度
            progressContainer.classList.remove('hidden');
            statisticsContainer.classList.add('hidden');
            resultsContainer.classList.add('hidden');
            quickLinks.classList.add('hidden');

            try {
                const formData = new FormData();
                formData.append('action', 'match');
                
                // 获取匹配模式
                const matchMode = document.querySelector('input[name="matchMode"]:checked').value;
                formData.append('only_missing', matchMode === 'missing' ? 'true' : 'false');

                const response = await fetch('run_image_match.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                // 隐藏进度
                progressContainer.classList.add('hidden');

                if (result.success) {
                    // 显示统计信息
                    statisticsContainer.classList.remove('hidden');
                    const stats = result.statistics;

                    statisticsContent.innerHTML = `
                        <div class="bg-blue-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-blue-600">${stats.total}</div>
                            <div class="text-sm text-gray-600 mt-1">手机总数</div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-green-600">${stats.matched}</div>
                            <div class="text-sm text-gray-600 mt-1">匹配成功</div>
                        </div>
                        <div class="bg-red-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-red-600">${stats.unmatched}</div>
                            <div class="text-sm text-gray-600 mt-1">未匹配</div>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-purple-600">${stats.updated}</div>
                            <div class="text-sm text-gray-600 mt-1">已更新</div>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-orange-600">${stats.total_images}</div>
                            <div class="text-sm text-gray-600 mt-1">图片总数</div>
                        </div>
                        <div class="bg-teal-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-teal-600">${stats.used_images || 0}</div>
                            <div class="text-sm text-gray-600 mt-1">已使用图片</div>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold text-yellow-600">${stats.unused_images || 0}</div>
                            <div class="text-sm text-gray-600 mt-1">未使用图片</div>
                        </div>
                    `;

                    // 显示结果容器
                    resultsContainer.classList.remove('hidden');

                    // 处理成功的匹配
                    const successResults = result.results.filter(r => r.status === 'success');
                    const failedResults = result.results.filter(r => r.status === 'failed');

                    if (successResults.length > 0) {
                        successContainer.classList.remove('hidden');
                        successCount.textContent = `(${successResults.length})`;
                        successList.innerHTML = '';

                        successResults.forEach((item, index) => {
                            setTimeout(() => {
                                const div = document.createElement('div');
                                div.className = 'result-item flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg';
                                
                                // 根据匹配分数显示不同的颜色
                                const score = item.score || 0;
                                let scoreColor = 'text-gray-600';
                                let scoreBg = 'bg-gray-100';
                                if (score >= 70) {
                                    scoreColor = 'text-green-600';
                                    scoreBg = 'bg-green-100';
                                } else if (score >= 50) {
                                    scoreColor = 'text-blue-600';
                                    scoreBg = 'bg-blue-100';
                                } else if (score >= 30) {
                                    scoreColor = 'text-yellow-600';
                                    scoreBg = 'bg-yellow-100';
                                }
                                
                                div.innerHTML = `
                                    <div class="flex items-center flex-1">
                                        <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <div class="ml-3 flex-1">
                                            <p class="font-semibold text-gray-800">${item.phone}</p>
                                            <p class="text-sm text-gray-600">${item.image}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                ${item.score ? `<span class="text-xs px-2 py-1 rounded ${scoreBg} ${scoreColor} font-semibold">匹配度: ${item.score}分</span>` : ''}
                                                ${item.total_matches > 1 ? `<span class="text-xs text-green-600">找到 ${item.total_matches} 张候选图片</span>` : ''}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500 ml-4 max-w-xs truncate">${item.path}</div>
                                `;
                                successList.appendChild(div);
                                setTimeout(() => div.classList.add('show'), 10);
                            }, index * 50);
                        });
                    }

                    if (failedResults.length > 0) {
                        failedContainer.classList.remove('hidden');
                        failedCount.textContent = `(${failedResults.length})`;
                        failedList.innerHTML = '';

                        failedResults.forEach((item, index) => {
                            setTimeout(() => {
                                const div = document.createElement('div');
                                div.className = 'result-item flex items-center p-4 bg-red-50 border border-red-200 rounded-lg';
                                div.innerHTML = `
                                    <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    <div class="ml-3 flex-1">
                                        <p class="font-semibold text-gray-800">${item.phone}</p>
                                        <p class="text-sm text-red-600">${item.message}</p>
                                    </div>
                                `;
                                failedList.appendChild(div);
                                setTimeout(() => div.classList.add('show'), 10);
                            }, index * 50);
                        });
                    }

                    // 显示快速链接
                    quickLinks.classList.remove('hidden');

                    // 更新按钮
                    matchBtn.innerHTML = '<svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>匹配完成！';
                    matchBtn.className = 'w-full bg-green-600 text-white font-bold py-4 px-6 rounded-lg cursor-not-allowed flex items-center justify-center';

                } else {
                    // 显示错误
                    alert('匹配失败：' + result.message);
                    matchBtn.disabled = false;
                    matchBtn.innerHTML = '<svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>重新匹配';
                }

            } catch (error) {
                progressContainer.classList.add('hidden');
                alert('网络错误：' + error.message);
                matchBtn.disabled = false;
                matchBtn.innerHTML = '<svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>重新匹配';
            }
        });
    </script>
</body>
</html>
