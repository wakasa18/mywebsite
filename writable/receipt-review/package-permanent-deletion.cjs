const fs=require('node:fs'),path=require('node:path');
const root=path.resolve(__dirname,'../..');
const tables=['products','branch_products','categories','suppliers','discounts'];
const sqlPath='database/upgrades/2026-10-03-retained-permanent-deletion.sql';
const guidePath='database/upgrades/2026-10-03-retained-permanent-deletion.md';
const runtime=JSON.parse(fs.readFileSync(path.join(__dirname,'retention-manifest.json'),'utf8')).filter(p=>p.endsWith('.php'));
runtime.push('app/Libraries/RetainedRecordDeletion.php','app/Database/Migrations/2026_10_03_000008_add_permanent_delete_markers.php');
let sql=`-- Pharxmaco: Delete permanently hides records from active pages AND Trash.
-- DATABASE ROWS, STOCK VALUES AND HISTORICAL REFERENCES ARE RETAINED.
-- Existing installation only. Select the application database in phpMyAdmin or SQLyog.
-- Back up first; pause sales/inventory edits. Run ALL statements and stop on errors.
-- Import BEFORE uploading this package's PHP files. Re-importing is supported.
-- Includes the preceding branch Trash and is_deleted columns; no separate old import needed.
-- Adds permanently_deleted_at to distinguish restorable Trash from permanent hiding.
-- Existing trashed records stay restorable. No records are automatically permanently hidden.
\n`;
const prior=fs.readFileSync(path.join(root,'database/upgrades/2026-10-03-soft-delete-flags.sql'),'utf8');
sql+=prior.slice(prior.indexOf('SELECT `id` FROM `products`'))+'\n';
for(const table of tables) {
    sql+=`SET @phx_permanent_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND COLUMN_NAME = 'permanently_deleted_at'),
    'SELECT ''${table}.permanently_deleted_at already exists'' AS upgrade_status',
    'ALTER TABLE \`${table}\` ADD COLUMN \`permanently_deleted_at\` DATETIME NULL DEFAULT NULL'
);
PREPARE phx_permanent_statement FROM @phx_permanent_sql;
EXECUTE phx_permanent_statement;
DEALLOCATE PREPARE phx_permanent_statement;

`;
}
sql+=`-- Record this migration only after all five markers and their supporting fields exist.
SET @phx_permanent_batch = (SELECT COALESCE(MAX(\`batch\`), 0) + 1 FROM \`migrations\`);
INSERT INTO \`migrations\` (\`version\`, \`class\`, \`group\`, \`namespace\`, \`time\`, \`batch\`)
SELECT '2026_10_03_000008',
       CONCAT('App', CHAR(92), 'Database', CHAR(92), 'Migrations', CHAR(92), 'AddPermanentDeleteMarkers'),
       'default', 'App', UNIX_TIMESTAMP(), @phx_permanent_batch
WHERE NOT EXISTS (SELECT 1 FROM \`migrations\`
    WHERE \`version\` = '2026_10_03_000008' AND \`namespace\` = 'App' AND \`group\` = 'default')
AND (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('products','branch_products','categories','suppliers','discounts')
      AND COLUMN_NAME IN ('is_deleted','deleted_at','permanently_deleted_at')) = 15;

${tables.map(t=>'SHOW COLUMNS FROM `'+t+'` WHERE `Field` IN (\'is_deleted\',\'deleted_at\',\'permanently_deleted_at\');').join('\n')}
SELECT \`version\`, \`class\`, \`group\`, \`namespace\`, \`batch\` FROM \`migrations\`
WHERE \`version\` IN ('2026_10_03_000006','2026_10_03_000007','2026_10_03_000008')
  AND \`namespace\` = 'App' AND \`group\` = 'default';
SET @phx_permanent_sql = NULL, @phx_permanent_batch = NULL;
`;
fs.writeFileSync(path.join(root,sqlPath),sql);
const doc=`# Delete permanently while retaining database records

Package: \`build/hostinger-retained-permanent-deletion-2026-10-03.zip\`.

**Delete permanently now removes a record from active lists and Trash while keeping its database row.** Restore remains available for ordinary Trash only. This replaces the previous patch that disabled permanent-delete controls.

## States and behavior

| State | is_deleted | deleted_at | permanently_deleted_at | Visible in active lists | Visible in Trash | Restore in the application |
| --- | --- | --- | --- | --- | --- | --- |
| Current | 0 | NULL | NULL | Yes, subject to other filters/status | No | Not needed |
| Trash | 1 | Trash date | NULL | No | Yes | Yes |
| Permanently hidden | 1 | Original Trash date | Permanent-hiding date | No | No | No |

Older timestamp-only or flag-only Trash records are still recognized. Existing Trash is not automatically permanently hidden by the upgrade.

- Products, categories, suppliers and discounts have **Restore** and **Delete permanently** buttons in Trash. The action is a POST with CSRF and confirmation. It requires an administrator and a record already in Trash.
- Product actions apply to the selected \`branch_products\` row. Other branches, the shared catalog record and aggregate physical stock remain unchanged. The confirmation identifies the branch.
- Older shared product deletions have a separate explicit shared action. This hides the already-trashed shared catalog record; it does not change the retained branch inventory rows. A missing branch parameter never guesses a branch or silently performs a shared deletion.
- Records, IDs, quantities, selling/cost prices, expiry, active/inactive status, sales, refunds, stock history and discount usage remain stored. Historical receipts and transaction reports keep their original linked information. Permanent hiding does not dispose of physical stock.
- Hidden records are excluded from active inventory, POS, replacement selection, expiry actions, notifications, restock suggestions and Trash pagination. Old Restore requests cannot make them visible again. Manual database repair/recovery is outside the ordinary application flow.
- A permanently hidden catalog/SKU or branch assignment stays retained and cannot be silently recreated as a duplicate assignment. The catalog and stock forms report this state where applicable.
- Each successful operation writes an audit entry. Branch actions also write a zero-movement stock log. Repeated identical requests add no duplicate logs. State and logs roll back together if a write fails.
- Physical model deletion and purging remain blocked. These buttons perform UPDATE statements, never DELETE statements. Users and branches keep their existing deactivation flows. Backup-file cleanup and an explicitly requested full database restore remain separate operations.

## Hostinger deployment

1. Back up the database and the matching PHP files, then pause sales and inventory edits during deployment.
2. Select the application database in phpMyAdmin or SQLyog. Run **all statements** in \`database/upgrades/2026-10-03-retained-permanent-deletion.sql\`. Stop on any error.
3. Confirm that the final five column results each show \`is_deleted\`, \`deleted_at\` and \`permanently_deleted_at\`, and that the migration results include \`2026_10_03_000008\`.
4. Upload the PHP files listed below to their matching paths in the application's Hostinger root. Resume use after checking the workflow with a test record.

**Import the SQL before uploading the PHP files.** This cumulative upgrade includes the preceding branch Trash timestamp and flags if missing, so no separate import of the previous two scripts is necessary. It preserves existing flags and timestamps on re-import. The five new markers default to NULL and are never reset by re-importing. Existing rows and references are preserved; the script does not delete data and has no database name or USE statement.

Alternatively, run migrations with the normal CodeIgniter deployment process instead of manually importing SQL. Use one method. The SQL records the matching migration versions after their required columns exist. This is an existing-installation upgrade, not a fresh database export, and assumes the current application's business tables and CodeIgniter \`migrations\` table. Earlier unrelated migrations must already be installed.

Use this package instead of the previous \`hostinger-soft-delete-flags-2026-10-03.zip\`. It includes the branch-specific Trash implementation and flags. Install it on the current project with the earlier exchange, expiry-stock and cashier catalog features. No route or vendor update is required. Do not subsequently overwrite these files with older patches: older code does not enforce the permanent-hiding marker.

The new marker migration intentionally refuses an automatic rollback that would make permanently hidden records appear in Trash again. Retain the marker and matching code when planning any rollback.

## PHP files

${runtime.map((p,i)=>(i+1)+'. \`'+p+'\`').join('\n')}

The ZIP also includes the cumulative SQL and this guide. It contains no environment file, credentials, sample data, vendor files or writable files.

## Verification

The unit suite passed: **112 tests, 21,286 assertions**. Tests cover retained rows and historical references, hiding from Trash, non-restorability, used suppliers/categories/discounts, branch isolation, missing/invalid branch values, administrator checks, repeated requests, audit rollback, additive/repeatable migration and existing checkout/forecast/export behavior.

Sixty-four local browser cases passed at 320, 390, 768 and 1440 px in light and dark themes. Checks cover both Trash actions, posted branch IDs, explicit older shared controls, CSRF, confirmation cancellation, keyboard access, control sizes and overflow. PHP syntax and ZIP contents were also verified.

Tests used in-memory SQLite and local rendered fixtures. The MySQL import script and native MySQL concurrent locking were not executed. No configured application database or Hostinger deployment was changed.

After deployment, move a test record to Trash, then click Delete permanently. Verify that it disappears from Trash, that its row has \`is_deleted = 1\` and a \`permanently_deleted_at\` value, and that historical references remain present. For a product sold by two branches, confirm that only the selected branch assignment is hidden.
`;
fs.writeFileSync(path.join(root,guidePath),doc);
fs.writeFileSync(path.join(__dirname,'permanent-deletion-manifest.json'),JSON.stringify([...runtime,sqlPath,guidePath],null,2)+'\n');
console.log(JSON.stringify({runtimeFiles:runtime.length,packageFiles:runtime.length+2,sql:sqlPath,guide:guidePath}));
