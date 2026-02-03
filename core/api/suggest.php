<?php
/**
 * 搜索建议API
 * 返回匹配的产品型号列表
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 检查是否使用SPU表
    $table_check = $conn->query("SHOW TABLES LIKE 'products_spu'")->rowCount();
    
    if ($table_check > 0) {
        // 从SPU表搜索
        $stmt = $conn->prepare("
            SELECT DISTINCT base_model 
            FROM products_spu 
            WHERE base_model LIKE ? OR brand LIKE ?
            ORDER BY min_price DESC
            LIMIT 10
        ");
        $stmt->execute(['%' . $query . '%', '%' . $query . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // 从旧表搜索
        $stmt = $conn->prepare("
            SELECT DISTINCT model 
            FROM mobile_phones 
            WHERE model LIKE ? OR brand LIKE ?
            ORDER BY price DESC
            LIMIT 10
        ");
        $stmt->execute(['%' . $query . '%', '%' . $query . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    echo json_encode($results, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
