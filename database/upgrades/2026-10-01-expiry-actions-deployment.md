# Expiry stock actions

Package: `build/hostinger-expiry-actions-2026-10-01.zip`.

The Expiry Report now has an Actions column and resolution history. Cashiers can replace stock or record disposal/supplier returns in their assigned branch. **Deactivate in branch is admin-only**, enforced both in the interface and in the server handling the request.

## Actions

### Replace stock

Open Replace stock, choose what happened to the old stock (Disposed or Returned to supplier), enter the new quantity and a future expiration date, and record a reason or supplier reference. Check the confirmation box and save.

The application stores one expiration date per product per branch. Replacement therefore removes **all existing stock in that branch** before receiving the new quantity. It records the old stock out and new stock in as separate movements within one database transaction, preserving both expiration dates in the remarks. The new quantity is the total replacement stock, not an amount added to the old quantity. Current cost and selling prices are retained. This workflow does not support keeping separate batches with different expiry dates.

### Dispose / Return

Choose Disposed or Returned to supplier and enter the quantity physically removed, which may be part or all of the current stock. A reason/reference and confirmation are required. Remaining stock keeps its current expiration date. Removing all units leaves a zero-stock inventory record; it does not delete the product or change its expiry date.

These actions record inventory movements. They do not process supplier payments or financial credits. New delivery prices can be reviewed separately through the existing admin product editor.

### Deactivate in branch — admin only

This sets only the selected `branch_products` record to Inactive. It preserves stock, expiry, the master product and every other branch. A zero-quantity adjustment and an activity event record the deactivation and reason. This is different from the existing product Trash action, which affects the master product across branches.

An administrator can reactivate the branch inventory in Edit Product by choosing Active for that branch. The checkout path also checks the locked inventory status, so a product deactivated while a checkout is in progress cannot pass that check.

## History and safeguards

- View history shows the latest 50 expiry-resolution movements for the selected product/branch, including old/new stock, expiry details, staff, reason and recorded time. Replacements have two movement entries when old stock exists.
- Resolution history at the top of the report opens the existing searchable, paginated Stock Logs. It includes prior resolutions even when the product is inactive or in trash. Existing stock-log exports can export these filtered records.
- Cashiers cannot read or update another branch's record by changing the URL or posted input.
- All mutation requests are POST with the existing CSRF filter, a form token and an explicit confirmation checkbox.
- The server locks the inventory row and checks the stock/expiry/status snapshot again before saving. Stale forms must be reviewed again.
- A marker in the existing stock-log remarks makes retries of the same form idempotent. Preserve these log entries; editing/deleting them is not part of this feature.
- Inventory changes, stock logs and the activity event commit together. A failed write rolls back the action.
- The expiry query explicitly excludes soft-deleted products, including from the existing CSV/PDF export query.

## Upload

Back up and replace all seven runtime files, keeping the project folder paths:

1. `app/Libraries/ExpiryStockResolution.php` — new
2. `app/Controllers/ExpiryStockController.php` — new
3. `app/Views/admin/reports/expiry_action.php` — new
4. `app/Views/admin/reports/expiry_report.php`
5. `app/Controllers/Admin/ReportsController.php`
6. `app/Controllers/Cashier/SalesController.php`
7. `app/Config/Routes.php`

No SQL import or schema migration is required. The package uses the existing stock/activity logs, inventory status and expiration-date fields. It assumes the project's existing readable-date helper, responsive table styles and transactional InnoDB tables. Upload the library and new view/controller before enabling their routes, or pause application use while uploading the whole package. This package includes the current local versions of shared controllers; keep the preceding application updates installed.

This package has not been uploaded to Hostinger. Test on a copy before recording real stock movements.

## Verification

25 focused PHP tests passed with 170 assertions: nine expiry-specific tests plus exchange, refund and checkout regressions. Expiry checks cover two-stage replacement, partial/all removal, preserving other branches, admin-only deactivation, forged cashier requests, stale stock, invalid quantities/dates, confirmation/token checks, duplicate submissions, failed-log rollback, history after trash, and checkout after a concurrent deactivation. All seven runtime PHP files passed syntax checks.

64 browser layout cases passed at 320, 390, 768 and 1440 px widths in light/dark themes, including role-specific action visibility, required form confirmation and CSRF fields. Mobile replacement and desktop dark-mode report screenshots were visually reviewed. No real browser stock actions were submitted.

Logic tests use an in-memory SQLite database and simulate another writer for locking scenarios; they do not verify native MySQL concurrent scheduling. No production database was modified.
