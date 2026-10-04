# Branch inventory and sales shortcuts

Package: `build/hostinger-branch-shortcuts-2026-09-28.zip`.

No database change or SQL import is required.

## Changes

- Branch rows now offer Edit, View inventory, and View sales, with visible labels and icons.
- View inventory opens Products filtered to the selected branch.
- View sales opens Reports for active branches. For inactive branches it opens Sales History, because Reports only accepts active branches. The selected inactive branch remains visible in the history filter with an Inactive label.
- Branch rows use the existing mobile card layout so actions are accessible without horizontal scrolling on phones.
- Existing editing, permissions and historical records are retained.

## Upload

Back up and replace these files in their matching application directories:

1. `app/Controllers/Cashier/SalesController.php`
2. `app/Views/admin/branches/index.php`
3. `app/Views/cashier/sales/history.php`
4. `public/assets/css/table-actions.css`
5. `public/assets/js/workspace.js`
6. `app/Views/layouts/staff.php`

The shared table button stylesheet is included as a dependency. Upload assets before the layout. Where `public_html` contains the contents of `public/`, assets go under `public_html/assets/`. The workspace script cache version is `20260928-2`.

This package assumes the project's existing workspace assets are installed. It has not been uploaded to Hostinger.

## Verification

PHP and JavaScript syntax checks passed. Local browser fixtures covered active and inactive branches at 320, 390, 768 and 1440 pixels in light and dark themes. Checks verified shortcut destination URLs and branch IDs, button sizing, overflow, decorative icons, keyboard navigation and larger-text/accessibility settings. The mobile screenshot was reviewed. No business actions were submitted and no production database was changed.

After uploading, open each shortcut from a branch row. Confirm the destination branch filter, check an inactive branch's sales history, and try Search there to confirm the inactive selection remains selected.
