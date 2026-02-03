<?php
/**
 * ==========================================
 * 收货地址管理 - 淘宝风格
 * ==========================================
 */

session_start();
require_once '../config/config.php';

// 检查登录状态
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// 确保地址表存在
$conn->exec("
    CREATE TABLE IF NOT EXISTS user_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(50) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        province VARCHAR(50) NOT NULL,
        city VARCHAR(50) NOT NULL,
        district VARCHAR(50) NOT NULL,
        address TEXT NOT NULL,
        is_default TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $isDefault = isset($_POST['is_default']) ? 1 : 0;

        if (empty($name) || empty($phone) || empty($province) || empty($city) || empty($address)) {
            $_SESSION['error'] = '请填写完整的地址信息';
        } else {
            // 如果设为默认，先取消其他默认
            if ($isDefault) {
                $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
            }

            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, name, phone, province, city, district, address, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $name, $phone, $province, $city, $district, $address, $isDefault]);
                $_SESSION['success'] = '地址添加成功';
            } else {
                $stmt = $conn->prepare("UPDATE user_addresses SET name=?, phone=?, province=?, city=?, district=?, address=?, is_default=? WHERE id=? AND user_id=?");
                $stmt->execute([$name, $phone, $province, $city, $district, $address, $isDefault, $id, $userId]);
                $_SESSION['success'] = '地址修改成功';
            }
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $conn->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
        $_SESSION['success'] = '地址删除成功';
    }

    if ($action === 'set_default') {
        $id = intval($_POST['id'] ?? 0);
        $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
        $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
        $_SESSION['success'] = '默认地址设置成功';
    }

    header('Location: addresses.php');
    exit;
}

// 获取用户地址列表
$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>收货地址管理 - 汇森科技</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --brand-red: #e1251b; }

        .address-card {
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }
        .address-card:hover {
            border-color: #e5e7eb;
        }
        .address-card.default {
            border-color: var(--brand-red);
            background: #fef2f2;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 100;
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- 顶部导航 -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="index_v4.php" class="flex items-center gap-2">
                    <span class="text-2xl font-bold" style="color: var(--brand-red);">汇森科技</span>
                </a>
                <nav class="flex items-center gap-6 text-sm">
                    <a href="index_v4.php" class="text-gray-600 hover:text-gray-900 transition">首页</a>
                    <a href="quotes_v6.php" class="text-gray-600 hover:text-gray-900 transition">手机报价</a>
                    <a href="cart.php" class="text-gray-600 hover:text-gray-900 transition">询价单</a>
                    <a href="profile.php" class="font-medium" style="color: var(--brand-red);">个人中心</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <!-- 面包屑 -->
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="profile.php" class="hover:text-gray-900">个人中心</a>
            <span>/</span>
            <span class="text-gray-900">收货地址</span>
        </div>

        <!-- 页面标题 -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">收货地址管理</h1>
            <button onclick="openModal()" class="px-6 py-2.5 text-white rounded-lg font-medium hover:opacity-90 transition flex items-center gap-2"
                    style="background: var(--brand-red);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                新增地址
            </button>
        </div>

        <!-- 提示消息 -->
        <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- 地址列表 -->
        <?php if (empty($addresses)): ?>
        <div class="bg-white rounded-2xl shadow-sm p-16 text-center">
            <div class="text-6xl mb-4">📍</div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">还没有收货地址</h3>
            <p class="text-gray-500 mb-6">添加收货地址，方便下单时快速选择</p>
            <button onclick="openModal()" class="inline-block px-8 py-3 text-white rounded-lg font-medium hover:opacity-90 transition"
                    style="background: var(--brand-red);">
                添加第一个地址
            </button>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($addresses as $addr): ?>
            <div class="address-card bg-white rounded-xl p-6 shadow-sm <?php echo $addr['is_default'] ? 'default' : ''; ?>">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="font-bold text-gray-900"><?php echo htmlspecialchars($addr['name']); ?></span>
                            <span class="text-gray-600"><?php echo htmlspecialchars($addr['phone']); ?></span>
                            <?php if ($addr['is_default']): ?>
                            <span class="px-2 py-0.5 text-xs text-white rounded" style="background: var(--brand-red);">默认</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-600">
                            <?php echo htmlspecialchars($addr['province'] . ' ' . $addr['city'] . ' ' . $addr['district']); ?>
                            <br>
                            <?php echo htmlspecialchars($addr['address']); ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <?php if (!$addr['is_default']): ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="set_default">
                            <input type="hidden" name="id" value="<?php echo $addr['id']; ?>">
                            <button type="submit" class="text-gray-500 hover:text-gray-900 transition">设为默认</button>
                        </form>
                        <span class="text-gray-300">|</span>
                        <?php endif; ?>
                        <button onclick="editAddress(<?php echo htmlspecialchars(json_encode($addr)); ?>)" class="text-blue-600 hover:text-blue-800 transition">编辑</button>
                        <span class="text-gray-300">|</span>
                        <form method="POST" class="inline" onsubmit="return confirm('确定要删除这个地址吗？');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $addr['id']; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 transition">删除</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <!-- 添加/编辑地址弹窗 -->
    <div class="modal" id="addressModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b flex items-center justify-between">
                <h3 class="text-lg font-bold" id="modalTitle">新增收货地址</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-5" id="addressForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="addressId" value="">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">收货人</label>
                        <input type="text" name="name" id="addrName" required
                               class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                               placeholder="请输入收货人姓名">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">手机号</label>
                        <input type="tel" name="phone" id="addrPhone" required
                               class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                               placeholder="请输入手机号">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">省份</label>
                        <input type="text" name="province" id="addrProvince" required
                               class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                               placeholder="省份">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">城市</label>
                        <input type="text" name="city" id="addrCity" required
                               class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                               placeholder="城市">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">区/县</label>
                        <input type="text" name="district" id="addrDistrict"
                               class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                               placeholder="区/县">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">详细地址</label>
                    <textarea name="address" id="addrAddress" required rows="3"
                              class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500 resize-none"
                              placeholder="街道、门牌号、小区、楼栋号等"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_default" id="addrDefault" class="w-4 h-4 text-red-600 rounded">
                    <label for="addrDefault" class="text-sm text-gray-600">设为默认收货地址</label>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeModal()" class="flex-1 px-6 py-3 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                        取消
                    </button>
                    <button type="submit" class="flex-1 px-6 py-3 text-white rounded-lg font-medium hover:opacity-90 transition"
                            style="background: var(--brand-red);">
                        保存地址
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openModal() {
        document.getElementById('addressModal').classList.add('show');
        document.getElementById('modalTitle').textContent = '新增收货地址';
        document.getElementById('formAction').value = 'add';
        document.getElementById('addressForm').reset();
    }

    function closeModal() {
        document.getElementById('addressModal').classList.remove('show');
    }

    function editAddress(addr) {
        document.getElementById('addressModal').classList.add('show');
        document.getElementById('modalTitle').textContent = '编辑收货地址';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('addressId').value = addr.id;
        document.getElementById('addrName').value = addr.name;
        document.getElementById('addrPhone').value = addr.phone;
        document.getElementById('addrProvince').value = addr.province;
        document.getElementById('addrCity').value = addr.city;
        document.getElementById('addrDistrict').value = addr.district || '';
        document.getElementById('addrAddress').value = addr.address;
        document.getElementById('addrDefault').checked = addr.is_default == 1;
    }

    // 点击遮罩关闭
    document.getElementById('addressModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    </script>
</body>
</html>
