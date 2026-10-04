-- Pharxmaco: easy permanent-deletion recovery with a 0/1 flag.
-- Select the application database in phpMyAdmin or SQLyog. Run ALL statements.
-- Requires the preceding retained-permanent-deletion upgrade (through 000008).
-- Back up first; pause inventory edits; import BEFORE uploading matching PHP files.
-- is_permanently_deleted = 1 hides the row; 0 allows ordinary Trash/Restore rules.
-- permanently_deleted_at remains history and no longer decides whether a row is hidden.
-- Existing permanent timestamps are copied into flags ONLY when a flag column is newly added.
-- Re-import never overwrites a flag that was manually reset to zero. No rows are deleted.

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
