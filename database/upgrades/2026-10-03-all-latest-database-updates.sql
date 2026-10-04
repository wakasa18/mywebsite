-- Pharxmaco: all missing database updates for the supplied October 3, 2026 export.
-- Existing database only: select the application database in phpMyAdmin or SQLyog.
-- Baseline already includes checkout recovery, supplier email, session and refund safeguards.
-- Adds all four remaining migrations: 2026_10_03_000006 through 2026_10_03_000009.
-- Adds 16 missing columns, preserves existing rows, stock, prices and historical references.
-- is_permanently_deleted: 1 = permanently hidden; 0 = normal active/Trash rules.
-- permanently_deleted_at stays as history. Existing Trash stays restorable.
-- Back up first; pause inventory/sales edits; run ALL statements and stop on any error.
-- Import before uploading the corresponding current PHP files. Re-import is supported.
-- This file does not recreate tables or insert sales, users, products or sample data.
-- MySQL schema changes commit automatically; this is not an atomic transaction.
-- No USE statement: it works with the database selected in your SQL client.

SELECT `checkout_token_hash` FROM `sales` LIMIT 0;
SELECT `email` FROM `suppliers` LIMIT 0;
SELECT `session_version` FROM `users` LIMIT 0;
SELECT `return_condition`, `refund_method`, `refund_event_id` FROM `refund_items` LIMIT 0;
SELECT `version`, `class`, `group`, `namespace`, `time`, `batch` FROM `migrations` LIMIT 0;

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
