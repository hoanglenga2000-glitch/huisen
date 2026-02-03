<?php
/**
 * 路由重定向到核心文件
 * 今日报价 → 产品列表页 (quotes_v6.php)
 */
chdir(__DIR__ . '/core');
require_once __DIR__ . '/core/quotes_v6.php';
?>