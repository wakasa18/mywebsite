# Cashier Add / Remove stock

Package: `build/hostinger-cashier-stock-actions-2026-10-01.zip`.

The cashier Products table now shows the current quantity, an Add stock / Remove stock selector, a units input, a required reason/reference and the resulting balance. For example, current stock 30 with Remove stock 5 previews 25 units after saving. The quantity entered is the number of units to add or remove, not the new total.

The previous Set count / Receive units controls and browser reason prompt are replaced in this cashier interface. Existing legacy endpoint modes remain supported for compatibility. Admin product editing is unchanged.

## Behavior

- Cashiers can change only their assigned branch inventory.
- Units must be positive whole numbers, up to 1,000,000 per action. Removing more than the available quantity is rejected; removing all units is allowed.
- A reason of 3–200 characters is required. Stock logs retain the movement quantity, previous balance, resulting balance and reason; the existing activity logging remains in place.
- The server locks the inventory row and rejects an Add/Remove request if the current quantity no longer matches the displayed balance. Refresh the list and review the new balance before trying again.
- New Add/Remove forms use a one-use session token alongside the existing CSRF protection. Repeated submissions or older forms invalidated by reopening the list cannot apply the same adjustment again.
- Stock adjustments keep the current expiration date and prices. Use the Expiry Report's Replace stock workflow when receiving replacements with a new expiration date.

## Upload

Back up and replace:

1. `app/Controllers/Products.php`
2. `app/Views/products/index.php`

Upload the controller before the view, then refresh the Products page. No database migration or SQL import is required. This package has not been uploaded to Hostinger.

## Verification

Both PHP files passed syntax checks. Four focused tests passed (26 assertions), covering add/remove arithmetic, master-stock synchronization, movement logging, repeated submissions, over-removal, zero balance, stale counts, required reasons/tokens, branch restrictions and legacy count/receive compatibility.

Eight local browser layout cases passed at 320, 390, 768 and 1440 px in light/dark themes. Checks covered previews, invalid removal quantities, required fields and CSRF/token fields. Mobile output was visually reviewed. Tests used an in-memory database and rendered fixtures; no production stock was changed.
