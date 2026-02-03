-- ============================================
-- 手机报价表 Schema 更新
-- ============================================
-- 运行环境: MySQL 5.7+
-- 用途: 添加 retail_price 字段，更新索引

-- 添加 retail_price 字段到 mobile_phones 表
ALTER TABLE `mobile_phones` 
ADD COLUMN IF NOT EXISTS `retail_price` DECIMAL(10, 2) DEFAULT NULL COMMENT '官网零售价' AFTER `price`;

-- 如果 MySQL 5.7 不支持 IF NOT EXISTS，使用以下方式检查并添加
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
               WHERE table_schema = DATABASE() 
               AND table_name = 'mobile_phones' 
               AND column_name = 'retail_price');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `mobile_phones` ADD COLUMN `retail_price` DECIMAL(10, 2) DEFAULT NULL COMMENT ''官网零售价'' AFTER `price`', 
    'SELECT ''列 retail_price 已存在，跳过''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 从现有的 note 字段中提取官网价并更新到 retail_price 字段
UPDATE `mobile_phones` 
SET `retail_price` = CAST(REGEXP_SUBSTR(note, '(?<=官网价[:：]\\s*¥?)\\d+(\\.\\d+)?') AS DECIMAL(10,2))
WHERE note LIKE '%官网价%' AND retail_price IS NULL;

-- 如果上面的 REGEXP_SUBSTR 不支持，使用以下替代方案
-- (MySQL 5.7 兼容版本)
UPDATE `mobile_phones` 
SET `retail_price` = CAST(
    SUBSTRING(
        note, 
        LOCATE('¥', note) + 1, 
        LOCATE('.', note, LOCATE('¥', note)) + 2 - LOCATE('¥', note)
    ) AS DECIMAL(10,2)
)
WHERE note LIKE '%官网价%¥%' AND (retail_price IS NULL OR retail_price = 0);

-- 为 retail_price 字段添加索引
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
SELECT COUNT(*) as total, 
       COUNT(retail_price) as with_retail_price,
       COUNT(*) - COUNT(retail_price) as without_retail_price
FROM `mobile_phones`;
