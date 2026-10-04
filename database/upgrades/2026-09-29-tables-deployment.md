# Workspace table improvements

Package: `build/hostinger-tables-2026-09-29.zip`.

No database change or SQL import is required.

## Changes

- Clearer headers with readable capitalization, consistent padding and stronger separators.
- Subtle alternating rows and row highlighting while hovering or using keyboard controls.
- Right-aligned recognized quantity and amount columns in standard record tables, with aligned numeric digits. Phone cards retain left-aligned labeled fields.
- Theme-aware scrollbars and keyboard focus for horizontally scrollable tables.
- Refined mobile record cards with wider space for addresses, email, remarks and activity text.
- Supplier lists and product/category/supplier trash pages now use mobile record cards. Removed faded trash-row text to improve contrast.
- Existing table action controls, confirmations, filters, pagination and sticky-header preference are preserved. Shared changes apply to workspace tables in table wrappers; standalone PDF/receipt templates are unchanged.

## Upload

Back up and replace these files in the existing application:

1. `public/assets/css/tables.css` — new
2. `public/assets/css/table-actions.css` — included dependency
3. `public/assets/js/workspace.js`
4. `app/Views/layouts/staff.php`
5. `app/Views/admin/suppliers/index.php`
6. `app/Views/admin/suppliers/trash.php`
7. `app/Views/products/trash.php`
8. `app/Views/categories/trash.php`

If Hostinger's `public_html` contains the contents of `public/`, place assets in `public_html/assets/`. Upload the assets before the views. The new stylesheet and workspace script use cache version `20260929-1`. Other existing workspace assets are required.

This package has not been uploaded to Hostinger.

## Verification

PHP and JavaScript syntax checks passed. Local browser checks passed 56 representative table layouts, 24 trash-table layouts, 36 report layouts and 30 dashboard scenarios. These cover phone/tablet/desktop widths, light/dark themes, larger controls/text, keyboard use, and preserved form/confirmation behavior. Desktop and phone table screenshots were reviewed. No production data or business actions were submitted.

After upload, check Products, Suppliers, Trash, Reports and Dashboard in both themes. Confirm filters and pagination still work, and confirm the mobile cards display all fields and actions.
