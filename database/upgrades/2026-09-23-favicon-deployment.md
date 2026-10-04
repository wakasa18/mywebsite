# Browser tab icon fix

The application pages had no explicit favicon link, allowing browsers to keep using the old root `favicon.ico`. They now explicitly request the existing pharmacy logo from a new filename and versioned URL.

Upload these files from `build/hostinger-favicon-2026-09-23.zip`:

- `app/Views/layouts/staff.php`
- `app/Views/auth/login.php`
- `app/Views/cashier/sales/receipt.php`
- `app/Views/cashier/sales/refund_slip.php`
- `public/assets/images/pharxmaco-favicon.jpg`

Place views in the existing private application directory. If `public_html` contains the contents of `public/`, the image must be at `public_html/assets/images/pharxmaco-favicon.jpg`.

This patch assumes the current navigation/modal updates are already uploaded; the staff layout still requires their partials and CSS/JS files. No database changes are needed.

After uploading:

1. Open `https://pharxmaco.space/assets/images/pharxmaco-favicon.jpg?v=20260923`. It must show the pharmacy logo, not a 404 or login page.
2. Reload the application, then close and reopen its tab if needed.
3. If it still shows the old icon, view the page source and search for `pharxmaco-favicon.jpg`. If absent, the live PHP view is still an older copy. Check the upload location and clear Hostinger's website cache if enabled. If present and the image loads, check a private browser window to distinguish a browser cache issue.

The existing logo image was copied unchanged; no new logo was generated. This patch has not been uploaded to Hostinger by this task.
