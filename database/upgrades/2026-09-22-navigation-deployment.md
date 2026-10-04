# Sidebar and topbar update

Files-only update. No SQL import or database changes are required.

## Upload all six files together

- `app/Views/layouts/staff.php`
- `app/Views/layouts/sidebar.php` (new)
- `app/Views/layouts/topbar.php` (new)
- `public/assets/css/sidebar.css` (new)
- `public/assets/css/topbar.css` (new)
- `public/assets/js/topbar.js` (new)

Upload `app/` into the existing application directory. If the contents of `public/` are hosted directly in `public_html`, place the CSS files in `public_html/assets/css/` and the JavaScript file in `public_html/assets/js/`. Do not put the private application directory inside the public web root. Keep your existing `.env`, `index.php`, dependencies and database.

The staff layout requires both new PHP partials. Upload them before or alongside the changed layout to avoid a missing-view error. The new assets use a versioned URL; reload open application tabs after uploading.

## Changes

- Sidebar links grouped into daily work, inventory, business, administration and preferences, with role visibility preserved.
- Search matches page names and group names, with a clear button and empty state.
- Light/dark sidebar colors follow the current theme and accent. Active links use exact path segments, including sale correction under sales history.
- Scrollable navigation, 44px minimum touch controls, reduced-motion support, Escape dismissal and keyboard focus containment.
- Topbar has larger controls, current page/branch context, Philippine time, and a compact account menu with settings/sign-out links.
- Phones show a second row for the page title. Account and notification panels stay within the viewport and close on Escape or outside interaction.

## Validation

Browser checks covered 20 combinations of admin/cashier, five viewport sizes (320px phone through 1440px desktop, including short landscape), and light/dark mode. Checks included search, empty state, hidden menu items, focus wrapping/restoration, account and notification panels, theme switching, large text, reduced motion and horizontal overflow. PHP syntax and rendered role visibility checks passed.

After upload, check the menu, search, notifications, account dropdown and theme toggle on a phone and desktop. Confirm the expected pages are visible for both administrator and cashier accounts. Backend authorization and business calculations are unchanged.
