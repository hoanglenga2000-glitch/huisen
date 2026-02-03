-- ============================================
-- 修复报价页面数据库字段问题
-- 用途：为 mobile_phones 表添加缺失的字段
-- 使用方法：在 phpMyAdmin 中执行此SQL文件
-- ============================================

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

-- 检查并添加 image_path 字段（可选，用于兼容）
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'image_path');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `image_path` VARCHAR(500) DEFAULT NULL COMMENT ''产品图片路径'' AFTER `image_url`', 
    'SELECT ''字段 image_path 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 tags 字段（如果不存在）
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'tags');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `tags` VARCHAR(255) DEFAULT NULL COMMENT ''标签'' AFTER `note`', 
    'SELECT ''字段 tags 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 显示完成信息
SELECT '数据库字段修复完成！' as message;
