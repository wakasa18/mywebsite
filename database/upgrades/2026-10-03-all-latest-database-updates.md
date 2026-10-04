# Latest database updates for the supplied Hostinger export

Prepared from `u315645644_pharxmacotest (1).sql`, exported on October 3, 2026. The original downloaded file is unchanged.

## Choose the file for your database

| Situation | SQL file |
| --- | --- |
| The existing database already contains your accounts, products and transactions | [2026-10-03-all-latest-database-updates.sql](2026-10-03-all-latest-database-updates.sql) |
| You are restoring the supplied export into an empty database | [u315645644_pharxmacotest-latest-2026-10-03.sql](../exports/u315645644_pharxmacotest-latest-2026-10-03.sql) |

Use one file. The updated full export contains the original export bytes followed by the latest upgrades. It includes the original records, table definitions, keys, constraints and auto-increment settings; importing it into an existing database would encounter existing tables. Its records are from the supplied snapshot, not a fresh export of your live website.

The upgrade file contains schema changes and migration registration only. It does not import or duplicate your accounts, sales, inventory or forecast records. Existing columns are checked before adding them, so repeat imports are supported.

## Included updates

The supplied export already contains refund tracking, checkout recovery and its unique index, supplier email, session invalidation, refund return conditions, payout methods, refund event grouping, the forecast table and report performance indexes. Its migration history also includes the earlier export-history cleanup. These existing records and definitions are retained.

Four newer migrations are added:

| Migration | Update |
| --- | --- |
| `2026_10_03_000006` | `branch_products.deleted_at` for branch-specific Trash |
| `2026_10_03_000007` | `is_deleted` on products, branch products, categories, suppliers and discounts |
| `2026_10_03_000008` | `permanently_deleted_at` on those five tables, preserving permanent deletion history |
| `2026_10_03_000009` | `is_permanently_deleted` on those five tables for easy 0/1 recovery |

This adds **16 missing columns** to the supplied database. Existing Trash dates remain in place and populate `is_deleted`. Existing permanent timestamps populate the permanent flag only when that flag column is first added; later repeat imports preserve a manual reset to zero. Other UI and report changes use the existing schema. Browser interface settings do not require a settings table.

## Import in phpMyAdmin or SQLyog

1. Export a backup of your current database. Pause sales and inventory changes during the update.
2. Select the correct application database in the SQL client. The files do not contain a `USE` statement or create a database.
3. Import the appropriate file above. In SQLyog, execute the complete script in the same connection. Stop on any SQL error; schema changes commit automatically.
4. Confirm that the last results show `is_permanently_deleted` on all five tables and migration `2026_10_03_000009`.
5. Use the current matching PHP files, including the [permanent-delete-flags package](2026-10-03-permanent-delete-flags.md). The database import alone does not update PHP behavior.

The older `2026-10-03-branch-product-trash.sql` adds only the original branch Trash column. This combined upgrade includes that change and the later deletion updates, so a separate import of the older file is unnecessary.

## Easy recovery

`is_permanently_deleted = 1` hides the retained record from active pages and Trash. Change it to **0** to return an otherwise trashed record to Trash, then use the application's Restore button. Keep `permanently_deleted_at` as history.

For branch inventory, edit the matching row in `branch_products`, using its branch ID to identify the correct assignment. The shared `products` record and other branches remain separate. [Detailed recovery instructions](2026-10-03-permanent-delete-flags.md#easy-recovery-in-phpmyadmin-or-sqlyog).

## Verification

Both import paths were executed against newly created disposable databases on local **MySQL 8.2.0**. All 15 original tables and **3,300 original records** were checked with hashes of their original columns. Both imports preserved those records, added the 16 columns and registered the four migrations correctly. Repeat imports did not duplicate migration records, and a manual permanent-flag reset survived another import with its historical timestamp intact.

The disposable databases were removed after verification. The configured application database and Hostinger database were not changed. The downloaded export's original bytes were preserved inside the updated full export.
