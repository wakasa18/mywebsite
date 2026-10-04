# Reports page interface update

Package: `build/hostinger-reports-ui-2026-09-23.zip`.

No SQL import, database migration, or controller change is required. The existing sales, refund, forecast and export calculations are preserved.

## Upload

Back up and replace these files:

- `app/Views/admin/reports/index.php`
- `app/Views/admin/reports/cash_movements.php`
- `public/assets/css/reports.css` (new)
- `public/assets/js/reports.js` (new)

Upload views into the existing private application directory. If `public_html` contains the contents of `public/`, upload the new assets to `public_html/assets/css/reports.css` and `public_html/assets/js/reports.js`. Upload assets before views, then reload the application tab. Asset URLs include a version query.

This patch assumes the current navigation and modal updates are installed, including the staff layout, `workspace.js`, `modals.css` and `modals.js`. It does not replace the PDF font repair or any controllers; retain those fixes.

## Changes

- Section links for filters/exports, overview, sales trends, forecast, sales details and export history.
- Quick report date ranges: today, last seven days, last thirty days and this month. Ranges are inclusive and use the application's current date; press Apply report filters to load their results.
- A pending-changes notice explains that exports still use the currently applied dates until filters are submitted. Report and forecast forms retain their independent date ranges.
- Clearer financial summary cards and separate receipts, refund payouts, net movement and cash movement totals.
- Report tables become labeled cards on phones. Long product names, invoice numbers and date ranges wrap within the available width.
- Larger filter/export controls, associated input labels, consistent light/dark styling and improved chart labels.
- Empty chart messages, reduced-motion support at chart initialization, and tooltip colors that update with dark mode. The existing chart-library failure message and CSV/PDF export links remain available.

## Validation

The package includes the empty-chart overlap fix (`reports.css?v=20260923-2`). Additional regression checks cover 36 combinations of populated, empty and mixed chart data; empty canvases no longer take up layout space and no-data messages remain within the chart card.

Browser checks covered 24 layouts: populated/empty reports, six widths from 320px to 1440px, and both themes. Charts were checked with the same Chart.js 4.4.0 version used by the page. Tests covered page/table overflow, touch targets, date presets, pending changes, preservation of applied export URLs and opening/closing restock suggestions. No forms or exports were submitted during the UI checks.

The existing 36 modal layout and interaction checks passed. Changed PHP views and the new JavaScript passed syntax checks. Screenshots were reviewed at phone and desktop sizes.

After upload, apply a date preset, switch branches, review the sales/forecast sections, open Restock Suggestions, and check mobile/dark mode. Confirm the date ranges displayed above match those you intend to export. The update has not been deployed to Hostinger by this task.
