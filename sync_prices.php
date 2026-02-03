<?php
/**
 * ==========================================
 * 价格数据同步脚本 V4 - 智能价格解析
 * ==========================================
 * 
 * 规则：
 * 1. 偶数位：从中间对半分 (批发价|官网价)
 *    例: 59105999 → 5910 | 5999
 * 
 * 2. 奇数位且以0结尾：去掉最后的0 = 官网价，批发价 = 官网价 * 0.98
 *    例: 75300 → 7530(官网价) → 7379(批发价)
 */

require_once 'config/config.php';

$file_path = __DIR__ . '/资料.txt';
if (!file_exists($file_path)) {
    die("错误：找不到 资料.txt 文件\n");
}

$content = file_get_contents($file_path);
$lines = explode("\n", $content);

echo "开始处理价格数据（智能解析）...\n\n";

// 连接数据库
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 添加 official_price 列
    $check = $conn->query("SHOW COLUMNS FROM mobile_phones LIKE 'official_price'");
    if ($check->rowCount() == 0) {
        $conn->exec("ALTER TABLE mobile_phones ADD COLUMN official_price INT DEFAULT NULL COMMENT '官网价格'");
        echo "✓ 添加 official_price 列\n\n";
    }
} catch (Exception $e) {
    die("数据库错误: " . $e->getMessage());
}

$stats = ['processed' => 0, 'updated' => 0, 'not_found' => 0, 'odd_prices' => 0, 'even_prices' => 0];

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
    
    // 提取数字价格（最后的连续数字）
    if (!preg_match('/^(.+?)(\d{4,})$/u', $line, $matches)) {
        continue;
    }
    
    $model_raw = trim($matches[1]);
    $prices_str = $matches[2];
    $total_len = strlen($prices_str);
    
    $wholesale = 0;
    $official = 0;
    $price_type = '';
    
    // ============================================
    // 智能价格解析
    // ============================================
    
    if ($total_len % 2 == 0) {
        // 偶数位：从中间对半分
        $half = $total_len / 2;
        $wholesale = (int)substr($prices_str, 0, $half);
        $official = (int)substr($prices_str, $half);
        $price_type = '偶数位中间分割';
        $stats['even_prices']++;
        
        // 如果批发价和官网价相同，批发价打98折
        if ($wholesale == $official) {
            $wholesale = (int)($wholesale * 0.98);
            $price_type .= ' (相同价格98折)';
        }
        // 如果批发价大于官网价，修正为98折
        else if ($wholesale > $official) {
            $wholesale = (int)($official * 0.98);
            $price_type .= ' (修正为98折)';
        }
        // 如果批发价太低或太高，修正为98折
        else if ($wholesale < $official * 0.70 || $wholesale > $official * 0.95) {
            $wholesale = (int)($official * 0.98);
            $price_type .= ' (异常折扣修正为98折)';
        }
        
    } else if ($total_len % 2 == 1 && substr($prices_str, -1) == '0') {
        // 奇数位且以0结尾：去掉最后的0 = 官网价
        $official = (int)substr($prices_str, 0, -1);
        $wholesale = (int)($official * 0.98);
        $price_type = '奇数位去尾0';
        $stats['odd_prices']++;
        
    } else {
        // 其他情况跳过
        continue;
    }
    
    // 最终价格合理性检查
    if ($wholesale < 100 || $wholesale > 200000 || $official < 100 || $official > 200000) {
        continue;
    }
    
    // 清理型号
    $model_clean = preg_replace('/\([A-Z0-9\/\-]+\)/u', '', $model_raw);
    $model_clean = preg_replace('/原封|省外|不管控|拆封未激活/u', '', $model_clean);
    $model_clean = trim($model_clean);
    
    $stats['processed']++;
    
    // 提取关键词匹配数据库
    $keywords = [];
    
    // 品牌
    if (preg_match('/(苹果|华为|小米|荣耀|VIVO|vivo|OPPO|三星|红米|IQOO|iqoo|Mate|Pura|iPhone|nova|Flip|MIX|Civi|麦芒|Redmi|pocket)/ui', $model_clean, $m)) {
        $keywords[] = $m[1];
    }
    
    // 型号数字
    if (preg_match('/(\d+(?:Pro|Max|Ultra|Plus|Air|GT|e|S|X)?)/ui', $model_clean, $m)) {
        $keywords[] = $m[1];
    }
    
    // 容量
    if (preg_match('/(\d+)(?:GB?|TB?|T|G)/ui', $model_clean, $m)) {
        $keywords[] = $m[1] . 'G';
    }
    
    // 至少需要2个关键词才能匹配
    if (count($keywords) < 2) {
        continue;
    }
    
    // 构建SQL
    $sql = "SELECT id, model, price FROM mobile_phones WHERE 1=1";
    $conditions = [];
    $params = [];
    
    foreach ($keywords as $kw) {
        $conditions[] = "model LIKE ?";
        $params[] = "%{$kw}%";
    }
    
    $sql .= " AND (" . implode(" AND ", $conditions) . ") LIMIT 1";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // 更新价格
            $update = $conn->prepare("UPDATE mobile_phones SET price = ?, official_price = ? WHERE id = ?");
            $update->execute([$wholesale, $official, $result['id']]);
            
            echo sprintf(
                "✓ %-30s | 批发¥%-6s 官网¥%-6s [%s]\n",
                mb_substr($model_clean, 0, 25),
                number_format($wholesale),
                number_format($official),
                $price_type
            );
            
            $stats['updated']++;
        } else {
            $stats['not_found']++;
        }
    } catch (Exception $e) {
        // 忽略错误
    }
}

// 统计报告
echo "\n" . str_repeat("=", 70) . "\n";
echo "完成！\n";
echo "解析数据: {$stats['processed']} 条\n";
echo "成功更新: {$stats['updated']} 条\n";
echo "偶数位价格: {$stats['even_prices']} 条\n";
echo "奇数位价格: {$stats['odd_prices']} 条\n";
echo "未匹配: {$stats['not_found']} 条\n";
echo str_repeat("=", 70) . "\n";
echo "\n✓ 价格更新完成！请刷新网站查看。\n";
