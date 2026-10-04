# Different products for exchanges

Package: `build/hostinger-exchange-different-products-2026-10-04.zip`.

Install on the current project with the existing Product Exchange and permanent-delete-flags updates. No database change or SQL import is required.

## Behavior

The replacement list excludes every product ID recorded on the original invoice. This applies before entering return quantities, to products the customer keeps, and to items already returned. For an invoice containing Zinc 20mg Tablet and Tempra Syrup, neither product appears as a replacement, including in search results. They remain in the return section while there is quantity available to return.

Different products still come from the original selling branch and must have active, available, unexpired stock. Administrators and cashiers use the same rule. Review and final confirmation reject submitted original-product replacements, including a mixture of original and different products; a rejected exchange does not create refunds, sales or stock movements.

Search displays a clear message when no replacements match. If the branch has no eligible different products, the page explains that no different replacement products are available. Previously completed exchanges remain recorded.

## Upload to Hostinger

Back up and replace these three files together, keeping their project paths:

1. `app/Controllers/Cashier/ExchangeController.php`
2. `app/Libraries/ProductExchange.php`
3. `app/Views/cashier/sales/exchange.php`

The ZIP includes this deployment guide as its fourth file. Upload the three PHP files to the application folder; the guide is for reference. Reopen the exchange form and review your selections again after uploading. This package has been prepared locally and has not been uploaded to Hostinger.

The previously supplied `app (1).zip` differs from the current workspace in exactly these three files after this change. All 26 files in the supplied `assets.zip` still match; no asset replacement is needed for this patch.

## Verification

- All 121 unit tests passed with 21,431 assertions, using in-memory SQLite. These include original-product exclusion, kept and previously returned items, stale draft selections, server rejection for both roles, and unchanged records after rejected confirmation.
- Existing different-product exchange, discount minimum/cap, repeated exchange, return credit, settlement, stock and rollback tests passed.
- PHP syntax checks passed for all three application files and the updated test file.
- Forty browser layout checks passed at 320, 390, 768 and 1440 pixels in light/dark themes, along with selected-product search, no-match feedback, POST/CSRF and receipt printing checks.

These checks used local fixtures, not the live Hostinger site or production transactions.
