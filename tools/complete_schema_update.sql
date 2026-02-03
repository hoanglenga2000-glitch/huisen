-- ============================================
-- 完整的 mobile_phones 表结构更新
-- ============================================
-- 用途：添加所有缺失的字段，确保能成功导入导出的数据
-- 运行环境: MySQL 5.7+

-- 检查并添加 detail_id 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'detail_id');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `detail_id` INT(11) UNSIGNED DEFAULT NULL COMMENT ''关联 phone_details.id'' AFTER `id`', 
    'SELECT ''字段 detail_id 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 image_url 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'image_url');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `image_url` VARCHAR(500) DEFAULT NULL COMMENT ''产品图片URL'' AFTER `note`', 
    'SELECT ''字段 image_url 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 product_link 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'product_link');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `product_link` VARCHAR(500) DEFAULT NULL COMMENT ''产品链接'' AFTER `image_url`', 
    'SELECT ''字段 product_link 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 official_price 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'official_price');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `official_price` DECIMAL(10, 2) DEFAULT NULL COMMENT ''官方价格'' AFTER `price`', 
    'SELECT ''字段 official_price 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 retail_price 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'retail_price');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `retail_price` DECIMAL(10, 2) DEFAULT NULL COMMENT ''官网零售价'' AFTER `price`', 
    'SELECT ''字段 retail_price 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 wholesale_price 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'wholesale_price');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `wholesale_price` DECIMAL(10, 2) DEFAULT NULL COMMENT ''批发价'' AFTER `price`', 
    'SELECT ''字段 wholesale_price 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 specs_json 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'specs_json');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `specs_json` TEXT DEFAULT NULL COMMENT ''规格JSON数据'' AFTER `spec`', 
    'SELECT ''字段 specs_json 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 intro_images 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'intro_images');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `intro_images` TEXT DEFAULT NULL COMMENT ''介绍图片JSON数组'' AFTER `image_url`', 
    'SELECT ''字段 intro_images 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 description 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'description');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `description` TEXT DEFAULT NULL COMMENT ''产品描述'' AFTER `note`', 
    'SELECT ''字段 description 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 detail_description 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'detail_description');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `detail_description` TEXT DEFAULT NULL COMMENT ''详细描述'' AFTER `description`', 
    'SELECT ''字段 detail_description 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 detail_highlights 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'detail_highlights');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `detail_highlights` TEXT DEFAULT NULL COMMENT ''产品亮点JSON数组'' AFTER `detail_description`', 
    'SELECT ''字段 detail_highlights 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 detail_specs_full 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'detail_specs_full');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `detail_specs_full` TEXT DEFAULT NULL COMMENT ''完整规格JSON'' AFTER `detail_highlights`', 
    'SELECT ''字段 detail_specs_full 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 detail_images 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'detail_images');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `detail_images` TEXT DEFAULT NULL COMMENT ''详情图片JSON数组'' AFTER `detail_specs_full`', 
    'SELECT ''字段 detail_images 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 source_url 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'source_url');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `source_url` VARCHAR(500) DEFAULT NULL COMMENT ''来源URL'' AFTER `detail_images`', 
    'SELECT ''字段 source_url 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 data_source 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'data_source');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `data_source` VARCHAR(50) DEFAULT NULL COMMENT ''数据来源'' AFTER `source_url`', 
    'SELECT ''字段 data_source 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 last_updated 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'last_updated');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `last_updated` TIMESTAMP NULL DEFAULT NULL COMMENT ''最后更新时间'' AFTER `updated_at`', 
    'SELECT ''字段 last_updated 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 cover_image 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'cover_image');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `cover_image` VARCHAR(500) DEFAULT NULL COMMENT ''封面图片URL'' AFTER `image_url`', 
    'SELECT ''字段 cover_image 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 normalized_model 字段
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'normalized_model');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `normalized_model` VARCHAR(150) DEFAULT NULL COMMENT ''标准化型号名称'' AFTER `model`', 
    'SELECT ''字段 normalized_model 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加索引
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND index_name = 'idx_detail_id');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD INDEX `idx_detail_id` (`detail_id`)', 
    'SELECT ''索引 idx_detail_id 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND index_name = 'idx_retail_price');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD INDEX `idx_retail_price` (`retail_price`)', 
    'SELECT ''索引 idx_retail_price 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 验证更新结果
SELECT '表结构更新完成！' as message;
SELECT COUNT(*) as total_columns FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'mobile_phones';
