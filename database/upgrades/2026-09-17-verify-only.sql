-- READ-ONLY verification for the latest Pharxmaco database upgrade.
-- In phpMyAdmin, first select your actual website database in the left sidebar.
-- Do not select information_schema, mysql, performance_schema or sys.
-- Run this file using Import, or paste its contents into the SQL tab.
-- It does not add, remove or change any records or columns.

-- 1. This must show your website database name.
SELECT DATABASE() AS selected_application_database;

-- 2. Expected: one row, session_version varchar(32), nullable.
SHOW COLUMNS FROM `users` WHERE `Field` = 'session_version';

-- 3. Expected: three rows, return_condition varchar(20) with default resellable,
--    refund_method varchar(10), refund_event_id varchar(32). All are nullable.
SHOW COLUMNS FROM `refund_items`
WHERE `Field` IN ('return_condition', 'refund_method', 'refund_event_id');

-- 4. Expected: exactly one row for this migration, namespace App, group default.
SELECT `version`, `class`, `group`, `namespace`, `batch`
FROM `migrations`
WHERE `version` = '2026_09_16_000005' AND `namespace` = 'App' AND `group` = 'default';
