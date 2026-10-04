-- Pharxmaco: Delete permanently hides records from active pages AND Trash.
-- DATABASE ROWS, STOCK VALUES AND HISTORICAL REFERENCES ARE RETAINED.
-- Existing installation only. Select the application database in phpMyAdmin or SQLyog.
-- Back up first; pause sales/inventory edits. Run ALL statements and stop on errors.
-- Import BEFORE uploading this package's PHP files. Re-importing is supported.
-- Includes the preceding branch Trash and is_deleted columns; no separate old import needed.
-- Includes is_permanently_deleted (0/1); permanently_deleted_at is retained as history.
-- Existing trashed records stay restorable. No records are automatically permanently hidden.

SELECT `id` FROM `products` LIMIT 0;
SELECT `id` FROM `branch_products` LIMIT 0;
SELECT `id` FROM `categories` LIMIT 0;
SELECT `id` FROM `suppliers` LIMIT 0;
SELECT `id` FROM `discounts` LIMIT 0;
SELECT `version`, `class`, `group`, `namespace`, `time`, `batch` FROM `migrations` LIMIT 0;

SET @phx_retention_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'deleted_at'),
    'SELECT ''products.deleted_at already exists'' AS upgrade_status',
    'ALTER TABLE `products` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL'
);
PREPARE phx_retention_statement FROM @phx_retention_sql;
EXECUTE phx_retention_statement;
DEALLOCATE PREPARE phx_retention_statement;

SET @phx_retention_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'is_deleted'),
    'SELECT ''products.is_deleted already exists'' AS upgrade_status',
    'ALTER TABLE `products` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0'
);
PREPARE phx_retention_statement FROM @phx_retention_sql;
EXECUTE phx_retention_statement;
DEALLOCATE PREPARE phx_retention_statement;

UPDATE `products` SET `is_deleted` = 1 WHERE `deleted_at` IS NOT NULL AND `is_deleted` <> 1;

SET @phx_retention_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'branch_products' AND COLUMN_NAME = 'deleted_at'),
    'SELECT ''branch_products.deleted_at already exists'' AS upgrade_status',
    'ALTER TABLE `branch_products` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL'
);
PREPARE phx_retention_statement FROM @phx_retention_sql;
EXECUTE phx_retention_statement;
DEALLOCATE PREPARE phx_retention_statement;

SET @phx_retention_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'branch_products' AND COLUMN_NAME = 'is_deleted'),
    'SELECT ''branch_products.is_deleted already exists'' AS upgrade_status',
    'ALTER TABLE `branch_products` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0'
);
PREPARE phx_retention_statement FROM @phx_retention_sql;
EXECUTE phx_retention_statement;
DEALLOCATE PREPARE phx_retention_statement;

UPDATE `branch_products` SET `is_deleted` = 1 WHERE `deleted_at` IS NOT NULL AND `is_deleted` <> 1;

SET @phx_retention_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'deleted_at'),
    'SELECT ''categories.deleted_at already exists'' AS upgrade_status',
    'ALTER TABLE `categories` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL'
);
PREPARE phx_retention_statement FROM @phx_retention_sql;
EXECUTE phx_retention_statement;
DEALLOCATE PREPARE phx_retention_statement;

SET @phx_retention_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'is_deleted'),
    'SELECT ''categories.is_deleted already exists'' AS upgrade_status',
    'ALTER TABLE `categories` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0'
);
PREPARE phx_retention_statement FROM @phx_retention_sql;
EXECUTE phx_retention_statement;
DEALLOCATE PREPARE phx_retention_statement;

UPDATE `categories` SET `is_deleted` = 1 WHERE `deleted_at` IS NOT NULL AND `is_deleted` <> 1;

SET @phx_retention_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'deleted_at'),
    'SELECT ''suppliers.deleted_at already exists'' AS upgrade_status',
    'ALTER TABLE `suppliers` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL'
);
PREPARE phx_retention_statement FROM @phx_retention_sql;
EXECUTE phx_retention_statement;
DEALLOCATE PREPARE phx_retention_statement;

SET @phx_retention_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'is_deleted'),
    'SELECT ''suppliers.is_deleted already exists'' AS upgrade_status',
    'ALTER TABLE `suppliers` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0'
);
PREPARE phx_retention_statement FROM @phx_retention_sql;
EXECUTE phx_retention_statement;
DEALLOCATE PREPARE phx_retention_statement;

