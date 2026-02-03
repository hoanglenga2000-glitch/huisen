<?php
/**
 * ==========================================
 * 价格同步分析可视化页面
 * ==========================================
 */

$json_file = __DIR__ . '/price_sync_analysis.json';
if (!file_exists($json_file)) {
    die("请先运行 analyze_price_sync.php 生成分析数据");
}

$analysis_data = json_decode(file_get_contents($json_file), true);
if (!$analysis_data) {
    die("分析数据文件格式错误");
}

$stats = [
    'total' => 0,
    'success' => count($analysis_data['success'] ?? []),
    'no_price_format' => count($analysis_data['no_price_format'] ?? []),
    'invalid_price' => count($analysis_data['invalid_price'] ?? []),
    'insufficient_keywords' => count($analysis_data['insufficient_keywords'] ?? []),
    'not_found_in_db' => count($analysis_data['not_found_in_db'] ?? [])
];

$stats['total'] = array_sum($stats) - $stats['total'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>价格同步分析报告</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Microsoft YaHei', sans-serif;
            background: #f5f7fa;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card.success { border-left: 4px solid #27ae60; }
        .stat-card.error { border-left: 4px solid #e74c3c; }
        .stat-card.warning { border-left: 4px solid #f39c12; }
        .stat-card.info { border-left: 4px solid #3498db; }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .tabs {
            display: flex;
            background: white;
            border-radius: 8px 8px 0 0;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .tab {
            flex: 1;
            padding: 15px 20px;
            background: #ecf0f1;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .tab:hover {
            background: #d5dbdb;
        }
        
        .tab.active {
            background: white;
            border-bottom-color: #3498db;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
            background: white;
            padding: 20px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .data-table th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tr:nth-child(even) {
            background: #fafbfc;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-error { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        .keyword-tag {
            display: inline-block;
            padding: 2px 6px;
            margin: 2px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .price {
            font-weight: bold;
            color: #27ae60;
        }
        
        .scrollable {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #95a5a6;
        }
        
        .reason-box {
            background: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin: 5px 0;
            font-size: 12px;
        }
        
        .similar-models {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .similar-models h4 {
            font-size: 12px;
            margin-bottom: 8px;
            color: #495057;
        }
        
        .similar-model-item {
            padding: 5px;
            font-size: 11px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 价格同步分析报告</h1>
        
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-label">成功匹配</div>
                <div class="stat-number"><?= $stats['success'] ?></div>
                <div class="stat-label"><?= $stats['total'] > 0 ? round($stats['success'] / $stats['total'] * 100, 1) : 0 ?>%</div>
            </div>
            <div class="stat-card error">
                <div class="stat-label">数据库未找到</div>
                <div class="stat-number"><?= $stats['not_found_in_db'] ?></div>
                <div class="stat-label"><?= $stats['total'] > 0 ? round($stats['not_found_in_db'] / $stats['total'] * 100, 1) : 0 ?>%</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-label">关键词不足</div>
                <div class="stat-number"><?= $stats['insufficient_keywords'] ?></div>
                <div class="stat-label"><?= $stats['total'] > 0 ? round($stats['insufficient_keywords'] / $stats['total'] * 100, 1) : 0 ?>%</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-label">价格格式错误</div>
                <div class="stat-number"><?= $stats['no_price_format'] ?></div>
                <div class="stat-label"><?= $stats['total'] > 0 ? round($stats['no_price_format'] / $stats['total'] * 100, 1) : 0 ?>%</div>
            </div>
            <div class="stat-card info">
                <div class="stat-label">价格不合理</div>
                <div class="stat-number"><?= $stats['invalid_price'] ?></div>
                <div class="stat-label"><?= $stats['total'] > 0 ? round($stats['invalid_price'] / $stats['total'] * 100, 1) : 0 ?>%</div>
            </div>
            <div class="stat-card info">
                <div class="stat-label">总计</div>
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">100%</div>
            </div>
        </div>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('success')">✅ 成功匹配 (<?= $stats['success'] ?>)</button>
            <button class="tab" onclick="showTab('not_found')">❌ 数据库未找到 (<?= $stats['not_found_in_db'] ?>)</button>
            <button class="tab" onclick="showTab('insufficient')">⚠️ 关键词不足 (<?= $stats['insufficient_keywords'] ?>)</button>
            <button class="tab" onclick="showTab('no_format')">📝 价格格式错误 (<?= $stats['no_price_format'] ?>)</button>
            <button class="tab" onclick="showTab('invalid')">💰 价格不合理 (<?= $stats['invalid_price'] ?>)</button>
        </div>
        
        <!-- 成功匹配 -->
        <div id="tab-success" class="tab-content active">
            <div class="search-box">
                <input type="text" id="search-success" placeholder="搜索型号、关键词..." onkeyup="filterTable('success-table', this.value)">
            </div>
            <div class="scrollable">
                <table class="data-table" id="success-table">
                    <thead>
                        <tr>
                            <th>行号</th>
                            <th>原始内容</th>
                            <th>清理后型号</th>
                            <th>关键词</th>
                            <th>批发价</th>
                            <th>官网价</th>
                            <th>匹配到的型号</th>
                            <th>当前价格</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($analysis_data['success'])): ?>
                            <tr><td colspan="8" class="empty-state">暂无数据</td></tr>
                        <?php else: ?>
                            <?php foreach ($analysis_data['success'] as $item): ?>
                                <tr>
                                    <td><?= $item['line'] ?></td>
                                    <td><?= htmlspecialchars(mb_substr($item['content'], 0, 50)) ?></td>
                                    <td><?= htmlspecialchars($item['model_clean']) ?></td>
                                    <td>
                                        <?php foreach ($item['keywords'] as $kw): ?>
                                            <span class="keyword-tag"><?= htmlspecialchars($kw) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td class="price">¥<?= number_format($item['wholesale']) ?></td>
                                    <td class="price">¥<?= number_format($item['official']) ?></td>
                                    <td><?= htmlspecialchars($item['matched_model']) ?></td>
                                    <td>¥<?= number_format($item['current_price'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- 数据库未找到 -->
        <div id="tab-not_found" class="tab-content">
            <div class="search-box">
                <input type="text" id="search-not_found" placeholder="搜索型号、关键词..." onkeyup="filterTable('not_found-table', this.value)">
            </div>
            <div class="scrollable">
                <table class="data-table" id="not_found-table">
                    <thead>
                        <tr>
                            <th>行号</th>
                            <th>原始内容</th>
                            <th>清理后型号</th>
                            <th>关键词</th>
                            <th>批发价</th>
                            <th>官网价</th>
                            <th>相似型号</th>
                            <th>原因</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($analysis_data['not_found_in_db'])): ?>
                            <tr><td colspan="8" class="empty-state">暂无数据</td></tr>
                        <?php else: ?>
                            <?php foreach ($analysis_data['not_found_in_db'] as $item): ?>
                                <tr>
                                    <td><?= $item['line'] ?></td>
                                    <td><?= htmlspecialchars(mb_substr($item['content'], 0, 50)) ?></td>
                                    <td><?= htmlspecialchars($item['model_clean']) ?></td>
                                    <td>
                                        <?php foreach ($item['keywords'] as $kw): ?>
                                            <span class="keyword-tag"><?= htmlspecialchars($kw) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td class="price">¥<?= number_format($item['wholesale']) ?></td>
                                    <td class="price">¥<?= number_format($item['official']) ?></td>
                                    <td>
                                        <?php if (!empty($item['similar_models'])): ?>
                                            <div class="similar-models">
                                                <h4>相似型号：</h4>
                                                <?php foreach (array_slice($item['similar_models'], 0, 3) as $similar): ?>
                                                    <div class="similar-model-item">
                                                        <?= htmlspecialchars($similar['model']) ?> 
                                                        (<?= htmlspecialchars($similar['spec'] ?? '') ?>)
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #95a5a6;">无相似型号</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="reason-box"><?= htmlspecialchars($item['reason']) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- 关键词不足 -->
        <div id="tab-insufficient" class="tab-content">
            <div class="search-box">
                <input type="text" id="search-insufficient" placeholder="搜索型号、关键词..." onkeyup="filterTable('insufficient-table', this.value)">
            </div>
            <div class="scrollable">
                <table class="data-table" id="insufficient-table">
                    <thead>
                        <tr>
                            <th>行号</th>
                            <th>原始内容</th>
                            <th>清理后型号</th>
                            <th>已提取关键词</th>
                            <th>批发价</th>
                            <th>官网价</th>
                            <th>原因</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($analysis_data['insufficient_keywords'])): ?>
                            <tr><td colspan="7" class="empty-state">暂无数据</td></tr>
                        <?php else: ?>
                            <?php foreach ($analysis_data['insufficient_keywords'] as $item): ?>
                                <tr>
                                    <td><?= $item['line'] ?></td>
                                    <td><?= htmlspecialchars(mb_substr($item['content'], 0, 50)) ?></td>
                                    <td><?= htmlspecialchars($item['model_clean']) ?></td>
                                    <td>
                                        <?php if (!empty($item['keyword_details'])): ?>
                                            <?php foreach ($item['keyword_details'] as $kw): ?>
                                                <span class="keyword-tag"><?= htmlspecialchars($kw['type']) ?>: <?= htmlspecialchars($kw['value']) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span style="color: #95a5a6;">无关键词</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="price">¥<?= number_format($item['wholesale']) ?></td>
                                    <td class="price">¥<?= number_format($item['official']) ?></td>
                                    <td>
                                        <div class="reason-box"><?= htmlspecialchars($item['reason']) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- 价格格式错误 -->
        <div id="tab-no_format" class="tab-content">
            <div class="search-box">
                <input type="text" id="search-no_format" placeholder="搜索内容..." onkeyup="filterTable('no_format-table', this.value)">
            </div>
            <div class="scrollable">
                <table class="data-table" id="no_format-table">
                    <thead>
                        <tr>
                            <th>行号</th>
                            <th>原始内容</th>
                            <th>原因</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($analysis_data['no_price_format'])): ?>
                            <tr><td colspan="3" class="empty-state">暂无数据</td></tr>
                        <?php else: ?>
                            <?php foreach ($analysis_data['no_price_format'] as $item): ?>
                                <tr>
                                    <td><?= $item['line'] ?></td>
                                    <td><?= htmlspecialchars($item['content']) ?></td>
                                    <td>
                                        <div class="reason-box"><?= htmlspecialchars($item['reason']) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- 价格不合理 -->
        <div id="tab-invalid" class="tab-content">
            <div class="search-box">
                <input type="text" id="search-invalid" placeholder="搜索内容..." onkeyup="filterTable('invalid-table', this.value)">
            </div>
            <div class="scrollable">
                <table class="data-table" id="invalid-table">
                    <thead>
                        <tr>
                            <th>行号</th>
                            <th>原始内容</th>
                            <th>批发价</th>
                            <th>官网价</th>
                            <th>原因</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($analysis_data['invalid_price'])): ?>
                            <tr><td colspan="5" class="empty-state">暂无数据</td></tr>
                        <?php else: ?>
                            <?php foreach ($analysis_data['invalid_price'] as $item): ?>
                                <tr>
                                    <td><?= $item['line'] ?></td>
                                    <td><?= htmlspecialchars($item['content']) ?></td>
                                    <td class="price">¥<?= number_format($item['wholesale']) ?></td>
                                    <td class="price">¥<?= number_format($item['official']) ?></td>
                                    <td>
                                        <div class="reason-box"><?= htmlspecialchars($item['reason']) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // 隐藏所有标签页内容
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // 移除所有标签的active类
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // 显示选中的标签页
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function filterTable(tableId, searchValue) {
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tr');
            const searchLower = searchValue.toLowerCase();
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchLower) ? '' : 'none';
            }
        }
    </script>
</body>
</html>
