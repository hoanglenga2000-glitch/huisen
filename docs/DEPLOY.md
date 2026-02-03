# 甘肃汇森 - 宝塔面板部署指南

## 📋 部署前准备

### 1. 文件清理

在部署前，请先运行清理脚本删除临时文件：

```bash
# 方式1：通过浏览器访问
访问：http://your-domain/cleanup_temp_files.php

# 方式2：命令行执行
php cleanup_temp_files.php
```

### 2. 需要上传的文件

**核心文件（必须）：**
- `config/` - 配置文件目录
- `api/` - API 接口目录
- `images/` - 图片资源目录
- `product_details/` - 产品详情 JSON 文件目录
- `sql/` - 数据库 SQL 文件目录
- `quotes.php` - 报价页面
- `product_detail.php` - 产品详情页
- `dashboard.php` - 管理后台
- `login.php` - 登录页面
- `index.php` - 首页
- `style.css` - 样式文件

**可选文件：**
- `collect_phone_images.py` - Python 图片采集脚本（如需使用）
- `import_database.php` - 数据库一键导入工具
- `README.md` - 说明文档

**不需要上传的文件：**
- `cleanup_temp_files.php` - 清理脚本（本地使用）
- `*.tmp` - 所有临时文件
- `map_images_to_products.php` - 开发脚本
- `execute_image_updates.php` - 开发脚本
- `get_phone_images.php` - 开发脚本

---

## 🚀 部署步骤

### 第一步：上传文件到宝塔面板

1. 登录宝塔面板
2. 进入 **文件** → 选择网站根目录（通常是 `/www/wwwroot/your-domain/`）
3. 上传所有文件（建议使用 FTP 或宝塔文件管理器）

### 第二步：配置数据库

#### 方法1：使用一键导入工具（推荐）

1. 访问：`http://your-domain/import_database.php`
2. 点击 **"开始导入数据库"** 按钮
3. 等待导入完成
4. **重要：导入完成后请删除或重命名 `import_database.php` 文件**

#### 方法2：手动导入

1. 登录宝塔面板 → **数据库** → **phpMyAdmin**
2. 创建数据库（名称：`甘肃汇森`，字符集：`utf8mb4`）
3. 创建数据库用户（用户名：`huisen`，密码：`123456`）
4. 授权用户访问数据库
5. 选择数据库，点击 **导入**
6. 按顺序导入以下 SQL 文件：
   - `sql/init.sql`
   - `sql/phone_details.sql`
   - `sql/update_schema.sql`

### 第三步：修改数据库配置

编辑 `config/config.php` 文件，根据宝塔面板的数据库配置修改：

```php
define('DB_HOST', 'localhost');        // 数据库主机（通常是 localhost）
define('DB_PORT', '3306');             // 数据库端口（通常是 3306）
define('DB_NAME', '甘肃汇森');           // 数据库名
define('DB_USER', 'huisen');           // 数据库用户名
define('DB_PASS', '123456');           // 数据库密码
define('DB_CHARSET', 'utf8mb4');       // 字符集
```

### 第四步：设置文件权限

在宝塔面板中，确保以下目录有写入权限：

- `images/phones/` - 图片上传目录
- `product_details/` - 产品详情 JSON 文件目录

**设置方法：**
1. 右键点击目录 → **权限**
2. 设置为 `755` 或 `777`（如果仍有问题）

### 第五步：配置网站域名

1. 进入宝塔面板 → **网站** → 选择你的网站
2. 点击 **设置** → **网站目录**
3. 确保运行目录指向项目根目录
4. 在 **域名管理** 中添加你的域名（如：`huisen.zhjjq.tech`）

### 第六步：测试访问

1. 访问首页：`http://your-domain/`
2. 访问报价页面：`http://your-domain/quotes.php`
3. 访问管理后台：`http://your-domain/dashboard.php`

---

## 🔒 安全设置

### 1. 删除敏感文件

部署完成后，请删除以下文件：

```bash
# 数据库导入工具（已使用后）
import_database.php

# 清理脚本（已使用后）
cleanup_temp_files.php
```

### 2. 设置文件权限

- PHP 文件：`644`
- 目录：`755`
- 上传目录：`755` 或 `777`

### 3. 配置防火墙

在宝塔面板中：
1. 进入 **安全** → **防火墙**
2. 只开放必要的端口（80, 443, 22）

---

## 📝 数据库导入说明

### 使用一键导入工具

1. 访问 `http://your-domain/import_database.php`
2. 查看将要导入的 SQL 文件列表
3. 确认数据库配置信息
4. 点击 **"开始导入数据库"**
5. 等待导入完成
6. **导入完成后立即删除该文件**

### SQL 文件说明

- `sql/init.sql` - 初始化数据库表结构（业务统计表等）
- `sql/phone_details.sql` - 产品详情表结构
- `sql/update_schema.sql` - 数据库更新脚本（添加字段等）

---

## 🐍 Python 脚本使用（可选）

如果需要使用图片采集脚本：

1. 在服务器上安装 Python 3.7+
2. 安装依赖：
   ```bash
   pip install requests beautifulsoup4 pillow pymysql
   ```
3. 修改 `collect_phone_images.py` 中的数据库配置
4. 运行脚本：
   ```bash
   python collect_phone_images.py
   ```

---

## ⚠️ 常见问题

### 1. 数据库连接失败

- 检查 `config/config.php` 中的数据库配置
- 确认数据库用户有访问权限
- 确认数据库已创建

### 2. 图片无法显示

- 检查 `images/` 目录权限
- 确认图片文件已上传
- 检查文件路径是否正确

### 3. 页面显示乱码

- 确认数据库字符集为 `utf8mb4`
- 确认 PHP 文件编码为 `UTF-8`
- 在宝塔面板中设置网站默认字符集为 `UTF-8`

### 4. 权限错误

- 在宝塔面板中检查文件/目录权限
- 确保 PHP 运行用户有读取权限

---

## 📞 技术支持

如有问题，请检查：
1. 宝塔面板错误日志：**网站** → **设置** → **日志**
2. PHP 错误日志：**软件商店** → **PHP** → **设置** → **日志**
3. MySQL 错误日志：**数据库** → **日志**

---

## ✅ 部署检查清单

- [ ] 文件已上传到服务器
- [ ] 数据库已创建并导入
- [ ] `config/config.php` 已配置正确
- [ ] 文件权限已设置
- [ ] 网站域名已配置
- [ ] 首页可以正常访问
- [ ] 报价页面可以正常访问
- [ ] 管理后台可以正常登录
- [ ] 敏感文件已删除（`import_database.php` 等）
- [ ] 图片可以正常显示

---

**部署完成后，建议删除 `import_database.php` 和 `cleanup_temp_files.php` 以确保安全！**
