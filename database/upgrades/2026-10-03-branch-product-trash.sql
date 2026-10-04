-- Pharxmaco: branch-specific product Trash for an EXISTING installation.
-- Select the application database in phpMyAdmin or SQLyog before running ALL statements.
-- Back up the database first. Import this before uploading the new PHP files.
-- Adds one nullable column; existing inventory and past shared-product deletions are preserved.
-- Re-import supported. Requires branch_products and the CodeIgniter migrations table.

SELECT `id` FROM `branch_products` LIMIT 0;
SELECT `version`, `class`, `group`, `namespace`, `time`, `batch` FROM `migrations` LIMIT 0;

SET @phx_branch_trash_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'branch_products' AND COLUMN_NAME = 'deleted_at'),
    'SELECT ''branch_products.deleted_at already exists'' AS upgrade_status',
    'ALTER TABLE `branch_products` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL'
);
PREPARE phx_branch_trash_statement FROM @phx_branch_trash_sql;
EXECUTE phx_branch_trash_statement;
DEALLOCATE PREPARE phx_branch_trash_statement;

SET @phx_branch_trash_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT '2026_10_03_000006',
       CONCAT('App', CHAR(92), 'Database', CHAR(92), 'Migrations', CHAR(92), 'AddBranchProductTrash'),
       'default', 'App', UNIX_TIMESTAMP(), @phx_branch_trash_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations`
    WHERE `version` = '2026_10_03_000006' AND `namespace` = 'App' AND `group` = 'default')
AND EXISTS (SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'branch_products' AND COLUMN_NAME = 'deleted_at');

SHOW COLUMNS FROM `branch_products` WHERE `Field` = 'deleted_at';
SELECT `version`, `class`, `group`, `namespace`, `batch` FROM `migrations`
WHERE `version` = '2026_10_03_000006' AND `namespace` = 'App' AND `group` = 'default';
SET @phx_branch_trash_sql = NULL, @phx_branch_trash_batch = NULL;
