<?php
/**
 * ============================================
 * 智能图片匹配函数
 * 从 images/phones 文件夹中匹配真实手机图片
 * ============================================
 */

/**
 * 获取手机图片路径
 * @param string $brand 品牌名称
 * @param string $model 型号名称
 * @param string|null $image_url 数据库中的 image_url（如果存在）
 * @return array ['image_src' => 图片路径, 'is_brand_logo' => 是否为品牌logo]
 */
function getPhoneImage($brand, $model, $image_url = null) {
    // 品牌键映射
    $brand_key_map = [
        '苹果' => 'apple', 'Apple' => 'apple', 'iPhone' => 'apple',
        '华为' => 'huawei', 'Huawei' => 'huawei',
        '荣耀' => 'honor', 'HONOR' => 'honor',
        'OPPO' => 'oppo', 'Oppo' => 'oppo',
        'Vivo' => 'vivo', 'vivo' => 'vivo',
        '小米' => 'xiaomi', 'Xiaomi' => 'xiaomi', 'Redmi' => 'xiaomi', '红米' => 'xiaomi',
        '三星' => 'samsung', 'Samsung' => 'samsung',
    ];
    $brand_key = $brand_key_map[$brand] ?? 'other';
    
    // 1. 优先使用数据库中的 image_url
    if (!empty($image_url)) {
        $image_src = $image_url;
        // 处理相对路径
        if (strpos($image_src, 'http://') !== 0 && 
            strpos($image_src, 'https://') !== 0 && 
            strpos($image_src, '//') !== 0 &&
            strpos($image_src, '/') !== 0) {
            $image_src = 'images/phones/' . $image_src;
        }
        // 检查文件是否存在
        if (file_exists(__DIR__ . '/../' . $image_src)) {
            return ['image_src' => $image_src, 'is_brand_logo' => false];
        }
    }
    
    // 2. 从 images/phones 文件夹中智能匹配
    $phones_dir = __DIR__ . '/../images/phones/';
    if (is_dir($phones_dir)) {
        $matched_image = findMatchingImage($phones_dir, $brand, $model);
        if ($matched_image) {
            return ['image_src' => 'images/phones/' . $matched_image, 'is_brand_logo' => false];
        }
    }
    
    // 3. 匹配不上，使用品牌 logo
    $brand_logo_path = 'images/brands/brand_' . $brand_key . '.png';
    $brand_logo_full_path = __DIR__ . '/../' . $brand_logo_path;
    
    if (file_exists($brand_logo_full_path)) {
        return ['image_src' => $brand_logo_path, 'is_brand_logo' => true];
    }
    
    // 4. 品牌 logo 也不存在，返回 null（前端会使用 SVG 占位符）
    return ['image_src' => null, 'is_brand_logo' => false];
}

/**
 * 在文件夹中查找匹配的图片
 * @param string $dir 文件夹路径
 * @param string $brand 品牌
 * @param string $model 型号
 * @return string|null 匹配的图片文件名
 */
function findMatchingImage($dir, $brand, $model) {
    // 清理品牌和型号名称
    $clean_brand = normalizeBrand($brand);
    $clean_model = normalizeModel($model);
    
    // 获取所有图片文件
    $files = glob($dir . '*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
    if (empty($files)) {
        return null;
    }
    
    // 提取文件名（不含路径）
    $filenames = array_map(function($file) {
        return basename($file);
    }, $files);
    
    // 匹配策略（按优先级排序）
    $match_patterns = [
        // 1. 精确匹配：品牌_型号（忽略大小写和特殊字符）
        function($filename) use ($clean_brand, $clean_model) {
            $filename_lower = mb_strtolower($filename);
            $pattern = mb_strtolower($clean_brand . '_' . $clean_model);
            $pattern = preg_replace('/[^a-z0-9_]/', '', $pattern);
            $filename_clean = preg_replace('/[^a-z0-9_]/', '', $filename_lower);
            return strpos($filename_clean, $pattern) !== false;
        },
        
        // 2. 品牌匹配 + 型号部分匹配
        function($filename) use ($clean_brand, $clean_model) {
            $filename_lower = mb_strtolower($filename);
            $brand_pattern = mb_strtolower($clean_brand);
            $brand_pattern = preg_replace('/[^a-z0-9]/', '', $brand_pattern);
            $filename_clean = preg_replace('/[^a-z0-9_]/', '', $filename_lower);
            
            if (strpos($filename_clean, $brand_pattern) === false) {
                return false;
            }
            
            // 提取型号关键词
            $model_keywords = extractModelKeywords($clean_model);
            foreach ($model_keywords as $keyword) {
                if (strpos($filename_clean, mb_strtolower($keyword)) !== false) {
                    return true;
                }
            }
            return false;
        },
        
        // 3. 只匹配品牌（作为最后备选）
        function($filename) use ($clean_brand) {
            $filename_lower = mb_strtolower($filename);
            $brand_pattern = mb_strtolower($clean_brand);
            $brand_pattern = preg_replace('/[^a-z0-9]/', '', $brand_pattern);
            $filename_clean = preg_replace('/[^a-z0-9_]/', '', $filename_lower);
            return strpos($filename_clean, $brand_pattern) !== false;
        },
    ];
    
    // 按优先级尝试匹配
    foreach ($match_patterns as $pattern_func) {
        foreach ($filenames as $filename) {
            if ($pattern_func($filename)) {
                return $filename;
            }
        }
    }
    
    return null;
}

/**
 * 标准化品牌名称
 */
function normalizeBrand($brand) {
    $brand = trim($brand);
    // 移除常见后缀
    $brand = preg_replace('/\s*手机\s*$/i', '', $brand);
    return $brand;
}

/**
 * 标准化型号名称
 */
function normalizeModel($model) {
    $model = trim($model);
    // 移除常见前缀
    $model = preg_replace('/^(iPhone|iP|华为|Huawei|小米|Xiaomi|Redmi|红米|OPPO|Vivo|vivo|三星|Samsung|荣耀|Honor)\s*/i', '', $model);
    // 移除规格信息（如 256G、512G等）
    $model = preg_replace('/\s*\d+[GBgb]+\s*/', '', $model);
    // 移除颜色信息（常见颜色词）
    $colors = ['黑', '白', '金', '银', '蓝', '红', '绿', '紫', '灰', '粉', '橙', '黄'];
    foreach ($colors as $color) {
        $model = preg_replace('/\s*' . $color . '\s*/', '', $model);
    }
    // 移除特殊字符，保留字母数字和常见符号
    $model = preg_replace('/[^\w\s\-+]/u', '', $model);
    return trim($model);
}

/**
 * 提取型号关键词
 */
function extractModelKeywords($model) {
    $keywords = [];
    
    // 提取数字序列（如 15、16、X8等）
    preg_match_all('/\d+/', $model, $numbers);
    $keywords = array_merge($keywords, $numbers[0]);
    
    // 提取字母序列（如 Pro、Max、Ultra等）
    preg_match_all('/[A-Z][a-z]+/', $model, $words);
    $keywords = array_merge($keywords, $words[0]);
    
    // 提取常见型号关键词
    $common_keywords = ['Pro', 'Max', 'Ultra', 'Plus', 'Mini', 'SE', 'X', 'Note', 'Find', 'Reno', 'Mate', 'Nova', 'P', 'S'];
    foreach ($common_keywords as $keyword) {
        if (stripos($model, $keyword) !== false) {
            $keywords[] = $keyword;
        }
    }
    
    return array_unique($keywords);
}
