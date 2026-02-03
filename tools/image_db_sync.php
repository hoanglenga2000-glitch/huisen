<?php
/**
 * ==========================================
 * 汇森科技 - 图片数据库同步脚本 v1.0
 * ==========================================
 *
 * 功能：
 * 1. 创建 product_images 表
 * 2. 扫描图片目录并匹配产品
 * 3. 自动同步图片路径到数据库
 */

require_once dirname(__DIR__) . '/config/config.php';

class ImageDatabaseSync {
    private $conn;
    private $images_base = 'images/';
    private $stats = [
        'matched' => 0,
        'unmatched' => 0,
        'errors' => 0,
    ];
    private $log = [];

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    /**
     * 创建图片表
     */
    public function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS product_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NULL COMMENT '关联SPU ID',
            sku_id INT NULL COMMENT '关联SKU ID',
            image_path VARCHAR(500) NOT NULL COMMENT '图片相对路径',
            image_type ENUM('thumbnail', 'main', 'detail', 'banner', 'gallery') DEFAULT 'main' COMMENT '图片类型',
            sort_order INT DEFAULT 0 COMMENT '排序权重',
            brand VARCHAR(50) COMMENT '品牌',
            model_name VARCHAR(100) COMMENT '型号名称',
            color VARCHAR(50) COMMENT '颜色',
            is_matched TINYINT(1) DEFAULT 0 COMMENT '是否已匹配产品',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_product (product_id),
            INDEX idx_sku (sku_id),
            INDEX idx_brand (brand),
            INDEX idx_type (image_type),
            UNIQUE KEY unique_path (image_path(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='产品图片表'";

        try {
            $this->conn->exec($sql);
            $this->log("✓ product_images 表创建成功");
            return true;
        } catch (Exception $e) {
            $this->log("✗ 创建表失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 扫描并同步图片
     */
    public function syncImages($directory = null) {
        $base_dir = dirname(__DIR__) . '/images';
        $scan_dir = $directory ?? $base_dir . '/图片素材';

        if (!is_dir($scan_dir)) {
            $this->log("目录不存在: $scan_dir");
            return false;
        }

        $this->log("开始扫描目录: $scan_dir");
        $this->scanAndSync($scan_dir, $base_dir);

        $this->log("\n=== 同步完成 ===");
        $this->log("成功匹配: {$this->stats['matched']}");
        $this->log("未匹配: {$this->stats['unmatched']}");
        $this->log("错误: {$this->stats['errors']}");

        return $this->stats;
    }

    /**
     * 递归扫描并同步
     */
    private function scanAndSync($dir, $base_dir) {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $extensions)) continue;

            $full_path = $file->getPathname();
            $relative_path = str_replace('\\', '/', str_replace($base_dir . '/', '', $full_path));

            // 从路径提取信息
            $info = $this->extractInfoFromPath($relative_path, $file->getFilename());

            // 尝试匹配产品
            $product = $this->matchProduct($info);

            // 插入或更新数据库
            $this->upsertImage($relative_path, $info, $product);
        }
    }

    /**
     * 从路径提取品牌、型号、颜色信息
     */
    private function extractInfoFromPath($path, $filename) {
        $info = [
            'brand' => null,
            'model' => null,
            'color' => null,
            'type' => 'main',
        ];

        // 路径部分
        $parts = explode('/', $path);

        // 品牌映射
        $brand_map = [
            '苹果' => 'Apple', 'Apple' => 'Apple',
            '华为' => 'Huawei', 'Huawei' => 'Huawei',
            '小米' => 'Xiaomi', 'Xiaomi' => 'Xiaomi',
            '荣耀' => 'Honor', 'Honor' => 'Honor',
            'Oppo' => 'OPPO', 'OPPO' => 'OPPO',
            'Vivo' => 'vivo', 'vivo' => 'vivo',
            '三星' => 'Samsung', 'Samsung' => 'Samsung',
            '一加' => 'OnePlus', 'OnePlus' => 'OnePlus',
            '红米' => 'Redmi', 'Redmi' => 'Redmi',
            '摩托罗拉' => 'Motorola', 'Motorola' => 'Motorola',
            '诺基亚' => 'Nokia', 'Nokia' => 'Nokia',
        ];

        foreach ($parts as $part) {
            if (isset($brand_map[$part])) {
                $info['brand'] = $brand_map[$part];
                break;
            }
        }

        // 从文件名提取型号
        // 例如: 14-128G_1.jpg -> iPhone 14 128G
        // 例如: Mate60Pro_black_01.jpg -> Mate 60 Pro
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // 尝试匹配常见型号模式
        if (preg_match('/^(\d+)[-_](\d+G)/i', $name, $matches)) {
            // iPhone 14-128G 格式
            $info['model'] = $matches[1];
            $info['storage'] = $matches[2];
        } elseif (preg_match('/^([A-Za-z]+\s*\d+\s*(?:Pro|Ultra|Max|Plus)?)/i', $name, $matches)) {
            $info['model'] = trim($matches[1]);
        }

        // 提取颜色
        $colors = [
            'black' => '黑色', 'white' => '白色', 'blue' => '蓝色',
            'gold' => '金色', 'silver' => '银色', 'green' => '绿色',
            'purple' => '紫色', 'pink' => '粉色', 'red' => '红色',
            'gray' => '灰色', 'titanium' => '钛金属',
            '黑' => '黑色', '白' => '白色', '蓝' => '蓝色',
            '金' => '金色', '银' => '银色', '绿' => '绿色',
        ];

        $name_lower = strtolower($name);
        foreach ($colors as $key => $value) {
            if (strpos($name_lower, strtolower($key)) !== false) {
                $info['color'] = $value;
                break;
            }
        }

        // 判断图片类型
        if (preg_match('/_(\d+)\.(jpg|png)/i', $filename, $m)) {
            $num = intval($m[1]);
            $info['type'] = $num === 1 ? 'main' : 'gallery';
            $info['sort'] = $num;
        }

        return $info;
    }

    /**
     * 匹配产品
     */
    private function matchProduct($info) {
        if (!$info['brand'] && !$info['model']) {
            return null;
        }

        // 检查使用哪个表
        $use_v4 = $this->conn->query("SHOW TABLES LIKE 'products_spu_v4'")->rowCount() > 0;
        $spu_table = $use_v4 ? 'products_spu_v4' : 'products_spu_v3';

        $where = [];
        $params = [];

        if ($info['brand']) {
            $where[] = "brand = ?";
            $params[] = $info['brand'];
        }

        if ($info['model']) {
            $where[] = "(model_name LIKE ? OR model_name LIKE ?)";
            $params[] = '%' . $info['model'] . '%';
            $params[] = $info['model'] . '%';
        }

        if (empty($where)) {
            return null;
        }

        $sql = "SELECT id, brand, model_name FROM $spu_table WHERE " . implode(' AND ', $where) . " LIMIT 1";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 插入或更新图片记录
     */
    private function upsertImage($path, $info, $product) {
        $sql = "INSERT INTO product_images
                (image_path, product_id, brand, model_name, color, image_type, sort_order, is_matched)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                product_id = VALUES(product_id),
                brand = VALUES(brand),
                model_name = VALUES(model_name),
                color = VALUES(color),
                image_type = VALUES(image_type),
                is_matched = VALUES(is_matched),
                updated_at = NOW()";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $path,
                $product ? $product['id'] : null,
                $info['brand'],
                $info['model'] ?? ($product ? $product['model_name'] : null),
                $info['color'],
                $info['type'],
                $info['sort'] ?? 0,
                $product ? 1 : 0,
            ]);

            if ($product) {
                $this->stats['matched']++;
            } else {
                $this->stats['unmatched']++;
            }
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->log("错误: $path - " . $e->getMessage());
        }
    }

    /**
     * 获取产品图片
     */
    public function getProductImages($product_id, $type = null) {
        $sql = "SELECT * FROM product_images WHERE product_id = ?";
        $params = [$product_id];

        if ($type) {
            $sql .= " AND image_type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY sort_order ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 根据品牌获取图片
     */
    public function getBrandImages($brand, $limit = 20) {
        $sql = "SELECT * FROM product_images
                WHERE brand = ? AND image_type IN ('main', 'gallery')
                ORDER BY RAND()
                LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$brand, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function log($message) {
        $this->log[] = $message;
        echo $message . "\n";
    }

    public function getLog() {
        return $this->log;
    }
}

// CLI运行
if (php_sapi_name() === 'cli') {
    echo "========================================\n";
    echo "汇森科技 - 图片数据库同步\n";
    echo "========================================\n\n";

    $sync = new ImageDatabaseSync();

    // 1. 创建表
    $sync->createTable();

    // 2. 同步图片素材目录
    $sync->syncImages();

    // 3. 同步phones目录
    $sync->syncImages(dirname(__DIR__) . '/images/phones');

    echo "\n完成!\n";
}
