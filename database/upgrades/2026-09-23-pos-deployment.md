# Point-of-sale interface update

Files-only update. No database changes or SQL import are required.

Package: `build/hostinger-pos-2026-09-23.zip`.

## Upload

Back up the matching files on Hostinger. Upload the packaged files together, placing new assets and partials before the updated views. This package includes the current navigation and modal files required by the staff layout.

POS changes:

- `app/Views/cashier/sales/index.php`
- `public/assets/css/pos.css` (new)

Included navigation and modal dependencies:

- `app/Views/layouts/staff.php`
- `app/Views/layouts/sidebar.php`
- `app/Views/layouts/topbar.php`
- `app/Views/layouts/low_stock_modal.php`
- `app/Views/admin/reports/index.php`
- `public/assets/css/sidebar.css`
- `public/assets/css/topbar.css`
- `public/assets/css/modals.css`
- `public/assets/js/topbar.js`
- `public/assets/js/modals.js`

Upload `app/` into the existing private application directory. If the contents of `public/` are hosted directly in `public_html`, place assets in `public_html/assets/`, preserving the `css/` and `js/` subdirectories. Keep the existing `.env`, front controller, dependencies and database. Reload open browser tabs after upload; the new POS stylesheet has a versioned URL.

## Improvements

- Desktop has three clearly separated product, cart and payment panels. Phone and tablet navigation retains the Products, Cart and Payment tabs.
- Medicines show their full names, price/unit and stock status. Phones use full-width product rows; tablets use a product grid and a two-column payment summary/form.
- The Available to sell filter excludes out-of-stock and expired products. Search and category filters work together, with clear-search and reset controls.
- Quantity selection has plus/minus controls that respect the existing stock limit. Touch selection avoids automatically opening the on-screen keyboard.
- Cart quantity/remove controls have larger touch targets and descriptive accessible names. Cancel and Escape restore focus to the selected medicine; arrow navigation skips unavailable products.
- Payment has a prominent total, clearer tender controls, labeled notes, and matching light/dark colors. Existing discount, checkout, stock and payment-reference rules are preserved.

## Verification

Local browser checks passed 68 layout combinations across filled/empty carts, seven viewport sizes (320–1440px, including short landscape), both themes and each visible panel. Functional checks covered combined filters, empty results, reset, disabled products, quantity limits, selection/focus, discounts, exact payment, GCash controls and empty-cart checkout disabling. No sales were submitted by the checks.

All six existing discount browser scenarios and 36 modal layout/interaction scenarios passed. The changed PHP view passed syntax validation. Screenshots were reviewed for phone, tablet and desktop layouts.

After upload, verify Products, Cart and Payment on a phone and desktop. Search for a medicine, use category/availability filters, select and cancel a product, and check held sales and dark mode. Use your normal controlled test-sale procedure to confirm the server checkout and receipt flow on Hostinger. This task did not deploy to the live site or submit a live sale.
