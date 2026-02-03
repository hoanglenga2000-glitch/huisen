-- ============================================
-- 甘肃汇森信息科技有限公司 - 数据库升级脚本
-- 功能：添加图片路径、维修价格、标签等字段
-- 执行方式：在phpMyAdmin中导入或使用命令行执行
-- ============================================

USE `甘肃汇森`;

-- ============================================
-- 1. 为 mobile_quotes 表添加新字段
-- ============================================

-- 添加图片路径字段（用于存储产品主图）
ALTER TABLE `mobile_quotes`
ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL COMMENT '产品图片路径' AFTER `note`;

-- 添加维修参考价字段（JSON格式存储多个维修项目）
ALTER TABLE `mobile_quotes`
ADD COLUMN `repair_price` TEXT DEFAULT NULL COMMENT '维修参考价(JSON格式)' AFTER `image_path`;

-- 添加标签字段（如：热销、降价、新品）
ALTER TABLE `mobile_quotes`
ADD COLUMN `tags` VARCHAR(100) DEFAULT NULL COMMENT '产品标签，多个用逗号分隔' AFTER `repair_price`;

-- 添加库存状态字段
ALTER TABLE `mobile_quotes`
ADD COLUMN `stock_status` ENUM('充足', '紧张', '缺货') DEFAULT '充足' COMMENT '库存状态' AFTER `tags`;

-- 添加销量字段（用于热销排序）
ALTER TABLE `mobile_quotes`
ADD COLUMN `sales_count` INT(11) DEFAULT 0 COMMENT '销量统计' AFTER `stock_status`;

-- ============================================
-- 2. 为 mobile_phones 表添加图片字段
-- ============================================
ALTER TABLE `mobile_phones`
ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL COMMENT '产品图片路径' AFTER `note`;

-- ============================================
-- 3. 插入示例维修价格数据（JSON格式）
-- ============================================

-- 苹果 iPhone 15 Pro Max 维修价格示例
UPDATE `mobile_quotes`
SET `repair_price` = '{
    "screen": {"name": "更换屏幕", "price": 2800, "time": "1-2小时"},
    "battery": {"name": "更换电池", "price": 580, "time": "30分钟"},
    "camera": {"name": "更换后置摄像头", "price": 1200, "time": "1小时"},
    "charge_port": {"name": "更换充电接口", "price": 380, "time": "1小时"},
    "back_glass": {"name": "更换后盖玻璃", "price": 680, "time": "2小时"}
}'
WHERE `model` LIKE '%iPhone 15 Pro Max%';

-- 华为 Mate 60 Pro 维修价格示例
UPDATE `mobile_quotes`
SET `repair_price` = '{
    "screen": {"name": "更换屏幕", "price": 1800, "time": "1-2小时"},
    "battery": {"name": "更换电池", "price": 380, "time": "30分钟"},
    "camera": {"name": "更换后置摄像头", "price": 880, "time": "1小时"},
    "charge_port": {"name": "更换充电接口", "price": 280, "time": "1小时"},
    "back_glass": {"name": "更换后盖玻璃", "price": 480, "time": "2小时"}
}'
WHERE `model` LIKE '%Mate 60 Pro%';

-- 小米 14 Pro 维修价格示例
UPDATE `mobile_quotes`
SET `repair_price` = '{
    "screen": {"name": "更换屏幕", "price": 1200, "time": "1-2小时"},
    "battery": {"name": "更换电池", "price": 280, "time": "30分钟"},
    "camera": {"name": "更换后置摄像头", "price": 680, "time": "1小时"},
    "charge_port": {"name": "更换充电接口", "price": 180, "time": "1小时"},
    "back_glass": {"name": "更换后盖玻璃", "price": 380, "time": "2小时"}
}'
WHERE `model` LIKE '%14 Pro%' AND `brand` = '小米';

-- ============================================
-- 4. 添加热销标签示例
-- ============================================
UPDATE `mobile_quotes` SET `tags` = '热销,旗舰' WHERE `model` LIKE '%iPhone 15 Pro%';
UPDATE `mobile_quotes` SET `tags` = '热销,国产旗舰' WHERE `model` LIKE '%Mate 60%';
UPDATE `mobile_quotes` SET `tags` = '性价比,热销' WHERE `model` LIKE '%14 Pro%' AND `brand` = '小米';

-- ============================================
-- 5. 创建索引优化查询性能
-- ============================================
ALTER TABLE `mobile_quotes` ADD INDEX `idx_tags` (`tags`);
ALTER TABLE `mobile_quotes` ADD INDEX `idx_stock_status` (`stock_status`);
ALTER TABLE `mobile_quotes` ADD INDEX `idx_sales_count` (`sales_count`);

-- ============================================
-- 6. 验证查询
-- ============================================
-- SELECT brand, model, spec, price, image_path, tags, stock_status FROM mobile_quotes LIMIT 10;

-- 执行完成提示
SELECT '数据库升级完成！新增字段：image_path, repair_price, tags, stock_status, sales_count' AS 'Status';
