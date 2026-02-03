<?php
/**
 * ============================================
 * 甘肃汇森信息科技有限公司 - 数据库配置文件
 * ============================================
 * 
 * 说明：
 * 1. 本文件用于配置数据库连接参数
 * 2. 请根据phpStudy的实际配置修改以下参数
 * 3. 确保MySQL服务已启动
 */

// 开启错误报告（生产环境请关闭）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// 数据库配置
// ============================================
define('DB_HOST', 'localhost');        // 数据库主机
define('DB_PORT', '3306');             // 数据库端口
define('DB_NAME', '甘肃汇森');           // 数据库名（建议使用英文名）
define('DB_USER', 'huisen');           // 数据库用户名
define('DB_PASS', '123456');           // 数据库密码
define('DB_CHARSET', 'utf8mb4');       // 字符集

// ============================================
// 系统配置
// ============================================
define('SITE_NAME', '甘肃汇森信息科技有限公司');
define('SITE_URL', 'http://huisen.zhjjq.tech');

// ============================================
// 数据库连接类
// ============================================
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            // 使用PDO连接数据库
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,           // 异常模式
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // 关联数组模式
                PDO::ATTR_EMULATE_PREPARES => false,                    // 使用真正的预处理语句
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET  // 设置字符集
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // 如果是数据库不存在或权限错误，提供更友好的提示
            $errorCode = $e->getCode();
            $errorMsg = $e->getMessage();
            
            // 检查是否是数据库不存在或权限问题
            if (strpos($errorMsg, '1044') !== false || strpos($errorMsg, '1049') !== false) {
                // 在 Web 环境下，提供友好的错误页面
                if (php_sapi_name() !== 'cli' && !headers_sent()) {
                    header('Content-Type: text/html; charset=utf-8');
                    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>数据库配置错误</title></head><body>';
                    echo '<h2>数据库连接失败</h2>';
                    echo '<p style="color: red;">' . htmlspecialchars($errorMsg) . '</p>';
                    echo '<h3>解决方案：</h3>';
                    echo '<ol>';
                    echo '<li>请访问 <a href="setup_database.php">setup_database.php</a> 自动创建数据库和用户</li>';
                    echo '<li>或者手动在 phpMyAdmin 中执行以下 SQL：</li>';
                    echo '</ol>';
                    echo '<pre style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
                    echo "-- 1. 创建数据库\n";
                    echo "CREATE DATABASE IF NOT EXISTS `huisen` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n\n";
                    echo "-- 2. 创建用户（如果不存在）\n";
                    echo "CREATE USER 'huisen'@'localhost' IDENTIFIED BY '123456';\n\n";
                    echo "-- 3. 授权\n";
                    echo "GRANT ALL PRIVILEGES ON `huisen`.* TO 'huisen'@'localhost';\n";
                    echo "FLUSH PRIVILEGES;";
                    echo '</pre>';
                    echo '<p><a href="setup_database.php" style="display: inline-block; padding: 10px 20px; background: #00f0ff; color: white; text-decoration: none; border-radius: 5px;">前往数据库设置向导</a></p>';
                    echo '</body></html>';
                    exit;
                }
            }
            
            // 其他情况，返回 JSON 错误
            die(json_encode([
                'success' => false,
                'error' => '数据库连接失败: ' . $errorMsg,
                'hint' => '请检查: 1.MySQL服务是否启动 2.数据库是否存在 3.用户名密码是否正确 4.用户是否有数据库访问权限'
            ], JSON_UNESCAPED_UNICODE));
        }
    }
    
    /**
     * 获取数据库实例（单例模式）
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 获取数据库连接
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * 执行查询
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('查询执行失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取所有结果
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * 获取单条结果
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * 插入数据
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($sql, array_values($data));
        return $this->conn->lastInsertId();
    }
    
    /**
     * 防止克隆
     */
    private function __clone() {}
}

// ============================================
// 辅助函数
// ============================================

/**
 * 返回JSON响应
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');  // 允许跨域（生产环境请限制）
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 成功响应
 */
function successResponse($data = [], $message = '操作成功') {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * 错误响应
 */
function errorResponse($message = '操作失败', $code = 400) {
    jsonResponse([
        'success' => false,
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], $code);
}
