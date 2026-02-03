# 甘肃汇森信息科技有限公司 - 官方网站

## 📁 项目结构

```
huisen/
├── core/                    # 🔒 核心页面文件（受保护）
│   ├── quotes.php          # 报价页面
│   ├── detail.php          # 产品详情页
│   ├── login.php           # 登录页面
│   └── dashboard.php       # 后台管理
│
├── api/                     # 🌐 API接口
│   ├── quotes.php          # 报价API
│   ├── product_detail.php  # 产品详情API
│   ├── auth.php            # 认证API
│   ├── api.php             # 业务数据API
│   └── match.php           # 智能匹配API
│
├── config/                  # ⚙️ 配置文件
│   └── config.php          # 数据库配置
│
├── tools/                   # 🔧 工具和修复脚本
│   ├── sql/                # SQL脚本
│   └── ...                 # 数据导入、图片匹配等工具
│
├── docs/                    # 📚 文档
│
├── images/                  # 🖼️ 图片资源
├── product_details/         # 📦 产品详情JSON
│
├── index.php               # 🏠 首页
├── quotes.php              # 🔀 路由（→ core/quotes.php）
├── detail.php              # 🔀 路由（→ core/detail.php）
├── login.php               # 🔀 路由（→ core/login.php）
├── dashboard.php           # 🔀 路由（→ core/dashboard.php）
└── style.css               # 🎨 样式文件
```

## 🔐 安全性设计

### 核心文件保护
- 所有控制网站页面的PHP文件已移至 `core/` 目录
- 根目录只保留路由文件（简单的重定向）
- 实际业务逻辑代码不直接暴露在根目录

### 访问流程
```
用户访问: /quotes.php
    ↓
路由文件: quotes.php (根目录)
    ↓
核心文件: core/quotes.php (实际执行)
```

## 🚀 快速开始

1. **配置数据库**
   - 编辑 `config/config.php`
   - 设置数据库连接信息

2. **导入数据**
   - 访问 `tools/import_price_data.php` 导入报价数据

3. **访问网站**
   - 首页：`http://yoursite.com/index.php`
   - 报价页：`http://yoursite.com/quotes.php`
   - 后台：`http://yoursite.com/dashboard.php`

## 📝 文件说明

### 核心页面（core/）
- `quotes.php` - 手机报价展示页面
- `detail.php` - 产品详情页面
- `login.php` - 员工登录页面
- `dashboard.php` - 内部数据大屏

### API接口（api/）
- `quotes.php` - 报价数据CRUD接口
- `product_detail.php` - 产品详情接口
- `auth.php` - 用户认证接口
- `api.php` - 业务数据接口
- `match.php` - 智能匹配接口

### 工具脚本（tools/）
- `sql/` - 数据库SQL脚本
- `import_price_data.php` - 报价数据导入
- `run_image_match.php` - 图片匹配工具
- 其他数据维护工具

## ⚙️ 技术栈

- **后端**: PHP 7.4+
- **数据库**: MySQL 5.7+
- **前端**: HTML5, CSS3, JavaScript
- **UI框架**: TailwindCSS
- **动画**: GSAP

## 📖 更多文档

详细文档请查看 `docs/` 目录：
- `文件结构说明.md` - 详细文件结构说明
- `项目结构说明.md` - 项目组织说明
