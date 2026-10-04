# Receipts and refund payouts scrollbar

Package: `build/hostinger-cash-table-scroll-2026-09-24.zip`.

Upload these files, backing up the existing copies and uploading CSS first:

- `public/assets/css/reports.css`
- `app/Views/admin/reports/cash_movements.php`
- `app/Views/admin/reports/index.php`

If the public directory is mapped to `public_html`, place the CSS at `public_html/assets/css/reports.css`. PHP views go in the existing application's `app/Views/admin/reports/` folder. Reload Reports to load CSS version `20260924-1`.

The receipts/refund table now scrolls independently within a maximum height of 480px or 60% of the viewport, whichever is smaller. Its desktop column headings stay visible; mobile cards keep their labels. Summary totals remain above the scrollable region. The region supports keyboard scrolling and theme-aware scrollbar colors. Short lists keep their natural height, and printing expands the list to include every row.

Browser checks passed on mobile and desktop in both themes, including keyboard scrolling, fixed headings, the existing sticky-table setting, and print expansion. PHP syntax checks passed. No database changes are required; this patch has not been uploaded to Hostinger.
