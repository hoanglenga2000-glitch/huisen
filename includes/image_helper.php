<?php
/**
 * ==========================================
 * 汇森科技 - 图片智能助手 v1.0
 * ==========================================
 *
 * 功能：
 * 1. 智能匹配产品图片
 * 2. 确保每张图片只使用一次
 * 3. 提供懒加载支持
 * 4. 自动选择最佳图片
 */

class ImageHelper {
    private static $used_images = []; // 记录已使用的图片
    private static $image_cache = null;
    private static $base_path = '';

    /**
     * 初始化
     */
    public static function init($base_path = '') {
        self::$base_path = $base_path;
        self::$used_images = [];
    }

    /**
     * 获取产品主图（只返回未使用的图片）
     */
    public static function getProductImage($brand, $model, $color = null, $fallback = true) {
        $image = self::findBestImage($brand, $model, $color);

        if ($image && !in_array($image, self::$used_images)) {
            self::$used_images[] = $image;
            return self::$base_path . $image;
        }

        // 如果没找到或已使用，返回占位图
        if ($fallback) {
            return self::getPlaceholder($brand);
        }

        return null;
    }

    /**
     * 查找最佳匹配图片
     */
    private static function findBestImage($brand, $model, $color = null) {
        // 品牌目录映射
        $brand_dirs = [
            'Apple' => ['苹果', 'Apple'],
            'Huawei' => ['华为', 'Huawei'],
            'Xiaomi' => ['小米', 'Xiaomi'],
            'Honor' => ['荣耀', 'Honor'],
            'OPPO' => ['Oppo', 'OPPO'],
            'vivo' => ['Vivo', 'vivo'],
            'Samsung' => ['三星', 'Samsung'],
            'Redmi' => ['红米', 'Redmi'],
            'OnePlus' => ['一加', 'OnePlus'],
        ];

        $base_dir = dirname(__DIR__) . '/images/图片素材/';

        // 获取品牌对应的目录名
        $dirs_to_check = $brand_dirs[$brand] ?? [$brand];

        foreach ($dirs_to_check as $dir_name) {
            $brand_dir = $base_dir . $dir_name . '/';
            if (!is_dir($brand_dir)) continue;

            // 扫描目录查找匹配的图片
            $files = scandir($brand_dir);
            $matches = [];

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (!preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) continue;

                // 计算匹配分数
                $score = self::calculateMatchScore($file, $model, $color);
                if ($score > 0) {
                    $matches[] = [
                        'file' => '图片素材/' . $dir_name . '/' . $file,
                        'score' => $score,
                    ];
                }
            }

            // 按分数排序，返回最佳匹配
            if (!empty($matches)) {
                usort($matches, fn($a, $b) => $b['score'] - $a['score']);

                // 找到第一个未使用的图片
                foreach ($matches as $match) {
                    if (!in_array($match['file'], self::$used_images)) {
                        return $match['file'];
                    }
                }
            }
        }

