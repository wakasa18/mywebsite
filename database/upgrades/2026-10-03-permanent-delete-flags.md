# Permanent deletion with a 0/1 flag

Package: `build/hostinger-permanent-delete-flags-2026-10-03.zip`.

The five Trash-enabled tables now use `is_permanently_deleted`: **1 means permanently hidden; 0 means ordinary active/Trash rules apply.** Editing this one flag to zero in phpMyAdmin or SQLyog brings an otherwise trashed record back to Trash, where the normal Restore button can restore it.

## Database states

| State | is_deleted | deleted_at | is_permanently_deleted | Where it appears |
| --- | --- | --- | --- | --- |
| Current | 0 | NULL | 0 | Active lists, subject to status and other filters |
| Trash | 1 | Trash date | 0 | Trash, with Restore available |
| Permanently hidden | 1 | Trash date | 1 | Neither active lists nor Trash |

`permanently_deleted_at` remains an audit timestamp. It can stay populated after recovery; the flag controls permanent hiding. A subsequent permanent deletion sets the flag to one again and updates the timestamp.

Covered tables: `products`, `branch_products`, `categories`, `suppliers`, `discounts`. Ordinary product actions affect the selected branch assignment only. Other branches, quantities, prices, expiry, previous active/inactive status, IDs and transaction references are retained. Physical deletion/purging stays disabled. Users and branches retain their existing deactivation flows.

## Hostinger update

1. Back up the database and matching PHP files, then pause sales and inventory edits during the update.
2. Select the application database in phpMyAdmin or SQLyog and import one of the following before uploading PHP:
   - If the preceding retained-permanent-deletion update through `2026_10_03_000008` is already installed, use `database/upgrades/2026-10-03-permanent-delete-flags.sql`.
   - Otherwise, use the updated cumulative `database/upgrades/2026-10-03-retained-permanent-deletion.sql` included in this package. It adds all the branch Trash, deletion and permanent-hiding fields through `000009`.
3. Run all statements; stop on any error. Confirm that each of the five tables shows `is_permanently_deleted` with default zero and that the final migration query includes `2026_10_03_000009`.
4. Upload the PHP files below at their matching paths, using this package instead of older retention patches. Resume use after checking Trash, permanent hiding and flag-based recovery with a test record.

Alternatively, run the matching CodeIgniter migrations instead of manually importing SQL. Use one method. Both approaches backfill existing permanent timestamps only while newly creating each flag column. Once the column exists, rerunning the upgrade preserves manual resets to zero. Existing timestamps and rows are never removed. Earlier unrelated migrations must already be installed; these are upgrade scripts for an existing application, not a fresh database export.

**The PHP update is required too.** Older PHP still uses the timestamp to hide records, so changing the flag alone will not work until these files are uploaded. Do not overwrite the new files with older patches afterward.

## Easy recovery in phpMyAdmin or SQLyog

For a branch product, find its branch inventory row:

```sql
SELECT bp.id, bp.product_id, p.product_name, bp.branch_id, b.branch_name,
       bp.is_deleted, bp.is_permanently_deleted, bp.permanently_deleted_at
FROM branch_products bp
JOIN products p ON p.id = bp.product_id
JOIN branches b ON b.id = bp.branch_id
WHERE bp.is_permanently_deleted = 1;
```

To return it to Trash, edit `is_permanently_deleted` from 1 to 0, or run:

```sql
SET @phx_recover_id = 0; -- Replace 0 with the branch_products.id found above.
UPDATE branch_products
SET is_permanently_deleted = 0, updated_at = NOW()
WHERE id = @phx_recover_id AND is_permanently_deleted = 1;
```

Refresh Product Trash and use Restore. Keep the historical permanent timestamp; no need to erase it. Normal Restore rules still apply, including record-name conflicts, active/inactive status and original dates. If the shared catalog product itself is also deleted, recover that shared record explicitly as well before expecting its branch inventory to be available.

For direct recovery to the active list instead of returning to Trash:

```sql
SET @phx_recover_id = 0; -- Replace with the specific branch_products.id.
UPDATE branch_products
SET is_permanently_deleted = 0,
    is_deleted = 0,
    deleted_at = NULL,
    updated_at = NOW()
WHERE id = @phx_recover_id AND is_permanently_deleted = 1;
```

This keeps stock, prices, expiry and previous status. An inactive inventory/product/branch still remains inactive. For categories, suppliers or discounts, substitute the relevant table and its row ID. Use `products` only for an explicitly selected older shared catalog deletion; that shared record affects availability across its branches. The branch inventory ID above is not the product ID.

Manual SQL edits bypass the application's audit logging and validations. Application Restore after the flag reset records the normal restore event. The migration refuses an automatic rollback to timestamp-based hiding, which would hide manually recovered rows again.

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
33. `app/Database/Migrations/2026_10_03_000009_add_permanent_delete_flags.php`

The ZIP also includes both SQL choices and this guide. It includes no environment file, credentials, vendor files, sample data or writable files. The schema and Hostinger deployment have not been applied by this task.

## Validation

The unit suite passed: **117 tests, 21,379 assertions**. It verifies permanent hiding, flag reset followed by restoration, retained timestamps, deleting again after recovery, branch isolation, POS/checkout/exchange/expiry availability after recovery, flag-only hidden records and one-time migration backfill with reruns preserving manual resets. Existing discount, forecast and export checks are included. All 33 packaged PHP files passed syntax checks, and ZIP contents were verified during packaging.

Tests use in-memory SQLite. The MySQL import script and native MySQL concurrency were not executed against a live database.
