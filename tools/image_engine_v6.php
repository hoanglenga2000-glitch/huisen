<?php
/**
 * ==========================================
 * 图片匹配引擎 V6 - Image Engine Ultimate
 * ==========================================
 *
 * 核心功能：
 * 1. 品类隔离算法 - 防止手表匹配成手机图
 * 2. 模糊清洗 V2 - 中文路径处理 + 特征提取
 * 3. 自动爬取缺失图片（预留接口）
 * 4. 深度文件系统扫描
 */

require_once __DIR__ . '/../config/config.php';

class ImageEngineV6 {
    private $db;
    private $conn;
    private $imageBasePath = 'C:/xampp/htdocs/huisen/images/图片素材/';
    private $webBasePath = 'images/图片素材/';

    // 品类文件夹映射
    private $categoryFolders = [
        'phone' => ['Apple', 'iPhone', 'Huawei', 'Xiaomi', '苹果', '华为', '小米', '荣耀', 'OPPO', 'Vivo', 'Samsung', '三星', '一加', 'Realme', '红米'],
        'watch' => ['Watch', 'Band', '手表', '手环', 'SKG', '小天才'],
        'tablet' => ['Pad', 'Tab', '平板', 'iPad'],
        'accessory' => ['Accessories', 'Earphone', 'AirPods', '配件', '耳机', '充电', '保护']
    ];

    // 品牌英文映射
    private $brandMapping = [
        '苹果' => 'Apple',
        '华为' => 'Huawei',
        '小米' => 'Xiaomi',
        '荣耀' => 'Honor',
        '三星' => 'Samsung',
    ];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * 主函数：执行图片匹配
     */
    public function matchAllImages() {
        echo "===========================================\n";
        echo "图片匹配引擎 V6 - 开始执行\n";
        echo "===========================================\n\n";

        // 1. 获取所有产品
        $products = $this->getAllProducts();
        echo "📊 共找到 " . count($products) . " 个产品\n\n";

        // 2. 扫描所有图片文件
        $allImages = $this->scanAllImages();
        echo "🖼️  共扫描到 " . count($allImages) . " 张图片\n\n";

        $matchedCount = 0;
        $unmatchedCount = 0;
        $updatedCount = 0;

        // 3. 为每个产品匹配图片
        foreach ($products as $product) {
            echo "处理: {$product['brand']} {$product['model_name']} (分类: {$product['category']})...\n";

            $matchedImage = $this->findBestMatch($product, $allImages);

            if ($matchedImage) {
                $matchedCount++;
                echo "  ✅ 匹配到: {$matchedImage}\n";

                // 更新数据库
                $this->updateProductImage($product['id'], $matchedImage);
                $updatedCount++;
            } else {
                $unmatchedCount++;
                echo "  ❌ 未找到匹配图片\n";
            }
        }

        echo "\n===========================================\n";
        echo "匹配完成！\n";
        echo "===========================================\n";
        echo "✅ 匹配成功: {$matchedCount}\n";
        echo "❌ 未匹配: {$unmatchedCount}\n";
        echo "📝 更新数据库: {$updatedCount}\n";
        echo "===========================================\n";

        return [
            'total' => count($products),
            'matched' => $matchedCount,
            'unmatched' => $unmatchedCount,
            'updated' => $updatedCount
        ];
    }

    /**
     * 获取所有产品
     */
    private function getAllProducts() {
        $stmt = $this->conn->query("SELECT id, brand, model_name, category FROM products_spu_v3 WHERE min_price > 0");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 扫描所有图片文件
     */
    private function scanAllImages() {
        $images = [];
        $this->scanDirectory($this->imageBasePath, $images);
        return $images;
    }

    /**
     * 递归扫描目录
     */
    private function scanDirectory($dir, &$images) {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . $file;

            if (is_dir($path)) {
                $this->scanDirectory($path . '/', $images);
            } elseif (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
                // 转换为相对路径
                $relativePath = str_replace($this->imageBasePath, '', $path);
                $relativePath = str_replace('\\', '/', $relativePath);
                $images[] = $relativePath;
            }
        }
    }

