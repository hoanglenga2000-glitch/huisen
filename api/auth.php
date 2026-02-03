<?php
/**
 * ============================================
 * 甘肃汇森信息科技有限公司 - 认证 API
 * ============================================
 * 
 * 接口说明：
 * POST /api/auth.php?action=login    用户登录
 * POST /api/auth.php?action=logout   用户登出
 * GET  /api/auth.php?action=check    检查登录状态
 */

// 开启 Session
session_start();

// 引入配置文件
require_once __DIR__ . '/../config/config.php';

// 获取请求方法和操作类型
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : 'check';

// 处理OPTIONS预检请求（CORS）
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
    exit(0);
}

// 获取数据库实例
$db = Database::getInstance();
$conn = $db->getConnection();

// 确保用户表存在
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
        INDEX idx_phone (phone)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// 检查并添加缺失的列（兼容旧表结构）
try {
    // 检查 phone 列是否存在
    $columns = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'")->fetchAll();
    if (empty($columns)) {
        $conn->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) UNIQUE AFTER password");
        $conn->exec("ALTER TABLE users ADD INDEX idx_phone (phone)");
    }
} catch (Exception $e) {
    // 如果添加失败，可能是索引已存在，忽略错误
}

try {
    // 检查 email 列是否存在
    $columns = $conn->query("SHOW COLUMNS FROM users LIKE 'email'")->fetchAll();
    if (empty($columns)) {
        $conn->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) AFTER phone");
    }
} catch (Exception $e) {
    // 忽略错误
}

try {
    // 检查 updated_at 列是否存在
    $columns = $conn->query("SHOW COLUMNS FROM users LIKE 'updated_at'")->fetchAll();
    if (empty($columns)) {
        $conn->exec("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    }
} catch (Exception $e) {
    // 忽略错误
}

// 确保 role 枚举值包含 'user'（兼容旧表可能只有 'admin', 'manager', 'staff'）
try {
    $conn->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'manager', 'staff') DEFAULT 'user'");
} catch (Exception $e) {
    // 如果修改失败，可能是权限问题或列已存在，忽略错误
}

// ============================================
// 路由处理
// ============================================
try {
    switch ($action) {
        case 'login':
            // 用户登录
            if ($method !== 'POST') {
                errorResponse('请使用POST方法', 405);
            }
            handleLogin($db);
            break;
            
        case 'logout':
            // 用户登出
            handleLogout();
            break;
            
        case 'check':
            // 检查登录状态
            checkAuthStatus();
            break;
            
        case 'change_password':
            // 修改密码
            if ($method !== 'POST') {
                errorResponse('请使用POST方法', 405);
            }
            handleChangePassword($db);
            break;

        case 'register':
            // 用户注册
            if ($method !== 'POST') {
                errorResponse('请使用POST方法', 405);
            }
            handleRegister($db);
            break;

        case 'update_profile':
            // 更新用户资料
            if ($method !== 'POST') {
                errorResponse('请使用POST方法', 405);
            }
            handleUpdateProfile($db);
            break;

        case 'get_profile':
            // 获取用户资料
            handleGetProfile($db);
            break;

        default:
            errorResponse('未知的操作类型', 400);
    }
} catch (Exception $e) {
    errorResponse($e->getMessage(), 500);
}

// ============================================
// API 函数定义
// ============================================

/**
 * 处理用户登录
 */
function handleLogin($db) {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        $input = $_POST;
    }
    
    // 验证必填字段
    if (empty($input['username']) || empty($input['password'])) {
        errorResponse('用户名和密码不能为空', 400);
    }
    
    $username = trim($input['username']);
    $password = $input['password'];
    
    // 查询用户
    $sql = "SELECT id, username, password, real_name, role, status FROM users WHERE username = ? LIMIT 1";
    $user = $db->fetchOne($sql, [$username]);
    
    // 验证用户是否存在
    if (!$user) {
        errorResponse('用户名或密码错误', 401);
    }
    
    // 验证用户状态
    if ($user['status'] != 1) {
        errorResponse('账户已被禁用', 403);
    }
    
    // 验证密码
    if (!password_verify($password, $user['password'])) {
        errorResponse('用户名或密码错误', 401);
    }
    
    // 登录成功，设置 Session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['real_name'] = $user['real_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    
    // 返回成功响应
    successResponse([
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'real_name' => $user['real_name'],
            'role' => $user['role']
        ]
    ], '登录成功');
}

/**
 * 处理用户登出
 */
function handleLogout() {
    // 销毁 Session
    session_unset();
    session_destroy();
    
    // 返回成功响应
    successResponse([], '登出成功');
}

/**
 * 检查登录状态
 */
function checkAuthStatus() {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        successResponse([
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'real_name' => $_SESSION['real_name'],
                'role' => $_SESSION['role']
            ]
        ], '已登录');
    } else {
        successResponse([
            'logged_in' => false
        ], '未登录');
    }
}

/**
 * 处理修改密码
 */
