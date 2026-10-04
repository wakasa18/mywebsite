# Redirect signed-in users away from login

Package: `build/hostinger-login-redirect-2026-09-30.zip`.

When a signed-in user visits `/login` or the site's root URL, the application validates the existing session against the current account and redirects:

- Admin: Dashboard (`dashboard`).
- Cashier: Point of Sale (`cashier/sales`), because the dashboard is admin-only.

Guests still see the login form. Invalid or revoked sessions are cleared by the existing account-session validation and show the form without a redirect loop. A valid session's cart remains intact. This change handles visits to the login page; the existing login submission, throttling and audit behavior is unchanged.

## Upload

Back up and replace `app/Controllers/Auth.php` with the file in the package. This uses the existing `app/Libraries/AccountSession.php` already required by the application's authentication filters.

No database change, SQL import or asset upload is required. The package has not been uploaded to Hostinger.

## Verification

PHP syntax passed. Six focused authentication/session tests passed with 37 assertions, including admin/cashier destinations, guest login rendering, inactive/password-changed sessions, cart preservation and existing audit behavior. Tests used an isolated in-memory database.

After upload, sign in as admin and open `/login` in a new tab: it should redirect to Dashboard. Check the site root too. A signed-in cashier should go to POS. After logout, `/login` should show the form normally.
