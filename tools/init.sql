-- ============================================
-- 甘肃汇森信息科技有限公司 - 数据库初始化脚本
-- 运行环境: MySQL 5.7+
-- 字符集: utf8mb4
-- ============================================

-- 如果数据库不存在则创建（使用phpMyAdmin或命令行执行）
-- CREATE DATABASE IF NOT EXISTS `huisen` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 选择数据库
-- USE `huisen`;

-- ============================================
-- 业务统计数据表 (business_stats)
-- ============================================
DROP TABLE IF EXISTS `business_stats`;

CREATE TABLE `business_stats` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `channel_name` VARCHAR(100) NOT NULL COMMENT '渠道名称',
    `new_adds` INT(11) DEFAULT 0 COMMENT '新增',
    `broadband` INT(11) DEFAULT 0 COMMENT '宽带',
    `new_coins` INT(11) DEFAULT 0 COMMENT '新增金币',
    `stock_coins` INT(11) DEFAULT 0 COMMENT '存量金币',
    `low_commission` INT(11) DEFAULT 0 COMMENT '低提',
    `gigabit` INT(11) DEFAULT 0 COMMENT '千兆',
    `family_net` INT(11) DEFAULT 0 COMMENT '亲情网',
    `mobile_home` INT(11) DEFAULT 0 COMMENT '移动爱家',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '记录时间',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    INDEX `idx_channel_name` (`channel_name`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='业务统计数据表';

-- ============================================
-- 插入测试数据（基于截图中的数据）
-- ============================================
INSERT INTO `business_stats` 
(`channel_name`, `new_adds`, `broadband`, `new_coins`, `stock_coins`, `low_commission`, `gigabit`, `family_net`, `mobile_home`) 
VALUES 
('七里河恒巨', 85, 34, 44, 0, 405, 38, 9, 0),
('城关汇达旗舰店', 81, 27, 46, 0, 0, 0, 5, 0),
('西固冯立超', 90, 46, 39, 73, 0, 0, 5, 0),
('西固金恒生', 5, 0, 0, 3, 0, 0, 1, 0),
('城关恒巨', 85, 37, 22, 0, 0, 0, 5, 0),
('汇森同创', 0, 0, 0, 70, 1, 0, 0, 0),
('西峰区统办楼', 0, 0, 0, 57, 0, 0, 0, 0),
('安定区物美超市', 0, 0, 0, 115, 0, 0, 0, 0),
('成县于军旗', 0, 0, 0, 45, 0, 0, 0, 0);

-- ============================================
-- 用户信息表（已启用，用于登录鉴权）
-- ============================================
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(255) NOT NULL COMMENT '密码(加密)',
    `real_name` VARCHAR(50) DEFAULT NULL COMMENT '真实姓名',
    `role` ENUM('admin', 'manager', 'staff') DEFAULT 'staff' COMMENT '角色',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态：1正常 0禁用',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户信息表';

-- 插入默认管理员账号
-- 用户名: admin
-- 密码: admin (使用 PHP password_hash 生成的哈希值)
-- 角色: admin
-- 注意：此哈希值对应密码 "admin"，使用 password_hash('admin', PASSWORD_DEFAULT) 生成
INSERT INTO `users` (`username`, `password`, `real_name`, `role`) 
VALUES ('admin', '$2y$10$H1A5zEpjOsLZniGHQsOAQu7UsdG34IIbUgQr4tNgHfJwTEnSsSj8e', '系统管理员', 'admin');

-- ============================================
-- 手机批发报价表 (mobile_quotes)
-- ============================================
DROP TABLE IF EXISTS `mobile_quotes`;

CREATE TABLE `mobile_quotes` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `brand` VARCHAR(50) NOT NULL COMMENT '品牌（如：苹果, 华为, 小米）',
    `model` VARCHAR(100) NOT NULL COMMENT '型号（如：iPhone 15 Pro Max）',
    `spec` VARCHAR(50) NOT NULL COMMENT '规格（如：256G / 512G）',
    `color` VARCHAR(50) DEFAULT NULL COMMENT '颜色',
    `price` DECIMAL(10, 2) NOT NULL COMMENT '批发价',
    `retail_price` DECIMAL(10, 2) DEFAULT NULL COMMENT '零售价（可选）',
    `condition` VARCHAR(50) DEFAULT '全新未拆' COMMENT '状态（如：全新未拆, 充新, 靓机）',
    `note` VARCHAR(255) DEFAULT NULL COMMENT '备注（如：带票, 港版, 国行）',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    INDEX `idx_brand` (`brand`),
    INDEX `idx_model` (`model`),
    INDEX `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='手机批发报价表';

-- ============================================
-- 插入示例报价数据
-- ============================================
INSERT INTO `mobile_quotes` 
(`brand`, `model`, `spec`, `color`, `price`, `retail_price`, `condition`, `note`) 
VALUES 
('苹果', 'iPhone 15 Pro Max', '256G', '原色钛金属', 8500.00, 9999.00, '全新未拆', '国行 带票'),
('苹果', 'iPhone 15 Pro Max', '512G', '原色钛金属', 9800.00, 11999.00, '全新未拆', '国行 带票'),
('苹果', 'iPhone 15 Pro', '256G', '蓝色钛金属', 7500.00, 8999.00, '全新未拆', '国行 带票'),
('华为', 'Mate 60 Pro', '512G', '雅川青', 6800.00, 7999.00, '全新未拆', '国行 带票'),
('华为', 'Mate 60 Pro', '256G', '羽砂紫', 6200.00, 6999.00, '全新未拆', '国行 带票'),
('小米', '14 Pro', '512G', '黑色', 4200.00, 4999.00, '全新未拆', '国行 带票'),
('小米', '14 Pro', '256G', '白色', 3800.00, 4299.00, '全新未拆', '国行 带票');

-- ============================================
-- 手机智能选机表 (mobile_phones)
-- ============================================
DROP TABLE IF EXISTS `mobile_phones`;

CREATE TABLE `mobile_phones` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `brand` VARCHAR(50) NOT NULL COMMENT '品牌（如：苹果、华为、小米）',
    `model` VARCHAR(100) NOT NULL COMMENT '型号（如：iPhone 15 Pro Max）',
    `spec` VARCHAR(100) DEFAULT NULL COMMENT '规格（如：256G 原色钛金属）',
    `price` DECIMAL(10, 2) NOT NULL COMMENT '批发价',
    `note` VARCHAR(255) DEFAULT NULL COMMENT '备注（如：国行 带票）',
    `performance_score` INT(3) DEFAULT 50 COMMENT '性能评分 (0-100，用于匹配游戏需求)',
    `camera_score` INT(3) DEFAULT 50 COMMENT '拍照评分 (0-100，用于匹配拍照需求)',
    `battery_score` INT(3) DEFAULT 50 COMMENT '续航评分 (0-100，用于匹配续航需求)',
    `tags` VARCHAR(255) DEFAULT NULL COMMENT '标签（如：高性能,拍照好,长续航）',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    INDEX `idx_brand` (`brand`),
    INDEX `idx_model` (`model`),
    INDEX `idx_price` (`price`),
    INDEX `idx_performance_score` (`performance_score`),
    INDEX `idx_camera_score` (`camera_score`),
    INDEX `idx_battery_score` (`battery_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='手机智能选机表';

-- ============================================
-- 插入示例数据（用于测试 AI 匹配功能）
-- ============================================
INSERT INTO `mobile_phones` 
(`brand`, `model`, `spec`, `price`, `note`, `performance_score`, `camera_score`, `battery_score`, `tags`) 
VALUES 
('苹果', 'iPhone 15 Pro Max', '256G 原色钛金属', 8500.00, '国行 带票', 95, 90, 75, '高性能,拍照好,旗舰机'),
('苹果', 'iPhone 15 Pro Max', '512G 原色钛金属', 9800.00, '国行 带票', 95, 90, 75, '高性能,拍照好,旗舰机'),
('苹果', 'iPhone 15 Pro', '256G 蓝色钛金属', 7500.00, '国行 带票', 92, 88, 70, '高性能,拍照好,旗舰机'),
('华为', 'Mate 60 Pro', '512G 雅川青', 6800.00, '国行 带票', 85, 95, 80, '高性能,拍照好,长续航,旗舰机'),
('华为', 'Mate 60 Pro', '256G 羽砂紫', 6200.00, '国行 带票', 85, 95, 80, '高性能,拍照好,长续航,旗舰机'),
('华为', 'nova 12', '256G 白色', 3200.00, '国行 带票', 70, 85, 75, '拍照好,性价比'),
('小米', '14 Pro', '512G 黑色', 4200.00, '国行 带票', 88, 82, 78, '高性能,拍照好,长续航'),
('小米', '14 Pro', '256G 白色', 3800.00, '国行 带票', 88, 82, 78, '高性能,拍照好,长续航'),
('小米', 'Redmi K70', '256G 黑色', 2800.00, '国行 带票', 82, 75, 85, '高性能,长续航,性价比'),
('OPPO', 'Find X7', '512G 黑色', 4500.00, '国行 带票', 80, 90, 75, '拍照好,长续航'),
('vivo', 'X100 Pro', '512G 蓝色', 4800.00, '国行 带票', 85, 92, 78, '高性能,拍照好,长续航'),
('一加', '12', '256G 白色', 4000.00, '国行 带票', 90, 80, 75, '高性能,游戏手机'),
('realme', 'GT5 Pro', '256G 橙色', 3500.00, '国行 带票', 88, 78, 80, '高性能,长续航,游戏手机'),
('荣耀', 'Magic6', '256G 紫色', 4200.00, '国行 带票', 82, 88, 85, '拍照好,长续航'),
('红米', 'Note 13 Pro', '256G 蓝色', 1800.00, '国行 带票', 65, 70, 90, '长续航,性价比');

-- ============================================
-- 查询验证
-- ============================================
-- SELECT * FROM business_stats;
-- SELECT * FROM users;
-- SELECT * FROM mobile_quotes;
-- SELECT * FROM mobile_phones;