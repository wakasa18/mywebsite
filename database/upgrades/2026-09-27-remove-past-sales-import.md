# Remove Import Past Sales

Package: `build/hostinger-remove-past-sales-import-2026-09-27.zip`.

Removed Import Past Sales from the sidebar and Sales History actions. Removed all six import routes, including upload preview, confirmation, cancellation, template download, and reference download. Auto-routing remains disabled, so the import controller is no longer accessible through the application's routes.

Previously imported sales, report history, inventory, and forecasting data are preserved. No database change or SQL import is required.

## Upload

Back up and replace these files in the application's existing directories:

1. `app/Config/Routes.php`
2. `app/Views/layouts/sidebar.php`
3. `app/Views/cashier/sales/history.php`

This patch is based on the current project, including the earlier September 27 forecast-saving update. Keep those backend files installed.

After upload, reload the sidebar and Sales History. The import links should be gone; old `/admin/sales-import` URLs should no longer open the import page. If route caching is enabled on the server, clear the application route cache using your existing deployment process.

All three PHP files passed syntax checks. The application's route listing contains no sales-import endpoints and retains Sales History and Update Forecast. No sales records were changed, and this package has not been uploaded to Hostinger.
