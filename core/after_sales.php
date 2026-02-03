<?php
/**
 * ==========================================
 * 汇森科技 - 售后服务中心 v1.0
 * 九机网专业风格
 * ==========================================
 *
 * 功能：
 * 1. 售后工单提交表单
 * 2. 工单状态追踪（进度条）
 * 3. 我的售后工单列表
 * 4. 工单详情查看
 */

session_start();
require_once '../config/config.php';

// 权限验证
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php?redirect=after_sales');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? '';

$message = '';
$message_type = '';

// 工单状态配置
$status_config = [
    'pending' => ['name' => '待审核', 'icon' => '📋', 'color' => '#FF9800', 'step' => 1],
    'received' => ['name' => '已收到设备', 'icon' => '📦', 'color' => '#2196F3', 'step' => 2],
    'testing' => ['name' => '检测中', 'icon' => '🔍', 'color' => '#9C27B0', 'step' => 3],
    'quoting' => ['name' => '报价维修', 'icon' => '💰', 'color' => '#FF5722', 'step' => 4],
    'repairing' => ['name' => '维修中', 'icon' => '🔧', 'color' => '#673AB7', 'step' => 5],
    'shipped' => ['name' => '已寄回', 'icon' => '🚚', 'color' => '#4CAF50', 'step' => 6],
    'completed' => ['name' => '已完成', 'icon' => '✅', 'color' => '#4CAF50', 'step' => 7],
];