UPDATE `suppliers` SET `is_deleted` = 1 WHERE `deleted_at` IS NOT NULL AND `is_deleted` <> 1;

SET @phx_retention_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'discounts' AND COLUMN_NAME = 'deleted_at'),
    'SELECT ''discounts.deleted_at already exists'' AS upgrade_status',
    'ALTER TABLE `discounts` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL'
);
PREPARE phx_retention_statement FROM @phx_retention_sql;
EXECUTE phx_retention_statement;
DEALLOCATE PREPARE phx_retention_statement;

SET @phx_retention_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'discounts' AND COLUMN_NAME = 'is_deleted'),
    'SELECT ''discounts.is_deleted already exists'' AS upgrade_status',
    'ALTER TABLE `discounts` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0'
);
PREPARE phx_retention_statement FROM @phx_retention_sql;
EXECUTE phx_retention_statement;
DEALLOCATE PREPARE phx_retention_statement;

UPDATE `discounts` SET `is_deleted` = 1 WHERE `deleted_at` IS NOT NULL AND `is_deleted` <> 1;

-- Record the preceding branch migration only after its column exists.
SET @phx_retention_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT '2026_10_03_000006',
       CONCAT('App', CHAR(92), 'Database', CHAR(92), 'Migrations', CHAR(92), 'AddBranchProductTrash'),
       'default', 'App', UNIX_TIMESTAMP(), @phx_retention_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `version` = '2026_10_03_000006' AND `namespace` = 'App' AND `group` = 'default')
AND EXISTS (SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'branch_products' AND COLUMN_NAME = 'deleted_at');

-- Record this migration only when all ten required fields exist.
INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT '2026_10_03_000007',
       CONCAT('App', CHAR(92), 'Database', CHAR(92), 'Migrations', CHAR(92), 'AddSoftDeleteFlags'),
       'default', 'App', UNIX_TIMESTAMP(), @phx_retention_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `version` = '2026_10_03_000007' AND `namespace` = 'App' AND `group` = 'default')
AND (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('products','branch_products','categories','suppliers','discounts')
      AND COLUMN_NAME IN ('is_deleted','deleted_at')) = 10;

-- Display results from application tables, without switching phpMyAdmin to information_schema.
SHOW COLUMNS FROM `products` WHERE `Field` IN ('is_deleted','deleted_at');
SHOW COLUMNS FROM `branch_products` WHERE `Field` IN ('is_deleted','deleted_at');
SHOW COLUMNS FROM `categories` WHERE `Field` IN ('is_deleted','deleted_at');
SHOW COLUMNS FROM `suppliers` WHERE `Field` IN ('is_deleted','deleted_at');
SHOW COLUMNS FROM `discounts` WHERE `Field` IN ('is_deleted','deleted_at');
SELECT `version`, `class`, `group`, `namespace`, `batch` FROM `migrations`
WHERE `version` IN ('2026_10_03_000006','2026_10_03_000007') AND `namespace` = 'App' AND `group` = 'default';
SET @phx_retention_sql = NULL, @phx_retention_batch = NULL;

SET @phx_permanent_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'permanently_deleted_at'),
    'SELECT ''products.permanently_deleted_at already exists'' AS upgrade_status',
    'ALTER TABLE `products` ADD COLUMN `permanently_deleted_at` DATETIME NULL DEFAULT NULL'
);
PREPARE phx_permanent_statement FROM @phx_permanent_sql;
EXECUTE phx_permanent_statement;
DEALLOCATE PREPARE phx_permanent_statement;

SET @phx_permanent_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'branch_products' AND COLUMN_NAME = 'permanently_deleted_at'),
    'SELECT ''branch_products.permanently_deleted_at already exists'' AS upgrade_status',
    'ALTER TABLE `branch_products` ADD COLUMN `permanently_deleted_at` DATETIME NULL DEFAULT NULL'
);
PREPARE phx_permanent_statement FROM @phx_permanent_sql;
EXECUTE phx_permanent_statement;
DEALLOCATE PREPARE phx_permanent_statement;

SET @phx_permanent_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'permanently_deleted_at'),
    'SELECT ''categories.permanently_deleted_at already exists'' AS upgrade_status',
    'ALTER TABLE `categories` ADD COLUMN `permanently_deleted_at` DATETIME NULL DEFAULT NULL'
);
PREPARE phx_permanent_statement FROM @phx_permanent_sql;
EXECUTE phx_permanent_statement;
DEALLOCATE PREPARE phx_permanent_statement;

