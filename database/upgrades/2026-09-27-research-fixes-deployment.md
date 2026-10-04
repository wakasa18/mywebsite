# Research alignment fixes: saved forecasts, branch lookup, login audit

Package: `build/hostinger-research-fixes-2026-09-27.zip`.

**No database migration or SQL import is required.** This update uses the existing nullable fields in `forecasting_data` and `activity_logs`. Their compatibility was checked against the local schema; the Hostinger database was not accessed.

## Changes

### Finding 2: saved sales forecasts

- Update Forecast saves all seven future revenue predictions using the same calculation as the sales chart, including daily, weekly or monthly target dates.
- Revenue rows have NULL product/quantity, their predicted revenue, and the target period start in the existing date field. `method_used` identifies the sales forecast, branch, training window, interval, alpha and beta. Weekly targets use ISO week dates, including year boundaries.
- The original product restock snapshots still save separately. They are labelled as monthly equivalents of a 30-day daily-demand outlook, not as the revenue chart's predictions.
- Matching records from the same day refresh without duplicate rows. Other dates, filter combinations and scheduled product snapshots remain separate.
- Both types save inside one transaction; a failed product save also rolls back the revenue points. MySQL branch-row locks serialize revenue saves.
- Revenue forecasts save even when no products are low on stock.
- Reports has a **Saved sales forecasts** button. It opens the saved tab with revenue rows selected by the list search. Reset the search to see product estimates too. The list shows the latest 100 records across branches.
- Existing historical product records are retained. Old revenue chart predictions cannot be reconstructed retrospectively from those product estimates; revenue history begins when this update is used.

### Finding 5: other-branch availability

- POS includes **Check other branches**, a read-only page searchable by product name or SKU.
- Results show branch name, address, contact number and available stock. Only active products/branches and sellable inventory are included; expired, deleted, inactive and zero-stock items are excluded.
- Cashiers retain their assigned selling branch and cart. This feature does not permit stock changes or sales against another branch.
- Searches are submitted explicitly; results are paginated in groups of 20.
- Results are alphabetical, not ranked by distance. Staff should confirm availability before referring a customer.

### Finding 8: authentication audit

- Successful login, failed credentials, missing required fields, inactive/invalid-role denial, rate-limit lockout, blocked submissions and authenticated logout generate activity records.
- Records contain a fixed event description, timestamp, validated request IP, and known account ID when available. Unknown/blocked submissions can have no linked account.
- Passwords, submitted usernames, request bodies and session tokens are never passed to the audit writer.
- Records appear in the existing **Activity Logs** page. Search `Authentication:` to find them.
- If the activity table cannot be written, a sanitized event falls back to the application error log so authentication does not fail solely because the audit insert failed. Restore table logging if fallback events appear.
- Past authentication events are not backfilled. This fixes authentication coverage; it does not assert that every unrelated application action is audited.

## Upload

This patch builds on the current September 27 project, including the shared modal manager, reports styles and staff layout. Back up the corresponding deployed files, then replace the package files in their matching locations. Deploy backend helpers before the updated controllers/views, preferably during a short maintenance window.

1. `app/Libraries/AuthenticationAudit.php` — new
2. `app/Libraries/SalesForecastSnapshot.php` — new
3. `app/Libraries/ReorderForecastService.php` — included current dependency
4. `app/Models/ForecastingDataModel.php`
5. `app/Filters/LoginThrottleFilter.php`
6. `app/Controllers/Auth.php`
7. `app/Controllers/Admin/ReportsController.php`
8. `app/Controllers/Cashier/SalesController.php`
9. `app/Config/Routes.php`
10. `app/Views/cashier/sales/branch_availability.php` — new
11. `app/Views/cashier/sales/index.php`
12. `app/Views/admin/reports/index.php`
13. `public/assets/js/reports.js`

If `public_html` contains the contents of `public/`, upload the JavaScript to `public_html/assets/js/reports.js`. PHP files belong in the existing application's `app/` folders. The reports script uses cache version `20260927-3`. Reload after deployment and ensure the new script was uploaded along with the view.

Do not upload the local test fixtures or the research paper. This package has not been uploaded to Hostinger.

## Validation and acceptance

- PHP syntax and JavaScript syntax checks passed.
- 31 business tests passed using isolated SQLite, including exact saved revenue/target dates for all three intervals, repeat saves, ISO week boundaries, atomic rollback, branch filtering/cart preservation, authentication outcomes, and throttle auditing. Additional refresh assertions confirmed changed revenue replaces matching saved values.
- The pre-existing migration test with the `loadForge() on null` setup error was excluded. This patch does not change migrations. SQLite checks do not establish real MySQL concurrency behavior.
- Eight responsive branch-lookup/saved-sales UI scenarios and 68 POS layouts passed, including light/dark themes and mobile sizes. Screenshots were reviewed.
- Twenty-four restock/saved-record dialog regression scenarios passed, covering search, tabs, empty states, scrolling and keyboard navigation.

After upload:

1. In Reports, select daily, weekly and monthly filters in turn and click Update Forecast. Check Saved sales forecasts for seven corresponding target periods and their chart values. Submit the same filters again and confirm matching records refresh. Saving with no low-stock products should still save revenue points.
2. As a cashier, open Check other branches. Search a known SKU stocked elsewhere, check its contact details, then return to POS and confirm the cart and branch remain intact.
3. Complete a login/logout and a failed login; inspect Activity Logs for Authentication events. Avoid intentionally triggering production lockout unless a test account/IP is available.

Application records were not modified by the development checks; test records used isolated fixtures. These user acceptance steps will create normal forecast/audit records when run on the deployed application.
