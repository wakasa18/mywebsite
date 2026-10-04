-- Pharxmaco: retained business records with explicit is_deleted flags.
-- EXISTING installation only. Select the application database in phpMyAdmin or SQLyog.
-- Back up first and pause sales/inventory edits. Run ALL statements; stop on SQL errors.
-- Import before uploading the matching PHP files. No database name or USE statement.
-- Adds flags to all five Trash-enabled tables, preserving rows, quantities and references.
-- Keeps deleted_at for the Trash date. Existing deleted_at values backfill is_deleted = 1.
-- Re-import is supported: it never resets an existing is_deleted = 1 to zero.
-- Includes the preceding branch_products.deleted_at upgrade if it is still missing.

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
