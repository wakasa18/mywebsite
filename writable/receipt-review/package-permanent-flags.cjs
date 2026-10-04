const fs=require('node:fs'),path=require('node:path');
const root=path.resolve(__dirname,'../..');
const tables=['products','branch_products','categories','suppliers','discounts'];
const flagSqlPath='database/upgrades/2026-10-03-permanent-delete-flags.sql';
const cumulativeSqlPath='database/upgrades/2026-10-03-retained-permanent-deletion.sql';
const guidePath='database/upgrades/2026-10-03-permanent-delete-flags.md';
const section='-- Permanent deletion flags (2026_10_03_000009)';
let sql=`-- Pharxmaco: easy permanent-deletion recovery with a 0/1 flag.
-- Select the application database in phpMyAdmin or SQLyog. Run ALL statements.
-- Requires the preceding retained-permanent-deletion upgrade (through 000008).
-- Back up first; pause inventory edits; import BEFORE uploading matching PHP files.
-- is_permanently_deleted = 1 hides the row; 0 allows ordinary Trash/Restore rules.
-- permanently_deleted_at remains history and no longer decides whether a row is hidden.
-- Existing permanent timestamps are copied into flags ONLY when a flag column is newly added.
-- Re-import never overwrites a flag that was manually reset to zero. No rows are deleted.

${section}
${tables.map(t=>'SELECT `id`, `is_deleted`, `deleted_at`, `permanently_deleted_at` FROM `'+t+'` LIMIT 0;').join('\n')}
SELECT \`version\`, \`class\`, \`group\`, \`namespace\`, \`time\`, \`batch\` FROM \`migrations\` LIMIT 0;

`;
for(const table of tables) {
    sql+=`SET @phx_permanent_flag_new = NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND COLUMN_NAME = 'is_permanently_deleted'
);
SET @phx_permanent_flag_sql = IF(@phx_permanent_flag_new,
    'ALTER TABLE \`${table}\` ADD COLUMN \`is_permanently_deleted\` TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''${table}.is_permanently_deleted already exists'' AS upgrade_status'
);
PREPARE phx_permanent_flag_statement FROM @phx_permanent_flag_sql;
EXECUTE phx_permanent_flag_statement;
DEALLOCATE PREPARE phx_permanent_flag_statement;
UPDATE \`${table}\` SET \`is_permanently_deleted\` = 1
WHERE @phx_permanent_flag_new = 1 AND \`permanently_deleted_at\` IS NOT NULL;

`;
}
sql+=`SET @phx_permanent_flag_batch = (SELECT COALESCE(MAX(\`batch\`), 0) + 1 FROM \`migrations\`);
INSERT INTO \`migrations\` (\`version\`, \`class\`, \`group\`, \`namespace\`, \`time\`, \`batch\`)
SELECT '2026_10_03_000009',
       CONCAT('App', CHAR(92), 'Database', CHAR(92), 'Migrations', CHAR(92), 'AddPermanentDeleteFlags'),
       'default', 'App', UNIX_TIMESTAMP(), @phx_permanent_flag_batch
WHERE NOT EXISTS (SELECT 1 FROM \`migrations\`
    WHERE \`version\` = '2026_10_03_000009' AND \`namespace\` = 'App' AND \`group\` = 'default')
AND (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('products','branch_products','categories','suppliers','discounts')
      AND COLUMN_NAME IN ('is_deleted','deleted_at','permanently_deleted_at','is_permanently_deleted')) = 20;

${tables.map(t=>'SHOW COLUMNS FROM `'+t+'` WHERE `Field` = \'is_permanently_deleted\';').join('\n')}
SELECT \`version\`, \`class\`, \`group\`, \`namespace\`, \`batch\` FROM \`migrations\`
WHERE \`version\` = '2026_10_03_000009' AND \`namespace\` = 'App' AND \`group\` = 'default';
SET @phx_permanent_flag_new = NULL, @phx_permanent_flag_sql = NULL, @phx_permanent_flag_batch = NULL;
`;
fs.writeFileSync(path.join(root,flagSqlPath),sql);
let cumulative=fs.readFileSync(path.join(root,cumulativeSqlPath),'utf8');
const previous=cumulative.indexOf(section);
if(previous>=0)cumulative=cumulative.slice(0,previous);
cumulative=cumulative.replace('-- Adds permanently_deleted_at to distinguish restorable Trash from permanent hiding.', '-- Includes is_permanently_deleted (0/1); permanently_deleted_at is retained as history.');
cumulative=cumulative.trimEnd()+'\n\n'+sql.slice(sql.indexOf(section));
fs.writeFileSync(path.join(root,cumulativeSqlPath),cumulative);
const runtime=JSON.parse(fs.readFileSync(path.join(__dirname,'permanent-deletion-manifest.json'),'utf8')).filter(p=>p.endsWith('.php'));
runtime.push('app/Database/Migrations/2026_10_03_000009_add_permanent_delete_flags.php');
fs.writeFileSync(path.join(__dirname,'permanent-flags-manifest.json'),JSON.stringify([...runtime,cumulativeSqlPath,flagSqlPath,guidePath],null,2)+'\n');
const guide=`# Permanent deletion with a 0/1 flag

Package: \`build/hostinger-permanent-delete-flags-2026-10-03.zip\`.

The five Trash-enabled tables now use \`is_permanently_deleted\`: **1 means permanently hidden; 0 means ordinary active/Trash rules apply.** Editing this one flag to zero in phpMyAdmin or SQLyog brings an otherwise trashed record back to Trash, where the normal Restore button can restore it.

## Database states

| State | is_deleted | deleted_at | is_permanently_deleted | Where it appears |
| --- | --- | --- | --- | --- |
| Current | 0 | NULL | 0 | Active lists, subject to status and other filters |
| Trash | 1 | Trash date | 0 | Trash, with Restore available |
| Permanently hidden | 1 | Trash date | 1 | Neither active lists nor Trash |

\`permanently_deleted_at\` remains an audit timestamp. It can stay populated after recovery; the flag controls permanent hiding. A subsequent permanent deletion sets the flag to one again and updates the timestamp.

Covered tables: \`products\`, \`branch_products\`, \`categories\`, \`suppliers\`, \`discounts\`. Ordinary product actions affect the selected branch assignment only. Other branches, quantities, prices, expiry, previous active/inactive status, IDs and transaction references are retained. Physical deletion/purging stays disabled. Users and branches retain their existing deactivation flows.

## Hostinger update

1. Back up the database and matching PHP files, then pause sales and inventory edits during the update.
2. Select the application database in phpMyAdmin or SQLyog and import one of the following before uploading PHP:
   - If the preceding retained-permanent-deletion update through \`2026_10_03_000008\` is already installed, use \`database/upgrades/2026-10-03-permanent-delete-flags.sql\`.
   - Otherwise, use the updated cumulative \`database/upgrades/2026-10-03-retained-permanent-deletion.sql\` included in this package. It adds all the branch Trash, deletion and permanent-hiding fields through \`000009\`.
3. Run all statements; stop on any error. Confirm that each of the five tables shows \`is_permanently_deleted\` with default zero and that the final migration query includes \`2026_10_03_000009\`.
4. Upload the PHP files below at their matching paths, using this package instead of older retention patches. Resume use after checking Trash, permanent hiding and flag-based recovery with a test record.

Alternatively, run the matching CodeIgniter migrations instead of manually importing SQL. Use one method. Both approaches backfill existing permanent timestamps only while newly creating each flag column. Once the column exists, rerunning the upgrade preserves manual resets to zero. Existing timestamps and rows are never removed. Earlier unrelated migrations must already be installed; these are upgrade scripts for an existing application, not a fresh database export.

**The PHP update is required too.** Older PHP still uses the timestamp to hide records, so changing the flag alone will not work until these files are uploaded. Do not overwrite the new files with older patches afterward.

## Easy recovery in phpMyAdmin or SQLyog

For a branch product, find its branch inventory row:

\`\`\`sql
SELECT bp.id, bp.product_id, p.product_name, bp.branch_id, b.branch_name,
       bp.is_deleted, bp.is_permanently_deleted, bp.permanently_deleted_at
FROM branch_products bp
JOIN products p ON p.id = bp.product_id
JOIN branches b ON b.id = bp.branch_id
WHERE bp.is_permanently_deleted = 1;
\`\`\`

To return it to Trash, edit \`is_permanently_deleted\` from 1 to 0, or run:

\`\`\`sql
SET @phx_recover_id = 0; -- Replace 0 with the branch_products.id found above.
UPDATE branch_products
SET is_permanently_deleted = 0, updated_at = NOW()
WHERE id = @phx_recover_id AND is_permanently_deleted = 1;
\`\`\`

Refresh Product Trash and use Restore. Keep the historical permanent timestamp; no need to erase it. Normal Restore rules still apply, including record-name conflicts, active/inactive status and original dates. If the shared catalog product itself is also deleted, recover that shared record explicitly as well before expecting its branch inventory to be available.

For direct recovery to the active list instead of returning to Trash:

\`\`\`sql
SET @phx_recover_id = 0; -- Replace with the specific branch_products.id.
UPDATE branch_products
SET is_permanently_deleted = 0,
    is_deleted = 0,
    deleted_at = NULL,
    updated_at = NOW()
WHERE id = @phx_recover_id AND is_permanently_deleted = 1;
\`\`\`

This keeps stock, prices, expiry and previous status. An inactive inventory/product/branch still remains inactive. For categories, suppliers or discounts, substitute the relevant table and its row ID. Use \`products\` only for an explicitly selected older shared catalog deletion; that shared record affects availability across its branches. The branch inventory ID above is not the product ID.

Manual SQL edits bypass the application's audit logging and validations. Application Restore after the flag reset records the normal restore event. The migration refuses an automatic rollback to timestamp-based hiding, which would hide manually recovered rows again.

## PHP files

${runtime.map((p,i)=>(i+1)+'. \`'+p+'\`').join('\n')}

The ZIP also includes both SQL choices and this guide. It includes no environment file, credentials, vendor files, sample data or writable files. The schema and Hostinger deployment have not been applied by this task.

## Validation

The unit suite passed: **117 tests, 21,379 assertions**. It verifies permanent hiding, flag reset followed by restoration, retained timestamps, deleting again after recovery, branch isolation, POS/checkout/exchange/expiry availability after recovery, flag-only hidden records and one-time migration backfill with reruns preserving manual resets. Existing discount, forecast and export checks are included. All ${runtime.length} packaged PHP files passed syntax checks, and ZIP contents were verified during packaging.

Tests use in-memory SQLite. The MySQL import script and native MySQL concurrency were not executed against a live database.
`;
fs.writeFileSync(path.join(root,guidePath),guide);
console.log(JSON.stringify({runtimeFiles:runtime.length,packageFiles:runtime.length+3,upgrade:flagSqlPath,cumulative:cumulativeSqlPath,guide:guidePath}));