SET @phx_permanent_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'permanently_deleted_at'),
    'SELECT ''suppliers.permanently_deleted_at already exists'' AS upgrade_status',
    'ALTER TABLE `suppliers` ADD COLUMN `permanently_deleted_at` DATETIME NULL DEFAULT NULL'
);
PREPARE phx_permanent_statement FROM @phx_permanent_sql;
EXECUTE phx_permanent_statement;
DEALLOCATE PREPARE phx_permanent_statement;

SET @phx_permanent_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'discounts' AND COLUMN_NAME = 'permanently_deleted_at'),
    'SELECT ''discounts.permanently_deleted_at already exists'' AS upgrade_status',
    'ALTER TABLE `discounts` ADD COLUMN `permanently_deleted_at` DATETIME NULL DEFAULT NULL'
);
PREPARE phx_permanent_statement FROM @phx_permanent_sql;
EXECUTE phx_permanent_statement;
DEALLOCATE PREPARE phx_permanent_statement;

-- Record this migration only after all five markers and their supporting fields exist.
SET @phx_permanent_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT '2026_10_03_000008',
       CONCAT('App', CHAR(92), 'Database', CHAR(92), 'Migrations', CHAR(92), 'AddPermanentDeleteMarkers'),
       'default', 'App', UNIX_TIMESTAMP(), @phx_permanent_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `version` = '2026_10_03_000008' AND `namespace` = 'App' AND `group` = 'default')
AND (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('products','branch_products','categories','suppliers','discounts')
      AND COLUMN_NAME IN ('is_deleted','deleted_at','permanently_deleted_at')) = 15;

SHOW COLUMNS FROM `products` WHERE `Field` IN ('is_deleted','deleted_at','permanently_deleted_at');
SHOW COLUMNS FROM `branch_products` WHERE `Field` IN ('is_deleted','deleted_at','permanently_deleted_at');
SHOW COLUMNS FROM `categories` WHERE `Field` IN ('is_deleted','deleted_at','permanently_deleted_at');
SHOW COLUMNS FROM `suppliers` WHERE `Field` IN ('is_deleted','deleted_at','permanently_deleted_at');
SHOW COLUMNS FROM `discounts` WHERE `Field` IN ('is_deleted','deleted_at','permanently_deleted_at');
SELECT `version`, `class`, `group`, `namespace`, `batch` FROM `migrations`
WHERE `version` IN ('2026_10_03_000006','2026_10_03_000007','2026_10_03_000008')
  AND `namespace` = 'App' AND `group` = 'default';
SET @phx_permanent_sql = NULL, @phx_permanent_batch = NULL;

-- Permanent deletion flags (2026_10_03_000009)
SELECT `id`, `is_deleted`, `deleted_at`, `permanently_deleted_at` FROM `products` LIMIT 0;
SELECT `id`, `is_deleted`, `deleted_at`, `permanently_deleted_at` FROM `branch_products` LIMIT 0;
SELECT `id`, `is_deleted`, `deleted_at`, `permanently_deleted_at` FROM `categories` LIMIT 0;
SELECT `id`, `is_deleted`, `deleted_at`, `permanently_deleted_at` FROM `suppliers` LIMIT 0;
SELECT `id`, `is_deleted`, `deleted_at`, `permanently_deleted_at` FROM `discounts` LIMIT 0;
SELECT `version`, `class`, `group`, `namespace`, `time`, `batch` FROM `migrations` LIMIT 0;

SET @phx_permanent_flag_new = NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'is_permanently_deleted'
);
SET @phx_permanent_flag_sql = IF(@phx_permanent_flag_new,
    'ALTER TABLE `products` ADD COLUMN `is_permanently_deleted` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''products.is_permanently_deleted already exists'' AS upgrade_status'
);
PREPARE phx_permanent_flag_statement FROM @phx_permanent_flag_sql;
EXECUTE phx_permanent_flag_statement;
DEALLOCATE PREPARE phx_permanent_flag_statement;
UPDATE `products` SET `is_permanently_deleted` = 1
WHERE @phx_permanent_flag_new = 1 AND `permanently_deleted_at` IS NOT NULL;

