# Delete permanently while retaining database records

For the latest **0/1 permanent-deletion flag** and easy database recovery, use [permanent delete flags](2026-10-03-permanent-delete-flags.md). The updated cumulative SQL at the same path now includes that flag upgrade through migration `000009`; use the newer flags package with it.

Package: `build/hostinger-retained-permanent-deletion-2026-10-03.zip`.

**Delete permanently now removes a record from active lists and Trash while keeping its database row.** Restore remains available for ordinary Trash only. This replaces the previous patch that disabled permanent-delete controls.

## States and behavior

| State | is_deleted | deleted_at | permanently_deleted_at | Visible in active lists | Visible in Trash | Restore in the application |
| --- | --- | --- | --- | --- | --- | --- |
| Current | 0 | NULL | NULL | Yes, subject to other filters/status | No | Not needed |
| Trash | 1 | Trash date | NULL | No | Yes | Yes |
| Permanently hidden | 1 | Original Trash date | Permanent-hiding date | No | No | No |

Older timestamp-only or flag-only Trash records are still recognized. Existing Trash is not automatically permanently hidden by the upgrade.

- Products, categories, suppliers and discounts have **Restore** and **Delete permanently** buttons in Trash. The action is a POST with CSRF and confirmation. It requires an administrator and a record already in Trash.
- Product actions apply to the selected `branch_products` row. Other branches, the shared catalog record and aggregate physical stock remain unchanged. The confirmation identifies the branch.
- Older shared product deletions have a separate explicit shared action. This hides the already-trashed shared catalog record; it does not change the retained branch inventory rows. A missing branch parameter never guesses a branch or silently performs a shared deletion.
- Records, IDs, quantities, selling/cost prices, expiry, active/inactive status, sales, refunds, stock history and discount usage remain stored. Historical receipts and transaction reports keep their original linked information. Permanent hiding does not dispose of physical stock.
- Hidden records are excluded from active inventory, POS, replacement selection, expiry actions, notifications, restock suggestions and Trash pagination. Old Restore requests cannot make them visible again. Manual database repair/recovery is outside the ordinary application flow.
- A permanently hidden catalog/SKU or branch assignment stays retained and cannot be silently recreated as a duplicate assignment. The catalog and stock forms report this state where applicable.
- Each successful operation writes an audit entry. Branch actions also write a zero-movement stock log. Repeated identical requests add no duplicate logs. State and logs roll back together if a write fails.
- Physical model deletion and purging remain blocked. These buttons perform UPDATE statements, never DELETE statements. Users and branches keep their existing deactivation flows. Backup-file cleanup and an explicitly requested full database restore remain separate operations.

## Hostinger deployment

1. Back up the database and the matching PHP files, then pause sales and inventory edits during deployment.
2. Select the application database in phpMyAdmin or SQLyog. Run **all statements** in `database/upgrades/2026-10-03-retained-permanent-deletion.sql`. Stop on any error.
3. Confirm that the final five column results each show `is_deleted`, `deleted_at` and `permanently_deleted_at`, and that the migration results include `2026_10_03_000008`.
4. Upload the PHP files listed below to their matching paths in the application's Hostinger root. Resume use after checking the workflow with a test record.

**Import the SQL before uploading the PHP files.** This cumulative upgrade includes the preceding branch Trash timestamp and flags if missing, so no separate import of the previous two scripts is necessary. It preserves existing flags and timestamps on re-import. The five new markers default to NULL and are never reset by re-importing. Existing rows and references are preserved; the script does not delete data and has no database name or USE statement.

Alternatively, run migrations with the normal CodeIgniter deployment process instead of manually importing SQL. Use one method. The SQL records the matching migration versions after their required columns exist. This is an existing-installation upgrade, not a fresh database export, and assumes the current application's business tables and CodeIgniter `migrations` table. Earlier unrelated migrations must already be installed.

Use this package instead of the previous `hostinger-soft-delete-flags-2026-10-03.zip`. It includes the branch-specific Trash implementation and flags. Install it on the current project with the earlier exchange, expiry-stock and cashier catalog features. No route or vendor update is required. Do not subsequently overwrite these files with older patches: older code does not enforce the permanent-hiding marker.

The new marker migration intentionally refuses an automatic rollback that would make permanently hidden records appear in Trash again. Retain the marker and matching code when planning any rollback.

## PHP files

1. `app/Models/RetainedRecordModel.php`
2. `app/Models/ProductModel.php`
3. `app/Models/BranchProductModel.php`
4. `app/Models/CategoryModel.php`
5. `app/Models/SupplierModel.php`
6. `app/Models/DiscountModel.php`
7. `app/Database/Migrations/2026_10_03_000006_add_branch_product_trash.php`
8. `app/Database/Migrations/2026_10_03_000007_add_soft_delete_flags.php`
9. `app/Controllers/Products.php`
10. `app/Controllers/Categories.php`
11. `app/Controllers/Dashboard.php`
12. `app/Controllers/Admin/Suppliers.php`
13. `app/Controllers/Admin/Discounts.php`
14. `app/Controllers/Admin/ReportsController.php`
15. `app/Controllers/Admin/SaleCorrection.php`
16. `app/Controllers/Admin/HistoricalSalesImport.php`
17. `app/Controllers/Cashier/SalesController.php`
18. `app/Controllers/Cashier/ExchangeController.php`
19. `app/Libraries/BranchProductTrash.php`
20. `app/Libraries/ProductExchange.php`
21. `app/Libraries/ExpiryStockResolution.php`
22. `app/Libraries/ReorderForecastService.php`
23. `app/Libraries/TopbarNotificationService.php`
24. `app/Views/products/index.php`
25. `app/Views/products/trash.php`
26. `app/Views/categories/trash.php`
27. `app/Views/admin/suppliers/trash.php`
28. `app/Views/admin/discounts/trash.php`
29. `app/Views/admin/reports/expiry_action.php`
30. `app/Views/cashier/sales/product_rows.php`
31. `app/Libraries/RetainedRecordDeletion.php`
32. `app/Database/Migrations/2026_10_03_000008_add_permanent_delete_markers.php`

The ZIP also includes the cumulative SQL and this guide. It contains no environment file, credentials, sample data, vendor files or writable files.

## Verification

The unit suite passed: **112 tests, 21,286 assertions**. Tests cover retained rows and historical references, hiding from Trash, non-restorability, used suppliers/categories/discounts, branch isolation, missing/invalid branch values, administrator checks, repeated requests, audit rollback, additive/repeatable migration and existing checkout/forecast/export behavior.

Sixty-four local browser cases passed at 320, 390, 768 and 1440 px in light and dark themes. Checks cover both Trash actions, posted branch IDs, explicit older shared controls, CSRF, confirmation cancellation, keyboard access, control sizes and overflow. PHP syntax and ZIP contents were also verified.

Tests used in-memory SQLite and local rendered fixtures. The MySQL import script and native MySQL concurrent locking were not executed. No configured application database or Hostinger deployment was changed.

After deployment, move a test record to Trash, then click Delete permanently. Verify that it disappears from Trash, that its row has `is_deleted = 1` and a `permanently_deleted_at` value, and that historical references remain present. For a product sold by two branches, confirm that only the selected branch assignment is hidden.
