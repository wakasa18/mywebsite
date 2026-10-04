# PDF and CSV format update

Files-only update. No SQL import, migration or database cleanup is required.

The update covers sales/forecast, expiry and stock-log exports. PDF reports share a purple-accented print style, clearer headings, summary cards, alternating table rows, wrapped text and repeated column headings. The sales PDF uses a dedicated cash-movement table with totals. CSV exports preserve the UTF-8 BOM, use standard quoted fields and CRLF records, label monetary columns in PHP, and write numeric decimal values with two places. CSV is plain text and does not carry PDF colors or fonts.

Upload these files, preserving their relative folders:

- `app/Controllers/Admin/ReportsController.php`
- `app/Controllers/StockLogsController.php`
- `app/Libraries/CsvExport.php` **new, required by both controllers**
- `app/Views/exports/pdf_styles.php` **new, required by all three PDF views**
- `app/Views/exports/cash_movements_pdf.php` **new, required by the sales PDF**
- `app/Views/admin/reports/reports_pdf.php`
- `app/Views/admin/reports/expiry_report_pdf.php`
- `app/Views/stock_logs/pdf.php`

The upload package also includes the previous duplicate-export fix's UI files:

- `app/Views/admin/reports/index.php`
- `public/assets/js/report-exports.js`

If public files are deployed directly into `public_html`, copy `public/assets/js/report-exports.js` into `public_html/assets/js/report-exports.js`. Upload `app/` into your existing private application directory. Do not extract the complete archive into the public web root blindly. Keep the current hosting `.env`, `index.php`, dependencies and writable data.

Reload the reports page after upload. Download one PDF and CSV from each report type and check the selected branch/date range. Export history should add one entry for each deliberate sales-report export. Existing history entries are not deleted.

Validation: 35 PHP tests with 20,432 assertions passed. Export-link JavaScript regression checks passed. Actual Dompdf output was rendered and visually checked, including a five-page stock log and empty sales sections. CSV tests cover numeric/negative amounts, formula protection, quotes, commas, multiline values and Unicode. Test PDFs contain synthetic data and are not for production upload.

This format update does not update Dompdf or CodeIgniter. The security patch recommendations in `HOSTINGER-DEPLOYMENT.md` still apply.
