# Restock Suggestions modal

Package: `build/hostinger-restock-modal-2026-09-27.zip`.

No database migration or SQL import is required. The package includes the September 27 Update Forecast saving feature as well as the redesigned modal.

## Changes

- Separate Suggestions and Saved records tabs with record counts.
- Search the current list by product, SKU, branch, or visible record details.
- Filter current suggestions by Urgent, Order soon, Monitor, or Review manually.
- Fixed header, controls, and footer surround a single scrollable list. Desktop tables keep their column headings while scrolling; phone/tablet rows become readable cards.
- Suggested order quantities stand out. Forecasts without a reliable demand estimate say Review manually instead of implying that zero is a confident order recommendation.
- The header identifies the current suggestion branch and sales window. Saved history clearly states that it includes records across all branches.
- Close and Forecast filters remain available at the bottom. Forecast filters closes the modal and focuses the forecast form without submitting it.
- Keyboard arrow keys, Home, and End switch tabs. Existing Escape, focus trapping, background isolation, and focus restoration remain supported.
- Styling follows the existing light/dark theme and accent.

Search only filters loaded records. The suggestions list retains the existing display limit, while saved history shows the latest 100 records. Forecast calculations and save behavior are unchanged by modal controls.

## Upload

Back up and replace the included files. Deploy the backend files before the reports view if the earlier save-forecast update has not been installed:

1. `app/Libraries/ReorderForecastService.php`
2. `app/Controllers/Admin/ReportsController.php`
3. `app/Config/Routes.php`
4. `app/Models/ForecastingDataModel.php`
5. `app/Commands/GenerateReorderForecast.php`
6. `public/assets/css/reports.css`
7. `public/assets/js/reports.js`
8. `app/Views/admin/reports/index.php`

If `public_html` contains the contents of `public/`, put the assets in `public_html/assets/css/` and `public_html/assets/js/`. Put PHP files in the application's existing `app/` directories. The reports assets use version `20260927-2`.

This view uses the existing shared modal manager and stylesheet loaded by the current staff layout (`assets/js/modals.js` and `assets/css/modals.css`). Keep those installed.

## Validation

PHP and JavaScript syntax checks passed. Twenty-four browser scenarios passed with populated/empty lists at phone, tablet, desktop, and landscape sizes in light/dark modes. Checks covered tab navigation, SKU search, priority filtering, no-match/reset states, viewport fit, scrolling, focus trapping, Escape, and returning to forecast filters without submitting. Mobile and desktop screenshots were reviewed.

Four manual forecast submission scenarios and four POST/redirect/GET position scenarios also passed. Tests used local fixtures; no application records were changed. The package has not been uploaded to Hostinger.

After uploading, open Reports > Restock Suggestions. Try both tabs, search a product, filter priorities, and check the modal on your phone. Use Forecast filters to return to the form; click Update Forecast only when ready to save new estimates.
