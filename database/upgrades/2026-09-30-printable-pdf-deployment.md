# Printable PDF exports

Package: `build/hostinger-printable-pdf-2026-09-30.zip`.

All three PDF export routes are covered: Sales and Forecast Report, Expiry Report, and Stock Logs. They use **A4 landscape (297 × 210 mm)** to accommodate their wide tables.

## Improvements

- Explicit print sizes in points: 10 pt body, 9 pt table text/headings, 9 pt metadata, and larger section titles.
- Printer-safe margins: 12 mm at the top/sides, 18 mm at the bottom for footer clearance.
- More readable footer text and page numbers, positioned inside the printable area.
- Repeated table headings on continuation pages, wrapped text and intact ordinary table rows.
- Stock-log column widths adjusted for product names, remarks and readable dates.
- Removed the forced page break before the forecast section so pagination can flow naturally.
- Date history summaries use readable text size instead of the former 9 px override.

Long reports use as many pages as needed. They are not shrunk to fit the whole report on one sheet. Existing filters, calculations, CSV exports and thermal receipt layouts are unchanged.

## Upload

Back up and replace these five files:

1. `app/Views/exports/pdf_styles.php`
2. `app/Views/admin/reports/reports_pdf.php`
3. `app/Views/stock_logs/pdf.php`
4. `app/Controllers/Admin/ReportsController.php`
5. `app/Controllers/StockLogsController.php`

This package assumes the preceding readable-dates update and existing PDF/font dependencies are installed. No database changes are required. It has not been uploaded to Hostinger.

## Printing

Choose **A4**, **Landscape**, and **Actual size / 100%** in the PDF viewer. If the printer uses a different paper size, use Fit to printable area. Disable extra browser headers/footers if offered; the report includes its own page numbers.

## Verification

All five changed PHP files passed syntax checks. Five fixture PDFs rendered in separate PHP processes, matching separate export requests: populated sales, expiry, stock logs, 65-row stock logs, and empty sales. Every canvas measured A4 landscape (approximately 841.89 × 595.28 points).

Rasterized sales, expiry and stock-log pages were reviewed, including continuation and final pages of the long stock report. Text, date wrapping, repeated headings and footer clearance were checked. Fixture data only; no production exports or database writes. Physical printer output was not tested.
