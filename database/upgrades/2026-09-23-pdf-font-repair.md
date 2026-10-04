# Hostinger PDF font repair

Package: `build/hostinger-pdf-font-repair-2026-09-23.zip`. No database changes.

## What the error means

The screenshot shows Dompdf's font library receiving a missing `cmap` character-map table. It fails while reading font data, not while querying expiry records. A damaged/incomplete font upload is a likely cause; the screenshot alone cannot confirm the exact server-side cause.

This repair restores the locally verified bundled font assets and gives sales, expiry and stock-log PDF exports a dedicated runtime directory under `writable/cache/pdf-v1/`. Generated metrics and temporary files are created on Hostinger rather than written inside `vendor/`. Font subsetting, Unicode and the peso symbol remain enabled. No vendor PHP code or error reporting was modified.

## Upload and test

1. Back up the matching application files. Upload this ZIP through Hostinger File Manager and extract it in the **application root containing `app/`, `vendor/` and `writable/`**. Do not extract it into `public_html` unless that is already your application root. ZIP extraction preserves binary fonts; avoid ASCII-mode FTP transfers for `.ttf` files.
2. Replace the packaged controllers, `app/Libraries/PdfFactory.php`, and font assets. Include the PDF templates/partials in the archive because the current controllers use them. Existing settings and database remain unchanged.
3. Ensure PHP can write inside `writable/cache/`. The application creates `pdf-v1/fonts/` and `pdf-v1/tmp/` automatically. Use the normal Hostinger account ownership/permissions; do not make the entire vendor tree writable.
4. With SSH/Terminal available, run `php tools/check-pdf-fonts.php` in the application root. It compares SHA-256 hashes and parses the TrueType character maps. Expected: `PASS`. This check does not modify the database.
5. Retry expiry PDF export, then sales and stock-log PDFs. Check that peso amounts and accented names are readable.

Do not upload your local `writable/cache/pdf-v1/` directory or old generated `*.ufm.json` cache files. This archive excludes them. You do not need to delete the whole writable directory.

If font verification passes but the same error remains, check that the new `PdfFactory.php` and controllers are active, and provide the **full backtrace plus the newest `writable/logs/` error entry**. The next investigation would cover the deployed php-font-lib files and the exact failing font path. This local repair cannot confirm the live host's filesystem or dependencies.

After troubleshooting, restore `CI_ENVIRONMENT = production` in the live `.env`; the screenshot currently exposes the development exception page. This hides diagnostic details from visitors but is not itself the PDF fix.

## Validation

- PDF-specific PHPUnit checks: 2 tests, 29 assertions passed, including regular/bold/italic/bold-italic Unicode and peso mappings, writable paths and embedded Unicode font streams.
- Generated sales, expiry, stock-log, multi-page stock-log and empty-sales reports successfully using the new factory; the expiry PDF was rendered and visually checked.
- The broader unit suite reported one separate existing migration test setup error (`loadForge()` on null in `BusinessLogicTest`); it is not part of the PDF repair and was not changed.
- The archive contains the project's installed Dompdf font assets, not a Dompdf dependency upgrade. It has not been deployed or tested on the live Hostinger server by this task.

Reference: [Dompdf runtime directory requirements](https://github.com/dompdf/dompdf/wiki/Requirements).