        // 尝试从auto_download目录查找
        return self::findInAutoDownload($brand, $model);
    }

    /**
     * 计算匹配分数
     */
    private static function calculateMatchScore($filename, $model, $color = null) {
        $score = 0;
        $filename_lower = strtolower($filename);
        $model_lower = strtolower($model);

        // 型号匹配
        // 提取型号中的数字和关键词
        if (preg_match('/(\d+)/', $model, $m)) {
            $model_num = $m[1];
            if (strpos($filename_lower, $model_num) !== false) {
                $score += 50;
            }
        }

        // Pro/Ultra/Max 匹配
        $variants = ['pro', 'ultra', 'max', 'plus', 'lite'];
        foreach ($variants as $v) {
            if (strpos($model_lower, $v) !== false && strpos($filename_lower, $v) !== false) {
                $score += 30;
            }
        }

        // 颜色匹配
        if ($color) {
            $color_lower = strtolower($color);
            $color_keywords = [
                '黑' => ['black', '黑', '墨', '玄'],
                '白' => ['white', '白', '银', '雪'],
                '蓝' => ['blue', '蓝', '青', '海'],
                '金' => ['gold', '金', '香槟'],
                '绿' => ['green', '绿', '翠'],
                '紫' => ['purple', '紫', '薰衣草'],
                '粉' => ['pink', '粉', '玫瑰'],
            ];

            foreach ($color_keywords as $base => $keywords) {
                if (strpos($color_lower, $base) !== false) {
                    foreach ($keywords as $kw) {
                        if (strpos($filename_lower, $kw) !== false) {
                            $score += 20;
                            break;
                        }
                    }
                }
            }
        }

        // 优先选择主图（_1）
        if (preg_match('/_1\.(jpg|png)/i', $filename)) {
            $score += 10;
        }

        return $score;
    }

    /**
     * 从auto_download目录查找
     */
    private static function findInAutoDownload($brand, $model) {
        $auto_dir = dirname(__DIR__) . '/images/auto_download/';
        if (!is_dir($auto_dir)) return null;

        $files = scandir($auto_dir);
        $brand_lower = strtolower($brand);

        foreach ($files as $file) {
            if (!preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) continue;

            $file_lower = strtolower($file);
            if (strpos($file_lower, $brand_lower) !== false) {
                $path = 'auto_download/' . $file;
                if (!in_array($path, self::$used_images)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * 获取品牌占位图
     */
    public static function getPlaceholder($brand = null) {
        $placeholders = [
            'Apple' => 'placeholder/apple_placeholder.svg',
            'Huawei' => 'placeholder/huawei_placeholder.svg',
            'Xiaomi' => 'placeholder/xiaomi_placeholder.svg',
        ];

        if ($brand && isset($placeholders[$brand])) {
            return self::$base_path . 'images/' . $placeholders[$brand];
        }

        return self::$base_path . 'images/default_phone_placeholder.svg';
    }

    /**
     * 生成懒加载图片HTML
     */
    public static function lazyImage($src, $alt = '', $class = '', $width = null, $height = null) {
        $placeholder = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E';

        $attrs = [
            'class' => 'lazy-image ' . $class,
            'data-src' => $src,
            'src' => $placeholder,
            'alt' => htmlspecialchars($alt),
            'loading' => 'lazy',
        ];

        if ($width) $attrs['width'] = $width;
        if ($height) $attrs['height'] = $height;

        $html = '<img';
        foreach ($attrs as $key => $value) {
            $html .= ' ' . $key . '="' . $value . '"';
        }
        $html .= '>';

        return $html;
    }

    /**
     * 获取产品图片画廊（多张图片）
     */
    public static function getProductGallery($brand, $model, $limit = 5) {
        $brand_dirs = [
            'Apple' => '苹果',
            'Huawei' => '华为',
            'Xiaomi' => '小米',
            'Honor' => '荣耀',
            'OPPO' => 'Oppo',
            'vivo' => 'Vivo',
            'Samsung' => '三星',
        ];

        $dir_name = $brand_dirs[$brand] ?? $brand;
        $brand_dir = dirname(__DIR__) . '/images/图片素材/' . $dir_name . '/';

        if (!is_dir($brand_dir)) {
            return [self::getPlaceholder($brand)];
        }

        $files = scandir($brand_dir);
        $gallery = [];

        // 提取型号数字
        $model_num = '';
        if (preg_match('/(\d+)/', $model, $m)) {
            $model_num = $m[1];
        }

        foreach ($files as $file) {
            if (!preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) continue;

            // 匹配型号
            if ($model_num && strpos($file, $model_num) !== false) {
                $path = '图片素材/' . $dir_name . '/' . $file;

                // 检查是否已使用
                if (!in_array($path, self::$used_images)) {
                    self::$used_images[] = $path;
                    $gallery[] = self::$base_path . 'images/' . $path;

                    if (count($gallery) >= $limit) break;
                }
            }
        }

        // 如果没找到足够的图片，用占位图补充
        while (count($gallery) < 1) {
            $gallery[] = self::getPlaceholder($brand);
            break;
        }

        return $gallery;
    }

    /**
     * 获取Banner图片
     */
    public static function getBanners($limit = 5) {
        $banner_dir = dirname(__DIR__) . '/images/banners/';
        $banners = [];

        if (is_dir($banner_dir)) {
            $files = scandir($banner_dir);
            foreach ($files as $file) {
                if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) {
                    $banners[] = [
                        'src' => 'images/banners/' . $file,
                        'alt' => pathinfo($file, PATHINFO_FILENAME),
                    ];
                }
            }
        }

        return array_slice($banners, 0, $limit);
    }

    /**
     * 获取热门机型展示图片（用于首页视差滚动）
     * 从数据库读取有真实封面图的热门机型
     */
    public static function getHotProductImages($limit = 8) {
        // 从数据库获取有封面图的热门机型
        try {
            require_once dirname(__DIR__) . '/config/config.php';
            $db = Database::getInstance();
            $conn = $db->getConnection();

            // 查询有封面图的热门手机（按价格排序取最贵的）
            $sql = "
                SELECT p.id, p.brand, p.model_name, p.min_price, pi.image_path
                FROM products_spu_v4 p
                INNER JOIN product_images pi ON p.id = pi.product_id AND pi.sort_order = 0
                WHERE p.category = 'phone' AND p.min_price > 3000
                ORDER BY p.min_price DESC
                LIMIT ?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$limit]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($products)) {
                $results = [];
                foreach ($products as $product) {
                    // 验证图片文件存在
                    $image_path = dirname(__DIR__) . '/' . $product['image_path'];
                    if (file_exists($image_path)) {
                        $results[] = [
                            'image' => self::$base_path . $product['image_path'],
                            'name' => $product['model_name'],
                            'brand' => $product['brand'],
                            'price' => $product['min_price'],
                            'id' => $product['id']
                        ];
                    }
                }
                if (count($results) >= 4) {
                    return $results;
                }
            }
        } catch (Exception $e) {
            // 数据库查询失败，使用备用方案
        }

        // 备用方案：使用预设热门机型（仅在数据库查询失败时）
        $hot_products = [
            ['brand' => 'Apple', 'model' => 'iPhone 16 Pro Max', 'name' => 'iPhone 16 Pro Max'],
            ['brand' => 'Huawei', 'model' => 'Mate 70 Pro', 'name' => '华为 Mate 70 Pro'],
            ['brand' => 'Xiaomi', 'model' => '15 Ultra', 'name' => '小米 15 Ultra'],
            ['brand' => 'Honor', 'model' => 'Magic 7 Pro', 'name' => '荣耀 Magic7 Pro'],
            ['brand' => 'OPPO', 'model' => 'Find X8 Ultra', 'name' => 'OPPO Find X8 Ultra'],
            ['brand' => 'vivo', 'model' => 'X200 Pro', 'name' => 'vivo X200 Pro'],
            ['brand' => 'Samsung', 'model' => 'Galaxy S25 Ultra', 'name' => '三星 S25 Ultra'],
            ['brand' => 'OnePlus', 'model' => '13', 'name' => '一加 13'],
        ];

        $results = [];
        foreach (array_slice($hot_products, 0, $limit) as $product) {
            $image = self::getProductImage($product['brand'], $product['model'], null, true);
            $results[] = [
                'image' => $image,
                'name' => $product['name'],
                'brand' => $product['brand'],
            ];
        }

        return $results;
    }

    /**
     * 重置已使用图片记录（新页面时调用）
     */
    public static function reset() {
        self::$used_images = [];
    }

    /**
     * 获取已使用图片数量
     */
    public static function getUsedCount() {
        return count(self::$used_images);
    }
}