SET @phx_permanent_flag_new = NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'branch_products' AND COLUMN_NAME = 'is_permanently_deleted'
);
SET @phx_permanent_flag_sql = IF(@phx_permanent_flag_new,
    'ALTER TABLE `branch_products` ADD COLUMN `is_permanently_deleted` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''branch_products.is_permanently_deleted already exists'' AS upgrade_status'
);
PREPARE phx_permanent_flag_statement FROM @phx_permanent_flag_sql;
EXECUTE phx_permanent_flag_statement;
DEALLOCATE PREPARE phx_permanent_flag_statement;
UPDATE `branch_products` SET `is_permanently_deleted` = 1
WHERE @phx_permanent_flag_new = 1 AND `permanently_deleted_at` IS NOT NULL;

SET @phx_permanent_flag_new = NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'is_permanently_deleted'
);
SET @phx_permanent_flag_sql = IF(@phx_permanent_flag_new,
    'ALTER TABLE `categories` ADD COLUMN `is_permanently_deleted` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''categories.is_permanently_deleted already exists'' AS upgrade_status'
);
PREPARE phx_permanent_flag_statement FROM @phx_permanent_flag_sql;
EXECUTE phx_permanent_flag_statement;
DEALLOCATE PREPARE phx_permanent_flag_statement;
UPDATE `categories` SET `is_permanently_deleted` = 1
WHERE @phx_permanent_flag_new = 1 AND `permanently_deleted_at` IS NOT NULL;

SET @phx_permanent_flag_new = NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'is_permanently_deleted'
);
SET @phx_permanent_flag_sql = IF(@phx_permanent_flag_new,
    'ALTER TABLE `suppliers` ADD COLUMN `is_permanently_deleted` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''suppliers.is_permanently_deleted already exists'' AS upgrade_status'
);
PREPARE phx_permanent_flag_statement FROM @phx_permanent_flag_sql;
EXECUTE phx_permanent_flag_statement;
DEALLOCATE PREPARE phx_permanent_flag_statement;
UPDATE `suppliers` SET `is_permanently_deleted` = 1
WHERE @phx_permanent_flag_new = 1 AND `permanently_deleted_at` IS NOT NULL;

SET @phx_permanent_flag_new = NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'discounts' AND COLUMN_NAME = 'is_permanently_deleted'
);
SET @phx_permanent_flag_sql = IF(@phx_permanent_flag_new,
    'ALTER TABLE `discounts` ADD COLUMN `is_permanently_deleted` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''discounts.is_permanently_deleted already exists'' AS upgrade_status'
);
PREPARE phx_permanent_flag_statement FROM @phx_permanent_flag_sql;
EXECUTE phx_permanent_flag_statement;
DEALLOCATE PREPARE phx_permanent_flag_statement;
UPDATE `discounts` SET `is_permanently_deleted` = 1
WHERE @phx_permanent_flag_new = 1 AND `permanently_deleted_at` IS NOT NULL;

SET @phx_permanent_flag_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT '2026_10_03_000009',
       CONCAT('App', CHAR(92), 'Database', CHAR(92), 'Migrations', CHAR(92), 'AddPermanentDeleteFlags'),
       'default', 'App', UNIX_TIMESTAMP(), @phx_permanent_flag_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `version` = '2026_10_03_000009' AND `namespace` = 'App' AND `group` = 'default')
AND (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('products','branch_products','categories','suppliers','discounts')
      AND COLUMN_NAME IN ('is_deleted','deleted_at','permanently_deleted_at','is_permanently_deleted')) = 20;

SHOW COLUMNS FROM `products` WHERE `Field` = 'is_permanently_deleted';
SHOW COLUMNS FROM `branch_products` WHERE `Field` = 'is_permanently_deleted';
SHOW COLUMNS FROM `categories` WHERE `Field` = 'is_permanently_deleted';
SHOW COLUMNS FROM `suppliers` WHERE `Field` = 'is_permanently_deleted';
SHOW COLUMNS FROM `discounts` WHERE `Field` = 'is_permanently_deleted';
SELECT `version`, `class`, `group`, `namespace`, `batch` FROM `migrations`
WHERE `version` = '2026_10_03_000009' AND `namespace` = 'App' AND `group` = 'default';
SET @phx_permanent_flag_new = NULL, @phx_permanent_flag_sql = NULL, @phx_permanent_flag_batch = NULL;
