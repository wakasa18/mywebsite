# Forecast history: 24 months

Package: `build/hostinger-forecast-24-months-2026-09-27.zip`.

Forecast filters now accept up to 24 months of historical sales, measured back from the selected end date. The server validation, browser date controls, and help text use the same limit. Saved manual forecasts and exports use the updated server validation. The future forecast horizon, normal report date filters, and dashboard trend controls are unchanged.

No database change or SQL import is required.

## Upload

Back up and replace:

- `app/Controllers/Admin/ReportsController.php`
- `app/Views/admin/reports/index.php`

This patch builds on the September 27 saved-forecast and restock-modal updates. Keep their service, route, CSS, and JavaScript files installed.

After uploading, reload Reports, select dates up to 24 months apart, and click Update Forecast. Changing fields alone still does not save.

Validation: both PHP files passed syntax checks. Server checks confirmed that 18- and 24-month ranges are accepted and a 25-month range is shortened with the correct message. Client date-script checks confirmed the 24-month minimum and unchanged normal report controls. No database records were changed during validation. This package has not been uploaded to Hostinger.
