<?php
/**
 * ============================================
 * 报价数据导入脚本
 * ============================================
 * 功能：
 * 1. 解析SQL文件，提取手机报价数据
 * 2. 按品牌+型号分组（相同型号不同颜色合并为一个手机）
 * 3. 检查是否已存在，只导入新手机
 * 4. 创建mobile_phones、phone_details、phone_variants记录
 */

require_once 'config/config.php';

// 设置执行时间和内存限制
set_time_limit(0);
ini_set('memory_limit', '512M');

/**
 * 标准化型号名称
 * 去掉颜色、存储等后缀，只保留基础型号
 */
function normalizeModel($model) {
    $normalized = trim($model);
    
    // 去掉常见的颜色后缀（按长度从长到短排序，避免部分匹配）
    $colorSuffixes = [
        '群青色', '深青色', '沙漠色', '星光色', '午夜色',
        '薰衣草', '鼠尾草', '青雾', '星宇', '原色',
        '深', '浅', '天', '云', '色'
    ];
    
    // 去掉颜色后缀
    foreach ($colorSuffixes as $suffix) {
        $normalized = preg_replace('/' . preg_quote($suffix, '/') . '$/u', '', $normalized);
    }
    
    // 去掉末尾的存储容量后缀（如-256G, -512G, -1TG等）
    // 只匹配末尾的存储容量，避免误删型号中的数字
    $normalized = preg_replace('/[-_\s]*\d+[GT]B?[-_\s]*$/i', '', $normalized);
    
    // 清理末尾多余的连字符和空格
    $normalized = preg_replace('/[-_\s]+$/', '', $normalized);
    $normalized = trim($normalized);
    
    // 如果标准化后为空，返回原值
    if (empty($normalized)) {
        return $model;
    }
    
    return $normalized;
}

