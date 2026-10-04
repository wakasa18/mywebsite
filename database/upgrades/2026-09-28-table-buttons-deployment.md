# Table action buttons

Package: `build/hostinger-table-buttons-2026-09-28.zip`.

No database change or SQL import is required.

## Changes

- Shared table button styling with consistent spacing, borders and height.
- Small line icons alongside visible labels for edit, view, print, password, stock save, refund, activate/deactivate, restore and trash/delete actions.
- Theme-aware colors distinguish ordinary, positive and destructive actions. Bare buttons such as Password now have a defined background and border.
- Action groups wrap on desktop and use a responsive grid in existing mobile record cards. Mobile/touch targets are at least 44 pixels high; desktop targets are at least 36 pixels high.
- Visible keyboard focus and support for large controls, larger text, high contrast and reduced motion.
- Existing links, form methods, CSRF fields, permissions, confirmation dialogs and busy labels are retained. Icons are hidden from screen readers, and groups receive a contextual accessible label when a record title is available.

This uses the existing staff layout and shared workspace enhancement script. It applies to table buttons across the app, including products, categories, suppliers, users, branches, discounts, trash pages and sales history. Standalone print/PDF receipts are unaffected.

## Upload

Back up and replace these files in the matching application directories:

1. `public/assets/css/table-actions.css` — new
2. `public/assets/js/workspace.js`
3. `app/Views/layouts/staff.php`
4. `app/Views/cashier/sales/history.php`
5. `app/Views/admin/suppliers/index.php`

Where Hostinger's `public_html` contains the contents of `public/`, put the assets under `public_html/assets/`. PHP views belong in the existing `app/Views/` folders. Upload the assets before the layout. The new stylesheet and workspace script use cache version `20260928`.

This package includes the current shared staff layout and requires the other existing workspace assets already installed in the project. It has not been uploaded to Hostinger.

## Verification

PHP and JavaScript syntax checks passed. Browser checks covered 56 combinations of seven real table views (users, sales history, products, product trash, categories, suppliers and branches), four widths (320, 390, 768 and 1440 pixels), and light/dark themes.

Checks covered button/group overflow, minimum target heights, visible labels, decorative icons, preserved POST/hidden fields, cancellation of confirmation dialogs, keyboard navigation, and larger-text/large-control/high-contrast settings. Phone and desktop screenshots were reviewed. Tests used local fixtures; no business actions were submitted.

After upload, reload the page and check a table on desktop and phone. Confirm Edit/View links still lead to the right record, and cancel a destructive confirmation to verify the dialog remains in place.
