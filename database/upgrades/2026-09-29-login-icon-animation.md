# Animated login sign-in icon

Package: `build/hostinger-login-icon-animation-2026-09-29.zip`.

The Staff sign in badge gently fades and scales into place. Its arrow moves toward the door twice, then settles. The sequence ends within 2.5 seconds and does not move form fields. Existing device and saved reduced-motion preferences disable the animations.

No database changes are required. Login behavior is unchanged.

Upload `public/assets/css/login.css` first, then `app/Views/auth/login.php`. If Hostinger uses `public_html` for the contents of `public/`, put the stylesheet under `public_html/assets/css/`. Its cache version is `20260929-2`.

This package includes the preceding Staff sign in label/icon change. It has not been uploaded to Hostinger. After upload, reload the login page to see the short animation; enable reduced motion to verify the icon stays still.
