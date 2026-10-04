# Interface settings update

Package: `build/hostinger-interface-settings-2026-09-24.zip`.

No database migration or SQL import is required. Preferences continue to use the existing browser storage key and are shared by people using the same browser profile.

## Upload

Back up and replace these files, uploading the assets first:

- `public/assets/css/interface-settings.css` — new
- `public/assets/js/interface-settings.js` — new
- `app/Views/layouts/staff.php`
- `app/Views/admin/settings/index.php`

If `public_html` contains the contents of `public/`, upload the assets into `public_html/assets/css/` and `public_html/assets/js/`. Upload the PHP views into the existing application's `app/Views/` directory. Both assets use version `20260924-1` in their URLs.

Use the staff layout from this package alongside the current navigation assets and partials. Its settings changes apply saved preferences through the existing shared theme function, keep the topbar theme button synchronized, and handle preferences being cleared in another tab. Existing layout features are retained.

## Changes

- Separate sections for appearance, reading/spacing, and accessibility, with larger selection targets and visible keyboard focus.
- Theme thumbnails and named accent choices show selected states with a checkmark as well as color.
- A sample pharmacy inventory preview demonstrates theme, text size, spacing, contrast, larger controls, and scrollable table headings. It stays beside the form on desktop and can be expanded near the top on mobile.
- A save bar stays within reach, shows the number of unsaved changes, and offers Save preferences and Discard changes.
- Settings explicitly opt out of the shared automatic-filter script. Selecting an option changes only the preview until Save preferences is clicked.
- Returning a control to its saved value clears its dirty state. Use defaults loads a draft; saving applies it. Discard returns to the latest saved preferences.
- Failed browser-storage writes leave the selected draft intact and show an error instead of applying unsaved changes.
- Topbar and external preference updates preserve fields currently being edited while updating untouched fields. Browsers warn about navigating away with unsaved changes after user interaction.
- Existing saved choices remain supported; the Device theme follows the operating system's color mode.

## Validation

Twenty browser layout/workflow scenarios passed for admin and cashier views, at 320, 390, 768, 1024, and 1440 pixels in light and dark themes. Checks covered preview-only changes, reverting choices, all settings saved and restored after reload, defaults/discard, larger text/spacing without page overflow, and mobile preview behavior.

Additional checks passed for blocked storage and retry, topbar synchronization, externally updated preferences, device theme changes, keyboard radio/switch operation, focus indicators, unsaved-navigation warnings, and canceling navigation without losing edits. Desktop/mobile previews and accessibility controls were visually reviewed. Changed PHP and JavaScript files passed syntax checks.

The checks used synthetic pages and a temporary browser profile. No application database records or user browser preferences were changed. This package has not been uploaded to Hostinger.

After upload, open Interface settings, preview a change, save, then reload. Check that the topbar theme control matches and that Discard changes and Use defaults behave as described.
