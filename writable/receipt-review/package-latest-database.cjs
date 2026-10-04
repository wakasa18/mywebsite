const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const assert = require('node:assert/strict');

const root = path.resolve(__dirname, '../..');
const sourcePath = 'C:/Users/warre/Downloads/u315645644_pharxmacotest (1).sql';
const original = fs.readFileSync(sourcePath);
const originalSql = original.toString('utf8');
const tables = [...originalSql.matchAll(/CREATE TABLE `([^`]+)` \(([\s\S]*?)\) ENGINE[^;]*;/g)];
const schema = Object.fromEntries(tables.map(match => [match[1], [...match[2].matchAll(/^  `([^`]+)`/gm)].map(column => column[1])]));
const retained = ['products', 'branch_products', 'categories', 'suppliers', 'discounts'];
const fields = ['deleted_at', 'is_deleted', 'permanently_deleted_at', 'is_permanently_deleted'];
const existingRequirements = {
    sales: ['checkout_token_hash'],
    suppliers: ['email'],
    users: ['session_version'],
    refund_items: ['return_condition', 'refund_method', 'refund_event_id'],
    migrations: ['version', 'class', 'group', 'namespace', 'time', 'batch'],
};
for (const [table, columns] of Object.entries(existingRequirements)) {
    for (const column of columns) assert(schema[table]?.includes(column), `Baseline missing ${table}.${column}`);
}
for (const table of retained) assert(schema[table], `Missing ${table}`);
const missing = retained.flatMap(table => fields.filter(column => !schema[table].includes(column)).map(column => `${table}.${column}`));
assert.equal(missing.length, 16, 'The supplied source has changed; compare it again before packaging.');
const sourceHash = crypto.createHash('sha256').update(original).digest('hex');
const cumulative = fs.readFileSync(path.join(root, 'database/upgrades/2026-10-03-retained-permanent-deletion.sql'), 'utf8');
const firstStatement = cumulative.indexOf('SELECT `id` FROM `products` LIMIT 0;');
assert(firstStatement > 0);
for (const version of ['000006', '000007', '000008', '000009']) assert(cumulative.includes(`2026_10_03_${version}`));
const upgradePath = 'database/upgrades/2026-10-03-all-latest-database-updates.sql';
const exportPath = 'database/exports/u315645644_pharxmacotest-latest-2026-10-03.sql';
const header = `-- Pharxmaco: all missing database updates for the supplied October 3, 2026 export.
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

`;
const preflight = Object.entries(existingRequirements).map(([table, columns]) => `SELECT ${columns.map(column => '`' + column + '`').join(', ')} FROM \`${table}\` LIMIT 0;`).join('\n');
const upgrade = header + preflight + '\n\n' + cumulative.slice(firstStatement);
const exportHeader = Buffer.from(`-- UPDATED FULL EXPORT: Pharxmaco, October 3, 2026.
-- Import this WHOLE FILE only into an EMPTY selected database.
-- It contains the supplied original records followed by the latest schema updates.
-- To update an existing database, use 2026-10-03-all-latest-database-updates.sql instead.
-- Source export SHA-256: ${sourceHash}

`, 'utf8');
const suffix = Buffer.from('\n\n-- Latest application database upgrades follow the original export.\n\n' + upgrade, 'utf8');
fs.mkdirSync(path.join(root, 'database/exports'), { recursive: true });
fs.writeFileSync(path.join(root, upgradePath), upgrade);
fs.writeFileSync(path.join(root, exportPath), Buffer.concat([exportHeader, original, suffix]));
assert(fs.readFileSync(path.join(root, exportPath)).subarray(exportHeader.length, exportHeader.length + original.length).equals(original), 'Original export bytes were changed.');
const manifest = { sourceHash, sourceBytes: original.length, tables: tables.map(match => match[1]), missingColumns: missing, upgradePath, exportPath };
fs.writeFileSync(path.join(__dirname, 'latest-database-manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
console.log(JSON.stringify({ tables: tables.length, missingColumns: missing.length, originalBytesPreserved: original.length, upgradePath, exportPath }));
