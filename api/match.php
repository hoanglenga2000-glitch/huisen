<?php
/**
 * ============================================
 * 甘肃汇森信息科技有限公司 - AI 智能选机匹配 API
 * ============================================
 * 
 * 接口说明：
 * POST /api/match.php    智能匹配手机
 * 
 * 请求参数：
 * - tags: 需求标签数组 (如: ['gaming', 'camera'])
 * - min_price: 最低价格
 * - max_price: 最高价格
 */

// 开启 Session（如果需要）
session_start();

// 引入配置文件
require_once __DIR__ . '/../config/config.php';

// 处理OPTIONS预检请求（CORS）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
    exit(0);
}

// 只接受 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('请使用POST方法', 405);
}

// 获取数据库实例
$db = Database::getInstance();

try {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        $input = $_POST;
    }
    
    // 验证必填字段
    if (empty($input['tags']) || !is_array($input['tags'])) {
        errorResponse('请提供需求标签', 400);
    }
    
    $tags = $input['tags'];
    $minPrice = isset($input['min_price']) ? floatval($input['min_price']) : 500;
    $maxPrice = isset($input['max_price']) ? floatval($input['max_price']) : 15000;
    
    // 执行匹配
    $results = performMatch($db, $tags, $minPrice, $maxPrice);
    
    successResponse($results, '匹配成功');
    
} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}

/**
 * 执行智能匹配算法
 * 
 * @param Database $db 数据库实例
 * @param array $tags 需求标签数组
 * @param float $minPrice 最低价格
 * @param float $maxPrice 最高价格
 * @return array 匹配结果（Top 3）
 */
function performMatch($db, $tags, $minPrice, $maxPrice) {
    // 获取所有符合条件的手机
    $sql = "SELECT id, brand, model, spec, price, note, 
                   performance_score, camera_score, battery_score, tags
            FROM mobile_phones 
            WHERE price >= ? AND price <= ?
            ORDER BY price ASC";
    
    $phones = $db->fetchAll($sql, [$minPrice, $maxPrice]);
    
    if (empty($phones)) {
        return [];
    }
    
    // 计算每个手机的匹配得分
    $scoredPhones = [];
    
    foreach ($phones as $phone) {
        $score = calculateMatchScore($phone, $tags, $minPrice, $maxPrice);
        $phone['match_score'] = $score;
        $phone['reason'] = generateRecommendationReason($phone, $tags);
        $scoredPhones[] = $phone;
    }
    
    // 按匹配得分降序排序
    usort($scoredPhones, function($a, $b) {
        return $b['match_score'] <=> $a['match_score'];
    });
    
    // 返回 Top 3
    return array_slice($scoredPhones, 0, 3);
}

/**
 * 计算匹配得分
 * 
 * @param array $phone 手机信息
 * @param array $tags 需求标签
 * @param float $minPrice 最低价格
 * @param float $maxPrice 最高价格
 * @return float 匹配得分
 */
