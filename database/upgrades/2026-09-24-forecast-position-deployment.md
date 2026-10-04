# Keep the forecast view position after updating

Package: `build/hostinger-forecast-position-2026-09-24.zip`.

This includes the manual forecast filter change and preserves the forecast section's position when Update Forecast reloads the page. No database changes are required.

## Upload

Back up and replace these files, uploading the JavaScript first:

- `public/assets/js/reports.js`
- `app/Views/admin/reports/index.php`

If Hostinger's `public_html` contains the contents of `public/`, upload the JavaScript to `public_html/assets/js/reports.js`. Upload the view into the existing application's `app/Views/admin/reports/` directory. Reload the Reports page to load script version `20260924-2`.

## Behavior

Forecast selections wait for Update Forecast. Submitting saves the forecast form's position within the viewport in this browser tab, then restores it after the matching report has loaded. This also accounts for content above the forecast changing height. The saved position is used once, expires after five minutes, and is matched to the submitted URL and filter values. Normal browser refresh and back/forward scroll restoration remain enabled.

Saved Forecast Records still come from the daily `php spark forecast:reorder` job. See `2026-09-24-manual-forecast-deployment.md` for scheduling and verification details.

## Validation

PHP and JavaScript syntax checks passed. Browser checks passed at 390px and 1440px: changing filters does not submit, clicking Update Forecast performs a real GET navigation and restores the forecast position, and a subsequent browser refresh retains it. The existing four manual forecast checks also passed.

After uploading, scroll to the forecast filters, change a date or interval, and click Update Forecast. Confirm the view stays in place, then refresh once more. This package has not been uploaded to Hostinger by this task.