// 维修类型配置
$repair_types = [
    'screen' => '屏幕维修/更换',
    'battery' => '电池更换',
    'camera' => '摄像头维修',
    'speaker' => '扬声器/听筒维修',
    'charging' => '充电口维修',
    'motherboard' => '主板维修',
    'water_damage' => '进水处理',
    'other' => '其他问题',
];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'submit_ticket') {
        $phone_brand = trim($_POST['phone_brand'] ?? '');
        $phone_model = trim($_POST['phone_model'] ?? '');
        $serial_number = trim($_POST['serial_number'] ?? '');
        $repair_type = $_POST['repair_type'] ?? '';
        $fault_description = trim($_POST['fault_description'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $contact_name = trim($_POST['contact_name'] ?? '');

        if (empty($phone_model) || empty($fault_description) || empty($contact_phone)) {
            $message = '请填写必填项目';
            $message_type = 'error';
        } else {
            try {
                // 检查售后工单表是否存在
                $table_check = $conn->query("SHOW TABLES LIKE 'after_sales_tickets'")->rowCount();
                if ($table_check == 0) {
                    // 创建表
                    $conn->exec("CREATE TABLE after_sales_tickets (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        ticket_no VARCHAR(20) NOT NULL,
                        user_id INT NOT NULL,
                        username VARCHAR(50),
                        phone_brand VARCHAR(50),
                        phone_model VARCHAR(100) NOT NULL,
                        serial_number VARCHAR(100),
                        repair_type VARCHAR(50),
                        fault_description TEXT NOT NULL,
                        contact_name VARCHAR(50),
                        contact_phone VARCHAR(20) NOT NULL,
                        status VARCHAR(20) DEFAULT 'pending',
                        admin_notes TEXT,
                        quote_amount DECIMAL(10,2),
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_user (user_id),
                        INDEX idx_status (status),
                        INDEX idx_ticket_no (ticket_no)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                // 生成工单号
                $ticket_no = 'AS' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

                $sql = "INSERT INTO after_sales_tickets
                        (ticket_no, user_id, username, phone_brand, phone_model, serial_number, repair_type, fault_description, contact_name, contact_phone)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $ticket_no, $user_id, $username, $phone_brand, $phone_model,
                    $serial_number, $repair_type, $fault_description, $contact_name, $contact_phone
                ]);

                $message = "工单提交成功！工单号：{$ticket_no}";
                $message_type = 'success';

            } catch (Exception $e) {
                $message = '提交失败：' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// 获取用户的售后工单
$tickets = [];
try {
    $table_check = $conn->query("SHOW TABLES LIKE 'after_sales_tickets'")->rowCount();
    if ($table_check > 0) {
        $stmt = $conn->prepare("SELECT * FROM after_sales_tickets WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // 表可能不存在
}

// 查看单个工单详情
$view_ticket = null;
if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM after_sales_tickets WHERE ticket_no = ? AND user_id = ?");
        $stmt->execute([$_GET['id'], $user_id]);
        $view_ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // 忽略
    }
}

$base_path = '../';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>售后服务中心 - 汇森科技</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        :root { --brand-red: #D32F2F; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #F5F5F5; }

        /* 进度条样式 */
        .progress-track {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 40px 0;
        }
        .progress-track::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 4px;
            background: #E0E0E0;
            z-index: 0;
        }
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        .progress-step .dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #E0E0E0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 8px;
            transition: all 0.3s;
        }
        .progress-step.active .dot {
            background: var(--brand-red);
            box-shadow: 0 4px 12px rgba(211,47,47,0.3);
        }
        .progress-step.completed .dot {
            background: #4CAF50;
        }
        .progress-step .label {
            font-size: 12px;
            color: #999;
            text-align: center;
        }
        .progress-step.active .label,
        .progress-step.completed .label {
            color: #333;
            font-weight: 600;
        }

        /* 工单卡片 */
        .ticket-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #EEEEEE;
            transition: all 0.2s;
        }
        .ticket-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            border-color: var(--brand-red);
        }

        /* 表单样式 */
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E0E0E0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--brand-red);
        }
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-label .required {
            color: var(--brand-red);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <!-- 页面标题 -->
        <div class="mb-8">
            <nav class="text-sm text-gray-500 mb-4">
                <a href="user_center.php" class="hover:text-red-600">会员中心</a>
                <span class="mx-2">›</span>
                <span class="text-gray-800">售后服务</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">🔧 售后服务中心</h1>
            <p class="text-gray-500">专业维修服务，全程可追踪</p>
        </div>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($view_ticket): ?>
        <!-- 工单详情视图 -->
        <div class="bg-white rounded-xl p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold">工单详情</h2>
                    <p class="text-gray-500">工单号：<?php echo htmlspecialchars($view_ticket['ticket_no']); ?></p>
                </div>
                <a href="after_sales.php" class="text-red-600 hover:underline">← 返回列表</a>
            </div>

            <!-- 进度追踪 -->
            <div class="bg-gray-50 rounded-xl p-6 mb-6">
                <h3 class="font-bold mb-4">📍 维修进度</h3>
                <?php
                $current_step = $status_config[$view_ticket['status']]['step'] ?? 1;
                $progress_steps = ['pending', 'received', 'testing', 'quoting', 'repairing', 'shipped', 'completed'];
                ?>
                <div class="progress-track">
                    <?php foreach ($progress_steps as $step):
                        $step_num = $status_config[$step]['step'];
                        $is_completed = $step_num < $current_step;
                        $is_active = $step_num == $current_step;
                    ?>
                    <div class="progress-step <?php echo $is_completed ? 'completed' : ($is_active ? 'active' : ''); ?>">
                        <div class="dot"><?php echo $status_config[$step]['icon']; ?></div>
                        <div class="label"><?php echo $status_config[$step]['name']; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 设备信息 -->
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="font-bold mb-4">📱 设备信息</h3>
                    <div class="space-y-3">
                        <div class="flex">
                            <span class="text-gray-500 w-24">品牌</span>
                            <span class="font-medium"><?php echo htmlspecialchars($view_ticket['phone_brand'] ?: '-'); ?></span>
                        </div>
                        <div class="flex">
                            <span class="text-gray-500 w-24">型号</span>
                            <span class="font-medium"><?php echo htmlspecialchars($view_ticket['phone_model']); ?></span>
                        </div>
                        <div class="flex">
                            <span class="text-gray-500 w-24">序列号</span>
                            <span class="font-medium font-mono"><?php echo htmlspecialchars($view_ticket['serial_number'] ?: '-'); ?></span>
                        </div>
                        <div class="flex">
                            <span class="text-gray-500 w-24">维修类型</span>
                            <span class="font-medium"><?php echo $repair_types[$view_ticket['repair_type']] ?? $view_ticket['repair_type']; ?></span>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="font-bold mb-4">👤 联系信息</h3>
                    <div class="space-y-3">
                        <div class="flex">
                            <span class="text-gray-500 w-24">联系人</span>
                            <span class="font-medium"><?php echo htmlspecialchars($view_ticket['contact_name'] ?: $username); ?></span>
                        </div>
                        <div class="flex">
                            <span class="text-gray-500 w-24">电话</span>
                            <span class="font-medium"><?php echo htmlspecialchars($view_ticket['contact_phone']); ?></span>
                        </div>
                        <div class="flex">
                            <span class="text-gray-500 w-24">提交时间</span>
                            <span class="font-medium"><?php echo $view_ticket['created_at']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 故障描述 -->
            <div class="mb-6">
                <h3 class="font-bold mb-4">📝 故障描述</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <?php echo nl2br(htmlspecialchars($view_ticket['fault_description'])); ?>
                </div>
            </div>

            <?php if ($view_ticket['quote_amount']): ?>
            <!-- 维修报价 -->
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                <h3 class="font-bold text-orange-700 mb-2">💰 维修报价</h3>
                <div class="text-2xl font-bold text-orange-600 font-mono">
                    ¥<?php echo number_format($view_ticket['quote_amount'], 2); ?>
                </div>
                <p class="text-sm text-orange-600 mt-2">请确认是否同意维修，如有疑问请联系客服</p>
            </div>
            <?php endif; ?>

            <?php if ($view_ticket['admin_notes']): ?>
            <!-- 客服备注 -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-bold text-blue-700 mb-2">💬 客服备注</h3>
                <p class="text-blue-600"><?php echo nl2br(htmlspecialchars($view_ticket['admin_notes'])); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <!-- 工单列表和提交表单 -->
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- 左侧：提交新工单 -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl p-6">
                    <h2 class="font-bold text-lg mb-6">📋 提交维修申请</h2>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="submit_ticket">

                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- 手机品牌 -->
                            <div>
                                <label class="form-label">手机品牌</label>
                                <select name="phone_brand" class="form-input">
                                    <option value="">请选择</option>
                                    <option value="Apple">Apple</option>
                                    <option value="Huawei">华为</option>
                                    <option value="Xiaomi">小米</option>
                                    <option value="OPPO">OPPO</option>
                                    <option value="vivo">vivo</option>
                                    <option value="Honor">荣耀</option>
                                    <option value="Samsung">三星</option>
                                    <option value="其他">其他</option>
                                </select>
                            </div>

                            <!-- 手机型号 -->
                            <div>
                                <label class="form-label">手机型号 <span class="required">*</span></label>
                                <input type="text" name="phone_model" class="form-input"
                                       placeholder="如：iPhone 16 Pro Max" required>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- 序列号 -->
                            <div>
                                <label class="form-label">手机序列号/IMEI</label>
                                <input type="text" name="serial_number" class="form-input"
                                       placeholder="设置 > 通用 > 关于本机 查看">
                            </div>

                            <!-- 维修类型 -->
                            <div>
                                <label class="form-label">维修类型 <span class="required">*</span></label>
                                <select name="repair_type" class="form-input" required>
                                    <option value="">请选择</option>
                                    <?php foreach ($repair_types as $key => $name): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- 故障描述 -->
                        <div>
                            <label class="form-label">故障描述 <span class="required">*</span></label>
                            <textarea name="fault_description" class="form-input" rows="4"
                                      placeholder="请详细描述手机的故障情况，如：屏幕碎裂、无法开机、进水等..." required></textarea>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- 联系人 -->
                            <div>
                                <label class="form-label">联系人</label>
                                <input type="text" name="contact_name" class="form-input"
                                       value="<?php echo htmlspecialchars($_SESSION['real_name'] ?? $username); ?>">
                            </div>

                            <!-- 联系电话 -->
                            <div>
                                <label class="form-label">联系电话 <span class="required">*</span></label>
                                <input type="tel" name="contact_phone" class="form-input"
                                       placeholder="请输入手机号码" required>
                            </div>
                        </div>

                        <!-- 提交按钮 -->
                        <button type="submit"
                                class="w-full py-4 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition">
                            📤 提交维修申请
                        </button>
                    </form>
                </div>

                <!-- 服务说明 -->
                <div class="bg-white rounded-xl p-6 mt-6">
                    <h3 class="font-bold mb-4">📖 服务说明</h3>
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">📦</span>
                            <div>
                                <div class="font-medium">邮寄维修</div>
                                <div class="text-gray-500">全国顺丰上门取件</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">🔧</span>
                            <div>
                                <div class="font-medium">专业检测</div>
                                <div class="text-gray-500">免费检测，先报价后维修</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">✅</span>
                            <div>
                                <div class="font-medium">质量保障</div>
                                <div class="text-gray-500">原装配件，90天质保</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">⚡</span>
                            <div>
                                <div class="font-medium">快速维修</div>
                                <div class="text-gray-500">一般3-5个工作日完成</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 右侧：我的工单 -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl p-6 sticky top-20">
                    <h2 class="font-bold text-lg mb-4">📑 我的售后工单</h2>

                    <?php if (empty($tickets)): ?>
                    <div class="text-center py-8">
                        <div class="text-4xl mb-3">📭</div>
                        <p class="text-gray-500">暂无售后工单</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($tickets as $ticket):
                            $status = $status_config[$ticket['status']] ?? $status_config['pending'];
                        ?>
                        <a href="?id=<?php echo urlencode($ticket['ticket_no']); ?>" class="ticket-card block">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-mono text-sm text-gray-500"><?php echo $ticket['ticket_no']; ?></span>
                                <span class="px-2 py-1 rounded text-xs font-medium"
                                      style="background: <?php echo $status['color']; ?>20; color: <?php echo $status['color']; ?>">
                                    <?php echo $status['icon']; ?> <?php echo $status['name']; ?>
                                </span>
                            </div>
                            <div class="font-medium text-gray-800 mb-1">
                                <?php echo htmlspecialchars($ticket['phone_model']); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo $repair_types[$ticket['repair_type']] ?? $ticket['repair_type']; ?>
                            </div>
                            <div class="text-xs text-gray-400 mt-2">
                                <?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <?php include '../includes/sidebar-tools.php'; ?>
    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>
