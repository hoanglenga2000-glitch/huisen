# 甘肃汇森信息科技有限公司 - 双门户系统

## 📋 项目简介

本项目是一个**双门户企业数据管理系统**，包含：
- **对外门户** (`index.php`): 面向公众的公司官网，集成对外客服 AI
- **内部门户** (`dashboard.php`): 面向员工的数据大屏，需要登录，集成内部财务 AI

## 📁 目录结构

```
huisen_web/                # [根目录] 用于上传服务器
├── api/                   # [后端] API 接口
│   ├── api.php           # 业务数据接口 (需登录访问)
│   └── auth.php          # 身份验证接口 (登录/退出/修改密码)
├── config/                # [配置] 数据库配置
│   └── config.php        # 数据库连接 (PDO模式)
├── sql/                   # [数据库] 初始化脚本
│   └── init.sql          # 建表与初始数据
├── index.php             # [前端-对外] 公司官网 (游客访问，含客服AI)
├── login.php             # [前端-入口] 员工登录页 (玻璃拟态风格)
├── dashboard.php         # [前端-对内] 内部数据大屏 (需登录，含财务AI+改密)
└── README.md             # 部署说明
```

## 🚀 部署步骤

### 1. 环境要求

- **PHP**: 7.4+ (推荐 7.4 或 8.0)
- **MySQL**: 5.7+ (推荐 5.7 或 8.0)
- **Web服务器**: Apache / Nginx / phpStudy
- **扩展**: PDO, PDO_MySQL, Session

### 2. 数据库配置

#### 方式一：使用 phpMyAdmin

1. 打开 phpMyAdmin
2. 选择数据库（或创建新数据库 `甘肃汇森`）
3. 导入 `sql/init.sql` 文件

#### 方式二：使用 MySQL 命令行

```bash
# 登录 MySQL
mysql -u root -p

# 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `甘肃汇森` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 使用数据库
USE `甘肃汇森`;

# 导入 SQL 文件
SOURCE /path/to/sql/init.sql;
```

### 3. 修改配置文件

编辑 `config/config.php`，修改以下数据库连接参数：

```php
define('DB_HOST', 'localhost');        // 数据库主机
define('DB_PORT', '3306');             // 数据库端口
define('DB_NAME', '甘肃汇森');           // 数据库名
define('DB_USER', 'huisen');           // 数据库用户名
define('DB_PASS', '123456');           // 数据库密码
```

### 4. 上传文件

将整个 `huisen_web` 目录上传到服务器 Web 根目录（如 `htdocs` 或 `www`）。

### 5. 设置权限

确保 Web 服务器对以下目录有读写权限：
- `config/` 目录（如果使用配置文件缓存）

### 6. 访问系统

- **对外门户**: `http://your-domain/index.php`
- **员工登录**: `http://your-domain/login.php`
- **内部大屏**: `http://your-domain/dashboard.php` (需登录)

## 🔐 默认账号

- **用户名**: `admin`
- **密码**: `admin`

**⚠️ 重要提示**: 首次登录后请立即修改密码！

## 🎨 功能特性

### 对外门户 (`index.php`)

- ✅ 公司官网展示（Hero、关于我们、页脚）
- ✅ 粒子背景动画效果
- ✅ 集成 Coze 对外客服 AI（Bot ID: `7595849107479543808`）
- ✅ 响应式设计，支持移动端

### 内部门户 (`dashboard.php`)

- ✅ 业务数据仪表盘（统计卡片、图表、数据表格）
- ✅ ECharts 可视化图表（柱状图、饼图、雷达图、折线图）
- ✅ 集成 Coze 内部财务 AI（Bot ID: `7595051655952597026`）
- ✅ 修改密码功能
- ✅ 权限保护（需登录访问）

### 登录系统 (`login.php`)

- ✅ 玻璃拟态设计风格
- ✅ AJAX 异步登录
- ✅ 平滑过渡动画
- ✅ Session 会话管理

## 🔧 API 接口

### 认证接口 (`api/auth.php`)

- `POST /api/auth.php?action=login` - 用户登录
- `POST /api/auth.php?action=logout` - 用户登出
- `GET /api/auth.php?action=check` - 检查登录状态
- `POST /api/auth.php?action=change_password` - 修改密码

### 业务数据接口 (`api/api.php`)

**⚠️ 所有接口都需要登录才能访问**

- `GET /api/api.php?action=stats` - 获取所有业务统计数据
- `GET /api/api.php?action=summary` - 获取汇总数据
- `GET /api/api.php?action=channels` - 获取渠道列表
- `POST /api/api.php?action=add` - 添加新记录
- `GET /api/api.php?action=chart_data` - 获取图表数据
- `GET /api/api.php?action=init_test` - 初始化测试数据

## 🛡️ 安全说明

1. **生产环境建议**:
   - 关闭 PHP 错误显示 (`display_errors = Off`)
   - 修改默认管理员密码
   - 使用 HTTPS 协议
   - 限制 CORS 跨域访问

2. **数据库安全**:
   - 使用强密码
   - 限制数据库用户权限
   - 定期备份数据

3. **Session 安全**:
   - 使用安全的 Session 配置
   - 设置合理的 Session 过期时间

## 📝 开发说明

### 技术栈

- **后端**: PHP 7.4+, MySQL 5.7+
- **前端**: HTML5, CSS3, JavaScript (ES6+)
- **UI框架**: TailwindCSS
- **动画库**: GSAP 3.12.2
- **图表库**: ECharts 5.4.3
- **AI SDK**: Coze Web SDK 1.2.0-beta.19

### 代码规范

- PHP 使用 PSR-12 编码规范
- JavaScript 使用 ES6+ 语法
- 所有文件使用 UTF-8 编码
- 代码注释使用中文

## 🐛 常见问题

### 1. 数据库连接失败

- 检查 MySQL 服务是否启动
- 检查 `config/config.php` 中的数据库配置
- 检查数据库用户权限

### 2. 登录失败

- 确认数据库 `users` 表已创建
- 确认默认管理员账号已插入
- 检查 Session 是否正常启动

### 3. AI 智能体无法加载

- 检查 Coze SDK 是否正常加载
- 检查 Bot ID 和 Token 是否正确
- 检查网络连接（需要访问 Coze CDN）

## 📞 技术支持

如有问题，请联系开发团队。

---

**版本**: 1.0.0  
**更新日期**: 2024-01-21  
**维护**: 甘肃汇森信息科技有限公司
