<?php
/**
 * 品牌分类工具
 * 用于区分手机品牌和其他产品品牌
 */

// 定义手机品牌（真正的手机品牌）
$phone_brands = [
    '苹果', 'Apple', 'iPhone',
    '华为', 'Huawei', 'Mate', 'Pura',
    '小米', 'Xiaomi',
    '荣耀', 'Honor',
    'VIVO', 'vivo',
    'OPPO', 'Oppo',
    '三星', 'Samsung',
    '红米', 'Redmi',
    'IQOO', 'iqoo',
    '一加',
    'Realme', 'realme',
    '摩托罗拉'
];

/**
 * 检查品牌是否为手机品牌
 */
function is_phone_brand($brand) {
    global $phone_brands;
    
    foreach ($phone_brands as $pb) {
        if (stripos($brand, $pb) !== false || stripos($pb, $brand) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * 生成品牌专区
 * 返回: array('phone_zones' => [], 'other_zone' => [])
 */
function generate_brand_zones($brand_counts) {
    $brand_colors = [
        '苹果' => '#000000',
        'Apple' => '#000000',
        '华为' => '#c8102e',
        'Huawei' => '#c8102e',
        '小米' => '#ff6700',
        'Xiaomi' => '#ff6700',
        'OPPO' => '#00a862',
        'vivo' => '#0084ff',
        'VIVO' => '#0084ff',
        '荣耀' => '#e60012',
        'Honor' => '#e60012',
        '三星' => '#0a78f4',
        'Samsung' => '#0a78f4',
        '红米' => '#ff6700',
        'Redmi' => '#ff6700',
    ];
    
    $phone_zones = [];
    $other_brands = [];
    
    foreach ($brand_counts as $bc) {
        if (is_phone_brand($bc['brand'])) {
            // 手机品牌：数量>=30的独立成区
            if ($bc['count'] >= 30) {
                $phone_zones[] = [
                    'name' => $bc['brand'],
                    'count' => $bc['count'],
                    'color' => $brand_colors[$bc['brand']] ?? '#666666',
                    'is_single' => true
                ];
            }
        } else {
            // 非手机品牌：归入"其他产品"
            $other_brands[] = $bc;
        }
    }
    
    // 添加"其他产品"专区
    $other_zone = null;
    if (count($other_brands) > 0) {
        $other_count = array_sum(array_column($other_brands, 'count'));
        $other_zone = [
            'name' => '其他产品',
            'count' => $other_count,
            'color' => '#888888',
            'is_single' => false,
            'brands' => array_column($other_brands, 'brand')
        ];
    }
    
    return [
        'phone_zones' => $phone_zones,
        'other_zone' => $other_zone
    ];
}
