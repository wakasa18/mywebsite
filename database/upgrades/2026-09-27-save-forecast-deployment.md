# Save forecasts from Update Forecast

Package: `build/hostinger-save-forecast-2026-09-27.zip`.

No SQL import or database schema change is needed. The existing `forecasting_data` table is used. This update supersedes the preview-only behavior described in the September 24 manual-forecast guide.

## Behavior

- Update Forecast applies the submitted filters and saves product estimates under Saved Forecast Records in the Restock Suggestions dialog.
- Changing a field alone does not save. Report visits, browser refreshes, and exports do not create saved forecasts.
- Each manual record identifies its branch, historical sales dates, and selected sales-chart interval. All Branches combines low-stock demand across active branches by product.
- Estimates in this table cover the displayed forecast month, as before; Daily/Weekly/Monthly controls the sales chart, not the saved product quantity's unit.
- Repeating the same filters on the same day updates the matching manual rows. A new day or different filters creates separate rows. Scheduled and previous-day records remain intact.
- Saving includes every matching low-stock product, even when the suggestion display is limited. Zero-demand products can have a saved zero estimate. If there are no matching active low-stock products, an explanatory message appears and no rows are created.
- Success, invalid-filter, and save-failure messages appear above the forecast filters. Writes are transactional. Invalid filters must be reviewed before saving under corrected values.
- The admin-only action uses a CSRF-protected POST followed by a 303 redirect. The selected filters and viewport position are retained; refreshing the result does not resubmit the save.

The existing `method_used` field stores the basis for manual rows, avoiding new columns. The local schema was verified as `VARCHAR(100)` with InnoDB tables. Product row locks serialize simultaneous manual saves on MySQL; the database checks below do not simulate real concurrent Hostinger requests.

## Upload

Back up and replace these files, deploying the service/controller/routes before the view:

1. `app/Libraries/ReorderForecastService.php`
2. `app/Controllers/Admin/ReportsController.php`
3. `app/Config/Routes.php`
4. `app/Models/ForecastingDataModel.php`
5. `app/Commands/GenerateReorderForecast.php`
6. `public/assets/js/reports.js`
7. `app/Views/admin/reports/index.php`

If `public_html` contains the contents of `public/`, upload the JavaScript to `public_html/assets/js/reports.js`. Keep the PHP files in the existing application's `app/` directories. The view requests JavaScript version `20260927-1`.

Keep the existing reports stylesheet, layout, and database. Scheduled `php spark forecast:reorder` jobs may continue as before.

## Verify after upload

1. Open Reports and choose a branch with active low-stock products.
2. Select forecast dates, click Update Forecast, and confirm the save message.
3. Open Restock Suggestions and inspect Saved Forecast Records. Check the branch and sales dates in Source / sales used.
4. Repeat with the same filters: today's matching rows refresh without another copy. Reload the page: no extra rows appear.
5. Change the branch or historical dates and update again: the saved basis and estimates should reflect that selection.

Local validation: 8 forecast/database/controller tests passed with 49 assertions, including same-day updates, scoped estimates, preservation of scheduled history, all-product saving, empty results, invalid filters, and transaction rollback. Four manual-submission and four POST/redirect/GET position browser scenarios passed, plus 36 reports layout checks. PHP/JavaScript syntax passed. Tests used isolated SQLite data and local browser fixtures; no application forecast records were written. This package has not been uploaded to Hostinger.
