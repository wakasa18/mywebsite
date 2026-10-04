# Quantity corrections for discounted sales

Package: `build/hostinger-discounted-sale-corrections-2026-10-04.zip`.

This is an application update for the current project. No database migration, SQL import or replacement database export is required.

## What changes

Administrators can correct quantities on completed discounted sales without refunds or exchange links. The page recalculates the linked discount, total and payment difference as quantities change. It uses the recorded selling and cost prices rather than current catalog prices. Branch and master stock, item discount allocations, profit, sale totals and the activity log are updated together in a transaction.

Percentage discounts, fixed discounts, caps, minimum purchases and product/category eligibility are applied to the corrected quantities. Discounts are allocated to eligible items in exact centavos. If the eligible subtotal falls below the minimum, the discount becomes zero and the page explains why. A completed sale must retain at least one item.

Example: Zinc at PHP 100 and Tempra at PHP 120 with a qualifying 20% discount originally total PHP 176. Increasing Zinc from one to two makes the subtotal PHP 320, discount PHP 64 and final total PHP 256. The administrator must collect and confirm the additional PHP 80 before saving.

## Payment confirmation

The difference is calculated from the old final total to the corrected final total. The page displays either the amount to collect or the amount to pay back. Saving a monetary change requires confirmation of that exact amount. Changing quantities, method or reference clears the confirmation; GCash and card differences require a reference.

Previously returned cash change does not count as extra payment. After confirmation, recorded tender changes by the settled difference and original change is retained. The audit records old/new totals, discount, tender, quantity changes, payment direction, method, reference and reason. Replaying the original submission fails its sale revision check and does not adjust stock twice.

This corrects an existing sale: reports use its corrected values on the original sale date. The confirmation records an administrator's acknowledgement of settlement; it does not charge a payment provider. Actual customer returns and product swaps use Refund or Exchange Product.

## Older discount records

Older sales do not have immutable discount-rule snapshots. This update uses the linked discount's saved settings only when they reproduce the recorded gross total, discount, final total and every item's discount allocation. The rule is checked again during saving, and a changed preview requires a new review.

An absent, invalid or incompatible discount rule leaves quantity inputs locked with an explanation. Notes and payment method/reference can still be corrected without repricing the sale. Historical promotion terms cannot be fully reconstructed when an edited rule happens to produce the same old amounts; the current linked settings are the calculation basis for these older records. Inactive, expired or retained deleted discounts can be used for an existing sale if their settings reproduce its recorded amounts.

## Upload to Hostinger

Back up and upload all three PHP files together, keeping their project paths:

1. `app/Libraries/SaleCorrectionPricing.php` — new file; upload it as well as replacing the existing files.
2. `app/Controllers/Admin/SaleCorrection.php`
3. `app/Views/admin/sale_correction/edit.php`

The fourth ZIP entry is this guide for reference. Reopen the correction page after uploading so it contains the current sale and discount revision fields. No public assets change in this patch. The package has been prepared locally and has not been uploaded to Hostinger.

The separate [different-product exchange patch](2026-10-04-exchange-different-products.md) remains the update for excluding original invoice products from exchange options.

## Verification

- All 131 unit tests passed with 21,518 assertions in in-memory SQLite.
- Tests cover the two-product 20% example, additional payment and payouts, previously returned cash change, fixed minimums, percentage caps, product/category scope, centavo allocation, incompatible/stale discounts, insufficient/changed stock, invalid quantities, administrator access, repeat submission and complete rollback when audit writing fails.
- Notes-only edits preserve historical item amounts even when discount settings have changed.
- PHP syntax checks passed for the three application files and updated test file.
- Thirty-two browser layouts passed at 320, 390, 768 and 1440 pixels in light/dark themes, including live totals, no-discount minimum warnings, settlement confirmation resets, reference requirements, empty-sale prevention and CSRF fields. Desktop/mobile screenshots were visually reviewed.

These checks did not change the configured application database, submit production corrections or verify native MySQL concurrent locking.
