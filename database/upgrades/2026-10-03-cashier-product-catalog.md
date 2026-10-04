# Add existing products to a cashier's branch

Package: `build/hostinger-cashier-product-catalog-2026-10-03.zip`.

For the later layout correction, apply `build/hostinger-cashier-catalog-ui-2026-10-03.zip` after this feature package. See [the UI upload instructions](2026-10-03-cashier-catalog-ui.md).

Cashiers can open **Products → Add from Existing Products**, search by name, SKU or category, select a product, and enter their branch's initial stock, prices, reorder level and expiration date. **Create New Product** is still available for items that do not exist in the shared catalog.

## Behavior

- Reuses the existing product ID, name, SKU, unit, category, manufacturer and supplier. Products without an SKU are supported.
- Shows active, non-deleted products that are not yet assigned to the cashier's branch. An inactive assignment is not added again; an administrator must review it.
- The destination is always the cashier's assigned active branch, regardless of posted branch values.
- Prices and reorder level default to the master product values and can be reviewed for the new branch. Initial stock defaults to zero and expiry starts blank. Other branches' quantities, prices and expiry dates are preserved.
- Initial stock and reorder level must be whole numbers from 0 through 1,000,000. Prices accept up to two decimal places; selling price cannot be below cost. Past expiry dates are rejected. An empty expiry is allowed when it does not apply.
- Saving creates a branch inventory record and an activity entry. A positive initial quantity also creates a stock-in record; the master stock total is recalculated.
- Transactions and row locks protect this workflow from duplicate submissions. A write failure rolls back the addition, stock movement and activity together.
- Catalog search is paginated, with 12 products per page. Layouts support mobile, tablet, desktop and light/dark themes.

## Hostinger upload

This is a patch for the current project, including the prior cashier stock actions. It is not a complete installation.

Back up the matching application files, then upload these files to the same paths in your application root:

1. `app/Views/products/catalog.php` (new)
2. `app/Controllers/Products.php`
3. `app/Config/Routes.php`
4. `app/Views/products/create.php`
5. `app/Views/products/index.php`

Upload the new view, controller and routes before exposing the links in the existing views. The deployment note in this ZIP is documentation only.

**No database migration or SQL import is required.** The application uses the existing products, branch_products, stock_logs and activity_logs tables. This package has not been deployed to Hostinger.

## Verification

All five runtime PHP files passed syntax checks. Nine focused tests passed with 74 assertions, including the existing cashier stock regression checks. New coverage verifies shared-product reuse, branch isolation, duplicate prevention, zero stock, missing SKU, inactive/deleted products, invalid input, rollback after an audit failure, catalog search and selection.

Twenty-four browser layout cases passed across 320, 390, 768 and 1440 px in light and dark themes, covering the catalog, setup form and empty state. Checks included overflow, initial quantity/expiry defaults and CSRF fields. Mobile setup and desktop dark catalog screenshots were visually reviewed.

Backend tests used in-memory SQLite with lock-query simulation. Native MySQL concurrency and the live Hostinger site were not exercised. No production inventory was changed.

For a deployment smoke check, sign in as a cashier assigned to a new active branch. Search for a known catalog product, review the setup form and add it with the actual received quantity (or zero before delivery). Confirm it appears in that branch and no longer appears in its catalog choices. Check the stock log when the initial quantity is positive.
