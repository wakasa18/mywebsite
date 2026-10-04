# Reports empty-chart overlap fix

The pale block overlapping the forecast filters came from the empty Best-Selling Products chart. Chart.js gives its canvas an inline display style, which overrode the HTML hidden attribute. The no-data message then occupied a second chart-height block outside the card.

The CSS now explicitly hides empty canvases and positions the no-data message within the chart wrapper. The stylesheet version is bumped to `20260923-2` to refresh cached copies.

Upload both files from `build/hostinger-reports-chart-fix-2026-09-23.zip`:

- `app/Views/admin/reports/index.php`
- `public/assets/css/reports.css`

Upload the view into the existing application directory. If public assets live directly under `public_html`, upload the stylesheet to `public_html/assets/css/reports.css`. Reload Reports after uploading. This patch requires the preceding Reports UI update; the complete `hostinger-reports-ui-2026-09-23.zip` has also been refreshed with the fix.

No database or controller changes. Local browser checks covered 36 combinations of populated, empty and mixed chart data, six screen widths and both themes. Regression assertions verify hidden canvases occupy no space and empty messages stay inside their chart bounds. The screenshot case (populated sales chart alongside empty product chart) was visually checked.