    /**
     * 为产品找到最佳匹配图片
     */
    private function findBestMatch($product, $allImages) {
        $brand = $product['brand'];
        $modelName = $product['model_name'];
        $category = $product['category'];

        // 品类隔离：过滤出符合品类的图片
        $categoryImages = $this->filterByCategory($allImages, $category);

        if (empty($categoryImages)) {
            $categoryImages = $allImages; // 如果没有品类限制，使用所有图片
        }

        // 进一步过滤：只保留包含品牌名的图片
        $brandImages = $this->filterByBrand($categoryImages, $brand);

        if (empty($brandImages)) {
            $brandImages = $categoryImages; // 如果没有品牌限制，使用品类图片
        }

        // 匹配算法：计算相似度分数
        $bestMatch = null;
        $bestScore = 0;

        foreach ($brandImages as $image) {
            $score = $this->calculateMatchScore($product, $image);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $image;
            }
        }

        // 如果分数太低，返回null
        if ($bestScore < 30) {
            return null;
        }

        return $this->webBasePath . $bestMatch;
    }

    /**
     * 品类隔离：根据分类过滤图片
     */
    private function filterByCategory($images, $category) {
        if (!isset($this->categoryFolders[$category])) {
            return $images;
        }

        $folders = $this->categoryFolders[$category];
        $filtered = [];

        foreach ($images as $image) {
            foreach ($folders as $folder) {
                if (stripos($image, $folder) !== false) {
                    $filtered[] = $image;
                    break;
                }
            }
        }

        return $filtered;
    }

    /**
     * 根据品牌过滤图片
     */
    private function filterByBrand($images, $brand) {
        $brandEn = $this->brandMapping[$brand] ?? $brand;
        $filtered = [];

        foreach ($images as $image) {
            if (stripos($image, $brand) !== false || stripos($image, $brandEn) !== false) {
                $filtered[] = $image;
            }
        }

        return $filtered;
    }

    /**
     * 计算匹配分数
     */
    private function calculateMatchScore($product, $imagePath) {
        $score = 0;
        $brand = $product['brand'];
        $modelName = $product['model_name'];

        // 提取型号关键词
        $keywords = $this->extractKeywords($modelName);

        // 文件名匹配
        $fileName = basename($imagePath);
        $fileNameLower = mb_strtolower($fileName);

        // 品牌匹配 (+30分)
        $brandEn = $this->brandMapping[$brand] ?? $brand;
        if (stripos($fileName, $brand) !== false || stripos($fileName, $brandEn) !== false) {
            $score += 30;
        }

        // 关键词匹配 (每个+20分)
        foreach ($keywords as $keyword) {
            if (stripos($fileNameLower, mb_strtolower($keyword)) !== false) {
                $score += 20;
            }
        }

        // 完整型号匹配 (+50分)
        $cleanModel = preg_replace('/\s+/', '', $modelName);
        $cleanFileName = preg_replace('/\s+/', '', $fileName);
        if (stripos($cleanFileName, $cleanModel) !== false) {
            $score += 50;
        }

        return $score;
    }

    /**
     * 提取型号关键词
     */
    private function extractKeywords($modelName) {
        $keywords = [];

        // 常见关键词
        $patterns = [
            '/\d+/',                    // 数字 (15, 60, 70)
            '/Pro/i',                   // Pro
            '/Max/i',                   // Max
            '/Ultra/i',                 // Ultra
            '/Plus/i',                  // Plus
            '/Mate/i',                  // Mate
            '/iPhone/i',                // iPhone
            '/Watch/i',                 // Watch
            '/Pad/i',                   // Pad
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $modelName, $matches)) {
                $keywords = array_merge($keywords, $matches[0]);
            }
        }

        return array_unique($keywords);
    }

    /**
     * 更新产品图片
     */
    private function updateProductImage($productId, $imagePath) {
        $stmt = $this->conn->prepare("UPDATE products_spu_v3 SET image_url = ? WHERE id = ?");
        $stmt->execute([$imagePath, $productId]);
    }
}

// 如果直接运行此脚本
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF']) === 'image_engine_v6.php') {
    $engine = new ImageEngineV6();
    $result = $engine->matchAllImages();

    if (php_sapi_name() !== 'cli') {
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }
}
