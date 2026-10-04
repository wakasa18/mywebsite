# Branch-specific product Trash

For the latest **Delete permanently** behavior with a recoverable 0/1 flag, use [permanent delete flags](2026-10-03-permanent-delete-flags.md). That cumulative package includes branch Trash and retains hidden rows in the database.

Package: `build/hostinger-branch-product-trash-2026-10-03.zip`.

Previously, Move to Trash deleted the shared product record. For example, trashing Adhesive Bandage in Main Branch also hid it in Patag Branch. The updated action marks only the selected branch inventory record as trashed. Other branches continue using the shared product.

## Behavior

- Only administrators can move or restore products through branch Trash. Requests must identify an existing product and branch; missing branch values never perform a shared deletion.
- Confirmation names the product and branch. Product Trash shows the branch, retained stock and trash date, and can be filtered by branch and name/SKU.
- Trashing preserves stock quantity, selling/cost prices, reorder level, expiry and the previous active/inactive status. Restore clears the trash date only for that branch. Restoring an inactive product does not reactivate it.
- Trash hides a branch record from Products, POS availability, replacement selection, dashboard inventory, notifications, expiry reports and restock suggestions. Checkout and exchange recheck availability when saving; stock-edit and expiry-action requests cannot change trashed inventory.
- Stock is physically retained. Trash does not record disposal or supplier return. Use Dispose / Return to supplier for physical stock removal. Historical sales, returns, forecasts and movement records remain available. A historical return can update retained stock without restoring the branch product to sale.
- The shared master product, other branch records and aggregate physical stock remain unchanged by Trash or Restore.
- Each successful change writes an adjustment log with zero movement, the retained quantity and a branch-specific activity entry. Repeated identical requests add no duplicate logs. Write failures roll back the state and logs together.
- Existing shared product deletions remain in a separate **Older shared product deletions** section. Its explicit **Restore Shared Product** action restores the old shared record. These historical deletions are not automatically assigned to a guessed branch.
- The existing catalog treats a trashed assignment as already assigned; an administrator must restore it rather than creating a second branch inventory record.
- New branch Trash provides Restore. It does not offer permanent deletion of a shared product.

The POS search result form also now posts its selected product ID correctly.

## Required database upgrade

This change adds one nullable DATETIME column: `branch_products.deleted_at`. It keeps branch Trash distinct from ordinary deactivation. Existing rows start with NULL; no inventory or historical shared product deletion is rewritten.

Back up the database and matching PHP files. Select the existing application database in phpMyAdmin or SQLyog, then run **all statements** in `database/upgrades/2026-10-03-branch-product-trash.sql`. Stop if a statement reports an error. Verify that the final column result includes `deleted_at` and the migration query returns version `2026_10_03_000006`.

Alternatively, run the included CodeIgniter migration with your normal migration deployment process. Use one upgrade method. Both skip the column if it already exists.

**Import the SQL before uploading the updated PHP files.** The updated inventory queries require this column. The SQL assumes existing `branch_products` and CodeIgniter `migrations` tables and has no database name or USE statement.

## PHP files to upload

Replace or add these files at the same relative paths in your application's Hostinger root:

1. `app/Libraries/BranchProductTrash.php` (new)
2. `app/Database/Migrations/2026_10_03_000006_add_branch_product_trash.php` (new)
3. `app/Models/BranchProductModel.php`
4. `app/Controllers/Products.php`
5. `app/Controllers/Dashboard.php`
6. `app/Controllers/Admin/ReportsController.php`
7. `app/Controllers/Admin/SaleCorrection.php`
8. `app/Controllers/Cashier/SalesController.php`
9. `app/Controllers/Cashier/ExchangeController.php`
10. `app/Libraries/ProductExchange.php`
11. `app/Libraries/ExpiryStockResolution.php`
12. `app/Libraries/ReorderForecastService.php`
13. `app/Libraries/TopbarNotificationService.php`
14. `app/Views/products/index.php`
15. `app/Views/products/trash.php`
16. `app/Views/admin/reports/expiry_action.php`
17. `app/Views/cashier/sales/product_rows.php`

Install this patch on the current project, including the earlier exchange, expiry and cashier catalog features. Upload the service/model/controllers before the views, during a brief pause in sales and inventory changes. No route or vendor update is needed. The two database/upgrades files in the ZIP are deployment artifacts, not website pages.

This package and database upgrade have not been deployed to Hostinger or applied to the configured application database.

## Validation

All 17 PHP files passed syntax checks. The full BusinessLogicTest suite passed: 72 tests and 20,856 assertions. Seven new tests cover branch isolation, restore preserving previous status and stock, duplicate requests, administrator/branch checks, rollback, filtering and explicit legacy restoration, POS/expiry/dashboard exclusion, exchange rejection, checkout rechecks and migration reruns. Existing test expectations were updated for readable dates and explicit in-memory migration dependencies.

Thirty-two local browser layout cases passed for product rows, branch Trash, empty Trash and legacy Trash at 320, 390, 768 and 1440 px in light/dark themes. Checks covered posted branch IDs, named confirmation, Restore forms, CSRF, cancellation, mobile controls and overflow. Mobile light and desktop dark Trash layouts were visually reviewed.

Tests used an in-memory SQLite database and local fixtures. The MySQL import script and native MySQL concurrent locking were not executed against a live database.

After deployment, use a known product available in two branches: trash its row in one branch, confirm it remains available in the other, then restore the selected branch. Check the named branch and retained quantity in Product Trash and the zero-movement stock log.
