-- Pharxmaco: latest database upgrade for an EXISTING installation.
-- Matches app/Database/Migrations/2026_09_16_000005_add_logic_safeguards.php.
-- Requires the existing users, refund_items and migrations tables.
-- Earlier application migrations (through 2026_08_20_000004) must already be applied.
-- This is not a full database export or a fresh-install script.
--
-- Before importing: export a backup and pause sales/refunds/account changes.
-- Select your existing Hostinger database in phpMyAdmin, then import this file.
-- Stop on any SQL error. MySQL schema changes commit automatically.
-- There is deliberately no USE statement, database name, password or sample data.
-- Existing rows and existing column values are preserved. Re-import is supported.

-- Check that the required tables can be read before changing their structure.
SELECT `id` FROM `users` LIMIT 0;
SELECT `id` FROM `refund_items` LIMIT 0;
SELECT `version`, `class`, `group`, `namespace`, `time`, `batch` FROM `migrations` LIMIT 0;

-- 1. Session invalidation token. Existing users start with NULL, as in the migration.
SET @pharxmaco_upgrade_sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'session_version'
    ),
    'SELECT ''users.session_version already exists'' AS upgrade_status',
    'ALTER TABLE `users` ADD COLUMN `session_version` VARCHAR(32) NULL'
);
PREPARE pharxmaco_upgrade_statement FROM @pharxmaco_upgrade_sql;
EXECUTE pharxmaco_upgrade_statement;
DEALLOCATE PREPARE pharxmaco_upgrade_statement;

-- 2. Return condition. The resellable default matches prior restocking behavior.
SET @pharxmaco_upgrade_sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'refund_items'
          AND COLUMN_NAME = 'return_condition'
    ),
    'SELECT ''refund_items.return_condition already exists'' AS upgrade_status',
    'ALTER TABLE `refund_items` ADD COLUMN `return_condition` VARCHAR(20) NULL DEFAULT ''resellable'''
);
PREPARE pharxmaco_upgrade_statement FROM @pharxmaco_upgrade_sql;
EXECUTE pharxmaco_upgrade_statement;
DEALLOCATE PREPARE pharxmaco_upgrade_statement;

-- 3. Refund payout method. NULL means the historical method was not recorded.
SET @pharxmaco_upgrade_sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'refund_items'
          AND COLUMN_NAME = 'refund_method'
    ),
    'SELECT ''refund_items.refund_method already exists'' AS upgrade_status',
    'ALTER TABLE `refund_items` ADD COLUMN `refund_method` VARCHAR(10) NULL'
);
PREPARE pharxmaco_upgrade_statement FROM @pharxmaco_upgrade_sql;
EXECUTE pharxmaco_upgrade_statement;
DEALLOCATE PREPARE pharxmaco_upgrade_statement;

-- 4. Groups items from the same refund. Historical events are not invented.
SET @pharxmaco_upgrade_sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'refund_items'
          AND COLUMN_NAME = 'refund_event_id'
    ),
    'SELECT ''refund_items.refund_event_id already exists'' AS upgrade_status',
    'ALTER TABLE `refund_items` ADD COLUMN `refund_event_id` VARCHAR(32) NULL'
);
PREPARE pharxmaco_upgrade_statement FROM @pharxmaco_upgrade_sql;
EXECUTE pharxmaco_upgrade_statement;
DEALLOCATE PREPARE pharxmaco_upgrade_statement;

-- Record this migration only when all four required fields exist.
-- CHAR(92) preserves PHP namespace separators with either SQL backslash mode.
SET @pharxmaco_upgrade_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT '2026_09_16_000005',
       CONCAT('App', CHAR(92), 'Database', CHAR(92), 'Migrations', CHAR(92), 'AddLogicSafeguards'),
       'default', 'App', UNIX_TIMESTAMP(), @pharxmaco_upgrade_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `version` = '2026_09_16_000005' AND `namespace` = 'App' AND `group` = 'default'
)
AND (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND ((TABLE_NAME = 'users' AND COLUMN_NAME = 'session_version')
        OR (TABLE_NAME = 'refund_items' AND COLUMN_NAME IN ('return_condition', 'refund_method', 'refund_event_id')))
) = 4;

-- Verify using application tables directly. A top-level information_schema SELECT
-- can make phpMyAdmin target that schema when displaying subsequent query results.
-- Expected: one user column, three refund columns, then one migration record.
SHOW COLUMNS FROM `users` WHERE `Field` = 'session_version';
SHOW COLUMNS FROM `refund_items`
WHERE `Field` IN ('return_condition', 'refund_method', 'refund_event_id');

SELECT `version`, `class`, `group`, `namespace`, `batch`
FROM `migrations`
WHERE `version` = '2026_09_16_000005' AND `namespace` = 'App' AND `group` = 'default';

SET @pharxmaco_upgrade_sql = NULL;
SET @pharxmaco_upgrade_batch = NULL;