// 输出HTML格式的进度显示
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>报价数据导入</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #00f0ff; padding-bottom: 10px; }
        .log { background: #f9f9f9; padding: 15px; border-radius: 5px; max-height: 600px; overflow-y: auto; font-family: monospace; font-size: 12px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        .progress { background: #e9ecef; height: 30px; border-radius: 15px; margin: 20px 0; overflow: hidden; }
        .progress-bar { background: #00f0ff; height: 100%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-box { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #00f0ff; }
        .stat-box h3 { margin: 0 0 10px 0; font-size: 14px; color: #666; }
        .stat-box .number { font-size: 24px; font-weight: bold; color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 报价数据导入工具</h1>
        <div class="log" id="log">
<?php
flush();

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo '<div class="info">✓ 数据库连接成功</div>';
    flush();
    
    // 读取SQL文件
    $sqlFile = __DIR__ . '/sql/import_price_data.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL文件不存在: {$sqlFile}");
    }
    
    echo '<div class="info">✓ 开始解析SQL文件: ' . basename($sqlFile) . '</div>';
    flush();
    
    $content = file_get_contents($sqlFile);
    
    // 解析SQL文件，提取VALUES中的数据
    $rows = [];
    $lines = explode("\n", $content);
    $inValues = false;
    
    foreach ($lines as $lineNum => $line) {
        $line = trim($line);
        
        // 检测INSERT INTO语句
        if (stripos($line, 'INSERT INTO') !== false) {
            $inValues = true;
            continue;
        }
        
        // 如果不在VALUES区域，跳过
        if (!$inValues) {
            continue;
        }
        
        // 跳过空行和注释
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        // 匹配VALUES行：('值1', '值2', ...),
        if (preg_match("/^\((.+)\)[,;]/", $line, $matches)) {
            $rows[] = $matches[1];
        }
    }
    
    if (empty($rows)) {
        throw new Exception("无法解析SQL文件中的数据，请检查文件格式");
    }
    
    echo '<div class="success">✓ 解析到 ' . count($rows) . ' 条记录</div>';
    flush();
    
    // 解析每行数据
    $phones = [];
    $totalRows = count($rows);
    $parsedCount = 0;
    
    foreach ($rows as $row) {
        $parsedCount++;
        if ($parsedCount % 100 == 0) {
            echo '<div class="info">正在解析: ' . $parsedCount . '/' . $totalRows . '</div>';
            flush();
        }
        
        // 解析字段值（处理引号内的逗号）
        // 使用更精确的解析方法
        $values = [];
        $len = strlen($row);
        $i = 0;
        $currentValue = '';
        $inQuotes = false;
        $quoteChar = '';
        
        while ($i < $len) {
            $char = $row[$i];
            
            // 处理引号
            if (($char == "'" || $char == '"') && ($i == 0 || $row[$i-1] != '\\')) {
                if (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                } elseif ($char == $quoteChar) {
                    $inQuotes = false;
                    $quoteChar = '';
                }
                $i++;
                continue;
            }
            
            // 处理逗号（分隔符）
            if ($char == ',' && !$inQuotes) {
                $values[] = trim($currentValue);
                $currentValue = '';
                $i++;
                continue;
            }
            
            $currentValue .= $char;
            $i++;
        }
        
        // 添加最后一个值
        if ($currentValue !== '') {
            $values[] = trim($currentValue);
        }
        
        if (count($values) < 8) {
            continue; // 跳过格式不正确的行
        }
        
        // 提取字段值
        $brand = trim($values[0], "'\"");
        $model = trim($values[1], "'\"");
        $spec = trim($values[2], "'\"");
        $color = trim($values[3], "'\"");
        $price = floatval($values[4]);
        $retailPrice = !empty($values[5]) && $values[5] !== 'NULL' ? floatval($values[5]) : null;
        $condition = !empty($values[6]) && $values[6] !== 'NULL' ? trim($values[6], "'\"") : '全新未拆';
        $note = !empty($values[7]) && $values[7] !== 'NULL' ? trim($values[7], "'\"") : '';
        
        // 标准化型号名称（去掉颜色后缀）
        $normalizedModel = normalizeModel($model);
        
        // 生成唯一键
        $key = $brand . '|' . $normalizedModel;
        
        if (!isset($phones[$key])) {
            $phones[$key] = [
                'brand' => $brand,
                'model' => $normalizedModel,
                'original_models' => [],
                'variants' => [],
                'min_price' => $price,
                'max_price' => $price,
                'note' => $note,
                'condition' => $condition
            ];
        }
        
        // 记录原始型号（用于显示）
        if (!in_array($model, $phones[$key]['original_models'])) {
            $phones[$key]['original_models'][] = $model;
        }
        
        // 更新价格范围
        $phones[$key]['min_price'] = min($phones[$key]['min_price'], $price);
        $phones[$key]['max_price'] = max($phones[$key]['max_price'], $price);
        
        // 添加变体（颜色+存储配置）
        $variantKey = $spec . '|' . $color;
        if (!isset($phones[$key]['variants'][$variantKey])) {
            $phones[$key]['variants'][$variantKey] = [
                'storage' => $spec,
                'color' => $color,
                'price' => $price,
                'retail_price' => $retailPrice,
                'condition' => $condition,
                'note' => $note
            ];
        } else {
            // 如果已存在，取最低价格
            if ($price < $phones[$key]['variants'][$variantKey]['price']) {
                $phones[$key]['variants'][$variantKey]['price'] = $price;
            }
        }
    }
    
    echo '<div class="success">✓ 解析完成，共 ' . count($phones) . ' 个不同型号的手机</div>';
    flush();
    
    // 统计信息
    $stats = [
        'total' => count($phones),
        'new' => 0,
        'existing' => 0,
        'variants_added' => 0,
        'errors' => 0
    ];
    
    // 检查字段是否存在
    $checkNormalizedField = $db->fetchOne(
        "SELECT COUNT(*) as cnt FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'mobile_phones' 
         AND column_name = 'normalized_model'"
    );
    $useNormalizedField = isset($checkNormalizedField['cnt']) && $checkNormalizedField['cnt'] > 0;
    
    $checkDetailIdField = $db->fetchOne(
        "SELECT COUNT(*) as cnt FROM information_schema.columns 
         WHERE table_schema = DATABASE() 
         AND table_name = 'mobile_phones' 
         AND column_name = 'detail_id'"
    );
    $hasDetailIdField = isset($checkDetailIdField['cnt']) && $checkDetailIdField['cnt'] > 0;
    
    // 检查phone_details和phone_variants表是否存在
    $checkPhoneDetailsTable = $db->fetchOne(
        "SELECT COUNT(*) as cnt FROM information_schema.tables 
         WHERE table_schema = DATABASE() 
         AND table_name = 'phone_details'"
    );
    $hasPhoneDetailsTable = isset($checkPhoneDetailsTable['cnt']) && $checkPhoneDetailsTable['cnt'] > 0;
    
    $checkPhoneVariantsTable = $db->fetchOne(
        "SELECT COUNT(*) as cnt FROM information_schema.tables 
         WHERE table_schema = DATABASE() 
         AND table_name = 'phone_variants'"
    );
    $hasPhoneVariantsTable = isset($checkPhoneVariantsTable['cnt']) && $checkPhoneVariantsTable['cnt'] > 0;
    
    if ($useNormalizedField) {
        echo '<div class="info">✓ 检测到normalized_model字段，将使用标准化型号检查</div>';
    } else {
        echo '<div class="info">ℹ 未检测到normalized_model字段，将使用model字段检查</div>';
    }
    
    if ($hasDetailIdField && $hasPhoneDetailsTable && $hasPhoneVariantsTable) {
        echo '<div class="info">✓ 检测到detail_id字段和相关表，将创建详细记录</div>';
    } else {
        echo '<div class="warning">ℹ 未检测到detail_id字段或相关表，将只创建mobile_phones记录</div>';
    }
    flush();
    
    // 开始导入
    echo '<div class="info">开始导入数据...</div>';
    flush();
    
    $processed = 0;
    foreach ($phones as $key => $phone) {
        $processed++;
        $progress = round(($processed / count($phones)) * 100);
        
        echo '<div class="info">[' . $progress . '%] 处理: ' . htmlspecialchars($phone['brand']) . ' ' . htmlspecialchars($phone['model']) . '</div>';
        flush();
        
        try {
            // 检查是否已存在该手机（通过brand+model，因为model已经是标准化后的）
            if ($useNormalizedField) {
                // 使用normalized_model字段检查
                $selectFields = $hasDetailIdField ? "id, detail_id" : "id";
                $checkSql = "SELECT {$selectFields} FROM mobile_phones 
                            WHERE brand = ? AND (normalized_model = ? OR model = ?) 
                            LIMIT 1";
                $existing = $db->fetchOne($checkSql, [$phone['brand'], $phone['model'], $phone['model']]);
            } else {
                // 使用model字段检查（需要标准化比较）
                $selectFields = $hasDetailIdField ? "id, detail_id, model" : "id, model";
                $checkSql = "SELECT {$selectFields} FROM mobile_phones WHERE brand = ? LIMIT 100";
                $allPhones = $db->fetchAll($checkSql, [$phone['brand']]);
                $existing = null;
                foreach ($allPhones as $p) {
                    $existingNormalized = normalizeModel($p['model']);
                    if ($existingNormalized == $phone['model']) {
                        $existing = $p;
                        break;
                    }
                }
            }
            
            if ($existing) {
                $stats['existing']++;
                echo '<div class="warning">  → 已存在，更新价格并检查变体</div>';
                flush();
                
                // 更新价格为最低批发价
                $updatePriceSql = "UPDATE mobile_phones SET price = ? WHERE id = ?";
                $db->query($updatePriceSql, [$phone['min_price'], $existing['id']]);
                
                // 检查是否需要更新变体
                $phoneId = $existing['id'];
                $detailId = isset($existing['detail_id']) ? $existing['detail_id'] : null;
                
                if ($detailId && $hasPhoneVariantsTable) {
                    // 添加新的变体
                    foreach ($phone['variants'] as $variant) {
                        $checkVariantSql = "SELECT id FROM phone_variants WHERE detail_id = ? AND storage = ? AND color = ? LIMIT 1";
                        $existingVariant = $db->fetchOne($checkVariantSql, [$detailId, $variant['storage'], $variant['color']]);
                        
                        if (!$existingVariant) {
                            $db->insert('phone_variants', [
                                'detail_id' => $detailId,
                                'storage' => $variant['storage'],
                                'color' => $variant['color'],
                                'price' => $variant['price'], // 使用批发价
                                'retail_price' => $variant['retail_price'],
                                'stock_status' => '有货'
                            ]);
                            $stats['variants_added']++;
                        } else {
                            // 更新已有变体的价格为批发价
                            $updateVariantSql = "UPDATE phone_variants SET price = ? WHERE id = ?";
                            $db->query($updateVariantSql, [$variant['price'], $existingVariant['id']]);
                        }
                    }
                }
                
                continue;
            }
            
            // 创建mobile_phones记录（使用最低批发价）
            $phoneData = [
                'brand' => $phone['brand'],
                'model' => $phone['model'],
                'spec' => null, // 不再使用单个spec字段
                'price' => $phone['min_price'], // 使用最低批发价
                'note' => $phone['note'],
                'performance_score' => 50,
                'camera_score' => 50,
                'battery_score' => 50
            ];
            
            // 如果存在normalized_model字段，也设置它
            if ($useNormalizedField) {
                $phoneData['normalized_model'] = $phone['model'];
            }
            
            $phoneId = $db->insert('mobile_phones', $phoneData);
            $stats['new']++;
            
            // 如果存在phone_details和phone_variants表，创建详细记录
            if ($hasPhoneDetailsTable && $hasPhoneVariantsTable) {
                // 收集所有存储选项和颜色选项
                $storageOptions = [];
                $colorOptions = [];
                foreach ($phone['variants'] as $variant) {
                    if (!empty($variant['storage']) && !in_array($variant['storage'], $storageOptions)) {
                        $storageOptions[] = $variant['storage'];
                    }
                    if (!empty($variant['color'])) {
                        $colorFound = false;
                        foreach ($colorOptions as $co) {
                            if ($co['name'] == $variant['color']) {
                                $colorFound = true;
                                break;
                            }
                        }
                        if (!$colorFound) {
                            $colorOptions[] = ['name' => $variant['color'], 'hex' => null];
                        }
                    }
                }
                
                // 创建phone_details记录
                $detailData = [
                    'phone_id' => $phoneId,
                    'brand' => $phone['brand'],
                    'model' => $phone['model'],
                    'full_name' => $phone['brand'] . ' ' . $phone['model'],
                    'storage_options' => !empty($storageOptions) ? json_encode($storageOptions, JSON_UNESCAPED_UNICODE) : null,
                    'color_options' => !empty($colorOptions) ? json_encode($colorOptions, JSON_UNESCAPED_UNICODE) : null,
                    'min_price' => $phone['min_price'], // 使用批发价
                    'max_price' => $phone['max_price'], // 使用批发价
                    'source' => '兰州八方报价',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $detailId = $db->insert('phone_details', $detailData);
                
                // 如果存在detail_id字段，更新mobile_phones的detail_id
                if ($hasDetailIdField) {
                    $db->query("UPDATE mobile_phones SET detail_id = ? WHERE id = ?", [$detailId, $phoneId]);
                }
                
                // 创建phone_variants记录（使用批发价）
                foreach ($phone['variants'] as $variant) {
                    $variantData = [
                        'detail_id' => $detailId,
                        'storage' => $variant['storage'],
                        'color' => $variant['color'],
                        'price' => $variant['price'], // 使用批发价
                        'retail_price' => $variant['retail_price'],
                        'stock_status' => '有货'
                    ];
                    
                    $db->insert('phone_variants', $variantData);
                    $stats['variants_added']++;
                }
            }
            
            echo '<div class="success">  → 导入成功 (ID: ' . $phoneId . ', 变体: ' . count($phone['variants']) . ')</div>';
            flush();
            
        } catch (Exception $e) {
            $stats['errors']++;
            echo '<div class="error">  → 导入失败: ' . htmlspecialchars($e->getMessage()) . '</div>';
            flush();
        }
    }
    
    // 检查并更新已有数据的价格（确保使用批发价）
    echo '<div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ddd;">';
    echo '<h2>检查并更新已有数据价格</h2>';
    flush();
    
    try {
        // 从mobile_quotes表获取最新的批发价，更新mobile_phones表
        $updatePriceSql = "
            UPDATE mobile_phones mp
            INNER JOIN (
                SELECT brand, model, MIN(price) as min_price
                FROM mobile_quotes
                GROUP BY brand, model
            ) mq ON mp.brand = mq.brand AND mp.model = mq.model
            SET mp.price = mq.min_price
            WHERE mp.price != mq.min_price
        ";
        
        $stmt = $pdo->prepare($updatePriceSql);
        $stmt->execute();
        $updatedPhones = $stmt->rowCount();
        
        echo '<div class="success">✓ 已更新 ' . $updatedPhones . ' 个手机的价格为批发价</div>';
        flush();
        
        // 如果有phone_variants表，也更新变体价格
        if ($hasPhoneVariantsTable) {
            $updateVariantsSql = "
                UPDATE phone_variants pv
                INNER JOIN phone_details pd ON pv.detail_id = pd.id
                INNER JOIN mobile_quotes mq ON pd.brand = mq.brand 
                    AND pd.model = mq.model 
                    AND pv.storage = mq.spec 
                    AND (pv.color = mq.color OR (pv.color IS NULL AND mq.color = ''))
                SET pv.price = mq.price
                WHERE pv.price != mq.price
            ";
            
            $stmt = $pdo->prepare($updateVariantsSql);
            $stmt->execute();
            $updatedVariants = $stmt->rowCount();
            
            echo '<div class="success">✓ 已更新 ' . $updatedVariants . ' 个变体的价格为批发价</div>';
            flush();
        }
        
    } catch (Exception $e) {
        echo '<div class="warning">⚠ 价格更新检查时出错: ' . htmlspecialchars($e->getMessage()) . '</div>';
        flush();
    }
    
    // 显示统计信息
    echo '<div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ddd;">';
    echo '<h2>导入完成统计</h2>';
    echo '<div class="stats">';
    echo '<div class="stat-box"><h3>总型号数</h3><div class="number">' . $stats['total'] . '</div></div>';
    echo '<div class="stat-box"><h3>新增手机</h3><div class="number" style="color: #28a745;">' . $stats['new'] . '</div></div>';
    echo '<div class="stat-box"><h3>已存在</h3><div class="number" style="color: #ffc107;">' . $stats['existing'] . '</div></div>';
    echo '<div class="stat-box"><h3>新增变体</h3><div class="number" style="color: #17a2b8;">' . $stats['variants_added'] . '</div></div>';
    echo '<div class="stat-box"><h3>错误数</h3><div class="number" style="color: #dc3545;">' . $stats['errors'] . '</div></div>';
    echo '</div>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="error">❌ 错误: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<div class="error">堆栈跟踪: <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></div>';
}
?>

        </div>
    </div>
</body>
</html>