function calculateMatchScore($phone, $tags, $minPrice, $maxPrice) {
    $score = 0;
    $price = floatval($phone['price']);
    $performanceScore = intval($phone['performance_score']);
    $cameraScore = intval($phone['camera_score']);
    $batteryScore = intval($phone['battery_score']);
    
    // 标签权重映射
    $tagWeights = [
        'gaming' => ['performance' => 0.6, 'camera' => 0.1, 'battery' => 0.3],
        'camera' => ['performance' => 0.2, 'camera' => 0.7, 'battery' => 0.1],
        'battery' => ['performance' => 0.2, 'camera' => 0.1, 'battery' => 0.7],
        'business' => ['performance' => 0.4, 'camera' => 0.3, 'battery' => 0.3],
        'elderly' => ['performance' => 0.1, 'camera' => 0.2, 'battery' => 0.7],
        'budget' => ['performance' => 0.3, 'camera' => 0.2, 'battery' => 0.5]
    ];
    
    // 计算每个标签的得分
    $totalWeight = 0;
    foreach ($tags as $tag) {
        if (isset($tagWeights[$tag])) {
            $weights = $tagWeights[$tag];
            
            // 根据权重计算得分
            $tagScore = 
                $performanceScore * $weights['performance'] +
                $cameraScore * $weights['camera'] +
                $batteryScore * $weights['battery'];
            
            $score += $tagScore;
            $totalWeight += 1;
        }
    }
    
    // 平均得分
    if ($totalWeight > 0) {
        $score = $score / $totalWeight;
    }
    
    // 价格优势加成（性价比）
    $priceRange = $maxPrice - $minPrice;
    if ($priceRange > 0) {
        // 价格越接近最低价，性价比越高
        $priceRatio = ($maxPrice - $price) / $priceRange;
        // 性价比加成：0-10分
        $priceBonus = $priceRatio * 10;
        
        // 如果是"性价比"标签，价格加成更高
        if (in_array('budget', $tags)) {
            $priceBonus *= 1.5;
        }
        
        $score += $priceBonus;
    }
    
    // 品牌加成（可选）
    $brandBonus = getBrandBonus($phone['brand'], $tags);
    $score += $brandBonus;
    
    // 标签匹配加成
    if (!empty($phone['tags'])) {
        $phoneTags = explode(',', $phone['tags']);
        foreach ($tags as $tag) {
            $tagName = getTagName($tag);
            if (in_array($tagName, $phoneTags)) {
                $score += 5; // 标签匹配加成
            }
        }
    }
    
    return round($score, 2);
}

/**
 * 获取品牌加成
 */
function getBrandBonus($brand, $tags) {
    $bonus = 0;
    
    // 根据需求标签给予品牌加成
    if (in_array('gaming', $tags)) {
        // 游戏需求：一加、realme、红米等游戏手机品牌
        if (in_array($brand, ['一加', 'realme', '红米', '小米'])) {
            $bonus += 3;
        }
    }
    
    if (in_array('camera', $tags)) {
        // 拍照需求：华为、OPPO、vivo等拍照强品牌
        if (in_array($brand, ['华为', '荣耀', 'OPPO', 'vivo'])) {
            $bonus += 3;
        }
    }
    
    if (in_array('elderly', $tags)) {
        // 老人模式：华为、荣耀等易用品牌
        if (in_array($brand, ['华为', '荣耀', 'OPPO', 'vivo'])) {
            $bonus += 2;
        }
    }
    
    return $bonus;
}

/**
 * 获取标签中文名称
 */
function getTagName($tag) {
    $tagNames = [
        'gaming' => '高性能',
        'camera' => '拍照好',
        'battery' => '长续航',
        'business' => '商务办公',
        'elderly' => '老人模式',
        'budget' => '性价比'
    ];
    
    return $tagNames[$tag] ?? $tag;
}

/**
 * 生成推荐理由
 */
function generateRecommendationReason($phone, $tags) {
    $reasons = [];
    
    $performanceScore = intval($phone['performance_score']);
    $cameraScore = intval($phone['camera_score']);
    $batteryScore = intval($phone['battery_score']);
    $price = floatval($phone['price']);
    
    // 根据标签生成理由
    if (in_array('gaming', $tags) && $performanceScore >= 85) {
        $reasons[] = '性能强劲，满帧运行大型游戏';
    }
    
    if (in_array('camera', $tags) && $cameraScore >= 85) {
        $reasons[] = '拍照出色，影像系统专业';
    }
    
    if (in_array('battery', $tags) && $batteryScore >= 80) {
        $reasons[] = '续航持久，满足全天使用';
    }
    
    if (in_array('elderly', $tags)) {
        $reasons[] = '操作简单，适合长辈使用';
    }
    
    if (in_array('budget', $tags) && $price <= 3000) {
        $reasons[] = '性价比极高，物超所值';
    }
    
    // 如果没有特定理由，生成通用理由
    if (empty($reasons)) {
        if ($performanceScore >= 85) {
            $reasons[] = '性能表现优秀';
        }
        if ($cameraScore >= 85) {
            $reasons[] = '拍照效果出色';
        }
        if ($batteryScore >= 80) {
            $reasons[] = '续航能力突出';
        }
        if (empty($reasons)) {
            $reasons[] = '综合表现均衡，值得推荐';
        }
    }
    
    return implode('；', $reasons);
}
