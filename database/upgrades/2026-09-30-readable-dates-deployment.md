# Readable dates and times

Package: `build/hostinger-readable-dates-2026-09-30.zip`.

No database changes or SQL imports are required.

## Display formats

| Value | Display |
| --- | --- |
| `2026-09-30` | September 30, 2026 |
| `2026-09-30 14:05:00` | September 30, 2026 at 2:05 PM |
| `2026-09` | September 2026 |
| `2026-W40` | Week of September 28, 2026 |

The shared `App\Libraries\DisplayDate` formatter uses the application timezone (Asia/Manila). It validates SQL dates before formatting; missing or invalid values show an em dash instead of an invented date. Dates remain specific calendar dates rather than changing relative labels such as yesterday.

The update covers sales history, sale details, completed sales, receipt/refund pages, stock/activity logs, branches/users/categories, trash records, stock transfers, inventory expiry, discount dates, backup history, dashboard dates/chart labels, report periods, saved forecast dates, cash movements and report exports. CSV report date columns now contain readable text with month names; the existing CSV writer quotes commas correctly. Filenames, stored records, filter values and date-input values keep their machine-readable formats. Calculations and forecasting keys are unchanged.

## Upload

Back up matching files before replacing them. Upload **`app/Libraries/DisplayDate.php` first**, then upload the remaining controllers and views from this package to their matching application paths. The full runtime file list is included in `2026-09-30-readable-dates-files.txt` beside this document.

These PHP files belong under the existing `app/` directory, not under `public_html/assets/`. No new CSS/JavaScript assets are required. This package assumes previous application updates and their assets are already installed. It has not been uploaded to Hostinger.

## Verification

- All changed PHP files passed syntax checks.
- Date formatter tests passed: 3 tests, 23 assertions covering month names, AM/PM, leap dates, invalid values, ISO week/year boundaries and timezone conversion. Existing CSV writer tests passed: 2 tests, 3 assertions.
- Browser fixtures passed 56 table layouts, 36 report layouts, 30 dashboard scenarios and 66 receipt screen/print checks. Report filters retained their date values and export URLs.
- Sales, expiry, stock-log, multi-page stock-log and empty report PDFs rendered. Sales/expiry/stock PDF pages and mobile history were visually reviewed for date wrapping.

Tests used fixtures; no live sales, refunds, exports or production database writes were performed.

After uploading, check Sales History, activity logs, expiry dates, Reports and a printed receipt. Confirm date filters still work, saved forecasts show the correct period, and PDF/CSV exports show month names.