function handleChangePassword($db) {
    // 检查是否已登录
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
        errorResponse('请先登录', 401);
    }
    
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        $input = $_POST;
    }
    
    // 验证必填字段
    if (empty($input['old_password']) || empty($input['new_password'])) {
        errorResponse('旧密码和新密码不能为空', 400);
    }
    
    $oldPassword = $input['old_password'];
    $newPassword = $input['new_password'];
    $userId = $_SESSION['user_id'];
    
    // 验证新密码长度（至少6位）
    if (strlen($newPassword) < 6) {
        errorResponse('新密码长度至少为6位', 400);
    }
    
    // 查询当前用户信息
    $sql = "SELECT id, password FROM users WHERE id = ? LIMIT 1";
    $user = $db->fetchOne($sql, [$userId]);
    
    if (!$user) {
        errorResponse('用户不存在', 404);
    }
    
    // 验证旧密码
    if (!password_verify($oldPassword, $user['password'])) {
        errorResponse('旧密码错误', 401);
    }
    
    // 加密新密码
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // 更新数据库
    try {
        $updateSql = "UPDATE users SET password = ? WHERE id = ?";
        $db->query($updateSql, [$hashedPassword, $userId]);
        
        // 返回成功响应
        successResponse([], '密码修改成功');
    } catch (Exception $e) {
        errorResponse('密码修改失败：' . $e->getMessage(), 500);
    }
}

/**
 * 处理用户注册
 */
function handleRegister($db) {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input)) {
        $input = $_POST;
    }

    // 验证必填字段
    if (empty($input['username']) || empty($input['password']) || empty($input['phone']) || empty($input['real_name'])) {
        errorResponse('请填写所有必填字段', 400);
    }

    $username = trim($input['username']);
    $password = $input['password'];
    $phone = trim($input['phone']);
    $realName = trim($input['real_name']);

    // 验证用户名格式
    if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        errorResponse('用户名格式不正确（4-20位字母数字下划线）', 400);
    }

    // 验证手机号格式
    if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
        errorResponse('手机号格式不正确', 400);
    }

    // 验证密码长度
    if (strlen($password) < 6) {
        errorResponse('密码长度至少6位', 400);
    }

    // 检查用户名是否已存在
    $checkSql = "SELECT id FROM users WHERE username = ? LIMIT 1";
    $existingUser = $db->fetchOne($checkSql, [$username]);
    if ($existingUser) {
        errorResponse('用户名已被注册', 400);
    }

    // 检查手机号是否已存在（如果 phone 列存在）
    try {
        $checkPhoneSql = "SELECT id FROM users WHERE phone = ? LIMIT 1";
        $existingPhone = $db->fetchOne($checkPhoneSql, [$phone]);
        if ($existingPhone) {
            errorResponse('手机号已被注册', 400);
        }
    } catch (Exception $e) {
        // 如果 phone 列不存在，忽略检查（理论上不应该发生，因为前面已经添加了列）
        // 记录错误但不阻止注册
        error_log("检查手机号时出错: " . $e->getMessage());
    }

    // 加密密码
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 插入用户
    try {
        $insertSql = "INSERT INTO users (username, password, phone, real_name, role, status, created_at) VALUES (?, ?, ?, ?, 'user', 1, NOW())";
        $db->query($insertSql, [$username, $hashedPassword, $phone, $realName]);

        successResponse([], '注册成功');
    } catch (Exception $e) {
        errorResponse('注册失败：' . $e->getMessage(), 500);
    }
}

/**
 * 处理更新用户资料
 */
function handleUpdateProfile($db) {
    // 检查是否已登录
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
        errorResponse('请先登录', 401);
    }

    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input)) {
        $input = $_POST;
    }

    $userId = $_SESSION['user_id'];
    $updates = [];
    $params = [];

    // 可更新的字段
    if (!empty($input['real_name'])) {
        $updates[] = 'real_name = ?';
        $params[] = trim($input['real_name']);
    }

    if (!empty($input['phone'])) {
        $phone = trim($input['phone']);
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            errorResponse('手机号格式不正确', 400);
        }
        // 检查手机号是否被其他用户使用
        $checkSql = "SELECT id FROM users WHERE phone = ? AND id != ? LIMIT 1";
        $existing = $db->fetchOne($checkSql, [$phone, $userId]);
        if ($existing) {
            errorResponse('手机号已被其他用户使用', 400);
        }
        $updates[] = 'phone = ?';
        $params[] = $phone;
    }

    if (!empty($input['email'])) {
        $email = trim($input['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            errorResponse('邮箱格式不正确', 400);
        }
        $updates[] = 'email = ?';
        $params[] = $email;
    }

    if (empty($updates)) {
        errorResponse('没有要更新的内容', 400);
    }

    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";

    try {
        $db->query($sql, $params);

        // 更新 Session
        if (!empty($input['real_name'])) {
            $_SESSION['real_name'] = trim($input['real_name']);
        }

        successResponse([], '资料更新成功');
    } catch (Exception $e) {
        errorResponse('更新失败：' . $e->getMessage(), 500);
    }
}

/**
 * 获取用户资料
 */
function handleGetProfile($db) {
    // 检查是否已登录
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
        errorResponse('请先登录', 401);
    }

    $userId = $_SESSION['user_id'];

    $sql = "SELECT id, username, real_name, phone, email, role, created_at FROM users WHERE id = ? LIMIT 1";
    $user = $db->fetchOne($sql, [$userId]);

    if (!$user) {
        errorResponse('用户不存在', 404);
    }

    successResponse(['user' => $user], '获取成功');
}

/**
 * 检查用户是否已登录（供其他文件调用）
 * @return bool|array 如果已登录返回用户信息数组，否则返回 false
 */
function requireAuth() {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'real_name' => $_SESSION['real_name'],
            'role' => $_SESSION['role']
        ];
    }
    return false;
}