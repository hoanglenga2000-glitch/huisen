<?php
/**
 * ============================================
 * 数据库初始化脚本
 * 自动创建所有必需的数据表
 * ============================================
 */

require_once __DIR__ . '/config.php';

function initDatabase() {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // 创建用户表
        $conn->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                phone VARCHAR(20) UNIQUE,
                email VARCHAR(100),
                real_name VARCHAR(50),
                role ENUM('user', 'admin') DEFAULT 'user',
                status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_phone (phone),
                INDEX idx_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 创建用户地址表
        $conn->exec("
            CREATE TABLE IF NOT EXISTS user_addresses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(50) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                province VARCHAR(50) NOT NULL,
                city VARCHAR(50) NOT NULL,
                district VARCHAR(50),
                address TEXT NOT NULL,
                is_default TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 创建订单表
        $conn->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                order_no VARCHAR(32) NOT NULL UNIQUE,
                total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
                status ENUM('pending', 'confirmed', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
                address_id INT,
                remark TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_order_no (order_no),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 创建订单商品表
        $conn->exec("
            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                spu_id INT,
                sku_id INT,
                product_name VARCHAR(200),
                sku_name VARCHAR(200),
                color VARCHAR(50),
                storage VARCHAR(50),
                price DECIMAL(10,2),
                quantity INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order_id (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 创建询价记录表
        $conn->exec("
            CREATE TABLE IF NOT EXISTS inquiries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                contact_name VARCHAR(50),
                contact_phone VARCHAR(20),
                items TEXT,
                total_amount DECIMAL(10,2),
                status ENUM('pending', 'processing', 'quoted', 'completed', 'cancelled') DEFAULT 'pending',
                admin_note TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 检查是否有管理员账户，如果没有则创建默认管理员
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();

        if ($adminCount == 0) {
            // 创建默认管理员账户
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO users (username, password, real_name, phone, role, status)
                VALUES ('admin', ?, '系统管理员', '13800000000', 'admin', 1)
                ON DUPLICATE KEY UPDATE id=id
            ");
            $stmt->execute([$adminPassword]);
        }

        return true;

    } catch (Exception $e) {
        error_log("数据库初始化失败: " . $e->getMessage());
        return false;
    }
}

// 如果直接访问此文件，执行初始化
if (basename($_SERVER['PHP_SELF']) === 'init_database.php') {
    header('Content-Type: text/html; charset=utf-8');

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>数据库初始化</title>';
    echo '<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}';
    echo '.success{color:#10b981;}.error{color:#ef4444;}.info{color:#3b82f6;}</style></head><body>';
    echo '<h1>数据库初始化</h1>';

    if (initDatabase()) {
        echo '<p class="success">✓ 数据库表创建/更新成功！</p>';
        echo '<p class="info">已创建以下数据表：</p>';
        echo '<ul>';
        echo '<li>users - 用户表</li>';
        echo '<li>user_addresses - 用户地址表</li>';
        echo '<li>orders - 订单表</li>';
        echo '<li>order_items - 订单商品表</li>';
        echo '<li>inquiries - 询价记录表</li>';
        echo '</ul>';
        echo '<p class="info">默认管理员账户：admin / admin123</p>';
        echo '<p><a href="/core/index_v4.php">返回首页</a> | <a href="/login.php">去登录</a></p>';
    } else {
        echo '<p class="error">✗ 数据库初始化失败，请检查数据库连接配置。</p>';
    }

    echo '</body></html>';
    exit;
}
