# Dashboard interface update

Package: `build/hostinger-dashboard-2026-09-24.zip`.

No database migration, SQL import, or controller replacement is required. This update uses the existing dashboard data and the current shared staff layout.

## Upload

Back up and replace these files, uploading the stylesheet first:

- `public/assets/css/dashboard.css` — new dashboard stylesheet
- `app/Views/dashboard/index.php` — updated dashboard view

If Hostinger's `public_html` contains the contents of `public/`, upload the stylesheet to `public_html/assets/css/dashboard.css`. Upload the view to the existing application's `app/Views/dashboard/` directory. Reload the dashboard after uploading; its stylesheet URL includes version `20260924-1`.

## Changes

- Today's sales, transaction count, and active product count appear together, with net sales emphasized and yesterday clearly labeled as a full-day comparison.
- Inventory watch brings low-stock, near-expiry, and expired-stock counts together. Counts are not added together because categories can overlap.
- Stock and expiry detail lists expand on demand using a keyboard-accessible disclosure. All three lists and their existing report links remain available.
- Section shortcuts lead to today's overview, inventory watch, sales trends, branch summaries, and recent activity.
- Sales-trend totals change with the 30-day / 12-month selector. Product-chart copy clarifies that the same top ten revenue-ranked products are shown when switching to units.
- Smaller screens use labeled table cards with wrapping product names and invoices. Controls have larger touch targets and visible keyboard focus.
- Colors follow the current theme. Chart labels, tooltips, and trend accents respond to dark mode and accent changes; chart animation respects the reduced-motion setting.
- Empty sales periods show a message instead of an empty chart. If Chart.js cannot load, chart controls are disabled and explanatory messages appear while totals and tables remain available.

The existing nine metrics, inventory lists, branch summaries, recent sales, and activity records are retained. Sales/refund calculations, permissions, routes, database queries, and other pages are unchanged.

## Verification

Thirty local browser scenarios passed: populated, empty, and unavailable-chart states at 320, 390, 768, 1024, and 1440 pixels, each in light and dark modes. Checks covered page overflow, mobile table overflow, keyboard disclosure, chart switching, selected-state indicators, tooltip colors, and browser runtime errors.

Additional checks passed for section navigation beneath the fixed topbar, accent changes, and reduced-motion settings. Desktop/mobile screenshots of the overview, charts, and expanded stock lists were visually reviewed. PHP syntax and rendered JavaScript syntax checks passed.

Fixtures used synthetic data; this task did not change database records or upload files to Hostinger. After uploading, check the dashboard with your own data, expand the stock lists, switch chart periods, and toggle dark mode.
