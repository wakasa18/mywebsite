# Modal interface update

Files-only update. No SQL import or database changes are required.

Package: `build/hostinger-modals-2026-09-22.zip`.

## Upload

Back up the matching files on Hostinger, then upload all files from this package together. The package includes the sidebar/topbar dependencies used by the updated staff layout.

- `app/Views/layouts/staff.php`
- `app/Views/layouts/low_stock_modal.php` (new)
- `app/Views/layouts/sidebar.php`
- `app/Views/layouts/topbar.php`
- `app/Views/admin/reports/index.php`
- `app/Views/cashier/sales/index.php`
- `public/assets/css/modals.css` (new)
- `public/assets/js/modals.js` (new)
- `public/assets/css/sidebar.css`
- `public/assets/css/topbar.css`
- `public/assets/js/topbar.js`

Upload `app/` to the existing private application directory. If the contents of `public/` are hosted directly in `public_html`, upload the assets to `public_html/assets/css/` and `public_html/assets/js/`. Keep the existing `.env`, front controller, dependencies and database. Upload new partials and assets before the updated views, then reload open application tabs.

## Changes

- Low-stock products fit the desktop dialog without horizontal scrolling. Phones and tablets show labeled product cards.
- Search, status badges, headings and action buttons have consistent spacing and theme colors. The search field does not automatically open a phone keyboard.
- Dialog headers and the low-stock footer remain accessible while the content scrolls, including short landscape screens.
- Restock suggestions and saved forecast records use responsive cards on smaller screens. Removed the old nested table height limit that caused overlapping content.
- Held sales have larger Resume/Discard controls and a clearer mobile layout. Existing forms and calculations are preserved.
- Shared keyboard handling traps focus, closes on Escape/backdrop, restores the previous focus and scroll state, and prevents interaction with the page behind the dialog.
- POS search and checkout shortcuts pause while a dialog is open. Closing low stock from notifications returns focus to the notification button.

## Validation

Local Chrome checks used the actual PHP views with synthetic records: 36 modal combinations across three dialogs, six viewport sizes (320px through 1440px, including landscape), and both themes. Checks covered overflow, content overlap, touch targets, search/empty results, Tab wrapping, Escape/backdrop dismissal, background isolation, scroll restoration and notification focus. A separate check confirmed checkout shortcuts cannot run behind the low-stock dialog. All 20 sidebar/topbar regression layouts also passed. Changed PHP views and the shared JavaScript passed syntax checks.

After uploading, open Low-stock products from notifications, Restock Suggestions from Reports, and Held Sales from the POS. Check search, scrolling, close buttons and dark mode on a phone and desktop. The package has not been deployed to the live website by this task.
