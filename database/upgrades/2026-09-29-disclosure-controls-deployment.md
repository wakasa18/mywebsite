# Clear dashboard disclosure control

Package: `build/hostinger-disclosure-controls-2026-09-29.zip`.

No database change is required.

The small circular plus in the dashboard stock-and-expiry section is replaced with a labeled Show lists / Hide lists control and a chevron. It uses the existing theme colors, a minimum 44-pixel height, wrapping on narrow screens and visible keyboard focus. The entire summary remains clickable. Native details behavior works without JavaScript; the label and chevron follow its open state using CSS.

The source review found this circular plus disclosure on the dashboard. Other plus signs are labeled add actions, pharmacy decoration, or quantity steppers with accessible labels; they are not the same disclosure control.

## Upload

Replace these files in your existing installation:

1. `public/assets/css/dashboard.css`
2. `app/Views/dashboard/index.php`

If Hostinger's `public_html` holds the contents of `public/`, upload the stylesheet to `public_html/assets/css/dashboard.css`. Upload the stylesheet before the view. Its cache version is now `20260929-1`.

PHP syntax passed. Browser checks passed 30 dashboard scenarios across five widths, light/dark themes and populated/empty/unavailable-chart fixtures, including keyboard expansion and mobile overflow. No production data was changed. The package has not been uploaded to Hostinger.

After upload, open the dashboard, expand and collapse the stock-and-expiry lists, and confirm the label changes between Show lists and Hide lists.
