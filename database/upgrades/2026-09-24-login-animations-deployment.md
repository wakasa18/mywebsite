# Login animations

Package: `build/hostinger-login-animations-2026-09-24.zip`.

This includes the redesigned login page and its animations. No database changes are required.

## Upload

Back up and upload these files, placing assets first and the PHP view last:

- `public/assets/css/login.css`
- `public/assets/js/login.js`
- `app/Views/auth/login.php`

If your public directory is deployed as Hostinger `public_html`, place the assets under `public_html/assets/`. Place the view under the existing application's `app/Views/auth/` directory. Keep the existing pharmacy logo and favicon in `assets/images/`.

Asset URLs now use version `20260924-2` so browsers fetch the updated styles.

## Animation behavior

- The header, welcome copy, and form enter in a short staggered sequence.
- The inventory card, medicine bottle, and capsule gently float into place. Automatic decorative motion finishes within five seconds.
- Desktop artwork reacts to hover; the sign-in arrow and input icons respond to interaction.
- Submitting shows a spinner alongside the existing signing-in message. Authentication is unchanged, with no artificial delay.
- Both the device reduced-motion setting and the saved interface preference disable animations and hover movement. Submission status remains visible as text.
- Focusing the form stops the container entrance so keyboard targets stay in place.

## Validation

PHP syntax and 30 local browser layout scenarios passed across phone, tablet, and desktop widths in light and dark themes. Checks covered existing form interactions, animated artwork movement, loading spinner, automatic motion ending, and both reduced-motion preferences. No real login request was submitted and no database records were changed.

After uploading, reload the login page and check the entrance, hover effects, and password visibility. Enable Reduce motion in Interface settings or your device accessibility settings and confirm that the login page stays still. Sign in normally using an existing account.
