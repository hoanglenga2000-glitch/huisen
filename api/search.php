<?php
/**
 * ============================================
 * 智能搜索 API
 * ============================================
 * 
 * 功能：
 * 1. 实时搜索建议
 * 2. 拼音搜索支持
 * 3. 模糊匹配
 * 4. 品牌智能识别
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (empty($query) || strlen($query) < 2) {
        echo json_encode(['success' => false, 'message' => '请输入至少2个字符']);
        exit;
    }
    
    // 智能搜索算法
    // 1. 拼音首字母映射
    $pinyin_map = [
        'ip' => 'iPhone',
        'pg' => '苹果',
        'hw' => '华为',
        'xm' => '小米',
        'op' => 'OPPO',
        'vv' => 'vivo',
        'ry' => '荣耀',
        'yj' => '一加',
        'sx' => '三星',
    ];
    
    $search_terms = [$query];
    
    // 检查是否是拼音缩写
    $query_lower = strtolower($query);
    foreach ($pinyin_map as $abbr => $full) {
        if (strpos($query_lower, $abbr) === 0) {
            $search_terms[] = $full;
        }
    }
    
    // 品牌智能识别
    $brand_keywords = [
        'iPhone', 'iphone', 'IP', 'ip', 'Apple', 'apple', '苹果',
        'Huawei', 'huawei', 'hw', 'HW', '华为',
        'Xiaomi', 'xiaomi', 'mi', 'MI', '小米', 'Redmi', 'redmi',
        'OPPO', 'oppo', 'Oppo',
        'vivo', 'VIVO', 'Vivo',
        'Honor', 'honor', '荣耀',
        'OnePlus', 'oneplus', '一加',
        'Samsung', 'samsung', '三星', 'Galaxy',
    ];
    
    // 构建SQL - 多条件匹配
    $sql = "SELECT id, brand, model, spec, price, image_url as image_path, tags
            FROM mobile_phones 
            WHERE brand != '未分类' AND (";
    
    $conditions = [];
    $params = [];
    
    foreach ($search_terms as $term) {
        $conditions[] = "(model LIKE ? OR brand LIKE ? OR spec LIKE ?)";
        $param = '%' . $term . '%';
        $params[] = $param;
        $params[] = $param;
        $params[] = $param;
    }
    
    $sql .= implode(' OR ', $conditions);
    $sql .= ") ORDER BY 
        CASE 
            WHEN model LIKE ? THEN 1
            WHEN brand LIKE ? THEN 2
            ELSE 3
        END,
        price DESC
        LIMIT 20";
    
    // 添加精确匹配排序参数
    $params[] = $query . '%';
    $params[] = $query . '%';
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 智能排序 - 提升热门机型
    usort($results, function($a, $b) {
        // 优先显示带热销标签的
        $a_hot = stripos($a['tags'] ?? '', '热销') !== false ? 1 : 0;
        $b_hot = stripos($b['tags'] ?? '', '热销') !== false ? 1 : 0;
        
        if ($a_hot != $b_hot) {
            return $b_hot - $a_hot;
        }
        
        // 其次按价格
        return $b['price'] - $a['price'];
    });
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results),
        'query' => $query
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
