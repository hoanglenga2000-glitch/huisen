<?php
/**
 * ==========================================
 * 价格同步分析工具
 * ==========================================
 * 分析为什么某些价格没有匹配上
 */

require_once 'config/config.php';

$file_path = __DIR__ . '/资料.txt';
if (!file_exists($file_path)) {
    die("错误：找不到 资料.txt 文件\n");
}

$content = file_get_contents($file_path);
$lines = explode("\n", $content);

// 连接数据库
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("数据库错误: " . $e->getMessage());
}

// 存储分析结果
$analysis_results = [
    'success' => [],
    'no_price_format' => [],      // 无法提取价格格式
    'invalid_price' => [],         // 价格不合理
    'insufficient_keywords' => [], // 关键词不足
    'not_found_in_db' => [],       // 数据库中找不到
    'similar_models' => []          // 找到相似但未匹配的型号
];

foreach ($lines as $line_num => $line) {
    $line = trim($line);
    
    // 跳过无效行
    if (empty($line) || 
        strpos($line, '批发价官网价') !== false || 
        strpos($line, '报价单') !== false ||
        strpos($line, '请输入') !== false ||
        strpos($line, '分类') !== false ||
        strpos($line, '品牌') !== false ||
        strpos($line, '我司') !== false) {
        continue;
    }
    
    $original_line = $line;
    
    // 提取数字价格（最后的连续数字）
    if (!preg_match('/^(.+?)(\d{4,})$/u', $line, $matches)) {
        $analysis_results['no_price_format'][] = [
            'line' => $line_num + 1,
            'content' => $original_line,
            'reason' => '无法提取价格格式（需要至少4位连续数字）'
        ];
        continue;
    }
    
    $model_raw = trim($matches[1]);
    $prices_str = $matches[2];
    $total_len = strlen($prices_str);
    
    $wholesale = 0;
    $official = 0;
    $price_type = '';
    
    // 价格解析
    if ($total_len % 2 == 0) {
        $half = $total_len / 2;
        $wholesale = (int)substr($prices_str, 0, $half);
        $official = (int)substr($prices_str, $half);
        $price_type = '偶数位中间分割';
        
        if ($wholesale == $official) {
            $wholesale = (int)($wholesale * 0.98);
            $price_type .= ' (相同价格98折)';
        } else if ($wholesale > $official) {
            $wholesale = (int)($official * 0.98);
            $price_type .= ' (修正为98折)';
        } else if ($wholesale < $official * 0.70 || $wholesale > $official * 0.95) {
            $wholesale = (int)($official * 0.98);
            $price_type .= ' (异常折扣修正为98折)';
        }
    } else if ($total_len % 2 == 1 && substr($prices_str, -1) == '0') {
        $official = (int)substr($prices_str, 0, -1);
        $wholesale = (int)($official * 0.98);
        $price_type = '奇数位去尾0';
    } else {
        $analysis_results['no_price_format'][] = [
            'line' => $line_num + 1,
            'content' => $original_line,
            'reason' => '价格格式不符合规则（需要偶数位或奇数位以0结尾）',
            'price_str' => $prices_str
        ];
        continue;
    }
    
    // 价格合理性检查
    if ($wholesale < 100 || $wholesale > 200000 || $official < 100 || $official > 200000) {
        $analysis_results['invalid_price'][] = [
            'line' => $line_num + 1,
            'content' => $original_line,
            'wholesale' => $wholesale,
            'official' => $official,
            'reason' => '价格超出合理范围（100-200000）'
        ];
        continue;
    }
    
    // 清理型号
    $model_clean = preg_replace('/\([A-Z0-9\/\-]+\)/u', '', $model_raw);
    $model_clean = preg_replace('/原封|省外|不管控|拆封未激活/u', '', $model_clean);
    $model_clean = trim($model_clean);
    
    // 提取关键词
    $keywords = [];
    $keyword_details = [];
    
    // 品牌
    if (preg_match('/(苹果|华为|小米|荣耀|VIVO|vivo|OPPO|三星|红米|IQOO|iqoo|Mate|Pura|iPhone|nova|Flip|MIX|Civi|麦芒|Redmi|pocket)/ui', $model_clean, $m)) {
        $keywords[] = $m[1];
        $keyword_details[] = ['type' => '品牌', 'value' => $m[1]];
    }
    
    // 型号数字
    if (preg_match('/(\d+(?:Pro|Max|Ultra|Plus|Air|GT|e|S|X)?)/ui', $model_clean, $m)) {
        $keywords[] = $m[1];
        $keyword_details[] = ['type' => '型号数字', 'value' => $m[1]];
    }
    
    // 容量
    if (preg_match('/(\d+)(?:GB?|TB?|T|G)/ui', $model_clean, $m)) {
        $keywords[] = $m[1] . 'G';
        $keyword_details[] = ['type' => '容量', 'value' => $m[1] . 'G'];
    }
    
    // 检查关键词数量
    if (count($keywords) < 2) {
        $analysis_results['insufficient_keywords'][] = [
            'line' => $line_num + 1,
            'content' => $original_line,
            'model_clean' => $model_clean,
            'keywords' => $keywords,
            'keyword_details' => $keyword_details,
            'wholesale' => $wholesale,
            'official' => $official,
            'reason' => '关键词不足（需要至少2个：品牌、型号数字、容量）'
        ];
        continue;
    }
    
    // 构建SQL查询
    $sql = "SELECT id, brand, model, spec, price FROM mobile_phones WHERE 1=1";
    $conditions = [];
    $params = [];
    
    foreach ($keywords as $kw) {
        $conditions[] = "model LIKE ?";
        $params[] = "%{$kw}%";
    }
    
    $sql .= " AND (" . implode(" AND ", $conditions) . ") LIMIT 10";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results) > 0) {
            // 找到匹配
            $best_match = $results[0];
            $analysis_results['success'][] = [
                'line' => $line_num + 1,
                'content' => $original_line,
                'model_clean' => $model_clean,
                'keywords' => $keywords,
                'wholesale' => $wholesale,
                'official' => $official,
                'price_type' => $price_type,
                'matched_id' => $best_match['id'],
                'matched_model' => $best_match['model'],
                'matched_spec' => $best_match['spec'],
                'current_price' => $best_match['price']
            ];
        } else {
            // 未找到匹配，尝试模糊搜索
            $fuzzy_results = [];
            
            // 尝试只用品牌搜索
            if (count($keyword_details) > 0 && $keyword_details[0]['type'] == '品牌') {
                $brand = $keyword_details[0]['value'];
                $fuzzy_sql = "SELECT id, brand, model, spec, price FROM mobile_phones WHERE brand LIKE ? OR model LIKE ? LIMIT 5";
                $fuzzy_stmt = $conn->prepare($fuzzy_sql);
                $fuzzy_stmt->execute(["%{$brand}%", "%{$brand}%"]);
                $fuzzy_results = $fuzzy_stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $analysis_results['not_found_in_db'][] = [
                'line' => $line_num + 1,
                'content' => $original_line,
                'model_clean' => $model_clean,
                'keywords' => $keywords,
                'keyword_details' => $keyword_details,
                'wholesale' => $wholesale,
                'official' => $official,
                'price_type' => $price_type,
                'sql' => $sql,
                'sql_params' => $params,
                'similar_models' => $fuzzy_results,
                'reason' => '数据库中未找到匹配的型号'
            ];
        }
    } catch (Exception $e) {
        $analysis_results['not_found_in_db'][] = [
            'line' => $line_num + 1,
            'content' => $original_line,
            'model_clean' => $model_clean,
            'keywords' => $keywords,
            'error' => $e->getMessage(),
            'reason' => '查询执行错误'
        ];
    }
}

// 保存分析结果到JSON文件
$json_file = __DIR__ . '/price_sync_analysis.json';
file_put_contents($json_file, json_encode($analysis_results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "分析完成！结果已保存到: price_sync_analysis.json\n";
echo "统计信息：\n";
echo "- 成功匹配: " . count($analysis_results['success']) . " 条\n";
echo "- 无法提取价格格式: " . count($analysis_results['no_price_format']) . " 条\n";
echo "- 价格不合理: " . count($analysis_results['invalid_price']) . " 条\n";
echo "- 关键词不足: " . count($analysis_results['insufficient_keywords']) . " 条\n";
echo "- 数据库中未找到: " . count($analysis_results['not_found_in_db']) . " 条\n";
echo "\n请访问 view_price_analysis.php 查看可视化报告\n";
