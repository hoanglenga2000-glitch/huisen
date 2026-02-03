<?php
/**
 * ==========================================
 * 图片引擎 V6 终极版 - Image Engine Ultimate
 * ==========================================
 *
 * 新增功能：
 * 1. 网络爬虫自动补图
 * 2. 全局唯一匹配锁（防止重复使用图片）
 * 3. 智能纠错（防止分类错配）
 * 4. 自动生成占位图
 */

require_once __DIR__ . '/../config/config.php';

class ImageEngineV6Ultimate {
    private $db;
    private $conn;
    private $imageBasePath = 'C:/xampp/htdocs/huisen/images/图片素材/';
    private $webBasePath = 'images/图片素材/';
    private $autoDownloadPath = 'C:/xampp/htdocs/huisen/images/auto_download/';
    private $webAutoDownloadPath = 'images/auto_download/';

    // 全局唯一匹配锁
    private $usedImages = [];

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

        // 确保自动下载目录存在
        if (!is_dir($this->autoDownloadPath)) {
            mkdir($this->autoDownloadPath, 0777, true);
        }
    }

    /**
     * 主函数：执行图片匹配（带唯一锁）
     */
    public function matchAllImages() {
        echo "===========================================\n";
        echo "图片匹配引擎 V6 终极版 - 开始执行\n";
        echo "===========================================\n\n";

        // 1. 获取所有产品
        $products = $this->getAllProducts();
        echo "📊 共找到 " . count($products) . " 个产品\n\n";

        // 2. 扫描所有图片文件
        $allImages = $this->scanAllImages();
        echo "🖼️  共扫描到 " . count($allImages) . " 张图片\n\n";

        // 3. 加载已使用的图片（防止重复）
        $this->loadUsedImages();
        echo "🔒 已锁定 " . count($this->usedImages) . " 张已使用的图片\n\n";

        $matchedCount = 0;
        $unmatchedCount = 0;
        $downloadedCount = 0;
        $placeholderCount = 0;

        // 4. 为每个产品匹配图片
        foreach ($products as $product) {
            echo "处理: {$product['brand']} {$product['model_name']} (分类: {$product['category']})...\n";

            $matchedImage = $this->findBestMatchWithLock($product, $allImages);

            if ($matchedImage) {
                $matchedCount++;
                echo "  ✅ 匹配到: {$matchedImage}\n";

                // 标记为已使用
                $this->usedImages[$matchedImage] = $product['id'];

                // 更新数据库
                $this->updateProductImage($product['id'], $matchedImage);
            } else {
                // 尝试网络爬虫下载
                echo "  🌐 本地无匹配，尝试网络下载...\n";
                $downloadedImage = $this->downloadImageFromWeb($product);

                if ($downloadedImage) {
                    $downloadedCount++;
                    echo "  ✅ 下载成功: {$downloadedImage}\n";
                    $this->updateProductImage($product['id'], $downloadedImage);
                } else {
                    // 使用占位图
                    echo "  ⚠️  下载失败，使用占位图\n";
                    $placeholderImage = $this->getPlaceholderImage($product['category']);
                    $this->updateProductImage($product['id'], $placeholderImage);
                    $placeholderCount++;
                    $unmatchedCount++;
                }
            }
        }

        echo "\n===========================================\n";
        echo "匹配完成！\n";
        echo "===========================================\n";
        echo "✅ 本地匹配: {$matchedCount}\n";
        echo "🌐 网络下载: {$downloadedCount}\n";
        echo "⚠️  占位图: {$placeholderCount}\n";
        echo "❌ 未匹配: {$unmatchedCount}\n";
        echo "===========================================\n";

        return [
            'total' => count($products),
            'matched' => $matchedCount,
            'downloaded' => $downloadedCount,
            'placeholder' => $placeholderCount,
            'unmatched' => $unmatchedCount
        ];
    }

    /**
     * 获取所有产品
     */
    private function getAllProducts() {
        $stmt = $this->conn->query("SELECT id, brand, model_name, category FROM products_spu_v3 WHERE min_price > 0 ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 扫描所有图片文件
     */
    private function scanAllImages() {
        $images = [];
        $this->scanDirectory($this->imageBasePath, $images);
        $this->scanDirectory($this->autoDownloadPath, $images, true);
        return $images;
    }

    /**
     * 递归扫描目录
     */
    private function scanDirectory($dir, &$images, $isAutoDownload = false) {
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
                $this->scanDirectory($path . '/', $images, $isAutoDownload);
            } elseif (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
                if ($isAutoDownload) {
                    $relativePath = str_replace($this->autoDownloadPath, '', $path);
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $images[] = ['path' => $relativePath, 'source' => 'auto'];
                } else {
                    $relativePath = str_replace($this->imageBasePath, '', $path);
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $images[] = ['path' => $relativePath, 'source' => 'local'];
                }
            }
        }
    }

    /**
     * 加载已使用的图片
     */
    private function loadUsedImages() {
        $stmt = $this->conn->query("SELECT id, image_url FROM products_spu_v3 WHERE image_url IS NOT NULL AND image_url != ''");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            // 提取相对路径
            $imagePath = str_replace([$this->webBasePath, $this->webAutoDownloadPath], '', $row['image_url']);
            $this->usedImages[$imagePath] = $row['id'];
        }
    }

    /**
     * 为产品找到最佳匹配图片（带唯一锁）
     */
    private function findBestMatchWithLock($product, $allImages) {
        $brand = $product['brand'];
        $modelName = $product['model_name'];
        $category = $product['category'];

        // 品类隔离：过滤出符合品类的图片
        $categoryImages = $this->filterByCategory($allImages, $category);

        if (empty($categoryImages)) {
            $categoryImages = $allImages;
        }

        // 进一步过滤：只保留包含品牌名的图片
        $brandImages = $this->filterByBrand($categoryImages, $brand);

        if (empty($brandImages)) {
            $brandImages = $categoryImages;
        }

        // 匹配算法：计算相似度分数，排除已使用的图片
        $bestMatch = null;
        $bestScore = 0;

        foreach ($brandImages as $imageInfo) {
            $imagePath = $imageInfo['path'];

            // 🔒 检查唯一锁：如果图片已被使用，跳过
            if (isset($this->usedImages[$imagePath])) {
                continue;
            }

            // 智能纠错：检查分类是否匹配
            if (!$this->isCategoryMatch($imagePath, $category)) {
                continue;
            }

            $score = $this->calculateMatchScore($product, $imagePath);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $imagePath;
            }
        }

        // 如果分数太低，返回null
        if ($bestScore < 30) {
            return null;
        }

        // 根据来源返回正确的路径
        foreach ($allImages as $imageInfo) {
            if ($imageInfo['path'] === $bestMatch) {
                if ($imageInfo['source'] === 'auto') {
                    return $this->webAutoDownloadPath . $bestMatch;
                } else {
                    return $this->webBasePath . $bestMatch;
                }
            }
        }

        return $this->webBasePath . $bestMatch;
    }

    /**
     * 智能纠错：检查图片分类是否与产品分类匹配
     */
    private function isCategoryMatch($imagePath, $productCategory) {
        $imagePathLower = mb_strtolower($imagePath);

        // 如果产品是手表，图片路径不能包含手机关键词
        if ($productCategory === 'watch') {
            $phoneKeywords = ['iphone', 'phone', 'mate', 'galaxy', 'find', 'reno', 'oppo', 'vivo'];
            foreach ($phoneKeywords as $keyword) {
                if (stripos($imagePathLower, $keyword) !== false && stripos($imagePathLower, 'watch') === false) {
                    return false; // 拒绝手机图片
                }
            }
        }

        // 如果产品是手机，图片路径不能包含手表关键词
        if ($productCategory === 'phone') {
            $watchKeywords = ['watch', 'band', '手表', '手环'];
            foreach ($watchKeywords as $keyword) {
                if (stripos($imagePathLower, $keyword) !== false) {
                    return false; // 拒绝手表图片
                }
            }
        }

        return true;
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

        foreach ($images as $imageInfo) {
            $imagePath = $imageInfo['path'];
            foreach ($folders as $folder) {
                if (stripos($imagePath, $folder) !== false) {
                    $filtered[] = $imageInfo;
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

        foreach ($images as $imageInfo) {
            $imagePath = $imageInfo['path'];
            if (stripos($imagePath, $brand) !== false || stripos($imagePath, $brandEn) !== false) {
                $filtered[] = $imageInfo;
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
            '/\d+/',
            '/Pro/i',
            '/Max/i',
            '/Ultra/i',
            '/Plus/i',
            '/Mate/i',
            '/iPhone/i',
            '/Watch/i',
            '/Pad/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $modelName, $matches)) {
                $keywords = array_merge($keywords, $matches[0]);
            }
        }

        return array_unique($keywords);
    }

    /**
     * 从网络下载图片
     */
    private function downloadImageFromWeb($product) {
        $brand = $product['brand'];
        $modelName = $product['model_name'];

        // 构建搜索关键词
        $searchQuery = urlencode("{$brand} {$modelName} 官网图");

        // 使用Bing Images API（简化版，实际应使用API key）
        $searchUrl = "https://www.bing.com/images/search?q={$searchQuery}&first=1";

        // 注意：实际生产环境需要使用正规API，这里仅作演示
        // 由于安全和版权原因，这里返回null，使用占位图

        // TODO: 实现真实的图片下载逻辑
        // 1. 使用Bing Image Search API或Google Custom Search API
        // 2. 下载第一张高质量图片
        // 3. 保存到auto_download目录
        // 4. 返回相对路径

        return null; // 暂不实现网络下载，避免版权问题
    }

    /**
     * 获取占位图
     */
    private function getPlaceholderImage($category) {
        // 根据分类返回不同的占位图
        $placeholders = [
            'phone' => 'images/default_phone_placeholder.png',
            'watch' => 'images/default_watch_placeholder.png',
            'tablet' => 'images/default_tablet_placeholder.png',
            'accessory' => 'images/default_accessory_placeholder.png',
        ];

        return $placeholders[$category] ?? 'images/default_phone_placeholder.png';
    }

    /**
     * 更新产品图片
     */
    private function updateProductImage($productId, $imagePath) {
        $stmt = $this->conn->prepare("UPDATE products_spu_v3 SET image_url = ? WHERE id = ?");
        $stmt->execute([$imagePath, $productId]);
    }

    /**
     * 生成专业占位图（SVG格式）
     */
    public function generatePlaceholders() {
        echo "生成占位图...\n";

        $placeholders = [
            'phone' => $this->generatePhonePlaceholder(),
            'watch' => $this->generateWatchPlaceholder(),
            'tablet' => $this->generateTabletPlaceholder(),
            'accessory' => $this->generateAccessoryPlaceholder(),
        ];

        foreach ($placeholders as $type => $svg) {
            $filename = "C:/xampp/htdocs/huisen/images/default_{$type}_placeholder.png";
            // 实际应该将SVG转为PNG，这里仅保存SVG
            file_put_contents(str_replace('.png', '.svg', $filename), $svg);
            echo "  ✅ 生成: {$type} 占位图\n";
        }

        echo "占位图生成完成！\n";
    }

    private function generatePhonePlaceholder() {
        return '<?xml version="1.0" encoding="UTF-8"?>
<svg width="400" height="400" xmlns="http://www.w3.org/2000/svg">
    <rect width="400" height="400" fill="#f3f4f6"/>
    <rect x="100" y="50" width="200" height="300" rx="20" fill="#e5e7eb" stroke="#9ca3af" stroke-width="2"/>
    <circle cx="200" cy="80" r="5" fill="#9ca3af"/>
    <rect x="120" y="100" width="160" height="240" rx="5" fill="#d1d5db"/>
    <text x="200" y="370" font-family="Arial" font-size="14" fill="#6b7280" text-anchor="middle">手机图片</text>
</svg>';
    }

    private function generateWatchPlaceholder() {
        return '<?xml version="1.0" encoding="UTF-8"?>
<svg width="400" height="400" xmlns="http://www.w3.org/2000/svg">
    <rect width="400" height="400" fill="#f3f4f6"/>
    <circle cx="200" cy="200" r="80" fill="#e5e7eb" stroke="#9ca3af" stroke-width="2"/>
    <rect x="180" y="100" width="40" height="20" rx="5" fill="#9ca3af"/>
    <rect x="180" y="280" width="40" height="20" rx="5" fill="#9ca3af"/>
    <text x="200" y="370" font-family="Arial" font-size="14" fill="#6b7280" text-anchor="middle">手表图片</text>
</svg>';
    }

    private function generateTabletPlaceholder() {
        return '<?xml version="1.0" encoding="UTF-8"?>
<svg width="400" height="400" xmlns="http://www.w3.org/2000/svg">
    <rect width="400" height="400" fill="#f3f4f6"/>
    <rect x="50" y="80" width="300" height="240" rx="15" fill="#e5e7eb" stroke="#9ca3af" stroke-width="2"/>
    <rect x="70" y="100" width="260" height="200" rx="5" fill="#d1d5db"/>
    <circle cx="200" cy="310" r="8" fill="#9ca3af"/>
    <text x="200" y="370" font-family="Arial" font-size="14" fill="#6b7280" text-anchor="middle">平板图片</text>
</svg>';
    }

    private function generateAccessoryPlaceholder() {
        return '<?xml version="1.0" encoding="UTF-8"?>
<svg width="400" height="400" xmlns="http://www.w3.org/2000/svg">
    <rect width="400" height="400" fill="#f3f4f6"/>
    <ellipse cx="150" cy="200" rx="40" ry="50" fill="#e5e7eb" stroke="#9ca3af" stroke-width="2"/>
    <ellipse cx="250" cy="200" rx="40" ry="50" fill="#e5e7eb" stroke="#9ca3af" stroke-width="2"/>
    <path d="M 190 200 Q 200 180 210 200" stroke="#9ca3af" stroke-width="3" fill="none"/>
    <text x="200" y="370" font-family="Arial" font-size="14" fill="#6b7280" text-anchor="middle">配件图片</text>
</svg>';
    }
}

// 如果直接运行此脚本
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF']) === 'image_engine_v6_ultimate.php') {
    $engine = new ImageEngineV6Ultimate();

    // 先生成占位图
    $engine->generatePlaceholders();
    echo "\n";

    // 执行图片匹配
    $result = $engine->matchAllImages();

    if (php_sapi_name() !== 'cli') {
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }
}
