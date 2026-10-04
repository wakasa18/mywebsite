# Creative login page

Package: `build/hostinger-login-2026-09-24.zip`.

No SQL import, database migration, or environment change is required.

## Upload

Back up and replace these files. Upload the assets first, then the PHP view:

- `public/assets/css/login.css` (new)
- `public/assets/js/login.js` (new)
- `app/Views/auth/login.php`

If your Hostinger `public_html` contains the contents of `public/`, place the CSS and JavaScript under `public_html/assets/css/` and `public_html/assets/js/`. Place the PHP view in the application's existing `app/Views/auth/` directory. Do not move the application or overwrite `.env`.

The view uses asset version `20260924-1` and reuses the existing pharmacy logo and favicon under `assets/images/`.

## Changes

- Cobalt blue pharmacy illustration, mint accents, and a distinct welcome panel.
- Sign-in form appears first on phones; desktop has a split illustration/form layout.
- Light and dark modes follow the saved interface theme or device preference. The theme button preserves other saved interface preferences.
- Larger fields and touch targets, visible keyboard focus, larger text preferences, high contrast, and reduced motion support.
- Password visibility control, Caps Lock notice, clearer errors, and administrator help.
- Submission feedback prevents repeated form submissions and resets when returning with browser Back.

The existing POST route, CSRF field, required credentials, same-host/subfolder form action, and authentication controller remain in use. Credentials are not saved in browser storage by this page.

## Validation

PHP and JavaScript syntax checks passed. Thirty browser layout scenarios passed across 320, 390, 768, 1024, and 1440 pixel widths in light/dark themes, including normal, error, and success states. Additional checks covered larger text, landscape layout, required fields, escaped username recovery, subfolder actions, password visibility, Caps Lock, submission feedback, browser Back recovery, and blocked preference storage. Desktop and mobile screenshots were visually reviewed.

Checks used locally rendered fixtures; no real login requests or database changes were made. This package has not been uploaded to Hostinger.

After upload, reload the login page, test Show/Hide and theme switching, and sign in with an existing account. Confirm an incorrect password shows the normal error and a valid login reaches the correct dashboard or POS page.
