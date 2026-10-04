# Retained records and explicit is_deleted flags

Superseded by [retained permanent deletion](2026-10-03-retained-permanent-deletion.md): Delete permanently now hides records from the application and Trash while retaining their database rows. Use that newer cumulative package.

Package: `build/hostinger-soft-delete-flags-2026-10-03.zip`.

Moving a record to Trash now sets `is_deleted = 1` and records its `deleted_at` time. Restore sets `is_deleted = 0` and clears the timestamp. Business records and their references remain in the database. This patch also contains the preceding branch-specific product Trash update.

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
2. Select the existing application database in phpMyAdmin or SQLyog. Run **all statements** in `database/upgrades/2026-10-03-soft-delete-flags.sql`. Stop on any error.
3. Check that all five final column results show `is_deleted` with default `0` and `deleted_at`. The migration results must include `2026_10_03_000007` (and the preceding branch migration).
4. Upload the PHP files listed below at their matching paths in the application root. Upload the shared model and services with their dependent controllers during the same maintenance window.
5. Resume use after checking Trash and Restore for a product in two branches and for a category, supplier and discount. Confirm that the row stays in the database and that only the chosen branch product disappears from active inventory.

**Import this SQL before uploading the PHP files.** The updated queries require the new columns. New fields default to zero; existing timestamps are backfilled to one. Existing flags, timestamps, IDs, stock, transaction data and historical references are preserved. Re-importing is supported. The script includes a missing `branch_products.deleted_at` column, so the previous branch Trash SQL does not need a separate import when using this package.

Alternatively, run the included migrations with the normal CodeIgniter migration process instead of manually importing SQL. Use one upgrade method. The SQL records both matching migration versions only after the required fields exist. The SQL assumes an existing application schema and CodeIgniter `migrations` table; it is not a fresh database export and does not fix unrelated older missing migrations.

Install this patch on the current project with the earlier exchange, expiry-stock and cashier catalog features. No vendor update or new route is required. Do not upload older branch Trash or other older patch files over these newer files afterward; older code does not synchronize the explicit flags.

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

The SQL and this guide are deployment artifacts. No environment file, credentials, sample data, vendor files or writable files are included in the ZIP.

## Validation

All 30 packaged PHP files passed syntax checks. The unit suite passed: **104 tests, 21,177 assertions**. Coverage includes all five model delete/restore paths, rejected permanent and unscoped deletion, retained reference data, flag-only and legacy-timestamp records, pagination, old force-delete URLs, controller restoration, migration backfill/reruns, branch isolation, expiry and exchange rejection, and checkout rechecks. Existing forecast, discount, receipt, CSV and PDF unit checks also passed.

Thirty-two local browser cases passed across product/category/supplier/discount Trash at 320, 390, 768 and 1440 px in light and dark themes. Checks included restored controls, absent permanent-delete forms, visible labels, CSRF, confirmation cancellation, keyboard access, control sizing and overflow.

Another 32 branch Trash cases passed for the product list, Trash, empty Trash and older shared deletions. Checks included branch IDs, named confirmations, legacy restore controls and responsive layouts.

Tests used in-memory SQLite and local rendered fixtures. The MySQL SQL import and native MySQL concurrent locking were not executed. This patch has not been applied to the configured application database or uploaded to Hostinger.
