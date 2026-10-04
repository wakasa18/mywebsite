const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');
const tables = ['products','branch_products','categories','suppliers','discounts'];
const runtime = [
    'app/Models/RetainedRecordModel.php',
    ...['ProductModel','BranchProductModel','CategoryModel','SupplierModel','DiscountModel'].map(n=>'app/Models/'+n+'.php'),
    'app/Database/Migrations/2026_10_03_000006_add_branch_product_trash.php',
    'app/Database/Migrations/2026_10_03_000007_add_soft_delete_flags.php',
    'app/Controllers/Products.php','app/Controllers/Categories.php','app/Controllers/Dashboard.php',
    'app/Controllers/Admin/Suppliers.php','app/Controllers/Admin/Discounts.php',
    'app/Controllers/Admin/ReportsController.php','app/Controllers/Admin/SaleCorrection.php',
    'app/Controllers/Admin/HistoricalSalesImport.php',
    'app/Controllers/Cashier/SalesController.php','app/Controllers/Cashier/ExchangeController.php',
    'app/Libraries/BranchProductTrash.php','app/Libraries/ProductExchange.php',
    'app/Libraries/ExpiryStockResolution.php','app/Libraries/ReorderForecastService.php',
    'app/Libraries/TopbarNotificationService.php',
    'app/Views/products/index.php','app/Views/products/trash.php',
    'app/Views/categories/trash.php','app/Views/admin/suppliers/trash.php','app/Views/admin/discounts/trash.php',
    'app/Views/admin/reports/expiry_action.php','app/Views/cashier/sales/product_rows.php',
];
const sqlPath='database/upgrades/2026-10-03-soft-delete-flags.sql';
const docPath='database/upgrades/2026-10-03-soft-delete-flags.md';
let sql=`-- Pharxmaco: retained business records with explicit is_deleted flags.
-- EXISTING installation only. Select the application database in phpMyAdmin or SQLyog.
-- Back up first and pause sales/inventory edits. Run ALL statements; stop on SQL errors.
-- Import before uploading the matching PHP files. No database name or USE statement.
-- Adds flags to all five Trash-enabled tables, preserving rows, quantities and references.
-- Keeps deleted_at for the Trash date. Existing deleted_at values backfill is_deleted = 1.
-- Re-import is supported: it never resets an existing is_deleted = 1 to zero.
-- Includes the preceding branch_products.deleted_at upgrade if it is still missing.

${tables.map(t=>'SELECT `id` FROM `'+t+'` LIMIT 0;').join('\n')}
SELECT \`version\`, \`class\`, \`group\`, \`namespace\`, \`time\`, \`batch\` FROM \`migrations\` LIMIT 0;

`;
for(const table of tables) {
    for(const [field,definition] of [['deleted_at','DATETIME NULL DEFAULT NULL'],['is_deleted','TINYINT(1) NOT NULL DEFAULT 0']]) {
        sql+=`SET @phx_retention_sql = IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND COLUMN_NAME = '${field}'),
    'SELECT ''${table}.${field} already exists'' AS upgrade_status',
    'ALTER TABLE \`${table}\` ADD COLUMN \`${field}\` ${definition}'
);
PREPARE phx_retention_statement FROM @phx_retention_sql;
EXECUTE phx_retention_statement;
DEALLOCATE PREPARE phx_retention_statement;

`;
    }
    sql+=`UPDATE \`${table}\` SET \`is_deleted\` = 1 WHERE \`deleted_at\` IS NOT NULL AND \`is_deleted\` <> 1;

`;
}
sql+=`-- Record the preceding branch migration only after its column exists.
SET @phx_retention_batch = (SELECT COALESCE(MAX(\`batch\`), 0) + 1 FROM \`migrations\`);
INSERT INTO \`migrations\` (\`version\`, \`class\`, \`group\`, \`namespace\`, \`time\`, \`batch\`)
SELECT '2026_10_03_000006',
       CONCAT('App', CHAR(92), 'Database', CHAR(92), 'Migrations', CHAR(92), 'AddBranchProductTrash'),
       'default', 'App', UNIX_TIMESTAMP(), @phx_retention_batch
WHERE NOT EXISTS (SELECT 1 FROM \`migrations\`
    WHERE \`version\` = '2026_10_03_000006' AND \`namespace\` = 'App' AND \`group\` = 'default')
AND EXISTS (SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'branch_products' AND COLUMN_NAME = 'deleted_at');

-- Record this migration only when all ten required fields exist.
INSERT INTO \`migrations\` (\`version\`, \`class\`, \`group\`, \`namespace\`, \`time\`, \`batch\`)
SELECT '2026_10_03_000007',
       CONCAT('App', CHAR(92), 'Database', CHAR(92), 'Migrations', CHAR(92), 'AddSoftDeleteFlags'),
       'default', 'App', UNIX_TIMESTAMP(), @phx_retention_batch
WHERE NOT EXISTS (SELECT 1 FROM \`migrations\`
    WHERE \`version\` = '2026_10_03_000007' AND \`namespace\` = 'App' AND \`group\` = 'default')
AND (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('products','branch_products','categories','suppliers','discounts')
      AND COLUMN_NAME IN ('is_deleted','deleted_at')) = 10;

-- Display results from application tables, without switching phpMyAdmin to information_schema.
${tables.map(t=>'SHOW COLUMNS FROM `'+t+'` WHERE `Field` IN (\'is_deleted\',\'deleted_at\');').join('\n')}
SELECT \`version\`, \`class\`, \`group\`, \`namespace\`, \`batch\` FROM \`migrations\`
WHERE \`version\` IN ('2026_10_03_000006','2026_10_03_000007') AND \`namespace\` = 'App' AND \`group\` = 'default';
SET @phx_retention_sql = NULL, @phx_retention_batch = NULL;
`;
fs.writeFileSync(path.join(root,sqlPath),sql);
const manifest=[...runtime,sqlPath,docPath];
fs.writeFileSync(path.join(__dirname,'retention-manifest.json'),JSON.stringify(manifest,null,2)+'\n');
const doc=`# Retained records and explicit is_deleted flags

Package: \`build/hostinger-soft-delete-flags-2026-10-03.zip\`.

Moving a record to Trash now sets \`is_deleted = 1\` and records its \`deleted_at\` time. Restore sets \`is_deleted = 0\` and clears the timestamp. Business records and their references remain in the database. This patch also contains the preceding branch-specific product Trash update.

## Covered records

| Table | Trash behavior |
| --- | --- |
| branch_products | Only the selected branch assignment is marked. Other branches and the shared product remain available. Stock, prices, expiry and active/inactive status are retained. |
| products | The shared catalog supports the same flag for older shared deletions. Ordinary product Trash uses the branch assignment. Older shared records still require the explicit Restore Shared Product action. |
| categories | Trash and Restore retain the category row. Categories still used by current product records cannot be trashed. |
| suppliers | Trash hides the supplier from selection and keeps product and stock-history references. |
| discounts | Trash hides the discount from new sales and keeps past usage, original dates and status. |

These are all business tables with delete/Trash actions in the current application. Users and branches already use activation/deactivation and retain their records. Sales, sale items, refunds, stock logs, activity logs and saved forecasts have no ordinary record-delete action; this patch does not introduce one.

All Delete Forever controls are removed. Old force-delete URLs return an explanation without deleting anything, and the five models reject forced deletion and purging. New flags are protected from ordinary form payloads. Default model queries, search, pagination, inventory lists, POS, replacement selection, expiry actions, dashboard inventory, notifications and reorder suggestions exclude trashed records. Records with only a flag or only an older timestamp are both recognized as trashed. Historical reports and reference joins can still read retained records.

Trash preserves quantities; physical disposal or supplier return still uses the expiry stock-removal actions and their stock logs. Backup-file cleanup and an explicitly requested full database restore remain separate operations; a database restore replaces database contents with the selected backup.

## Hostinger installation

1. Export a database backup and keep the matching current PHP files. Pause sales and inventory edits during the upgrade.
2. Select the existing application database in phpMyAdmin or SQLyog. Run **all statements** in \`database/upgrades/2026-10-03-soft-delete-flags.sql\`. Stop on any error.
3. Check that all five final column results show \`is_deleted\` with default \`0\` and \`deleted_at\`. The migration results must include \`2026_10_03_000007\` (and the preceding branch migration).
4. Upload the PHP files listed below at their matching paths in the application root. Upload the shared model and services with their dependent controllers during the same maintenance window.
5. Resume use after checking Trash and Restore for a product in two branches and for a category, supplier and discount. Confirm that the row stays in the database and that only the chosen branch product disappears from active inventory.

**Import this SQL before uploading the PHP files.** The updated queries require the new columns. New fields default to zero; existing timestamps are backfilled to one. Existing flags, timestamps, IDs, stock, transaction data and historical references are preserved. Re-importing is supported. The script includes a missing \`branch_products.deleted_at\` column, so the previous branch Trash SQL does not need a separate import when using this package.

Alternatively, run the included migrations with the normal CodeIgniter migration process instead of manually importing SQL. Use one upgrade method. The SQL records both matching migration versions only after the required fields exist. The SQL assumes an existing application schema and CodeIgniter \`migrations\` table; it is not a fresh database export and does not fix unrelated older missing migrations.

Install this patch on the current project with the earlier exchange, expiry-stock and cashier catalog features. No vendor update or new route is required. Do not upload older branch Trash or other older patch files over these newer files afterward; older code does not synchronize the explicit flags.

## PHP files

${runtime.map((p,i)=>(i+1)+'. \`'+p+'\`').join('\n')}

The SQL and this guide are deployment artifacts. No environment file, credentials, sample data, vendor files or writable files are included in the ZIP.

## Validation

All ${runtime.length} packaged PHP files passed syntax checks. The unit suite passed: **104 tests, 21,177 assertions**. Coverage includes all five model delete/restore paths, rejected permanent and unscoped deletion, retained reference data, flag-only and legacy-timestamp records, pagination, old force-delete URLs, controller restoration, migration backfill/reruns, branch isolation, expiry and exchange rejection, and checkout rechecks. Existing forecast, discount, receipt, CSV and PDF unit checks also passed.

Thirty-two local browser cases passed across product/category/supplier/discount Trash at 320, 390, 768 and 1440 px in light and dark themes. Checks included restored controls, absent permanent-delete forms, visible labels, CSRF, confirmation cancellation, keyboard access, control sizing and overflow.

Another 32 branch Trash cases passed for the product list, Trash, empty Trash and older shared deletions. Checks included branch IDs, named confirmations, legacy restore controls and responsive layouts.

Tests used in-memory SQLite and local rendered fixtures. The MySQL SQL import and native MySQL concurrent locking were not executed. This patch has not been applied to the configured application database or uploaded to Hostinger.
`;
fs.writeFileSync(path.join(root,docPath),doc);
console.log(JSON.stringify({runtimeFiles:runtime.length,packageFiles:manifest.length,sql:sqlPath,guide:docPath}));
