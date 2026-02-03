-- ============================================
-- 甘肃汇森信息科技有限公司 - 产品详情表
-- 存储手机详细规格、图片、视频等信息
-- ============================================

-- 选择数据库
-- USE `huisen`;

-- ============================================
-- 产品详情表 (phone_details)
-- ============================================
CREATE TABLE IF NOT EXISTS `phone_details` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `phone_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '关联 mobile_phones.id',
    `brand` VARCHAR(50) NOT NULL COMMENT '品牌',
    `model` VARCHAR(150) NOT NULL COMMENT '型号',
    `full_name` VARCHAR(255) DEFAULT NULL COMMENT '完整名称',
    
    -- 详细规格
    `screen_size` VARCHAR(50) DEFAULT NULL COMMENT '屏幕尺寸',
    `screen_type` VARCHAR(100) DEFAULT NULL COMMENT '屏幕类型（如OLED, LCD）',
    `processor` VARCHAR(100) DEFAULT NULL COMMENT '处理器',
    `ram` VARCHAR(50) DEFAULT NULL COMMENT '运行内存',
    `storage_options` TEXT DEFAULT NULL COMMENT '存储选项 JSON: ["128GB", "256GB"]',
    `color_options` TEXT DEFAULT NULL COMMENT '颜色选项 JSON: [{"name":"黑色","hex":"#000000"}]',
    `battery` VARCHAR(50) DEFAULT NULL COMMENT '电池容量',
    `camera_specs` TEXT DEFAULT NULL COMMENT '相机规格 JSON',
    `os_version` VARCHAR(50) DEFAULT NULL COMMENT '操作系统版本',
    `weight` VARCHAR(50) DEFAULT NULL COMMENT '重量',
    `dimensions` VARCHAR(100) DEFAULT NULL COMMENT '尺寸',
    `network` VARCHAR(100) DEFAULT NULL COMMENT '网络制式（如5G/4G）',
    `features` TEXT DEFAULT NULL COMMENT '特色功能 JSON: ["防水", "无线充电"]',
    
    -- 媒体资源
    `main_image` VARCHAR(500) DEFAULT NULL COMMENT '主图片URL',
    `images` TEXT DEFAULT NULL COMMENT '图片列表 JSON',
    `video_url` VARCHAR(500) DEFAULT NULL COMMENT '视频URL',
    `video_thumbnail` VARCHAR(500) DEFAULT NULL COMMENT '视频缩略图',
    
    -- 价格信息
    `official_price` DECIMAL(10,2) DEFAULT NULL COMMENT '官网价',
    `min_price` DECIMAL(10,2) DEFAULT NULL COMMENT '最低配置价格',
    `max_price` DECIMAL(10,2) DEFAULT NULL COMMENT '最高配置价格',
    
    -- 评分
    `rating` DECIMAL(2,1) DEFAULT NULL COMMENT '评分（1-5）',
    `review_count` INT(11) DEFAULT 0 COMMENT '评价数量',
    
    -- 元数据
    `source` VARCHAR(50) DEFAULT '9ji.com' COMMENT '数据来源',
    `source_url` VARCHAR(500) DEFAULT NULL COMMENT '来源页面URL',
    `source_id` VARCHAR(100) DEFAULT NULL COMMENT '来源网站的产品ID',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_source_id` (`source`, `source_id`),
    INDEX `idx_brand` (`brand`),
    INDEX `idx_model` (`model`),
    INDEX `idx_phone_id` (`phone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='产品详情表';

-- ============================================
-- 产品配置价格表 (phone_variants)
-- 存储不同颜色/存储配置的价格
-- ============================================
CREATE TABLE IF NOT EXISTS `phone_variants` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `detail_id` INT(11) UNSIGNED NOT NULL COMMENT '关联 phone_details.id',
    `storage` VARCHAR(50) DEFAULT NULL COMMENT '存储容量（如256GB）',
    `color` VARCHAR(50) DEFAULT NULL COMMENT '颜色',
    `color_hex` VARCHAR(20) DEFAULT NULL COMMENT '颜色代码',
    `color_image` VARCHAR(500) DEFAULT NULL COMMENT '该颜色的产品图',
    `price` DECIMAL(10,2) NOT NULL COMMENT '批发价',
    `retail_price` DECIMAL(10,2) DEFAULT NULL COMMENT '零售价',
    `stock_status` VARCHAR(20) DEFAULT '有货' COMMENT '库存状态',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    
    PRIMARY KEY (`id`),
    INDEX `idx_detail_id` (`detail_id`),
    INDEX `idx_storage` (`storage`),
    INDEX `idx_color` (`color`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='产品配置价格表';

-- ============================================
-- 更新 mobile_phones 表，添加详情关联
-- ============================================
ALTER TABLE `mobile_phones` 
ADD COLUMN IF NOT EXISTS `detail_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '关联 phone_details.id' AFTER `id`,
ADD COLUMN IF NOT EXISTS `image_url` VARCHAR(500) DEFAULT NULL COMMENT '产品图片URL' AFTER `note`,
ADD INDEX IF NOT EXISTS `idx_detail_id` (`detail_id`);
